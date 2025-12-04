<?php
require_once __DIR__ . '/../../config/database.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("SHOW COLUMNS FROM `Bookings`");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Columns in Bookings table:\n";
    foreach ($cols as $c) {
        echo sprintf("- %s (%s) %s\n", $c['Field'], $c['Type'], $c['Null'] === 'NO' ? 'NOT NULL' : 'NULL');
    }
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
