<?php

namespace SelfPhp; 

use SelfPhp\SP;

/**
 * Custom Exception Class for Logging Errors.
 *
 * This class extends PHP's built-in Exception class to provide additional logging functionality.
 * The class logs error details such as the message, code, file, line, and stack trace to a log file
 * whenever the exception is thrown. It also formats the error message based on the current debug
 * environment setting.
 *
 * Usage:
 * - Throw this exception where logging and custom error handling is needed.
 * - The error details are logged into a file (default: 'public/storage/logs/sp_error_log.txt').
 * - The exception message can be displayed differently depending on the DEBUG environment.
 */
class SPException extends \Exception
{
    public $logFile;
    
    public $userMessage;
    
    /**
     * Constructor to initialize the exception and log error details.
     *
     * @param string $message The exception message (detailed for developers).
     * @param int $code The exception code (default: 0).
     * @param Exception|null $previous The previous exception (default: null).
     * @param string $logFile The log file path to save errors (default: 'public/storage/logs/sp_error_log.txt').
     * @param string $userMessage The message to show to the user (default: 'An error has occurred. Please try again later.').
     */
    public function __construct($message, $code = 0, Exception $previous = null, $logFile = 'public/storage/logs/sp_error_log.txt', $userMessage = 'An error has occurred. Please try again later.') {
        $this->logFile = $logFile;
        $this->userMessage = $userMessage;
        parent::__construct($message, $code, $previous);
        $this->logError();  // Log the error details
    }
    
    /**
     * Logs the error details to the log file.
     *
     * This method captures details such as the message, code, file, line, and stack trace
     * of the exception and writes them to the specified log file for future debugging.
     */
    public function logError() {
        if ((new SP())->debugMode()) { 
            $logDir = dirname($this->logFile);
            if (!is_dir($logDir)) {
                mkdir($logDir, 0777, true);  // Create the directory if it does not exist
            }

            // Ensure the log file exists or create it
            if (!file_exists($this->logFile)) {
                touch($this->logFile);  // Create the log file if it doesn't exist
            }

            // Capture more useful information for debugging
            $errorDetails = date('Y-m-d H:i:s') . ' | ' . $this->getMessage() . ' | Code: ' . $this->getCode() . PHP_EOL;
            $errorDetails .= 'File: ' . $this->getFile() . ' | Line: ' . $this->getLine() . PHP_EOL;
            $errorDetails .= 'Stack Trace: ' . $this->getTraceAsString() . PHP_EOL . PHP_EOL;

            // Log to the specified log file
            file_put_contents($this->logFile, $errorDetails, FILE_APPEND);
        }
    }
    
    /**
     * Custom string representation of the exception.
     *
     * @return string The exception message, formatted based on the DEBUG environment.
     */
    public function __toString() {
        // Check if the DEBUG environment variable is set to true
        if ((new SP())->debugMode()) {
            return "Error [{$this->getCode()}]: {$this->getMessage()} in {$this->getFile()} on line {$this->getLine()}";
        }

        return $this->userMessage;
    }
} 