<?php

require_once __DIR__ . '/../Helpers/Database.php';

class EmailTemplate
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public static function getTemplate(string $templateType)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM EmailTemplates WHERE TemplateType = :templateType");
        $stmt->execute(['templateType' => $templateType]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function getAllTemplates()
    {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM EmailTemplates ORDER BY TemplateType ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateTemplate(int $templateId, string $subject, string $body, bool $useCustom)
    {
        $stmt = $this->db->prepare(
            "UPDATE EmailTemplates SET Subject = :subject, Body = :body, UseCustom = :useCustom WHERE TemplateID = :templateId"
        );
        return $stmt->execute([
            'subject' => $subject,
            'body' => $body,
            'useCustom' => $useCustom,
            'templateId' => $templateId
        ]);
    }

    public static function createInitialTemplates()
    {
        $db = Database::getInstance();
        $defaults = self::getDefaults();

        foreach ($defaults as $type => $content) {
            $stmt = $db->prepare("SELECT TemplateID FROM EmailTemplates WHERE TemplateType = :TemplateType");
            $stmt->execute(['TemplateType' => $type]);
            if (!$stmt->fetch()) {
                $insertStmt = $db->prepare(
                    "INSERT INTO EmailTemplates (TemplateType, Subject, Body, UseCustom) VALUES (:TemplateType, :Subject, :Body, :UseCustom)"
                );
                $insertStmt->execute([
                    'TemplateType' => $type,
                    'Subject' => $content['Subject'],
                    'Body' => $content['Body'],
                    'UseCustom' => 0 // Default to not using custom
                ]);
            }
        }
    }

    public static function getDefaultTemplate(string $templateType)
    {
        $defaults = self::getDefaults();
        return $defaults[$templateType] ?? null;
    }

    public static function getDefaults()
    {
        return [
            'welcome_email' => [
                'Subject' => 'Welcome to Our Resort!',
                'Body' => '<p>Dear {{customer_name}},</p><p>Thank you for registering. We are excited to have you!</p>'
            ],
            'booking_confirmation' => [
                'Subject' => 'Booking Confirmation - Action Required',
                'Body' => '<p>Dear {{customer_name}},</p><p>Your booking for {{resort_name}} on {{booking_date}} is pending. Please submit payment to confirm.</p><p>This booking will expire at {{expiration_time}}.</p>'
            ],
            'payment_submission_admin' => [
                'Subject' => 'Payment Submitted for Booking #{{booking_id}}',
                'Body' => '<p>Dear Admin,</p><p>A payment has been submitted by {{customer_name}} for booking #{{booking_id}}. Please log in to verify.</p>'
            ],
            'payment_submission_customer' => [
                'Subject' => 'We Have Received Your Payment',
                'Body' => '<p>Dear {{customer_name}},</p><p>We have received your payment for booking #{{booking_id}}. We will notify you once it is verified (typically within 24-48 hours).</p>'
            ],
            'payment_verified' => [
                'Subject' => 'Booking Confirmed!',
                'Body' => '<p>Dear {{customer_name}},</p><p>Your payment has been verified and your booking #{{booking_id}} is confirmed! We look forward to seeing you.</p>'
            ],
            'booking_expired' => [
                'Subject' => 'Your Booking Has Expired',
                'Body' => '<p>Dear {{customer_name}},</p><p>Your booking #{{booking_id}} has expired due to non-payment and has been cancelled.</p>'
            ],
           'booking_confirmed_paid' => [
               'Subject' => 'Booking Confirmed and Paid: #{{booking_id}}',
               'Body' => '<p>Dear {{customer_name}},</p><p>An administrator has created a new booking for you for {{resort_name}} on {{booking_date}}. This booking is confirmed and fully paid. We look forward to seeing you!</p>'
           ]
        ];
    }
}