<?php
/**
 * Admin/Staff Sidebar Navigation Component
 * This file contains the sidebar navigation for Admin and Staff users
 */

// Get current page info for active state highlighting
$currentController = $_GET['controller'] ?? '';
$currentAction = $_GET['action'] ?? '';

// Get current user's role
$currentUserRole = $_SESSION['role'] ?? '';
$isStaffRole = ($currentUserRole === 'Staff');

// Calculate badge counts (only for Admins)
$totalPendingPayments = 0;
$totalActiveBookings = 0;
if (!$isStaffRole) {
    $totalPendingPayments = Payment::getPendingPaymentCount();
    $totalActiveBookings = Booking::getActiveBookingsCountForAdmin();
}

// Check admin permissions (Staff has limited permissions)
$hasBookingAccess = !$isStaffRole && User::hasAdminPermission($_SESSION['user_id'], 'booking_management');
$hasOperationsAccess = !$isStaffRole && (User::hasAdminPermission($_SESSION['user_id'], 'pricing_management') ||
                      User::hasAdminPermission($_SESSION['user_id'], 'advanced_blocking') ||
                      User::hasAdminPermission($_SESSION['user_id'], 'resort_management'));
$hasReportsAccess = !$isStaffRole && (User::hasAdminPermission($_SESSION['user_id'], 'income_analytics') ||
                  User::hasAdminPermission($_SESSION['user_id'], 'income_analytics_view') ||
                  User::hasAdminPermission($_SESSION['user_id'], 'operational_reports'));
$isMainAdmin = !$isStaffRole && User::isMainAdmin($_SESSION['user_id']);

// Get current user info for sidebar display
$currentAdminUser = User::findById($_SESSION['user_id']);
if ($isStaffRole) {
    $userTypeDisplay = 'Staff';
} else {
    $userTypeDisplay = User::getAdminTypeDisplay($currentAdminUser['AdminType'] ?? 'Admin');
}
$userFirstName = htmlspecialchars($currentAdminUser['FirstName'] ?? ($isStaffRole ? 'Staff' : 'Admin'));

/**
 * Helper function to check if a nav item is active
 */
function isNavActive($controller, $action, $currentController, $currentAction) {
    return $controller === $currentController && $action === $currentAction;
}

/**
 * Helper function to check if any item in a dropdown is active
 */
function isDropdownActive($items, $currentController, $currentAction) {
    foreach ($items as $item) {
        if (isset($item['controller']) && isset($item['action'])) {
            if ($item['controller'] === $currentController && $item['action'] === $currentAction) {
                return true;
            }
        }
    }
    return false;
}
?>

<aside id="admin-sidebar" class="admin-sidebar">
    <!-- Sidebar Header -->
    <div class="sidebar-header">
        <a class="sidebar-brand" href="?">
            <i class="fas fa-swimming-pool"></i>
            <span>Resort Haven</span>
        </a>
        <button class="sidebar-close d-lg-none" id="sidebarClose">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <!-- User Info -->
    <div class="sidebar-user-info">
        <div class="user-type"><?= $userTypeDisplay ?></div>
        <div class="user-name"><?= $userFirstName ?></div>
    </div>

    <!-- Sidebar Navigation -->
    <nav class="sidebar-nav">
        <ul class="sidebar-menu">
            <!-- Dashboard -->
            <li class="sidebar-item">
                <a class="sidebar-link <?= isNavActive('admin', 'dashboard', $currentController, $currentAction) ? 'active' : '' ?>" 
                   href="?controller=admin&action=dashboard">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <?php if ($hasBookingAccess): ?>
                <!-- Booking & Payments Section -->
                <?php 
                $bookingItems = [
                    ['controller' => 'admin', 'action' => 'unifiedBookingManagement'],
                    ['controller' => 'payment', 'action' => 'showPendingPayments'],
                    ['controller' => 'admin', 'action' => 'showOnSiteBookingForm']
                ];
                $isBookingActive = isDropdownActive($bookingItems, $currentController, $currentAction);
                ?>
                <li class="sidebar-item has-submenu">
                    <a class="sidebar-link <?= $isBookingActive ? 'active' : '' ?>"
                       href="#bookingSubmenu"
                       data-bs-toggle="collapse"
                       aria-expanded="<?= $isBookingActive ? 'true' : 'false' ?>">
                        <i class="fas fa-calendar-check"></i>
                        <span>Booking & Payments</span>
                        <i class="fas fa-chevron-down submenu-arrow"></i>
                    </a>
                    <ul class="sidebar-submenu collapse <?= $isBookingActive ? 'show' : '' ?>" id="bookingSubmenu">
                        <li>
                            <a class="sidebar-sublink <?= isNavActive('admin', 'unifiedBookingManagement', $currentController, $currentAction) ? 'active' : '' ?>" 
                               href="?controller=admin&action=unifiedBookingManagement">
                                <i class="fas fa-calendar-check"></i>
                                <span>Unified Management</span>
                                <?php if ($totalActiveBookings > 0): ?>
                                    <span class="sidebar-badge bg-info"><?= $totalActiveBookings ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li>
                            <a class="sidebar-sublink <?= isNavActive('payment', 'showPendingPayments', $currentController, $currentAction) ? 'active' : '' ?>" 
                               href="?controller=payment&action=showPendingPayments">
                                <i class="fas fa-credit-card"></i>
                                <span>Payment Verification</span>
                                <?php if ($totalPendingPayments > 0): ?>
                                    <span class="sidebar-badge bg-danger"><?= $totalPendingPayments ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <?php if ($isMainAdmin): ?>
                        <li>
                            <a class="sidebar-sublink <?= isNavActive('admin', 'showOnSiteBookingForm', $currentController, $currentAction) ? 'active' : '' ?>" 
                               href="?controller=admin&action=showOnSiteBookingForm">
                                <i class="fas fa-store"></i>
                                <span>On-Site Booking</span>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </li>
            <?php endif; ?>

            <?php if ($hasOperationsAccess): ?>
                <!-- Resort Operations Section -->
                <?php 
                $operationsItems = [
                    ['controller' => 'admin', 'action' => 'management'],
                    ['controller' => 'admin', 'action' => 'pricingManagement'],
                    ['controller' => 'admin', 'action' => 'advancedBlocking']
                ];
                $isOperationsActive = isDropdownActive($operationsItems, $currentController, $currentAction);
                ?>
                <li class="sidebar-item has-submenu">
                    <a class="sidebar-link <?= $isOperationsActive ? 'active' : '' ?>" 
                       href="#operationsSubmenu" 
                       data-bs-toggle="collapse" 
                       aria-expanded="<?= $isOperationsActive ? 'true' : 'false' ?>">
                        <i class="fas fa-cogs"></i>
                        <span>Resort Operations</span>
                        <i class="fas fa-chevron-down submenu-arrow"></i>
                    </a>
                    <ul class="sidebar-submenu collapse <?= $isOperationsActive ? 'show' : '' ?>" id="operationsSubmenu">
                        <?php if (User::hasAdminPermission($_SESSION['user_id'], 'resort_management')): ?>
                        <li>
                            <a class="sidebar-sublink <?= isNavActive('admin', 'management', $currentController, $currentAction) ? 'active' : '' ?>" 
                               href="?controller=admin&action=management">
                                <i class="fas fa-building"></i>
                                <span>Resort Haven</span>
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if (User::hasAdminPermission($_SESSION['user_id'], 'pricing_management')): ?>
                        <li>
                            <a class="sidebar-sublink <?= isNavActive('admin', 'pricingManagement', $currentController, $currentAction) ? 'active' : '' ?>" 
                               href="?controller=admin&action=pricingManagement">
                                <i class="fas fa-tags"></i>
                                <span>Pricing Management</span>
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if (User::hasAdminPermission($_SESSION['user_id'], 'advanced_blocking')): ?>
                        <li>
                            <a class="sidebar-sublink <?= isNavActive('admin', 'advancedBlocking', $currentController, $currentAction) ? 'active' : '' ?>" 
                               href="?controller=admin&action=advancedBlocking">
                                <i class="fas fa-ban"></i>
                                <span>Advanced Blocking</span>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </li>
            <?php endif; ?>

            <?php if ($hasReportsAccess): ?>
                <!-- Statistics Section -->
                <?php 
                $reportsItems = [
                    ['controller' => 'admin', 'action' => 'incomeAnalytics'],
                    ['controller' => 'admin', 'action' => 'operationalReports'],
                    ['controller' => 'feedback', 'action' => 'listAllFeedback']
                ];
                $isReportsActive = isDropdownActive($reportsItems, $currentController, $currentAction);
                ?>
                <li class="sidebar-item has-submenu">
                    <a class="sidebar-link <?= $isReportsActive ? 'active' : '' ?>" 
                       href="#reportsSubmenu" 
                       data-bs-toggle="collapse" 
                       aria-expanded="<?= $isReportsActive ? 'true' : 'false' ?>">
                        <i class="fas fa-chart-bar"></i>
                        <span>Statistics</span>
                        <i class="fas fa-chevron-down submenu-arrow"></i>
                    </a>
                    <ul class="sidebar-submenu collapse <?= $isReportsActive ? 'show' : '' ?>" id="reportsSubmenu">
                        <?php if (User::hasAdminPermission($_SESSION['user_id'], 'income_analytics') || User::hasAdminPermission($_SESSION['user_id'], 'income_analytics_view')): ?>
                        <li>
                            <a class="sidebar-sublink <?= isNavActive('admin', 'incomeAnalytics', $currentController, $currentAction) ? 'active' : '' ?>" 
                               href="?controller=admin&action=incomeAnalytics">
                                <i class="fas fa-chart-line"></i>
                                <span>Income Analytics</span>
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if (User::hasAdminPermission($_SESSION['user_id'], 'operational_reports')): ?>
                        <li>
                            <a class="sidebar-sublink <?= isNavActive('admin', 'operationalReports', $currentController, $currentAction) ? 'active' : '' ?>" 
                               href="?controller=admin&action=operationalReports">
                                <i class="fas fa-file-alt"></i>
                                <span>Operational Reports</span>
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if (User::hasAdminPermission($_SESSION['user_id'], 'feedback_view')): ?>
                        <li>
                            <a class="sidebar-sublink <?= isNavActive('feedback', 'listAllFeedback', $currentController, $currentAction) ? 'active' : '' ?>" 
                               href="?controller=feedback&action=listAllFeedback">
                                <i class="fas fa-comments"></i>
                                <span>View Feedback</span>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </li>
            <?php endif; ?>

            <?php if ($isMainAdmin): ?>
                <!-- System Section -->
                <?php 
                $systemItems = [
                    ['controller' => 'admin', 'action' => 'users'],
                    ['controller' => 'admin', 'action' => 'emailTemplates'],
                    ['controller' => 'admin', 'action' => 'previewFacilities']
                ];
                $isSystemActive = isDropdownActive($systemItems, $currentController, $currentAction);
                ?>
                <li class="sidebar-item has-submenu">
                    <a class="sidebar-link <?= $isSystemActive ? 'active' : '' ?>" 
                       href="#systemSubmenu" 
                       data-bs-toggle="collapse" 
                       aria-expanded="<?= $isSystemActive ? 'true' : 'false' ?>">
                        <i class="fas fa-cog"></i>
                        <span>System</span>
                        <i class="fas fa-chevron-down submenu-arrow"></i>
                    </a>
                    <ul class="sidebar-submenu collapse <?= $isSystemActive ? 'show' : '' ?>" id="systemSubmenu">
                        <li>
                            <a class="sidebar-sublink <?= isNavActive('admin', 'users', $currentController, $currentAction) ? 'active' : '' ?>" 
                               href="?controller=admin&action=users">
                                <i class="fas fa-users"></i>
                                <span>Manage Users</span>
                            </a>
                        </li>
                        <li>
                            <a class="sidebar-sublink <?= isNavActive('admin', 'emailTemplates', $currentController, $currentAction) ? 'active' : '' ?>" 
                               href="?controller=admin&action=emailTemplates">
                                <i class="fas fa-envelope"></i>
                                <span>Email Templates</span>
                            </a>
                        </li>
                        <?php if (User::hasAdminPermission($_SESSION['user_id'], 'preview_customer')): ?>
                        <li>
                            <a class="sidebar-sublink <?= isNavActive('admin', 'previewFacilities', $currentController, $currentAction) ? 'active' : '' ?>" 
                               href="?controller=admin&action=previewFacilities">
                                <i class="fas fa-eye"></i>
                                <span>Preview Customer</span>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </li>
            <?php endif; ?>

            <?php // Sub-Admin Standalone Links ?>
            <?php if (!$isStaffRole && User::hasAdminPermission($_SESSION['user_id'], 'preview_customer') && !$isMainAdmin): ?>
            <li class="sidebar-item">
                <a class="sidebar-link <?= isNavActive('admin', 'previewFacilities', $currentController, $currentAction) ? 'active' : '' ?>"
                   href="?controller=admin&action=previewFacilities">
                    <i class="fas fa-eye"></i>
                    <span>Preview Customer</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if ((!$isStaffRole && User::hasAdminPermission($_SESSION['user_id'], 'feedback_view') && !$hasReportsAccess) || $isStaffRole): ?>
            <li class="sidebar-item">
                <a class="sidebar-link <?= isNavActive('feedback', 'listAllFeedback', $currentController, $currentAction) ? 'active' : '' ?>"
                   href="?controller=feedback&action=listAllFeedback">
                    <i class="fas fa-comments"></i>
                    <span>View Feedback</span>
                </a>
            </li>
            <?php endif; ?>
        </ul>

        <!-- Sidebar Divider -->
        <div class="sidebar-divider"></div>

        <!-- User Section -->
        <ul class="sidebar-menu sidebar-menu-bottom">
            <li class="sidebar-item">
                <a class="sidebar-link <?= isNavActive('user', 'profile', $currentController, $currentAction) ? 'active' : '' ?>" 
                   href="?controller=user&action=profile">
                    <i class="fas fa-user"></i>
                    <span>My Profile</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a class="sidebar-link" href="?controller=user&action=logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>

        <!-- Theme Switcher -->
        <div class="sidebar-theme-switcher">
            <div class="theme-switcher" id="sidebar-theme-switcher">
                <div class="theme-switcher-slider"></div>
                <i class="fas fa-sun"></i>
                <i class="fas fa-moon"></i>
            </div>
            <span class="theme-label">Theme</span>
        </div>
    </nav>
</aside>