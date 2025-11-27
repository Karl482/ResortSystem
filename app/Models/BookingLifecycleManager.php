<?php

/**
 * BookingLifecycleManager - Phase 6 Enhancement
 * Automated booking status transition logic and lifecycle management
 */

require_once __DIR__ . '/Booking.php';
require_once __DIR__ . '/Payment.php';
require_once __DIR__ . '/PaymentSchedule.php';
require_once __DIR__ . '/BookingAuditTrail.php';

class BookingLifecycleManager {
    
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

    /**
     * Process all bookings for automated status transitions
     */
    public static function processAllBookings() {
        $results = [
            'processed' => 0,
            'confirmed' => 0,
            'cancelled' => 0,
            'completed' => 0,
            'overdue' => 0,
            'errors' => []
        ];

        try {
            // Get all active bookings that might need status updates
            $bookings = self::getBookingsForProcessing();
            
            foreach ($bookings as $booking) {
                $results['processed']++;

                $oldStatus = $booking->status;
                $newStatus = self::determineNewStatus($booking);

                if ($newStatus !== $oldStatus) {
                    if (self::updateBookingStatus($booking->bookingId, $oldStatus, $newStatus, 'Automated status transition')) {
                        $results[strtolower($newStatus)]++;
                    } else {
                        $results['errors'][] = "Failed to update booking {$booking->bookingId}";
                    }
                }
            }

            // Update overdue payment schedules
            PaymentSchedule::updateOverdueStatus();
            
        } catch (Exception $e) {
            $results['errors'][] = "System error: " . $e->getMessage();
        }

        return $results;
    }

    /**
     * Process a single booking for automated status transitions, typically after a payment.
     * This is a targeted and efficient alternative to processAllBookings().
     */
    public static function processBookingAfterPayment($bookingId) {
        try {
            // Get the specific booking that needs processing
            $booking = self::getSingleBookingForProcessing($bookingId);
            
            if (!$booking) {
                // Booking might not be in a state that needs processing, which is not an error.
                return ['success' => true, 'message' => 'Booking not found or does not require processing.'];
            }

            $oldStatus = $booking->status;
            $newStatus = self::determineNewStatus($booking);

            if ($newStatus !== $oldStatus) {
                if (self::updateBookingStatus($booking->bookingId, $oldStatus, $newStatus, 'Automated transition after payment')) {
                    return ['success' => true, 'status_changed' => true, 'old' => $oldStatus, 'new' => $newStatus];
                } else {
                    return ['success' => false, 'message' => "Failed to update booking {$booking->bookingId}"];
                }
            }
            
            return ['success' => true, 'status_changed' => false];

        } catch (Exception $e) {
            return ['success' => false, 'message' => "System error: " . $e->getMessage()];
        }
    }

    /**
     * Get a single booking for processing, including its total paid amount.
     */
    private static function getSingleBookingForProcessing($bookingId) {
        $db = self::getDB();
        
        $sql = "SELECT b.*,
                       COALESCE(SUM(CASE WHEN p.Status = 'Verified' THEN p.Amount ELSE 0 END), 0) as TotalPaid
                FROM Bookings b
                LEFT JOIN Payments p ON b.BookingID = p.BookingID
                WHERE b.BookingID = :bookingId
                GROUP BY b.BookingID";
        
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':bookingId', $bookingId, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data) {
            $booking = new Booking();
            $booking->bookingId = $data['BookingID'];
            $booking->customerId = $data['CustomerID'];
            $booking->resortId = $data['ResortID'];
            $booking->facilityId = $data['FacilityID'];
            $booking->bookingDate = $data['BookingDate'];
            $booking->timeSlotType = $data['TimeSlotType'];

            $booking->status = $data['Status'];
            $booking->totalAmount = $data['TotalAmount'];
            $booking->paymentProofURL = $data['PaymentProofURL'];
            $booking->paymentReference = $data['PaymentReference'];
            $booking->remainingBalance = $data['RemainingBalance'];
            $booking->createdAt = $data['CreatedAt'];
            $booking->TotalPaid = $data['TotalPaid'];
            return $booking;
        }
        return null;
    }

    /**
     * Get bookings that need processing
     */
    private static function getBookingsForProcessing() {
        $db = self::getDB();
        
        $sql = "SELECT b.*,
                       COALESCE(SUM(CASE WHEN p.Status = 'Verified' THEN p.Amount ELSE 0 END), 0) as TotalPaid
                FROM Bookings b
                LEFT JOIN Payments p ON b.BookingID = p.BookingID
                WHERE b.Status IN ('Pending', 'Confirmed')
                AND (b.BookingDate <= DATE_ADD(CURDATE(), INTERVAL 1 DAY) OR b.RemainingBalance > 0)
                GROUP BY b.BookingID
                ORDER BY b.BookingDate ASC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $bookings = [];
        foreach ($results as $data) {
            $booking = new Booking();
            $booking->bookingId = $data['BookingID'];
            $booking->customerId = $data['CustomerID'];
            $booking->resortId = $data['ResortID'];
            $booking->facilityId = $data['FacilityID'];
            $booking->bookingDate = $data['BookingDate'];
            $booking->timeSlotType = $data['TimeSlotType'];
            $booking->status = $data['Status'];
            $booking->totalAmount = $data['TotalAmount'];
            $booking->paymentProofURL = $data['PaymentProofURL'];
            $booking->paymentReference = $data['PaymentReference'];
            $booking->remainingBalance = $data['RemainingBalance'];
            $booking->createdAt = $data['CreatedAt'];
            // Add the extra property dynamically
            $booking->TotalPaid = $data['TotalPaid'];
            $bookings[] = $booking;
        }
        return $bookings;
    }

    /**
     * Determine the new status for a booking based on business rules
     */
    private static function determineNewStatus($booking) {
        $currentStatus = $booking->status;
        $bookingDate = new DateTime($booking->bookingDate);
        $today = new DateTime('today'); // Compare against midnight for date-only comparison
        $totalPaid = $booking->TotalPaid ?? 0;
        $totalAmount = $booking->totalAmount ?? 0;
        $remainingBalance = $booking->remainingBalance ?? $totalAmount;

        // Rule 1: Mark as completed if booking date has passed and was confirmed
        if ($currentStatus === 'Confirmed' && $bookingDate < $today) {
            return 'Completed';
        }

        // Rule 2: Auto-confirm if fully paid
        if ($currentStatus === 'Pending' && $remainingBalance <= 0 && $totalPaid >= $totalAmount) {
            return 'Confirmed';
        }

        // Rule 3: Cancel if booking date is today or has passed and still pending with no payment
        if ($currentStatus === 'Pending' && $bookingDate <= $today && $totalPaid <= 0) {
            return 'Cancelled';
        }

        // Rule 4: Cancel if booking date is within 24 hours and less than 50% paid
        $twentyFourHoursFromNow = clone $today;
        $twentyFourHoursFromNow->modify('+1 day');
        
        if ($currentStatus === 'Pending' && $bookingDate <= $twentyFourHoursFromNow) {
            $paidPercentage = $totalAmount > 0 ? ($totalPaid / $totalAmount) : 0;
            if ($paidPercentage < 0.5) {
                return 'Cancelled';
            }
        }

        // Rule 5: Keep current status if no rules apply
        return $currentStatus;
    }

    /**
     * Update booking status with audit trail
     */
    private static function updateBookingStatus($bookingId, $oldStatus, $newStatus, $reason) {
        $db = self::getDB();
        
        $db->beginTransaction();
        
        try {
            // Update booking status
            $stmt = $db->prepare("UPDATE Bookings SET Status = :newStatus WHERE BookingID = :bookingId");
            $stmt->bindValue(':newStatus', $newStatus, PDO::PARAM_STR);
            $stmt->bindValue(':bookingId', $bookingId, PDO::PARAM_INT);
            $stmt->execute();

            // Log the change in audit trail
            BookingAuditTrail::logStatusChange($bookingId, 1, $oldStatus, $newStatus, $reason); // Use system user ID = 1

            // Handle status-specific actions
            self::handleStatusSpecificActions($bookingId, $newStatus);

            $db->commit();
            return true;

        } catch (Exception $e) {
            $db->rollback();
            return false;
        }
    }

    /**
     * Handle actions specific to certain status changes
     */
    private static function handleStatusSpecificActions($bookingId, $newStatus) {
        switch ($newStatus) {
            case 'Cancelled':
                // Cancel remaining payment schedules
                PaymentSchedule::cancelRemainingSchedules($bookingId);
                // Could send cancellation notification here
                break;
                
            case 'Confirmed':
                // Could send confirmation notification here
                break;
                
            case 'Completed':
                // Could trigger post-visit actions (feedback request, etc.)
                break;
        }
    }

    /**
     * Manual status change with validation
     */
    public static function changeBookingStatus($bookingId, $newStatus, $userId, $reason = null) {
        $booking = Booking::findById($bookingId);
        
        if (!$booking) {
            return ['success' => false, 'message' => 'Booking not found'];
        }

        // Validate status transition
        $validationResult = self::validateStatusTransition($booking->status, $newStatus);
        if (!$validationResult['valid']) {
            return ['success' => false, 'message' => $validationResult['message']];
        }

        // Perform the status change
        if (self::updateBookingStatus($bookingId, $booking->status, $newStatus, $reason ?: 'Manual status change')) {
            return ['success' => true, 'message' => 'Status updated successfully'];
        } else {
            return ['success' => false, 'message' => 'Failed to update status'];
        }
    }

    /**
     * Validate if a status transition is allowed
     */
    private static function validateStatusTransition($currentStatus, $newStatus) {
        $allowedTransitions = [
            'Pending' => ['Confirmed', 'Cancelled'],
            'Confirmed' => ['Completed', 'Cancelled'],
            'Completed' => [], // Usually no transitions from completed
            'Cancelled' => ['Pending'] // Allow reactivation
        ];

        if (!isset($allowedTransitions[$currentStatus])) {
            return ['valid' => false, 'message' => "Unknown current status: {$currentStatus}"];
        }

        if (!in_array($newStatus, $allowedTransitions[$currentStatus])) {
            return ['valid' => false, 'message' => "Cannot change status from {$currentStatus} to {$newStatus}"];
        }

        return ['valid' => true, 'message' => 'Status transition is valid'];
    }

    /**
     * Get booking lifecycle summary
     */
    public static function getBookingLifecycleSummary($resortId = null) {
        $db = self::getDB();
        
        $sql = "SELECT 
                    Status,
                    COUNT(*) as Count,
                    SUM(TotalAmount) as TotalValue,
                    AVG(DATEDIFF(CURDATE(), CreatedAt)) as AvgDaysOld
                FROM Bookings b";
        
        if ($resortId) {
            $sql .= " WHERE b.ResortID = :resortId";
        }
        
        $sql .= " GROUP BY Status
                  ORDER BY 
                    CASE Status
                        WHEN 'Pending' THEN 1
                        WHEN 'Confirmed' THEN 2
                        WHEN 'Completed' THEN 3
                        WHEN 'Cancelled' THEN 4
                        ELSE 5
                    END";
        
        $stmt = $db->prepare($sql);
        if ($resortId) {
            $stmt->bindValue(':resortId', $resortId, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Get bookings requiring attention
     */
    public static function getBookingsRequiringAttention($resortId = null) {
        $db = self::getDB();
        
        $sql = "SELECT b.*, u.Username as CustomerName, r.Name as ResortName,
                       COALESCE(SUM(CASE WHEN p.Status = 'Verified' THEN p.Amount ELSE 0 END), 0) as TotalPaid,
                       DATEDIFF(b.BookingDate, CURDATE()) as DaysUntilBooking
                FROM Bookings b
                LEFT JOIN Users u ON b.CustomerID = u.UserID
                LEFT JOIN Resorts r ON b.ResortID = r.ResortID
                LEFT JOIN Payments p ON b.BookingID = p.BookingID
                WHERE (
                    (b.Status = 'Pending' AND b.BookingDate <= DATE_ADD(CURDATE(), INTERVAL 3 DAY)) OR
                    (b.Status = 'Pending' AND b.RemainingBalance > 0 AND b.BookingDate <= DATE_ADD(CURDATE(), INTERVAL 7 DAY))
                )";
        
        if ($resortId) {
            $sql .= " AND b.ResortID = :resortId";
        }
        
        $sql .= " GROUP BY b.BookingID
                  ORDER BY b.BookingDate ASC";
        
        $stmt = $db->prepare($sql);
        if ($resortId) {
            $stmt->bindValue(':resortId', $resortId, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Schedule automated processing (for cron job integration)
     */
    public static function scheduleAutomatedProcessing() {
        // This would typically be called by a cron job
        $results = self::processAllBookings();
        
        // Log the results
        error_log("Automated booking processing completed: " . json_encode($results));
        
        return $results;
    }

    /**
     * Get booking status flow recommendations
     */
    public static function getStatusRecommendations($bookingId) {
        $booking = Booking::findById($bookingId);
        
        if (!$booking) {
            return [];
        }

        // To make the $booking object compatible with determineNewStatus,
        // we need to fetch and add the TotalPaid property.
        $db = self::getDB();
        $sql = "SELECT COALESCE(SUM(CASE WHEN p.Status = 'Verified' THEN p.Amount ELSE 0 END), 0) as TotalPaid
                FROM Payments p
                WHERE p.BookingID = :bookingId";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':bookingId', $bookingId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $booking->TotalPaid = $result['TotalPaid'] ?? 0;

        $recommendations = [];
        $newStatus = self::determineNewStatus($booking);
        
        if ($newStatus !== $booking->status) {
            $recommendations[] = [
                'type' => 'status_change',
                'current' => $booking->status,
                'recommended' => $newStatus,
                'reason' => self::getStatusChangeReason($booking, $newStatus),
                'priority' => self::getRecommendationPriority($booking, $newStatus)
            ];
        }

        return $recommendations;
    }

    /**
     * Get reason for status change recommendation
     */
    private static function getStatusChangeReason($booking, $newStatus) {
        $bookingDate = new DateTime($booking->bookingDate);
        $today = new DateTime();

        switch ($newStatus) {
            case 'Confirmed':
                return 'Booking is fully paid and ready for confirmation';
            case 'Cancelled':
                if ($bookingDate <= $today) {
                    return 'Booking date has passed without confirmation';
                } else {
                    return 'Insufficient payment received before deadline';
                }
            case 'Completed':
                return 'Booking date has passed and service was confirmed';
            default:
                return 'Status change recommended by system rules';
        }
    }

    /**
     * Get priority level for recommendation
     */
    private static function getRecommendationPriority($booking, $newStatus) {
        $bookingDate = new DateTime($booking->bookingDate);
        $today = new DateTime();
        $daysDiff = $today->diff($bookingDate)->days;

        if ($newStatus === 'Cancelled' && $bookingDate <= $today) {
            return 'high';
        } elseif ($newStatus === 'Confirmed' && $daysDiff <= 1) {
            return 'high';
        } elseif ($daysDiff <= 3) {
            return 'medium';
        } else {
            return 'low';
        }
    }
}
