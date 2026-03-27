<?php

namespace SelfPhp;

use AltoRouter;
use SelfPhp\Request;
use SelfPhp\SP;
use SelfPhp\Auth;
use SelfPhp\SPException;
use function view;

/**
 * Class Route
 * 
 * Handles routing for various HTTP methods and calls the associated controllers.
 * Supports both string-based and array-based controller definitions with middleware.
 */
class Route extends AltoRouter
{
    /**
     * @var array The routes to be registered.
     */
    public $routes;

    /**
     * @var array The controller array containing controller and method names.
     */
    public static $controller_array;

    /**
     * @var array The middlewares to be applied to the current route.
     */
    public static $route_middlewares = [];

    /**
     * @var string|null The controller to be executed.
     */
    public $controller;

    /**
     * @var string|null The callable function/method within the controller.
     */
    public $callable_function;

    protected static ?self $routerInstance = null;

    /**
     * Constructor for the Route class.
     * 
     * @param string|null $controller The controller to be executed.
     * @param string|null $callable_function The callable function/method within the controller.
     * @param array $middlewares The middlewares to be applied on the controllers.
     */
    public function __construct($controller = null, $callable_function = null, array $middlewares = [])
    {
        // Start session if not already active
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }        

        $this->controller = $controller;
        $this->callable_function = $callable_function;
        
        // Call parent constructor if needed
        parent::__construct();
    }

    /**
     * Parses the controller definition which can be either a string or an array.
     * 
     * @param string|array $controller The controller definition.
     * @param string|array $middlewares The middlewares definition.
     * @return array Returns an array with 'controller', 'method', and 'middlewares'
     */
    private static function parseControllerDefinition(string|array $controller, array $middlewares): array
    {
        $result = [
            'controller' => null,
            'method' => null,
            'middlewares' => []
        ];

        if (is_string($controller)) {
            // Handle string format: "Controller@method"
            $parts = explode('@', $controller);
            $result['controller'] = $parts[0];
            $result['method'] = $parts[1] ?? null;
        } elseif (is_array($controller)) {
            // Handle array format: [Controller::class, 'method', ...middlewares]
            if (isset($controller[0]) && isset($controller[1])) {
                $result['controller'] = $controller[0];
                $result['method'] = $controller[1];
                
                // Extract middlewares from the middlewares array
                if (count($middlewares) > 0) {
                    $result['middlewares'] = $middlewares;
                }
            }
        }

        return $result;
    }

    /**
     * Handles the routing call for a specified HTTP method and route.
     * 
     * @param string $route_method The HTTP method for the route (GET, POST, PUT, DELETE).
     * @param string $route The route to be registered.
     * @param string|array $controller The controller and method names to be called.
     * @param array $middlewares The middlewares to be applied on the controllers (for backward compatibility).
     * @return void
     */
    public static function route_call(string $route_method, string $route, string|array $controller, array $middlewares = [])
    {
        if (isset($route) || isset($controller)) {

            // Parse the controller definition
            $parsed = self::parseControllerDefinition($controller, $middlewares);
            
            // Use middlewares from parsed definition or from parameter (for backward compatibility)
            $routeMiddlewares = !empty($parsed['middlewares']) ? $parsed['middlewares'] : $middlewares;
            
            // Store controller info and middlewares
            self::$controller_array = [$parsed['controller'], $parsed['method']];
            self::$route_middlewares = $routeMiddlewares;

            $router = self::instance();
            $router->map($route_method, $route, function () use ($parsed, $routeMiddlewares) {
                self::executeMiddlewares($routeMiddlewares);
                self::route($parsed['controller'], $parsed['method'], $routeMiddlewares);
            });
        } else {
            echo "Corrupt route or route refused to parse!";
        }
    }

    /**
     * Executes all middlewares for the current route.
     * 
     * @param array $middlewares Array of middleware class names.
     * @return void
     */
    private static function executeMiddlewares(array $middlewares)
    {
        foreach ($middlewares as $middleware) {
            if (!class_exists($middleware)) {
                throw new SPException("Middleware {$middleware} not found");
            }

            $middlewareInstance = new $middleware();

            if (!method_exists($middlewareInstance, 'handle')) {
                continue; // skip middleware without handle
            }

            try {
                $response = $middlewareInstance->handle(new Request());

                // If middleware returns an error array, return JSON immediately
                if (is_array($response) && ($response['status'] ?? '') === 'error') {
                    http_response_code($response['code'] ?? 400);
                    header('Content-Type: application/json');
                    echo json_encode($response, JSON_PRETTY_PRINT);
                    exit();
                }

                // If middleware explicitly stops execution
                if ($response === false) {
                    exit();
                }

            } catch (\Exception $e) {
                // Catch unexpected errors and return as JSON 
                echo json_encode([
                    'status'  => 'error',
                    'code'    => 500,
                    'message' => $e->getMessage(),
                ], JSON_PRETTY_PRINT);
                exit();
            }
        }
    }

    /**
     * Registers a GET route.
     * Supports both string format: Route::get('/login', 'AuthController@login')
     * And array format: Route::get('/dashboard', [DashboardController::class, 'index'], [AuthMiddleware::class])
     * 
     * @param string $route The route to be registered.
     * @param string|array $controller The controller and method names to be called.
     * @param array $middlewares The middlewares to be applied on the controllers (for backward compatibility).
     * @return void
     */
    public static function get(string $route, string|array $controller, array $middlewares = [])
    {
        self::route_call("GET", $route, $controller, $middlewares);
    }

    /**
     * Registers a POST route.
     * 
     * @param string $route The route to be registered.
     * @param string|array $controller The controller and method names to be called.
     * @param array $middlewares The middlewares to be applied on the controllers (for backward compatibility).
     * @return void
     */
    public static function post(string $route, string|array $controller, array $middlewares = [])
    {
        self::route_call("POST", $route, $controller, $middlewares);
    }

    /**
     * Registers a PATCH route.
     * 
     * @param string $route The route to be registered.
     * @param string|array $controller The controller and method names to be called.
     * @param array $middlewares The middlewares to be applied on the controllers (for backward compatibility).
     * @return void
     */
    public static function patch(string $route, string|array $controller, array $middlewares = [])
    {
        self::route_call("PATCH", $route, $controller, $middlewares);
    }

    /**
     * Registers a PUT route.
     * 
     * @param string $route The route to be registered.
     * @param string|array $controller The controller and method names to be called.
     * @param array $middlewares The middlewares to be applied on the controllers (for backward compatibility).
     * @return void
     */
    public static function put(string $route, string|array $controller, array $middlewares = [])
    {
        self::route_call("PUT", $route, $controller, $middlewares);
    }

    /**
     * Registers a DELETE route.
     * 
     * @param string $route The route to be registered.
     * @param string|array $controller The controller and method names to be called.
     * @param array $middlewares The middlewares to be applied on the controllers (for backward compatibility).
     * @return void
     */
    public static function delete(string $route, string|array $controller, array $middlewares = [])
    {
        self::route_call("DELETE", $route, $controller, $middlewares);
    }

    public static function dispatch(): void
    {
        self::route_matcher_call(self::instance());
    }

    /**
     * Execute matched route target.
     *
     * @param Route $router
     * @return void
     */
    public static function route_matcher_call(Route $router): void
    {
        $match = $router->match(); 

        // No match OR invalid target → 404
        if (!$match || !isset($match['target']) || !is_callable($match['target'])) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
            echo view('404');
            exit;
        }

        $params = $match['params'] ?? []; 
        
        ($match['target'])(...$params);
    }

    protected static function instance(): self
    {
        if (!self::$routerInstance) {
            self::$routerInstance = new self();
        }

        return self::$routerInstance;
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
                            try {
                                $params[] = new $typeName();
                            } catch (\Exception $e) {
                                $params[] = null;
                            }
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
     * @param array $middlewares The middlewares to be applied on the controllers.
     * @return mixed The response from the controller.
     */
    public function classMethodInstantiationHelper($controller, $controllerInstance, $callable_function, array $middlewares = [])
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
                    try {
                        $params[] = new $typeName();
                    } catch (\Exception $e) {
                        $params[] = null;
                    }
                }
            } else {
                if ($param->getName() == 'id') {
                    $params[] = isset((new Request())->get->id) ? (new Request())->get->id : null;
                } else {
                    $params[] = null;
                }
            }
        }

        return $reflectionMethod->invokeArgs($controllerInstance, $params);
    }

    /**
     * Static method to handle routing and controller execution.
     * 
     * @param string $controller The controller to be executed.
     * @param string $callable_function The callable function/method within the controller.
     * @param array $middlewares The middlewares to be applied on the controllers.
     * @return void
     */ 
    public static function route($controller, $callable_function = null, array $middlewares = [])
    {
        try {
            $routeInstance = new self();
            $sp = new SP();
            
            $sp->verify_domain_format(env("APP_DOMAIN"));

            // Case 1: "AuthController@signup"
            if (is_string($controller) && str_contains($controller, '@')) {
                [$controllerName, $method] = explode('@', $controller);
                $controllerClass = $controllerName;
                $callable_function = $method;

                // Manual require only if class does not exist
                if (!class_exists($controllerClass)) {
                    $file = $routeInstance->controller_path($controllerClass);

                    if (!$file) {
                        throw new SPException("Controller {$controllerClass} not found.");
                    }

                    require_once $file;
                }
            }

            // Case 2: [DashboardController::class, 'index']
            elseif (is_array($controller)) {
                $controllerClass = $controller[0];
                $callable_function = $controller[1];

                // PSR-4 should already load it
                if (!class_exists($controllerClass)) {
                    throw new SPException("Controller {$controllerClass} not found.");
                }
            }

            // Case 3: Direct class string (rare case)
            else {
                $controllerClass = $controller;

                if (!class_exists($controllerClass)) {
                    throw new SPException("Controller {$controllerClass} not found.");
                }
            }

            $config = $sp->setup_config();
            $routeInstance->setUpCommonApplicationConfigurations($config);

            $controllerInstance = $routeInstance->classInstantiationHelper($controllerClass);

            if (!method_exists($controllerInstance, $callable_function)) {
                throw new SPException("Method {$callable_function} not found in {$controllerClass}");
            }

            $response = $routeInstance->classMethodInstantiationHelper(
                $controllerClass,
                $controllerInstance,
                $callable_function
            );

            $data = null;

            if (isset($response)) {
                $data = isset($response['controller_response_data'])
                    ? $response['controller_response_data']
                    : $response;
            }

            if (isset($response['view_url'])) {
                if (file_exists($response['view_url'])) {
                    echo $sp->fileParser($data, $response['view_url']);
                    (new self())->unsetSession();
                    exit();
                } else {
                    throw new SPException("View path could not be found.");
                }
            } else {
                $routeInstance->alternative_callable_method_response($response, $sp);
                (new self())->unsetSession();
            } 
        } catch (SPException $th) {
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
        if ($this->isValidTimezone($config['TIMEZONE'])) {
            date_default_timezone_set($config['TIMEZONE']);
        } else {
            throw new SPException("Timezone must be in Continent/City format");
        }

        // Set the default locale
        if (preg_match("/^[a-z]{2}_[A-Z]{2}$/", $config['LOCALE'])) {
            setlocale(LC_ALL, $config['LOCALE']);
        } else {
            throw new SPException("Locale must be in ISO 639-1 format");
        }

        // Set the default character encoding 
        if ($this->isValidCharset($config['CHARACTER_ENCODING'])) {
            mb_internal_encoding($config['CHARACTER_ENCODING']);
        } else {
            throw new SPException("Character encoding must be in ISO 8859-1 format");
        }

        // Set the default language
        if (preg_match("/^[a-z]{2}$/", $config['LANGUAGE'])) {
            mb_language($config['LANGUAGE']);
        } else {
            throw new SPException("Language must be in ISO 639-1 format");
        }

        // Set the default currency
        if (preg_match("/^[A-Z]{3}$/", $config['CURRENCY'])) {
            setlocale(LC_MONETARY, $config['CURRENCY']);
        } else {
            throw new SPException("Currency must be in ISO 4217 format");
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

    /**
     * Validates if a timezone is valid.
     * 
     * @param string $timezone The timezone to validate.
     * @return bool Returns true if valid, false otherwise.
     */
    function isValidTimezone($timezone)
    {
        $supportedTimezones = \DateTimeZone::listIdentifiers();
        return in_array($timezone, $supportedTimezones);
    }

    /**
     * Validates if a charset is valid.
     * 
     * @param string $charset The charset to validate.
     * @return bool Returns true if valid, false otherwise.
     */
    function isValidCharset($charset)
    {
        $pattern = '/^[A-Za-z0-9_\-]+$/';
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
    public function controller_path(string $controller): ?string
    {
        $controllerPaths = config('CONTROLLER_PATH');

        foreach ($controllerPaths as $folder) {

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($folder)
            );

            foreach ($iterator as $file) {
                if ($file->getFilename() === $controller . '.php') {
                    return $file->getPathname();
                }
            }
        }

        return null;
    }
}