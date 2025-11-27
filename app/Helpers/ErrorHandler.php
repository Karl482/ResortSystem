<?php

/**
 * Enhanced Error Handler - Phase 6
 * Comprehensive error handling, logging, and recovery system
 */

class ErrorHandler {
    
    private static $logFile = __DIR__ . '/../../logs/application.log';
    private static $errorLevels = [
        'DEBUG' => 0,
        'INFO' => 1,
        'WARNING' => 2,
        'ERROR' => 3,
        'CRITICAL' => 4,
        'EMERGENCY' => 5
    ];

    /**
     * Initialize error handler
     */
    public static function initialize() {
        // Set error reporting based on environment
        if (self::isDevelopment()) {
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
        } else {
            error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);
            ini_set('display_errors', 0);
        }

        // Set custom error handlers
        set_error_handler([__CLASS__, 'handleError']);
        set_exception_handler([__CLASS__, 'handleException']);
        register_shutdown_function([__CLASS__, 'handleShutdown']);

        // Create log directory if it doesn't exist
        $logDir = dirname(self::$logFile);
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }

    /**
     * Handle PHP errors
     */
    public static function handleError($severity, $message, $file, $line) {
        if (!(error_reporting() & $severity)) {
            return false;
        }

        $errorType = self::getErrorType($severity);
        $logMessage = "PHP {$errorType}: {$message} in {$file} on line {$line}";
        
        self::log($logMessage, 'ERROR');

        // In development, show detailed error
        if (self::isDevelopment()) {
            self::displayError($errorType, $message, $file, $line);
        } else {
            // In production, show generic error and redirect
            self::handleProductionError();
        }

        return true;
    }

    /**
     * Handle uncaught exceptions
     */
    public static function handleException($exception) {
        $message = "Uncaught exception: " . $exception->getMessage();
        $file = $exception->getFile();
        $line = $exception->getLine();
        $trace = $exception->getTraceAsString();

        $logMessage = "{$message} in {$file} on line {$line}\nStack trace:\n{$trace}";
        
        self::log($logMessage, 'CRITICAL');

        if (self::isDevelopment()) {
            self::displayException($exception);
        } else {
            self::handleProductionError();
        }
    }

    /**
     * Handle fatal errors during shutdown
     */
    public static function handleShutdown() {
        $error = error_get_last();
        
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $message = "Fatal error: {$error['message']} in {$error['file']} on line {$error['line']}";
            self::log($message, 'EMERGENCY');
            
            if (!self::isDevelopment()) {
                self::handleProductionError();
            }
        }
    }

    /**
     * Log message with timestamp and context
     */
    public static function log($message, $level = 'INFO', $context = []) {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' Context: ' . json_encode($context) : '';
        $userInfo = self::getUserContext();
        
        $logEntry = "[{$timestamp}] [{$level}] {$message}{$contextStr}{$userInfo}\n";
        
        file_put_contents(self::$logFile, $logEntry, FILE_APPEND | LOCK_EX);
        
        // Also log to system log if available
        if (function_exists('syslog')) {
            $priority = self::getSyslogPriority($level);
            syslog($priority, $message);
        }
    }

    /**
     * Enhanced validation with detailed error reporting
     */
    public static function validateInput($data, $rules, $customMessages = []) {
        $errors = [];
        $sanitizedData = [];

        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            $ruleArray = is_string($rule) ? explode('|', $rule) : $rule;

            // If the field is nullable and not present (or null), it's valid for this field.
            if (in_array('nullable', $ruleArray) && $value === null) {
                $sanitizedData[$field] = null;
                continue; // Skip further validation for this field
            }
            
            foreach ($ruleArray as $singleRule) {
                // We already handled nullable, so just skip it in the loop
                if ($singleRule === 'nullable') {
                    continue;
                }

                $result = self::applyValidationRule($field, $value, $singleRule, $customMessages);
                
                if ($result['valid']) {
                    $sanitizedData[$field] = $result['value'];
                } else {
                    $errors[$field][] = $result['message'];
                    break; // Stop validating this field on first error
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'data' => $sanitizedData
        ];
    }

    /**
     * Apply individual validation rule
     */
    private static function applyValidationRule($field, $value, $rule, $customMessages) {
        $ruleParts = explode(':', $rule);
        $ruleName = $ruleParts[0];
        $ruleParam = $ruleParts[1] ?? null;

        switch ($ruleName) {
            case 'required':
                if (empty($value) && $value !== '0') {
                    return [
                        'valid' => false,
                        'message' => $customMessages["{$field}.required"] ?? "{$field} is required"
                    ];
                }
                break;

            case 'integer':
                $filtered = filter_var($value, FILTER_VALIDATE_INT);
                if ($filtered === false) {
                    return [
                        'valid' => false,
                        'message' => $customMessages["{$field}.integer"] ?? "{$field} must be an integer"
                    ];
                }
                $value = $filtered;
                break;

            case 'float':
                $filtered = filter_var($value, FILTER_VALIDATE_FLOAT);
                if ($filtered === false) {
                    return [
                        'valid' => false,
                        'message' => $customMessages["{$field}.float"] ?? "{$field} must be a number"
                    ];
                }
                $value = $filtered;
                break;

            case 'email':
                $filtered = filter_var($value, FILTER_VALIDATE_EMAIL);
                if ($filtered === false) {
                    return [
                        'valid' => false,
                        'message' => $customMessages["{$field}.email"] ?? "{$field} must be a valid email"
                    ];
                }
                $value = $filtered;
                break;

            case 'min':
                if (is_numeric($value) && $value < $ruleParam) {
                    return [
                        'valid' => false,
                        'message' => $customMessages["{$field}.min"] ?? "{$field} must be at least {$ruleParam}"
                    ];
                } elseif (is_string($value) && strlen($value) < $ruleParam) {
                    return [
                        'valid' => false,
                        'message' => $customMessages["{$field}.min"] ?? "{$field} must be at least {$ruleParam} characters"
                    ];
                }
                break;

            case 'max':
                if (is_numeric($value) && $value > $ruleParam) {
                    return [
                        'valid' => false,
                        'message' => $customMessages["{$field}.max"] ?? "{$field} must not exceed {$ruleParam}"
                    ];
                } elseif (is_string($value) && strlen($value) > $ruleParam) {
                    return [
                        'valid' => false,
                        'message' => $customMessages["{$field}.max"] ?? "{$field} must not exceed {$ruleParam} characters"
                    ];
                }
                break;

            case 'date':
                $date = DateTime::createFromFormat('Y-m-d', $value);
                if (!$date || $date->format('Y-m-d') !== $value) {
                    return [
                        'valid' => false,
                        'message' => $customMessages["{$field}.date"] ?? "{$field} must be a valid date (YYYY-MM-DD)"
                    ];
                }
                break;

            case 'in':
                $options = explode(',', $ruleParam);
                if (!in_array($value, $options)) {
                    return [
                        'valid' => false,
                        'message' => $customMessages["{$field}.in"] ?? "{$field} must be one of: " . implode(', ', $options)
                    ];
                }
                break;

            case 'array':
                if (!is_array($value)) {
                    return [
                        'valid' => false,
                        'message' => $customMessages["{$field}.array"] ?? "{$field} must be an array"
                    ];
                }
                break;

            case 'sanitize':
                $value = htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
                break;
        }

        return ['valid' => true, 'value' => $value];
    }

    /**
     * Enhanced database error handling
     */
    public static function handleDatabaseError($exception, $query = null, $params = []) {
        $errorCode = $exception->getCode();
        $errorMessage = $exception->getMessage();
        
        // Log detailed database error
        $logMessage = "Database Error [{$errorCode}]: {$errorMessage}";
        if ($query) {
            $logMessage .= " Query: {$query}";
        }
        if (!empty($params)) {
            $logMessage .= " Params: " . json_encode($params);
        }
        
        self::log($logMessage, 'ERROR', ['type' => 'database']);

        // Determine error type and appropriate response
        switch ($errorCode) {
            case '23000': // Integrity constraint violation
                if (strpos($errorMessage, 'Duplicate entry') !== false) {
                    return [
                        'type' => 'duplicate_entry',
                        'message' => 'This record already exists',
                        'user_message' => 'A record with this information already exists. Please check your input.'
                    ];
                } elseif (strpos($errorMessage, 'foreign key constraint') !== false) {
                    return [
                        'type' => 'foreign_key_violation',
                        'message' => 'Cannot perform operation due to data relationships',
                        'user_message' => 'This operation cannot be completed due to existing data relationships.'
                    ];
                }
                break;
                
            case '42000': // Syntax error
                return [
                    'type' => 'syntax_error',
                    'message' => 'Database query error',
                    'user_message' => 'A technical error occurred. Please try again.'
                ];
                
            case 'HY000': // General error
                if (strpos($errorMessage, 'server has gone away') !== false) {
                    return [
                        'type' => 'connection_lost',
                        'message' => 'Database connection lost',
                        'user_message' => 'Connection lost. Please try again.'
                    ];
                }
                break;
        }

        // Default database error
        return [
            'type' => 'database_error',
            'message' => 'Database operation failed',
            'user_message' => 'A database error occurred. Please try again or contact support.'
        ];
    }

    /**
     * Enhanced file operation error handling
     */
    public static function handleFileError($operation, $file, $error) {
        $message = "File operation '{$operation}' failed for '{$file}': {$error}";
        self::log($message, 'ERROR', ['type' => 'file_operation']);

        switch ($operation) {
            case 'upload':
                return [
                    'type' => 'upload_error',
                    'message' => 'File upload failed',
                    'user_message' => 'Failed to upload file. Please check file size and format.'
                ];
                
            case 'delete':
                return [
                    'type' => 'delete_error',
                    'message' => 'File deletion failed',
                    'user_message' => 'Failed to delete file. The file may no longer exist.'
                ];
                
            case 'read':
                return [
                    'type' => 'read_error',
                    'message' => 'File read failed',
                    'user_message' => 'Failed to read file. The file may be corrupted or missing.'
                ];
                
            default:
                return [
                    'type' => 'file_error',
                    'message' => 'File operation failed',
                    'user_message' => 'A file operation error occurred. Please try again.'
                ];
        }
    }

    /**
     * Create standardized API error response
     */
    public static function createApiErrorResponse($message, $code = 500, $details = []) {
        $response = [
            'success' => false,
            'error' => [
                'message' => $message,
                'code' => $code,
                'timestamp' => date('c')
            ]
        ];

        if (!empty($details)) {
            $response['error']['details'] = $details;
        }

        if (self::isDevelopment() && isset($details['debug'])) {
            $response['error']['debug'] = $details['debug'];
        }

        return $response;
    }

    /**
     * Get error type from PHP error severity
     */
    private static function getErrorType($severity) {
        switch ($severity) {
            case E_ERROR:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
            case E_USER_ERROR:
                return 'Fatal Error';
            case E_WARNING:
            case E_CORE_WARNING:
            case E_COMPILE_WARNING:
            case E_USER_WARNING:
                return 'Warning';
            case E_PARSE:
                return 'Parse Error';
            case E_NOTICE:
            case E_USER_NOTICE:
                return 'Notice';
            case E_STRICT:
                return 'Strict Standards';
            case E_RECOVERABLE_ERROR:
                return 'Catchable Fatal Error';
            case E_DEPRECATED:
            case E_USER_DEPRECATED:
                return 'Deprecated';
            default:
                return 'Unknown Error';
        }
    }

    /**
     * Get syslog priority from error level
     */
    private static function getSyslogPriority($level) {
        switch ($level) {
            case 'EMERGENCY':
                return LOG_EMERG;
            case 'CRITICAL':
                return LOG_CRIT;
            case 'ERROR':
                return LOG_ERR;
            case 'WARNING':
                return LOG_WARNING;
            case 'INFO':
                return LOG_INFO;
            case 'DEBUG':
                return LOG_DEBUG;
            default:
                return LOG_INFO;
        }
    }

    /**
     * Get user context for logging
     */
    private static function getUserContext() {
        $context = [];
        
        if (isset($_SESSION['user_id'])) {
            $context['user_id'] = $_SESSION['user_id'];
        }
        
        if (isset($_SESSION['role'])) {
            $context['role'] = $_SESSION['role'];
        }
        
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $context['ip'] = $_SERVER['REMOTE_ADDR'];
        }
        
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $context['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        }

        return !empty($context) ? ' User: ' . json_encode($context) : '';
    }

    /**
     * Display error in development mode
     */
    private static function displayError($type, $message, $file, $line) {
        echo "<div style='background: #ffebee; border: 1px solid #f44336; padding: 10px; margin: 10px; border-radius: 4px;'>";
        echo "<h3 style='color: #f44336; margin: 0;'>{$type}</h3>";
        echo "<p><strong>Message:</strong> {$message}</p>";
        echo "<p><strong>File:</strong> {$file}</p>";
        echo "<p><strong>Line:</strong> {$line}</p>";
        echo "</div>";
    }

    /**
     * Display exception in development mode
     */
    private static function displayException($exception) {
        echo "<div style='background: #ffebee; border: 1px solid #f44336; padding: 10px; margin: 10px; border-radius: 4px;'>";
        echo "<h3 style='color: #f44336; margin: 0;'>Uncaught Exception</h3>";
        echo "<p><strong>Message:</strong> " . $exception->getMessage() . "</p>";
        echo "<p><strong>File:</strong> " . $exception->getFile() . "</p>";
        echo "<p><strong>Line:</strong> " . $exception->getLine() . "</p>";
        echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 4px; overflow: auto;'>";
        echo $exception->getTraceAsString();
        echo "</pre>";
        echo "</div>";
    }

    /**
     * Handle production errors
     */
    private static function handleProductionError() {
        if (!headers_sent()) {
            http_response_code(500);
            
            // Check if request expects JSON
            if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'error' => [
                        'message' => 'An internal error occurred. Please try again later.',
                        'code' => 500
                    ]
                ]);
            } else {
                // Redirect to error page
                header('Location: ' . BASE_URL . '/public/error.php');
            }
        }
        
        exit();
    }

    /**
     * Check if running in development environment
     */
    private static function isDevelopment() {
        return (isset($_SERVER['APPLICATION_ENV']) && $_SERVER['APPLICATION_ENV'] === 'development') ||
               (defined('APPLICATION_ENV') && APPLICATION_ENV === 'development') ||
               (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'development');
    }

    /**
     * Clean up log files (remove old entries)
     */
    public static function cleanupLogs($daysToKeep = 30) {
        if (!file_exists(self::$logFile)) {
            return;
        }

        $lines = file(self::$logFile);
        $cutoffDate = date('Y-m-d', strtotime("-{$daysToKeep} days"));
        $filteredLines = [];

        foreach ($lines as $line) {
            if (preg_match('/^\[(\d{4}-\d{2}-\d{2})/', $line, $matches)) {
                if ($matches[1] >= $cutoffDate) {
                    $filteredLines[] = $line;
                }
            }
        }

        file_put_contents(self::$logFile, implode('', $filteredLines), LOCK_EX);
    }
}