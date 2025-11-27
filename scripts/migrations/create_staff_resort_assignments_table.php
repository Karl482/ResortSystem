<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Models/User.php';
require_once __DIR__ . '/../../app/Models/Resort.php';

function up() {
    $db = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Create the StaffResortAssignments table
    $sql = <<<SQL
    CREATE TABLE IF NOT EXISTS StaffResortAssignments (
        UserID INT NOT NULL,
        ResortID INT NOT NULL,
        PRIMARY KEY (UserID, ResortID),
        FOREIGN KEY (UserID) REFERENCES Users(UserID) ON DELETE CASCADE,
        FOREIGN KEY (ResortID) REFERENCES Resorts(ResortID) ON DELETE CASCADE
    ) ENGINE=InnoDB;
SQL;
    $db->exec($sql);
    echo "Table 'StaffResortAssignments' created successfully.\n";

    // 2. Assign all existing resorts to all existing staff members
    $staffUsers = User::findByRole('Staff');
    $resorts = Resort::findAll();

    if (empty($staffUsers) || empty($resorts)) {
        echo "No staff or resorts found to assign.\n";
        return;
    }

    $stmt = $db->prepare("INSERT IGNORE INTO StaffResortAssignments (UserID, ResortID) VALUES (:userId, :resortId)");

    $assignmentsCount = 0;
    foreach ($staffUsers as $staff) {
        foreach ($resorts as $resort) {
            $stmt->execute([
                ':userId' => $staff['UserID'],
                ':resortId' => $resort->resortId
            ]);
            $assignmentsCount++;
        }
    }
    echo "Assigned all " . count($resorts) . " resorts to " . count($staffUsers) . " staff members. Total assignments: $assignmentsCount.\n";
}

function down() {
    $db = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "DROP TABLE IF EXISTS StaffResortAssignments;";
    $db->exec($sql);
    echo "Table 'StaffResortAssignments' dropped successfully.\n";
}

// Check command line arguments to run up() or down()
if (isset($argv[1])) {
    if ($argv[1] === 'up') {
        up();
    } elseif ($argv[1] === 'down') {
        down();
    }
}