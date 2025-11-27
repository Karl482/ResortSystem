<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Models/Booking.php';
require_once __DIR__ . '/../Models/Payment.php';
require_once __DIR__ . '/../Models/Facility.php';

require_once __DIR__ . '/../Models/Resort.php';
require_once __DIR__ . '/../Models/BlockedFacilityAvailability.php';
require_once __DIR__ . '/../Models/BlockedResortAvailability.php';
require_once __DIR__ . '/../Models/ResortTimeframePricing.php';
require_once __DIR__ . '/../Models/ResortPaymentMethods.php';
require_once __DIR__ . '/../Models/BookingFacilities.php';
// Phase 6: Advanced lifecycle management models
require_once __DIR__ . '/../Models/BookingLifecycleManager.php';
require_once __DIR__ . '/../Models/BookingAuditTrail.php';
require_once __DIR__ . '/../Models/PaymentSchedule.php';
require_once __DIR__ . '/../Helpers/ValidationHelper.php';
require_once __DIR__ . '/../Models/EmailTemplate.php';
require_once __DIR__ . '/../Helpers/AsyncHelper.php';

class AdminController {
    private $db;
    private $userModel;

    public function __construct() {
        // Ensure user is an admin
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
            // If user is not logged in, deny access
            http_response_code(403);
            require_once __DIR__ . '/../Views/errors/403.php';
            exit();
        }

        // Allow Staff to access their own dashboard, but ensure they are logged in
        // The specific view logic will be handled in the dashboard method.
        // For other admin functions, enforce Admin-only access later in specific methods if needed.
        // This constructor's primary role is now general access control and DB setup.
        if ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Staff') {
            http_response_code(403);
            require_once __DIR__ . '/../Views/errors/403.php';
            exit();
        }

        try {
            $this->db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
        $this->userModel = new User($this->db);
    }

    public function dashboard() {
        $resortId = filter_input(INPUT_GET, 'resort_id', FILTER_VALIDATE_INT);
        $resorts = Resort::findAll(); // For the filter dropdown

        // Role-based dashboard logic
        if ($_SESSION['role'] === 'Admin') {
            // Check if this is a Main Admin or a sub-admin
            if (User::isMainAdmin($_SESSION['user_id'])) {
                // Main Admin gets the full dashboard
                $todaysBookings = Booking::findTodaysBookings($resortId);
                $upcomingBookings = Booking::findUpcomingBookings($resortId, 10);

                // Augment bookings with payment status
                foreach ($todaysBookings as $booking) {
                    $payments = Payment::findByBookingId($booking->BookingID);
                    if (empty($payments)) {
                        $booking->PaymentStatus = 'Unpaid';
                    } else {
                        // Use the status of the most recent payment
                        $booking->PaymentStatus = $payments[0]->Status;
                    }
                }

                foreach ($upcomingBookings as $booking) {
                    $payments = Payment::findByBookingId($booking->BookingID);
                    if (empty($payments)) {
                        $booking->PaymentStatus = 'Unpaid';
                    } else {
                        $booking->PaymentStatus = $payments[0]->Status;
                    }
                }

                // Get financial and history data
                $currentMonth = date('m');
                $currentYear = date('Y');
                $monthlyIncome = Payment::getMonthlyIncome($currentYear, $currentMonth, $resortId);
                $bookingHistory = Booking::getBookingHistory($resortId, 10);
                $dailyIncomeData = Payment::getDailyIncomeForMonth($currentYear, $currentMonth, $resortId);

                // Get counts for dashboard buttons
                $pendingPaymentCount = Payment::getPendingPaymentCount($resortId);
                $activeBookingCount = Booking::getActiveBookingsCountForAdmin($resortId);

                include __DIR__ . '/../Views/admin/dashboard.php';
            } else {
                // Sub-admins get the simplified staff dashboard
                $this->staffDashboard($resortId, $resorts);
            }
        } elseif ($_SESSION['role'] === 'Staff') {
            $this->staffDashboard($resortId, $resorts);
        } else {
            // Fallback for any other roles or errors
            http_response_code(403);
            require_once __DIR__ . '/../Views/errors/403.php';
            exit();
        }
    }

    public function staffDashboard($resortId = null, $resorts = null) {
        // If called directly, fetch the necessary data
        if ($resortId === null) {
            $resortId = filter_input(INPUT_GET, 'resort_id', FILTER_VALIDATE_INT);
        }

        // Staff's resort list is now based on their assignments
        if ($_SESSION['role'] === 'Staff') {
            $staffUserId = $_SESSION['user_id'];
            $assignedResorts = User::getAssignedResorts($staffUserId);
            $resorts = $assignedResorts; // Always use assigned resorts for staff
        } elseif ($resorts === null) {
            // For sub-admins, if resorts aren't passed, get all
            $resorts = Resort::findAll();
        }

        // Determine if "All Resorts" should be an option
        $totalResorts = count(Resort::findAll());
        $allResortsAssigned = (count($resorts) === $totalResorts);

        $todaysBookings = Booking::findTodaysBookings($resortId);
        $upcomingBookings = Booking::findUpcomingBookings($resortId);

        foreach ($todaysBookings as $booking) {
            $payments = Payment::findByBookingId($booking->BookingID);
            if (empty($payments)) {
                $booking->PaymentStatus = 'Unpaid';
            } else {
                $booking->PaymentStatus = $payments[0]->Status;
            }
        }

        foreach ($upcomingBookings as $booking) {
            $payments = Payment::findByBookingId($booking->BookingID);
            if (empty($payments)) {
                $booking->PaymentStatus = 'Unpaid';
            } else {
                $booking->PaymentStatus = $payments[0]->Status;
            }
        }
        include __DIR__ . '/../Views/admin/staff_dashboard.php';
    }

    public function incomeAnalytics() {
        if ($_SESSION['role'] !== 'Admin') {
            http_response_code(403);
            require_once __DIR__ . '/../Views/errors/403.php';
            exit();
        }

        // Operations Admin and Reports Admin can view, but not Main Admin exclusive
        if (!User::hasAdminPermission($_SESSION['user_id'], 'income_analytics') &&
            !User::hasAdminPermission($_SESSION['user_id'], 'income_analytics_view')) {
            http_response_code(403);
            require_once __DIR__ . '/../Views/errors/403.php';
            exit();
        }

        $resortId = filter_input(INPUT_GET, 'resort_id', FILTER_VALIDATE_INT);
        $year = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT) ?? date('Y');
        $month = filter_input(INPUT_GET, 'month', FILTER_VALIDATE_INT) ?? date('m');

        $resorts = Resort::findAll();
        
        // Data for the whole year
        $yearlyIncomeData = Payment::getMonthlyIncomeForYear($year, $resortId);

        // Data for the selected month
        $dailyIncomeData = Payment::getDailyIncomeForMonth($year, $month, $resortId);
        $totalMonthlyIncome = array_sum($dailyIncomeData);

        $pageTitle = "Income Analytics";
        require_once __DIR__ . '/../Views/admin/income_analytics.php';
    }

    public function operationalReports() {
        if ($_SESSION['role'] !== 'Admin') {
            http_response_code(403);
            require_once __DIR__ . '/../Views/errors/403.php';
            exit();
        }

        // Only Main Admin and Reports Admin can access operational reports
        if (!User::hasAdminPermission($_SESSION['user_id'], 'operational_reports')) {
            http_response_code(403);
            require_once __DIR__ . '/../Views/errors/403.php';
            exit();
        }

        $filters = [
            'resort_id' => filter_input(INPUT_GET, 'resort_id', FILTER_VALIDATE_INT),
            'status' => filter_input(INPUT_GET, 'status', FILTER_UNSAFE_RAW),
            'customer_id' => filter_input(INPUT_GET, 'customer_id', FILTER_VALIDATE_INT),
            'payment_status' => filter_input(INPUT_GET, 'payment_status', FILTER_UNSAFE_RAW),
            'month' => filter_input(INPUT_GET, 'month', FILTER_VALIDATE_INT),
            'year' => filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT)
        ];

        $resorts = Resort::findAll();
        $customers = User::findByRole('Customer');
        $bookings = Booking::getBookingsWithPaymentDetails($filters);

        // Calculate summary statistics for the report view
        $totalBookings = count($bookings);
        $totalRevenue = 0;
        $statusCounts = [
            'Pending' => 0,
            'Confirmed' => 0,
            'Completed' => 0,
            'Cancelled' => 0,
        ];

        foreach ($bookings as $booking) {
            if ($booking->Status !== 'Cancelled') {
                $totalRevenue += $booking->TotalAmount;
            }
            if (array_key_exists($booking->Status, $statusCounts)) {
                $statusCounts[$booking->Status]++;
            }
        }

        require_once __DIR__ . '/../Views/admin/operational_reports.php';
    }

    public function users() {
        // Only Main Admin can access user management
        if (!User::isMainAdmin($_SESSION['user_id'])) {
            http_response_code(403);
            require_once __DIR__ . '/../Views/errors/403.php';
            exit();
        }
        
        $users = $this->userModel->findAll();
        $resorts = Resort::findAll();
        include __DIR__ . '/../Views/admin/users.php';
    }

    public function addUser() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = filter_input(INPUT_POST, 'username', FILTER_UNSAFE_RAW);
            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
            $password = $_POST['password'];
            $confirmPassword = $_POST['confirm_password'];
            $role = filter_input(INPUT_POST, 'role', FILTER_UNSAFE_RAW);
            $adminType = filter_input(INPUT_POST, 'adminType', FILTER_UNSAFE_RAW) ?? null;
            $firstName = filter_input(INPUT_POST, 'firstName', FILTER_UNSAFE_RAW);
            $lastName = filter_input(INPUT_POST, 'lastName', FILTER_UNSAFE_RAW);
            $phoneNumber = filter_input(INPUT_POST, 'phoneNumber', FILTER_UNSAFE_RAW);
            $notes = filter_input(INPUT_POST, 'notes', FILTER_UNSAFE_RAW) ?? '';
            $socials = filter_input(INPUT_POST, 'socials', FILTER_UNSAFE_RAW) ?? '';
 
            // Only Main Admin can create other admins
            if ($role === 'Admin' && !User::isMainAdmin($_SESSION['user_id'])) {
                header('Location: ?controller=admin&action=users&error=only_main_admin_can_create_admins');
                exit();
            }
            
            if ($password !== $confirmPassword) {
                header('Location: ?controller=admin&action=addUser&error=password_mismatch');
                exit();
            }

            $result = $this->userModel->create($username, $password, $email, $role, $firstName, $lastName, $phoneNumber, $notes, $socials, null, $adminType);
            if ($result === true) {
                header('Location: ?controller=admin&action=users&status=user_added');
                exit();
            } else {
                header('Location: ?controller=admin&action=users&error=' . $result);
                exit();
            }
        }
    }

    // The getAddUserForm() method is no longer needed as the form is now static in user_modals.php
    // public function getAddUserForm() { ... }

    public function editUser() {
        if (!isset($_GET['id'])) {
            die('User ID not specified.');
        }
        $userId = $_GET['id'];
 
        $userToEdit = $this->userModel->findById($userId);
        // Only Main Admin can edit other admins; sub-admins can only edit themselves
        if ($userToEdit && $userToEdit['Role'] === 'Admin' && $userId != $_SESSION['user_id'] && !User::isMainAdmin($_SESSION['user_id'])) {
            header('Location: ?controller=admin&action=users&error=only_main_admin_can_edit_admins');
            exit();
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Handle the form submission
            $username = filter_input(INPUT_POST, 'username', FILTER_UNSAFE_RAW);
            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
            $firstName = filter_input(INPUT_POST, 'firstName', FILTER_UNSAFE_RAW);
            $lastName = filter_input(INPUT_POST, 'lastName', FILTER_UNSAFE_RAW);
            $phoneNumber = filter_input(INPUT_POST, 'phoneNumber', FILTER_UNSAFE_RAW);
            $notes = filter_input(INPUT_POST, 'notes', FILTER_UNSAFE_RAW) ?? '';
            $socials = null;
            $profileImageURL = null;

            if ($userToEdit['Role'] === 'Admin') {
                $socials = filter_input(INPUT_POST, 'socials', FILTER_UNSAFE_RAW) ?? '';

                if (isset($_FILES['profileImage']) && $_FILES['profileImage']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = __DIR__ . '/../../public/uploads/profiles/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    $fileName = uniqid() . '-' . basename($_FILES['profileImage']['name']);
                    $targetFile = $uploadDir . $fileName;

                    if (move_uploaded_file($_FILES['profileImage']['tmp_name'], $targetFile)) {
                        $profileImageURL = 'public/uploads/profiles/' . $fileName;

                        if (!empty($userToEdit['ProfileImageURL'])) {
                            $oldImagePath = __DIR__ . '/../../' . $userToEdit['ProfileImageURL'];
                            if (file_exists($oldImagePath)) {
                                unlink($oldImagePath);
                            }
                        }
                    }
                }
            }


            if ($userToEdit['Role'] === 'Admin' && $userId == $_SESSION['user_id']) {
                $notes = $userToEdit['Notes']; // Keep existing notes
            }

            $result = $this->userModel->update($userId, $username, $email, $firstName, $lastName, $phoneNumber, $notes, $socials, $profileImageURL);

            if ($result === true) {
                // If admin edits their own profile, update session
                if ($userId == $_SESSION['user_id']) {
                    $_SESSION['username'] = $username;
                }
                header('Location: ?controller=admin&action=users&status=user_updated');
                exit();
            } else {
                header('Location: ?controller=admin&action=users&error=' . $result);
                exit();
            }
        }
        // This part is now handled by getEditUserForm
    }

    public function getUserJson() {
        header('Content-Type: application/json');
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'User ID not specified.']);
            exit();
        }
        $userId = $_GET['id'];
        $user = $this->userModel->findById($userId);

        if ($user) {
            echo json_encode($user);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'User not found.']);
        }
        exit();
    }

    public function getStaffResortAssignmentsJson() {
        header('Content-Type: application/json');
        if (!User::isMainAdmin($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            exit();
        }

        $userId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$userId) {
            http_response_code(400);
            echo json_encode(['error' => 'User ID not specified.']);
            exit();
        }

        $assignedResorts = User::getAssignedResorts($userId);
        $assignedResortIds = array_map(function($resort) {
            return $resort->ResortID;
        }, $assignedResorts);

        echo json_encode(['assigned' => $assignedResortIds]);
        exit();
    }

    public function updateStaffResortAssignments() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !User::isMainAdmin($_SESSION['user_id'])) {
            header('Location: ?controller=admin&action=users&error=unauthorized');
            exit();
        }

        $userId = filter_input(INPUT_POST, 'userId', FILTER_VALIDATE_INT);
        $resortIds = $_POST['resort_ids'] ?? [];

        if (!$userId) {
            header('Location: ?controller=admin&action=users&error=invalid_user');
            exit();
        }

        if (User::assignResorts($userId, $resortIds)) {
            header('Location: ?controller=admin&action=users&status=assignments_updated');
        } else {
            header('Location: ?controller=admin&action=users&error=assignment_failed');
        }
        exit();
    }

    public function deleteUser() {
        if (!isset($_GET['id'])) {
            die('User ID not specified.');
        }
        $userId = $_GET['id'];

        // Prevent admin from deleting themselves
        if ($userId == $_SESSION['user_id']) {
            header('Location: ?controller=admin&action=users&error=cannot_delete_self');
            exit();
        }

        // Only Main Admin can delete other admins
        $userToDelete = $this->userModel->findById($userId);
        if ($userToDelete && $userToDelete['Role'] === 'Admin' && !User::isMainAdmin($_SESSION['user_id'])) {
            header('Location: ?controller=admin&action=users&error=only_main_admin_can_delete_admins');
            exit();
        }

        if ($this->userModel->delete($userId)) {
            header('Location: ?controller=admin&action=users&status=user_deleted');
            exit();
        } else {
            die('Failed to delete user.');
        }
    }

    public function toggleUserActivation() {
        if (!isset($_GET['id'])) {
            die('User ID not specified.');
        }
        $userId = $_GET['id'];

        if ($userId == $_SESSION['user_id']) {
            header('Location: ?controller=admin&action=users&error=cannot_deactivate_self');
            exit();
        }

        $userToToggle = $this->userModel->findById($userId);
        if ($userToToggle && $userToToggle['Role'] !== 'Customer') {
            header('Location: ?controller=admin&action=users&error=only_customers_can_be_deactivated');
            exit();
        }

        if ($this->userModel->toggleActivationStatus($userId)) {
            header('Location: ?controller=admin&action=users&status=user_status_updated');
        } else {
            header('Location: ?controller=admin&action=users&error=update_failed');
        }
        exit();
    }
    
    public function viewUserBookings() {
        if (!isset($_GET['id'])) {
            die('User ID not specified.');
        }
        $userId = $_GET['id'];

        $user = $this->userModel->findById($userId);
        if (!$user) {
            die('User not found.');
        }

        $bookings = Booking::findByCustomerId($userId);
        // This part is now handled by getUserBookings
        include __DIR__ . '/../Views/admin/view_user_bookings.php';
    }

    public function getUserBookings() {
        if (!isset($_GET['id'])) {
            die('User ID not specified.');
        }
        $userId = $_GET['id'];
        $user = $this->userModel->findById($userId);
        if (!$user) {
            die('User not found.');
        }
        $bookings = Booking::findByCustomerId($userId);

        // This view will be loaded into the modal
        include __DIR__ . '/../Views/admin/view_user_bookings.php';
    }

    public function facilities() {
        $facilities = Facility::findAllWithResort();
        $resorts = Resort::findAll(); // Fetch all resorts for the dropdown
        include __DIR__ . '/../Views/admin/facilities/index.php';
    }

    public function addFacility() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Phase 6: Enhanced validation
            $validation = ValidationHelper::validateFacilityData($_POST);

            if (!$validation['valid']) {
                $_SESSION['error_message'] = implode('<br>', array_merge(...array_values($validation['errors'])));
                header('Location: ?controller=admin&action=management&error=validation_failed');
                exit;
            }

            $validatedData = $validation['data'];
            $facility = new Facility();
            $facility->name = $validatedData['name'];
            $facility->rate = $validatedData['rate'];
            $facility->shortDescription = $validatedData['short_description'];
            $facility->fullDescription = $validatedData['description'];
            $facility->resortId = $validatedData['resort_id'];

            $facilityId = Facility::create($facility);

            if ($facilityId) {
                $photoURLs = $this->handlePhotoUpload('photos', 'facilities');
                if (!empty($photoURLs)) {
                    Facility::setMainPhoto($facilityId, $photoURLs[0]);
                    foreach ($photoURLs as $url) {
                        Facility::addPhoto($facilityId, $url);
                    }
                }
                $_SESSION['success_message'] = "Facility added successfully.";
                header('Location: ?controller=admin&action=management&active_resort_id=' . $validatedData['resort_id']);
            } else {
                $_SESSION['error_message'] = "Failed to add facility.";
                header('Location: ?controller=admin&action=management&active_resort_id=' . $validatedData['resort_id']);
            }
            exit();
        }
    }

    public function editFacility() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Phase 6: Enhanced validation
            $validation = ValidationHelper::validateFacilityData($_POST);

            if (!$validation['valid']) {
                $_SESSION['error_message'] = implode('<br>', array_merge(...array_values($validation['errors'])));
                header('Location: ?controller=admin&action=management&error=validation_failed');
                exit;
            }

            $validatedData = $validation['data'];
            $facilityId = filter_input(INPUT_POST, 'facilityId', FILTER_VALIDATE_INT);
            if (!$facilityId) { die('Invalid Facility ID.'); }

            // First, get the existing facility data from the database
            $facility = Facility::findById($facilityId);
            if (!$facility) {
                die('Facility not found.');
            }

            // Now, update the object with the validated data from the form
            $facility->name = $validatedData['name'];
            $facility->rate = $validatedData['rate'];
            $facility->shortDescription = $validatedData['short_description'];
            $facility->fullDescription = $validatedData['full_description']; // Corrected from 'description'
            $facility->resortId = $validatedData['resort_id'];

            // Handle new photo uploads
            $newPhotoURLs = $this->handlePhotoUpload('photos', 'facilities');
            if (!empty($newPhotoURLs)) {
                foreach ($newPhotoURLs as $url) {
                    Facility::addPhoto($facilityId, $url);
                }
            }

            // Always retrieve resortId for the redirect, whether from the form or the database
            $resortId = $validatedData['resort_id'] ?? Facility::findById($facilityId)->resortId;

            if (Facility::update($facility)) {
                $_SESSION['success_message'] = "Facility updated successfully.";
                header('Location: ?controller=admin&action=management&active_resort_id=' . $resortId);
            } else {
                $_SESSION['error_message'] = "Failed to update facility.";
                header('Location: ?controller=admin&action=management&active_resort_id=' . $resortId);
            }
            exit();
        }
    }
    
    public function getFacilityJson() {
        header('Content-Type: application/json');
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Facility ID not specified.']);
            exit();
        }
        $facilityId = $_GET['id'];
        $facility = Facility::findById($facilityId);

        if ($facility) {
            echo json_encode($facility);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Facility not found.']);
        }
        exit();
    }
    
    public function deleteFacility() {
        if (!isset($_GET['id'])) {
            $_SESSION['error_message'] = "Facility ID not specified.";
            header('Location: ?controller=admin&action=management');
            exit();
        }
        $facilityId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

        $facility_data = Facility::findById($facilityId);
        // Check for dependencies: existing bookings
        $bookings = BookingFacilities::findByFacilityId($facilityId);
        if (!empty($bookings)) {
            $_SESSION['error_message'] = "Cannot delete facility. It is associated with one or more bookings.";
            header('Location: ?controller=admin&action=management&active_resort_id=' . $facility_data->resortId);
            exit();
        }

        if (Facility::delete($facilityId)) {
            $_SESSION['success_message'] = "Facility deleted successfully.";
        } else {
            $_SESSION['error_message'] = "Failed to delete facility.";
        }
        header('Location: ?controller=admin&action=management&active_resort_id=' . $facility_data->resortId);
        exit();
    }
   public function uploadPhoto() {
       if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_GET['id'])) {
           die('Invalid request.');
       }
       $facilityId = $_GET['id'];

       if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
           $uploadDir = __DIR__ . '/../../public/uploads/facilities/';
           if (!is_dir($uploadDir)) {
               mkdir($uploadDir, 0777, true);
           }
           $fileName = uniqid() . '-' . basename($_FILES['photo']['name']);
           $targetPath = $uploadDir . $fileName;

           if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetPath)) {
               $photoURL = '/public/uploads/facilities/' . $fileName;
               Facility::addPhoto($facilityId, $photoURL);
               header('Location: ?controller=admin&action=editFacility&id=' . $facilityId . '&status=photo_uploaded');
           } else {
               header('Location: ?controller=admin&action=editFacility&id=' . $facilityId . '&error=upload_failed');
           }
       } else {
           header('Location: ?controller=admin&action=editFacility&id=' . $facilityId . '&error=no_file');
       }
       exit();
   }

   public function deletePhoto() {
       if (!isset($_GET['id']) || !isset($_GET['photoId'])) {
           die('Required parameters not specified.');
       }
       $facilityId = $_GET['id'];
       $photoId = $_GET['photoId'];

       $photo = Facility::findPhotoById($photoId);
       if ($photo) {
           $filePath = __DIR__ . '/../../' . ltrim($photo['PhotoURL'], '/');
           if (file_exists($filePath)) {
               unlink($filePath);
           }
           Facility::deletePhoto($photoId);
           header('Location: ?controller=admin&action=editFacility&id=' . $facilityId . '&status=photo_deleted');
       } else {
           header('Location: ?controller=admin&action=editFacility&id=' . $facilityId . '&error=photo_not_found');
       }
       exit();
   }

   public function setMainPhoto() {
       if (!isset($_GET['id']) || !isset($_GET['photoId'])) {
           die('Required parameters not specified.');
       }
       $facilityId = $_GET['id'];
       $photoId = $_GET['photoId'];

       $photo = Facility::findPhotoById($photoId);
       $facility = Facility::findById($facilityId);

       if ($photo && $facility) {
           $facility->mainPhotoURL = $photo['PhotoURL'];
           Facility::update($facility);
           header('Location: ?controller=admin&action=editFacility&id=' . $facilityId . '&status=main_photo_set');
       } else {
           header('Location: ?controller=admin&action=editFacility&id=' . $facilityId . '&error=set_main_failed');
       }
       exit();
   }

   public function previewFacilities() {
        if ($_SESSION['role'] !== 'Admin') {
            http_response_code(403);
            require_once __DIR__ . '/../Views/errors/403.php';
            exit();
        }

        // Check if admin has preview customer permission
        if (!User::hasAdminPermission($_SESSION['user_id'], 'preview_customer')) {
            http_response_code(403);
            require_once __DIR__ . '/../Views/errors/403.php';
            exit();
        }
       // This action is for admins/staff to see the customer view
       // We can reuse the logic from the UserController's dashboard
       $resorts = Resort::findAllWithStats();
       include __DIR__ . '/../Views/admin/facilities/preview.php';
   }
    public function management() {
        if ($_SESSION['role'] !== 'Admin') {
            http_response_code(403);
            require_once __DIR__ . '/../Views/errors/403.php';
            exit();
        }

        // Only Main Admin and Operations Admin can access resort management
        if (!User::hasAdminPermission($_SESSION['user_id'], 'resort_management')) {
            http_response_code(403);
            require_once __DIR__ . '/../Views/errors/403.php';
            exit();
        }

        $resortsWithFacilities = Resort::findAllWithFacilities();
        include __DIR__ . '/../Views/admin/management/index.php';
    }

    // Resort Management Logic (Moved from ResortController)
    public function storeResort() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            $response = ['success' => false];

            $validation = ValidationHelper::validateResortData($_POST);

            if (!$validation['valid']) {
                $response['message'] = implode(', ', array_merge(...array_values($validation['errors'])));
                echo json_encode($response);
                exit;
            }

            $validatedData = $validation['data'];
            $resort = new Resort();
            $resort->name = $validatedData['name'];
            $resort->address = $validatedData['address'];
            $resort->contactPerson = $validatedData['contactPerson'];
            $resort->shortDescription = $validatedData['shortDescription'];
            $resort->fullDescription = $validatedData['fullDescription'];
            $resort->googleMapsLink = filter_input(INPUT_POST, 'googleMapsLink', FILTER_SANITIZE_URL) ?? null;
            
            $resortId = Resort::create($resort);

            if ($resortId) {
                $photoURLs = $this->handlePhotoUpload('photos');
                if (!empty($photoURLs)) {
                    Resort::setMainPhoto($resortId, $photoURLs[0]);
                    foreach ($photoURLs as $url) {
                        Resort::addPhoto($resortId, $url);
                    }
                }
                $response['success'] = true;
                $response['message'] = 'Resort added successfully!';
                $response['resortId'] = $resortId;
            } else {
                $response['message'] = 'Failed to add resort.';
            }
            echo json_encode($response);
            exit();
        }
    }

    public function updateResort() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $resortId = filter_input(INPUT_POST, 'resortId', FILTER_VALIDATE_INT);
            if (!$resortId) { die('Invalid Resort ID.'); }

            $resort = Resort::findById($resortId);
            if (!$resort) { die('Resort not found.'); }

            $validation = ValidationHelper::validateResortData($_POST);

            if (!$validation['valid']) {
                $_SESSION['error_message'] = implode('<br>', array_merge(...array_values($validation['errors'])));
                header('Location: ?controller=admin&action=management&error=validation_failed');
                exit;
            }

            $validatedData = $validation['data'];
            $resort->name = $validatedData['name'];
            $resort->address = $validatedData['address'];
            $resort->contactPerson = $validatedData['contactPerson'];
            $resort->shortDescription = $validatedData['shortDescription'];
            $resort->fullDescription = $validatedData['fullDescription'];
            $resort->googleMapsLink = filter_input(INPUT_POST, 'googleMapsLink', FILTER_SANITIZE_URL) ?? $resort->googleMapsLink;
            
            // Handle new photo uploads
            $newPhotoURLs = $this->handlePhotoUpload('photos', 'resorts');
            if (!empty($newPhotoURLs)) {
                foreach ($newPhotoURLs as $url) {
                    Resort::addPhoto($resortId, $url);
                }
            }
            
            if (Resort::update($resort)) {
                $_SESSION['success_message'] = "Resort updated successfully.";
                header('Location: ?controller=admin&action=management&active_resort_id=' . $resortId);
            } else {
                $_SESSION['error_message'] = "Failed to update resort.";
                header('Location: ?controller=admin&action=management&active_resort_id=' . $resortId);
            }
            exit();
        }
    }

    public function destroyResort() {
        $resortId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$resortId) {
            $_SESSION['error_message'] = "Invalid Resort ID provided.";
            header('Location: ?controller=admin&action=management');
            exit();
        }

        // Check for dependent facilities before deleting
        $facilities = Facility::findByResortId($resortId);
        if (!empty($facilities)) {
            $_SESSION['error_message'] = "Cannot delete resort. It has associated facilities.";
            header('Location: ?controller=admin&action=management&active_resort_id=' . $resortId);
            exit();
        }

        if (Resort::delete($resortId)) {
            $_SESSION['success_message'] = "Resort deleted successfully.";
        } else {
            $_SESSION['error_message'] = "Failed to delete resort.";
        }
        
        // Redirect back to the main management page, which will now show the session message
        header('Location: ?controller=admin&action=management');
        exit();
    }

    public function getResortJson() {
        header('Content-Type: application/json');
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Resort ID not specified.']);
            exit();
        }
        $resortId = $_GET['id'];
        $resort = Resort::findById($resortId);

        if ($resort) {
            // The findById method already populates the photos property
            echo json_encode($resort);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Resort not found.']);
        }
        exit();
    }


    public function setResortMainPhoto() {
        $resortId = filter_input(INPUT_GET, 'resortId', FILTER_VALIDATE_INT);
        $photoId = filter_input(INPUT_GET, 'photoId', FILTER_VALIDATE_INT);

        if (!$resortId || !$photoId) { die('Invalid parameters.'); }
        
        $photo = Resort::findPhotoById($photoId);
        if ($photo && $photo['ResortID'] == $resortId) {
            Resort::setMainPhoto($resortId, $photo['PhotoURL']);
            header('Location: ?controller=admin&action=management&status=main_photo_set');
        } else {
            header('Location: ?controller=admin&action=management&error=set_main_failed');
        }
        exit();
    }

    public function deleteResortPhoto() {
        $resortId = filter_input(INPUT_GET, 'resortId', FILTER_VALIDATE_INT);
        $photoId = filter_input(INPUT_GET, 'photoId', FILTER_VALIDATE_INT);

        if (!$resortId || !$photoId) { die('Invalid parameters.'); }

        $photo = Resort::findPhotoById($photoId);
        if ($photo && $photo['ResortID'] == $resortId) {
            // Physical file deletion
            $filePath = __DIR__ . '/../../' . ltrim($photo['PhotoURL'], '/');
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            // DB record deletion
            Resort::deletePhoto($photoId);

            // If the deleted photo was the main photo, pick a new main one
            $resort = Resort::findById($resortId);
            if ($resort->mainPhotoURL === $photo['PhotoURL']) {
                $remainingPhotos = Resort::getPhotos($resortId);
                $newMainPhoto = !empty($remainingPhotos) ? $remainingPhotos[0]['PhotoURL'] : null;
                Resort::setMainPhoto($resortId, $newMainPhoto);
            }

            header('Location: ?controller=admin&action=management&status=photo_deleted');
        } else {
            header('Location: ?controller=admin&action=management&error=delete_failed');
        }
        exit();
    }


    public function setFacilityMainPhoto() {
        $facilityId = filter_input(INPUT_GET, 'facilityId', FILTER_VALIDATE_INT);
        $photoId = filter_input(INPUT_GET, 'photoId', FILTER_VALIDATE_INT);

        if (!$facilityId || !$photoId) { die('Invalid parameters.'); }
        
        $photo = Facility::findPhotoById($photoId);
        if ($photo && $photo['FacilityID'] == $facilityId) {
            Facility::setMainPhoto($facilityId, $photo['PhotoURL']);
            header('Location: ?controller=admin&action=management&status=main_photo_set');
        } else {
            header('Location: ?controller=admin&action=management&error=set_main_failed');
        }
        exit();
    }

    public function deleteFacilityPhoto() {
        $facilityId = filter_input(INPUT_GET, 'facilityId', FILTER_VALIDATE_INT);
        $photoId = filter_input(INPUT_GET, 'photoId', FILTER_VALIDATE_INT);

        if (!$facilityId || !$photoId) { die('Invalid parameters.'); }

        $photo = Facility::findPhotoById($photoId);
        if ($photo && $photo['FacilityID'] == $facilityId) {
            $filePath = __DIR__ . '/../../' . ltrim($photo['PhotoURL'], '/');
            if (file_exists($filePath)) { unlink($filePath); }
            
            Facility::deletePhoto($photoId);

            $facility = Facility::findById($facilityId);
            if ($facility->mainPhotoURL === $photo['PhotoURL']) {
                $remainingPhotos = Facility::getPhotos($facilityId);
                $newMainPhoto = !empty($remainingPhotos) ? $remainingPhotos[0]['PhotoURL'] : null;
                Facility::setMainPhoto($facilityId, $newMainPhoto);
            }
            header('Location: ?controller=admin&action=management&status=photo_deleted');
        } else {
            header('Location: ?controller=admin&action=management&error=delete_failed');
        }
        exit();
    }

    private function handlePhotoUpload($fileInputName, $type = 'resorts') {
        $uploadedPaths = [];
        if (isset($_FILES[$fileInputName])) {
            $files = $_FILES[$fileInputName];
            $uploadDir = __DIR__ . '/../../public/uploads/' . $type . '/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            foreach ($files['tmp_name'] as $key => $tmpName) {
                if ($files['error'][$key] === UPLOAD_ERR_OK) {
                    $fileName = uniqid() . '-' . basename($files['name'][$key]);
                    $targetPath = $uploadDir . $fileName;

                    if (move_uploaded_file($tmpName, $targetPath)) {
                        $uploadedPaths[] = '/public/uploads/' . $type . '/' . $fileName;
                    }
                }
            }
        }
        return $uploadedPaths;
    }


    public function getPaymentMethodsJson() {
        header('Content-Type: application/json');
        $resortId = filter_input(INPUT_GET, 'resort_id', FILTER_VALIDATE_INT);
        if (!$resortId) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid Resort ID']);
            exit();
        }

        $methods = ResortPaymentMethods::findByResortId($resortId, false);
        // Manually map to avoid issues with inconsistent property names if any exist
        $mappedMethods = array_map(function($method) {
            return [
                'PaymentMethodID' => $method->PaymentMethodID,
                'ResortID' => $method->ResortID,
                'MethodType' => $method->MethodType,
                'AccountDetails' => $method->AccountDetails,
                'IsActive' => $method->IsActive
            ];
        }, $methods);
        echo json_encode($mappedMethods);
        exit();
    }

    public function addPaymentMethod() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $resortId = filter_input(INPUT_POST, 'resort_id', FILTER_VALIDATE_INT);
            $methodType = filter_input(INPUT_POST, 'method_type', FILTER_UNSAFE_RAW);
            $accountName = filter_input(INPUT_POST, 'account_name', FILTER_SANITIZE_STRING);
            $accountNumber = filter_input(INPUT_POST, 'account_number', FILTER_SANITIZE_STRING);
            
            $validationErrors = [];
            if (!$resortId) $validationErrors[] = "Invalid resort ID.";
            if (!$methodType) $validationErrors[] = "Payment method type is required.";
            if (!$accountName) $validationErrors[] = "Account name is required.";
            if (!$accountNumber) $validationErrors[] = "Account number is required.";

            // Handle QR Code Upload
            $qrCodeUrl = null;
            $fileValidation = ValidationHelper::validateQrCodeFile($_FILES['qr_code'] ?? null);

            if (!$fileValidation['valid']) {
                // If validation fails, add all specific errors to the main validationErrors array
                foreach ($fileValidation['errors'] as $error) {
                    $validationErrors[] = $error;
                }
            } else {
                // If validation is successful, proceed with file upload
                $uploadDir = __DIR__ . '/../../public/uploads/qr_codes/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $fileName = 'qr_' . $resortId . '_' . uniqid() . '.' . pathinfo($_FILES['qr_code']['name'], PATHINFO_EXTENSION);
                $targetFile = $uploadDir . $fileName;

                if (move_uploaded_file($_FILES['qr_code']['tmp_name'], $targetFile)) {
                    $qrCodeUrl = 'public/uploads/qr_codes/' . $fileName;
                } else {
                    $validationErrors[] = "An unexpected error occurred while saving the QR code image.";
                }
            }

            if (!empty($validationErrors)) {
                $_SESSION['error_message'] = implode('<br>', $validationErrors);
                header('Location: ' . $_SERVER['HTTP_REFERER']);
                exit();
            }

            $paymentMethod = new ResortPaymentMethods();
            $paymentMethod->resortId = $resortId;
            $paymentMethod->methodType = $methodType;
            $paymentMethod->accountName = $accountName;
            $paymentMethod->accountNumber = $accountNumber;
            $paymentMethod->qrCodeUrl = $qrCodeUrl;
            $paymentMethod->isDefault = false; // Set default logic if needed
            $paymentMethod->isActive = true;

            if (ResortPaymentMethods::create($paymentMethod)) {
                $_SESSION['success_message'] = "Payment method added successfully.";
            } else {
                $_SESSION['error_message'] = "Failed to add payment method. A method with the same type might already exist for this resort.";
            }
            
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit();
        }
    }

    public function deletePaymentMethod() {
        $methodId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if ($methodId && ResortPaymentMethods::delete($methodId)) {
            $_SESSION['success_message'] = "Payment method deleted successfully.";
        } else {
            $_SESSION['error_message'] = "Failed to delete payment method.";
        }
        // Redirect back to the referrer (e.g., pricing management page)
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '?controller=admin&action=pricingManagement'));
        exit();
    }
    public function blockResortAvailability() {
        if ($_SESSION['role'] !== 'Admin') {
            http_response_code(403);
            exit('Forbidden');
        }
        $resortId = filter_input(INPUT_POST, 'resortId', FILTER_VALIDATE_INT);
        $blockDate = filter_input(INPUT_POST, 'blockDate', FILTER_UNSAFE_RAW);
        $reason = trim(filter_input(INPUT_POST, 'reason', FILTER_UNSAFE_RAW));

        if (!$resortId || !$blockDate || empty($reason)) {
            $_SESSION['error_message'] = "Block date and reason are required.";
            // Redirect back to the advanced blocking page specifically
            header('Location: ?controller=admin&action=advancedBlocking&resort_id=' . $resortId);
            exit;
        }

        if (BlockedResortAvailability::create($resortId, $blockDate, $reason)) {
            $_SESSION['success_message'] = "Successfully blocked the date: " . htmlspecialchars($blockDate);
        } else {
            $_SESSION['error_message'] = "Failed to block the date. It may already be blocked.";
        }
        header('Location: ?controller=admin&action=advancedBlocking&resort_id=' . $resortId);
        exit;
    }

    public function deleteResortAvailabilityBlock() {
        if ($_SESSION['role'] !== 'Admin') {
            http_response_code(403);
            exit('Forbidden');
        }
        $blockId = filter_input(INPUT_GET, 'block_id', FILTER_VALIDATE_INT);
        if ($blockId) {
            BlockedResortAvailability::delete($blockId);
        }
        // Return a JSON response for AJAX request
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }

    public function getResortScheduleJson() {
        header('Content-Type: application/json');
        try {
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Resort ID not specified.']);
                exit();
            }
            $resortId = $_GET['id'];
            $blocks = BlockedResortAvailability::findByResortId($resortId);
            echo json_encode($blocks);
        } catch (Exception $e) {
            http_response_code(500); // Internal Server Error
            // Log the actual error to the server's error log for debugging
            error_log('Error in getResortScheduleJson: ' . $e->getMessage());
            // Send a generic error message to the client
            echo json_encode(['error' => 'A server error occurred while fetching the schedule.']);
        }
        exit();
    }

    public function blockFacilityAvailability() {
        if ($_SESSION['role'] !== 'Admin') {
            http_response_code(403);
            exit('Forbidden');
        }
        $facilityId = filter_input(INPUT_POST, 'facilityId', FILTER_VALIDATE_INT);
        $blockDate = filter_input(INPUT_POST, 'blockDate', FILTER_UNSAFE_RAW);
        $reason = trim(filter_input(INPUT_POST, 'reason', FILTER_UNSAFE_RAW));
        $resortId = filter_input(INPUT_POST, 'resort_id', FILTER_VALIDATE_INT);

        if (!$facilityId || !$blockDate || empty($reason)) {
            $_SESSION['error_message'] = "Facility, block date, and reason are required.";
            $tab = filter_input(INPUT_POST, 'tab', FILTER_UNSAFE_RAW);
            header('Location: ?controller=admin&action=advancedBlocking&resort_id=' . $resortId . '&tab=' . $tab);
            exit;
        }

        if (BlockedFacilityAvailability::create($facilityId, $blockDate, $reason)) {
            $_SESSION['success_message'] = "Successfully blocked the date for the facility.";
        } else {
            $_SESSION['error_message'] = "Failed to block the date for the facility. It may already be blocked.";
        }
        
        $tab = filter_input(INPUT_POST, 'tab', FILTER_UNSAFE_RAW);
        header('Location: ?controller=admin&action=advancedBlocking&resort_id=' . $resortId . '&tab=' . $tab);
        exit;
    }

    public function deleteFacilityAvailabilityBlock() {
        if ($_SESSION['role'] !== 'Admin') {
            http_response_code(403);
            exit('Forbidden');
        }
        $blockId = filter_input(INPUT_GET, 'block_id', FILTER_VALIDATE_INT);
        if ($blockId) {
            BlockedFacilityAvailability::delete($blockId);
        }
        // Return a JSON response for AJAX request
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }

    public function getFacilityScheduleJson() {
        header('Content-Type: application/json');
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Facility ID not specified.']);
            exit();
        }
        $facilityId = $_GET['id'];
        $blocks = BlockedFacilityAvailability::findByFacilityId($facilityId);
        echo json_encode($blocks);
        exit();
    }

    /**
     * PHASE 5: UNIFIED BOOKING/PAYMENT MANAGEMENT
     */

    /**
     * Show unified booking and payment management interface
     */
    public function unifiedBookingManagement() {
        if ($_SESSION['role'] !== 'Admin') {
            http_response_code(403);
            require_once __DIR__ . '/../Views/errors/403.php';
            exit();
        }

        // Only Main Admin and Booking Admin can access booking management
        if (!User::hasAdminPermission($_SESSION['user_id'], 'booking_management')) {
            http_response_code(403);
            require_once __DIR__ . '/../Views/errors/403.php';
            exit();
        }

        $filters = [
            'resort_id' => filter_input(INPUT_GET, 'resort_id', FILTER_VALIDATE_INT),
            'status' => filter_input(INPUT_GET, 'status', FILTER_UNSAFE_RAW),
            'customer_id' => filter_input(INPUT_GET, 'customer_id', FILTER_VALIDATE_INT),
            'payment_status' => filter_input(INPUT_GET, 'payment_status', FILTER_UNSAFE_RAW),
            'month' => filter_input(INPUT_GET, 'month', FILTER_VALIDATE_INT),
            'year' => filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT)
        ];

        $resorts = Resort::findAll();
        $customers = User::findByRole('Customer');

        // Get bookings with payment information
        $bookings = Booking::getBookingsWithPaymentDetails($filters);

        // Get pending payment count for notification - filtered by resort
        $pendingPaymentCount = Payment::getPendingPaymentCount($filters['resort_id']);

        // Get active booking count (excluding completed) for header
        $activeBookingCount = Booking::getActiveBookingsCountForAdmin($filters['resort_id']);

        // For the view: get total non-completed bookings count for header (separate from filtered $bookings)
        $totalNonCompletedBookings = Booking::getActiveBookingsCountForAdmin($filters['resort_id']);

        require_once __DIR__ . '/../Views/admin/unified_booking_management.php';
    }

    /**
     * Update booking with payment information
     */
    public function updateBookingPayment() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?controller=admin&action=unifiedBookingManagement');
            exit();
        }

        $bookingId = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);
        $bookingStatus = filter_input(INPUT_POST, 'booking_status', FILTER_UNSAFE_RAW);
        $paymentAmount = filter_input(INPUT_POST, 'payment_amount', FILTER_VALIDATE_FLOAT);
        $paymentMethod = filter_input(INPUT_POST, 'payment_method', FILTER_UNSAFE_RAW);
        $paymentStatus = filter_input(INPUT_POST, 'payment_status', FILTER_UNSAFE_RAW);

        if (!$bookingId) {
            $_SESSION['error_message'] = "Invalid booking ID.";
            header('Location: ?controller=admin&action=unifiedBookingManagement');
            exit();
        }

        // Update booking status if provided
        if ($bookingStatus) {
            Booking::updateStatus($bookingId, $bookingStatus);
        }

        // Add payment if provided
        if ($paymentAmount && $paymentMethod && $paymentStatus) {
            $payment = new Payment();
            $payment->bookingId = $bookingId;
            $payment->amount = $paymentAmount;
            $payment->paymentMethod = $paymentMethod;
            $payment->status = $paymentStatus;
            
            if (Payment::create($payment)) {
                // Update booking remaining balance
                $booking = Booking::findById($bookingId);
                if ($booking && $booking->totalAmount > 0) {
                    $totalPaid = Payment::getTotalPaidAmount($bookingId);
                    $remainingBalance = max(0, $booking->totalAmount - $totalPaid);
                    Booking::updateRemainingBalance($bookingId, $remainingBalance);
                }
                $_SESSION['success_message'] = "Payment added successfully!";
            } else {
                $_SESSION['error_message'] = "Failed to add payment.";
            }
        }

        header('Location: ?controller=admin&action=unifiedBookingManagement');
        exit();
    }

    /**
     * PRICING MANAGEMENT SYSTEM
     */

    /**
     * Show pricing management interface
     */
    public function pricingManagement() {
        if ($_SESSION['role'] !== 'Admin') {
            http_response_code(403);
            require_once __DIR__ . '/../Views/errors/403.php';
            exit();
        }

        // Only Main Admin and Operations Admin can access pricing management
        if (!User::hasAdminPermission($_SESSION['user_id'], 'pricing_management')) {
            http_response_code(403);
            require_once __DIR__ . '/../Views/errors/403.php';
            exit();
        }

        $resortId = filter_input(INPUT_GET, 'resort_id', FILTER_VALIDATE_INT);
        $resorts = Resort::findAll();
        
        $resortPricing = [];
        $facilityPricing = [];
        $paymentMethods = [];
        
        if ($resortId) {
            $resortPricing = ResortTimeframePricing::findByResortId($resortId);
            $facilityPricing = Facility::findByResortId($resortId);
            $paymentMethods = ResortPaymentMethods::findByResortId($resortId, false);
        }
        
        require_once __DIR__ . '/../Views/admin/pricing_management.php';
    }

    /**
     * Update resort timeframe pricing
     */
    public function updateResortPricing() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?controller=admin&action=pricingManagement');
            exit();
        }

        $resortId = filter_input(INPUT_POST, 'resort_id', FILTER_VALIDATE_INT);
        $timeframeType = filter_input(INPUT_POST, 'timeframe_type', FILTER_UNSAFE_RAW);
        $basePrice = filter_input(INPUT_POST, 'base_price', FILTER_VALIDATE_FLOAT);
        $weekendSurcharge = filter_input(INPUT_POST, 'weekend_surcharge', FILTER_VALIDATE_FLOAT);
        $holidaySurcharge = filter_input(INPUT_POST, 'holiday_surcharge', FILTER_VALIDATE_FLOAT);

        if (!$resortId || !$timeframeType || !$basePrice) {
            $_SESSION['error_message'] = "Invalid pricing data provided.";
            header('Location: ?controller=admin&action=pricingManagement&resort_id=' . $resortId);
            exit();
        }

        // Check if pricing already exists
        $existingPricing = ResortTimeframePricing::findByResortAndTimeframe($resortId, $timeframeType);
        
        if ($existingPricing) {
            // Update existing pricing
            $existingPricing->basePrice = $basePrice;
            $existingPricing->weekendSurcharge = $weekendSurcharge ?? 0;
            $existingPricing->holidaySurcharge = $holidaySurcharge ?? 0;
            
            if (ResortTimeframePricing::update($existingPricing)) {
                $_SESSION['success_message'] = "Pricing updated successfully!";
            } else {
                $_SESSION['error_message'] = "Failed to update pricing.";
            }
        } else {
            // Create new pricing
            $pricing = new ResortTimeframePricing();
            $pricing->resortId = $resortId;
            $pricing->timeframeType = $timeframeType;
            $pricing->basePrice = $basePrice;
            $pricing->weekendSurcharge = $weekendSurcharge ?? 0;
            $pricing->holidaySurcharge = $holidaySurcharge ?? 0;
            
            if (ResortTimeframePricing::create($pricing)) {
                $_SESSION['success_message'] = "Pricing created successfully!";
            } else {
                $_SESSION['error_message'] = "Failed to create pricing.";
            }
        }

        header('Location: ?controller=admin&action=pricingManagement&resort_id=' . $resortId);
        exit();
    }

    /**
     * Update facility fixed pricing
     */
    public function updateFacilityPricing() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?controller=admin&action=pricingManagement');
            exit();
        }

        $facilityId = filter_input(INPUT_POST, 'facility_id', FILTER_VALIDATE_INT);
        $rate = filter_input(INPUT_POST, 'rate', FILTER_VALIDATE_FLOAT);

        if (!$facilityId || !$rate) {
            $_SESSION['error_message'] = "Invalid facility pricing data.";
            header('Location: ?controller=admin&action=pricingManagement');
            exit();
        }

        if (Facility::updateRate($facilityId, $rate)) {
            $_SESSION['success_message'] = "Facility pricing updated successfully!";
        } else {
            $_SESSION['error_message'] = "Failed to update facility pricing.";
        }

        // Get resort ID for redirect
        $facility = Facility::findById($facilityId);
        $resortId = $facility ? $facility->resortId : null;

        header('Location: ?controller=admin&action=pricingManagement&resort_id=' . $resortId);
        exit();
    }

    /**
     * ADVANCED BLOCKING SYSTEM
     */

    /**
     * Show advanced blocking management
     */
    public function advancedBlocking() {
        if ($_SESSION['role'] !== 'Admin') {
            http_response_code(403);
            require_once __DIR__ . '/../Views/errors/403.php';
            exit();
        }

        // Only Main Admin and Operations Admin can access advanced blocking
        if (!User::hasAdminPermission($_SESSION['user_id'], 'advanced_blocking')) {
            http_response_code(403);
            require_once __DIR__ . '/../Views/errors/403.php';
            exit();
        }

        $resortId = filter_input(INPUT_GET, 'resort_id', FILTER_VALIDATE_INT);
        $resorts = Resort::findAll();

        // Phase 6: Get holidays dynamically for the view
        require_once __DIR__ . '/../Helpers/HolidayHelper.php';
        $holidays = HolidayHelper::getHolidays();
        
        // Phase 6: Automatically clear old block dates for easier management
        BlockedResortAvailability::clearOldBlocks();
        BlockedFacilityAvailability::clearOldBlocks();

        require_once __DIR__ . '/../Views/admin/advanced_blocking.php';
    }

    /**
     * Apply preset blocking (weekends, holidays)
     */
    public function applyPresetBlocking() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?controller=admin&action=advancedBlocking');
            exit();
        }

        $resortId = filter_input(INPUT_POST, 'resort_id', FILTER_VALIDATE_INT);
        $presetType = filter_input(INPUT_POST, 'preset_type', FILTER_UNSAFE_RAW);
        $reason = trim(filter_input(INPUT_POST, 'reason', FILTER_UNSAFE_RAW));
        $blockedCount = 0;
        $skippedCount = 0;

        if (!$resortId || !$presetType || empty($reason)) {
            $_SESSION['error_message'] = "Resort, preset type, and reason are required.";
            header('Location: ?controller=admin&action=advancedBlocking&resort_id=' . $resortId);
            exit();
        }

        if ($presetType === 'philippine_holidays') {
            $holidaysToBlock = $_POST['holidays'] ?? [];
            if (empty($holidaysToBlock)) {
                $_SESSION['error_message'] = "Please select at least one holiday to block.";
                header('Location: ?controller=admin&action=advancedBlocking&resort_id=' . $resortId);
                exit();
            }
            
            require_once __DIR__ . '/../Helpers/HolidayHelper.php';
            $currentYear = date('Y');
            
            foreach ($holidaysToBlock as $monthDay) {
                // Validate that the provided holiday is a legitimate holiday
                if (HolidayHelper::isHoliday($currentYear . '-' . $monthDay)) {
                    $dateStr = $currentYear . '-' . $monthDay;
                    if (BlockedResortAvailability::create($resortId, $dateStr, $reason)) {
                        $blockedCount++;
                    } else {
                        $skippedCount++;
                    }
                }
            }
        } else {
            $startDate = filter_input(INPUT_POST, 'start_date', FILTER_UNSAFE_RAW);
            $endDate = filter_input(INPUT_POST, 'end_date', FILTER_UNSAFE_RAW);

            if (!$startDate || !$endDate) {
                $_SESSION['error_message'] = "Start date and end date are required for this preset.";
                header('Location: ?controller=admin&action=advancedBlocking&resort_id=' . $resortId);
                exit();
            }

            $currentDate = new DateTime($startDate);
            $lastDate = new DateTime($endDate);

            while ($currentDate <= $lastDate) {
                $shouldBlock = false;
                $dateStr = $currentDate->format('Y-m-d');
                $dayOfWeek = $currentDate->format('w'); // 0 = Sunday, 6 = Saturday

                switch ($presetType) {
                    case 'weekends':
                        $shouldBlock = ($dayOfWeek == 0 || $dayOfWeek == 6); // Sunday or Saturday
                        break;
                    case 'all_dates':
                        $shouldBlock = true;
                        break;
                }

                if ($shouldBlock) {
                    if (BlockedResortAvailability::create($resortId, $dateStr, $reason)) {
                        $blockedCount++;
                    } else {
                        $skippedCount++;
                    }
                }
                $currentDate->modify('+1 day');
            }
        }

        $message = "Blocked $blockedCount dates successfully.";
        if ($skippedCount > 0) {
            $message .= " Skipped $skippedCount dates that were already blocked.";
        }
        $_SESSION['success_message'] = $message;
        header('Location: ?controller=admin&action=advancedBlocking&resort_id=' . $resortId);
        exit();
    }

    /**
     * Deblock all dates for a specific resort
     */
    public function deblockAll() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?controller=admin&action=advancedBlocking');
            exit();
        }

        $resortId = filter_input(INPUT_POST, 'resort_id', FILTER_VALIDATE_INT);
        if (!$resortId) {
            $_SESSION['error_message'] = "Invalid Resort ID.";
            header('Location: ?controller=admin&action=advancedBlocking');
            exit();
        }

        $deletedCount = BlockedResortAvailability::deleteAllForResort($resortId);

        if ($deletedCount > 0) {
            $_SESSION['success_message'] = "Successfully removed all $deletedCount blocks for the resort.";
        } else {
            $_SESSION['success_message'] = "No blocks were found to remove for this resort.";
        }
        header('Location: ?controller=admin&action=advancedBlocking&resort_id=' . $resortId);
        exit();
    }

    /**
     * Deblock dates within a specific range for a resort
     */
    public function deblockByDateRange() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?controller=admin&action=advancedBlocking');
            exit();
        }

        $resortId = filter_input(INPUT_POST, 'resort_id', FILTER_VALIDATE_INT);
        $startDate = filter_input(INPUT_POST, 'start_date', FILTER_UNSAFE_RAW);
        $endDate = filter_input(INPUT_POST, 'end_date', FILTER_UNSAFE_RAW);

        if (!$resortId || !$startDate || !$endDate) {
            $_SESSION['error_message'] = "Resort ID, start date, and end date are required.";
            header('Location: ?controller=admin&action=advancedBlocking&resort_id=' . $resortId);
            exit();
        }

        $deletedCount = BlockedResortAvailability::deleteByDateRange($resortId, $startDate, $endDate);

        if ($deletedCount > 0) {
            $_SESSION['success_message'] = "Successfully removed $deletedCount blocks within the selected date range.";
        } else {
            $_SESSION['success_message'] = "No blocks were found to remove within the selected date range.";
        }
        header('Location: ?controller=admin&action=advancedBlocking&resort_id=' . $resortId);
        exit();
    }
    /**
     * Apply preset blocking to one or more facilities
     */
    public function applyFacilityPresetBlocking() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?controller=admin&action=advancedBlocking');
            exit();
        }

        $resortId = filter_input(INPUT_POST, 'resort_id', FILTER_VALIDATE_INT);
        $facilityIds = $_POST['facility_ids'] ?? [];
        $presetType = filter_input(INPUT_POST, 'preset_type', FILTER_UNSAFE_RAW);
        $reason = trim(filter_input(INPUT_POST, 'reason', FILTER_UNSAFE_RAW));
        $blockedCount = 0;
        $skippedCount = 0;

        if (!$resortId || empty($facilityIds) || !$presetType || empty($reason)) {
            $_SESSION['error_message'] = "Resort, at least one facility, preset type, and reason are required.";
            $tab = filter_input(INPUT_POST, 'tab', FILTER_UNSAFE_RAW);
            header('Location: ?controller=admin&action=advancedBlocking&resort_id=' . $resortId . '&tab=' . $tab);
            exit();
        }

        foreach ($facilityIds as $facilityId) {
            if ($presetType === 'philippine_holidays') {
                $holidaysToBlock = $_POST['holidays'] ?? [];
                if (empty($holidaysToBlock)) {
                    $_SESSION['error_message'] = "Please select at least one holiday to block.";
                    $tab = filter_input(INPUT_POST, 'tab', FILTER_UNSAFE_RAW);
                    header('Location: ?controller=admin&action=advancedBlocking&resort_id=' . $resortId . '&tab=' . $tab);
                    exit();
                }
                
                require_once __DIR__ . '/../Helpers/HolidayHelper.php';
                $currentYear = date('Y');
                
                foreach ($holidaysToBlock as $monthDay) {
                    if (HolidayHelper::isHoliday($currentYear . '-' . $monthDay)) {
                        $dateStr = $currentYear . '-' . $monthDay;
                        if (BlockedFacilityAvailability::create($facilityId, $dateStr, $reason)) {
                            $blockedCount++;
                        } else {
                            $skippedCount++;
                        }
                    }
                }
            } else {
                $startDate = filter_input(INPUT_POST, 'start_date', FILTER_UNSAFE_RAW);
                $endDate = filter_input(INPUT_POST, 'end_date', FILTER_UNSAFE_RAW);

                if (!$startDate || !$endDate) {
                    $_SESSION['error_message'] = "Start date and end date are required for this preset.";
                    $tab = filter_input(INPUT_POST, 'tab', FILTER_UNSAFE_RAW);
                    header('Location: ?controller=admin&action=advancedBlocking&resort_id=' . $resortId . '&tab=' . $tab);
                    exit();
                }

                $currentDate = new DateTime($startDate);
                $lastDate = new DateTime($endDate);

                while ($currentDate <= $lastDate) {
                    $shouldBlock = false;
                    $dateStr = $currentDate->format('Y-m-d');
                    $dayOfWeek = $currentDate->format('w');

                    switch ($presetType) {
                        case 'weekends':
                            $shouldBlock = ($dayOfWeek == 0 || $dayOfWeek == 6);
                            break;
                        case 'all_dates':
                            $shouldBlock = true;
                            break;
                    }

                    if ($shouldBlock) {
                        if (BlockedFacilityAvailability::create($facilityId, $dateStr, $reason)) {
                            $blockedCount++;
                        } else {
                            $skippedCount++;
                        }
                    }
                    $currentDate->modify('+1 day');
                }
            }
        }

        $message = "Blocked $blockedCount dates across selected facilities successfully.";
        if ($skippedCount > 0) {
            $message .= " Skipped $skippedCount dates that were already blocked.";
        }
        $_SESSION['success_message'] = $message;
        $tab = filter_input(INPUT_POST, 'tab', FILTER_UNSAFE_RAW);
        header('Location: ?controller=admin&action=advancedBlocking&resort_id=' . $resortId . '&tab=' . $tab);
        exit();
    }

    /**
     * Deblock all dates for a specific facility
     */
    public function deblockFacilityAll() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?controller=admin&action=advancedBlocking');
            exit();
        }

        $resortId = filter_input(INPUT_POST, 'resort_id', FILTER_VALIDATE_INT);
        $facilityId = filter_input(INPUT_POST, 'facility_id', FILTER_VALIDATE_INT);
        if (!$resortId || !$facilityId) {
            $_SESSION['error_message'] = "Invalid Resort or Facility ID.";
            $tab = filter_input(INPUT_POST, 'tab', FILTER_UNSAFE_RAW);
            header('Location: ?controller=admin&action=advancedBlocking' . '&tab=' . $tab);
            exit();
        }

        $deletedCount = BlockedFacilityAvailability::deleteAllForFacility($facilityId);

        if ($deletedCount > 0) {
            $_SESSION['success_message'] = "Successfully removed all $deletedCount blocks for the selected facility.";
        } else {
            $_SESSION['success_message'] = "No blocks were found to remove for the selected facility.";
        }
        $tab = filter_input(INPUT_POST, 'tab', FILTER_UNSAFE_RAW);
        header('Location: ?controller=admin&action=advancedBlocking&resort_id=' . $resortId . '&tab=' . $tab);
        exit();
    }

    /**
     * Deblock dates within a specific range for a facility
     */
    public function deblockFacilityByDateRange() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?controller=admin&action=advancedBlocking');
            exit();
        }

        $resortId = filter_input(INPUT_POST, 'resort_id', FILTER_VALIDATE_INT);
        $facilityId = filter_input(INPUT_POST, 'facility_id', FILTER_VALIDATE_INT);
        $startDate = filter_input(INPUT_POST, 'start_date', FILTER_UNSAFE_RAW);
        $endDate = filter_input(INPUT_POST, 'end_date', FILTER_UNSAFE_RAW);

        if (!$resortId || !$facilityId || !$startDate || !$endDate) {
            $_SESSION['error_message'] = "Resort ID, Facility ID, start date, and end date are required.";
            $tab = filter_input(INPUT_POST, 'tab', FILTER_UNSAFE_RAW);
            header('Location: ?controller=admin&action=advancedBlocking&resort_id=' . $resortId . '&tab=' . $tab);
            exit();
        }

        $deletedCount = BlockedFacilityAvailability::deleteByDateRangeAndFacility($facilityId, $startDate, $endDate);

        if ($deletedCount > 0) {
            $_SESSION['success_message'] = "Successfully removed $deletedCount blocks for the facility within the selected date range.";
        } else {
            $_SESSION['success_message'] = "No blocks were found to remove for the facility within the selected date range.";
        }
        $tab = filter_input(INPUT_POST, 'tab', FILTER_UNSAFE_RAW);
        header('Location: ?controller=admin&action=advancedBlocking&resort_id=' . $resortId . '&tab=' . $tab);
        exit();
    }

    /**
     * Get pricing summary for dashboard
     */
    public function getPricingSummary() {
        header('Content-Type: application/json');
        
        $resortId = filter_input(INPUT_GET, 'resort_id', FILTER_VALIDATE_INT);
        
        if (!$resortId) {
            echo json_encode(['error' => 'Resort ID required']);
            exit();
        }

        $pricing = ResortTimeframePricing::findByResortId($resortId);
        $facilities = Facility::findByResortId($resortId);
        
        $summary = [
            'timeframe_pricing' => $pricing,
            'facility_pricing' => $facilities
        ];
        
        echo json_encode($summary);
        exit();
    }

    /**
     * PHASE 6: BOOKING LIFECYCLE MANAGEMENT
     */

    /**
     * Process automated lifecycle recommendations
     */
    public function processLifecycleRecommendations() {
        if ($_SESSION['role'] !== 'Admin') {
            http_response_code(403);
            require_once __DIR__ . '/../Views/errors/403.php';
            exit();
        }

        $results = BookingLifecycleManager::processAllBookings();
        
        $_SESSION['success_message'] = "Processed {$results['processed']} bookings: " .
            "{$results['confirmed']} confirmed, {$results['cancelled']} cancelled, " .
            "{$results['completed']} completed.";
            
        if (!empty($results['errors'])) {
            $_SESSION['error_message'] = "Some errors occurred: " . implode(', ', $results['errors']);
        }

        header('Location: ?controller=admin&action=unifiedBookingManagement');
        exit();
    }

    /**
     * Apply individual lifecycle recommendation
     */
    public function applyLifecycleRecommendation() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            exit();
        }

        if ($_SESSION['role'] !== 'Admin') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit();
        }

        $bookingId = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);
        $newStatus = filter_input(INPUT_POST, 'new_status', FILTER_UNSAFE_RAW);
        $reason = filter_input(INPUT_POST, 'reason', FILTER_UNSAFE_RAW);

        if (!$bookingId || !$newStatus) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            exit();
        }

        $result = BookingLifecycleManager::changeBookingStatus(
            $bookingId,
            $newStatus,
            $_SESSION['user_id'],
            $reason ?: 'Lifecycle recommendation applied'
        );

        header('Content-Type: application/json');
        echo json_encode($result);
        exit();
    }

    /**
     * Get booking audit trail data for modal
     */
    public function getBookingAuditTrail() {
        if ($_SESSION['role'] !== 'Admin') {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized']);
            exit();
        }

        $bookingId = filter_input(INPUT_GET, 'booking_id', FILTER_VALIDATE_INT);
        if (!$bookingId) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Invalid booking ID']);
            exit();
        }

        $auditTrail = BookingAuditTrail::getBookingAuditTrail($bookingId, 50);
        
        $formattedTrail = [];
        foreach ($auditTrail as $entry) {
            $formattedTrail[] = [
                'id' => $entry->AuditID,
                'action' => $entry->Action,
                'fieldName' => $entry->FieldName,
                'oldValue' => $entry->OldValue,
                'newValue' => $entry->NewValue,
                'reason' => $entry->ChangeReason,
                'username' => $entry->Username ?? 'System',
                'role' => $entry->Role ?? 'Unknown',
                'createdAt' => $entry->CreatedAt,
                'description' => BookingAuditTrail::getChangeDescription($entry)
            ];
        }

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'bookingId' => $bookingId,
            'auditTrail' => $formattedTrail,
            'totalEntries' => count($formattedTrail)
        ]);
        exit();
    }

    /**
     * Get payment schedule data for modal
     */
    public function getPaymentScheduleData() {
        if ($_SESSION['role'] !== 'Admin') {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized']);
            exit();
        }

        $bookingId = filter_input(INPUT_GET, 'booking_id', FILTER_VALIDATE_INT);
        if (!$bookingId) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Invalid booking ID']);
            exit();
        }

        $schedule = PaymentSchedule::findByBookingId($bookingId);
        $summary = PaymentSchedule::getScheduleSummary($bookingId);
        $nextPayment = PaymentSchedule::getNextPaymentDue($bookingId);

        $formattedSchedule = [];
        foreach ($schedule as $item) {
            $formattedSchedule[] = [
                'scheduleId' => $item->ScheduleID,
                'installmentNumber' => $item->InstallmentNumber,
                'dueDate' => $item->DueDate,
                'amount' => number_format($item->Amount, 2),
                'status' => $item->Status,
                'paymentId' => $item->PaymentID,
                'isOverdue' => ($item->Status === 'Pending' && strtotime($item->DueDate) < time())
            ];
        }

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'bookingId' => $bookingId,
            'schedule' => $formattedSchedule,
            'summary' => [
                'totalInstallments' => $summary->TotalInstallments ?? 0,
                'totalAmount' => number_format($summary->TotalAmount ?? 0, 2),
                'paidAmount' => number_format($summary->PaidAmount ?? 0, 2),
                'remainingAmount' => number_format($summary->RemainingAmount ?? 0, 2),
                'overdueCount' => $summary->OverdueCount ?? 0
            ],
            'nextPayment' => $nextPayment ? [
                'installmentNumber' => $nextPayment->InstallmentNumber,
                'dueDate' => $nextPayment->DueDate,
                'amount' => number_format($nextPayment->Amount, 2),
                'status' => $nextPayment->Status
            ] : null
        ]);
        exit();
    }

    /**
     * Manual trigger for automated processing
     */
    public function runAutomatedProcessing() {
        if ($_SESSION['role'] !== 'Admin') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit();
        }

        $results = BookingLifecycleManager::processAllBookings();

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => "Automated processing completed",
            'results' => $results
        ]);
        exit();
    }

    /**
     * Get lifecycle dashboard data
     */
    public function getLifecycleDashboard() {
        if ($_SESSION['role'] !== 'Admin') {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized']);
            exit();
        }

        $resortId = filter_input(INPUT_GET, 'resort_id', FILTER_VALIDATE_INT);
        
        $summary = BookingLifecycleManager::getBookingLifecycleSummary($resortId);
        $requiresAttention = BookingLifecycleManager::getBookingsRequiringAttention($resortId);
        $overduePayments = PaymentSchedule::getOverdueSchedules($resortId);
        $auditStats = BookingAuditTrail::getAuditStatistics($resortId, 30);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'summary' => $summary,
            'requiresAttention' => $requiresAttention,
            'overduePayments' => $overduePayments,
            'auditStatistics' => $auditStats,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit();
    }

    /**
     * Update payment schedule manually
     */
    public function updatePaymentSchedule() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            exit();
        }

        if ($_SESSION['role'] !== 'Admin') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit();
        }

        $scheduleId = filter_input(INPUT_POST, 'schedule_id', FILTER_VALIDATE_INT);
        $paymentId = filter_input(INPUT_POST, 'payment_id', FILTER_VALIDATE_INT);
        $action = filter_input(INPUT_POST, 'action', FILTER_UNSAFE_RAW);

        if (!$scheduleId || !$action) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            exit();
        }

        $result = false;
        switch ($action) {
            case 'mark_paid':
                if ($paymentId) {
                    $result = PaymentSchedule::markAsPaid($scheduleId, $paymentId);
                }
                break;
            case 'cancel_schedule':
                // This would require a method to cancel individual schedule items
                $result = true; // Placeholder for now
                break;
        }

        header('Content-Type: application/json');
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'Payment schedule updated successfully' : 'Failed to update payment schedule'
        ]);
        exit();
    }
    /**
     * Get comprehensive booking details for the on-site management modal
     */
    public function getBookingDetailsForManagement() {
        header('Content-Type: application/json');
        if ($_SESSION['role'] !== 'Admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit();
        }

        $bookingId = filter_input(INPUT_GET, 'booking_id', FILTER_VALIDATE_INT);
        if (!$bookingId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid Booking ID.']);
            exit();
        }

        try {
            $booking = Booking::findById($bookingId);
            if (!$booking) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Booking not found.']);
                exit();
            }

            $bookedFacilitiesRaw = BookingFacilities::findByBookingId($bookingId);
            
            // Remap keys to camelCase for JS consistency
            $bookedFacilities = array_map(function($bf) {
                return [
                    'facilityId' => $bf->FacilityID,
                    'name' => $bf->FacilityName,
                    'rate' => $bf->FacilityRate
                ];
            }, $bookedFacilitiesRaw);

            $booking->BookedFacilities = $bookedFacilities;

            $payments = Payment::findByBookingId($bookingId);
            $booking->Payments = $payments;

            echo json_encode(['success' => true, 'booking' => $booking]);
        } catch (Exception $e) {
            http_response_code(500);
            error_log("Error in getBookingDetailsForManagement: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'A server error occurred.']);
        }
        exit();
    }

    /**
     * Handle comprehensive booking updates from the on-site management modal
     */
    public function adminUpdateBooking() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?controller=admin&action=unifiedBookingManagement');
            exit();
        }

        if ($_SESSION['role'] !== 'Admin') {
            $_SESSION['error_message'] = "You are not authorized to perform this action.";
            header('Location: ?controller=admin&action=unifiedBookingManagement');
            exit();
        }

        // --- Data Collection ---
        $bookingId = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);
        $resortId = filter_input(INPUT_POST, 'resort_id', FILTER_VALIDATE_INT);
        $bookingStatus = filter_input(INPUT_POST, 'booking_status', FILTER_UNSAFE_RAW);
        
        // Facility IDs will be an array. Check if it's submitted.
        // If 'facilities' is not in the POST data, we should not update them.
        $facilityIds = isset($_POST['facilities']) ? $_POST['facilities'] : null;

        $paymentAmount = filter_input(INPUT_POST, 'payment_amount', FILTER_VALIDATE_FLOAT);
        $paymentMethod = filter_input(INPUT_POST, 'payment_method', FILTER_UNSAFE_RAW);
        
        // --- Validation ---
        if (!$bookingId || !$resortId || !$bookingStatus) {
            $_SESSION['error_message'] = "Missing required booking information.";
            header('Location: ?controller=admin&action=unifiedBookingManagement');
            exit();
        }

        // --- Prepare data for the model ---
        $updateData = [
            'booking_id' => $bookingId,
            'status' => $bookingStatus,
            // Pass facility IDs only if they were submitted
            'facility_ids' => $facilityIds
        ];

        $paymentData = null;
        if ($paymentAmount > 0 && !empty($paymentMethod)) {
            $paymentData = [
                'amount' => $paymentAmount,
                'method' => $paymentMethod,
                'status' => 'Verified' // Admin-added payments are auto-verified
            ];
        }
        
        $adminUserId = $_SESSION['user_id'];

        // --- Call the Model to process the update ---
        $result = Booking::adminUpdateBooking($updateData, $paymentData, $adminUserId);

        if ($result['success']) {
            $_SESSION['success_message'] = "Booking #{$bookingId} has been updated successfully.";
        } else {
            $_SESSION['error_message'] = "Failed to update booking #{$bookingId}: " . ($result['error'] ?? 'An unknown error occurred.');
        }

        header('Location: ?controller=admin&action=unifiedBookingManagement');
        exit();
    }
    /**
     * Handle on-site booking updates for facility changes
     */
    public function onSiteUpdateBooking() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?controller=admin&action=unifiedBookingManagement');
            exit();
        }

        if ($_SESSION['role'] !== 'Admin') {
            $_SESSION['error_message'] = "You are not authorized to perform this action.";
            header('Location: ?controller=admin&action=unifiedBookingManagement');
            exit();
        }

        $bookingId = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);
        $facilityIds = $_POST['facilities'] ?? [];
        $adminUserId = $_SESSION['user_id'];

        if (!$bookingId) {
            $_SESSION['error_message'] = "Missing required booking information.";
            header('Location: ?controller=admin&action=unifiedBookingManagement');
            exit();
        }
        
        $result = Booking::onSiteUpdateFacilities($bookingId, $facilityIds, $adminUserId);

        if ($result['success']) {
            $_SESSION['success_message'] = "On-site edit for booking #{$bookingId} was successful.";
        } else {
            $_SESSION['error_message'] = "Failed to update booking #{$bookingId}: " . ($result['error'] ?? 'An unknown error occurred.');
        }

        header('Location: ?controller=admin&action=unifiedBookingManagement');
        exit();
    }
    /**
     * Get data for the new "Payments" modal
     */
    public function getPaymentsData() {
        header('Content-Type: application/json');
        if ($_SESSION['role'] !== 'Admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit();
        }

        $bookingId = filter_input(INPUT_GET, 'booking_id', FILTER_VALIDATE_INT);
        if (!$bookingId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid Booking ID.']);
            exit();
        }

        try {
            $booking = Booking::findById($bookingId);
            if (!$booking) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Booking not found.']);
                exit();
            }

            $payments = Payment::findByBookingId($bookingId);
            $schedule = PaymentSchedule::findByBookingId($bookingId);
            $summary = PaymentSchedule::getScheduleSummary($bookingId);
            $resortPaymentMethods = ResortPaymentMethods::findByResortId($booking->resortId, true);

            echo json_encode([
                'success' => true,
                'booking' => $booking,
                'payments' => $payments,
                'schedule' => $schedule,
                'summary' => $summary,
                'paymentMethods' => $resortPaymentMethods
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            error_log("Error in getPaymentsData: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'A server error occurred.']);
        }
        exit();
    }

    public function generateInvoiceForAdmin() {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
            http_response_code(403);
            die('Forbidden');
        }

        $bookingId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$bookingId) {
            die('Invalid booking ID.');
        }

        // We can reuse the BookingController's logic by instantiating it
        require_once __DIR__ . '/BookingController.php';
        $bookingController = new BookingController();
        
        // Temporarily grant access for the admin to generate the invoice
        // This is a simplified approach; a more robust RBAC would be better
        $originalUserId = $_SESSION['user_id'];
        
        // Find the customer ID from the booking to satisfy the check in generateInvoice
        $booking = Booking::findById($bookingId);
        if (!$booking) {
            die('Booking not found.');
        }
        $_SESSION['user_id'] = $booking->customerId;

        // Call the public method, which contains the PDF generation logic
        $bookingController->generateInvoice();

        // Restore the original admin user ID
        $_SESSION['user_id'] = $originalUserId;
        exit();
    }
    
    public function emailTemplates() {
        if ($_SESSION['role'] !== 'Admin') {
            http_response_code(403);
            require_once __DIR__ . '/../Views/errors/403.php';
            exit();
        }

        // Only Main Admin can access email templates
        if (!User::isMainAdmin($_SESSION['user_id'])) {
            http_response_code(403);
            require_once __DIR__ . '/../Views/errors/403.php';
            exit();
        }

        $customTemplatesData = EmailTemplate::getAllTemplates();
        $customTemplates = [];
        foreach ($customTemplatesData as $template) {
            $customTemplates[$template['TemplateType']] = $template;
        }

        $defaultTemplates = EmailTemplate::getDefaults();

        require_once __DIR__ . '/../Views/admin/email_templates.php';
    }

    public function updateEmailTemplate() {
        if ($_SESSION['role'] !== 'Admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(403);
            exit('Forbidden');
        }

        $templateId = filter_input(INPUT_POST, 'templateId', FILTER_VALIDATE_INT);
        $subject = filter_input(INPUT_POST, 'subject', FILTER_UNSAFE_RAW);
        $body = $_POST['body']; // Not filtering to allow HTML
        $useCustom = filter_input(INPUT_POST, 'template_choice', FILTER_UNSAFE_RAW) === 'custom';

        if (!$templateId || !$subject || !$body) {
            $_SESSION['error_message'] = "All fields are required when saving a custom template.";
            header('Location: ?controller=admin&action=emailTemplates');
            exit();
        }

        $templateModel = new EmailTemplate();
        if ($templateModel->updateTemplate($templateId, $subject, $body, $useCustom)) {
            $_SESSION['success_message'] = "Email template updated successfully.";
        } else {
            $_SESSION['error_message'] = "Failed to update email template.";
        }

        header('Location: ?controller=admin&action=emailTemplates');
        exit();
    }

    public function showOnSiteBookingForm() {
        if ($_SESSION['role'] !== 'Admin') {
            http_response_code(403);
            require_once __DIR__ . '/../Views/errors/403.php';
            exit();
        }

        // Only Main Admin and Booking Admin can access on-site booking
        if (!User::hasAdminPermission($_SESSION['user_id'], 'onsite_booking')) {
            http_response_code(403);
            require_once __DIR__ . '/../Views/errors/403.php';
            exit();
        }

        // Fetch resorts
        $resorts = Resort::findAll();
        foreach ($resorts as $resort) {
            $resort->icon = $this->getIconForResort($resort->name);
            $resort->mainPhotoURL = BASE_URL . '/' . $resort->mainPhotoURL;
            $resort->hasCompletePricing = ResortTimeframePricing::hasCompletePricing($resort->resortId);
        }

        // Fetch customers
        $customers = User::findByRole('Customer');
        
        $adminUsers = User::getAdminUsers();
        $adminContact = !empty($adminUsers) ? $adminUsers[0] : null;

        $pageTitle = "On-Site Booking";
        require_once __DIR__ . '/../Views/admin/on_site_booking.php';
    }

    public function createOnSiteBooking() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?controller=admin&action=showOnSiteBookingForm');
            exit;
        }

        if ($_SESSION['role'] !== 'Admin') {
            $_SESSION['error_message'] = "You are not authorized to perform this action.";
            header('Location: ?controller=admin&action=dashboard');
            exit;
        }

        $validation = ValidationHelper::validateBookingData($_POST, true); // Admin validation includes customer ID

        if (!$validation['valid']) {
            $errorMessages = [];
            foreach ($validation['errors'] as $field => $errors) {
                $errorMessages = array_merge($errorMessages, $errors);
            }
            $_SESSION['error_message'] = implode('<br>', $errorMessages);
            $_SESSION['old_input'] = $_POST;
            header('Location: ?controller=admin&action=showOnSiteBookingForm');
            exit;
        }

        $validatedData = $validation['data'];
        $resortId = $validatedData['resort_id'];
        $bookingDate = $validatedData['booking_date'];
        $timeSlotType = $validatedData['timeframe'];
        $facilityIds = $validatedData['facility_ids'] ?? [];
        $customerId = $validatedData['customer_id'];
        $adminUserId = $_SESSION['user_id'];

        // Check availability
        if (!Booking::isResortTimeframeAvailable($resortId, $bookingDate, $timeSlotType, $facilityIds)) {
            $_SESSION['error_message'] = "The selected date and timeframe is not available.";
            $_SESSION['old_input'] = $_POST;
            header('Location: ?controller=admin&action=showOnSiteBookingForm');
            exit;
        }

        // Create booking with 'Confirmed' status
        $totalAmount = Booking::calculateBookingTotal($resortId, $timeSlotType, $bookingDate, $facilityIds);
        $bookingId = Booking::createResortBooking($customerId, $resortId, $bookingDate, $timeSlotType, $totalAmount, $facilityIds, 'Confirmed');

        if ($bookingId) {
            // Log creation
            $bookingData = [
                'resortId' => $resortId,
                'bookingDate' => $bookingDate,
                'timeSlotType' => $timeSlotType,
                'totalAmount' => $totalAmount
            ];
            BookingAuditTrail::logBookingCreation($bookingId, $adminUserId, $bookingData, "On-site booking created by admin.");

            // Create a 'Paid' cash payment record
            Payment::createFromBookingPayment(
                $bookingId,
                $totalAmount,
                'Cash',
                'On-Site Payment',
                null,
                'Paid'
            );
            
            // Update booking balance to zero
            Booking::updateRemainingBalance($bookingId, 0);
            
            // Send confirmation email to customer
            AsyncHelper::triggerEmailWorker('booking_confirmed_paid', $bookingId);

            $_SESSION['success_message'] = "On-site booking #{$bookingId} created successfully.";
            header('Location: ?controller=admin&action=unifiedBookingManagement&highlight=' . $bookingId);
        } else {
            $_SESSION['error_message'] = "Could not create the on-site booking. Please try again.";
            $_SESSION['old_input'] = $_POST;
            header('Location: ?controller=admin&action=showOnSiteBookingForm');
        }
        exit;
    }

    private function getIconForFacility($facilityName) {
        $name = strtolower($facilityName);
        if (strpos($name, 'pool') !== false) return 'fas fa-swimming-pool';
        if (strpos($name, 'videoke') !== false || strpos($name, 'karaoke') !== false) return 'fas fa-microphone-alt';
        if (strpos($name, 'room') !== false) return 'fas fa-bed';
        if (strpos($name, 'cottage') !== false) return 'fas fa-campground';
        if (strpos($name, 'grill') !== false) return 'fas fa-fire-alt';
        if (strpos($name, 'kitchen') !== false) return 'fas fa-utensils';
        return 'fas fa-swimming-pool'; // Default icon
    }

    private function getIconForResort($resortName) {
        $name = strtolower($resortName);
        if (strpos($name, 'villa') !== false) return 'fas fa-house-user';
        if (strpos($name, 'season') !== false) return 'fas fa-cloud-sun';
        if (strpos($name, 'classic') !== false) return 'fas fa-gopuram';
        return 'fas fa-hotel'; // Default icon
    }
}
