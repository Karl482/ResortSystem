<?php

require_once __DIR__ . '/../Models/Payment.php';
require_once __DIR__ . '/../Models/Booking.php';
require_once __DIR__ . '/../Models/User.php';

require_once __DIR__ . '/../Helpers/AsyncHelper.php';

class PaymentController {

    public function __construct() {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
            header('Location: index.php?controller=user&action=login&error=unauthorized');
            exit();
        }
    }

    public function index() {
        // This will be used to list all bookings with payment info
        // For now, let's redirect to the admin dashboard
        header('Location: index.php?controller=admin&action=dashboard');
    }


    public function add() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?controller=admin&action=dashboard');
            exit();
        }

        $bookingId = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);
        $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
        $paymentMethod = filter_input(INPUT_POST, 'payment_method', FILTER_UNSAFE_RAW);
        $adminUserId = $_SESSION['user_id'];

        // Preserve filters from the referring URL
        $redirectUrl = 'index.php?controller=admin&action=unifiedBookingManagement';
        if (isset($_SERVER['HTTP_REFERER'])) {
            $parts = parse_url($_SERVER['HTTP_REFERER']);
            if (isset($parts['query'])) {
                parse_str($parts['query'], $query);
                // Keep only relevant filters, not action/controller
                unset($query['controller']);
                unset($query['action']);
                if (!empty($query)) {
                    $redirectUrl .= '&' . http_build_query($query);
                }
            }
        }

        if (!$bookingId || !$amount || !$paymentMethod) {
            $_SESSION['error_message'] = "Invalid input for adding payment.";
            header('Location: ' . $redirectUrl);
            exit();
        }

        $result = Booking::processNewPayment($bookingId, $amount, $paymentMethod, $adminUserId);

        if ($result['success']) {
            $_SESSION['success_message'] = "Payment of ₱" . number_format($amount, 2) . " added successfully.";
        } else {
            $_SESSION['error_message'] = "Failed to add payment: " . ($result['error'] ?? 'An unknown error occurred.');
        }

        header('Location: ' . $redirectUrl);
        exit();
    }

    public function updateStatus() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?controller=admin&action=dashboard');
            exit();
        }

        $paymentId = filter_input(INPUT_POST, 'payment_id', FILTER_VALIDATE_INT);
        $bookingId = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);
        $status = filter_input(INPUT_POST, 'status', FILTER_UNSAFE_RAW);

        if (!$paymentId || !$bookingId || !$status) {
            header('Location: index.php?controller=admin&action=unifiedBookingManagement&error=invalid_input');
            exit();
        }

        if (Payment::updateStatus($paymentId, $status)) {
            // If status is updated to 'Paid', auto-confirm the booking
            if ($status === 'Paid') {
                Booking::updateStatus($bookingId, 'Confirmed');
            }
            header('Location: index.php?controller=admin&action=unifiedBookingManagement&status=status_updated');
        } else {
            header('Location: index.php?controller=admin&action=unifiedBookingManagement&error=update_failed');
        }
        exit();
    }

    public function updateBookingStatus() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?controller=admin&action=dashboard');
            exit();
        }

        $bookingId = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);
        $status = filter_input(INPUT_POST, 'status', FILTER_UNSAFE_RAW);

        if (!$bookingId || !$status) {
            header('Location: index.php?controller=admin&action=unifiedBookingManagement&error=invalid_input');
            exit();
        }

        if (Booking::updateStatus($bookingId, $status)) {
            header('Location: index.php?controller=admin&action=unifiedBookingManagement&status=booking_status_updated');
        } else {
            header('Location: index.php?controller=admin&action=unifiedBookingManagement&error=booking_update_failed');
        }
        exit();
    }

    /**
     * Show pending payments for admin review
     */
    public function showPendingPayments() {
        $resortId = filter_input(INPUT_GET, 'resort_id', FILTER_VALIDATE_INT);

        // Store resort filter for maintaining filter after actions
        if ($resortId) {
            $_SESSION['pending_resort_filter'] = $resortId;
        } elseif (!isset($_GET['resort_id'])) {
            unset($_SESSION['pending_resort_filter']);
        }

        require_once __DIR__ . '/../Models/Resort.php';
        $resorts = Resort::findAll();

        require_once __DIR__ . '/../Models/Payment.php';
        $pendingPayments = Payment::getPendingPayments($resortId);

        require_once __DIR__ . '/../Views/admin/payments/pending.php';
    }

    /**
     * Verify a payment
     */
    public function verifyPayment() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?controller=payment&action=showPendingPayments');
            exit();
        }

        $paymentId = filter_input(INPUT_POST, 'payment_id', FILTER_VALIDATE_INT);
        if (!$paymentId) {
            $_SESSION['error_message'] = "Invalid payment ID.";
            $redirectUrl = '?controller=payment&action=showPendingPayments';
            if (isset($_SESSION['pending_resort_filter'])) {
                $redirectUrl .= '&resort_id=' . urlencode($_SESSION['pending_resort_filter']);
            }
            header('Location: ' . $redirectUrl);
            exit();
        }

        require_once __DIR__ . '/../Models/Payment.php';
        require_once __DIR__ . '/../Helpers/Notification.php';

        $result = Payment::verifyPayment($paymentId, $_SESSION['user_id']);

        if ($result['success']) {
            // Payment verification successful - try to send confirmation email
            $payment = Payment::findById($paymentId);
            if ($payment) {
                AsyncHelper::triggerEmailWorker('payment_verified', $payment->bookingId);
                $_SESSION['success_message'] = "Payment for Booking #" . $payment->bookingId . " verified. The booking is now Confirmed.";
            } else {
                $_SESSION['success_message'] = "Payment verified successfully, but could not retrieve booking details.";
            }
        } else {
            $_SESSION['error_message'] = "Failed to verify payment: " . ($result['error'] ?? 'An unknown error occurred.');
        }

        $redirectUrl = '?controller=payment&action=showPendingPayments';
        if (isset($_SESSION['pending_resort_filter'])) {
            $redirectUrl .= '&resort_id=' . urlencode($_SESSION['pending_resort_filter']);
        }
        header('Location: ' . $redirectUrl);
        exit();
    }

}
