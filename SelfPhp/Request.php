<?php

namespace SelfPhp;

use SelfPhp\SP;
use SelfPhp\SPException;

/**
 * Class Request
 * 
 * Handles and provides access to various request data such as GET, POST, FILES, etc.
 */
class Request extends SP
{
    /**
     * @var object The GET request data.
     */
    public $get;

    /**
     * @var array The combined HTTP request data.
     */
    public $http_requests = [];

    /**
     * @var array Normalized files array.
     */
    private $normalizedFiles = [];

    /**
     * Constructor for the Request class.
     * 
     * Initializes the GET request data and sets up the combined HTTP request data.
     */
    public function __construct()
    {
        $this->get = $this->requests();
        $this->normalizeFiles();
    }

    /**
     * Capture input data by key.
     *
     * @param string $var
     * @param mixed $default
     * @return mixed
     */
    public function capture($key, $default = null)
    {
        return $this->get->$key ?? $default;
    }

    /**
     * Capture multiple input values by keys.
     *
     * @param array $keys
     * @return array
     */
    public function multicapture(array $keys): array
    {
        $captured = [];

        foreach ($keys as $key) {
            $captured[$key] = $this->capture($key);
        }

        return $captured;
    }

    /**
     * Capture every input on the request sent.
     *
     * Captures: JSON body, POST data, GET/query parameters
     *
     * @return array
     */
    public function captureAll(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        $data = $_GET;

        if (stripos($contentType, 'application/json') !== false) {
            $input = file_get_contents('php://input');
            $decoded = json_decode($input, true);

            if (is_array($decoded)) {
                // Merge JSON data with query params
                $data = array_merge($data, $decoded);
            }
        } elseif (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $data = array_merge($data, $_POST);
        }

        return $data;
    }

    /**
     * Get all uploaded files in normalized format.
     *
     * @param bool $includeEmpty Whether to include empty file uploads
     * @return array Normalized files array
     */
    public function files(bool $includeEmpty = false): array
    {
        return $includeEmpty ? $this->normalizedFiles : $this->filterEmptyFiles($this->normalizedFiles);
    }

    /**
     * Get files for a specific field.
     *
     * @param string $fieldName The name of the file input field
     * @param bool $includeEmpty Whether to include empty uploads
     * @return array|null Array of files or single file, null if field doesn't exist
     */
    public function file(string $fieldName, bool $includeEmpty = false)
    {
        if (!isset($this->normalizedFiles[$fieldName])) {
            return null;
        }

        $files = $this->normalizedFiles[$fieldName];

        if (!$includeEmpty) {
            $files = $this->filterEmptyFieldFiles($files);
        }

        return $files;
    }

    /**
     * Check if a file was uploaded for a specific field.
     *
     * @param string $fieldName The name of the file input field
     * @return bool
     */
    public function hasFile(string $fieldName): bool
    {
        if (!isset($this->normalizedFiles[$fieldName])) {
            return false;
        }

        $files = $this->filterEmptyFieldFiles($this->normalizedFiles[$fieldName]);
        return !empty($files);
    }

    /**
     * Check if any files were uploaded.
     *
     * @return bool
     */
    public function hasFiles(): bool
    {
        $filteredFiles = $this->filterEmptyFiles($this->normalizedFiles);
        return !empty($filteredFiles);
    }

    /**
     * Validate uploaded files against rules.
     *
     * @param array $rules Validation rules (optional)
     * @return array Validation results
     */
    public function validateFiles(array $rules = []): array
    {
        $validationRules = array_merge([
            'max_size' => 5242880, // 5MB
            'allowed_types' => ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'],
            'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'pdf'],
            'required' => false,
        ], $rules);

        $results = [];
        $filteredFiles = $this->filterEmptyFiles($this->normalizedFiles);

        foreach ($filteredFiles as $field => $fileOrFiles) {
            if ($this->isFileArray($fileOrFiles)) {
                // Multiple files for this field
                $results[$field] = [];
                foreach ($fileOrFiles as $key => $file) {
                    $results[$field][$key] = $this->validateSingleFile($file, $validationRules);
                }
            } else {
                // Single file for this field
                $results[$field] = $this->validateSingleFile($fileOrFiles, $validationRules);
            }
        }

        return $results;
    }

    /**
     * Move uploaded files to a destination directory.
     *
     * @param string $destination Directory path
     * @param callable|null $filenameCallback Optional callback to generate filenames
     * @return array Array of moved file paths
     * @throws \RuntimeException If directory cannot be created
     */
    public function moveFiles(string $destination, ?callable $filenameCallback = null): array
    {
        if (!is_dir($destination) && !mkdir($destination, 0755, true)) {
            throw new \RuntimeException("Cannot create directory: $destination");
        }

        $movedFiles = [];
        $filteredFiles = $this->filterEmptyFiles($this->normalizedFiles);

        foreach ($filteredFiles as $field => $fileOrFiles) {
            if ($this->isFileArray($fileOrFiles)) {
                // Multiple files
                $movedFiles[$field] = [];
                foreach ($fileOrFiles as $key => $file) {
                    $movedFiles[$field][$key] = $this->moveSingleFile($file, $destination, $filenameCallback);
                }
            } else {
                // Single file
                $movedFiles[$field] = $this->moveSingleFile($fileOrFiles, $destination, $filenameCallback);
            }
        }

        return $movedFiles;
    }

    /**
     * Move a single uploaded file.
     *
     * @param string $fieldName The field name
     * @param string $destination Directory path
     * @param string|null $newFilename Optional new filename (without extension)
     * @return string|null Path to moved file or null if failed
     */
    public function moveFile(string $fieldName, string $destination, ?string $newFilename = null): ?string
    {
        $file = $this->file($fieldName);
        if (!$file || !is_array($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        if (!is_dir($destination) && !mkdir($destination, 0755, true)) {
            throw new \RuntimeException("Cannot create directory: $destination");
        }

        if ($newFilename) {
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = $newFilename . '.' . $extension;
        } else {
            $filename = $this->generateSafeFilename($file['name']);
        }

        $destinationPath = rtrim($destination, '/') . '/' . $filename;

        if (move_uploaded_file($file['tmp_name'], $destinationPath)) {
            return $destinationPath;
        }

        return null;
    }

    /**
     * Get file extension from filename.
     *
     * @param string $fieldName
     * @return string|null
     */
    public function fileExtension(string $fieldName): ?string
    {
        $file = $this->file($fieldName);
        if (!$file || !is_array($file)) {
            return null;
        }

        return pathinfo($file['name'], PATHINFO_EXTENSION);
    }

    /**
     * Get file MIME type.
     *
     * @param string $fieldName
     * @return string|null
     */
    public function fileType(string $fieldName): ?string
    {
        $file = $this->file($fieldName);
        return $file['type'] ?? null;
    }

    /**
     * Get file size in bytes.
     *
     * @param string $fieldName
     * @return int|null
     */
    public function fileSize(string $fieldName): ?int
    {
        $file = $this->file($fieldName);
        return $file['size'] ?? null;
    }

    /**
     * Get human-readable file size.
     *
     * @param string $fieldName
     * @param int $precision
     * @return string|null
     */
    public function fileSizeFormatted(string $fieldName, int $precision = 2): ?string
    {
        $size = $this->fileSize($fieldName);
        if ($size === null) {
            return null;
        }

        return $this->formatBytes($size, $precision);
    }

    // ============================
    // PRIVATE HELPER METHODS
    // ============================

    /**
     * Normalize the $_FILES array on construction.
     */
    private function normalizeFiles(): void
    {
        if (empty($_FILES)) {
            $this->normalizedFiles = [];
            return;
        }

        foreach ($_FILES as $fieldName => $fileArray) {
            if (!is_array($fileArray['name'])) {
                // Single file
                $this->normalizedFiles[$fieldName] = $this->createFileInfo($fileArray);
            } else {
                // Multiple files
                $this->normalizedFiles[$fieldName] = $this->normalizeMultipleFiles($fileArray);
            }
        }
    }

    /**
     * Create standardized file info array.
     */
    private function createFileInfo(array $fileData): array
    {
        return [
            'name' => $fileData['name'] ?? '',
            'type' => $fileData['type'] ?? '',
            'tmp_name' => $fileData['tmp_name'] ?? '',
            'error' => $fileData['error'] ?? UPLOAD_ERR_NO_FILE,
            'size' => $fileData['size'] ?? 0,
            'extension' => pathinfo($fileData['name'] ?? '', PATHINFO_EXTENSION),
            'is_uploaded' => is_uploaded_file($fileData['tmp_name'] ?? ''),
            'uploaded_at' => time()
        ];
    }

    /**
     * Normalize multiple file uploads.
     */
    private function normalizeMultipleFiles(array $fileArray): array
    {
        $files = [];

        // Check if this is a multi-dimensional array
        if (is_array($fileArray['name'][0])) {
            // Nested arrays: files[images][0], files[images][1]
            foreach ($fileArray['name'] as $firstKey => $firstArray) {
                foreach ($firstArray as $secondKey => $name) {
                    $fileData = [
                        'name' => $name,
                        'type' => $fileArray['type'][$firstKey][$secondKey] ?? '',
                        'tmp_name' => $fileArray['tmp_name'][$firstKey][$secondKey] ?? '',
                        'error' => $fileArray['error'][$firstKey][$secondKey] ?? UPLOAD_ERR_NO_FILE,
                        'size' => $fileArray['size'][$firstKey][$secondKey] ?? 0,
                    ];

                    $files[$firstKey][$secondKey] = $this->createFileInfo($fileData);
                }
            }
        } else {
            // Simple array: files[0], files[1]
            foreach ($fileArray['name'] as $index => $name) {
                $fileData = [
                    'name' => $name,
                    'type' => $fileArray['type'][$index] ?? '',
                    'tmp_name' => $fileArray['tmp_name'][$index] ?? '',
                    'error' => $fileArray['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                    'size' => $fileArray['size'][$index] ?? 0,
                ];

                $files[$index] = $this->createFileInfo($fileData);
            }
        }

        return $files;
    }

    /**
     * Filter out empty file uploads from normalized files.
     */
    private function filterEmptyFiles(array $files): array
    {
        $filtered = [];

        foreach ($files as $field => $fileOrFiles) {
            $filteredField = $this->filterEmptyFieldFiles($fileOrFiles);
            if (!empty($filteredField)) {
                $filtered[$field] = $filteredField;
            }
        }

        return $filtered;
    }

    /**
     * Filter empty uploads from a field's files.
     */
    private function filterEmptyFieldFiles($files)
    {
        if (!is_array($files)) {
            return $files;
        }

        // Check if it's a file info array
        if (isset($files['tmp_name'])) {
            return $files['error'] === UPLOAD_ERR_NO_FILE ? null : $files;
        }

        // It's an array of files
        $filtered = [];
        foreach ($files as $key => $file) {
            if (isset($file['tmp_name']) && $file['error'] !== UPLOAD_ERR_NO_FILE) {
                $filtered[$key] = $file;
            } elseif (is_array($file)) {
                $nestedFiltered = $this->filterEmptyFieldFiles($file);
                if (!empty($nestedFiltered)) {
                    $filtered[$key] = $nestedFiltered;
                }
            }
        }

        return $filtered;
    }

    /**
     * Check if array is a file array or array of files.
     */
    private function isFileArray($data): bool
    {
        if (!is_array($data)) {
            return false;
        }

        // If it has 'tmp_name' key, it's a single file
        if (isset($data['tmp_name'])) {
            return false;
        }

        // Otherwise it's an array of files
        return true;
    }

    /**
     * Validate a single file.
     */
    private function validateSingleFile(array $file, array $rules): array
    {
        $errors = [];

        // Check upload error
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = $this->getUploadErrorMessage($file['error']);
        }

        // Check file size
        if ($file['size'] > $rules['max_size']) {
            $errors[] = sprintf(
                'File size (%s) exceeds maximum allowed size (%s)',
                $this->formatBytes($file['size']),
                $this->formatBytes($rules['max_size'])
            );
        }

        // Check MIME type
        if (!empty($rules['allowed_types']) && !in_array($file['type'], $rules['allowed_types'])) {
            $errors[] = sprintf('File type %s is not allowed', $file['type']);
        }

        // Check extension
        $extension = strtolower($file['extension']);
        if (!empty($rules['allowed_extensions']) && !in_array($extension, $rules['allowed_extensions'])) {
            $errors[] = sprintf('File extension .%s is not allowed', $extension);
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'file' => $file
        ];
    }

    /**
     * Move a single file to destination.
     */
    private function moveSingleFile(array $file, string $destination, ?callable $filenameCallback = null): ?string
    {
        if ($file['error'] !== UPLOAD_ERR_OK || !$file['is_uploaded']) {
            return null;
        }

        // Generate filename
        if ($filenameCallback) {
            $filename = $filenameCallback($file);
        } else {
            $filename = $this->generateSafeFilename($file['name']);
        }

        $destinationPath = rtrim($destination, '/') . '/' . $filename;

        if (move_uploaded_file($file['tmp_name'], $destinationPath)) {
            return $destinationPath;
        }

        return null;
    }

    /**
     * Generate safe filename.
     */
    private function generateSafeFilename(string $originalName): string
    {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $basename = pathinfo($originalName, PATHINFO_FILENAME);

        // Clean basename
        $safeBasename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $basename);
        $safeBasename = substr($safeBasename, 0, 100);

        // Add timestamp for uniqueness
        $timestamp = time();

        return $safeBasename . '_' . $timestamp . '.' . $extension;
    }

    /**
     * Get upload error message.
     */
    private function getUploadErrorMessage(int $errorCode): string
    {
        $messages = [
            UPLOAD_ERR_OK => 'No error',
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive in php.ini',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive in HTML form',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension',
        ];

        return $messages[$errorCode] ?? 'Unknown upload error';
    }

    /**
     * Format bytes to human readable format.
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Retrieves the application configuration.
     * 
     * @return mixed The application configuration.
     */
    public function appConfig()
    {
        $request = $this->request_config("app");
        return $request;
    }

    /**
     * Sets up the combined HTTP request data.
     * 
     * @return object The combined HTTP request data.
     */
    public function requests()
    {
        $requestObject = $this->set_http_requests();
        return (object) $requestObject;
    }

    /**
     * Retrieves the application configuration and combines various request arrays.
     * 
     * @return array The combined HTTP request data.
     */
    public function set_http_requests()
    {
        $app_configurations = $this->appConfig();

        if (isset($_SERVER['REQUEST_METHOD'])) {
            if (isset($_POST)) {
                $this->combine_req_array_values([$app_configurations, $_POST]);
            }

            if (isset($_GET)) {
                $this->combine_req_array_values([$app_configurations, $_GET]);
            }

            if (isset($_FILES)) {
                $multipleArrayDetected = false;
                foreach ($_FILES as $file) {
                    if (is_array($file['name'])) {
                        $multipleArrayDetected = true;
                        break;
                    }
                }

                if ($multipleArrayDetected) {
                    if (count($_FILES) > 1) {
                        $files = (object) $_FILES[current(array_keys($_FILES))];

                        $this->combine_req_array_values([
                            $app_configurations,
                            [
                                current(array_keys($_FILES)) => $files
                            ]
                        ]);
                    } else {
                        $fileObject = [];
                        foreach ($_FILES as $fileArray) {
                            $fileObject['name'] = $fileArray['name'];
                            $fileObject['type'] = $fileArray['type'];
                            $fileObject['tmp_name'] = $fileArray['tmp_name'];
                            $fileObject['error'] = $fileArray['error'];
                            $fileObject['size'] = $fileArray['size'];
                        }

                        $fileObject = (object) $fileObject;
                        $this->combine_req_array_values([
                            $app_configurations,
                            [current(array_keys($_FILES)) => $fileObject]
                        ]);
                    }
                } else {
                    $this->combine_req_array_values([$app_configurations, $_FILES]);
                }
            }

            if (isset($_REQUEST)) {
                $this->combine_req_array_values([$app_configurations, $_REQUEST]);
            }

            if (isset($_SERVER)) {
                $this->combine_req_array_values([$app_configurations, $_SERVER]);
            }

            if (isset($_ENV)) {
                $this->combine_req_array_values([$app_configurations, $_ENV]);
            }
        }

        return $this->http_requests;
    }

    /**
     * Combines values from multiple arrays into a single array.
     * 
     * @param array $multi_dim_array An array containing arrays to be combined.
     * @return void
     */
    public function combine_req_array_values(array $multi_dim_array)
    {
        foreach ($multi_dim_array as $key => $array) {
            foreach ($array as $sub_key => $sub_value) {
                $this->http_requests[$sub_key] = $sub_value;
            }
        }
    }
}
