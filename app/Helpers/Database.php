<?php

class Database {
    private static $instance = null;

    private function __construct() {
        // Private constructor to prevent direct instantiation
    }

    public static function getInstance() {
        if (self::$instance === null) {
            require_once __DIR__ . '/../../config/database.php';
            try {
                $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ];
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                // In a real application, you would log this error, not die
                die("Database connection failed: " . $e->getMessage());
            }
        }
        return self::$instance;
    }

    public static function safeCommit() {
        if (self::$instance && self::$instance->inTransaction()) {
            self::$instance->commit();
        }
    }

    // Prevent cloning of the instance
    private function __clone() {
    }

    // Prevent unserialization of the instance
    public function __wakeup() {
    }
}