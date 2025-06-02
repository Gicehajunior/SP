<?php
namespace SelfPhp;

use SelfPhp\Page;
use SelfPhp\SPException; 

class Auth extends Page
{
    /**
     * Hashes a given password.
     * 
     * @param string $password
     * @param array $options Optional array of options for password hashing (e.g., 'cost').
     * @return bool|string The hashed password or false on failure.
     */
    public static function hash_pass($password, $options = [])
    { 
        if (empty($password)) {
            throw new SPException('Password cannot be empty');
        }

        $default_options = [
            'cost' => 10, // Default cost factor
        ];
        
        $options = array_merge($default_options, $options);

        // Validate custom cost option if provided
        if (isset($options['cost']) && (!is_int($options['cost']) || $options['cost'] < 4 || $options['cost'] > 31)) {
            throw new SPException('Invalid cost parameter. It must be an integer between 4 and 31.');
        }

        // Use password_hash to hash the password using the default algorithm (bcrypt)
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT, $options);
            
            if ($hashed_password === false) {
                throw new SPException('Password hashing failed.');
            }

            return $hashed_password;
        } catch (SPException $e) {
            throw new SPException('Error hashing password: ' . $e->getMessage());
        }
    }

    /**
     * Verifies a given password against a hashed password.
     * 
     * @param string $password
     * @param string $hashed_password
     * @return bool True if the password is verified, false otherwise.
     */
    public static function verify_pass($password, $hashed_password)
    { 
        if (empty($password) || empty($hashed_password)) {
            throw new SPException('Password or hashed password cannot be empty');
        }

        // Use password_verify to check if the password matches the hashed password
        return password_verify($password, $hashed_password);
    }

    /**
     * Creates temporary session variables.
     * 
     * @param array $session_object An associative array of session variables.
     * @return bool True on success, false on failure.
     */
    public static function push_session($session_object)
    {
        if (is_array($session_object) && count($session_object) > 0) {
            foreach ($session_object as $key => $value) { 
                $sanitized_key = self::sanitize_session_key($key);
                $sanitized_value = self::sanitize_session_value($value);
                
                $_SESSION[$sanitized_key] = $sanitized_value;
            }

            return true;
        } else {
            return false;
        }
    }

    /**
     * Creates permanent session variables.
     * 
     * @param array $session_object An associative array of session variables.
     * @param string $name The session name or namespace to store the variables under (default is 'auth').
     * @return bool True on success, false on failure.
     */
    public static function start_session($session_object = [], $name = "auth")
    {
        if (is_array($session_object) && count($session_object) > 0) {
            try {
                foreach ($session_object as $key => $value) { 
                    $sanitized_key = self::sanitize_session_key($key);
                    $sanitized_value = self::sanitize_session_value($value);

                    $_SESSION[$name][$sanitized_key] = $sanitized_value;
                }

                return true;
            } catch (SPException $e) { 
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Resolves or updates an existing session key with a new value. 
     * Optionally, creates the session key if it doesn't exist.
     * 
     * @param string $key The key of the session variable to resolve/update.
     * @param mixed $value The new value to set for the session key.
     * @param string $var The session namespace (default is 'auth').
     * @param bool $create_if_not_exists Flag to create the session key if it does not exist (default is false).
     * @return bool True if the key was resolved/updated successfully, false if the key does not exist and $create_if_not_exists is false.
     */
    public static function resolve_session($key, $value, $var = "auth", $create_if_not_exists = true)
    {
        // Check if the session has been started
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // Check if the session key exists
        if (isset($_SESSION[$var][$key])) {
            // Update the existing session key with the new value
            $_SESSION[$var][$key] = $value;
            return true;
        } elseif ($create_if_not_exists) {
            // create the session key if it doesn't exist
            $_SESSION[$var][$key] = $value;
            return true;
        }
        
        return false;
    }

    /**
     * Checks if a session is active for the given session key.
     * 
     * @param string $var The session variable to check for (default is 'auth').
     * @return bool True if the session is active and contains data for the given key, false otherwise.
     */
    public static function User($key, $var = "auth")
    {
        if (!isset($_SESSION[$var])) {
            return null;
        }
    
        // If it's an array, access like array
        if (is_array($_SESSION[$var]) && isset($_SESSION[$var][$key])) {
            return $_SESSION[$var][$key];
        }
    
        // If it's an object, access like object
        if (is_object($_SESSION[$var]) && isset($_SESSION[$var]->$key)) {
            return $_SESSION[$var]->$key;
        }
    
        return null;
    }

    /**
     * Retrieves a given session variable from permanent session variables.
     * 
     * @param string $key The key of the session variable.
     * @param string $var The session variable to check for (default is 'auth').
     * @return mixed|null The session variable value or null if not set.
     */
    public static function User($key, $var = "auth")
    {
        return (isset($_SESSION[$var][$key])) ? $_SESSION[$var][$key] : null;
    }

    /**
     * Retrieves a given session variable.
     * 
     * @param string|array $key The key or array of keys of the session variable.
     * @return mixed|null The session variable value or null if not set.
     */
    public static function get_session($key)
    {
        $session_array = [];

        if (is_array($key)) {
            foreach ($key as $value) { 
                $sanitized_key = self::sanitize_session_key($value);
                $session_array[$sanitized_key] = (isset($_SESSION[$sanitized_key])) ? $_SESSION[$sanitized_key] : null;
            }
        } else {
            $sanitized_key = self::sanitize_session_key($key);
            $session_array[$sanitized_key] = (isset($_SESSION[$sanitized_key])) ? $_SESSION[$sanitized_key] : null;
        }

        return $session_array;
    }

    /**
     * Destroys the current session.
     * 
     * @return bool True on success, false on failure.
     */
    public static function boot_out()
    {
        // Sanitize session destruction for complete cleanup
        $_SESSION = [];

        // Destroy the session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, 
                    $params["path"], $params["domain"], 
                    $params["secure"], $params["httponly"]);
        }

        // Destroy the session
        return session_destroy();
    }

    /**
     * Sanitizes the session key.
     *
     * @param string $key The session key.
     * @return string The sanitized session key.
     */
    private static function sanitize_session_key($key)
    {
        // Make sure the key is a safe string and not malicious
        return preg_replace('/[^a-zA-Z0-9_]/', '', $key);
    }

    /**
     * Sanitizes the session value.
     *
     * @param mixed $value The session value.
     * @return mixed The sanitized session value.
     */
    private static function sanitize_session_value($value)
    {
        // Handle sanitization based on the value type (string, array, etc.)
        if (is_string($value)) {
            return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        } elseif (is_array($value)) {
            return array_map([self::class, 'sanitize_session_value'], $value);
        }

        return $value;
    }
}
