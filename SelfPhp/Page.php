<?php

namespace SelfPhp;

use SelfPhp\SP;
use SelfPhp\Auth;

class Page extends SP
{
    // Status of the controller response
    private $status;

    // Message of the controller response
    private $message;

    // Given route to navigate to
    public $route;

    /**
     * Page constructor. 
     * 
     * @return void
     */
    public function __construct()
    {
        // ANY EXISTING CONSTRUCTOR LOGIC
    }

    /**
     * set_alert_properties function
     * 
     * Sets alert properties in the session based on the provided message.
     * 
     * @param mixed $message The message to set as an alert. Can be a string or an associative array with 'status' and 'message' keys.
     */
    public function set_alert_properties($message)
    {
        // Check if $message is an array
        if (is_array($message) && !empty($message)) {
            // Set entire response data in session and object based on the provided message or default to null
            $_SESSION['controller_response_data'] = $this->message = $message;
        }
    }


    /**
     * navigate_to function
     * 
     * Navigates to the specified route and optionally sets alert properties based on a provided message.
     * 
     * @param string $route The route to navigate to.
     * @param array $message An optional associative array containing alert properties.
     */
    public function navigate_to($route, $message = [])
    {
        // Check if $route is set
        if (!isset($route)) {
            return false;
        }

        // Modify the route by replacing dots with slashes
        $this->route = str_replace(".", "/", $route);

        // Check if $message is an array and has elements
        if (is_array($message) && !empty($message)) {
            // Set alert properties based on the provided message 
            $this->set_alert_properties($message);
        }

        // Redirect to the specified route
        header("Location: /" . $this->route);
        exit(0);
    }

    /**
     * back function
     * 
     * Redirects to the previous page, and optionally sets alert properties based on a provided message.
     * 
     * @param array $message An optional associative array containing alert properties.
     */
    public function back($message = [])
    {
        // Check if HTTP_REFERER is set and not empty, else set a fallback route
        $this->route = isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER']) 
            ? $_SERVER['HTTP_REFERER'] 
            : '/';  // Fallback to homepage or another default route

        // Check if $message is an array and has elements
        if (is_array($message) && !empty($message)) { 
            $this->set_alert_properties($message);
        } 

        // Perform the redirect to the determined route
        header("Location: {$this->route}");
        exit;
    }

}
