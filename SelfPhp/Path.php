<?php

namespace SelfPhp;

use AltoRouter;
use SelfPhp\Request;
use SelfPhp\SP;
use SelfPhp\Auth;

/**
 * Custom Path class extending AltoRouter
 * 
 * Handles routing and controller execution based on specified paths.
 */
class Path extends AltoRouter
{
    /**
     * @var string|null The controller to be executed.
     */
    public $controller;

    /**
     * @var string|null The callable function/method within the controller.
     */
    public $callable_function;

    /**
     * Constructor for the Path class.
     * 
     * @param string|null $controller The controller to be executed.
     * @param string|null $callable_function The callable function/method within the controller.
     */
    public function __construct($controller = null, $callable_function = null)
    {
        // Start session if not already active
        ($this->is_session_active() == true) ? null : session_start();

        $this->controller = $controller;
        $this->callable_function = $callable_function;
    }

    /**
     * Checks if the session is active.
     * 
     * @return bool Returns true if the session is active, false otherwise.
     */
    public function is_session_active()
    {
        // Check if running in a web environment
        if (php_sapi_name() !== 'cli') {
            // Check PHP version for session status
            if (version_compare(phpversion(), '5.4.0', '>=')) {
                return session_status() === PHP_SESSION_ACTIVE ? true : false;
            } else {
                return session_id() === '' ? false : true;
            }
        }
        return false;
    }

    /**
     * Instantiates a class with dynamic parameters.
     * 
     * @param string $controller The controller to be instantiated.
     * @return object|null The instantiated controller object or null if not found.
     */
    public function classInstantiationHelper($controller)
    {
        $controllerInstance = null;

        if (class_exists($controller)) {
            $reflectionClass = new \ReflectionClass($controller);

            if ($reflectionClass->hasMethod('__construct')) {
                $constructor = $reflectionClass->getMethod('__construct');
                $params = [];

                foreach ($constructor->getParameters() as $param) {
                    if ($param->hasType()) {
                        $typeName = $param->getType()->getName();
                        if ($param->getType()->isBuiltin()) {
                            $params[] = $typeName == 'int' ? 0 : '';
                        } else {
                            $params[] = new $typeName();
                        }
                    } else {
                        $params[] = null;
                    }
                }

                // Create an instance with dynamic parameters
                $controllerInstance = $reflectionClass->newInstanceArgs($params);
            } else {
                // Create an instance without parameters
                $controllerInstance = new $controller();
            }
        }

        return $controllerInstance;
    }

    /**
     * Instantiates a class method with dynamic parameters.
     * 
     * @param string $controller The controller to be instantiated.
     * @param object $controllerInstance The instantiated controller object.
     * @param string $callable_function The callable function/method within the controller.
     * @return mixed The response from the controller.
     */
    public function classMethodInstantiationHelper($controller, $controllerInstance, $callable_function)
    {
        $reflectionClass = new \ReflectionClass($controller);

        $reflectionMethod = $reflectionClass->getMethod($callable_function);

        $params = [];

        foreach ($reflectionMethod->getParameters() as $param) {
            if ($param->hasType()) {
                $typeName = $param->getType()->getName();
                if ($param->getType()->isBuiltin()) {
                    $params[] = $typeName == 'int' ? 0 : '';
                } else {
                    $params[] = new $typeName();
                }
            } else {
                if ($param->getName() == 'id') {
                    $params[] = isset((new Request())->get->id) ? (new Request())->get->id : null;
                }
                $params[] = null;
            }
        }

        return $reflectionMethod->invokeArgs($controllerInstance, $params);
    }

    /**
     * Static method to handle routing and controller execution.
     * 
     * @param string $controller The controller to be executed.
     * @param string $callable_function The callable function/method within the controller.
     * @return void
     */
    public static function route($controller, $callable_function)
    {
        try {
            $path = new Path();

            $sp = new SP();

            SP::requestHelperFunctions("Helper");

            $sp->verify_domain_format(env("APP_DOMAIN"));

            $route = $path->controller_path($controller);

            if (isset($route)) {
                require $route;
            } else {
                throw new \Exception($controller . " Controller not found");
            }

            $config = $sp->setup_config();

            $path->setUpCommonApplicationConfigurations($config);

            $controllerInstance = $path->classInstantiationHelper($controller);

            $response = $path->classMethodInstantiationHelper($controller, $controllerInstance, $callable_function);

            $data = null;

            // Return data from backend to frontend    
            if (isset($response)) {
                $data = isset($response['controller_response_data'])
                    ? $response['controller_response_data']
                    : $response;
            }

            if (isset($response['view_url'])) {
                if (file_exists($response['view_url'])) {
                    echo $sp->fileParser($data, $response['view_url']);
                    (new Path())->unsetSession();
                    exit();
                } else {
                    throw new \Exception("View path could not be found. You might have deleted the view, or the view path is incorrect.");
                }
            } else {
                (new Path())->alternative_callable_method_response($response, $sp);
                (new Path())->unsetSession();
            }
        } catch (\Exception $th) {
            echo $th->getMessage();
        }
    }

    /**
     * Handles an alternative response when the controller response is not a view.
     * 
     * @param mixed $controllerResponse The response from the controller.
     * @param SP $sp The SP instance for utility functions.
     * @return void
     */
    public function alternative_callable_method_response($controllerResponse, $sp)
    {
        if (is_array($controllerResponse)) {
            if (count($controllerResponse) > 0) {
                echo $sp->serve_json($controllerResponse);
                exit();
            }
        }
    }

    /**
     * Sets up common application configurations.
     * 
     * @param array $config The application configuration array.
     * @return void
     */
    public function setUpCommonApplicationConfigurations($config)
    {
        // Set the default timezone
        // check if timezone is correct
        if ($this->isValidTimezone($config['TIMEZONE'])) {
            date_default_timezone_set($config['TIMEZONE']);
        } else {
            throw new \Exception("Timezone must be in Continent/City format");
        }

        // Set the default locale
        // check if locale is correct
        if (preg_match("/^[a-z]{2}_[A-Z]{2}$/", $config['LOCALE'])) {
            setlocale(LC_ALL, $config['LOCALE']);
        } else {
            throw new \Exception("Locale must be in ISO 639-1 format");
        }

        // Set the default character encoding 
        if ($this->isValidCharset($config['CHARACTER_ENCODING'])) {
            mb_internal_encoding($config['CHARACTER_ENCODING']);
        } else {
            throw new \Exception("Character encoding must be in ISO 8859-1 format");
        }

        // Set the default language
        // check if language is rhymes with ISO 639-1
        if (preg_match("/^[a-z]{2}$/", $config['LANGUAGE'])) {
            mb_language($config['LANGUAGE']);
        } else {
            throw new \Exception("Language must be in ISO 639-1 format");
        }

        // Set the default currency
        // check if currency is rhymes with ISO 4217
        if (preg_match("/^[A-Z]{3}$/", $config['CURRENCY'])) {
            setlocale(LC_MONETARY, $config['CURRENCY']);
        } else {
            throw new \Exception("Currency must be in ISO 4217 format");
        }

        // Override server configurations if they are set and not null
        if ((isset($config['DISPLAY_ERRORS']) && trim(strtolower($config['DISPLAY_ERRORS'])) == 'on') || (strtolower(env("DEBUG")) == "true")) {
            ini_set('display_errors', $config['DISPLAY_ERRORS']);
        }

        if (isset($config['MAX_EXECUTION_TIME'])) {
            ini_set('max_execution_time', $config['MAX_EXECUTION_TIME']);
        }

        if (isset($config['MAX_INPUT_TIME'])) {
            ini_set('max_input_time', $config['MAX_INPUT_TIME']);
        }

        if (isset($config['MAX_INPUT_VARS'])) {
            ini_set('max_input_vars', $config['MAX_INPUT_VARS']);
        }

        if (isset($config['MEMORY_LIMIT'])) {
            ini_set('memory_limit', $config['MEMORY_LIMIT']);
        }

        if (isset($config['POST_MAX_SIZE'])) {
            ini_set('post_max_size', $config['POST_MAX_SIZE']);
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            if (isset($config['SESSION_LIFETIME'])) {
                ini_set('session.gc_maxlifetime', $config['SESSION_LIFETIME']);
            }

            if (isset($config['SESSION_SAVE_PATH'])) {
                ini_set('session.save_path', $config['SESSION_SAVE_PATH']);
            }
        }

        if (isset($config['UPLOAD_MAX_FILESIZE'])) {
            ini_set('upload_max_filesize', $config['UPLOAD_MAX_FILESIZE']);
        }

        if (isset($config['ZLIB_OUTPUT_COMPRESSION'])) {
            ini_set('zlib.output_compression', $config['ZLIB_OUTPUT_COMPRESSION']);
        }
    }

    function isValidTimezone($timezone)
    {
        // Get a list of supported timezones
        $supportedTimezones = \DateTimeZone::listIdentifiers();

        // Check if the provided timezone is in the list of supported timezones
        if (in_array($timezone, $supportedTimezones)) {
            return true;
        } else {
            return false;
        }
    }

    function isValidCharset($charset)
    {
        // Get a list of supported charsets// Define the pattern for charset validation
        $pattern = '/^[A-Za-z0-9_\-]+$/';

        // Check if the provided charset matches the pattern
        return (bool) preg_match($pattern, $charset);
    }

    /**
     * Unsets the controller response session.
     */
    public function unsetSession()
    {
        if (isset($_SESSION['controller_response_data'])) {
            unset($_SESSION['controller_response_data']);
        }
    }

    /**
     * Retrieves the path of the specified controller.
     * 
     * @param string $controller The name of the controller.
     * @return string|null The path to the controller file or null if not found.
     */
    public function controller_path($controller)
    {
        $config = (new SP())->request_config('app');

        // $controllerPath = $GLOBALS['controllerPath'];
        $controllerPath = $config['CONTROLLER_PATH'];

        $controller_found_array = array();

        foreach ($controllerPath as $controller_folder) {
            $controller_path = glob($controller_folder . DIRECTORY_SEPARATOR . $controller . '.php');

            if (count($controller_path) > 0) {
                array_push($controller_found_array, $controller_path);
            }
        }

        if (isset($controller_found_array[0][0]) && !empty($controller_found_array[0][0])) {
            return $controller_found_array[0][0];
        } else if (isset($controller_found_array[1][0]) && !empty($controller_found_array[1][0])) {
            return $controller_found_array[1][0];
        } else {
            return null;
        }
    }
}
