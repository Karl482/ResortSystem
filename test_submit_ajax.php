<?php
// Test file to verify reschedule submission works

session_start();
$_SESSION['user_id'] = 3;
$_SESSION['role'] = 'Customer';
$_SESSION['username'] = 'test_customer';

$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['booking_id'] = '2';
$_POST['requested_date'] = date('Y-m-d', strtotime('+5 days'));
$_POST['requested_time_slot'] = 'Morning';
$_POST['reason'] = 'Test reschedule';
$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';

require_once 'config/database.php';
require_once 'app/Helpers/Database.php';
require_once 'app/Helpers/ErrorHandler.php';
require_once 'app/Models/RescheduleRequest.php';
require_once 'app/Models/Booking.php';
require_once 'app/Controllers/UserController.php';

// Call the controller
$controller = new UserController();
$controller->submitRescheduleRequest();
?>
