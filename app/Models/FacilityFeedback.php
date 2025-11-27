<?php

class FacilityFeedback {
    public $facilityFeedbackId;
    public $feedbackId;
    public $facilityId;
    public $rating;
    public $comment;
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

    public static function create(FacilityFeedback $feedback) {
        \ErrorHandler::log("Creating FacilityFeedback: feedbackId={$feedback->feedbackId}, facilityId={$feedback->facilityId}, rating={$feedback->rating}", 'DEBUG');

        // Validate rating
        if ($feedback->rating < 1 || $feedback->rating > 5) {
            \ErrorHandler::log("Invalid rating: {$feedback->rating}", 'ERROR');
            return false;
        }

        $db = self::getDB();
        $stmt = $db->prepare(
            "INSERT INTO FacilityFeedback (FeedbackID, FacilityID, Rating, Comment)
             VALUES (:feedbackId, :facilityId, :rating, :comment)"
        );
        $stmt->bindValue(':feedbackId', $feedback->feedbackId, PDO::PARAM_INT);
        $stmt->bindValue(':facilityId', $feedback->facilityId, PDO::PARAM_INT);
        $stmt->bindValue(':rating', $feedback->rating, PDO::PARAM_INT);
        $stmt->bindValue(':comment', $feedback->comment, PDO::PARAM_STR);

        if ($stmt->execute()) {
            $id = $db->lastInsertId();
            \ErrorHandler::log("FacilityFeedback created with ID: $id", 'DEBUG');
            return $id;
        }
        \ErrorHandler::log("Failed to execute FacilityFeedback insert", 'ERROR');
        return false;
    }
}
