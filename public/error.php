<?php
// Define BASE_URL if it's not already defined to ensure CSS and links work correctly.
if (!defined('BASE_URL')) {
    // Determines the base URL dynamically.
    // This assumes the app is in a subdirectory of the web root.
    // e.g., http://localhost/ResortsSystem
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $scriptName = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    
    // If the script is in a subdirectory (like /public), go up one level
    $basePath = dirname($scriptName);

    // If the app is at the root, basePath will be '\' or '/', clean it up.
    if ($basePath === '/' || $basePath === '\\') {
        $basePath = '';
    }
    
    define('BASE_URL', $protocol . $host . $basePath);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Error</title>
    <link href="<?= BASE_URL ?>/assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f8f9fa;
        }
        .error-container {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .error-container h1 {
            font-size: 3rem;
            color: #dc3545;
        }
        .error-container p {
            font-size: 1.2rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <h1>An Error Occurred</h1>
        <p>We're sorry, but something went wrong. Please try again later.</p>
        <a href="<?= BASE_URL ?>/public/index.php" class="btn btn-primary">Go to Homepage</a>
    </div>
</body>
</html>
