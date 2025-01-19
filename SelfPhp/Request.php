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
     * Constructor for the Request class.
     * 
     * Initializes the GET request data and sets up the combined HTTP request data.
     */
    public function __construct()
    {
        $this->get = $this->requests();
    }

    /**
     * Capture input data by key.
     *
     * @param string $var
     * @param mixed $default
     * @return mixed
     */
    public function capture($key, $default=null) {
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
