<?php

/**
 * BookingAuditTrail Model - Phase 6 Enhancement
 * Comprehensive audit trail system for tracking all booking modifications
 */
class BookingAuditTrail {
    public $auditId;
    public $bookingId;
    public $userId; // Who made the change
    public $action; // 'CREATE', 'UPDATE', 'DELETE', 'STATUS_CHANGE', 'PAYMENT_UPDATE'
    public $fieldName; // Which field was changed
    public $oldValue; // Previous value
    public $newValue; // New value
    public $changeReason; // Optional reason for change
    public $ipAddress; // IP address of user making change
    public $userAgent; // Browser/client information
    public $createdAt;

    private static $db;

    private static function getDB() {
        require_once __DIR__ . '/../Helpers/Database.php';
        return Database::getInstance();
    }

    /**
     * Log a booking creation
     */
    public static function logBookingCreation($bookingId, $userId, $bookingData) {
        // Consolidate booking data into a single, readable summary
        $summary = sprintf(
            "Resort ID: %s, Date: %s, Time: %s, Total: %.2f",
            $bookingData['resortId'] ?? 'N/A',
            $bookingData['bookingDate'] ?? 'N/A',
            $bookingData['timeSlotType'] ?? 'N/A',
            $bookingData['totalAmount'] ?? 0.00
        );

        self::logChange(
            $bookingId,
            $userId,
            'CREATE',
            'Booking', // FieldName is now 'Booking'
            null,
            $summary, // NewValue is the consolidated summary
            'Initial booking creation'
        );
    }

    /**
     * Log a field change
     */
    public static function logChange($bookingId, $userId, $action, $fieldName, $oldValue, $newValue, $reason = null) {
        // Don't log if values are the same
        if ($oldValue === $newValue) {
            return true;
        }

        $db = self::getDB();
        $stmt = $db->prepare(
            "INSERT INTO BookingAuditTrail (BookingID, UserID, Action, FieldName, OldValue, NewValue, ChangeReason, IPAddress, UserAgent)
             VALUES (:bookingId, :userId, :action, :fieldName, :oldValue, :newValue, :reason, :ipAddress, :userAgent)"
        );

        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

        $stmt->bindValue(':bookingId', $bookingId, PDO::PARAM_INT);
        $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':action', $action, PDO::PARAM_STR);
        $stmt->bindValue(':fieldName', $fieldName, PDO::PARAM_STR);
        $stmt->bindValue(':oldValue', $oldValue, PDO::PARAM_STR);
        $stmt->bindValue(':newValue', $newValue, PDO::PARAM_STR);
        $stmt->bindValue(':reason', $reason, PDO::PARAM_STR);
        $stmt->bindValue(':ipAddress', $ipAddress, PDO::PARAM_STR);
        $stmt->bindValue(':userAgent', $userAgent, PDO::PARAM_STR);

        return $stmt->execute();
    }

    /**
     * Log booking status change with detailed tracking
     */
    public static function logStatusChange($bookingId, $userId, $oldStatus, $newStatus, $reason = null) {
        return self::logChange($bookingId, $userId, 'STATUS_CHANGE', 'Status', $oldStatus, $newStatus, $reason);
    }

    /**
     * Log payment-related changes
     */
    public static function logPaymentUpdate($bookingId, $userId, $fieldName, $oldValue, $newValue, $reason = null) {
        return self::logChange($bookingId, $userId, 'PAYMENT_UPDATE', $fieldName, $oldValue, $newValue, $reason);
    }

    /**
     * Log booking deletion
     */
    public static function logBookingDeletion($bookingId, $userId, $reason = null) {
        return self::logChange($bookingId, $userId, 'DELETE', 'Entire Booking', 'Active', 'Deleted', $reason);
    }

    /**
     * Get audit trail for a specific booking
     */
    public static function getBookingAuditTrail($bookingId, $limit = null) {
        $db = self::getDB();
        
        $sql = "SELECT bat.*, u.Username, u.Role
                FROM BookingAuditTrail bat
                LEFT JOIN Users u ON bat.UserID = u.UserID
                WHERE bat.BookingID = :bookingId
                ORDER BY bat.CreatedAt DESC";
        
        if ($limit) {
            $sql .= " LIMIT :limit";
        }
        
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':bookingId', $bookingId, PDO::PARAM_INT);
        
        if ($limit) {
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Get recent audit trail across all bookings (admin view)
     */
    public static function getRecentAuditTrail($resortId = null, $limit = 50) {
        $db = self::getDB();
        
        $sql = "SELECT bat.*, u.Username, u.Role, b.BookingDate, r.Name as ResortName
                FROM BookingAuditTrail bat
                LEFT JOIN Users u ON bat.UserID = u.UserID
                LEFT JOIN Bookings b ON bat.BookingID = b.BookingID
                LEFT JOIN Resorts r ON b.ResortID = r.ResortID
                WHERE 1=1";
        
        $params = [];
        if ($resortId) {
            $sql .= " AND b.ResortID = :resortId";
            $params[':resortId'] = $resortId;
        }
        
        $sql .= " ORDER BY bat.CreatedAt DESC LIMIT :limit";
        
        $stmt = $db->prepare($sql);
        foreach ($params as $param => $value) {
            $stmt->bindValue($param, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Get audit statistics
     */
    public static function getAuditStatistics($resortId = null, $days = 30) {
        $db = self::getDB();
        
        $sql = "SELECT 
                    Action,
                    COUNT(*) as ActionCount,
                    COUNT(DISTINCT BookingID) as UniqueBookings,
                    COUNT(DISTINCT UserID) as UniqueUsers
                FROM BookingAuditTrail bat";
        
        if ($resortId) {
            $sql .= " JOIN Bookings b ON bat.BookingID = b.BookingID
                      WHERE b.ResortID = :resortId AND";
        } else {
            $sql .= " WHERE";
        }
        
        $sql .= " bat.CreatedAt >= DATE_SUB(NOW(), INTERVAL :days DAY)
                  GROUP BY Action
                  ORDER BY ActionCount DESC";
        
        $stmt = $db->prepare($sql);
        if ($resortId) {
            $stmt->bindValue(':resortId', $resortId, PDO::PARAM_INT);
        }
        $stmt->bindValue(':days', $days, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Search audit trail by criteria
     */
    public static function searchAuditTrail($criteria = []) {
        $db = self::getDB();
        
        $sql = "SELECT bat.*, u.Username, u.Role, b.BookingDate, r.Name as ResortName
                FROM BookingAuditTrail bat
                LEFT JOIN Users u ON bat.UserID = u.UserID
                LEFT JOIN Bookings b ON bat.BookingID = b.BookingID
                LEFT JOIN Resorts r ON b.ResortID = r.ResortID
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($criteria['booking_id'])) {
            $sql .= " AND bat.BookingID = :bookingId";
            $params[':bookingId'] = $criteria['booking_id'];
        }
        
        if (!empty($criteria['user_id'])) {
            $sql .= " AND bat.UserID = :userId";
            $params[':userId'] = $criteria['user_id'];
        }
        
        if (!empty($criteria['action'])) {
            $sql .= " AND bat.Action = :action";
            $params[':action'] = $criteria['action'];
        }
        
        if (!empty($criteria['field_name'])) {
            $sql .= " AND bat.FieldName = :fieldName";
            $params[':fieldName'] = $criteria['field_name'];
        }
        
        if (!empty($criteria['resort_id'])) {
            $sql .= " AND b.ResortID = :resortId";
            $params[':resortId'] = $criteria['resort_id'];
        }
        
        if (!empty($criteria['date_from'])) {
            $sql .= " AND bat.CreatedAt >= :dateFrom";
            $params[':dateFrom'] = $criteria['date_from'];
        }
        
        if (!empty($criteria['date_to'])) {
            $sql .= " AND bat.CreatedAt <= :dateTo";
            $params[':dateTo'] = $criteria['date_to'];
        }
        
        $sql .= " ORDER BY bat.CreatedAt DESC";
        
        if (!empty($criteria['limit'])) {
            $sql .= " LIMIT :limit";
            $params[':limit'] = $criteria['limit'];
        }
        
        $stmt = $db->prepare($sql);
        foreach ($params as $param => $value) {
            if ($param === ':limit') {
                $stmt->bindValue($param, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($param, $value);
            }
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Get formatted change description
     */
    public static function getChangeDescription($auditRecord) {
        $action = strtolower($auditRecord->Action);
        $field = $auditRecord->FieldName;
        $oldValue = $auditRecord->OldValue;
        $newValue = $auditRecord->NewValue;
        
        switch ($action) {
            case 'create':
                return "Booking created. Details: {$newValue}";
            case 'update':
                return "Updated {$field} from '{$oldValue}' to '{$newValue}'";
            case 'status_change':
                return "Changed booking status from '{$oldValue}' to '{$newValue}'";
            case 'payment_update':
                return "Updated payment {$field} from '{$oldValue}' to '{$newValue}'";
            case 'delete':
                return "Deleted booking";
            default:
                return "Performed {$action} on {$field}";
        }
    }

    /**
     * Clean old audit records (retention policy)
     */
    public static function cleanOldRecords($retentionDays = 365) {
        $db = self::getDB();
        $stmt = $db->prepare("DELETE FROM BookingAuditTrail WHERE CreatedAt < DATE_SUB(NOW(), INTERVAL :days DAY)");
        $stmt->bindValue(':days', $retentionDays, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->rowCount();
    }
}
