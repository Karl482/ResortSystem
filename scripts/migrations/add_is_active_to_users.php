<?php
/**
 * Migration: Add IsActive column to Users table for deactivation/activation
 * 
 * This migration adds a boolean flag to allow admins to deactivate customer accounts
 * instead of permanently deleting them.
 */

require_once __DIR__ . '/../../config/database.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Adding IsActive column to Users table...\n";

    // Add IsActive column, defaulting to 1 (true) for all existing users
    $sql = "ALTER TABLE Users 
            ADD COLUMN IsActive BOOLEAN NOT NULL DEFAULT 1 
            AFTER AdminType";
    
    $pdo->exec($sql);
    echo "✓ IsActive column added successfully.\n";

    echo "\nMigration completed successfully!\n";
    echo "All existing users have been set to 'Active'.\n";

} catch (PDOException $e) {
    die("Migration failed: " . $e->getMessage() . "\n");
}