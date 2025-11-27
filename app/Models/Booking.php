<?php

require_once __DIR__ . '/BlockedFacilityAvailability.php';
require_once __DIR__ . '/ResortTimeframePricing.php';
require_once __DIR__ . '/BookingFacilities.php';
require_once __DIR__ . '/BookingAuditTrail.php';

class Booking {
    public $bookingId;
    public $customerId;
    public $resortId;
    public $facilityId; // This will be deprecated in favor of multiple facilities
    public $bookingDate;
    public $timeSlotType;
    public $status; // e.g., 'Pending', 'Confirmed', 'Cancelled', 'Completed'
    public $totalAmount;
    public $paymentProofURL;
    public $paymentReference;
    public $remainingBalance;
    public $createdAt;
    public $expiresAt;

    private static $db;

    public function __construct() {
        // Constructor can be used for setting default values.
    }

    private static function getDB() {
        if (!self::$db) {
            require_once __DIR__ . '/../Helpers/Database.php';
            self::$db = Database::getInstance();
        }
        return self::$db;
    }

    public static function create(Booking $booking) {
        $db = self::getDB();
        $stmt = $db->prepare(
            "INSERT INTO Bookings (CustomerID, ResortID, FacilityID, BookingDate, TimeSlotType, Status, TotalAmount, PaymentProofURL, PaymentReference, RemainingBalance, ExpiresAt)
             VALUES (:customerId, :resortId, :facilityId, :bookingDate, :timeSlotType, :status, :totalAmount, :paymentProofURL, :paymentReference, :remainingBalance, :expiresAt)"
        );
        $stmt->bindValue(':customerId', $booking->customerId, PDO::PARAM_INT);
        $stmt->bindValue(':resortId', $booking->resortId, PDO::PARAM_INT);
        $stmt->bindValue(':facilityId', $booking->facilityId, PDO::PARAM_INT);
        $stmt->bindValue(':bookingDate', $booking->bookingDate, PDO::PARAM_STR);
        $stmt->bindValue(':timeSlotType', $booking->timeSlotType, PDO::PARAM_STR);
        $stmt->bindValue(':status', $booking->status, PDO::PARAM_STR);
        $stmt->bindValue(':totalAmount', $booking->totalAmount, PDO::PARAM_STR);
        $stmt->bindValue(':paymentProofURL', $booking->paymentProofURL, PDO::PARAM_STR);
        $stmt->bindValue(':paymentReference', $booking->paymentReference, PDO::PARAM_STR);
        $stmt->bindValue(':remainingBalance', $booking->remainingBalance, PDO::PARAM_STR);
        $stmt->bindValue(':expiresAt', $booking->expiresAt, PDO::PARAM_STR);

        if ($stmt->execute()) {
            return $db->lastInsertId();
        }
        return false;
    }

    /**
     * Create a new resort-centric booking with multiple facilities
     */
    public static function createResortBooking($customerId, $resortId, $bookingDate, $timeSlotType, $totalAmount, $facilityIds = [], $status = 'Pending') {
        $db = self::getDB();
        
        // Start transaction
        $db->beginTransaction();
        
        try {
            // Create booking record
            $booking = new Booking();
            $booking->customerId = $customerId;
            $booking->resortId = $resortId;
            $booking->facilityId = null; // Will be deprecated
            $booking->bookingDate = $bookingDate;
            $booking->timeSlotType = $timeSlotType;
            $booking->status = $status;
            $booking->totalAmount = $totalAmount;
            $booking->paymentProofURL = null;
            $booking->paymentReference = null;
            $booking->remainingBalance = $totalAmount;
            
            // Create a DateTime object in UTC to ensure correct expiration time across timezones
            // Set expiration only for pending bookings
            if ($status === 'Pending') {
                $dt = new DateTime("now", new DateTimeZone('UTC'));
                $dt->add(new DateInterval('PT3H'));
                $booking->expiresAt = $dt->format('Y-m-d H:i:s');
            } else {
               $booking->expiresAt = null;
            }
            
            $bookingId = self::create($booking);
            
            if (!$bookingId) {
                throw new Exception("Failed to create booking");
            }
            
            // Add facilities to booking, ensuring facilityIds is an array
            if (!empty($facilityIds) && is_array($facilityIds)) {
                if (!BookingFacilities::addFacilitiesToBooking($bookingId, $facilityIds)) {
                    throw new Exception("Failed to add facilities to booking");
                }
            }
            
            // Commit transaction
            $db->commit();
            return $bookingId;
            
        } catch (Exception $e) {
            // Rollback transaction
            $db->rollback();
            return false;
        }
    }

    public static function findById($bookingId) {
        $db = self::getDB();
        $stmt = $db->prepare("SELECT * FROM Bookings WHERE BookingID = :bookingId");
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
            $booking->expiresAt = $data['ExpiresAt'];
            return $booking;
        }
        return null;
    }

    public static function findByCustomerId($customerId) {
        $db = self::getDB();
        $stmt = $db->prepare(
            "SELECT b.*, r.Name as ResortName,
                    GROUP_CONCAT(f.Name SEPARATOR ', ') as FacilityNames
             FROM Bookings b
             LEFT JOIN Resorts r ON b.ResortID = r.ResortID
             LEFT JOIN BookingFacilities bf ON b.BookingID = bf.BookingID
             LEFT JOIN Facilities f ON bf.FacilityID = f.FacilityID
             WHERE b.CustomerID = :customerId
             GROUP BY b.BookingID
             ORDER BY b.CreatedAt DESC"
        );
        $stmt->bindValue(':customerId', $customerId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public static function findPendingAndCancelledByCustomerId($customerId) {
        $db = self::getDB();
        $stmt = $db->prepare(
            "SELECT b.*, r.Name as ResortName,
                    GROUP_CONCAT(f.Name SEPARATOR ', ') as FacilityNames
             FROM Bookings b
             LEFT JOIN Resorts r ON b.ResortID = r.ResortID
             LEFT JOIN BookingFacilities bf ON b.BookingID = bf.BookingID
             LEFT JOIN Facilities f ON bf.FacilityID = f.FacilityID
             WHERE b.CustomerID = :customerId AND b.Status IN ('Pending', 'Cancelled')
             GROUP BY b.BookingID
             ORDER BY b.CreatedAt DESC"
        );
        $stmt->bindValue(':customerId', $customerId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public static function findConfirmedByCustomerId($customerId) {
        $db = self::getDB();
        $stmt = $db->prepare(
            "SELECT b.*, r.Name as ResortName,
                    GROUP_CONCAT(f.Name SEPARATOR ', ') as FacilityNames,
                    (SELECT COUNT(*) FROM Feedback WHERE BookingID = b.BookingID) AS hasFeedback
             FROM Bookings b
             LEFT JOIN Resorts r ON b.ResortID = r.ResortID
             LEFT JOIN BookingFacilities bf ON b.BookingID = bf.BookingID
             LEFT JOIN Facilities f ON bf.FacilityID = f.FacilityID
             WHERE b.CustomerID = :customerId AND b.Status IN ('Confirmed', 'Completed')
             GROUP BY b.BookingID
             ORDER BY b.CreatedAt DESC"
        );
        $stmt->bindValue(':customerId', $customerId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public static function findTodaysBookings($resortId = null) {
        $db = self::getDB();
        $today = date('Y-m-d');
        
        $sql = "SELECT b.*, r.Name as ResortName, u.Username as CustomerName,
                       GROUP_CONCAT(f.Name SEPARATOR ', ') as FacilityNames
                FROM Bookings b
                LEFT JOIN Resorts r ON b.ResortID = r.ResortID
                LEFT JOIN Users u ON b.CustomerID = u.UserID
                LEFT JOIN BookingFacilities bf ON b.BookingID = bf.BookingID
                LEFT JOIN Facilities f ON bf.FacilityID = f.FacilityID
                WHERE b.BookingDate = :today AND b.Status IN ('Confirmed', 'Pending')";

        if ($resortId) {
            $sql .= " AND b.ResortID = :resortId";
        }

        $sql .= " GROUP BY b.BookingID ORDER BY b.BookingDate ASC, b.CreatedAt ASC";

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':today', $today, PDO::PARAM_STR);
        if ($resortId) {
            $stmt->bindValue(':resortId', $resortId, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public static function findUpcomingBookings($resortId = null, $limit = null) {
        $db = self::getDB();
        $today = date('Y-m-d');

        $sql = "SELECT b.*, r.Name as ResortName, u.Username as CustomerName,
                       GROUP_CONCAT(f.Name SEPARATOR ', ') as FacilityNames
                FROM Bookings b
                LEFT JOIN Resorts r ON b.ResortID = r.ResortID
                LEFT JOIN Users u ON b.CustomerID = u.UserID
                LEFT JOIN BookingFacilities bf ON b.BookingID = bf.BookingID
                LEFT JOIN Facilities f ON bf.FacilityID = f.FacilityID
                WHERE b.BookingDate > :today AND b.Status IN ('Pending', 'Confirmed')";

        if ($resortId) {
            $sql .= " AND b.ResortID = :resortId";
        }

        $sql .= " GROUP BY b.BookingID ORDER BY b.BookingDate ASC, b.CreatedAt ASC";

        if ($limit) {
            $sql .= " LIMIT :limit";
        }
        
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':today', $today, PDO::PARAM_STR);
        if ($resortId) {
            $stmt->bindValue(':resortId', $resortId, PDO::PARAM_INT);
        }
        if ($limit) {
            $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
    

    public static function getBookingHistory($resortId = null, $limit = 10) {
        $db = self::getDB();
        $sql = "SELECT b.*, r.Name as ResortName, u.Username as CustomerName,
                       GROUP_CONCAT(f.Name SEPARATOR ', ') as FacilityNames
                FROM Bookings b
                LEFT JOIN Resorts r ON b.ResortID = r.ResortID
                LEFT JOIN Users u ON b.CustomerID = u.UserID
                LEFT JOIN BookingFacilities bf ON b.BookingID = bf.BookingID
                LEFT JOIN Facilities f ON bf.FacilityID = f.FacilityID
                WHERE b.BookingDate < CURDATE()";

        $params = [];

        if ($resortId) {
            $sql .= " AND b.ResortID = :resortId";
            $params[':resortId'] = $resortId;
        }

        $sql .= " GROUP BY b.BookingID ORDER BY b.BookingDate DESC, b.CreatedAt DESC LIMIT :limit";
        $params[':limit'] = $limit;

        $stmt = $db->prepare($sql);

        foreach ($params as $param => &$val) {
            $stmt->bindParam($param, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public static function update(Booking $booking) {
        $db = self::getDB();
        $stmt = $db->prepare(
            "UPDATE Bookings
             SET CustomerID = :customerId, ResortID = :resortId, FacilityID = :facilityId, BookingDate = :bookingDate,
                 TimeSlotType = :timeSlotType, Status = :status,
                 TotalAmount = :totalAmount, PaymentProofURL = :paymentProofURL, PaymentReference = :paymentReference,
                 RemainingBalance = :remainingBalance, ExpiresAt = :expiresAt
             WHERE BookingID = :bookingId"
    );
    $stmt->bindValue(':customerId', $booking->customerId, PDO::PARAM_INT);
    $stmt->bindValue(':resortId', $booking->resortId, PDO::PARAM_INT);
    $stmt->bindValue(':facilityId', $booking->facilityId, PDO::PARAM_INT);
    $stmt->bindValue(':bookingDate', $booking->bookingDate, PDO::PARAM_STR);
    $stmt->bindValue(':timeSlotType', $booking->timeSlotType, PDO::PARAM_STR);
    $stmt->bindValue(':status', $booking->status, PDO::PARAM_STR);
    $stmt->bindValue(':totalAmount', $booking->totalAmount, PDO::PARAM_STR);
    $stmt->bindValue(':paymentProofURL', $booking->paymentProofURL, PDO::PARAM_STR);
    $stmt->bindValue(':paymentReference', $booking->paymentReference, PDO::PARAM_STR);
    $stmt->bindValue(':remainingBalance', $booking->remainingBalance, PDO::PARAM_STR);
    $stmt->bindValue(':expiresAt', $booking->expiresAt, PDO::PARAM_STR);
    $stmt->bindValue(':bookingId', $booking->bookingId, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    public static function updateStatus($bookingId, $status) {
        $db = self::getDB();
        $stmt = $db->prepare("UPDATE Bookings SET Status = :status WHERE BookingID = :bookingId");
        $stmt->bindValue(':status', $status, PDO::PARAM_STR);
        $stmt->bindValue(':bookingId', $bookingId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public static function delete($bookingId) {
        $db = self::getDB();
        $stmt = $db->prepare("DELETE FROM Bookings WHERE BookingID = :bookingId");
        $stmt->bindValue(':bookingId', $bookingId, PDO::PARAM_INT);
        return $stmt->execute();
    }
    public static function getTimeSlotDisplay($timeSlotType) {
        switch ($timeSlotType) {
            case '12_hours':
                return 'Check In: 7:00 AM Check Out: 5:00 PM (12 hrs)';
            case 'overnight':
                return 'Check In: 7:00 AM Check Out: 5:00 AM Next Day (Overnight)';
            case '24_hours':
                return 'Check In: 7:00 PM Check Out: 5:00 AM (24 hrs)';
            default:
                return 'N/A';
        }
    }

    public static function isTimeSlotAvailable($facilityId, $bookingDate, $timeSlotType, $excludeBookingId = null) {
        $db = self::getDB();
    
        // Get the ResortID for the given FacilityID
        $facilityStmt = $db->prepare("SELECT ResortID FROM Facilities WHERE FacilityID = ?");
        $facilityStmt->execute([$facilityId]);
        $resortId = $facilityStmt->fetchColumn();
    
        if (!$resortId) {
            return false; // Facility not found or not associated with a resort
        }
    
        // New logic: Check if there is ANY booking for the resort on the given date.
        $sql = "SELECT COUNT(*) FROM Bookings
                WHERE ResortID = ?
                AND BookingDate = ?
                AND Status IN ('Pending', 'Confirmed')";
        
        $params = [$resortId, $bookingDate];
    
        if ($excludeBookingId) {
            $sql .= " AND BookingID != ?";
            $params[] = $excludeBookingId;
        }
    
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        if ($stmt->fetchColumn() > 0) {
            return false; // A booking already exists for this resort on this day
        }
    
        // Check for resort-level blocks for the entire day
        $blockSql = "SELECT COUNT(*) FROM BlockedResortAvailability
                     WHERE ResortID = ?
                     AND BlockDate = ?";
        $blockStmt = $db->prepare($blockSql);
        $blockStmt->execute([$resortId, $bookingDate]);
        if ($blockStmt->fetchColumn() > 0) {
            return false; // The entire resort is blocked for this day
        }
    
        // Check for facility-level blocks for the entire day
        $facilityBlockSql = "SELECT COUNT(*) FROM BlockedFacilityAvailability
                             WHERE FacilityID = ?
                             AND BlockDate = ?";
        $facilityBlockStmt = $db->prepare($facilityBlockSql);
        $facilityBlockStmt->execute([$facilityId, $bookingDate]);
        if ($facilityBlockStmt->fetchColumn() > 0) {
            return false; // The specific facility is blocked for this day
        }
    
        return true; // No conflicts found
    }

    /**
     * Get all booked timeframes for a specific resort and date.
     */
    public static function getBookedTimeframesForDate($resortId, $bookingDate, $excludeBookingId = null) {
        $db = self::getDB();
        $sql = "SELECT TimeSlotType FROM Bookings
                WHERE ResortID = ?
                AND BookingDate = ?
                AND Status IN ('Pending', 'Confirmed')";
        
        $params = [$resortId, $bookingDate];

        if ($excludeBookingId) {
            $sql .= " AND BookingID != ?";
            $params[] = $excludeBookingId;
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Check if a resort + timeframe combination is available (for resort-centric booking)
     */
    public static function isResortTimeframeAvailable($resortId, $bookingDate, $timeSlotType, $facilityIds = [], $excludeBookingId = null) {
        // 1. Check for resort-level blocks for the entire day
        $blockSql = "SELECT COUNT(*) FROM BlockedResortAvailability WHERE ResortID = ? AND BlockDate = ?";
        $blockStmt = self::getDB()->prepare($blockSql);
        $blockStmt->execute([$resortId, $bookingDate]);
        if ($blockStmt->fetchColumn() > 0) {
            return false; // Resort is blocked for this date
        }
    
        // 2. Check for timeframe availability
        $availableTimeframes = self::getAvailableTimeframesForDate($resortId, $bookingDate, $excludeBookingId);
        if (!in_array($timeSlotType, $availableTimeframes)) {
            return false; // The requested timeframe is not available
        }
    
        // 3. If facilities are selected, check their individual availability (for blocks)
        if (!empty($facilityIds)) {
            foreach ($facilityIds as $facilityId) {
                $facilityBlockSql = "SELECT COUNT(*) FROM BlockedFacilityAvailability WHERE FacilityID = ? AND BlockDate = ?";
                $facilityBlockStmt = self::getDB()->prepare($facilityBlockSql);
                $facilityBlockStmt->execute([$facilityId, $bookingDate]);
                if ($facilityBlockStmt->fetchColumn() > 0) {
                    return false; // A required facility is specifically blocked.
                }
            }
        }
    
        return true; // Available
    }

    /**
     * Get the actually available timeframes for a date, considering conflicts.
     */
    public static function getAvailableTimeframesForDate($resortId, $date, $excludeBookingId = null) {
        $bookedTimeframes = self::getBookedTimeframesForDate($resortId, $date, $excludeBookingId);
        
        $allTimeframes = ['12_hours', 'overnight', '24_hours'];

        if (empty($bookedTimeframes)) {
            return $allTimeframes;
        }
        
        $available = $allTimeframes;

        // If a 24-hour slot is booked, nothing else is available
        if (in_array('24_hours', $bookedTimeframes)) {
            return [];
        }

        // If a 12-hour slot is booked, 24-hour is unavailable
        if (in_array('12_hours', $bookedTimeframes)) {
            $available = array_diff($available, ['12_hours', '24_hours']);
        }
        
        // If an overnight slot is booked, 24-hour is unavailable
        if (in_array('overnight', $bookedTimeframes)) {
            $available = array_diff($available, ['overnight', '24_hours']);
        }

        return array_values($available); // Return re-indexed array
    }

    /**
     * Update payment information for a booking
     */
    public static function updatePaymentInfo($bookingId, $paymentProofURL, $paymentReference, $amountPaid = null) {
        $db = self::getDB();
        
        $booking = self::findById($bookingId);
        if (!$booking) {
            return false;
        }

        // Calculate remaining balance
        $remainingBalance = $booking->remainingBalance;
        if ($amountPaid !== null) {
            $remainingBalance = max(0, $booking->remainingBalance - $amountPaid);
        }

        // Do not update status here; status is only updated upon admin verification.
        $stmt = $db->prepare(
            "UPDATE Bookings
             SET PaymentProofURL = :paymentProofURL, PaymentReference = :paymentReference,
                 RemainingBalance = :remainingBalance
             WHERE BookingID = :bookingId"
        );
        $stmt->bindValue(':paymentProofURL', $paymentProofURL, PDO::PARAM_STR);
        $stmt->bindValue(':paymentReference', $paymentReference, PDO::PARAM_STR);
        $stmt->bindValue(':remainingBalance', $remainingBalance, PDO::PARAM_STR);
        $stmt->bindValue(':bookingId', $bookingId, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public static function clearExpiration($bookingId) {
        $db = self::getDB();
        $stmt = $db->prepare("UPDATE Bookings SET ExpiresAt = NULL WHERE BookingID = :bookingId");
        $stmt->bindValue(':bookingId', $bookingId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Get bookings with facilities and resort information
     */
    public static function getBookingsWithDetails($resortId = null, $status = null, $limit = null) {
        $db = self::getDB();
        
        $sql = "SELECT b.*, r.Name as ResortName, u.Username as CustomerName,
                       GROUP_CONCAT(f.Name SEPARATOR ', ') as FacilityNames
                FROM Bookings b
                LEFT JOIN Resorts r ON b.ResortID = r.ResortID
                LEFT JOIN Users u ON b.CustomerID = u.UserID
                LEFT JOIN BookingFacilities bf ON b.BookingID = bf.BookingID
                LEFT JOIN Facilities f ON bf.FacilityID = f.FacilityID
                WHERE 1=1";
        
        $params = [];
        
        if ($resortId) {
            $sql .= " AND b.ResortID = ?";
            $params[] = $resortId;
        }
        
        if ($status) {
            $sql .= " AND b.Status = ?";
            $params[] = $status;
        }
        
        $sql .= " GROUP BY b.BookingID ORDER BY b.BookingDate DESC, b.CreatedAt DESC";
        
        if ($limit) {
            $sql .= " LIMIT ?";
            $params[] = $limit;
        }
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Calculate total booking value with facilities
     */
    public static function calculateBookingTotal($resortId, $timeSlotType, $bookingDate, $facilityIds = []) {
        // Get base price from resort timeframe pricing
        $basePrice = ResortTimeframePricing::calculatePrice($resortId, $timeSlotType, $bookingDate);
        
        // Add facility costs
        $facilityTotal = 0;
        if (!empty($facilityIds)) {
            require_once __DIR__ . '/Facility.php';
            foreach ($facilityIds as $facilityId) {
                $facility = Facility::findById($facilityId);
                if ($facility) {
                    $facilityTotal += $facility->rate;
                }
            }
        }
        
        return $basePrice + $facilityTotal;
    }

    /**
     * PHASE 5: Get bookings with payment details for unified management
     */
    public static function getBookingsWithPaymentDetails($filters = []) {
        $db = self::getDB();
        
        $sql = "SELECT
                    b.*,
                    u.Username as CustomerName,
                    u.Email as CustomerEmail,
                    r.Name as ResortName,
                    GROUP_CONCAT(f.Name SEPARATOR ', ') as FacilityNames,
                    COALESCE(p.TotalPaid, 0) as TotalPaid,
                    -- Phase 6 Data
                    COALESCE(at.AuditTrailCount, 0) as AuditTrailCount,
                    COALESCE(ps.PaymentScheduleSummary, 'Not Scheduled') as PaymentScheduleSummary
                FROM Bookings b
                LEFT JOIN Users u ON b.CustomerID = u.UserID
                LEFT JOIN Resorts r ON b.ResortID = r.ResortID
                LEFT JOIN BookingFacilities bf ON b.BookingID = bf.BookingID
                LEFT JOIN Facilities f ON bf.FacilityID = f.FacilityID
                LEFT JOIN (
                    SELECT
                        BookingID,
                        SUM(CASE WHEN Status = 'Verified' THEN Amount ELSE 0 END) as TotalPaid
                    FROM Payments
                    GROUP BY BookingID
                ) p ON b.BookingID = p.BookingID
                -- Phase 6: Join for Audit Trail Count
                LEFT JOIN (
                    SELECT BookingID, COUNT(*) as AuditTrailCount
                    FROM BookingAuditTrail
                    GROUP BY BookingID
                ) at ON b.BookingID = at.BookingID
                -- Phase 6: Join for Payment Schedule Summary
                LEFT JOIN (
                    SELECT
                        BookingID,
                        CONCAT(
                            COUNT(*), ' installments (',
                            SUM(CASE WHEN Status = 'Paid' THEN 1 ELSE 0 END), ' Paid, ',
                            SUM(CASE WHEN Status IN ('Pending', 'Overdue') THEN 1 ELSE 0 END), ' Due)'
                        ) as PaymentScheduleSummary
                    FROM PaymentSchedules
                    GROUP BY BookingID
                ) ps ON b.BookingID = ps.BookingID
                WHERE 1=1";

        $params = [];
        
        if (!empty($filters['resort_id'])) {
            $sql .= " AND b.ResortID = :resortId";
            $params[':resortId'] = $filters['resort_id'];
        }
        
        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $sql .= " AND b.Status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['customer_id'])) {
            $sql .= " AND b.CustomerID = :customerId";
            $params[':customerId'] = $filters['customer_id'];
        }

        if (!empty($filters['month'])) {
            $sql .= " AND MONTH(b.BookingDate) = :month";
            $params[':month'] = $filters['month'];
        }

        if (!empty($filters['year'])) {
            $sql .= " AND YEAR(b.BookingDate) = :year";
            $params[':year'] = $filters['year'];
        }
        
        $sql .= " GROUP BY b.BookingID";

        // Handle payment status filter using a HAVING clause
        if (!empty($filters['payment_status'])) {
            switch ($filters['payment_status']) {
                case 'Paid':
                    $sql .= " HAVING b.RemainingBalance <= 0 AND b.TotalAmount > 0";
                    break;
                case 'Partial':
                    $sql .= " HAVING TotalPaid > 0 AND b.RemainingBalance > 0";
                    break;
                case 'Unpaid':
                    $sql .= " HAVING TotalPaid = 0";
                    break;
            }
        }

        $sql .= " ORDER BY b.BookingDate DESC, b.CreatedAt DESC";
        
        $stmt = $db->prepare($sql);
        foreach ($params as $param => $value) {
            $stmt->bindValue($param, $value);
        }
        
        $stmt->execute();
        $bookings = $stmt->fetchAll(PDO::FETCH_OBJ);

        // Manually set the correct PaymentStatus based on accurate balance
        foreach ($bookings as $booking) {
            if ($booking->RemainingBalance <= 0 && $booking->TotalAmount > 0) {
                $booking->PaymentStatus = 'Paid';
            } elseif ($booking->TotalPaid > 0) {
                $booking->PaymentStatus = 'Partial';
            } else {
                $booking->PaymentStatus = 'Unpaid';
            }
        }

        return $bookings;
    }

    /**
     * Update remaining balance for a booking
     */
    /**
     * Get count of active bookings (excluding completed and cancelled) for a customer
     */
    public static function getConfirmedBookingsCount($customerId) {
        $db = self::getDB();
        $stmt = $db->prepare(
            "SELECT COUNT(*) as count FROM Bookings
             WHERE CustomerID = :customerId AND Status = 'Confirmed'"
        );
        $stmt->bindValue(':customerId', $customerId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['count'];
    }

    public static function getPendingBookingsCount($customerId) {
        $db = self::getDB();
        $stmt = $db->prepare(
            "SELECT COUNT(*) as count FROM Bookings
             WHERE CustomerID = :customerId AND Status = 'Pending'"
        );
        $stmt->bindValue(':customerId', $customerId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['count'];
    }

    /**
     * Get count of active bookings (excluding completed and cancelled) filtered by resort for admin dashboard
     */
    public static function getActiveBookingsCountForAdmin($resortId = null) {
        $db = self::getDB();
        $sql = "SELECT COUNT(*) as count FROM Bookings WHERE Status IN ('Pending', 'Confirmed')";

        $params = [];
        if ($resortId) {
            $sql .= " AND ResortID = ?";
            $params[] = $resortId;
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['count'];
    }

    public static function updateRemainingBalance($bookingId, $remainingBalance) {
        $db = self::getDB();
        $stmt = $db->prepare("UPDATE Bookings SET RemainingBalance = :remainingBalance WHERE BookingID = :bookingId");
        $stmt->bindValue(':remainingBalance', $remainingBalance, PDO::PARAM_STR);
        $stmt->bindValue(':bookingId', $bookingId, PDO::PARAM_INT);
        return $stmt->execute();
    }
    /**
     * Recalculate and update the total amount and remaining balance for a booking.
     * This should be called within a transaction.
     */
    public static function recalculateBookingTotals($bookingId) {
        $db = self::getDB();
        $booking = self::findById($bookingId);
        if (!$booking) {
            throw new Exception("Booking not found for recalculation.");
        }

        // Recalculate TotalAmount
        $newTotalAmount = self::calculateBookingTotal(
            $booking->resortId,
            $booking->timeSlotType,
            $booking->bookingDate,
            BookingFacilities::getFacilityIds($bookingId)
        );

        // Recalculate RemainingBalance
        $totalPaid = Payment::getTotalPaidAmount($bookingId);
        $newRemainingBalance = max(0, $newTotalAmount - $totalPaid);

        // Update the booking record
        $stmt = $db->prepare("UPDATE Bookings SET TotalAmount = :totalAmount, RemainingBalance = :remainingBalance WHERE BookingID = :bookingId");
        $stmt->bindValue(':totalAmount', $newTotalAmount, PDO::PARAM_STR);
        $stmt->bindValue(':remainingBalance', $newRemainingBalance, PDO::PARAM_STR);
        $stmt->bindValue(':bookingId', $bookingId, PDO::PARAM_INT);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update booking totals.");
        }

        return ['totalAmount' => $newTotalAmount, 'remainingBalance' => $newRemainingBalance];
    }

    /**
     * Comprehensive, transactional update for a booking by an admin.
     * Handles status changes, facility modifications, and on-site payments.
     */
    public static function adminUpdateBooking($updateData, $paymentData, $adminUserId) {
        $db = self::getDB();
        $db->beginTransaction();

        try {
            $bookingId = $updateData['booking_id'];
            $originalBooking = self::findById($bookingId);
            if (!$originalBooking) {
                throw new Exception("Booking not found.");
            }

            // 1. Update Booking Status
            if ($originalBooking->status !== $updateData['status']) {
                $oldStatus = $originalBooking->status;
                self::updateStatus($bookingId, $updateData['status']);
                BookingAuditTrail::logStatusChange($bookingId, $adminUserId, $oldStatus, $updateData['status'], 'Admin booking modification');
            }

            // 2. Update Facilities (only if facility_ids is provided)
            if (isset($updateData['facility_ids']) && $updateData['facility_ids'] !== null) {
                $originalFacilityIds = BookingFacilities::getFacilityIds($bookingId);
                $newFacilityIds = $updateData['facility_ids'];
                // Sort to ensure consistent comparison
                sort($originalFacilityIds);
                sort($newFacilityIds);

                if ($originalFacilityIds !== $newFacilityIds) {
                    // Get facility names for audit trail
                    require_once __DIR__ . '/Facility.php';
                $originalNames = [];
                foreach ($originalFacilityIds as $facilityId) {
                    $facility = Facility::findById($facilityId);
                    if ($facility) {
                        $originalNames[] = $facility->name;
                    }
                }
                $originalFacilitiesStr = implode(', ', $originalNames);

                $newNames = [];
                foreach ($newFacilityIds as $facilityId) {
                    $facility = Facility::findById($facilityId);
                    if ($facility) {
                        $newNames[] = $facility->name;
                    }
                }
                $newFacilitiesStr = implode(', ', $newNames);

                    BookingFacilities::updateBookingFacilities($bookingId, $newFacilityIds, $db);
                    BookingAuditTrail::logChange($bookingId, $adminUserId, 'UPDATE', 'Facilities', $originalFacilitiesStr, $newFacilitiesStr, 'Admin facility modification');
                
                    // 3. Recalculate Totals (only if facilities changed)
                    $oldTotalAmount = $originalBooking->totalAmount;
                    $oldRemainingBalance = $originalBooking->remainingBalance;
                    $totalsResult = self::recalculateBookingTotals($bookingId);
                    BookingAuditTrail::logChange($bookingId, $adminUserId, 'UPDATE', 'TotalAmount', $oldTotalAmount, $totalsResult['totalAmount'], 'Recalculated due to facility changes');
                    if ($oldRemainingBalance !== $totalsResult['remainingBalance']) {
                        BookingAuditTrail::logChange($bookingId, $adminUserId, 'UPDATE', 'RemainingBalance', $oldRemainingBalance, $totalsResult['remainingBalance'], 'Recalculated due to facility changes');
                    }
                }
            }

            // 4. Add On-Site Payment (if provided)
            if ($paymentData) {
                $paymentAmount = $paymentData['amount'];
                $oldRemainingBalance = $originalBooking->remainingBalance;
                $payment = new Payment();
                $payment->bookingId = $bookingId;
                $payment->amount = $paymentAmount;
                $payment->paymentMethod = $paymentData['method'];
                $payment->status = 'Verified'; // Admin payments are auto-verified
                $payment->proofOfPaymentURL = 'Payment recorded by Admin';

                if (!Payment::create($payment)) {
                    throw new Exception("Failed to record payment.");
                }
                BookingAuditTrail::logPaymentUpdate($bookingId, $adminUserId, 'PaymentAmount', '0', $paymentAmount, 'Payment recorded by admin');

                // After adding payment, recalculate balance again
                $balanceResult = self::recalculateBookingTotals($bookingId);
                if ($oldRemainingBalance !== $balanceResult['remainingBalance']) {
                    BookingAuditTrail::logChange($bookingId, $adminUserId, 'UPDATE', 'RemainingBalance', $oldRemainingBalance, $balanceResult['remainingBalance'], 'Updated after payment');
                }
            }

            $db->commit();
            return ['success' => true];

        } catch (Exception $e) {
            $db->rollback();
            error_log("Admin Booking Update Failed: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    public static function onSiteUpdateFacilities($bookingId, $newFacilityIds, $adminUserId) {
        $db = self::getDB();
        $db->beginTransaction();
 
        try {
            $booking = self::findById($bookingId);
            if (!$booking) {
                throw new Exception("Booking not found.");
            }

            // Validation: Only allow for confirmed and fully paid bookings
            if ($booking->status !== 'Confirmed') {
                throw new Exception("On-site edits are only allowed for Confirmed bookings.");
            }
            if ($booking->remainingBalance > 0) {
                throw new Exception("Booking must be fully paid before making on-site edits.");
            }

            // Get original facility info for audit
            $originalFacilityIds = BookingFacilities::getFacilityIds($bookingId);
            sort($originalFacilityIds);
            sort($newFacilityIds);

            if ($originalFacilityIds !== $newFacilityIds) {
                require_once __DIR__ . '/Facility.php';
                $originalNames = [];
                foreach ($originalFacilityIds as $facilityId) {
                    $facility = Facility::findById($facilityId);
                    if ($facility) $originalNames[] = $facility->name;
                }
                $originalFacilitiesStr = implode(', ', $originalNames);

                $newNames = [];
                foreach ($newFacilityIds as $facilityId) {
                    $facility = Facility::findById($facilityId);
                    if ($facility) $newNames[] = $facility->name;
                }
                $newFacilitiesStr = implode(', ', $newNames);

                // Update facilities, passing the existing transaction context
                BookingFacilities::updateBookingFacilities($bookingId, $newFacilityIds, $db);
                BookingAuditTrail::logChange($bookingId, $adminUserId, 'UPDATE', 'Facilities', $originalFacilitiesStr, $newFacilitiesStr, 'On-Site facility modification');

                // Recalculate totals and create a new on-site payment record for the difference
                $oldTotalAmount = $booking->totalAmount;
                $totalsResult = self::recalculateBookingTotals($bookingId);
                $newTotalAmount = $totalsResult['totalAmount'];

                $adjustmentAmount = $newTotalAmount - $oldTotalAmount;

                if ($adjustmentAmount != 0) {
                    $payment = new Payment();
                    $payment->bookingId = $bookingId;
                    $payment->amount = $adjustmentAmount;
                    $payment->paymentMethod = 'Cash';
                    $payment->status = 'Verified';
                    $payment->proofOfPaymentURL = 'Facility change adjustment by Admin';
                    
                    if (!Payment::create($payment)) {
                        throw new Exception("Failed to record payment adjustment.");
                    }
                    BookingAuditTrail::logPaymentUpdate($bookingId, $adminUserId, 'PaymentAdjustment', '0', $adjustmentAmount, 'Facility change adjustment by admin');
                }
                
                // Final recalculation of balance
                self::recalculateBookingTotals($bookingId);
            }

            $db->commit();
            return ['success' => true];

        } catch (Exception $e) {
            $db->rollback();
            error_log("On-Site Booking Update Failed: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    /**
     * Process a new payment for a booking in a transactional way.
     */
    public static function processNewPayment($bookingId, $amount, $paymentMethod, $adminUserId) {
        // First, validate the payment amount to prevent overpayment
        $validation = Payment::validatePaymentAmount($bookingId, $amount);
        if (!$validation['valid']) {
            // Immediately return the specific validation error (e.g., "Payment exceeds remaining balance")
            return ['success' => false, 'error' => implode(', ', $validation['errors'])];
        }

        $db = self::getDB();
        $db->beginTransaction();

        try {
            // 1. Create the payment record
            $payment = new Payment();
            $payment->bookingId = $bookingId;
            $payment->amount = $amount;
            $payment->paymentMethod = $paymentMethod;
            $payment->status = 'Verified'; // Admin-added payments are auto-verified
            $payment->proofOfPaymentURL = 'On-site payment by Admin';
            
            $paymentId = Payment::create($payment);
            if (!$paymentId) {
                throw new Exception("Failed to create payment record.");
            }

            // 2. Find and update the corresponding payment schedule
            $nextScheduleItem = PaymentSchedule::getNextPaymentDue($bookingId);
            if ($nextScheduleItem) {
                PaymentSchedule::markAsPaid($nextScheduleItem->ScheduleID, $paymentId);
            }

            // 3. Recalculate booking totals and status
            $booking = self::findById($bookingId);
            $oldRemainingBalance = $booking->remainingBalance;
            
            $totals = self::recalculateBookingTotals($bookingId);
            $newRemainingBalance = $totals['remainingBalance'];
            
            // 4. Log the payment and balance change
            BookingAuditTrail::logPaymentUpdate(
                $bookingId,
                $adminUserId,
                'Payment',
                'N/A',
                '₱' . number_format($amount, 2),
                "Admin recorded an on-site payment via {$paymentMethod}."
            );
            
            if ($oldRemainingBalance != $newRemainingBalance) {
                BookingAuditTrail::logChange(
                    $bookingId,
                    $adminUserId,
                    'UPDATE',
                    'RemainingBalance',
                    $oldRemainingBalance,
                    $newRemainingBalance,
                    'Balance updated after new payment.'
                );
            }
            
            // The recalculateBookingTotals method already updates the DB, so we just need to commit.
            $db->commit();
            return ['success' => true];

        } catch (Exception $e) {
            $db->rollback();
            error_log("Failed to process new payment for booking #{$bookingId}: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }


    /**
     * Get the total number of completed bookings for a specific facility.
     */
    public static function countCompletedBookingsByFacility($facilityId) {
        $db = self::getDB();
        $stmt = $db->prepare(
            "SELECT COUNT(DISTINCT b.BookingID)
             FROM Bookings b
             JOIN BookingFacilities bf ON b.BookingID = bf.BookingID
             WHERE bf.FacilityID = :facilityId
               AND b.Status = 'Completed'"
        );
        $stmt->bindValue(':facilityId', $facilityId, PDO::PARAM_INT);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    /**
     * Get the total number of completed bookings for a specific customer.
     */
    public static function countCompletedBookingsByCustomer($customerId) {
        $db = self::getDB();
        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM Bookings WHERE CustomerID = :customerId AND Status = 'Completed'"
        );
        $stmt->bindValue(':customerId', $customerId, PDO::PARAM_INT);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    /**
     * Get completed bookings that are ready for feedback (not yet submitted).
     */
    public static function findCompletedBookingsAwaitingFeedback($customerId) {
        $db = self::getDB();
        $stmt = $db->prepare(
            "SELECT b.*, r.Name as ResortName,
                    GROUP_CONCAT(f.Name SEPARATOR ', ') as FacilityNames
             FROM Bookings b
             LEFT JOIN Resorts r ON b.ResortID = r.ResortID
             LEFT JOIN BookingFacilities bf ON b.BookingID = bf.BookingID
             LEFT JOIN Facilities f ON bf.FacilityID = f.FacilityID
             WHERE b.CustomerID = :customerId
               AND b.Status = 'Completed'
               AND NOT EXISTS (SELECT 1 FROM Feedback WHERE BookingID = b.BookingID)
             GROUP BY b.BookingID
             ORDER BY b.BookingDate DESC"
        );
        $stmt->bindValue(':customerId', $customerId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
    
    /**
     * Get count of completed bookings that are ready for feedback (not yet submitted).
     */
    public static function countBookingsAwaitingFeedback($customerId) {
        $db = self::getDB();
        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM Bookings b
             WHERE b.CustomerID = :customerId
               AND b.Status = 'Completed'
               AND NOT EXISTS (SELECT 1 FROM Feedback WHERE BookingID = b.BookingID)"
        );
        $stmt->bindValue(':customerId', $customerId, PDO::PARAM_INT);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }
}
