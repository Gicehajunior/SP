<?php

use SelfPhp\SP;
use SelfPhp\Auth;
use SelfPhp\Page;
use SelfPhp\SPException;

// Globally accessible config container
$GLOBALS['config'] = [];

/**
 * Checks if the user is authenticated
 * 
 * @return bool Returns true if authenticated, false otherwise.
 */
function Authenticated() {  
    return Auth::auth();
}

/**
 * Returns the user session object based on the provided key
 * 
 * @param string $key The key to retrieve from the user session.
 * @return mixed The value stored in the user session for the provided key.
 */
function Auth($key) { 
    return Auth::User($key);
}

/**
 * Reads environment variable, considering redeclaration exemption
 * 
 * @param string $key The key of the environment variable.
 * @return string The value of the environment variable.
 */
if (!function_exists('env'))
{
    function env($key) {
        return (new SP())->env($key);
    }
}

/**
 * Reads configuration variable
 * 
 * @param string $key The key of the configuration variable.
 * @return string The value of the configuration variable.
 */
function config($key) {
    if (empty($key)) {
        return false;
    }
    
    return (new SP())->config(strtoupper($key));
}


foreach (config('GLOBAL_CONFIGS') as $key => $value) {
    $GLOBALS['config'][$key] = include getcwd() . DIRECTORY_SEPARATOR . $value;
} 

/**
 * Configuration helper function
 * 
 * @param key|group
 * @return configuration
 */
function config_all($group=null)
{  
    return !empty($group) ? ($GLOBALS['config'][$group]) : $GLOBALS['config'];
}

/**
 * Configuration helper function
 * 
 * @param key|group
 * @return configuration
 */
function config_parse($key, $group = 'app')
{ 
    return $GLOBALS['config'][$group][$key] ?? null;
}

/**
 * Determine and return the current route.
 *
 * @return string The current route (e.g. 'bootstrap4', 'bootstrap5')
 */
function current_route(): string { 
    return trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/') ?: 'index';
}

/**
 * Determine the Bootstrap version to be used for the current route.
 *
 * @return string The bootstrap version (e.g. 'bootstrap4', 'bootstrap5')
 */
function bootstrap(): string {
    $config = config_parse('bootstrap', 'assets');
    
    $defaultVersion = $config['default'] ?? '4';
    $routes = $config['routes'] ?? [];

    // Get the current route/view.  
    $currentRoute = current_route();
    

    foreach ($routes as $version => $routeList) {
        if (in_array($currentRoute, $routeList) || in_array('*', $routeList)) {
            return $version;
        }
    }

    return $defaultVersion;
} 

/**
 * Retrieves the application name
 * 
 * @return string The application name.
 */
function sys_name() {
    $app_name = (new SP())->app_name();

    return $app_name;
}

/**
 * Retrieves the public path of the application
 * 
 * @param string $path The path to append to the public path.
 * @return string The full public path including the provided path.
 */
function public_path($path) {
    return (new SP())->public_path($path);
}

/**
 * Retrieves the asset path of the application
 * 
 * @param string $path The path to append to the asset path.
 * @return string The full asset path including the provided path.
 */
function asset_path($path) {
    return (new SP())->asset_path($path);
}

/**
 * Retrieves the storage path of the application
 * 
 * @param string $path The path to append to the storage path.
 * @return string The full storage path including the provided path.
 */
function storage_path($path) {
    return (new SP())->storage_path($path);
}

/**
 * Reads the domain set in the .env file
 * 
 * @param string $var The key of the domain variable.
 * @return string The value of the domain variable.
 */
function sys_domain($var) {
    return (new SP())->env($var);
}

/**
 * Parses HTML/PHP files with post data
 * 
 * @param array $data The data to be parsed.
 * @param string $filename The name of the file being parsed.
 * @return mixed The parsed data.
 */
function file_parser($data, $filename) {
    return (new SP())->fileParser($data, $filename);
}

/**
 * Retrieves the set application login page route
 * 
 * @return string The login page route.
 */
function login_page() {
    return (new SP())->login_page();
}

/**
 * Retrieves the set application dashboard page route
 * 
 * @return string The dashboard page route.
 */
function dashboard_page() {
    return (new SP())->dashboard_page();
}

/**
 * Searches for the file passed, requires the file to be parsed
 * 
 * @param string $file The name of the file to extend.
 * @param mixed $data The data to be passed to the extended file.
 * @return mixed The content of the extended file.
 */
function resource($filename, $data=[]) { 
    return (new SP())->resource($filename, $data);
}

/**
 * Searches for the file passed, requires the file to be parsed
 * 
 * @param string $file The name of the file to extend.
 * @param mixed $data The data to be passed to the extended file.
 * @return mixed The content of the extended file.
 */
function page_extends($file, $data=[]) {   
    $filecontent = (new SP())->resource($file, $data);

    echo $filecontent;
}

/**
 * Redirects and views the given view file
 * 
 * @param string $view_dir The directory of the view file.
 * @param array $controller_response_data The data to be passed to the view file.
 * @return array The view URL and data.
 */
function view($view_dir, $data = []) { 
    $page = new Page();
    $response = []; 
    
    if (isset($_SESSION['controller_response_data']) && !empty($_SESSION['controller_response_data'])) {
        $_data = $_SESSION['controller_response_data']; 
        foreach ($_data as $key => $response_data) { 
            $data[$key] = $response_data; 
        }
    }
    
    $view_response = $page->View($view_dir, $data); 

    $response['view_url'] = $view_response;  
    $response['controller_response_data'] = $data;

    return $response;
}

/**
 * Redirects to the given route
 * 
 * @param string $route The route to navigate to.
 * @param array $data The data to be passed to the route.
 * @return bool
 */
function route($route, $data = []) {  
    $page = new Page(); 
    $view_response = $page->navigate_to($route, $data); 
    
    return $view_response;
}

/**
 * Redirects back to the previous route
 *  
 * @param array $data The data to be passed to the route.
 * @return bool
 */
function back($data = []) {  
    $page = new Page(); 
    $view_response = $page->back($data); 
    
    return $view_response;
}

function sp_error_logger($error, $error_code=0) { 
    try {
        $isDebug = (new SP())->debugMode();
        
        if ($isDebug) {
            throw new \Exception($error);
        } 

        return "An error occurred!";
    } catch (\Exception $err) {
        return "Error [{$err->getCode()}]: {$err->getMessage()} in {$err->getFile()} on line {$err->getLine()}";
    }
}
