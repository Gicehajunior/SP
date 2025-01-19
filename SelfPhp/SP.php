<?php

namespace SelfPhp;

use SelfPhp\SPException; 
use SelfPhp\TemplatingEngine\SPTemplateEngine;

/**
 * The SP class acts as the main controller for the entire application, handling 
 * resources, asset management, and serving as the base for controllers and models.
 *
 * @copyright  2022 SelfPHP Framework Technology
 * @license    https://github.com/Gicehajunior/selfphp-framework/blob/main/LICENSE
 * @version    Release: 1.0.9
 * @link       https://github.com/Gicehajunior/selfphp-framework/blob/main/config/SP.php
 * @since      Class available since Release 1.1.0
 */
class SP
{
    /**
     * Holds the application configurations.
     *
     * @var object
     */
    public $app;

    /**
     * Initializes the SP class, loading application configurations.
     *
     * @return void
     */
    public function __construct()
    {
        $this->app = (Object) $this->request_config("app");
    }

    public static function requestHelperFunctions($helper)
    {
        $helper = ucfirst(strtolower($helper));
        require __DIR__ . DIRECTORY_SEPARATOR . $helper . '.php';
    }

    /**
     * Requests and returns a specified configuration file.
     *
     * @param string $config The configuration file to request.
     * @return mixed The requested configuration file.
     */
    public function request_config($config)
    {
        $config = ucfirst(strtolower($config));
        $config_file = require getcwd() . DIRECTORY_SEPARATOR . "config/" . $config . '.php';
        return $config_file;
    }

    /**
     * Returns a JSON-encoded representation of an array.
     *
     * @param array $data The data to be encoded.
     * @return string JSON-encoded data.
     */
    public function serve_json(array $data)
    {
        return json_encode($data);
    }

    /**
     * Returns the application database configurations.
     *
     * @return object The application database configurations.
     */
    public function getAppDbConfigurations()
    {
        $db_config = $this->request_config("Database");
        return $db_config;
    }

    /**
     * Set up configurations.
     *
     * @return array
     */
    public function setup_config()
    {
        $config_1 = $this->request_config("config");
        $config_2 = $this->request_config("app");

        $config = array_merge($config_1, $config_2);

        return $config;
    }

    public function config($key)
    {
        $config = $this->setup_config();

        return isset($config[$key]) ? $config[$key] : null;
    }

    /**
     * Retrieves the value of an environment variable.
     *
     * @param string $var_name The name of the environment variable.
     * @return mixed The value of the environment variable.
     */
    public function env($var_name)
    {
        return isset($_ENV[strtoupper($var_name)]) ? $_ENV[strtoupper($var_name)] : '{{ ' . $var_name . " is not set in the .env file. }}";
    }

    /**
     * Determine if debugging mode is enabled.
     * 
     * - First checks the application configuration ("DEBUG").
     * - If the configuration is explicitly set to `true`, debugging is enabled.
     * - If the configuration is explicitly set to `false`, it falls back to checking the environment variable ("DEBUG").
     * - Defaults to `false` if neither the configuration nor the environment variable enables debugging.
     * 
     * @return bool True if debugging is enabled, false otherwise.
     */
    public function debugMode()
    { 
        $debug = $this->config("DEBUG");

        // If the config explicitly enables debugging, return true
        if ($debug === true) {
            return true;
        }

        // If the config disables debugging, check the environment variable
        if ($debug === false && env("DEBUG") === true) {
            return true;
        }

        return false;
    }

    /**
     * Gets the application name.
     *
     * @return string The application name.
     */
    public function app_name()
    {
        $app_name = $this->env("APP_NAME");

        if (isset($app_name) && !empty($app_name) && $app_name !== "{{ APP_NAME is not set in the .env file. }}") {
            return $this->env("APP_NAME");
        } else {
            return $this->app->APP_NAME;
        }
    }

    /**
     * Retrieves the application domain.
     *
     * @return string|null The application domain.
     */
    public function domain()
    {
        return isset($this->app->APP_DOMAIN) ? $this->app->APP_DOMAIN : null;
    }
    /**
     * Retrieves the login page name.
     *
     * @return string|null The login page name.
     */
    public function login_page()
    {
        return isset($this->app->AUTHPAGE) ? $this->app->AUTHPAGE : null;
    }

    /**
     * Retrieves the dashboard page name.
     *
     * @return string|null The dashboard page name.
     */
    public function dashboard_page()
    {
        return isset($this->app->HOMEPAGE) ? $this->app->HOMEPAGE : null;
    }

    /**
     * Verifies the format of the provided domain.
     *
     * @param string|null $domain The domain to be verified.
     * @throws SPException if the domain format is invalid.
     * @return string|null The verified domain.
     */
    public function verify_domain_format($domain = null)
    {
        if ($domain !== null) {
            if (strpos($domain, "http://") == false || strpos($domain, "https://") == false) {
                return $domain;
            }

            throw new SPException("DomainFormatException: Domain must be in the format of http:// or https://");
        }
    }

    /**
     * Constructs the public path by appending the path to the application domain.
     *
     * @param string|null $path The path to be appended.
     * @return string The constructed public path.
     */
    public function public_path($path = null)
    {
        $path = ($this->env("APP_DOMAIN") ? $this->env("APP_DOMAIN") : $this->domain()) . DIRECTORY_SEPARATOR . $this->app->PUBLIC_PATH . DIRECTORY_SEPARATOR . $path;
        return $path;
    }

    /**
     * Constructs the asset path by appending the path to the application domain.
     *
     * @param string|null $path The path to be appended.
     * @return string The constructed asset path.
     */
    public function asset_path($path = null)
    {
        $path = ($this->env("APP_DOMAIN") ? $this->env("APP_DOMAIN") : $this->domain()) . DIRECTORY_SEPARATOR . $this->app->PUBLIC_PATH . DIRECTORY_SEPARATOR . $path;
        return $path;
    }

    /**
     * Constructs the storage path by appending the path to the application domain.
     *
     * @param string|null $path The path to be appended.
     * @return string The constructed storage path.
     */
    public function storage_path($path = null)
    {
        $path = ($this->env("APP_DOMAIN") ? $this->env("APP_DOMAIN") : $this->domain()) . DIRECTORY_SEPARATOR . $this->app->STORAGE_PATH . DIRECTORY_SEPARATOR . $path;
        return $path;
    }

    /**
     * Requires and parses a view file, providing the data to be used.
     *
     * @param string $view The name of the view file.
     * @param array $data The data to be used in the view.
     * @return string The parsed view content.
     * @throws SPException if the view file is not found.
     */
    public function resource($view, $data = [])
    {
        $fileArray = array();

        if (empty($this->app)) {
            $this->app = (Object) $this->request_config("app");
        }

        $resourcePath = getcwd() . DIRECTORY_SEPARATOR . $this->app->RESOURCE_VIEWS_DIRECTORY;

        $files = $this->scanDirectory($resourcePath);

        $endName = null;
        $fileName = null;

        $viewPathArray = explode(".", $view);

        if (strtolower(end($viewPathArray)) == "partial") {
            $endName .= end($viewPathArray);

            array_pop($viewPathArray);

            $fileName .= end($viewPathArray);

            array_pop($viewPathArray);
        } else {
            $endName = null;

            $fileName .= end($viewPathArray);

            array_pop($viewPathArray);
        }

        $dynamicPath = null;

        foreach ($viewPathArray as $key => $viewPath) {
            $dynamicPath .= $viewPath . DIRECTORY_SEPARATOR;
        }

        $fileMatchArray = array();
        foreach ($files as $key => $folder) {
            $file = glob($folder . DIRECTORY_SEPARATOR . $dynamicPath . $fileName . (($endName == null) ? null : ("." . $endName)) . ".php");

            if (count($file) > 0) {
                array_push($fileMatchArray, $file);
            }
        }

        $includedFilePath = (isset($fileMatchArray[0])) ? array_unique($fileMatchArray[0]) : null;

        if (!empty($includedFilePath)) {
            $includedFile = current($includedFilePath);

            if (count($data) > 0) {
                $_SESSION['controller_response_data'] = $data;
            }

            $controllerParsedData = isset($_SESSION['controller_response_data']) ? $_SESSION['controller_response_data'] : null;

            if (is_array($controllerParsedData)) {
                if (count($controllerParsedData) > 0) {
                    foreach ($controllerParsedData as $key => $value) {
                        $data[$key] = $value;
                    }
                }
            }

            return $this->fileParser($data, $includedFile);
        } else {
            throw new SPException("FileNotFoundException: " . $view . ' could not be found.');
        }
    }

    /**
     * Scans a directory and returns an array of file paths.
     *
     * @param string $resource_path The path to the directory to be scanned.
     * @return array An array of file paths.
     */
    public function scanDirectory($resourcePath)
    {
        $files = array();
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($resourcePath),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                array_push($files, $file->getRealpath());
            }
        }

        return $files;
    }

    /**
     * Parses HTML/PHP files with post data.
     *
     * @param array $data The data to be used in the parsed file.
     * @param string|null $filename The name of the file to be parsed.
     * @return string|false The parsed file content.
     */
    public function fileParser($data = [], $filename = null)
    {

        // If the data is an array and is empty, 
        // then the data is set to the session data.
        // Otherwise, the session data is set to the data. 
        // By doing so, this will distribute the data to the extended pages.
        if (is_array($data) && count($data) == 0) {
            $data = isset($_SESSION['controller_response_data']) ? $_SESSION['controller_response_data'] : null;
        } else {
            $_SESSION['controller_response_data'] = $data;
        }

        // Perform the extraction of the data, and require 
        // the full page respectively. 
        if (is_file($filename)) {
            if (is_array($data) && count($data) > 0) {
                extract($data);
            }

            ob_start();

            require ($filename);

            $htmlcontent = ob_get_clean();

            $SPTemplatingEngine = new SPTemplateEngine($htmlcontent);
            $SPTemplatingEngine->assignArray($data);

            // Return the parsed template content.
            return $SPTemplatingEngine->render();
        }

        return false;
    }

    /**
     * Converts CSV file data to an associative array.
     *
     * @param string $filepath The path to the CSV file.
     * @param int $MAX_LENGTH The maximum number of rows to read from the CSV file.
     * @return array|null An associative array representing the CSV data.
     */
    public static function csvToArray($filepath, $maxLength = 1000)
    {
        $csv = array();

        try {
            $count = 0;
            $reader = fopen($filepath, "r");

            if ($reader !== false) {
                $headerCellValues = fgetcsv($reader);
                $headerColumnCount = count($headerCellValues);

                while (!feof($reader)) {
                    $row = fgetcsv($reader);

                    if ($row !== false && !empty(array_filter($row))) {
                        $count++;
                        $rowColumnCount = count($row);

                        if ($rowColumnCount == $headerColumnCount) {
                            $entry = array_combine($headerCellValues, $row);
                            $csv[] = $entry;
                        } else {
                            return null;
                        }

                        if ($count == $maxLength) {
                            break;
                        }
                    }
                }
                fclose($reader);
            }

            return $csv;
        } catch (SPException $th) {
            return [
                'status' => 'error',
                'message' => $th
            ];
        }
    }

    /**
     * Processes the name of an uploaded file.
     *
     * @param string $fileName The original file name.
     * @param bool $autorename Whether to auto-rename the file if a duplicate exists.
     * @param string|null $custom_name A custom name for the file (optional).
     * @return string Processed file name.
     */
    public function uploads_name_proprocessor(string $fileName, bool $autorename, ?string $custom_name = null): string
    { 
        if ($custom_name !== null) {
            $fileName = $custom_name;
        }
        
        $fileName = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $fileName);

        if ($autorename) {
            $fileName = $this->autoRenameFile($fileName);
        }

        return $fileName;
    }

    /**
     * Generates a unique file name.
     *
     * @param string $fileName The original file name.
     * @return string Unique file name.
     */
    private function autoRenameFile(string $fileName): string
    {
        $fileInfo = pathinfo($fileName);
        $uniqueId = uniqid('', true); // Generate a unique identifier
        $timestamp = time();

        // Combine original name, unique ID, and extension
        $uniqueFileName = $fileInfo['filename'] . '_' . $uniqueId . '_' . $timestamp;

        // Add the file extension if it exists
        if (!empty($fileInfo['extension'])) {
            $uniqueFileName .= '.' . $fileInfo['extension'];
        }

        return $uniqueFileName;
    }

    /**
     * Moves and stores a file in the application's storage directory.
     *
     * @param object $fileMetadata The metadata of the file.
     * @param string $path The storage path for the file.
     * @return string The final destination path of the stored file.
     */
    public static function storageAdd($fileMetadata, $path, $autorename=false, $custom_name=null)
    {
        $config = (Object) self::request_config("app");

        try {
            $baseStoragePath = getcwd() . DIRECTORY_SEPARATOR . $config->STORAGE_PATH;
            if (substr($path, 1) === "/") {
                $storagePath = $baseStoragePath . $path;
            } else {
                $storagePath = $baseStoragePath . DIRECTORY_SEPARATOR . $path;
            }

            if (!file_exists($storagePath)) {
                mkdir($storagePath, 0777, true);
            }

            if (isset($fileMetadata->name) && is_array($fileMetadata->name)) {
                $totalFiles = count($fileMetadata->name);

                $output = [];

                // Loop through each uploaded file
                for ($i = 0; $i < $totalFiles; $i++) {
                    $fileName = $fileMetadata->name[$i];
                    $fileTmp = $fileMetadata->tmp_name[$i];
                    $fileSize = $fileMetadata->size[$i];
                    $fileError = $fileMetadata->error[$i];
                    $fileType = $fileMetadata->type[$i];
                    
                    $fileName = $this->uploads_name_proprocessor($fileName, $autorename, $custom_name);

                    // Move the uploaded file to the storage path.
                    if (substr($storagePath, -1) === "/") {
                        $currentUpload = $storagePath . $fileName;
                    } else {
                        $currentUpload = $storagePath . DIRECTORY_SEPARATOR . $fileName;
                    }

                    // If the file exists on path, delete it, and replace it with the new one.
                    if (file_exists($currentUpload)) {
                        unlink($currentUpload);
                    }

                    $fileDestination = $currentUpload;
                    move_uploaded_file($fileTmp, $fileDestination);

                    array_push($output, $fileDestination);
                }
            } else {
                if (is_object($fileMetadata)) {
                    $fileName = $fileMetadata->name;
                    $fileTmp = $fileMetadata->tmp_name;
                    $fileSize = $fileMetadata->size;
                    $fileError = $fileMetadata->error;
                    $fileType = $fileMetadata->type;
                } else {
                    $fileName = $fileMetadata['name'];
                    $fileTmp = $fileMetadata['tmp_name'];
                    $fileSize = $fileMetadata['size'];
                    $fileError = $fileMetadata['error'];
                    $fileType = $fileMetadata['type'];
                }

                $fileName = $this->uploads_name_proprocessor($fileName, $autorename, $custom_name);

                // Move the uploaded file to the storage path.
                if (substr($storagePath, -1) === "/") {
                    $currentUpload = $storagePath . $fileName;
                } else {
                    $currentUpload = $storagePath . DIRECTORY_SEPARATOR . $fileName;
                }

                // If the file exists on path, delete it, and replace it with the new one.
                if (file_exists($currentUpload)) {
                    unlink($currentUpload);
                }

                $fileDestination = $currentUpload;
                move_uploaded_file($fileTmp, $fileDestination);

                $output = $fileDestination;
            }

            return $output;
        } catch (SPException $th) {
            return $th;
        }
    }

    /**
     * Initializes SQL debugging based on the DEBUG environment variable.
     *
     * @param \mysqli $db_connection The database connection object.
     * @throws SPException if DEBUG is set to true and there is a MySQL error.
     */
    public static function initSqlDebug($dbConnection = null)
    {
        if (!empty(self::env('DEBUG'))) {
            if (strtolower(self::env('DEBUG')) == 'true') {
                throw new SPException(mysqli_error($dbConnection));
            }
        }
    }

    /**
     * Shows debug backtrace based on the DEBUG environment variable.
     *
     * @param string|null $exception The exception message to be thrown.
     * @throws SPException if DEBUG is set to true and there is an exception.
     */
    public static function debugBacktraceShow($exception = null)
    {
        if (!empty(self::env('DEBUG'))) {
            if (strtolower(self::env('DEBUG')) == 'true') {
                if (!empty($exception)) {
                    throw new SPException($exception);
                }
            }
        }
    }
}
