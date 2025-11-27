<?php

require_once __DIR__ . '/../../config/database.php';

function up() {
    $db = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = <<<SQL
    CREATE TABLE IF NOT EXISTS EmailVerifications (
        VerificationID INT AUTO_INCREMENT PRIMARY KEY,
        Email VARCHAR(255) NOT NULL,
        VerificationCodeHash VARCHAR(255) NOT NULL,
        Payload LONGTEXT NOT NULL,
        Attempts INT NOT NULL DEFAULT 0,
        ExpiresAt DATETIME NOT NULL,
        CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_email (Email),
        INDEX idx_expires_at (ExpiresAt)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

    $db->exec($sql);
    echo "Table 'EmailVerifications' created successfully.\n";
}

function down() {
    $db = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $db->exec("DROP TABLE IF EXISTS EmailVerifications;");
    echo "Table 'EmailVerifications' dropped successfully.\n";
}

if (isset($argv[1])) {
    if ($argv[1] === 'up') {
        up();
    } elseif ($argv[1] === 'down') {
        down();
    }
} else {
    up();
}

