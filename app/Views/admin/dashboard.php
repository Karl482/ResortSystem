<?php
// Enforce admin-only access
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    // Redirect to a 403 Forbidden page or the login page
    header('HTTP/1.0 403 Forbidden');
    include __DIR__ . '/../errors/403.php';
    exit();
}

// Get the current admin's type and set dynamic title
$currentUser = User::findById($_SESSION['user_id']);
$adminTypeDisplay = User::getAdminTypeDisplay($currentUser['AdminType']);
$pageTitle = $adminTypeDisplay . " Dashboard";
require_once __DIR__ . '/../partials/header.php';
?>

    <div class="row mb-3">
        <div class="col-md-4">
            <form action="" method="GET" id="resortFilterForm">
                <input type="hidden" name="controller" value="admin">
                <input type="hidden" name="action" value="dashboard">
                <select name="resort_id" class="form-select" onchange="this.form.submit()">
                    <option value="">All Resorts</option>
                    <?php foreach ($resorts as $resort): ?>
                        <option value="<?= $resort->resortId ?>" <?= (isset($_GET['resort_id']) && $_GET['resort_id'] == $resort->resortId) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($resort->name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
    </div>

    <!-- Phase 5: Quick Admin Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-tools"></i> Quick Management Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <a href="?controller=admin&action=unifiedBookingManagement<?php echo isset($_GET['resort_id']) ? '&resort_id=' . urlencode($_GET['resort_id']) : ''; ?>" class="btn btn-outline-primary w-100 mb-2 position-relative">
                                <?php if ($activeBookingCount > 0): ?>
                                    <span class="badge bg-info position-absolute top-0 start-100 translate-middle">
                                        <?php echo $activeBookingCount; ?>
                                        <span class="visually-hidden">active bookings</span>
                                    </span>
                                <?php endif; ?>
                                <i class="fas fa-calendar-check"></i><br>
                                <small>Unified Booking & Payment</small>
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="?controller=payment&action=showPendingPayments<?php echo isset($_GET['resort_id']) ? '&resort_id=' . urlencode($_GET['resort_id']) : ''; ?>" class="btn btn-outline-info w-100 mb-2 position-relative">
                                <?php if ($pendingPaymentCount > 0): ?>
                                    <span class="badge bg-danger position-absolute top-0 start-100 translate-middle">
                                        <?php echo $pendingPaymentCount; ?>
                                        <span class="visually-hidden">pending payments</span>
                                    </span>
                                <?php endif; ?>
                                <i class="fas fa-credit-card"></i><br>
                                <small>Payment Verification</small>
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="?controller=admin&action=pricingManagement" class="btn btn-outline-success w-100 mb-2">
                                <i class="fas fa-tags"></i><br>
                                <small>Pricing Management</small>
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="?controller=admin&action=advancedBlocking" class="btn btn-outline-warning w-100 mb-2">
                                <i class="fas fa-ban"></i><br>
                                <small>Advanced Blocking</small>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row d-flex align-items-stretch">
        <div class="col-md-3">
            <div class="card text-white bg-primary mb-3 h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="text-white">Monthly Income</span>
                    <a href="?controller=admin&action=incomeAnalytics<?php echo isset($_GET['resort_id']) ? '&resort_id=' . urlencode($_GET['resort_id']) : ''; ?>" class="btn btn-sm btn-light">
                        <i class="fas fa-chart-line"></i> Analytics
                    </a>
                </div>
                <div class="card-body d-flex flex-column">
                    <h4 class="card-title">₱<?= number_format($monthlyIncome, 2) ?></h4>
                    <p class="card-text">Income for <?= date('F Y') ?></p>
                    <div class="mt-auto">
                        <canvas id="monthlyIncomeChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-9">
            <div class="card h-100">
                <div class="card-header">
                    <h3 class="card-title"><?= htmlspecialchars($adminTypeDisplay) ?> Dashboard</h3>
                </div>
                <div class="card-body">
                    <h4 class="mb-4">Today's Bookings (<?= date('F j, Y') ?>)</h4>
                    
                    <?php if (empty($todaysBookings)): ?>
                        <div class="alert alert-info">No bookings scheduled for today.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-primary">
                                    <tr>
                                        <th>Booking ID</th>
                                        <th>Time</th>
                                        <th>Customer</th>
                                        <th>Resort</th>
                                        <th>Facilities</th>
                                        <th>Total</th>
                                        <th>Booking Status</th>
                                        <th>Payment Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($todaysBookings as $booking): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($booking->BookingID) ?></td>
                                            <td><?= htmlspecialchars(Booking::getTimeSlotDisplay($booking->TimeSlotType)) ?></td>
                                            <td><?= htmlspecialchars($booking->CustomerName) ?></td>
                                            <td><strong><?= htmlspecialchars($booking->ResortName ?? 'Unknown') ?></strong></td>
                                            <td>
                                                <?php if (!empty($booking->FacilityNames)): ?>
                                                    <span class="badge bg-info text-dark"><?= htmlspecialchars($booking->FacilityNames) ?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Resort only</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($booking->TotalAmount)): ?>
                                                    <strong>₱<?= number_format($booking->TotalAmount, 2) ?></strong>
                                                    <?php if (!empty($booking->RemainingBalance) && $booking->RemainingBalance > 0): ?>
                                                        <br><small class="text-warning">Bal: ₱<?= number_format($booking->RemainingBalance, 2) ?></small>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge
                                                    <?php
                                                        switch ($booking->Status) {
                                                            case 'Confirmed': echo 'bg-success'; break;
                                                            case 'Pending': echo 'bg-warning text-dark'; break;
                                                            case 'Cancelled': echo 'bg-danger'; break;
                                                            default: echo 'bg-secondary';
                                                        }
                                                    ?>">
                                                    <?= htmlspecialchars($booking->Status) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge
                                                    <?php
                                                        switch ($booking->PaymentStatus) {
                                                            case 'Paid': echo 'bg-success'; break;
                                                            case 'Partial': echo 'bg-warning text-dark'; break;
                                                            case 'Unpaid': echo 'bg-danger'; break;
                                                            default: echo 'bg-secondary';
                                                        }
                                                    ?>">
                                                    <?= htmlspecialchars($booking->PaymentStatus) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="?controller=admin&action=unifiedBookingManagement&booking_id=<?= $booking->BookingID ?>#booking-row-<?= $booking->BookingID ?>" class="btn btn-primary btn-sm">View Details</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Upcoming Bookings</h4>
                </div>
                <div class="card-body">
                    <?php if (empty($upcomingBookings)): ?>
                        <div class="alert alert-info">No upcoming bookings found.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-info">
                                    <tr>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Customer</th>
                                        <th>Resort</th>
                                        <th>Facilities</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Payment Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($upcomingBookings as $booking): ?>
                                        <tr>
                                            <td><?= htmlspecialchars(date('M j, Y', strtotime($booking->BookingDate))) ?></td>
                                            <td><?= htmlspecialchars(Booking::getTimeSlotDisplay($booking->TimeSlotType)) ?></td>
                                            <td><?= htmlspecialchars($booking->CustomerName) ?></td>
                                            <td><strong><?= htmlspecialchars($booking->ResortName ?? 'Unknown') ?></strong></td>
                                            <td>
                                                <?php if (!empty($booking->FacilityNames)): ?>
                                                    <span class="badge bg-info text-dark"><?= htmlspecialchars($booking->FacilityNames) ?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Resort only</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($booking->TotalAmount)): ?>
                                                    <strong>₱<?= number_format($booking->TotalAmount, 2) ?></strong>
                                                    <?php if (!empty($booking->RemainingBalance) && $booking->RemainingBalance > 0): ?>
                                                        <br><small class="text-warning">Bal: ₱<?= number_format($booking->RemainingBalance, 2) ?></small>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge
                                                    <?php
                                                        switch ($booking->Status) {
                                                            case 'Confirmed': echo 'bg-success'; break;
                                                            case 'Pending': echo 'bg-warning text-dark'; break;
                                                            case 'Cancelled': echo 'bg-danger'; break; // This should not happen for upcoming
                                                            default: echo 'bg-secondary';
                                                        }
                                                    ?>">
                                                    <?= htmlspecialchars($booking->Status) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge
                                                    <?php
                                                        switch ($booking->PaymentStatus) {
                                                            case 'Paid': echo 'bg-success'; break;
                                                            case 'Partial': echo 'bg-warning text-dark'; break;
                                                            case 'Unpaid': echo 'bg-danger'; break;
                                                            default: echo 'bg-secondary';
                                                        }
                                                    ?>">
                                                    <?= htmlspecialchars($booking->PaymentStatus) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="?controller=admin&action=unifiedBookingManagement&booking_id=<?= $booking->BookingID ?>#booking-row-<?= $booking->BookingID ?>" class="btn btn-primary btn-sm">View Details</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer text-center">
                    <a href="?controller=admin&action=unifiedBookingManagement&status=Confirmed" class="btn btn-outline-primary btn-sm">View More Upcoming Bookings</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Recent Booking History</h4>
                </div>
                <div class="card-body">
                    <?php if (empty($bookingHistory)): ?>
                        <div class="alert alert-info">No past bookings found.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-secondary">
                                    <tr>
                                        <th>Booking ID</th>
                                        <th>Date</th>
                                        <th>Customer</th>
                                        <th>Resort</th>
                                        <th>Facilities</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bookingHistory as $booking): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($booking->BookingID) ?></td>
                                            <td><?= htmlspecialchars(date('M j, Y', strtotime($booking->BookingDate))) ?></td>
                                            <td><?= htmlspecialchars($booking->CustomerName) ?></td>
                                            <td><strong><?= htmlspecialchars($booking->ResortName ?? 'Unknown') ?></strong></td>
                                            <td>
                                                <?php if (!empty($booking->FacilityNames)): ?>
                                                    <span class="badge bg-info text-dark"><?= htmlspecialchars($booking->FacilityNames) ?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Resort only</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                 <span class="badge
                                                    <?php
                                                        switch ($booking->Status) {
                                                            case 'Completed': echo 'bg-info'; break;
                                                            case 'Cancelled': echo 'bg-danger'; break;
                                                            default: echo 'bg-secondary';
                                                        }
                                                    ?>">
                                                    <?= htmlspecialchars($booking->Status) ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer text-center">
                    <a href="?controller=admin&action=unifiedBookingManagement&status=Completed" class="btn btn-outline-secondary btn-sm">View More Booking History</a>
                </div>
            </div>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('monthlyIncomeChart').getContext('2d');
    
    // Prepare data from PHP
    const dailyData = <?= json_encode(array_values($dailyIncomeData)) ?>;
    const labels = <?= json_encode(array_keys($dailyIncomeData)) ?>;
    
    const monthlyIncomeChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Daily Income',
                data: dailyData,
                backgroundColor: 'rgba(255, 255, 255, 0.6)',
                borderColor: 'rgba(255, 255, 255, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false // Hide legend for a cleaner look
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                label += new Intl.NumberFormat('en-US', { style: 'currency', currency: 'PHP' }).format(context.parsed.y);
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: 'white',
                        callback: function(value, index, values) {
                            return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'PHP', notation: 'compact' }).format(value);
                        }
                    },
                    grid: {
                        color: 'rgba(255, 255, 255, 0.2)'
                    }
                },
                x: {
                    ticks: {
                        color: 'white'
                    },
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
