<?php

class Feedback {
    public $feedbackId;
    public $bookingId;
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

    public static function create(Feedback $feedback) {
        $db = self::getDB();
        $stmt = $db->prepare(
            "INSERT INTO Feedback (BookingID, Rating, Comment)
             VALUES (:bookingId, :rating, :comment)"
        );
        $stmt->bindValue(':bookingId', $feedback->bookingId, PDO::PARAM_INT);
        $stmt->bindValue(':rating', $feedback->rating, PDO::PARAM_INT);
        $stmt->bindValue(':comment', $feedback->comment, PDO::PARAM_STR);

        if ($stmt->execute()) {
            return $db->lastInsertId();
        }
        return false;
    }

    public static function findByBookingId($bookingId) {
        $db = self::getDB();
        $stmt = $db->prepare("SELECT * FROM Feedback WHERE BookingID = :bookingId");
        $stmt->bindValue(':bookingId', $bookingId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchObject('Feedback');
    }

    public static function findAll($resortIds = null) {
        $db = self::getDB();
        $query = "SELECT f.FeedbackID, f.BookingID, f.Rating, f.Comment, f.CreatedAt, b.BookingDate, u.UserID as CustomerID, u.Username as CustomerName,
                         r.Name as ResortName, b.ResortID,
                         (SELECT GROUP_CONCAT(fac.Name SEPARATOR ', ') FROM Facilities fac JOIN BookingFacilities bf ON fac.FacilityID = bf.FacilityID WHERE bf.BookingID = b.BookingID) as IncludedFacilities,
                         GROUP_CONCAT(DISTINCT CONCAT(fm.MediaType, ':::', fm.MediaURL) SEPARATOR '|||') AS Media
                  FROM Feedback f
                  JOIN Bookings b ON f.BookingID = b.BookingID
                  JOIN Users u ON b.CustomerID = u.UserID
                  JOIN Resorts r ON b.ResortID = r.ResortID
                  LEFT JOIN FeedbackMedia fm ON f.FeedbackID = fm.FeedbackID";

        $conditions = [];
        $params = [];

        if ($resortIds !== null) {
            if (is_array($resortIds)) {
                if (empty($resortIds)) {
                    return []; // No resorts assigned, return empty result
                }
                $placeholders = implode(',', array_fill(0, count($resortIds), '?'));
                $conditions[] = "b.ResortID IN ($placeholders)";
                $params = $resortIds;
            } else {
                $conditions[] = "b.ResortID = ?";
                $params[] = $resortIds;
            }
        }

        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }

        $query .= " GROUP BY f.FeedbackID ORDER BY f.CreatedAt DESC";

        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($results as &$result) {
            $media = [];
            if (!empty($result['Media'])) {
                $items = explode('|||', $result['Media']);
                foreach ($items as $item) {
                    list($type, $url) = explode(':::', $item);
                    $media[] = ['MediaType' => $type, 'MediaURL' => $url];
                }
            }
            $result['Media'] = $media;
        }

        return $results;
    }

    public static function findAllFacilityFeedbacks($resortIds = null) {
        $db = self::getDB();
        $query = "SELECT ff.FacilityFeedbackID, ff.FeedbackID, ff.FacilityID, ff.Rating, ff.Comment, ff.CreatedAt,
                         u.UserID as CustomerID, u.Username as CustomerName, fac.Name as FacilityName, r.Name as ResortName, b.BookingDate, r.ResortID,
                         GROUP_CONCAT(DISTINCT CONCAT(fm.MediaType, ':::', fm.MediaURL) SEPARATOR '|||') AS Media
                  FROM FacilityFeedback ff
                  JOIN Feedback f ON ff.FeedbackID = f.FeedbackID
                  JOIN Bookings b ON f.BookingID = b.BookingID
                  JOIN Users u ON b.CustomerID = u.UserID
                  JOIN Facilities fac ON ff.FacilityID = fac.FacilityID
                  JOIN Resorts r ON b.ResortID = r.ResortID
                  LEFT JOIN FeedbackMedia fm ON f.FeedbackID = fm.FeedbackID";

        $conditions = [];
        $params = [];

        if ($resortIds !== null) {
            if (is_array($resortIds)) {
                if (empty($resortIds)) {
                    return []; // No resorts assigned, return empty result
                }
                $placeholders = implode(',', array_fill(0, count($resortIds), '?'));
                $conditions[] = "b.ResortID IN ($placeholders)";
                $params = $resortIds;
            } else {
                $conditions[] = "b.ResortID = ?";
                $params[] = $resortIds;
            }
        }

        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }

        $query .= " GROUP BY ff.FacilityFeedbackID ORDER BY ff.CreatedAt DESC";

        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($results as &$result) {
            $media = [];
            if (!empty($result['Media'])) {
                $items = explode('|||', $result['Media']);
                foreach ($items as $item) {
                    list($type, $url) = explode(':::', $item);
                    $media[] = ['MediaType' => $type, 'MediaURL' => $url];
                }
            }
            $result['Media'] = $media;
        }

        return $results;
    }

   public static function findByFacilityId($facilityId) {
       $db = self::getDB();
       $stmt = $db->prepare(
           "SELECT ff.Rating, ff.Comment, ff.CreatedAt, u.Username as CustomerName, CONCAT('Feedback for ', fac.Name) as FeedbackContext
            FROM FacilityFeedback ff
            JOIN Feedback f ON ff.FeedbackID = f.FeedbackID
            JOIN Bookings b ON f.BookingID = b.BookingID
            JOIN Users u ON b.CustomerID = u.UserID
            JOIN Facilities fac ON ff.FacilityID = fac.FacilityID
            WHERE ff.FacilityID = :facilityId
            ORDER BY ff.CreatedAt DESC"
       );
       $stmt->bindValue(':facilityId', $facilityId, PDO::PARAM_INT);
       $stmt->execute();
       return $stmt->fetchAll(PDO::FETCH_OBJ);
   }

   public static function findByResortId($resortId) {
       $db = self::getDB();
       $stmt = $db->prepare(
           "SELECT f.Rating, f.Comment, f.CreatedAt, u.UserID as CustomerID, u.Username as CustomerName,
                   (SELECT GROUP_CONCAT(fac.Name SEPARATOR ', ') FROM Facilities fac JOIN BookingFacilities bf ON fac.FacilityID = bf.FacilityID WHERE bf.BookingID = b.BookingID) as IncludedFacilities,
                   GROUP_CONCAT(DISTINCT CONCAT(fm.MediaType, ':::', fm.MediaURL) SEPARATOR '|||') AS Media
            FROM Feedback f
            JOIN Bookings b ON f.BookingID = b.BookingID
            JOIN Users u ON b.CustomerID = u.UserID
            LEFT JOIN FeedbackMedia fm ON f.FeedbackID = fm.FeedbackID
            WHERE b.ResortID = :resortId
            GROUP BY f.FeedbackID
            ORDER BY f.CreatedAt DESC"
       );
       $stmt->bindValue(':resortId', $resortId, PDO::PARAM_INT);
       $stmt->execute();
       $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

       foreach ($results as &$result) {
           $media = [];
           if (!empty($result['Media'])) {
               $items = explode('|||', $result['Media']);
               foreach ($items as $item) {
                   list($type, $url) = explode(':::', $item);
                   $media[] = ['MediaType' => $type, 'MediaURL' => $url];
               }
           }
           $result['Media'] = $media;
       }

       return $results;
   }
    public static function createWithFacilities(Feedback $feedback, $facilityFeedbacks = [], $mediaFiles = []) {
        ErrorHandler::log("Starting feedback submission for booking ID: {$feedback->bookingId}", 'INFO');
        $db = self::getDB();
        $fileProcessingStart = microtime(true);

        // Step 1: Process and save media files BEFORE the transaction
        $processedMedia = [];
        if (!empty($mediaFiles['tmp_name']) && is_array($mediaFiles['tmp_name'])) {
            foreach ($mediaFiles['tmp_name'] as $key => $tmpName) {
                if (empty($tmpName) || $mediaFiles['error'][$key] !== UPLOAD_ERR_OK) {
                    continue;
                }

                $mediaFile = [
                    'name' => $mediaFiles['name'][$key],
                    'type' => $mediaFiles['type'][$key],
                    'tmp_name' => $tmpName,
                    'size' => $mediaFiles['size'][$key]
                ];

                // Validate file type and size
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'video/mp4', 'video/webm'];
                if (!in_array($mediaFile['type'], $allowedTypes) || $mediaFile['size'] > 100000000) { // 100MB limit
                    ErrorHandler::log("Feedback media validation failed for file: " . $mediaFile['name'], 'WARNING');
                    continue; // Skip invalid file
                }

                $uploadDir = __DIR__ . '/../../public/uploads/feedback/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $fileName = uniqid() . '-' . basename($mediaFile['name']);
                $targetFile = $uploadDir . $fileName;

                if (move_uploaded_file($mediaFile['tmp_name'], $targetFile)) {
                    $processedMedia[] = [
                        'mediaURL' => 'public/uploads/feedback/' . $fileName,
                        'mediaType' => strpos($mediaFile['type'], 'video') === 0 ? 'Video' : 'Image',
                        'fullPath' => $targetFile
                    ];
                } else {
                    ErrorHandler::log("Failed to move uploaded feedback media file: " . $mediaFile['name'], 'ERROR');
                }
            }
        }
        $fileProcessingTime = microtime(true) - $fileProcessingStart;
        ErrorHandler::log("File processing completed in {$fileProcessingTime} seconds.", 'INFO');

        try {
            ErrorHandler::log("Starting database transaction.", 'INFO');
            $transactionStart = microtime(true);
            $db->beginTransaction();

            // Step 2: Create main feedback entry
            $stmt = $db->prepare("INSERT INTO Feedback (BookingID, Rating, Comment) VALUES (:bookingId, :rating, :comment)");
            $stmt->bindValue(':bookingId', $feedback->bookingId, PDO::PARAM_INT);
            $stmt->bindValue(':rating', $feedback->rating, PDO::PARAM_INT);
            $stmt->bindValue(':comment', $feedback->comment, PDO::PARAM_STR);
            $stmt->execute();
            $feedbackId = $db->lastInsertId();

            // Step 3: Create facility feedback entries
            if (!empty($facilityFeedbacks)) {
                require_once __DIR__ . '/FacilityFeedback.php';
                $facilityStmt = $db->prepare(
                    "INSERT INTO FacilityFeedback (FeedbackID, FacilityID, Rating, Comment)
                     VALUES (:feedbackId, :facilityId, :rating, :comment)"
                );
                foreach ($facilityFeedbacks as $facilityData) {
                    if (isset($facilityData['rating']) && !empty($facilityData['rating'])) {
                        $facilityStmt->bindValue(':feedbackId', $feedbackId, PDO::PARAM_INT);
                        $facilityStmt->bindValue(':facilityId', $facilityData['id'], PDO::PARAM_INT);
                        $facilityStmt->bindValue(':rating', $facilityData['rating'], PDO::PARAM_INT);
                        $facilityStmt->bindValue(':comment', $facilityData['comment'], PDO::PARAM_STR);
                        $facilityStmt->execute();
                    }
                }
            }

            // Step 4: Insert media records into the database
            if (!empty($processedMedia)) {
                require_once __DIR__ . '/FeedbackMedia.php';
                $mediaStmt = $db->prepare(
                    "INSERT INTO FeedbackMedia (FeedbackID, MediaType, MediaURL)
                     VALUES (:feedbackId, :mediaType, :mediaURL)"
                );
                foreach ($processedMedia as $media) {
                    $mediaStmt->bindValue(':feedbackId', $feedbackId, PDO::PARAM_INT);
                    $mediaStmt->bindValue(':mediaType', $media['mediaType'], PDO::PARAM_STR);
                    $mediaStmt->bindValue(':mediaURL', $media['mediaURL'], PDO::PARAM_STR);
                    $mediaStmt->execute();
                }
            }

            $db->commit();
            $transactionTime = microtime(true) - $transactionStart;
            ErrorHandler::log("Database transaction committed successfully in {$transactionTime} seconds for feedback ID: {$feedbackId}", 'INFO');
            return $feedbackId;
        } catch (Exception $e) {
            $db->rollBack();
            $transactionTime = microtime(true) - $transactionStart;
            ErrorHandler::log("Database transaction rolled back after {$transactionTime} seconds. Error: " . $e->getMessage(), 'ERROR');
            
            // Step 5: Cleanup - delete uploaded files if DB operations fail
            foreach ($processedMedia as $media) {
                if (file_exists($media['fullPath'])) {
                    unlink($media['fullPath']);
                }
            }

            ErrorHandler::log("Feedback submission failed: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }

    /**
     * Get all feedback entries for a specific customer, including facility feedback details.
     */
    public static function findCustomerFeedbackHistory($customerId) {
        $db = self::getDB();
        $stmt = $db->prepare(
            "SELECT
                f.FeedbackID, f.BookingID, f.Rating AS ResortRating, f.Comment AS ResortComment, f.CreatedAt,
                r.Name AS ResortName, b.BookingDate,
                GROUP_CONCAT(DISTINCT CONCAT(fac.Name, ':::', ff.Rating, ':::', ff.Comment) SEPARATOR '|||') AS FacilityFeedbacks,
                GROUP_CONCAT(DISTINCT CONCAT(fm.MediaType, ':::', fm.MediaURL) SEPARATOR '|||') AS Media
             FROM Feedback f
             JOIN Bookings b ON f.BookingID = b.BookingID
             JOIN Resorts r ON b.ResortID = r.ResortID
             LEFT JOIN FacilityFeedback ff ON f.FeedbackID = ff.FeedbackID
             LEFT JOIN Facilities fac ON ff.FacilityID = fac.FacilityID
             LEFT JOIN FeedbackMedia fm ON f.FeedbackID = fm.FeedbackID
             WHERE b.CustomerID = :customerId
             GROUP BY f.FeedbackID
             ORDER BY f.CreatedAt DESC"
        );
        $stmt->bindValue(':customerId', $customerId, PDO::PARAM_INT);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Process results to structure the data
        foreach ($results as &$result) {
            // Process facility feedback
            $facilityFeedbacks = [];
            if (!empty($result['FacilityFeedbacks'])) {
                $items = explode('|||', $result['FacilityFeedbacks']);
                foreach ($items as $item) {
                    list($name, $rating, $comment) = explode(':::', $item);
                    $facilityFeedbacks[] = ['FacilityName' => $name, 'Rating' => $rating, 'Comment' => $comment];
                }
            }
            $result['FacilityFeedbacks'] = $facilityFeedbacks;

            // Process media
            $media = [];
            if (!empty($result['Media'])) {
                $items = explode('|||', $result['Media']);
                foreach ($items as $item) {
                    list($type, $url) = explode(':::', $item);
                    $media[] = ['MediaType' => $type, 'MediaURL' => $url];
                }
            }
            $result['Media'] = $media;
        }

        return $results;
    }

    /**
     * Get all feedback entries for a specific facility with customer details.
     */
    public static function findFacilityFeedbackDetails($facilityId) {
        $db = self::getDB();
        $stmt = $db->prepare(
            "SELECT ff.Rating, ff.Comment, ff.CreatedAt, u.UserID AS CustomerID, u.Username AS CustomerName
             FROM FacilityFeedback ff
             JOIN Feedback f ON ff.FeedbackID = f.FeedbackID
             JOIN Bookings b ON f.BookingID = b.BookingID
             JOIN Users u ON b.CustomerID = u.UserID
             WHERE ff.FacilityID = :facilityId
             ORDER BY ff.CreatedAt DESC"
        );
        $stmt->bindValue(':facilityId', $facilityId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
