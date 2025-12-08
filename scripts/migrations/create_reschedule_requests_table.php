<?php

require_once __DIR__ . '/../../config/database.php';

function up() {
    $db = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = <<<SQL
    CREATE TABLE IF NOT EXISTS RescheduleRequests (
        RequestID INT AUTO_INCREMENT PRIMARY KEY,
        BookingID INT NOT NULL,
        RequestedBy INT NOT NULL,
        CurrentDate DATE NOT NULL,
        CurrentTimeSlot VARCHAR(50) NOT NULL,
        RequestedDate DATE NOT NULL,
        RequestedTimeSlot VARCHAR(50) NOT NULL,
        Reason TEXT NULL,
        Status ENUM('Pending','Approved','Rejected','Cancelled') NOT NULL DEFAULT 'Pending',
        ReviewedBy INT NULL,
        ReviewedAt DATETIME NULL,
        ReviewNotes TEXT NULL,
        CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_booking_id (BookingID),
        INDEX idx_requested_by (RequestedBy),
        INDEX idx_status (Status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

    $db->exec($sql);
    echo "Table 'RescheduleRequests' created successfully.\n";
}

function down() {
    $db = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $db->exec("DROP TABLE IF EXISTS RescheduleRequests;");
    echo "Table 'RescheduleRequests' dropped successfully.\n";
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
