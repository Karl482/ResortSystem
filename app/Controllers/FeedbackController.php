<?php

require_once __DIR__ . '/../Models/Feedback.php';
require_once __DIR__ . '/../Models/Booking.php';
require_once __DIR__ . '/../Models/User.php';

class FeedbackController {


    public function submitFeedback() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
            header('Location: index.php');
            exit;
        }

        $bookingId = filter_input(INPUT_POST, 'bookingId', FILTER_VALIDATE_INT);
        $resortRating = filter_input(INPUT_POST, 'resort_rating', FILTER_VALIDATE_INT);
        $resortComment = filter_input(INPUT_POST, 'resort_comment', FILTER_SANITIZE_SPECIAL_CHARS);
        $facilitiesData = $_POST['facilities'] ?? [];
        $mediaFiles = $_FILES['media'] ?? [];
        $redirectUrl = filter_input(INPUT_POST, 'redirect_url', FILTER_SANITIZE_URL) ?: '?controller=booking&action=showMyBookings';

        if (!$bookingId || !$resortRating) {
            $_SESSION['error_message'] = "A rating for the resort is required.";
            header('Location: ' . $redirectUrl);
            exit;
        }

        $booking = Booking::findById($bookingId);
        if (!$booking || $booking->customerId != $_SESSION['user_id']) {
            $_SESSION['error_message'] = "Invalid booking.";
            header('Location: ' . $redirectUrl);
            exit;
        }

        $feedback = new Feedback();
        $feedback->bookingId = $bookingId;
        $feedback->rating = $resortRating;
        $feedback->comment = $resortComment;

        $facilityFeedbacks = [];
        if (!empty($facilitiesData)) {
            foreach ($facilitiesData as $facilityId => $data) {
                $facilityFeedbacks[] = [
                    'id' => filter_var($facilityId, FILTER_VALIDATE_INT),
                    'rating' => filter_var($data['rating'], FILTER_VALIDATE_INT),
                    'comment' => filter_var($data['comment'], FILTER_SANITIZE_SPECIAL_CHARS)
                ];
            }
        }

        if (Feedback::createWithFacilities($feedback, $facilityFeedbacks, $mediaFiles)) {
            $_SESSION['success_message'] = "Thank you for your feedback!";
        } else {
            $_SESSION['error_message'] = "Failed to submit feedback. Please try again.";
        }
        header('Location: ' . $redirectUrl);
        exit;
    }

    public function showMyFeedback() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?action=login');
            exit;
        }

        $customerId = $_SESSION['user_id'];
        
        // Load required models
        require_once __DIR__ . '/../Models/Booking.php';
        require_once __DIR__ . '/../Models/Feedback.php';
        
        // 1. Get bookings awaiting feedback (Completed but no Feedback entry)
        $pendingFeedbacks = Booking::findCompletedBookingsAwaitingFeedback($customerId);

        // 2. Get customer's past feedback history
        $feedbackHistory = Feedback::findCustomerFeedbackHistory($customerId);

        $pageTitle = "My Feedback";
        require_once __DIR__ . '/../Views/feedback/my_feedback.php';
    }
    
    public function listAllFeedback() {
        if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Staff'])) {
            $_SESSION['error_message'] = "You are not authorized to view this page.";
            header('Location: index.php');
            exit;
        }

        // Check if admin has feedback viewing permission
        if ($_SESSION['role'] === 'Admin' && !User::hasAdminPermission($_SESSION['user_id'], 'feedback_view')) {
            http_response_code(403);
            require_once __DIR__ . '/../Views/errors/403.php';
            exit();
        }

        require_once __DIR__ . '/../Models/Resort.php';

        $resortIdParam = isset($_GET['resort_id']) && trim($_GET['resort_id']) !== '' ? filter_var($_GET['resort_id'], FILTER_VALIDATE_INT) : null;
        $resortsToQuery = null;
        $allResorts = Resort::findAll(); // For the dropdown

        if ($_SESSION['role'] === 'Staff') {
            $assignedResorts = User::getAssignedResorts($_SESSION['user_id']);
            $assignedResortIds = array_map(fn($resort) => $resort->ResortID, $assignedResorts);
            
            // Staff can only see their assigned resorts in the dropdown
            $resorts = array_filter($allResorts, fn($resort) => in_array($resort->resortId, $assignedResortIds));

            // Determine which resorts to query for feedback
            if ($resortIdParam && in_array($resortIdParam, $assignedResortIds)) {
                // If a specific, assigned resort is selected, query only that one
                $resortsToQuery = $resortIdParam;
            } else {
                // Otherwise, query all assigned resorts
                $resortsToQuery = $assignedResortIds;
            }
        } else {
            // Admins see all resorts
            $resorts = $allResorts;
            $resortsToQuery = $resortIdParam;
        }

        $resortFeedbacks = Feedback::findAll($resortsToQuery);
        $facilityFeedbacks = Feedback::findAllFacilityFeedbacks($resortsToQuery);

        require_once __DIR__ . '/../Views/admin/feedback/index.php';
    }

    public function getFacilityFeedback() {
        header('Content-Type: application/json');

        if (!isset($_GET['facility_id'])) {
            echo json_encode(['success' => false, 'error' => 'Facility ID is required.']);
            exit;
        }
        $facilityId = filter_var($_GET['facility_id'], FILTER_VALIDATE_INT);
        if (!$facilityId) {
            echo json_encode(['success' => false, 'error' => 'Invalid Facility ID.']);
            exit;
        }

        // Get reviews for the facility
        $reviews = Feedback::findFacilityFeedbackDetails($facilityId);

        // Get the total number of completed bookings for the facility
        $totalCompletedBookings = Booking::countCompletedBookingsByFacility($facilityId);

        if ($reviews === false) {
            echo json_encode(['success' => false, 'error' => 'Failed to retrieve feedback data.']);
            exit;
        }
        
        $reviewsWithCounts = [];
        foreach ($reviews as $review) {
            $review['completedBookings'] = Booking::countCompletedBookingsByCustomer($review['CustomerID']);
            $reviewsWithCounts[] = $review;
        }

        $response = [
            'success' => true,
            'feedback' => [
                'reviews' => $reviewsWithCounts,
                'totalCompletedBookings' => $totalCompletedBookings
            ]
        ];

        echo json_encode($response);
        exit;
    }
}
