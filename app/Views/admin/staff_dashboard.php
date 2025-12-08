<?php
// Enforce staff-only access
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Staff', 'Admin'])) {
    // Redirect to a 403 Forbidden page or the login page
    header('HTTP/1.0 403 Forbidden');
    include __DIR__ . '/../errors/403.php';
    exit();
}

// Get the current user's role and set dynamic title
$currentUser = User::findById($_SESSION['user_id']);
if ($_SESSION['role'] === 'Admin' && isset($currentUser['AdminType'])) {
    $adminTypeDisplay = User::getAdminTypeDisplay($currentUser['AdminType']);
    $pageTitle = $adminTypeDisplay . " Dashboard";
    $cardTitle = $adminTypeDisplay . " Dashboard";
} else {
    $pageTitle = "Staff Dashboard";
    $cardTitle = "Staff Dashboard";
}
require_once __DIR__ . '/../partials/header.php';
?>

<div class="row mb-3">
    <div class="col-md-4">
        <form action="" method="GET" id="resortFilterForm">
            <input type="hidden" name="controller" value="admin">
            <input type="hidden" name="action" value="staffDashboard">
            <select name="resort_id" class="form-select" onchange="this.form.submit()">
                <?php if ($allResortsAssigned): ?>
                    <option value="">All Resorts</option>
                <?php endif; ?>
                <?php foreach ($resorts as $resort): ?>
                    <option value="<?= $resort->ResortID ?? $resort->resortId ?>" <?= (isset($_GET['resort_id']) && $_GET['resort_id'] == ($resort->ResortID ?? $resort->resortId)) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($resort->Name ?? $resort->name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
</div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?= htmlspecialchars($cardTitle) ?></h3>
        </div>
        <div class="card-body">
            <h4 class="mb-4">Today's Bookings (<?= date('F j, Y') ?>)</h4>
            
            <?php if (empty($todaysBookings)): ?>
                <div class="alert alert-info">No bookings scheduled for today.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Customer</th>
                                <th>Resort</th>
                                <th>Facilities</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($todaysBookings as $booking): ?>
                                <?php
                                $statusColors = [
                                    'Pending' => 'bg-warning text-dark',
                                    'Confirmed' => 'bg-success',
                                    'Cancelled' => 'bg-danger',
                                    'Completed' => 'bg-primary'
                                ];
                                $statusClass = $statusColors[$booking->Status] ?? 'bg-secondary';

                                $timeSlotDisplay = [
                                    '12_hours' => '12 Hours (7:00 AM - 5:00 PM)',
                                    'overnight' => 'Overnight (7:00 PM - 5:00 AM)',
                                    '24_hours' => '24 Hours (7:00 AM - 5:00 AM)'
                                ];
                                $timeDisplay = $timeSlotDisplay[$booking->TimeSlotType] ?? 'N/A';
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($timeDisplay) ?></td>
                                    <td><?= htmlspecialchars($booking->CustomerName) ?></td>
                                    <td class="fw-bold"><?= htmlspecialchars($booking->ResortName) ?></td>
                                    <td>
                                        <?php if (!empty($booking->FacilityNames)): ?>
                                            <span class="badge bg-info text-dark"><?= htmlspecialchars($booking->FacilityNames) ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Resort access only</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge <?= $statusClass ?>">
                                            <?= htmlspecialchars($booking->Status) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <h4 class="mb-4 mt-5">Upcoming Bookings</h4>
            
            <?php if (empty($upcomingBookings)): ?>
                <div class="alert alert-info">No upcoming bookings found.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Customer</th>
                                <th>Resort</th>
                                <th>Facilities</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="upcoming-bookings-tbody">
                            <?php foreach ($upcomingBookings as $index => $booking): ?>
                                <?php
                                $statusColors = [
                                    'Pending' => 'bg-warning text-dark',
                                    'Confirmed' => 'bg-success',
                                    'Cancelled' => 'bg-danger',
                                    'Completed' => 'bg-primary'
                                ];
                                $statusClass = $statusColors[$booking->Status] ?? 'bg-secondary';

                                $timeSlotDisplay = [
                                    '12_hours' => '12 Hours (7:00 AM - 5:00 PM)',
                                    'overnight' => 'Overnight (7:00 PM - 5:00 AM)',
                                    '24_hours' => '24 Hours (7:00 AM - 5:00 AM)'
                                ];
                                $timeDisplay = $timeSlotDisplay[$booking->TimeSlotType] ?? 'N/A';
                                ?>
                                <tr class="booking-row" style="<?= $index >= 10 ? 'display: none;' : '' ?>">
                                    <td><?= htmlspecialchars(date('M j, Y', strtotime($booking->BookingDate))) ?></td>
                                    <td><?= htmlspecialchars($timeDisplay) ?></td>
                                    <td><?= htmlspecialchars($booking->CustomerName) ?></td>
                                    <td class="fw-bold"><?= htmlspecialchars($booking->ResortName) ?></td>
                                    <td>
                                        <?php if (!empty($booking->FacilityNames)): ?>
                                            <span class="badge bg-info text-dark"><?= htmlspecialchars($booking->FacilityNames) ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Resort access only</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge <?= $statusClass ?>">
                                            <?= htmlspecialchars($booking->Status) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($booking->Status === 'Confirmed' || $booking->Status === 'Pending'): ?>
                                            <button type="button" class="btn btn-warning btn-sm"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#staffRescheduleModal"
                                                    data-booking-id="<?= $booking->BookingID ?>"
                                                    data-booking-date="<?= $booking->BookingDate ?>"
                                                    data-time-slot="<?= $booking->TimeSlotType ?>"
                                                    data-customer-name="<?= htmlspecialchars($booking->CustomerName) ?>"
                                                    data-resort-name="<?= htmlspecialchars($booking->ResortName) ?>">
                                                <i class="fas fa-calendar-alt"></i> Request Reschedule
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (count($upcomingBookings) > 10): ?>
                <div class="text-center mt-3">
                    <button id="view-more-btn" class="btn btn-primary">View More</button>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const viewMoreBtn = document.getElementById('view-more-btn');
    if (viewMoreBtn) {
        viewMoreBtn.addEventListener('click', function() {
            const hiddenRows = document.querySelectorAll('#upcoming-bookings-tbody .booking-row[style*="display: none;"]');
            hiddenRows.forEach(row => {
                row.style.display = '';
            });
            viewMoreBtn.style.display = 'none'; // Hide the button after showing all rows
        });
    }
});

// Handle staff reschedule modal - must be inside DOMContentLoaded
document.addEventListener('DOMContentLoaded', function() {
    const staffRescheduleModal = document.getElementById('staffRescheduleModal');
    if (staffRescheduleModal) {
        staffRescheduleModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const bookingId = button.getAttribute('data-booking-id');
            const bookingDate = button.getAttribute('data-booking-date');
            const timeSlot = button.getAttribute('data-time-slot');
            const customerName = button.getAttribute('data-customer-name');
            const resortName = button.getAttribute('data-resort-name');

            console.log('Staff reschedule modal - Booking ID:', bookingId); // Debug log

            document.getElementById('staffRescheduleBookingId').value = bookingId;
            
            const timeSlotDisplay = {
                '12_hours': '12 Hours (7:00 AM - 5:00 PM)',
                'overnight': 'Overnight (7:00 PM - 5:00 AM)',
                '24_hours': '24 Hours (7:00 AM - 5:00 AM Next Day)'
            };
            
            document.getElementById('staffCurrentBookingInfo').innerHTML = `
                <strong>Customer:</strong> ${customerName}<br>
                <strong>Resort:</strong> ${resortName}<br>
                <strong>Date:</strong> ${new Date(bookingDate).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}<br>
                <strong>Time Slot:</strong> ${timeSlotDisplay[timeSlot] || timeSlot}
            `;
        });
    }
});
</script>

<!-- Staff Reschedule Request Modal -->
<div class="modal fade" id="staffRescheduleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="?controller=user&action=submitRescheduleRequest">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-calendar-alt"></i> Request Booking Reschedule</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="staffRescheduleBookingId" name="booking_id">
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> <strong>Current Booking:</strong>
                        <div id="staffCurrentBookingInfo" class="mt-2"></div>
                    </div>

                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> Your reschedule request will be reviewed by an admin. The customer will be notified once it's approved or rejected.
                    </div>

                    <div class="mb-3">
                        <label for="staffRequestedDate" class="form-label">Requested New Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="staffRequestedDate" name="requested_date" required min="<?= date('Y-m-d') ?>">
                    </div>

                    <div class="mb-3">
                        <label for="staffRequestedTimeSlot" class="form-label">Requested Time Slot <span class="text-danger">*</span></label>
                        <select class="form-select" id="staffRequestedTimeSlot" name="requested_time_slot" required>
                            <option value="">Select time slot</option>
                            <option value="12_hours">12 Hours (7:00 AM - 5:00 PM)</option>
                            <option value="overnight">Overnight (7:00 PM - 5:00 AM)</option>
                            <option value="24_hours">24 Hours (7:00 AM - 5:00 AM Next Day)</option>
                        </select>
                        <small class="text-muted">Note: Availability will be checked by admin before approval</small>
                    </div>

                    <div class="mb-3">
                        <label for="staffRescheduleReason" class="form-label">Reason for Reschedule <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="staffRescheduleReason" name="reason" rows="3" placeholder="Please explain why this booking needs to be rescheduled..." required></textarea>
                        <small class="text-muted">This reason will be visible to the admin and customer</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Submit Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>

<script>
// AJAX submit for staff reschedule modal
document.addEventListener('DOMContentLoaded', function() {
    const staffRescheduleModal = document.getElementById('staffRescheduleModal');
    const staffRescheduleForm = document.querySelector('#staffRescheduleModal form');
    
    if (staffRescheduleForm) {
        staffRescheduleForm.addEventListener('submit', function (e) {
            // Allow normal form submission - don't prevent default
            // e.preventDefault();
            return true; // Let form submit normally
            
            /* AJAX code disabled - using normal form submission
            const form = this;
            const submitBtn = form.querySelector('button[type="submit"]');
            const data = new FormData(form);

            submitBtn.disabled = true;

            // Use the form's action attribute directly (browsers auto-resolve it to absolute URL)
            const actionUrl = form.action;
            
            console.log('Submitting staff reschedule to:', actionUrl);

            fetch(actionUrl, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: data
            }).then(r => {
                console.log('Response status:', r.status, r.statusText);
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.text().then(text => {
                    console.log('Response text:', text);
                    return text ? JSON.parse(text) : {};
                });
            }).then(json => {
                console.log('Parsed JSON:', json);
                if (json.success) {
                    // show success alert on page
                    const alert = document.createElement('div');
                    alert.className = 'alert alert-success alert-dismissible fade show mt-3';
                    alert.role = 'alert';
                    alert.innerHTML = (json.message || 'Request submitted') + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                    document.querySelector('.container-fluid')?.prepend(alert);
                    // close modal
                    if (staffRescheduleModal) {
                        const modalObj = bootstrap.Modal.getInstance(staffRescheduleModal);
                        if (modalObj) modalObj.hide();
                    }

                    // Dispatch custom event so sidebar badge updates
                    try {
                        var pending = null;
                        if (typeof json.pendingCount !== 'undefined') {
                            pending = parseInt(json.pendingCount, 10) || 0;
                        } else {
                            var badge = document.getElementById('rescheduleBadge');
                            if (badge) {
                                var cur = parseInt(badge.dataset.count || badge.textContent || '0', 10) || 0;
                                pending = cur + 1;
                            }
                        }
                        window.dispatchEvent(new CustomEvent('reschedule:submitted', { detail: { pendingCount: pending } }));
                    } catch (err) {
                        console.warn('Could not dispatch reschedule event', err);
                    }
                } else {
                    const alert = document.createElement('div');
                    alert.className = 'alert alert-danger alert-dismissible fade show mt-3';
                    alert.role = 'alert';
                    alert.innerHTML = (json.error || 'Failed to submit') + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                    document.querySelector('.container-fluid')?.prepend(alert);
                }
            }).catch(err => {
                console.error('Reschedule request error:', err);
                const alert = document.createElement('div');
                alert.className = 'alert alert-danger alert-dismissible fade show mt-3';
                alert.role = 'alert';
                alert.innerHTML = 'Network error: ' + err.message + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                document.querySelector('.container-fluid')?.prepend(alert);
            }).finally(() => { submitBtn.disabled = false; });
            */
        });
    }
});
</script>
