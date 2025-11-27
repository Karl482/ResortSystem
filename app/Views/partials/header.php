<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../app/Models/Booking.php';
require_once __DIR__ . '/../../../app/Models/Payment.php';
require_once __DIR__ . '/../../../app/Models/User.php';

$pendingFeedbackCount = 0;
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'Customer') {
    require_once __DIR__ . '/../../../app/Models/Feedback.php'; // Required for consistency, although Booking model handles count
    $pendingFeedbackCount = Booking::countBookingsAwaitingFeedback($_SESSION['user_id']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Resort Haven' ?></title>
    <!-- Bootstrap CSS -->
    <link href="<?= BASE_URL ?>/assets/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link href="<?= BASE_URL ?>/assets/css/fontawesome.min.css" rel="stylesheet">
    <!-- Main Application CSS -->
    <link href="<?= BASE_URL ?>/assets/css/main.css" rel="stylesheet">
    <!-- Bootstrap JS Bundle - Load in head for navbar dropdowns -->
    <script src="<?= BASE_URL ?>/assets/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js - Added for dashboard graphs -->
    <script src="<?= BASE_URL ?>/assets/js/chart.min.js"></script>


    <style>
        .booking-count-badge {
            font-size: 12px !important;
            line-height: 1;
            padding: 0.3em 0.6em;
        }
    </style>
    <script>
        window.BASE_URL = '<?= BASE_URL ?>';
    </script>
</head>
<body>
<!-- Background Wave Effect -->
<div class="main-background-overlay"></div>

<?php if (isset($_SESSION['user_id'])): ?>
    <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['Admin', 'Staff'])): ?>
        <!-- Admin/Staff Layout with Sidebar -->
        <div class="admin-layout-wrapper">
            <!-- Mobile Header for Admin/Staff -->
            <header class="admin-mobile-header d-lg-none">
                <button class="sidebar-toggle" id="sidebarToggle" type="button">
                    <i class="fas fa-bars"></i>
                </button>
                <a class="mobile-brand" href="?">
                    <i class="fas fa-swimming-pool"></i> Resort Haven
                </a>
                <div class="mobile-actions">
                    <div class="theme-switcher theme-switcher-mobile" id="mobile-theme-switcher">
                        <div class="theme-switcher-slider"></div>
                        <i class="fas fa-sun"></i>
                        <i class="fas fa-moon"></i>
                    </div>
                </div>
            </header>

            <!-- Sidebar Overlay for Mobile -->
            <div class="sidebar-overlay d-lg-none" id="sidebarOverlay"></div>

            <!-- Include Admin/Staff Sidebar -->
            <?php require_once __DIR__ . '/admin_sidebar.php'; ?>

            <!-- Main Content Wrapper -->
            <main id="admin-main-content">
                <div class="container-fluid py-4">
    <?php else: ?>
        <!-- Customer Layout with Top Navbar -->
        <nav class="navbar navbar-expand-lg sticky-top">
            <div class="container-fluid">
                <a class="navbar-brand" href="?">
                    <i class="fas fa-swimming-pool"></i> Resort Haven
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <?php if ($_SESSION['role'] === 'Customer'): ?>
                        <?php 
                            $confirmedBookingCount = Booking::getConfirmedBookingsCount($_SESSION['user_id']);
                            $pendingBookingCount = Booking::getPendingBookingsCount($_SESSION['user_id']);
                        ?>
                        <li class="nav-item">
                            <a class="nav-link" href="?">
                                <i class="fas fa-home"></i> Home
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="?controller=booking&action=showBookingForm">
                                <i class="fas fa-plus-circle"></i> New Reservation
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="?controller=booking&action=showMyReservations">
                                <i class="fas fa-clock"></i> My Reservations<?php if ($pendingBookingCount > 0): ?><span class="badge booking-count-badge bg-warning text-dark fw-semibold ms-1"><?php echo $pendingBookingCount; ?></span><?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="?controller=booking&action=showMyBookings">
                                <i class="fas fa-calendar-check"></i> My Bookings<?php if ($confirmedBookingCount > 0): ?><span class="badge booking-count-badge bg-success text-white fw-semibold ms-1"><?php echo $confirmedBookingCount; ?></span><?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="?controller=feedback&action=showMyFeedback">
                                <i class="fas fa-comment-dots"></i> My Feedback<?php if ($pendingFeedbackCount > 0): ?><span class="badge booking-count-badge bg-danger text-white fw-semibold ms-1"><?php echo $pendingFeedbackCount; ?></span><?php endif; ?>
                            </a>
                        </li>
                        <?php endif; ?>
                    <li class="nav-item">
                            <a class="nav-link" href="?controller=user&action=profile">
                                <i class="fas fa-user"></i> My Profile
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="?controller=user&action=logout">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </li>
                        <!-- Theme Switcher -->
                        <li class="nav-item d-flex align-items-center">
                            <div class="theme-switcher" id="theme-switcher">
                                <div class="theme-switcher-slider"></div>
                                <i class="fas fa-sun"></i>
                                <i class="fas fa-moon"></i>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <div class="container mt-4">
    <?php endif; ?>
<?php endif; ?>
