<?php
$pageTitle = "Operational Reports";
require_once __DIR__ . '/../partials/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3><i class="fas fa-file-alt"></i> Operational Reports</h3>
                </div>

                <!-- Filters -->
                <div class="card-body border-bottom">
                    <form method="GET" class="row g-3 align-items-end">
                        <input type="hidden" name="controller" value="admin">
                        <input type="hidden" name="action" value="operationalReports">
                        
                        <div class="col-lg-2 col-md-4">
                            <label class="form-label">Resort</label>
                            <select name="resort_id" class="form-select">
                                <option value="">All Resorts</option>
                                <?php foreach ($resorts as $resort): ?>
                                    <option value="<?= $resort->resortId ?>" <?= (isset($_GET['resort_id']) && $_GET['resort_id'] == $resort->resortId) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($resort->name) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-lg-2 col-md-4">
                            <label class="form-label">Booking Status</label>
                            <select name="status" class="form-select">
                                <option value="">All</option>
                                <option value="Pending" <?= (isset($_GET['status']) && $_GET['status'] == 'Pending') ? 'selected' : '' ?>>Pending</option>
                                <option value="Confirmed" <?= (isset($_GET['status']) && $_GET['status'] == 'Confirmed') ? 'selected' : '' ?>>Confirmed</option>
                                <option value="Completed" <?= (isset($_GET['status']) && $_GET['status'] == 'Completed') ? 'selected' : '' ?>>Completed</option>
                                <option value="Cancelled" <?= (isset($_GET['status']) && $_GET['status'] == 'Cancelled') ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                        </div>

                        <div class="col-lg-2 col-md-4">
                            <label class="form-label">Payment Status</label>
                            <select name="payment_status" class="form-select">
                                <option value="">All</option>
                                <option value="Paid" <?= (isset($_GET['payment_status']) && $_GET['payment_status'] == 'Paid') ? 'selected' : '' ?>>Paid</option>
                                <option value="Partial" <?= (isset($_GET['payment_status']) && $_GET['payment_status'] == 'Partial') ? 'selected' : '' ?>>Partial</option>
                                <option value="Unpaid" <?= (isset($_GET['payment_status']) && $_GET['payment_status'] == 'Unpaid') ? 'selected' : '' ?>>Unpaid</option>
                            </select>
                        </div>
                        
                        <div class="col-lg-2 col-md-4">
                            <label class="form-label">Customer Search</label>
                            <input type="text" name="customer_name_search" id="customerSearchInput" class="form-control" placeholder="Search customer name..." value="<?= htmlspecialchars($_GET['customer_name_search'] ?? '') ?>">
                        </div>
                        
                        <div class="col-lg-1 col-md-4">
                            <label class="form-label">Month</label>
                            <select name="month" class="form-select">
                                <option value="">Any</option>
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?= $m ?>" <?= (isset($_GET['month']) && $_GET['month'] == $m) ? 'selected' : '' ?>>
                                        <?= date('M', mktime(0, 0, 0, $m, 10)) ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="col-lg-1 col-md-4">
                            <label class="form-label">Year</label>
                            <select name="year" class="form-select">
                                <option value="">Any</option>
                                <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                                    <option value="<?= $y ?>" <?= (isset($_GET['year']) && $_GET['year'] == $y) ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <div class="col-lg-2 col-md-12 d-flex align-items-end mt-3 mt-lg-0">
                            <button type="submit" class="btn btn-primary me-2 w-100">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                            <a href="?controller=admin&action=operationalReports" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i>
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Summary Cards -->
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="card text-white bg-primary mb-3">
                                <div class="card-body">
                                    <h5 class="card-title"><?= $totalBookings ?></h5>
                                    <p class="card-text">Total Bookings</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-white bg-success mb-3">
                                <div class="card-body">
                                    <h5 class="card-title">₱<?= number_format($totalRevenue, 2) ?></h5>
                                    <p class="card-text">Total Revenue</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h5 class="card-title">Booking Status Breakdown</h5>
                                    <div class="d-flex justify-content-around">
                                        <span><span class="badge bg-warning text-dark"><?= $statusCounts['Pending'] ?></span> Pending</span>
                                        <span><span class="badge bg-success"><?= $statusCounts['Confirmed'] ?></span> Confirmed</span>
                                        <span><span class="badge bg-info"><?= $statusCounts['Completed'] ?></span> Completed</span>
                                        <span><span class="badge bg-danger"><?= $statusCounts['Cancelled'] ?></span> Cancelled</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bookings Table -->
                <div class="card-body">
                    <?php if (empty($bookings)): ?>
                        <div class="text-center p-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">No bookings found</h4>
                            <p class="text-muted">No bookings match your current filters.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Date & Time</th>
                                        <th>Customer</th>
                                        <th>Resort</th>
                                        <th>Facilities</th>
                                        <th>Payment Info</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bookings as $booking): ?>
                                        <tr id="booking-row-<?= $booking->BookingID ?>">
                                            <td><strong><?= htmlspecialchars($booking->BookingID) ?></strong></td>
                                            <td>
                                                <div><?= date('M j, Y', strtotime($booking->BookingDate)) ?></div>
                                                <small class="text-muted"><?= htmlspecialchars($booking->TimeSlotType ?? 'N/A') ?></small>
                                            </td>
                                            <td>
                                                <div><?= htmlspecialchars($booking->CustomerName) ?></div>
                                                <small class="text-muted"><?= htmlspecialchars($booking->CustomerEmail) ?></small>
                                            </td>
                                            <td><strong><?= htmlspecialchars($booking->ResortName) ?></strong></td>
                                            <td>
                                                <?php if (!empty($booking->FacilityNames)): ?>
                                                    <span class="badge bg-info"><?= htmlspecialchars($booking->FacilityNames) ?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Resort only</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($booking->TotalAmount)): ?>
                                                    <div><strong>₱<?= number_format($booking->TotalAmount, 2) ?></strong></div>
                                                    <?php if (!empty($booking->RemainingBalance) && $booking->RemainingBalance > 0): ?>
                                                        <small class="text-warning">Bal: ₱<?= number_format($booking->RemainingBalance, 2) ?></small>
                                                    <?php endif; ?>
                                                    <div class="mt-1">
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
                                                    </div>
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
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Highlight and scroll to booking if a hash is present
    if (window.location.hash) {
        const hash = window.location.hash;
        const targetRow = document.querySelector(hash);
        if (targetRow) {
            // Add a temporary highlight class
            targetRow.classList.add('table-info');
            
            // Scroll to the element
            targetRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
            
            // Remove the highlight after a few seconds
            setTimeout(() => {
                targetRow.classList.remove('table-info');
            }, 3000);
        }
    }

    // Real-time Customer Search Filter
    const customerSearchInput = document.getElementById('customerSearchInput');
    const bookingTableBody = document.querySelector('.table-hover tbody');

    if (customerSearchInput && bookingTableBody) {
        customerSearchInput.addEventListener('input', function() {
            const searchTerm = this.value.trim().toLowerCase();
            const rows = bookingTableBody.querySelectorAll('tr'); // Select all trs in the tbody

            rows.forEach(row => {
                // Customer name/email is in the 3rd td (index 2)
                const customerCell = row.cells[2];
                if (customerCell) {
                    const customerText = customerCell.textContent.trim().toLowerCase();
                    
                    if (customerText.includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                }
            });
        });
    }
});
</script>