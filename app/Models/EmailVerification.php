<?php

class EmailVerification
{
    const MAX_ATTEMPTS = 5;
    const EXPIRATION_MINUTES = 15;

    private static $db;

    private static function getDB()
    {
        if (!self::$db) {
            require_once __DIR__ . '/../Helpers/Database.php';
            self::$db = Database::getInstance();
        }
        return self::$db;
    }

    public static function startVerification(string $email, string $code, array $payload): bool
    {
        $db = self::getDB();
        $db->prepare("DELETE FROM EmailVerifications WHERE Email = :email")->execute([':email' => $email]);

        $stmt = $db->prepare("
            INSERT INTO EmailVerifications (Email, VerificationCodeHash, Payload, Attempts, ExpiresAt)
            VALUES (:email, :hash, :payload, 0, :expiresAt)
        ");

        $expiresAt = (new DateTimeImmutable('now', new DateTimeZone('UTC')))
            ->modify('+' . self::EXPIRATION_MINUTES . ' minutes')
            ->format('Y-m-d H:i:s');

        return $stmt->execute([
            ':email' => $email,
            ':hash' => password_hash($code, PASSWORD_DEFAULT),
            ':payload' => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ':expiresAt' => $expiresAt
        ]);
    }

    public static function verifyCode(string $email, string $code)
    {
        $db = self::getDB();
        $stmt = $db->prepare("SELECT * FROM EmailVerifications WHERE Email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$record) {
            return ['success' => false, 'reason' => 'not_found'];
        }

        if ((int) $record['Attempts'] >= self::MAX_ATTEMPTS) {
            self::deleteById((int) $record['VerificationID']);
            return ['success' => false, 'reason' => 'too_many_attempts'];
        }

        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $expiresAt = new DateTimeImmutable($record['ExpiresAt'], new DateTimeZone('UTC'));
        if ($expiresAt < $now) {
            self::deleteById((int) $record['VerificationID']);
            return ['success' => false, 'reason' => 'expired'];
        }

        if (!password_verify($code, $record['VerificationCodeHash'])) {
            self::incrementAttempts((int) $record['VerificationID']);
            return ['success' => false, 'reason' => 'invalid_code', 'attempts' => $record['Attempts'] + 1];
        }

        self::deleteById((int) $record['VerificationID']);

        $payload = json_decode($record['Payload'], true);
        if (!is_array($payload)) {
            return ['success' => false, 'reason' => 'payload_corrupt'];
        }

        return ['success' => true, 'payload' => $payload];
    }

    private static function incrementAttempts(int $verificationId): void
    {
        $stmt = self::getDB()->prepare("
            UPDATE EmailVerifications
            SET Attempts = Attempts + 1
            WHERE VerificationID = :id
        ");
        $stmt->execute([':id' => $verificationId]);
    }

    private static function deleteById(int $verificationId): void
    {
        $stmt = self::getDB()->prepare("DELETE FROM EmailVerifications WHERE VerificationID = :id");
        $stmt->execute([':id' => $verificationId]);
    }
}

