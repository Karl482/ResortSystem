<?php
/**
 * Migration: Add CancellationNoticePending and CancellationReason to Bookings
 */

require_once __DIR__ . '/../../config/database.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Adding CancellationNoticePending and CancellationReason to Bookings...\n";

    $sqls = [
        "ALTER TABLE Bookings ADD COLUMN CancellationNoticePending TINYINT(1) NOT NULL DEFAULT 0 AFTER ExpiresAt",
        "ALTER TABLE Bookings ADD COLUMN CancellationReason TEXT NULL AFTER CancellationNoticePending"
    ];

    foreach ($sqls as $sql) {
        try {
            $pdo->exec($sql);
            echo "✓ Executed: $sql\n";
        } catch (PDOException $e) {
            echo "Skipped or failed (might already exist): " . $e->getMessage() . "\n";
        }
    }

    echo "\nMigration completed.\n";

} catch (PDOException $e) {
    die("Migration failed: " . $e->getMessage() . "\n");
}
