<?php
// Script to add 'Archived' status to Bookings table
require_once __DIR__ . '/config/database.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check current Status column definition
    $stmt = $pdo->query("SHOW COLUMNS FROM Bookings WHERE Field = 'Status'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Current Status column type: " . $column['Type'] . "\n\n";
    
    // Modify the Status column to include 'Archived'
    $sql = "ALTER TABLE Bookings 
            MODIFY COLUMN Status ENUM('Pending', 'Confirmed', 'Cancelled', 'Completed', 'Archived') 
            DEFAULT 'Pending'";
    
    $pdo->exec($sql);
    
    echo "✅ SUCCESS! 'Archived' status has been added to the Bookings table!\n";
    echo "You can now archive bookings.\n";
    echo "\nYou can delete this file now: add_archived_status.php\n";
    
} catch (PDOException $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    
    if (strpos($e->getMessage(), "Duplicate entry") !== false) {
        echo "\n'Archived' status already exists in the database.\n";
    }
}
?>
