<?php

/**
 * PaymentSchedule Model - Phase 6 Enhancement
 * Manages payment schedules and installments for bookings
 */
class PaymentSchedule {
    public $scheduleId;
    public $bookingId;
    public $installmentNumber;
    public $dueDate;
    public $amount;
    public $status; // 'Pending', 'Paid', 'Overdue', 'Cancelled'
    public $paymentId; // Link to actual payment when paid
    public $createdAt;
    public $updatedAt;

    private static $db;

    private static function getDB() {
        if (!self::$db) {
            require_once __DIR__ . '/../Helpers/Database.php';
            self::$db = Database::getInstance();
        }
        return self::$db;
    }

    /**
     * Create a payment schedule for a booking
     */
    public static function createScheduleForBooking($bookingId, $totalAmount, $downPayment = null, $installments = 2) {
        $db = self::getDB();
        
        // If downpayment is not specified, assume it's a 50/50 split unless it's a full payment.
        if ($downPayment === null) {
            // This logic assumes a default of 2 installments if not a full payment
            $downPayment = $totalAmount * 0.5;
        }

        // If the initial payment covers the full amount, create a single installment schedule.
        if ($downPayment >= $totalAmount) {
            $downPayment = $totalAmount;
            $installments = 1;
        }
        
        $db->beginTransaction();
        
        try {
            if ($installments === 1) {
                // Handle single (full) payment
                $schedule = new PaymentSchedule();
                $schedule->bookingId = $bookingId;
                $schedule->installmentNumber = 1;
                $schedule->dueDate = date('Y-m-d'); // Due immediately
                $schedule->amount = $totalAmount;
                $schedule->status = 'Pending';
                self::createScheduleItem($schedule);
            } else {
                // Handle multiple installments (downpayment + remaining)
                $remainingAmount = $totalAmount - $downPayment;
                // Ensure there's at least one more installment for the remaining amount
                $numRemainingInstallments = max(1, $installments - 1);
                $installmentAmount = $remainingAmount / $numRemainingInstallments;

                // 1. Create down payment schedule
                $schedule = new PaymentSchedule();
                $schedule->bookingId = $bookingId;
                $schedule->installmentNumber = 1;
                $schedule->dueDate = date('Y-m-d'); // Due immediately
                $schedule->amount = $downPayment;
                $schedule->status = 'Pending';
                self::createScheduleItem($schedule);
                
                // 2. Create remaining installment schedules
                for ($i = 1; $i <= $numRemainingInstallments; $i++) {
                    $schedule = new PaymentSchedule();
                    $schedule->bookingId = $bookingId;
                    $schedule->installmentNumber = $i + 1;
                    // Simple weekly due date for subsequent payments
                    $schedule->dueDate = date('Y-m-d', strtotime("+" . $i . " week"));
                    $schedule->amount = $installmentAmount;
                    $schedule->status = 'Pending';
                    self::createScheduleItem($schedule);
                }
            }
            
            $db->commit();
            return true;
            
        } catch (Exception $e) {
            $db->rollback();
            // Log the error for debugging
            error_log("Payment schedule creation failed for booking {$bookingId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create individual schedule item
     */
    private static function createScheduleItem(PaymentSchedule $schedule) {
        $db = self::getDB();
        $stmt = $db->prepare(
            "INSERT INTO PaymentSchedules (BookingID, InstallmentNumber, DueDate, Amount, Status)
             VALUES (:bookingId, :installmentNumber, :dueDate, :amount, :status)"
        );
        
        $stmt->bindValue(':bookingId', $schedule->bookingId, PDO::PARAM_INT);
        $stmt->bindValue(':installmentNumber', $schedule->installmentNumber, PDO::PARAM_INT);
        $stmt->bindValue(':dueDate', $schedule->dueDate, PDO::PARAM_STR);
        $stmt->bindValue(':amount', $schedule->amount, PDO::PARAM_STR);
        $stmt->bindValue(':status', $schedule->status, PDO::PARAM_STR);
        
        return $stmt->execute();
    }

    /**
     * Get payment schedule for a booking
     */
    public static function findByBookingId($bookingId) {
        $db = self::getDB();
        $stmt = $db->prepare("SELECT * FROM PaymentSchedules WHERE BookingID = :bookingId ORDER BY InstallmentNumber ASC");
        $stmt->bindValue(':bookingId', $bookingId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Mark a schedule item as paid
     */
    public static function markAsPaid($scheduleId, $paymentId) {
        $db = self::getDB();
        $stmt = $db->prepare("UPDATE PaymentSchedules SET Status = 'Paid', PaymentID = :paymentId WHERE ScheduleID = :scheduleId");
        $stmt->bindValue(':paymentId', $paymentId, PDO::PARAM_INT);
        $stmt->bindValue(':scheduleId', $scheduleId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Get overdue payment schedules
     */
    public static function getOverdueSchedules($resortId = null) {
        $db = self::getDB();
        
        $sql = "SELECT ps.*, b.BookingID, b.BookingDate, u.Username as CustomerName, r.Name as ResortName
                FROM PaymentSchedules ps
                JOIN Bookings b ON ps.BookingID = b.BookingID
                JOIN Users u ON b.CustomerID = u.UserID
                JOIN Resorts r ON b.ResortID = r.ResortID
                WHERE ps.Status = 'Pending' AND ps.DueDate < CURDATE()";
        
        $params = [];
        if ($resortId) {
            $sql .= " AND b.ResortID = :resortId";
            $params[':resortId'] = $resortId;
        }
        
        $sql .= " ORDER BY ps.DueDate ASC";
        
        $stmt = $db->prepare($sql);
        foreach ($params as $param => $value) {
            $stmt->bindValue($param, $value);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Update overdue schedules
     */
    public static function updateOverdueStatus() {
        $db = self::getDB();
        $stmt = $db->prepare("UPDATE PaymentSchedules SET Status = 'Overdue' WHERE Status = 'Pending' AND DueDate < CURDATE()");
        return $stmt->execute();
    }

    /**
     * Calculate next payment due for a booking
     */
    public static function getNextPaymentDue($bookingId) {
        $db = self::getDB();
        $stmt = $db->prepare(
            "SELECT * FROM PaymentSchedules 
             WHERE BookingID = :bookingId AND Status IN ('Pending', 'Overdue') 
             ORDER BY InstallmentNumber ASC 
             LIMIT 1"
        );
        $stmt->bindValue(':bookingId', $bookingId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    /**
     * Get payment schedule summary for a booking
     */
    public static function getScheduleSummary($bookingId) {
        $db = self::getDB();
        $stmt = $db->prepare(
            "SELECT 
                COUNT(*) as TotalInstallments,
                SUM(Amount) as TotalAmount,
                SUM(CASE WHEN Status = 'Paid' THEN Amount ELSE 0 END) as PaidAmount,
                SUM(CASE WHEN Status IN ('Pending', 'Overdue') THEN Amount ELSE 0 END) as RemainingAmount,
                COUNT(CASE WHEN Status = 'Overdue' THEN 1 END) as OverdueCount
             FROM PaymentSchedules 
             WHERE BookingID = :bookingId"
        );
        $stmt->bindValue(':bookingId', $bookingId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    /**
     * Cancel remaining payment schedules for a booking
     */
    public static function cancelRemainingSchedules($bookingId) {
        $db = self::getDB();
        $stmt = $db->prepare("UPDATE PaymentSchedules SET Status = 'Cancelled' WHERE BookingID = :bookingId AND Status IN ('Pending', 'Overdue')");
        $stmt->bindValue(':bookingId', $bookingId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Flexible payment schedule creation with custom dates
     */
    public static function createCustomSchedule($bookingId, $scheduleItems) {
        $db = self::getDB();
        $db->beginTransaction();
        
        try {
            foreach ($scheduleItems as $index => $item) {
                $schedule = new PaymentSchedule();
                $schedule->bookingId = $bookingId;
                $schedule->installmentNumber = $index + 1;
                $schedule->dueDate = $item['dueDate'];
                $schedule->amount = $item['amount'];
                $schedule->status = 'Pending';
                
                self::createScheduleItem($schedule);
            }
            
            $db->commit();
            return true;
            
        } catch (Exception $e) {
            $db->rollback();
            return false;
        }
    }
}