<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class PromotionNotification {
    private static function getDB() {
        static $db = null;
        if ($db === null) {
            $db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        return $db;
    }

    /**
     * Create a new promotions/announcement
     */
    public static function create($data) {
        try {
            $db = self::getDB();
            $stmt = $db->prepare(
                "INSERT INTO PromotionNotifications 
                (Title, Message, ImageURL, CTAButtonText, DiscountCode, ExpirationDate, CreatedBy, Status) 
                VALUES (:title, :message, :imageURL, :ctaButtonText, :discountCode, :expirationDate, :createdBy, :status)"
            );
            
            $result = $stmt->execute([
                ':title' => $data['title'],
                ':message' => $data['message'],
                ':imageURL' => $data['image_url'] ?? null,
                ':ctaButtonText' => $data['cta_button_text'] ?? 'View Details',
                ':discountCode' => $data['discount_code'] ?? null,
                ':expirationDate' => $data['expiration_date'] ?? null,
                ':createdBy' => $data['created_by'],
                ':status' => $data['status'] ?? 'Active'
            ]);
            
            if ($result) {
                return $db->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            error_log("Failed to create promotion: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all promotions/announcements
     */
    public static function getAll($status = null) {
        $db = self::getDB();
        $sql = "SELECT p.*, u.Username as CreatorName 
                FROM PromotionNotifications p
                LEFT JOIN Users u ON p.CreatedBy = u.UserID";
        
        if ($status) {
            $sql .= " WHERE p.Status = :status";
        }
        
        $sql .= " ORDER BY p.CreatedAt DESC";
        
        $stmt = $db->prepare($sql);
        if ($status) {
            $stmt->bindValue(':status', $status);
        }
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Get promotions/announcement by ID
     */
    public static function findById($id) {
        $db = self::getDB();
        $stmt = $db->prepare("SELECT * FROM PromotionNotifications WHERE PromotionID = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    /**
     * Update promotions/announcement
     */
    public static function update($id, $data) {
        $db = self::getDB();
        $stmt = $db->prepare(
            "UPDATE PromotionNotifications 
            SET Title = :title, Message = :message, ImageURL = :imageURL, 
                CTAButtonText = :ctaButtonText, DiscountCode = :discountCode, 
                ExpirationDate = :expirationDate, Status = :status
            WHERE PromotionID = :id"
        );
        
        return $stmt->execute([
            ':id' => $id,
            ':title' => $data['title'],
            ':message' => $data['message'],
            ':imageURL' => $data['image_url'] ?? null,
            ':ctaButtonText' => $data['cta_button_text'],
            ':discountCode' => $data['discount_code'] ?? null,
            ':expirationDate' => $data['expiration_date'] ?? null,
            ':status' => $data['status']
        ]);
    }

    /**
     * Delete promotions/announcement
     */
    public static function delete($id) {
        $db = self::getDB();
        $stmt = $db->prepare("DELETE FROM PromotionNotifications WHERE PromotionID = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Send promotions/announcement to users
     */
    public static function sendToCustomers($promotionId, $customerIds) {
        $promotion = self::findById($promotionId);
        if (!$promotion) {
            return ['success' => false, 'message' => 'News/Announcement not found'];
        }

        require_once __DIR__ . '/../../vendor/autoload.php';
        require_once __DIR__ . '/../../config/mail.php';

        $sentCount = 0;
        $failedCount = 0;

        foreach ($customerIds as $customerId) {
            $user = User::findById($customerId);
            if ($user && !empty($user['Email'])) {
                try {
                    $mail = new PHPMailer(true);
                    
                    // Server settings
                    $mail->isSMTP();
                    $mail->Host = MAIL_HOST;
                    $mail->SMTPAuth = MAIL_SMTPAUTH;
                    $mail->Username = MAIL_USERNAME;
                    $mail->Password = MAIL_PASSWORD;
                    $mail->SMTPSecure = MAIL_SMTPSECURE;
                    $mail->Port = MAIL_PORT;

                    // Recipients
                    $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
                    $mail->addAddress($user['Email'], $user['FirstName'] . ' ' . $user['LastName']);

                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = $promotion->Title;
                    
                    // Convert relative image URL to absolute URL for email
                    $imageUrl = $promotion->ImageURL;
                    if (!empty($imageUrl)) {
                        if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                            // It's a relative path, convert to absolute
                            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
                            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                            
                            // Remove leading slash and construct full URL
                            $cleanPath = ltrim($imageUrl, '/');
                            
                            // Check if path starts with 'public/', if so, remove it since it's in the URL path
                            if (strpos($cleanPath, 'public/') === 0) {
                                $cleanPath = substr($cleanPath, 7); // Remove 'public/'
                            }
                            
                            // Construct the full URL
                            $imageUrl = $protocol . $host . '/ResortsSystem/public/' . $cleanPath;
                        }
                        // Log the final image URL for debugging
                        error_log("News/Announcement Image URL: " . $imageUrl);
                    }
                    
                    // Generate email body from template
                    $data = [
                        'customer_name' => $user['FirstName'] ?? $user['Username'],
                        'title' => $promotion->Title,
                        'message' => $promotion->Message,
                        'image_url' => $imageUrl,
                        'cta_button_text' => $promotion->CTAButtonText,
                        'discount_code' => $promotion->DiscountCode,
                        'expiration_date' => $promotion->ExpirationDate
                    ];
                    
                    ob_start();
                    include __DIR__ . '/../Views/email/promotion.php';
                    $mail->Body = ob_get_clean();

                    $mail->send();
                    $sentCount++;
                    self::logSent($promotionId, $customerId);
                    
                } catch (Exception $e) {
                    error_log("Failed to send promotions/announcement to {$user['Email']}: {$mail->ErrorInfo}");
                    $failedCount++;
                }
            } else {
                $failedCount++;
            }
        }

        return [
            'success' => true,
            'sent' => $sentCount,
            'failed' => $failedCount
        ];
    }

    /**
     * Log sent promotion
     */
    private static function logSent($promotionId, $customerId) {
        $db = self::getDB();
        $stmt = $db->prepare(
            "INSERT INTO PromotionSentLog (PromotionID, CustomerID, SentAt) 
            VALUES (:promotionId, :customerId, NOW())"
        );
        $stmt->execute([
            ':promotionId' => $promotionId,
            ':customerId' => $customerId
        ]);
    }

    /**
     * Get sent statistics
     */
    public static function getSentStats($promotionId) {
        $db = self::getDB();
        $stmt = $db->prepare(
            "SELECT COUNT(*) as total_sent 
            FROM PromotionSentLog 
            WHERE PromotionID = :promotionId"
        );
        $stmt->bindValue(':promotionId', $promotionId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_OBJ);
    }
}
