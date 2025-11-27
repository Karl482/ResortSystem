<?php

class BlockedFacilityAvailability {
    public $blockedAvailabilityId;
    public $facilityId;
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

    public static function create($facilityId, $blockDate, $reason) {
        if (self::isBlocked($facilityId, $blockDate)) {
            return false;
        }

        $db = self::getDB();
        $stmt = $db->prepare(
            "INSERT INTO BlockedFacilityAvailability (FacilityID, BlockDate, Reason)
             VALUES (:facilityId, :blockDate, :reason)"
        );
        $stmt->bindValue(':facilityId', $facilityId, PDO::PARAM_INT);
        $stmt->bindValue(':blockDate', $blockDate, PDO::PARAM_STR);
        $stmt->bindValue(':reason', $reason, PDO::PARAM_STR);
        
        return $stmt->execute();
    }

    public static function findByFacilityId($facilityId) {
        $db = self::getDB();
        $stmt = $db->prepare("SELECT * FROM BlockedFacilityAvailability WHERE FacilityID = :facilityId ORDER BY BlockDate ASC");
        $stmt->bindValue(':facilityId', $facilityId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public static function delete($blockedAvailabilityId) {
        $db = self::getDB();
        $stmt = $db->prepare("DELETE FROM BlockedFacilityAvailability WHERE BlockedAvailabilityID = :id");
        $stmt->bindValue(':id', $blockedAvailabilityId, PDO::PARAM_INT);
        return $stmt->execute();
    }
    public static function deleteAllForFacility($facilityId) {
        $db = self::getDB();
        $stmt = $db->prepare("DELETE FROM BlockedFacilityAvailability WHERE FacilityID = :facilityId");
        $stmt->bindValue(':facilityId', $facilityId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount();
    }

    public static function deleteByDateRangeAndFacility($facilityId, $startDate, $endDate) {
        $db = self::getDB();
        $stmt = $db->prepare(
            "DELETE FROM BlockedFacilityAvailability
             WHERE FacilityID = :facilityId
             AND BlockDate BETWEEN :startDate AND :endDate"
        );
        $stmt->bindValue(':facilityId', $facilityId, PDO::PARAM_INT);
        $stmt->bindValue(':startDate', $startDate, PDO::PARAM_STR);
        $stmt->bindValue(':endDate', $endDate, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->rowCount();
    }

    public static function isBlocked($facilityId, $blockDate) {
        $db = self::getDB();
        $stmt = $db->prepare("SELECT COUNT(*) FROM BlockedFacilityAvailability WHERE FacilityID = :facilityId AND BlockDate = :blockDate");
        $stmt->bindValue(':facilityId', $facilityId, PDO::PARAM_INT);
        $stmt->bindValue(':blockDate', $blockDate, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }

    public static function isFacilityBlockedOnDate($facilityId, $date) {
        $db = self::getDB();
        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM BlockedFacilityAvailability
             WHERE FacilityID = :facilityId AND BlockDate = :date"
        );
        $stmt->bindValue(':facilityId', $facilityId, PDO::PARAM_INT);
        $stmt->bindValue(':date', $date, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }

    public static function clearOldBlocks() {
        $db = self::getDB();
        $stmt = $db->prepare(
            "DELETE FROM BlockedFacilityAvailability
             WHERE BlockDate < CURDATE()"
        );
        $stmt->execute();
        return $stmt->rowCount();
    }
}
