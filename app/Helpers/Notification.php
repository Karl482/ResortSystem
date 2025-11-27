<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../vendor/autoload.php';

require_once __DIR__ . '/../../config/app.php'; // Ensure BASE_URL is defined
require_once __DIR__ . '/../../config/mail.php';
require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Models/Booking.php';
require_once __DIR__ . '/../Models/EmailTemplate.php';
require_once __DIR__ . '/../Models/EmailVerification.php';

class Notification {

    private static function replacePlaceholders($content, $data) {
        foreach ($data as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value, $content);
        }
        return $content;
    }

    private static function getMailer() {
        $mail = new PHPMailer(true);
        // Server settings
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = MAIL_SMTPAUTH;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = MAIL_SMTPSECURE;
        $mail->Port       = MAIL_PORT;
        $mail->Timeout    = 5; // 5 second timeout to prevent hanging
        $mail->CharSet = 'UTF-8';
        // Sender
        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        return $mail;
    }

    public static function sendBookingConfirmation($bookingId) {
        $booking = Booking::findById($bookingId);
        if (!$booking) return false;

        $customer = User::findById($booking->customerId);
        if (!$customer) return false;

        $template = EmailTemplate::getTemplate('booking_confirmation');
        $isCustomTemplate = $template && $template['UseCustom'];

        $mail = self::getMailer();
        try {
            $mail->addAddress($customer['Email'], $customer['FirstName']);

            $expirationWarning = '';
            $expirationTime = 'N/A';
            if (!empty($booking->expiresAt) && new DateTime($booking->expiresAt) > new DateTime()) {
                try {
                    $expiresAtUTC = new DateTime($booking->expiresAt, new DateTimeZone('UTC'));
                    $expiresAtUTC->setTimezone(new DateTimeZone('Asia/Shanghai'));
                    $expirationTime = htmlspecialchars($expiresAtUTC->format('F j, Y, g:i A'));
                } catch (Exception $e) { /* Ignore date conversion errors */ }
            }

            require_once __DIR__ . '/../Models/Resort.php';
            $resort = Resort::findById($booking->resortId);

            $placeholders = [
                'customer_name' => htmlspecialchars($customer['FirstName']),
                'booking_id' => $booking->bookingId,
                'booking_date' => date('F j, Y', strtotime($booking->bookingDate)),
                'timeslot' => htmlspecialchars(Booking::getTimeSlotDisplay($booking->timeSlotType)),
                'resort_name' => $resort ? htmlspecialchars($resort->name) : 'our resort',
                'expiration_time' => $expirationTime
            ];

            $mail->isHTML(true);

            $emailContent = $isCustomTemplate ? $template : EmailTemplate::getDefaultTemplate('booking_confirmation');
            $mail->Subject = self::replacePlaceholders($emailContent['Subject'], $placeholders);
            $mail->Body    = self::replacePlaceholders($emailContent['Body'], $placeholders);

            $mail->AltBody = 'Your booking has been created and requires payment. Booking ID: ' . $booking->bookingId;
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
            return false;
        }
    }


    public static function sendWelcomeEmail($userId) {
        $user = User::findById($userId);
        if (!$user) return false;

        $template = EmailTemplate::getTemplate('welcome_email');
        $isCustomTemplate = $template && $template['UseCustom'];

        $mail = self::getMailer();
        try {
            $mail->addAddress($user['Email'], $user['FirstName']);
            
            $placeholders = [
                'customer_name' => htmlspecialchars($user['FirstName'])
            ];

            $mail->isHTML(true);

            $emailContent = $isCustomTemplate ? $template : EmailTemplate::getDefaultTemplate('welcome_email');
            $mail->Subject = self::replacePlaceholders($emailContent['Subject'], $placeholders);
            $mail->Body    = self::replacePlaceholders($emailContent['Body'], $placeholders);
            
            $mail->AltBody = 'Welcome to Our Resort System! Thank you for registering.';
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
            return false;
        }
    }

    /**
     * Send payment submission notification to admin
     */
    public static function sendPaymentSubmissionNotification($bookingId, $customer) {
        $booking = Booking::findById($bookingId);
        if (!$booking) return false;

        $template = EmailTemplate::getTemplate('payment_submission_admin');
        $isCustomTemplate = $template && $template['UseCustom'];

        require_once __DIR__ . '/../Models/Resort.php';
        $resort = Resort::findById($booking->resortId);
        $admins = User::getAdminUsers();
        $mail = self::getMailer();

        $placeholders = [
            'customer_name' => htmlspecialchars($customer['FirstName'] . ' ' . $customer['LastName']),
            'booking_id' => $booking->bookingId,
            'resort_name' => $resort ? htmlspecialchars($resort->name) : 'N/A',
            'booking_date' => date('F j, Y', strtotime($booking->bookingDate)),
            'payment_reference' => htmlspecialchars($booking->paymentReference ?? 'N/A')
        ];

        foreach ($admins as $admin) {
            try {
                $recipientEmail = $admin['Email'];
                if (empty($recipientEmail) || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) continue;

                $mail->clearAddresses();
                $mail->addAddress($recipientEmail, $admin['FirstName']);
                $mail->isHTML(true);

                $placeholders['admin_name'] = htmlspecialchars($admin['FirstName']);

                $emailContent = $isCustomTemplate ? $template : EmailTemplate::getDefaultTemplate('payment_submission_admin');
                $mail->Subject = self::replacePlaceholders($emailContent['Subject'], $placeholders);
                $mail->Body    = self::replacePlaceholders($emailContent['Body'], $placeholders);
                
                $mail->AltBody = "Payment submitted for Booking #{$booking->bookingId}. Please verify.";
                $mail->send();
            } catch (Exception $e) {
                error_log("Admin notification failed for {$admin['Email']}: {$mail->ErrorInfo}");
                continue;
            }
        }
        return true;
    }

    /**
     * Send payment submission confirmation to customer
     */
    public static function sendPaymentSubmissionConfirmation($bookingId) {
        $booking = Booking::findById($bookingId);
        if (!$booking) return false;

        $customer = User::findById($booking->customerId);
        if (!$customer) return false;

        $template = EmailTemplate::getTemplate('payment_submission_customer');
        $isCustomTemplate = $template && $template['UseCustom'];

        $mail = self::getMailer();
        try {
            $mail->addAddress($customer['Email'], $customer['FirstName']);

            $placeholders = [
                'customer_name' => htmlspecialchars($customer['FirstName']),
                'booking_id' => $booking->bookingId
            ];

            $mail->isHTML(true);

            $emailContent = $isCustomTemplate ? $template : EmailTemplate::getDefaultTemplate('payment_submission_customer');
            $mail->Subject = self::replacePlaceholders($emailContent['Subject'], $placeholders);
            $mail->Body    = self::replacePlaceholders($emailContent['Body'], $placeholders);

            $mail->AltBody = "Your payment for Booking #{$booking->bookingId} has been submitted and is pending review.";
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Payment submission confirmation email failed: {$mail->ErrorInfo}");
            return false;
        }
    }

    /**
     * Send payment verification confirmation to customer
     */
    public static function sendPaymentVerificationConfirmation($bookingId) {
        $booking = Booking::findById($bookingId);
        if (!$booking) return false;

        $customer = User::findById($booking->customerId);
        if (!$customer) return false;

        $templateType = 'payment_verified';
        $template = EmailTemplate::getTemplate($templateType);
        $isCustomTemplate = $template && $template['UseCustom'];

        $mail = self::getMailer();
        try {
            $mail->addAddress($customer['Email'], $customer['FirstName']);

            $placeholders = [
                'customer_name' => htmlspecialchars($customer['FirstName']),
                'booking_id' => $booking->bookingId,
                'remaining_balance' => number_format($booking->remainingBalance, 2)
            ];

            $mail->isHTML(true);

            $emailContent = $isCustomTemplate ? $template : EmailTemplate::getDefaultTemplate($templateType);
            $mail->Subject = self::replacePlaceholders($emailContent['Subject'], $placeholders);
            $mail->Body    = self::replacePlaceholders($emailContent['Body'], $placeholders);

            $mail->AltBody = "Payment verified! Booking #{$booking->bookingId} is confirmed.";
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Payment verification email failed: {$mail->ErrorInfo}");
            return false;
        }
    }

    /**
     * Send Gmail verification code to new registrants.
     */
    public static function sendGmailVerificationCode($email, $code, $recipientName = 'there') {
        $mail = self::getMailer();
        try {
            $mail->addAddress($email, $recipientName);
            $mail->isHTML(true);

            $mail->Subject = 'Your Resort System verification code';
            $mail->Body = "
                <p>Hi " . htmlspecialchars($recipientName) . ",</p>
                <p>Use the verification code below to finish creating your account:</p>
                <p style=\"font-size:24px;font-weight:bold;letter-spacing:4px;\">{$code}</p>
                <p>This code expires in " . EmailVerification::EXPIRATION_MINUTES . " minutes. If you did not request this, feel free to ignore the message.</p>
                <p>Thank you,<br>Resort Management Team</p>
            ";
            $mail->AltBody = "Your verification code is {$code}. It expires in " . EmailVerification::EXPIRATION_MINUTES . " minutes.";
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Verification email failed for {$email}: {$mail->ErrorInfo}");
            return false;
        }
    }

    public static function sendBookingExpiredNotification($bookingId) {
        $booking = Booking::findById($bookingId);
        if (!$booking) return false;

        $customer = User::findById($booking->customerId);
        if (!$customer) return false;

        $template = EmailTemplate::getTemplate('booking_expired');
        $isCustomTemplate = $template && $template['UseCustom'];

        $mail = self::getMailer();
        try {
            $mail->addAddress($customer['Email'], $customer['FirstName']);

            $placeholders = [
                'customer_name' => htmlspecialchars($customer['FirstName']),
                'booking_id' => $booking->bookingId,
                'booking_date' => date('F j, Y', strtotime($booking->bookingDate))
            ];

            $mail->isHTML(true);

            $emailContent = $isCustomTemplate ? $template : EmailTemplate::getDefaultTemplate('booking_expired');
            $mail->Subject = self::replacePlaceholders($emailContent['Subject'], $placeholders);
            $mail->Body    = self::replacePlaceholders($emailContent['Body'], $placeholders);

            $mail->AltBody = 'Your booking #' . $booking->bookingId . ' has expired due to non-payment.';
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Expired booking notification could not be sent. Mailer Error: {$mail->ErrorInfo}");
            return false;
        }
    }

}
