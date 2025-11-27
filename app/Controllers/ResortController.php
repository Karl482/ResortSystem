<?php

require_once __DIR__ . '/../Models/Resort.php';
require_once __DIR__ . '/../Models/Facility.php';

require_once __DIR__ . '/../Models/ResortPaymentMethods.php';

class ResortController {

    public function __construct() {
        // Public actions that don't require admin role can be whitelisted.
        $action = $_GET['action'] ?? 'index';
        $publicActions = ['getFacilitiesJson', 'getResortJson', 'getResortFacilities'];

        if (in_array($action, $publicActions)) {
            if (!isset($_SESSION['user_id'])) {
                http_response_code(403);
                require_once __DIR__ . '/../Views/errors/403.php';
                exit();
            }
        } else {
            // For all other actions, ensure user is an Admin
            if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
                http_response_code(403);
                require_once __DIR__ . '/../Views/errors/403.php';
                exit();
            }
        }
    }

    public function index() {
        $resorts = Resort::findAll();
        include __DIR__ . '/../Views/admin/resorts/index.php';
    }

    public function store() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $resort = new Resort();
            $resort->name = filter_input(INPUT_POST, 'name', FILTER_UNSAFE_RAW);
            $resort->address = filter_input(INPUT_POST, 'address', FILTER_UNSAFE_RAW);
            $resort->contactPerson = filter_input(INPUT_POST, 'contactPerson', FILTER_UNSAFE_RAW);

            if (Resort::create($resort)) {
                header('Location: ?controller=resort&action=index&status=resort_added');
            } else {
                header('Location: ?controller=resort&action=index&error=add_failed');
            }
            exit();
        }
    }

    public function update() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $resortId = filter_input(INPUT_POST, 'resortId', FILTER_VALIDATE_INT);
            if (!$resortId) {
                die('Invalid Resort ID.');
            }

            $resort = new Resort();
            $resort->resortId = $resortId;
            $resort->name = filter_input(INPUT_POST, 'name', FILTER_UNSAFE_RAW);
            $resort->address = filter_input(INPUT_POST, 'address', FILTER_UNSAFE_RAW);
            $resort->contactPerson = filter_input(INPUT_POST, 'contactPerson', FILTER_UNSAFE_RAW);

            if (Resort::update($resort)) {
                header('Location: ?controller=resort&action=index&status=resort_updated');
            } else {
                header('Location: ?controller=resort&action=index&error=update_failed');
            }
            exit();
        }
    }

    public function destroy() {
        $resortId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$resortId) {
            $_SESSION['error_message'] = "Invalid Resort ID.";
            header('Location: ?controller=resort&action=index');
            exit();
        }

        // Check for dependent facilities before deleting
        $facilities = Facility::findByResortId($resortId);
        if (!empty($facilities)) {
            $_SESSION['error_message'] = "Cannot delete resort. It has associated facilities.";
            header('Location: ?controller=resort&action=index');
            exit();
        }

        if (Resort::delete($resortId)) {
            $_SESSION['success_message'] = "Resort deleted successfully!";
        } else {
            $_SESSION['error_message'] = "Failed to delete resort.";
        }
        header('Location: ?controller=resort&action=index');
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
            echo json_encode($resort);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Resort not found.']);
        }
        exit();
    }

    public function getFacilitiesJson() {
        header('Content-Type: application/json');
        
        $resortId = filter_input(INPUT_GET, 'resort_id', FILTER_VALIDATE_INT);
        if (!$resortId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid or missing Resort ID.']);
            exit();
        }

        try {
            $facilities = Facility::findByResortId($resortId);
            echo json_encode(['success' => true, 'facilities' => $facilities]);
        } catch (Exception $e) {
            http_response_code(500);
            error_log("Error in getFacilitiesJson: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'A server error occurred while fetching facilities.']);
        }
        exit();
    }

    public function getResortFacilities() {
        header('Content-Type: application/json');
        $resortId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$resortId) {
            http_response_code(400);
            echo json_encode([]);
            exit();
        }

        $facilities = Facility::getFacilitiesWithFeedback($resortId);
        echo json_encode($facilities);
        exit();
    }
}