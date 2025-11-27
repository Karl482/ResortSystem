<?php
// Prevent direct access to this file
if (!defined('APP_LOADED') && php_sapi_name() !== 'cli') { // Allow CLI access
    http_response_code(403);
    die('Direct access not allowed.');
}

// Set the application's base URL.
// This is used for generating absolute URLs in emails and other parts of the application.
// IMPORTANT: Change this to your actual production domain when deploying.
define('APP_URL', 'http://localhost/ResortsSystem');

// Define BASE_URL based on the context (web vs. CLI)
if (php_sapi_name() === 'cli') {
    // For command-line scripts, use the hardcoded APP_URL.
    define('BASE_URL', APP_URL);
} else {
    // For web requests, dynamically determine the base URL.
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    // Adjust script_name to point to the project root, not the public directory
    $script_name = str_replace('/public/index.php', '', $_SERVER['SCRIPT_NAME']);
    
    // Define the base URL constant
    define('BASE_URL', rtrim($protocol . $host . $script_name, '/'));
}

// Define path to the PHP executable for background tasks
// You might need to change this depending on your system configuration
define('PHP_PATH', 'C:\\xampp\\php\\php.exe');

// Define path for log files
define('LOGS_PATH', __DIR__ . '/../logs');