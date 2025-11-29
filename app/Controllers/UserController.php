<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Helpers/Notification.php';
require_once __DIR__ . '/../Models/Facility.php';
require_once __DIR__ . '/../Models/Feedback.php';
require_once __DIR__ . '/../Models/Resort.php';
require_once __DIR__ . '/../Helpers/ValidationHelper.php';
require_once __DIR__ . '/../Models/EmailVerification.php';

require_once __DIR__ . '/../Helpers/AsyncHelper.php';

class UserController {

   public function dashboard() {
       $resorts = Resort::findAllWithStats();
       $admins = User::getAdminUsers();
       $admin = !empty($admins) ? $admins[0] : null;
       include __DIR__ . '/../Views/dashboard.php';
   }

   public function getFacilityDetails() {
       if (!isset($_GET['id'])) {
           http_response_code(400);
           echo json_encode(['error' => 'Facility ID not specified.']);
           exit();
       }
       $facilityId = $_GET['id'];
       $facility = Facility::findById($facilityId);

       if ($facility) {
           header('Content-Type: application/json');
           // Format descriptions for display
           $facility->shortDescription = nl2br(htmlspecialchars($facility->shortDescription));
           $facility->fullDescription = nl2br(htmlspecialchars($facility->fullDescription));
           echo json_encode($facility);
       } else {
           http_response_code(404);
           echo json_encode(['error' => 'Facility not found.']);
       }
       exit();
   }

   public function getFacilityFeedback() {
       if (!isset($_GET['id'])) {
           http_response_code(400);
           echo json_encode(['error' => 'Facility ID not specified.']);
           exit();
       }
       $facilityId = $_GET['id'];
       $feedback = Feedback::findByFacilityId($facilityId);

       if ($feedback) {
           header('Content-Type: application/json');
           echo json_encode($feedback);
       } else {
           http_response_code(404);
           echo json_encode([]); // Return empty array if no feedback
       }
       exit();
   }

   public function getResortDetails() {
       if (!isset($_GET['id'])) {
           http_response_code(400);
           echo json_encode(['error' => 'Resort ID not specified.']);
           exit();
       }
       $resortId = $_GET['id'];
       $resort = Resort::findById($resortId);

       if ($resort) {
           header('Content-Type: application/json');
           // Format descriptions for display
           $resort->shortDescription = nl2br(htmlspecialchars($resort->shortDescription));
           $resort->fullDescription = nl2br(htmlspecialchars($resort->fullDescription));
           $resort->startingPrice = Resort::getStartingPrice($resortId);
           echo json_encode($resort);
       } else {
           http_response_code(404);
           echo json_encode(['error' => 'Resort not found.']);
       }
       exit();
   }

   public function getResortFacilities() {
       if (!isset($_GET['id'])) {
           http_response_code(400);
           echo json_encode(['error' => 'Resort ID not specified.']);
           exit();
       }
       $resortId = $_GET['id'];
       // Phase 6: Switched to a method that includes feedback and booking counts
       $facilities = Facility::getFacilitiesWithFeedback($resortId);

       if ($facilities) {
           header('Content-Type: application/json');
           // Format descriptions for display
           foreach ($facilities as &$facility) {
               // The data is now an associative array, not an object
               $facility['shortDescription'] = nl2br(htmlspecialchars($facility['ShortDescription']));
               $facility['fullDescription'] = nl2br(htmlspecialchars($facility['FullDescription']));
           }
           echo json_encode($facilities);
       } else {
           http_response_code(404);
           echo json_encode([]); // Return empty array if no facilities
       }
       exit();
   }

   public function getResortFeedback() {
       if (!isset($_GET['id'])) {
           http_response_code(400);
           echo json_encode(['error' => 'Resort ID not specified.']);
           exit();
       }
       $resortId = $_GET['id'];
       $feedbackRecords = Feedback::findByResortId($resortId);

       if ($feedbackRecords) {
           foreach ($feedbackRecords as &$record) {
               $record['completedBookings'] = Booking::countCompletedBookingsByCustomer($record['CustomerID']);
           }
           header('Content-Type: application/json');
           echo json_encode($feedbackRecords);
       } else {
           // To ensure consistency, we'll return a 200 OK with an empty array
           // even if no feedback is found. This prevents the client-side
           // fetch from throwing an error on a 404 response.
           header('Content-Type: application/json');
           echo json_encode([]); // Return empty array if no feedback
       }
       exit();
   }
 
     public function register() {
         if ($_SERVER['REQUEST_METHOD'] === 'POST') {
             // Phase 6: Enhanced validation
            $validation = ValidationHelper::validateUserRegistration($_POST);

            if (!$validation['valid']) {
                $_SESSION['error_message'] = implode('<br>', array_merge(...array_values($validation['errors'])));
                $_SESSION['old_input'] = $_POST;
                header('Location: index.php?action=showRegisterForm');
                exit;
            }

            $validatedData = $validation['data'];

            if ($this->isGmailAddress($validatedData['email'])) {
                $_SESSION['error_message'] = 'Gmail accounts now require verification. Please use the verification prompt to complete registration.';
                $_SESSION['old_input'] = $_POST;
                header('Location: index.php?action=showRegisterForm');
                exit;
            }

            // Attempt to create the user
            $result = User::create(
                $validatedData['username'],
                $validatedData['password'],
                $validatedData['email'],
                'Customer',
                $_POST['firstName'] ?? '',
                $_POST['lastName'] ?? '',
                $_POST['phoneNumber'] ?? ''
            );

            if ($result === true) {
                // Find the new user to get their details
                $newUser = User::findByUsername($validatedData['username']);
                if ($newUser) {
                    AsyncHelper::triggerEmailWorker('welcome_email', $newUser['UserID']);
                }
                // Redirect to login page on success
                header('Location: index.php?action=login&registration=success');
                exit();
            } else {
                // Handle registration failure
                header('Location: index.php?action=showRegisterForm&error=' . $result);
                exit();
            }
        }
    }

    public function initiateGmailVerification() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit();
        }

        header('Content-Type: application/json');

        $validation = ValidationHelper::validateUserRegistration($_POST);
        if (!$validation['valid']) {
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validation['errors']
            ]);
            exit();
        }

        $validatedData = $validation['data'];
        $email = strtolower(trim($validatedData['email']));

        if (!$this->isGmailAddress($email)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Verification is only required for Gmail addresses.'
            ]);
            exit();
        }

        if (User::findByUsername($validatedData['username'])) {
            http_response_code(409);
            echo json_encode([
                'success' => false,
                'message' => 'Username already exists.'
            ]);
            exit();
        }

        if (User::findByEmail($email)) {
            http_response_code(409);
            echo json_encode([
                'success' => false,
                'message' => 'Email already exists.'
            ]);
            exit();
        }

        $firstName = trim($_POST['firstName'] ?? '');
        $lastName = trim($_POST['lastName'] ?? '');
        $phoneNumber = trim($_POST['phoneNumber'] ?? '');

        $payload = [
            'username' => $validatedData['username'],
            'email' => $email,
            'password' => password_hash($validatedData['password'], PASSWORD_DEFAULT),
            'role' => 'Customer',
            'firstName' => $firstName,
            'lastName' => $lastName,
            'phoneNumber' => $phoneNumber
        ];

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        if (!EmailVerification::startVerification($email, $code, $payload)) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to prepare verification. Please try again.'
            ]);
            exit();
        }

        $displayName = $firstName ?: $validatedData['username'];
        if (!Notification::sendGmailVerificationCode($email, $code, $displayName)) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to send verification email. Please try again later.'
            ]);
            exit();
        }

        echo json_encode([
            'success' => true,
            'message' => 'Verification code sent to your Gmail inbox.',
            'email' => $email
        ]);
        exit();
    }

    public function completeGmailRegistration() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit();
        }

        header('Content-Type: application/json');

        $email = strtolower(trim($_POST['email'] ?? ''));
        $code = trim($_POST['code'] ?? '');

        if (!$this->isGmailAddress($email)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Only Gmail addresses require verification.'
            ]);
            exit();
        }

        if (!preg_match('/^\d{6}$/', $code)) {
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'message' => 'Please enter the 6-digit verification code.'
            ]);
            exit();
        }

        $verificationResult = EmailVerification::verifyCode($email, $code);
        if (!$verificationResult['success']) {
            $reason = $verificationResult['reason'];
            $response = [
                'success' => false,
                'message' => 'Verification failed.'
            ];

            switch ($reason) {
                case 'invalid_code':
                    $response['message'] = 'Incorrect code. Please try again.';
                    $response['attempts'] = $verificationResult['attempts'] ?? null;
                    break;
                case 'expired':
                    $response['message'] = 'Code expired. Please request a new one.';
                    break;
                case 'too_many_attempts':
                    $response['message'] = 'Too many invalid attempts. Please request a new code.';
                    break;
                case 'not_found':
                    $response['message'] = 'No pending verification found for this email.';
                    break;
                default:
                    $response['message'] = 'Unable to verify the code. Please try again.';
            }

            http_response_code(400);
            echo json_encode($response);
            exit();
        }

        $payload = $verificationResult['payload'];

        if (User::findByUsername($payload['username'])) {
            http_response_code(409);
            echo json_encode([
                'success' => false,
                'message' => 'Username already exists.'
            ]);
            exit();
        }

        if (User::findByEmail($payload['email'])) {
            http_response_code(409);
            echo json_encode([
                'success' => false,
                'message' => 'Email already exists.'
            ]);
            exit();
        }

        $result = User::create(
            $payload['username'],
            $payload['password'],
            $payload['email'],
            $payload['role'] ?? 'Customer',
            $payload['firstName'] ?? '',
            $payload['lastName'] ?? '',
            $payload['phoneNumber'] ?? '',
            null,
            null,
            null,
            null,
            true
        );

        if ($result !== true) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Registration failed. Please try again.'
            ]);
            exit();
        }

        $newUser = User::findByUsername($payload['username']);
        if ($newUser) {
            AsyncHelper::triggerEmailWorker('welcome_email', $newUser['UserID']);
        }

        echo json_encode([
            'success' => true,
            'redirect' => 'index.php?action=login&registration=success'
        ]);
        exit();
    }

    public function showRegisterForm() {
        include __DIR__ . '/../Views/register.php';
    }

    public function registerAdmin() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Phase 6: Enhanced validation
            $validation = ValidationHelper::validateUserRegistration($_POST, $_FILES);

            if (!$validation['valid']) {
                $_SESSION['error_message'] = implode('<br>', array_merge(...array_values($validation['errors'])));
                $_SESSION['old_input'] = $_POST;
                header('Location: index.php?action=showAdminRegisterForm');
                exit;
            }

            $validatedData = $validation['data'];

            $profileImageURL = null;
            if (isset($_FILES['profileImage']) && $_FILES['profileImage']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../public/uploads/profiles/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $fileName = uniqid() . '-' . basename($_FILES['profileImage']['name']);
                $targetFile = $uploadDir . $fileName;

                if (move_uploaded_file($_FILES['profileImage']['tmp_name'], $targetFile)) {
                    $profileImageURL = 'public/uploads/profiles/' . $fileName;
                }
            }

            // Attempt to create the user with Admin role
            $result = User::create(
                $validatedData['username'],
                $validatedData['password'],
                $validatedData['email'],
                'Admin',
                $_POST['firstName'] ?? '',
                $_POST['lastName'] ?? '',
                $_POST['phoneNumber'] ?? '',
                null,
                $_POST['socials'] ?? '',
                $profileImageURL,
                'Admin' // Explicitly set AdminType to 'Admin' for the Main Admin
            );

            if ($result === true) {
                // Redirect to login page on success
                header('Location: index.php?action=loginAdmin&registration=success');
                exit();
            } else {
                // Handle registration failure
                header('Location: index.php?action=showAdminRegisterForm&error=' . $result);
                exit();
            }
        }
    }

    public function showAdminRegisterForm() {
        $mainAdminExists = User::mainAdminExists();
        include __DIR__ . '/../Views/register-admin.php';
    }
 
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $username = filter_input(INPUT_POST, 'username', FILTER_UNSAFE_RAW);
            $password = $_POST['password'];

            $user = User::findByUsername($username);

            if ($user && password_verify($password, $user['Password']) && $user['Role'] === 'Customer') {
                if (!$user['IsActive']) {
                    header('Location: index.php?action=login&error=account_deactivated');
                    exit();
                }
                // Password is correct and user is Customer, destroy old session and start new one
                session_destroy();
                session_start();
                $_SESSION['user_id'] = $user['UserID'];
                $_SESSION['username'] = $user['Username'];
                $_SESSION['role'] = $user['Role'];

                // Set full name for display purposes
                $firstName = $user['FirstName'] ?? '';
                $lastName = $user['LastName'] ?? '';
                $fullName = trim($firstName . ' ' . $lastName);
                if (empty($fullName)) {
                    $fullName = $user['Username'];
                }
                $_SESSION['user_name'] = $fullName;

                header('Location: ?controller=user&action=dashboard');
                exit();
            } else {
                // Invalid credentials or wrong role, reload the login page with an error
                header('Location: index.php?action=login&error=invalid_credentials');
                exit();
            }
        } else {
            // Display the login form
            include __DIR__ . '/../Views/login.php';
        }
    }

    public function loginAdmin() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $username = filter_input(INPUT_POST, 'username', FILTER_UNSAFE_RAW);
            $password = $_POST['password'];

            $user = User::findByUsername($username);

            if ($user && password_verify($password, $user['Password']) && ($user['Role'] === 'Admin' || $user['Role'] === 'Staff')) {
                // Password is correct and user is Admin/Staff, destroy old session and start new one
                session_destroy();
                session_start();
                $_SESSION['user_id'] = $user['UserID'];
                $_SESSION['username'] = $user['Username'];
                $_SESSION['role'] = $user['Role'];

                // Set full name for display purposes
                $firstName = $user['FirstName'] ?? '';
                $lastName = $user['LastName'] ?? '';
                $fullName = trim($firstName . ' ' . $lastName);
                if (empty($fullName)) {
                    $fullName = $user['Username'];
                }
                $_SESSION['user_name'] = $fullName;

                header('Location: ?controller=admin&action=dashboard');
                exit();
            } else {
                // Invalid credentials or wrong role, reload the login page with an error
                header('Location: index.php?action=loginAdmin&error=invalid_credentials');
                exit();
            }
        } else {
            // Display the login form
            include __DIR__ . '/../Views/login-admin.php';
        }
    }

    public function profile() {
        if (!isset($_SESSION['user_id'])) {
            // Show guest-friendly profile page
            include __DIR__ . '/../Views/profile_guest.php';
            return;
        }

        $userId = $_SESSION['user_id'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Handle profile update
            $username = filter_input(INPUT_POST, 'username', FILTER_UNSAFE_RAW);
            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
            $firstName = filter_input(INPUT_POST, 'firstName', FILTER_UNSAFE_RAW);
            $lastName = filter_input(INPUT_POST, 'lastName', FILTER_UNSAFE_RAW);
            $phoneNumber = filter_input(INPUT_POST, 'phoneNumber', FILTER_UNSAFE_RAW);
            $socials = null;
            $profileImageURL = null;

            if (isset($_SESSION['role']) && $_SESSION['role'] === 'Admin') {
                $socials = filter_input(INPUT_POST, 'socials', FILTER_UNSAFE_RAW);
                if (isset($_FILES['profileImage']) && $_FILES['profileImage']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = __DIR__ . '/../../public/uploads/profiles/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    $fileName = uniqid() . '-' . basename($_FILES['profileImage']['name']);
                    $targetFile = $uploadDir . $fileName;

                    if (move_uploaded_file($_FILES['profileImage']['tmp_name'], $targetFile)) {
                        $profileImageURL = 'public/uploads/profiles/' . $fileName;
                    }
                }
            }
            
            $password = $_POST['password'];
            $confirmPassword = $_POST['confirm_password'];

            // Notification preferences: read submitted 0/1 values (hidden inputs + checkbox)
            $notifyOnNewReservation = isset($_POST['notify_new_reservation']) ? (int)$_POST['notify_new_reservation'] : 0;
            $notifyOnReservationUpdate = isset($_POST['notify_reservation_update']) ? (int)$_POST['notify_reservation_update'] : 0;

            // Update user details
            $result = User::update($userId, $username, $email, $firstName, $lastName, $phoneNumber, null, $socials, $profileImageURL, $notifyOnNewReservation, $notifyOnReservationUpdate);

            if ($result) {
                // Update the session variables
                $_SESSION['username'] = $username;
                // Also update user_name for display purposes
                $updatedUser = User::findById($userId);
                if ($updatedUser) {
                    $firstName = $updatedUser['FirstName'] ?? '';
                    $lastName = $updatedUser['LastName'] ?? '';
                    $fullName = trim($firstName . ' ' . $lastName);
                    if (empty($fullName)) {
                        $fullName = $updatedUser['Username'];
                    }
                    $_SESSION['user_name'] = $fullName;
                }
            }

            // Update password if provided
            if (!empty($password)) {
                if ($password !== $confirmPassword) {
                    header('Location: ?controller=user&action=profile&error=password_mismatch');
                    exit();
                }
                User::updatePassword($userId, $password);
            }
            
            header('Location: ?controller=user&action=profile&status=updated');
            exit();

        } else {
            // Display profile form
            $user = User::findById($userId);
            include __DIR__ . '/../Views/profile.php';
        }
    }

    public function logout() {

        // Check user role before destroying session
        $redirectAction = 'login'; // Default to customer login
        if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['Admin', 'Staff'])) {
            $redirectAction = 'loginAdmin';
        }

        // Unset all session variables
        $_SESSION = array();

        // Destroy the session
        session_destroy();

        // Redirect to appropriate login page
        header('Location: index.php?action=' . $redirectAction . '&logout=success');
        exit();
    }

    private function isGmailAddress($email) {
        return (bool) preg_match('/@gmail\.com$/i', $email);
    }
}

// This router is now handled by public/index.php
