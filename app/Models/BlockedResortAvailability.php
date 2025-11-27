<?php

class BlockedResortAvailability {
    public $blockedAvailabilityId;
    public $resortId;
    public $blockDate;
    public $reason;

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

    public static function create($resortId, $blockDate, $reason) {
        if (self::isBlocked($resortId, $blockDate)) {
            return false; // Indicate that the date is already blocked
        }

        $db = self::getDB();
        $stmt = $db->prepare(
            "INSERT INTO BlockedResortAvailability (ResortID, BlockDate, Reason)
             VALUES (:resortId, :blockDate, :reason)"
        );
        $stmt->bindValue(':resortId', $resortId, PDO::PARAM_INT);
        $stmt->bindValue(':blockDate', $blockDate, PDO::PARAM_STR);
        $stmt->bindValue(':reason', $reason, PDO::PARAM_STR);
        
        return $stmt->execute();
    }

    public static function isBlocked($resortId, $blockDate) {
        $db = self::getDB();
        $stmt = $db->prepare("SELECT COUNT(*) FROM BlockedResortAvailability WHERE ResortID = :resortId AND BlockDate = :blockDate");
        $stmt->bindValue(':resortId', $resortId, PDO::PARAM_INT);
        $stmt->bindValue(':blockDate', $blockDate, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }

    public static function findByResortId($resortId) {
        $db = self::getDB();
        $stmt = $db->prepare("SELECT * FROM BlockedResortAvailability WHERE ResortID = :resortId ORDER BY BlockDate ASC");
        $stmt->bindValue(':resortId', $resortId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public static function delete($blockedAvailabilityId) {
        $db = self::getDB();
        $stmt = $db->prepare("DELETE FROM BlockedResortAvailability WHERE BlockedAvailabilityID = :id");
        $stmt->bindValue(':id', $blockedAvailabilityId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public static function deleteAllForResort($resortId) {
        $db = self::getDB();
        $stmt = $db->prepare("DELETE FROM BlockedResortAvailability WHERE ResortID = :resortId");
        $stmt->bindValue(':resortId', $resortId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount();
    }

    public static function deleteByDateRange($resortId, $startDate, $endDate) {
        $db = self::getDB();
        $stmt = $db->prepare(
            "DELETE FROM BlockedResortAvailability
             WHERE ResortID = :resortId
             AND BlockDate BETWEEN :startDate AND :endDate"
        );
        $stmt->bindValue(':resortId', $resortId, PDO::PARAM_INT);
        $stmt->bindValue(':startDate', $startDate, PDO::PARAM_STR);
        $stmt->bindValue(':endDate', $endDate, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->rowCount();
    }

    public static function clearOldBlocks() {
        $db = self::getDB();
        $stmt = $db->prepare(
            "DELETE FROM BlockedResortAvailability
             WHERE BlockDate < CURDATE()"
        );
        $stmt->execute();
        return $stmt->rowCount();
    }
}