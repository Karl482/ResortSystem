<?php

class Facility {
    public $facilityId;
    public $resortId;
    public $name;
    public $rate;
   public $shortDescription;
   public $fullDescription;
   public $mainPhotoURL;
   public $photos = [];
   public $completedBookingsCount = 0;

    private static $db;

    public function __construct() {
        // The constructor can be used to set default values if needed.
    }

    private static function getDB() {
        if (!self::$db) {
            require_once __DIR__ . '/../Helpers/Database.php';
            self::$db = Database::getInstance();
        }
        return self::$db;
    }

    public static function create(Facility $facility) {
        $db = self::getDB();
        $stmt = $db->prepare(
            "INSERT INTO Facilities (ResortID, Name, Rate, ShortDescription, FullDescription, MainPhotoURL)
             VALUES (:resortId, :name, :rate, :shortDescription, :fullDescription, :mainPhotoURL)"
        );
        $stmt->bindValue(':resortId', $facility->resortId, PDO::PARAM_INT);
        $stmt->bindValue(':name', $facility->name, PDO::PARAM_STR);
        $stmt->bindValue(':rate', $facility->rate, PDO::PARAM_STR);
       $stmt->bindValue(':shortDescription', $facility->shortDescription, PDO::PARAM_STR);
       $stmt->bindValue(':fullDescription', $facility->fullDescription, PDO::PARAM_STR);
       $stmt->bindValue(':mainPhotoURL', $facility->mainPhotoURL, PDO::PARAM_STR);
        
        if ($stmt->execute()) {
            return $db->lastInsertId();
        }
        return false;
    }

    public static function findById($facilityId) {
        $db = self::getDB();
        $stmt = $db->prepare("SELECT * FROM Facilities WHERE FacilityID = :facilityId");
        $stmt->bindValue(':facilityId', $facilityId, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data) {
            $facility = new Facility();
            $facility->facilityId = $data['FacilityID'];
            $facility->resortId = $data['ResortID'];
            $facility->name = $data['Name'];
            $facility->rate = $data['Rate'];
           $facility->shortDescription = $data['ShortDescription'];
           $facility->fullDescription = $data['FullDescription'];
           $facility->mainPhotoURL = $data['MainPhotoURL'];
           $facility->photos = self::getPhotos($facilityId);
            return $facility;
        }
        return null;
    }

    public static function findAll() {
        $db = self::getDB();
        $stmt = $db->query("SELECT * FROM Facilities ORDER BY FacilityID ASC");
        $facilities = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $facility = new Facility();
            $facility->facilityId = $data['FacilityID'];
            $facility->resortId = $data['ResortID'];
            $facility->name = $data['Name'];
            $facility->rate = $data['Rate'];
           $facility->shortDescription = $data['ShortDescription'];
           $facility->fullDescription = $data['FullDescription'];
           $facility->mainPhotoURL = $data['MainPhotoURL'];
            $facilities[] = $facility;
        }
        return $facilities;
    }

    public static function findAllWithResort() {
        $db = self::getDB();
        $stmt = $db->query(
            "SELECT f.*, r.Name as ResortName
             FROM Facilities f
             JOIN Resorts r ON f.ResortID = r.ResortID
             ORDER BY f.FacilityID ASC"
        );
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public static function findByResortId($resortId) {
        $db = self::getDB();
        $stmt = $db->prepare("SELECT * FROM Facilities WHERE ResortID = :resortId");
        $stmt->bindValue(':resortId', $resortId, PDO::PARAM_INT);
        $stmt->execute();
        $facilities = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $facility = new Facility();
            $facility->facilityId = $data['FacilityID'];
            $facility->resortId = $data['ResortID'];
            $facility->name = $data['Name'];
            $facility->rate = $data['Rate'];
           $facility->shortDescription = $data['ShortDescription'];
           $facility->fullDescription = $data['FullDescription'];
           $facility->mainPhotoURL = $data['MainPhotoURL'];
            $facilities[] = $facility;
        }
        return $facilities;
    }

    public static function update(Facility $facility) {
        $db = self::getDB();
        $stmt = $db->prepare(
            "UPDATE Facilities
             SET ResortID = :resortId, Name = :name, Rate = :rate,
                 ShortDescription = :shortDescription, FullDescription = :fullDescription, MainPhotoURL = :mainPhotoURL
             WHERE FacilityID = :facilityId"
        );
        $stmt->bindValue(':resortId', $facility->resortId, PDO::PARAM_INT);
        $stmt->bindValue(':name', $facility->name, PDO::PARAM_STR);
        $stmt->bindValue(':rate', $facility->rate, PDO::PARAM_STR);
       $stmt->bindValue(':shortDescription', $facility->shortDescription, PDO::PARAM_STR);
       $stmt->bindValue(':fullDescription', $facility->fullDescription, PDO::PARAM_STR);
       $stmt->bindValue(':mainPhotoURL', $facility->mainPhotoURL, PDO::PARAM_STR);
        $stmt->bindValue(':facilityId', $facility->facilityId, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    public static function delete($facilityId) {
        $db = self::getDB();
        $stmt = $db->prepare("DELETE FROM Facilities WHERE FacilityID = :facilityId");
        $stmt->bindValue(':facilityId', $facilityId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public static function getPhotos($facilityId) {
        $db = self::getDB();
        $stmt = $db->prepare("SELECT * FROM FacilityPhotos WHERE FacilityID = :facilityId ORDER BY CreatedAt DESC");
        $stmt->bindValue(':facilityId', $facilityId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function addPhoto($facilityId, $photoURL) {
        $db = self::getDB();
        $stmt = $db->prepare("INSERT INTO FacilityPhotos (FacilityID, PhotoURL) VALUES (:facilityId, :photoURL)");
        $stmt->bindValue(':facilityId', $facilityId, PDO::PARAM_INT);
        $stmt->bindValue(':photoURL', $photoURL, PDO::PARAM_STR);
        return $stmt->execute();
    }

    public static function deletePhoto($photoId) {
        $db = self::getDB();
        $stmt = $db->prepare("DELETE FROM FacilityPhotos WHERE PhotoID = :photoId");
        $stmt->bindValue(':photoId', $photoId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public static function findPhotoById($photoId) {
        $db = self::getDB();
        $stmt = $db->prepare("SELECT * FROM FacilityPhotos WHERE PhotoID = :photoId");
        $stmt->bindValue(':photoId', $photoId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function setMainPhoto($facilityId, $photoURL) {
        $db = self::getDB();
        $stmt = $db->prepare("UPDATE Facilities SET MainPhotoURL = :photoURL WHERE FacilityID = :facilityId");
        $stmt->bindValue(':photoURL', $photoURL, PDO::PARAM_STR);
        $stmt->bindValue(':facilityId', $facilityId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Get the fixed price for a facility (used as add-on in resort-centric booking)
     */
    public static function getFixedPrice($facilityId) {
        $facility = self::findById($facilityId);
        return $facility ? $facility->rate : 0;
    }

    /**
     * Update facility rate (fixed pricing)
     */
    public static function updateRate($facilityId, $rate) {
        $db = self::getDB();
        $stmt = $db->prepare("UPDATE Facilities SET Rate = :rate WHERE FacilityID = :facilityId");
        $stmt->bindValue(':rate', $rate, PDO::PARAM_STR);
        $stmt->bindValue(':facilityId', $facilityId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Get facilities formatted for booking selection (with pricing)
     */
    public static function getFacilitiesForBooking($resortId) {
        $facilities = self::findByResortId($resortId);
        $formatted = [];

        foreach ($facilities as $facility) {
            $formatted[] = [
                'id' => $facility->facilityId,
                'name' => $facility->name,
                'price' => $facility->rate,
                'description' => $facility->shortDescription,
                'display' => $facility->name . ' (₱' . number_format($facility->rate, 2) . ')'
            ];
        }

        return $formatted;
    }

    /**
     * Calculate total cost for multiple facilities
     */
    public static function calculateFacilitiesTotalCost($facilityIds) {
        if (empty($facilityIds)) {
            return 0;
        }

        $total = 0;
        foreach ($facilityIds as $facilityId) {
            $total += self::getFixedPrice($facilityId);
        }

        return $total;
    }

    /**
     * Get facilities with resort information for admin management
     */
    public static function getFacilitiesWithResortInfo() {
        $db = self::getDB();
        $stmt = $db->query(
            "SELECT f.*, r.Name as ResortName
             FROM Facilities f
             LEFT JOIN Resorts r ON f.ResortID = r.ResortID
             ORDER BY r.Name ASC, f.Name ASC"
        );
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Get facilities with aggregated feedback data for a specific resort.
     */
    public static function getFacilitiesWithFeedback($resortId) {
        $db = self::getDB();
        $stmt = $db->prepare(
            "SELECT
                f.FacilityID,
                f.Name,
                f.ShortDescription,
                f.FullDescription,
                f.MainPhotoURL,
                COALESCE(AVG(ff.Rating), 0) as AverageRating,
                COUNT(ff.FacilityFeedbackID) as FeedbackCount
            FROM Facilities f
            LEFT JOIN FacilityFeedback ff ON f.FacilityID = ff.FacilityID
            WHERE f.ResortID = :resortId
            GROUP BY f.FacilityID"
        );
        $stmt->bindValue(':resortId', $resortId, PDO::PARAM_INT);
        $stmt->execute();
        
        $facilities = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Now, for each facility, get the completed bookings count
        foreach ($facilities as &$facility) {
            $facility['CompletedBookingsCount'] = Booking::countCompletedBookingsByFacility($facility['FacilityID']);
        }

        return $facilities;
    }
}
