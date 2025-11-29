<?php
/**
 * Migration: Add notification preference columns to Users table
 *
 * Adds two boolean columns:
 *  - NotifyOnNewReservation (default 1)
 *  - NotifyOnReservationUpdate (default 1)
 */

require_once __DIR__ . '/../../config/database.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Adding notification preference columns to Users table...\n";

    $sqls = [
        "ALTER TABLE Users ADD COLUMN NotifyOnNewReservation TINYINT(1) NOT NULL DEFAULT 1 AFTER IsActive",
        "ALTER TABLE Users ADD COLUMN NotifyOnReservationUpdate TINYINT(1) NOT NULL DEFAULT 1 AFTER NotifyOnNewReservation"
    ];

    foreach ($sqls as $sql) {
        try {
            $pdo->exec($sql);
            echo "✓ Executed: $sql\n";
        } catch (PDOException $e) {
            echo "Skipped or failed (might already exist): " . $e->getMessage() . "\n";
        }
    }

    echo "\nMigration completed. If columns already existed, they were left unchanged.\n";

} catch (PDOException $e) {
    die("Migration failed: " . $e->getMessage() . "\n");
}
