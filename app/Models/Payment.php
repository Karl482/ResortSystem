<?php

require_once __DIR__ . '/PaymentSchedule.php';
require_once __DIR__ . '/BookingAuditTrail.php';
require_once __DIR__ . '/BookingFacilities.php';

class Payment {
    public $paymentId;
    public $bookingId;
    public $amount;
    public $paymentMethod;
    public $paymentDate;
    public $status;
    public $proofOfPaymentURL;
    public $ScheduleID; // Link to payment schedule if applicable
    public $validationErrors = [];

    private static $db;

    private static function getDB() {
        require_once __DIR__ . '/../Helpers/Database.php';
        return Database::getInstance();
    }

    public static function create(Payment $payment) {
        $db = self::getDB();
        $stmt = $db->prepare(
            "INSERT INTO Payments (BookingID, Amount, PaymentMethod, Status, ProofOfPaymentURL, Reference)
             VALUES (:bookingId, :amount, :paymentMethod, :status, :proofOfPaymentURL, :reference)"
        );
        $stmt->bindValue(':bookingId', $payment->bookingId, PDO::PARAM_INT);
        $stmt->bindValue(':amount', $payment->amount, PDO::PARAM_STR);
        $stmt->bindValue(':paymentMethod', $payment->paymentMethod, PDO::PARAM_STR);
        $stmt->bindValue(':status', $payment->status, PDO::PARAM_STR);
        $stmt->bindValue(':proofOfPaymentURL', $payment->proofOfPaymentURL, PDO::PARAM_STR);
        $stmt->bindValue(':reference', $payment->reference ?? null, PDO::PARAM_STR);

        if ($stmt->execute()) {
            return $db->lastInsertId();
        }
        return false;
    }

    public static function findByBookingId($bookingId) {
        $db = self::getDB();
        $stmt = $db->prepare("SELECT * FROM Payments WHERE BookingID = :bookingId ORDER BY PaymentDate DESC");
        $stmt->bindValue(':bookingId', $bookingId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
    
    public static function findById($paymentId) {
        $db = self::getDB();
        $stmt = $db->prepare("SELECT * FROM Payments WHERE PaymentID = :paymentId");
        $stmt->bindValue(':paymentId', $paymentId, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data) {
            $payment = new Payment();
            $payment->paymentId = $data['PaymentID'];
            $payment->bookingId = $data['BookingID'];
            $payment->amount = $data['Amount'];
            $payment->paymentMethod = $data['PaymentMethod'];
            $payment->paymentDate = $data['PaymentDate'];
            $payment->status = $data['Status'];
            $payment->proofOfPaymentURL = $data['ProofOfPaymentURL'];
            return $payment;
        }
        return null;
    }

    public static function updateStatus($paymentId, $status) {
        $db = self::getDB();
        $stmt = $db->prepare("UPDATE Payments SET Status = :status WHERE PaymentID = :paymentId");
        $stmt->bindValue(':status', $status, PDO::PARAM_STR);
        $stmt->bindValue(':paymentId', $paymentId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Create payment record from booking payment submission with enhanced validation
     */
    public static function createFromBookingPayment($bookingId, $amount, $paymentMethod, $paymentReference, $paymentProofURL, $status = 'Pending', $scheduleId = null) {
        // Validate payment amount against booking and existing payments
        $validation = self::validatePaymentAmount($bookingId, $amount);
        if (!$validation['valid']) {
            return ['success' => false, 'errors' => $validation['errors']];
        }

        $payment = new Payment();
        $payment->bookingId = $bookingId;
        $payment->amount = $amount;
        $payment->paymentMethod = $paymentMethod; // Use the provided payment method
        $payment->status = $status; // Default to 'Pending', but allow override
        $payment->proofOfPaymentURL = $paymentProofURL;
        $payment->reference = $paymentReference; // Store the reference
        $payment->ScheduleID = $scheduleId;

        $paymentId = self::create($payment);
        
        if ($paymentId) {
            // Log comprehensive payment submission in audit trail
            $formattedAmount = '₱' . number_format($amount, 2);
            $auditDetails = "Customer submitted payment of {$formattedAmount} with reference: {$paymentReference} via {$paymentMethod} (Pending admin verification)";

            BookingAuditTrail::logPaymentUpdate(
                $bookingId,
                $_SESSION['user_id'] ?? 1,
                'Payment', // Consolidated field name
                'No Payment Submitted',
                'Payment Submitted (Pending)',
                $auditDetails
            );

            // Update payment schedule if linked
            if ($scheduleId) {
                PaymentSchedule::markAsPaid($scheduleId, $paymentId);
            }

            // Clear the expiration time now that a payment has been made
            require_once __DIR__ . '/Booking.php';
            Booking::clearExpiration($bookingId);


            return ['success' => true, 'paymentId' => $paymentId];
        }

        return ['success' => false, 'errors' => ['Failed to create payment record']];
    }

    /**
     * Get total amount paid for a booking
     */
    public static function getTotalPaidAmount($bookingId) {
        $db = self::getDB();
        $stmt = $db->prepare("SELECT SUM(Amount) FROM Payments WHERE BookingID = :bookingId AND Status IN ('Paid', 'Verified')");
        $stmt->bindValue(':bookingId', $bookingId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetchColumn();
        return $result ?: 0;
    }

    /**
     * Get pending payments for admin review
     */
    public static function getPendingPayments($resortId = null) {
        $db = self::getDB();
        $sql = "SELECT p.*, b.BookingID, b.BookingDate, b.TotalAmount, b.RemainingBalance,
                       u.Username as CustomerName, u.Email as CustomerEmail, u.PhoneNumber as CustomerPhone,
                       r.Name as ResortName
                FROM Payments p
                JOIN Bookings b ON p.BookingID = b.BookingID
                JOIN Users u ON b.CustomerID = u.UserID
                JOIN Resorts r ON b.ResortID = r.ResortID
                WHERE p.Status = 'Pending'";
        
        $params = [];
        if ($resortId) {
            $sql .= " AND b.ResortID = ?";
            $params[] = $resortId;
        }
        
        $sql .= " ORDER BY p.PaymentDate DESC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $payments = $stmt->fetchAll(PDO::FETCH_OBJ);

        // Fetch included facilities for each payment's booking
        foreach ($payments as $payment) {
            $payment->IncludedFacilities = BookingFacilities::getFacilitiesForBooking($payment->BookingID);
        }

        return $payments;
    }

    /**
     * Verify payment and update booking status with enhanced audit trail
     */
    public static function verifyPayment($paymentId, $adminId) {
        $db = self::getDB();
        
        try {
            $db->beginTransaction();
            
            // Get payment info before verification
            $payment = self::findById($paymentId);
            if (!$payment) {
                throw new Exception("Payment not found");
            }

            // Update payment status
            $stmt = $db->prepare("UPDATE Payments SET Status = 'Verified' WHERE PaymentID = ?");
            $stmt->execute([$paymentId]);
            
            // Get booking info for balance calculation
            require_once __DIR__ . '/Booking.php';
            $booking = Booking::findById($payment->bookingId);
            if (!$booking) {
                throw new Exception("Booking not found");
            }
            
            // Calculate new balance with smart validation
            $balanceCalculation = self::calculateSmartBalance($booking->bookingId);
            $newBalance = $balanceCalculation['remainingBalance'];
            $oldStatus = $booking->status;
            // As per requirement, verifying any payment confirms the booking.
            $newStatus = 'Confirmed';
            
            // Update booking
            $updateStmt = $db->prepare("UPDATE Bookings SET RemainingBalance = ?, Status = ? WHERE BookingID = ?");
            $updateStmt->execute([$newBalance, $newStatus, $booking->bookingId]);
            
            // Log consolidated payment verification in audit trail
            $verificationDetails = "Admin verified payment of ₱" . number_format($payment->amount, 2);

            if ($oldStatus !== $newStatus) {
                $verificationDetails .= "; Booking status changed from '$oldStatus' to '$newStatus'";
            }

            if ($booking->remainingBalance != $newBalance) {
                $verificationDetails .= "; Balance updated from ₱" . number_format($booking->remainingBalance, 2) . " to ₱" . number_format($newBalance, 2);
            }

            BookingAuditTrail::logPaymentUpdate(
                $payment->bookingId,
                $adminId,
                'Payment',
                'Pending Verification',
                'Verified',
                $verificationDetails
            );
            
            $db->commit();
            return ['success' => true, 'newStatus' => $newStatus, 'newBalance' => $newBalance];
            
        } catch (Exception $e) {
            $db->rollback();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get payment history for a booking
     */
    public static function getPaymentHistory($bookingId) {
        $db = self::getDB();
        $stmt = $db->prepare("SELECT * FROM Payments WHERE BookingID = ? ORDER BY PaymentDate DESC");
        $stmt->execute([$bookingId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Get count of pending payments for notification
     */
    public static function getPendingPaymentCount($resortId = null) {
        $db = self::getDB();
        $sql = "SELECT COUNT(*) FROM Payments p
                JOIN Bookings b ON p.BookingID = b.BookingID
                WHERE p.Status = 'Pending'";

        if ($resortId) {
            $sql .= " AND b.ResortID = ?";
        }

        $stmt = $db->prepare($sql);
        if ($resortId) {
            $stmt->execute([$resortId]);
        } else {
            $stmt->execute();
        }
        return $stmt->fetchColumn();
    }

    /**
     * Phase 6: Smart balance calculation with validation
     */
    public static function calculateSmartBalance($bookingId) {
        $db = self::getDB();

        // Get booking total
        $bookingStmt = $db->prepare("SELECT TotalAmount FROM Bookings WHERE BookingID = ?");
        $stmtResult = $bookingStmt->execute([$bookingId]);

        if (!$stmtResult) {
            return ['error' => 'Database query failed'];
        }

        $booking = $bookingStmt->fetch(PDO::FETCH_OBJ);

        if (!$booking) {
            return ['error' => 'Booking not found'];
        }

        // Calculate total verified payments
        $paymentStmt = $db->prepare("SELECT SUM(Amount) as TotalPaid FROM Payments WHERE BookingID = ? AND Status = 'Verified'");
        $paymentStmt->execute([$bookingId]);
        $paymentResult = $paymentStmt->fetch(PDO::FETCH_OBJ);
        
        $totalPaid = $paymentResult->TotalPaid ?? 0;
        $totalAmount = $booking->TotalAmount ?? 0;
        $remainingBalance = max(0, $totalAmount - $totalPaid);
        
        // Get pending payments
        $pendingStmt = $db->prepare("SELECT SUM(Amount) as PendingAmount FROM Payments WHERE BookingID = ? AND Status = 'Pending'");
        $pendingStmt->execute([$bookingId]);
        $pendingResult = $pendingStmt->fetch(PDO::FETCH_OBJ);
        
        $pendingAmount = $pendingResult->PendingAmount ?? 0;

        return [
            'totalAmount' => $totalAmount,
            'totalPaid' => $totalPaid,
            'pendingAmount' => $pendingAmount,
            'remainingBalance' => $remainingBalance,
            'paymentPercentage' => $totalAmount > 0 ? ($totalPaid / $totalAmount) * 100 : 0,
            'isFullyPaid' => $remainingBalance <= 0,
            'hasOverpayment' => $totalPaid > $totalAmount
        ];
    }

    /**
     * Validate payment amount against booking constraints
     */
    public static function validatePaymentAmount($bookingId, $amount) {
        $errors = [];
        
        // Basic amount validation
        if ($amount <= 0) {
            $errors[] = 'Payment amount must be greater than zero';
        }

        // Get current balance calculation
        $balanceInfo = self::calculateSmartBalance($bookingId);
        
        if (isset($balanceInfo['error'])) {
            $errors[] = $balanceInfo['error'];
            return ['valid' => false, 'errors' => $errors];
        }

        // Check if payment exceeds remaining balance with a small tolerance for floating-point issues
        $tolerance = 0.001;
        if ($amount > ($balanceInfo['remainingBalance'] + $tolerance)) {
            $errors[] = 'Payment amount (₱' . number_format($amount, 2) . ') exceeds remaining balance (₱' . number_format($balanceInfo['remainingBalance'], 2) . ')';
        }

        // Check for reasonable payment limits (e.g., minimum ₱50)
        if ($amount < 50) {
            $errors[] = 'Minimum payment amount is ₱50';
        }

        // Check for maximum single payment (e.g., ₱50,000)
        if ($amount > 50000) {
            $errors[] = 'Maximum single payment amount is ₱50,000';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'balanceInfo' => $balanceInfo
        ];
    }

    /**
     * Determine booking status based on balance
     */
    private static function determineBookingStatusFromBalance($remainingBalance, $totalAmount) {
        if ($remainingBalance <= 0) {
            return 'Confirmed'; // Fully paid
        } elseif ($remainingBalance < $totalAmount) {
            return 'Pending'; // Partially paid
        } else {
            return 'Pending'; // No payment yet
        }
    }

    /**
     * Get payment summary for a booking
     */
    public static function getPaymentSummary($bookingId) {
        $db = self::getDB();
        
        $sql = "SELECT
                    COUNT(*) as PaymentCount,
                    SUM(CASE WHEN Status = 'Verified' THEN Amount ELSE 0 END) as VerifiedTotal,
                    SUM(CASE WHEN Status = 'Pending' THEN Amount ELSE 0 END) as PendingTotal,
                    MAX(PaymentDate) as LastPaymentDate,
                    MIN(PaymentDate) as FirstPaymentDate
                FROM Payments
                WHERE BookingID = ?";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$bookingId]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    /**
     * Process payment with enhanced error handling
     */
    public static function processPaymentWithValidation($paymentData) {
        $errors = [];
        
        // Validate required fields
        $required = ['bookingId', 'amount', 'paymentMethod'];
        foreach ($required as $field) {
            if (empty($paymentData[$field])) {
                $errors[] = "Field '{$field}' is required";
            }
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        // Validate payment amount
        $validation = self::validatePaymentAmount($paymentData['bookingId'], $paymentData['amount']);
        if (!$validation['valid']) {
            return ['success' => false, 'errors' => $validation['errors']];
        }

        // Create payment
        $payment = new Payment();
        $payment->bookingId = $paymentData['bookingId'];
        $payment->amount = $paymentData['amount'];
        $payment->paymentMethod = $paymentData['paymentMethod'];
        $payment->status = $paymentData['status'] ?? 'Pending';
        $payment->proofOfPaymentURL = $paymentData['proofOfPaymentURL'] ?? null;

        $paymentId = self::create($payment);
        
        if ($paymentId) {
            return ['success' => true, 'paymentId' => $paymentId, 'balanceInfo' => $validation['balanceInfo']];
        } else {
            return ['success' => false, 'errors' => ['Failed to create payment record']];
        }
    }

    /**
     * Get overdue payments report
     */
    public static function getOverduePaymentsReport($resortId = null) {
        $db = self::getDB();
        
        $sql = "SELECT b.BookingID, b.BookingDate, b.TotalAmount, b.RemainingBalance,
                       u.Username as CustomerName, u.Email as CustomerEmail,
                       r.Name as ResortName,
                       DATEDIFF(CURDATE(), b.BookingDate) as DaysOverdue,
                       ps.DueDate as NextPaymentDue
                FROM Bookings b
                JOIN Users u ON b.CustomerID = u.UserID
                JOIN Resorts r ON b.ResortID = r.ResortID
                LEFT JOIN PaymentSchedules ps ON b.BookingID = ps.BookingID AND ps.Status = 'Overdue'
                WHERE b.Status IN ('Pending', 'Confirmed')
                AND b.RemainingBalance > 0
                AND b.BookingDate < CURDATE()";
        
        if ($resortId) {
            $sql .= " AND b.ResortID = ?";
        }
        
        $sql .= " ORDER BY DaysOverdue DESC";
        
        $stmt = $db->prepare($sql);
        if ($resortId) {
            $stmt->execute([$resortId]);
        } else {
            $stmt->execute();
        }
        
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Get total verified income for a specific month and year.
     */
    public static function getMonthlyIncome($year, $month, $resortId = null) {
        $db = self::getDB();
        
        $sql = "SELECT SUM(p.Amount) as TotalIncome
                FROM Payments p";

        $params = [
            ':year' => $year,
            ':month' => $month
        ];

        if ($resortId) {
            $sql .= " JOIN Bookings b ON p.BookingID = b.BookingID";
        }
                
        $sql .= " WHERE YEAR(p.PaymentDate) = :year AND MONTH(p.PaymentDate) = :month
                AND p.Status IN ('Verified', 'Paid')"; // 'Verified' is the new standard, 'Paid' for legacy if any

        if ($resortId) {
            $sql .= " AND b.ResortID = :resortId";
            $params[':resortId'] = $resortId;
        }

        $stmt = $db->prepare($sql);
        
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['TotalIncome'] ?? 0;
    }
    /**
     * Get daily income for a specific month and year, prepared for charting.
     */
    public static function getDailyIncomeForMonth($year, $month, $resortId = null) {
        $db = self::getDB();
        
        $sql = "SELECT 
                    DAY(p.PaymentDate) as Day,
                    SUM(p.Amount) as DailyIncome
                FROM Payments p";

        $params = [
            ':year' => $year,
            ':month' => $month
        ];

        if ($resortId) {
            $sql .= " JOIN Bookings b ON p.BookingID = b.BookingID";
        }
                
        $sql .= " WHERE YEAR(p.PaymentDate) = :year AND MONTH(p.PaymentDate) = :month
                AND p.Status IN ('Verified', 'Paid')";

        if ($resortId) {
            $sql .= " AND b.ResortID = :resortId";
            $params[':resortId'] = $resortId;
        }
        
        $sql .= " GROUP BY DAY(p.PaymentDate) ORDER BY DAY(p.PaymentDate)";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        // Initialize an array for all days of the month with 0 income
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $dailyData = array_fill(1, $daysInMonth, 0);

        // Fill in the income for the days that have it
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($results as $row) {
            $dailyData[(int)$row['Day']] = (float)$row['DailyIncome'];
        }

        return $dailyData;
    }
    
    /**
     * Get monthly income for a specific year, prepared for charting.
     */
    public static function getMonthlyIncomeForYear($year, $resortId = null) {
        $db = self::getDB();
        
        $sql = "SELECT
                    MONTH(p.PaymentDate) as Month,
                    SUM(p.Amount) as MonthlyIncome
                FROM Payments p";

        $params = [':year' => $year];

        if ($resortId) {
            $sql .= " JOIN Bookings b ON p.BookingID = b.BookingID";
        }
                
        $sql .= " WHERE YEAR(p.PaymentDate) = :year
                AND p.Status IN ('Verified', 'Paid')";

        if ($resortId) {
            $sql .= " AND b.ResortID = :resortId";
            $params[':resortId'] = $resortId;
        }
        
        $sql .= " GROUP BY MONTH(p.PaymentDate) ORDER BY MONTH(p.PaymentDate)";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        // Initialize an array for all months of the year with 0 income
        $monthlyData = array_fill(1, 12, 0);

        // Fill in the income for the months that have it
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($results as $row) {
            $monthlyData[(int)$row['Month']] = (float)$row['MonthlyIncome'];
        }

        return $monthlyData;
    }

}
