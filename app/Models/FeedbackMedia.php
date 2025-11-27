<?php

class FeedbackMedia {
    public $mediaId;
    public $feedbackId;
    public $mediaType;
    public $mediaURL;
    public $createdAt;

    private static $db;

    private static function getDB() {
        if (!self::$db) {
            require_once __DIR__ . '/../../config/database.php';
            try {
                self::$db = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
                self::$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                die("Database connection failed: " . $e->getMessage());
            }
        }
        return self::$db;
    }

    public static function create($feedbackId, $mediaFile) {
        $uploadDir = __DIR__ . '/../../public/uploads/feedback/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = uniqid() . '-' . basename($mediaFile['name']);
        $targetFile = $uploadDir . $fileName;
        $mediaURL = 'public/uploads/feedback/' . $fileName;
        $mediaType = strpos($mediaFile['type'], 'video') === 0 ? 'Video' : 'Image';

        // Validate file type and size
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'video/mp4', 'video/webm'];
        if (!in_array($mediaFile['type'], $allowedTypes) || $mediaFile['size'] > 10000000) { // 10MB limit
            return false;
        }

        if (move_uploaded_file($mediaFile['tmp_name'], $targetFile)) {
            $db = self::getDB();
            $stmt = $db->prepare(
                "INSERT INTO FeedbackMedia (FeedbackID, MediaType, MediaURL)
                 VALUES (:feedbackId, :mediaType, :mediaURL)"
            );
            $stmt->bindValue(':feedbackId', $feedbackId, PDO::PARAM_INT);
            $stmt->bindValue(':mediaType', $mediaType, PDO::PARAM_STR);
            $stmt->bindValue(':mediaURL', $mediaURL, PDO::PARAM_STR);

            return $stmt->execute();
        }
        return false;
    }
}