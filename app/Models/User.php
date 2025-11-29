<?php

class User {
    private static $db;
    private static $table = 'Users';

    public function __construct() {
        // Constructor can be used for setting default values.
    }

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
     * Creates a new user in the database.
     *
     * @param string $username
     * @param string $password
     * @param string $email
     * @param string $role
     * @param string $firstName
     * @param string $lastName
     * @param string $phoneNumber
     * @param bool $passwordIsHashed Whether the supplied password has already been hashed.
     * @return bool|string Returns true on success or an error code string.
     */
    public static function create($username, $password, $email, $role = 'Customer', $firstName = null, $lastName = null, $phoneNumber = null, $notes = null, $socials = null, $profileImageURL = null, $adminType = null, $passwordIsHashed = false) {
        // Check for existing user
        if (self::findByUsername($username)) {
            return 'username_exists';
        }
        if (self::findByEmail($email)) {
            return 'email_exists';
        }

        // Ensure AdminType is unique for sub-admins
        if ($role === 'Admin' && $adminType !== 'Admin' && self::findByAdminType($adminType)) {
            return 'admin_type_exists';
        }

        // Hash the password for security unless already hashed
        $hashed_password = $passwordIsHashed ? $password : password_hash($password, PASSWORD_DEFAULT);

        $query = "INSERT INTO " . self::$table . " (Username, Password, Email, Role, AdminType, FirstName, LastName, PhoneNumber, ProfileImageURL, Notes, Socials)
                  VALUES (:username, :password, :email, :role, :adminType, :firstName, :lastName, :phoneNumber, :profileImageURL, :notes, :socials)";

        $stmt = self::getDB()->prepare($query);

        // Bind parameters
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':role', $role);
        $stmt->bindParam(':adminType', $adminType);
        $stmt->bindParam(':firstName', $firstName);
        $stmt->bindParam(':lastName', $lastName);
        $stmt->bindParam(':phoneNumber', $phoneNumber);
        $stmt->bindParam(':profileImageURL', $profileImageURL);
        $stmt->bindParam(':notes', $notes);
        $stmt->bindParam(':socials', $socials);

        // Execute the query
        try {
            if ($stmt->execute()) {
                return true;
            }
        } catch (PDOException $e) {
            // Return a generic error to avoid leaking implementation details
            return 'registration_failed';
        }

        return false;
    }

    /**
     * Finds a user by their username.
     *
     * @param string $username
     * @return mixed Returns the user data as an associative array or false if not found.
     */
    public static function findByUsername($username, $excludeId = null) {
        $sql = "SELECT * FROM " . self::$table . " WHERE Username = :username";
        if ($excludeId) {
            $sql .= " AND UserID != :excludeId";
        }
        $sql .= " LIMIT 1";
        
        $stmt = self::getDB()->prepare($sql);
        $stmt->bindParam(':username', $username);
        if ($excludeId) {
            $stmt->bindParam(':excludeId', $excludeId);
        }
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function findByEmail($email, $excludeId = null) {
        $sql = "SELECT * FROM " . self::$table . " WHERE Email = :email";
        if ($excludeId) {
            $sql .= " AND UserID != :excludeId";
        }
        $sql .= " LIMIT 1";
        
        $stmt = self::getDB()->prepare($sql);
        $stmt->bindParam(':email', $email);
        if ($excludeId) {
            $stmt->bindParam(':excludeId', $excludeId);
        }
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function findByAdminType($adminType) {
        $query = "SELECT * FROM " . self::$table . " WHERE AdminType = :adminType LIMIT 1";
        
        $stmt = self::getDB()->prepare($query);
        $stmt->bindParam(':adminType', $adminType);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function findAll() {
        $db = self::getDB();
        $sql = "
            SELECT
                u.UserID, u.Username, u.Email, u.Role, u.AdminType, u.IsActive,
                u.FirstName, u.LastName, u.PhoneNumber, u.ProfileImageURL, u.Notes, u.Socials, u.CreatedAt,
                GROUP_CONCAT(r.Name SEPARATOR ', ') as AssignedResorts
            FROM " . self::$table . " u
            LEFT JOIN StaffResortAssignments sra ON u.UserID = sra.UserID AND u.Role = 'Staff'
            LEFT JOIN Resorts r ON sra.ResortID = r.ResortID
            GROUP BY u.UserID
            ORDER BY u.CreatedAt DESC
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findById($id) {
        $query = "SELECT * FROM " . self::$table . " WHERE UserID = :id LIMIT 1";
        
        $stmt = self::getDB()->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function update($id, $username, $email, $firstName, $lastName, $phoneNumber, $notes = null, $socials = null, $profileImageURL = null, $notifyOnNewReservation = null, $notifyOnReservationUpdate = null) {
        // Check if the new username or email already exists for another user
        $query = "SELECT UserID FROM " . self::$table . " WHERE (Username = :username OR Email = :email) AND UserID != :id";
        $stmt = self::getDB()->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        if ($stmt->fetch()) {
            return 'username_or_email_exists';
        }

        $fields = [
            'Username' => $username,
            'Email' => $email,
            'FirstName' => $firstName,
            'LastName' => $lastName,
            'PhoneNumber' => $phoneNumber,
            'Notes' => $notes,
            'Socials' => $socials,
        ];

        if ($profileImageURL !== null) {
            $fields['ProfileImageURL'] = $profileImageURL;
        }

        // Optional notification preferences
        if ($notifyOnNewReservation !== null) {
            $fields['NotifyOnNewReservation'] = $notifyOnNewReservation ? 1 : 0;
        }
        if ($notifyOnReservationUpdate !== null) {
            $fields['NotifyOnReservationUpdate'] = $notifyOnReservationUpdate ? 1 : 0;
        }

        $setClauses = [];
        foreach ($fields as $key => $value) {
            $setClauses[] = "$key = :$key";
        }
        $setClause = implode(', ', $setClauses);

        $query = "UPDATE " . self::$table . " SET $setClause WHERE UserID = :id";
        
        $stmt = self::getDB()->prepare($query);

        $stmt->bindParam(':id', $id);
        foreach ($fields as $key => &$value) {
            $stmt->bindParam(":$key", $value);
        }

        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public static function delete($id) {
        $query = "DELETE FROM " . self::$table . " WHERE UserID = :id";
        $stmt = self::getDB()->prepare($query);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }

    public static function toggleActivationStatus($id) {
        $query = "UPDATE " . self::$table . " SET IsActive = !IsActive WHERE UserID = :id";
        $stmt = self::getDB()->prepare($query);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }

    public static function updatePassword($id, $newPassword) {
        $hashed_password = password_hash($newPassword, PASSWORD_DEFAULT);

        $query = "UPDATE " . self::$table . " SET Password = :password WHERE UserID = :id";
        
        $stmt = self::getDB()->prepare($query);

        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':password', $hashed_password);

        return $stmt->execute();
    }

    /**
     * Find users by role
     */
    public static function findByRole($role) {
        $query = "SELECT * FROM " . self::$table . " WHERE Role = :role ORDER BY CreatedAt DESC";
        
        $stmt = self::getDB()->prepare($query);
        $stmt->bindParam(':role', $role);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get admin users for notifications
     */
    public static function getAdminUsers() {
        return self::findByRole('Admin');
    }

    /**
     * Check if user is Main Admin (System Admin)
     */
    public static function isMainAdmin($userId) {
        $user = self::findById($userId);
        return $user && $user['Role'] === 'Admin' && $user['AdminType'] === 'Admin';
    }

    /**
     * Check if a Main Admin (AdminType = 'Admin') already exists.
     *
     * @return bool
     */
    public static function mainAdminExists() {
        $query = "SELECT COUNT(*) FROM " . self::$table . " WHERE Role = 'Admin' AND AdminType = 'Admin'";
        $stmt = self::getDB()->prepare($query);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Get admin type display name
     */
    public static function getAdminTypeDisplay($adminType) {
        $displayNames = [
            'Admin' => 'Main Admin',
            'BookingAdmin' => 'Reservations Manager',
            'OperationsAdmin' => 'Resort Manager',
            'ReportsAdmin' => 'Finance Manager'
        ];
        return $displayNames[$adminType] ?? $adminType;
    }

    /**
     * Check if user has specific admin permission
     */
    public static function hasAdminPermission($userId, $permission) {
        $user = self::findById($userId);
        if (!$user || $user['Role'] !== 'Admin') {
            return false;
        }

        $adminType = $user['AdminType'];
        
        // Main Admin has all permissions
        if ($adminType === 'Admin') {
            return true;
        }

        // Permission mappings for sub-admins
        $permissions = [
            'BookingAdmin' => ['booking_management', 'payment_verification', 'onsite_booking', 'view_customers', 'feedback_view'],
            'OperationsAdmin' => ['pricing_management', 'advanced_blocking', 'resort_management', 'preview_customer', 'feedback_view'],
            'ReportsAdmin' => ['dashboard_view', 'income_analytics_view', 'operational_reports', 'feedback_view']
        ];

        return isset($permissions[$adminType]) && in_array($permission, $permissions[$adminType]);
    }
    public static function getAssignedResorts($userId) {
        $db = self::getDB();
        $stmt = $db->prepare("
            SELECT r.ResortID, r.Name
            FROM Resorts r
            JOIN StaffResortAssignments sra ON r.ResortID = sra.ResortID
            WHERE sra.UserID = :userId
            ORDER BY r.Name ASC
        ");
        $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public static function assignResorts($userId, $resortIds) {
        $db = self::getDB();
        $db->beginTransaction();
        try {
            // 1. Delete existing assignments for the user
            $stmt = $db->prepare("DELETE FROM StaffResortAssignments WHERE UserID = :userId");
            $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
            $stmt->execute();

            // 2. Insert new assignments
            if (!empty($resortIds)) {
                $stmt = $db->prepare("INSERT INTO StaffResortAssignments (UserID, ResortID) VALUES (:userId, :resortId)");
                foreach ($resortIds as $resortId) {
                    $stmt->execute([
                        ':userId' => $userId,
                        ':resortId' => $resortId
                    ]);
                }
            }
            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            return false;
        }
    }
}