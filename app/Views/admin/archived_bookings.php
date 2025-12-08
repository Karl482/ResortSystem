<?php
// Enforce admin-only access
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header('HTTP/1.0 403 Forbidden');
    include __DIR__ . '/../errors/403.php';
    exit();
}

$pageTitle = "Archived Bookings Management";
require_once __DIR__ . '/../partials/header.php';
?>

<style>
tr[id^="booking-row-"] {
    scroll-margin-top: 80px;
}
</style>

<div class="container-fluid mt-4">
    <!-- Session Messages -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['success_message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['error_message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h3><i class="fas fa-archive"></i> Archived Bookings Management (<?= count($bookings) ?>)</h3>
                    <div class="d-flex gap-2 flex-wrap justify-content-end">
                        <a href="?controller=admin&action=unifiedBookingManagement<?php echo isset($_GET['resort_id']) ? '&resort_id=' . urlencode($_GET['resort_id']) : ''; ?>" class="btn btn-primary">
                            <i class="fas fa-arrow-left"></i> Back to Active Bookings
                        </a>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card-body border-bottom">
                    <form id="filterForm" method="GET" class="row g-3 align-items-end">
                        <input type="hidden" name="controller" value="admin">
                        <input type="hidden" name="action" value="archivedBookings">
                        
                        <div class="col-lg-3 col-md-4">
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
                        
                        <div class="col-lg-3 col-md-4">
                            <label class="form-label">Customer Search</label>
                            <input type="text" name="customer_name_search" id="customerSearchInput" class="form-control" placeholder="Search customer name..." value="<?= htmlspecialchars($_GET['customer_name_search'] ?? '') ?>">
                        </div>
                        
                        <div class="col-lg-2 col-md-4">
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

                        <div class="col-lg-2 col-md-4">
                            <label class="form-label">Year</label>
                            <select name="year" class="form-select">
                                <option value="">Any</option>
                                <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                                    <option value="<?= $y ?>" <?= (isset($_GET['year']) && $_GET['year'] == $y) ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <div class="col-lg-2 col-md-12 d-flex align-items-end mt-3 mt-lg-0">
                            <a href="?controller=admin&action=archivedBookings" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-times"></i> Clear Filters
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Bookings Table -->
                <div class="card-body">
                    <?php if (empty($bookings)): ?>
                        <div class="text-center p-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">No Archived Bookings</h4>
                            <p class="text-muted">No bookings have been archived yet.</p>
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
                                        <th>Archived Date</th>
                                        <th>Actions</th>
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
                                                        <span class="badge <?php
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
                                                <span class="badge bg-secondary">
                                                    <i class="fas fa-archive"></i> Archived
                                                </span>
                                            </td>
                                            <td>
                                                <small class="text-muted"><?= date('M j, Y', strtotime($booking->CreatedAt)) ?></small>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="fas fa-cog"></i> Actions
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li>
                                                            <button class="dropdown-item" type="button" onclick="unarchiveBooking(<?= $booking->BookingID ?>, 'Completed')">
                                                                <i class="fas fa-undo fa-fw me-2"></i>Restore as Completed
                                                            </button>
                                                        </li>
                                                        <li>
                                                            <button class="dropdown-item" type="button" onclick="unarchiveBooking(<?= $booking->BookingID ?>, 'Confirmed')">
                                                                <i class="fas fa-undo fa-fw me-2"></i>Restore as Confirmed
                                                            </button>
                                                        </li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <button class="dropdown-item" type="button" onclick="showAuditTrail(<?= $booking->BookingID ?>)">
                                                                <i class="fas fa-history fa-fw me-2"></i>View Audit Trail
                                                            </button>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($pagination['total_pages'] > 1): ?>
                            <div class="d-flex justify-content-between align-items-center mt-3 px-3 pb-3">
                                <div class="text-muted">
                                    Showing <?= count($bookings) ?> of <?= $pagination['total_items'] ?> archived bookings
                                    (Page <?= $pagination['current_page'] ?> of <?= $pagination['total_pages'] ?>)
                                </div>
                                <nav aria-label="Archived booking pagination">
                                    <ul class="pagination mb-0">
                                        <!-- First Page -->
                                        <?php if ($pagination['current_page'] > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="<?= buildPaginationUrl(1) ?>" aria-label="First">
                                                    <span aria-hidden="true">&laquo;&laquo;</span>
                                                </a>
                                            </li>
                                            <li class="page-item">
                                                <a class="page-link" href="<?= buildPaginationUrl($pagination['current_page'] - 1) ?>" aria-label="Previous">
                                                    <span aria-hidden="true">&laquo;</span>
                                                </a>
                                            </li>
                                        <?php else: ?>
                                            <li class="page-item disabled">
                                                <span class="page-link">&laquo;&laquo;</span>
                                            </li>
                                            <li class="page-item disabled">
                                                <span class="page-link">&laquo;</span>
                                            </li>
                                        <?php endif; ?>

                                        <!-- Page Numbers -->
                                        <?php
                                        $startPage = max(1, $pagination['current_page'] - 2);
                                        $endPage = min($pagination['total_pages'], $pagination['current_page'] + 2);
                                        
                                        for ($i = $startPage; $i <= $endPage; $i++):
                                        ?>
                                            <li class="page-item <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                                                <a class="page-link" href="<?= buildPaginationUrl($i) ?>"><?= $i ?></a>
                                            </li>
                                        <?php endfor; ?>

                                        <!-- Next/Last Page -->
                                        <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="<?= buildPaginationUrl($pagination['current_page'] + 1) ?>" aria-label="Next">
                                                    <span aria-hidden="true">&raquo;</span>
                                                </a>
                                            </li>
                                            <li class="page-item">
                                                <a class="page-link" href="<?= buildPaginationUrl($pagination['total_pages']) ?>" aria-label="Last">
                                                    <span aria-hidden="true">&raquo;&raquo;</span>
                                                </a>
                                            </li>
                                        <?php else: ?>
                                            <li class="page-item disabled">
                                                <span class="page-link">&raquo;</span>
                                            </li>
                                            <li class="page-item disabled">
                                                <span class="page-link">&raquo;&raquo;</span>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            </div>

                            <!-- Per Page Selector -->
                            <div class="px-3 pb-3">
                                <div class="d-flex align-items-center gap-2">
                                    <label for="perPageSelect" class="mb-0 text-muted small">Items per page:</label>
                                    <select id="perPageSelect" class="form-select form-select-sm" style="width: auto;" onchange="changePerPage(this.value)">
                                        <option value="10" <?= $pagination['per_page'] == 10 ? 'selected' : '' ?>>10</option>
                                        <option value="20" <?= $pagination['per_page'] == 20 ? 'selected' : '' ?>>20</option>
                                        <option value="50" <?= $pagination['per_page'] == 50 ? 'selected' : '' ?>>50</option>
                                        <option value="100" <?= $pagination['per_page'] == 100 ? 'selected' : '' ?>>100</option>
                                    </select>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Helper function to build pagination URLs preserving filters
function buildPaginationUrl($page) {
    $params = $_GET;
    $params['page'] = $page;
    return '?' . http_build_query($params);
}
?>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>

<script>
function changePerPage(perPage) {
    const params = new URLSearchParams(window.location.search);
    params.set('per_page', perPage);
    params.set('page', 1); // Reset to first page when changing per page
    window.location.search = params.toString();
}

// Auto-submit filter form on change

<script>
// Auto-submit filter form on change
document.querySelectorAll('#filterForm select, #filterForm input').forEach(element => {
    element.addEventListener('change', function() {
        document.getElementById('filterForm').submit();
    });
});

// Unarchive booking function
function unarchiveBooking(bookingId, newStatus) {
    if (confirm(`Are you sure you want to restore this booking to ${newStatus} status?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '?controller=admin&action=unarchiveBooking';
        
        const bookingInput = document.createElement('input');
        bookingInput.type = 'hidden';
        bookingInput.name = 'booking_id';
        bookingInput.value = bookingId;
        
        const statusInput = document.createElement('input');
        statusInput.type = 'hidden';
        statusInput.name = 'new_status';
        statusInput.value = newStatus;
        
        form.appendChild(bookingInput);
        form.appendChild(statusInput);
        document.body.appendChild(form);
        form.submit();
    }
}

// Show audit trail function
function showAuditTrail(bookingId) {
    fetch(`?controller=admin&action=getAuditTrail&booking_id=${bookingId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let html = '<div class="timeline">';
                if (data.trail && data.trail.length > 0) {
                    data.trail.forEach(entry => {
                        html += `
                            <div class="timeline-item mb-3">
                                <div class="d-flex justify-content-between">
                                    <strong>${entry.Action}</strong>
                                    <small class="text-muted">${formatDateTime(entry.Timestamp)}</small>
                                </div>
                                <div class="text-muted small">By: ${entry.Username || 'System'}</div>
                                <div>${entry.Description}</div>
                            </div>
                        `;
                    });
                } else {
                    html += '<p class="text-muted">No audit trail available.</p>';
                }
                html += '</div>';
                
                // Create and show modal
                const modalHtml = `
                    <div class="modal fade" id="auditModal" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title"><i class="fas fa-history"></i> Booking Audit Trail</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">${html}</div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                // Remove existing modal if any
                const existingModal = document.getElementById('auditModal');
                if (existingModal) {
                    existingModal.remove();
                }
                
                document.body.insertAdjacentHTML('beforeend', modalHtml);
                const modal = new bootstrap.Modal(document.getElementById('auditModal'));
                modal.show();
            } else {
                alert('Failed to load audit trail: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading audit trail');
        });
}

function formatDateTime(dateTime) {
    return new Date(dateTime).toLocaleString();
}
</script>
