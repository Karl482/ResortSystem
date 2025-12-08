<?php

/**
 * RescheduleRequest Model
 * Handles customer/staff reschedule requests that require admin approval
 */
class RescheduleRequest {
    public $requestId;
    public $bookingId;
    public $requestedBy;
    public $currentDate;
    public $currentTimeSlot;
    public $requestedDate;
    public $requestedTimeSlot;
    public $reason;
    public $status;
    public $reviewedBy;
    public $reviewedAt;
    public $reviewNotes;
    public $createdAt;

    private static $db;

    private static function getDB() {
        require_once __DIR__ . '/../Helpers/Database.php';
        return Database::getInstance();
    }

    /**
     * Create a new reschedule request
     */
    public static function create($bookingId, $requestedBy, $currentDate, $currentTimeSlot, $requestedDate, $requestedTimeSlot, $reason = null) {
        try {
            $db = self::getDB();

            // Check if there's already a pending request for this booking
            $existingRequest = self::getPendingRequestByBookingId($bookingId);
            if ($existingRequest) {
                return ['success' => false, 'error' => 'There is already a pending reschedule request for this booking.'];
            }

            $stmt = $db->prepare(
                "INSERT INTO RescheduleRequests (BookingID, RequestedBy, CurrentDate, CurrentTimeSlot, RequestedDate, RequestedTimeSlot, Reason, Status)
                 VALUES (:bookingId, :requestedBy, :currentDate, :currentTimeSlot, :requestedDate, :requestedTimeSlot, :reason, 'Pending')"
            );

            $stmt->bindValue(':bookingId', $bookingId, PDO::PARAM_INT);
            $stmt->bindValue(':requestedBy', $requestedBy, PDO::PARAM_INT);
            $stmt->bindValue(':currentDate', $currentDate, PDO::PARAM_STR);
            $stmt->bindValue(':currentTimeSlot', $currentTimeSlot, PDO::PARAM_STR);
            $stmt->bindValue(':requestedDate', $requestedDate, PDO::PARAM_STR);
            $stmt->bindValue(':requestedTimeSlot', $requestedTimeSlot, PDO::PARAM_STR);
            $stmt->bindValue(':reason', $reason, PDO::PARAM_STR);

            if ($stmt->execute()) {
                $requestId = $db->lastInsertId();
                return ['success' => true, 'request_id' => $requestId];
            }

            return ['success' => false, 'error' => 'Failed to create reschedule request.'];
        } catch (Exception $e) {
            // Return a friendly error instead of letting the exception bubble up to the app
            return ['success' => false, 'error' => 'Unable to submit reschedule request: ' . $e->getMessage()];
        }
    }

    /**
     * Get pending request by booking ID
     */
    public static function getPendingRequestByBookingId($bookingId) {
        $db = self::getDB();
        $stmt = $db->prepare(
            "SELECT * FROM RescheduleRequests WHERE BookingID = :bookingId AND Status = 'Pending' ORDER BY CreatedAt DESC LIMIT 1"
        );
        $stmt->bindValue(':bookingId', $bookingId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    /**
     * Get all pending reschedule requests
     */
    public static function getAllPendingRequests($resortId = null, $page = null, $perPage = null) {
        $db = self::getDB();
        
        $sql = "SELECT rr.*, 
                       b.ResortID, b.Status as BookingStatus,
                       r.Name as ResortName,
                       u.Username as RequestedByName, u.Email as RequestedByEmail, u.Role as RequestedByRole
                FROM RescheduleRequests rr
                JOIN Bookings b ON rr.BookingID = b.BookingID
                JOIN Resorts r ON b.ResortID = r.ResortID
                JOIN Users u ON rr.RequestedBy = u.UserID
                WHERE rr.Status = 'Pending'";
        
        if ($resortId) {
            $sql .= " AND b.ResortID = :resortId";
        }
        
        $sql .= " ORDER BY rr.CreatedAt ASC";
        
        // Add pagination if provided
        if ($page !== null && $perPage !== null) {
            $offset = ($page - 1) * $perPage;
            $sql .= " LIMIT :limit OFFSET :offset";
        }
        
        $stmt = $db->prepare($sql);
        
        if ($resortId) {
            $stmt->bindValue(':resortId', $resortId, PDO::PARAM_INT);
        }
        
        // Bind pagination parameters if provided
        if ($page !== null && $perPage !== null) {
            $stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)(($page - 1) * $perPage), PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Count all pending reschedule requests
     */
    public static function countPendingRequests($resortId = null) {
        $db = self::getDB();
        
        $sql = "SELECT COUNT(*) as total
                FROM RescheduleRequests rr
                JOIN Bookings b ON rr.BookingID = b.BookingID
                WHERE rr.Status = 'Pending'";
        
        if ($resortId) {
            $sql .= " AND b.ResortID = :resortId";
        }
        
        $stmt = $db->prepare($sql);
        
        if ($resortId) {
            $stmt->bindValue(':resortId', $resortId, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return (int)$result['total'];
    }

    /**
     * Get reschedule requests by user
     */
    public static function getRequestsByUserId($userId) {
        $db = self::getDB();
        $stmt = $db->prepare(
            "SELECT rr.*, 
                    b.ResortID,
                    r.Name as ResortName,
                    reviewer.Username as ReviewedByName
             FROM RescheduleRequests rr
             JOIN Bookings b ON rr.BookingID = b.BookingID
             JOIN Resorts r ON b.ResortID = r.ResortID
             LEFT JOIN Users reviewer ON rr.ReviewedBy = reviewer.UserID
             WHERE rr.RequestedBy = :userId
             ORDER BY rr.CreatedAt DESC"
        );
        $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Approve reschedule request
     */
    public static function approve($requestId, $adminUserId, $reviewNotes = null) {
        $db = self::getDB();
        
        // Get request details
        $request = self::findById($requestId);
        if (!$request || $request->Status !== 'Pending') {
            return ['success' => false, 'error' => 'Request not found or already processed.'];
        }
        
        // Check if the requested date/time is still available
        require_once __DIR__ . '/Booking.php';
        require_once __DIR__ . '/BookingFacilities.php';
        
        $booking = Booking::findById($request->BookingID);
        if (!$booking) {
            return ['success' => false, 'error' => 'Booking not found.'];
        }
        
        $facilityIds = BookingFacilities::getFacilityIds($request->BookingID);
        $isAvailable = Booking::isResortTimeframeAvailable(
            $booking->resortId,
            $request->RequestedDate,
            $request->RequestedTimeSlot,
            $facilityIds,
            $request->BookingID
        );
        
        if (!$isAvailable) {
            return ['success' => false, 'error' => 'The requested date and time slot is no longer available.'];
        }
        
        $db->beginTransaction();
        
        try {
            // Update request status
            $stmt = $db->prepare(
                "UPDATE RescheduleRequests 
                 SET Status = 'Approved', ReviewedBy = :adminUserId, ReviewedAt = NOW(), ReviewNotes = :reviewNotes
                 WHERE RequestID = :requestId"
            );
            $stmt->bindValue(':adminUserId', $adminUserId, PDO::PARAM_INT);
            $stmt->bindValue(':reviewNotes', $reviewNotes, PDO::PARAM_STR);
            $stmt->bindValue(':requestId', $requestId, PDO::PARAM_INT);
            $stmt->execute();
            
            // Update the booking
            $booking->bookingDate = $request->RequestedDate;
            $booking->timeSlotType = $request->RequestedTimeSlot;
            Booking::update($booking);
            
            // Log in audit trail
            require_once __DIR__ . '/BookingAuditTrail.php';
            $oldDateDisplay = $request->CurrentDate . ' (' . $request->CurrentTimeSlot . ')';
            $newDateDisplay = $request->RequestedDate . ' (' . $request->RequestedTimeSlot . ')';
            
            BookingAuditTrail::logChange(
                $request->BookingID,
                $adminUserId,
                'UPDATE',
                'BookingDate & TimeSlot',
                $oldDateDisplay,
                $newDateDisplay,
                'Reschedule request approved. Reason: ' . ($request->Reason ?: 'Not specified')
            );
            
            $db->commit();
            
            // Send notification to customer
            require_once __DIR__ . '/../Helpers/Notification.php';
            Notification::sendBookingRescheduleNotification($request->BookingID, $request->RequestedDate, $request->RequestedTimeSlot);
            
            return ['success' => true];
            
        } catch (Exception $e) {
            $db->rollback();
            return ['success' => false, 'error' => 'Failed to approve request: ' . $e->getMessage()];
        }
    }

    /**
     * Reject reschedule request
     */
    public static function reject($requestId, $adminUserId, $reviewNotes = null) {
        $db = self::getDB();
        
        $request = self::findById($requestId);
        if (!$request || $request->Status !== 'Pending') {
            return ['success' => false, 'error' => 'Request not found or already processed.'];
        }
        
        $stmt = $db->prepare(
            "UPDATE RescheduleRequests 
             SET Status = 'Rejected', ReviewedBy = :adminUserId, ReviewedAt = NOW(), ReviewNotes = :reviewNotes
             WHERE RequestID = :requestId"
        );
        $stmt->bindValue(':adminUserId', $adminUserId, PDO::PARAM_INT);
        $stmt->bindValue(':reviewNotes', $reviewNotes, PDO::PARAM_STR);
        $stmt->bindValue(':requestId', $requestId, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            return ['success' => true];
        }
        
        return ['success' => false, 'error' => 'Failed to reject request.'];
    }

    /**
     * Find request by ID
     */
    public static function findById($requestId) {
        $db = self::getDB();
        $stmt = $db->prepare("SELECT * FROM RescheduleRequests WHERE RequestID = :requestId");
        $stmt->bindValue(':requestId', $requestId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    /**
     * Get pending request count
     */
    public static function getPendingCount($resortId = null) {
        try {
            $db = self::getDB();

            $sql = "SELECT COUNT(*) FROM RescheduleRequests rr
                    JOIN Bookings b ON rr.BookingID = b.BookingID
                    WHERE rr.Status = 'Pending'";

            if ($resortId) {
                $sql .= " AND b.ResortID = :resortId";
            }

            $stmt = $db->prepare($sql);

            if ($resortId) {
                $stmt->bindValue(':resortId', $resortId, PDO::PARAM_INT);
            }

            $stmt->execute();
            return (int) $stmt->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Cancel a pending request (by customer)
     */
    public static function cancel($requestId, $userId) {
        $db = self::getDB();
        
        $request = self::findById($requestId);
        if (!$request || $request->Status !== 'Pending') {
            return ['success' => false, 'error' => 'Request not found or already processed.'];
        }
        
        // Verify the user owns this request
        if ($request->RequestedBy != $userId) {
            return ['success' => false, 'error' => 'Unauthorized.'];
        }
        
        $stmt = $db->prepare("DELETE FROM RescheduleRequests WHERE RequestID = :requestId");
        $stmt->bindValue(':requestId', $requestId, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            return ['success' => true];
        }
        
        return ['success' => false, 'error' => 'Failed to cancel request.'];
    }
}
