<?php
// CLI email worker used by AsyncHelper->triggerEmailWorker
// Usage: php send_email_worker.php <email_type> <id>

// Minimal bootstrap
require_once __DIR__ . '/../vendor/autoload.php';

// Load models/helpers used by Notification
require_once __DIR__ . '/../app/Helpers/Notification.php';
require_once __DIR__ . '/../app/Models/Booking.php';
require_once __DIR__ . '/../app/Models/User.php';

$argv0 = $_SERVER['argv'][0] ?? 'send_email_worker.php';
$emailType = $_SERVER['argv'][1] ?? null;
$id = $_SERVER['argv'][2] ?? null;

if (!$emailType || !$id) {
    error_log("[send_email_worker] Usage: php {$argv0} <email_type> <id>");
    exit(1);
}

try {
    switch ($emailType) {
        case 'booking_confirmation':
            Notification::sendBookingConfirmation((int)$id);
            break;

        case 'payment_submission_admin':
            // Notification::sendPaymentSubmissionNotification expects the booking id and the customer record
            $booking = Booking::findById((int)$id);
            if ($booking) {
                $customer = User::findById($booking->customerId);
                Notification::sendPaymentSubmissionNotification((int)$id, $customer);
            } else {
                error_log("[send_email_worker] booking not found: {$id}");
            }
            break;

        case 'payment_submission_customer':
            Notification::sendPaymentSubmissionConfirmation((int)$id);
            break;

        case 'payment_verified':
            Notification::sendPaymentVerificationConfirmation((int)$id);
            break;

        case 'welcome_email':
            Notification::sendWelcomeEmail((int)$id);
            break;

        case 'booking_expired':
            Notification::sendBookingExpiredNotification((int)$id);
            break;

        case 'booking_confirmed_paid':
            // Best-effort: notify customer of booking confirmation
            Notification::sendBookingStatusChangeNotification((int)$id, null, 'Confirmed');
            break;

        default:
            error_log("[send_email_worker] Unknown email type: {$emailType}");
            exit(2);
    }
} catch (Exception $e) {
    error_log("[send_email_worker] Exception while sending {$emailType} for id {$id}: " . $e->getMessage());
    exit(3);
}

exit(0);
