<?php

// PHPMailer SMTP configuration
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_SMTPAUTH', true);
define('MAIL_SMTPSECURE', 'tls');

// Credentials for the sender's email account
define('MAIL_USERNAME', 'your.email@gmail.com'); // Replace with your Gmail address
define('MAIL_PASSWORD', 'your_app_password');   // Replace with your Google App Password
define('MAIL_FROM', 'your.email@gmail.com');      // Replace with your Gmail address
define('MAIL_FROM_NAME', 'Resorts System Admin');
