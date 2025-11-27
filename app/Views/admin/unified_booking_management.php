<?php
$pageTitle = "Unified Booking & Payment Management";
require_once __DIR__ . '/../partials/header.php';
?>

<style>
/* Adjust scroll target position to account for the sticky navigation bar */
tr[id^="booking-row-"] {
    scroll-margin-top: 80px; /* Provides top offset for scrolled-to elements */
}
</style>

<div class="container-fluid mt-4">
    <!-- Session Message Alerts -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['success_message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['error_message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3><i class="fas fa-calendar-check"></i> Unified Booking & Payment Management (<?= $activeBookingCount ?>)</h3>
                    <div class="d-flex gap-2">
                        <?php if ($pendingPaymentCount > 0): ?>
                            <a href="?controller=payment&action=showPendingPayments<?php echo isset($_GET['resort_id']) ? '&resort_id=' . urlencode($_GET['resort_id']) : ''; ?>" class="btn btn-warning">
                                <i class="fas fa-exclamation-circle"></i> <?= $pendingPaymentCount ?> Pending Payments
                            </a>
                        <?php endif; ?>
                        <a href="?controller=admin&action=dashboard<?php echo isset($_GET['resort_id']) ? '?resort_id=' . urlencode($_GET['resort_id']) : ''; ?>" class="btn btn-secondary">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card-body border-bottom">
                    <form method="GET" class="row g-3 align-items-end">
                        <input type="hidden" name="controller" value="admin">
                        <input type="hidden" name="action" value="unifiedBookingManagement">
                        
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
                            <a href="?controller=admin&action=unifiedBookingManagement" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i>
                            </a>
                        </div>
                         <div class="col-12 d-flex justify-content-end mt-2">
                            <a href="?controller=admin&action=showOnSiteBookingForm" class="btn btn-success">
                                <i class="fas fa-plus"></i> On-Site Booking
                            </a>
                        </div>
                    </form>
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
                                        <th>Audit & Payment Sched</th>
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
                                                <?php
                                                // Phase 6: Get lifecycle recommendations for this booking
                                                require_once __DIR__ . '/../../Models/BookingLifecycleManager.php';
                                                $recommendations = BookingLifecycleManager::getStatusRecommendations($booking->BookingID);
                                                ?>
                                                <?php if (!empty($recommendations)): ?>
                                                    <div class="mt-1">
                                                        <?php foreach ($recommendations as $rec): ?>
                                                            <span class="badge bg-info" title="<?= htmlspecialchars($rec['reason']) ?>">
                                                                <i class="fas fa-lightbulb"></i> Suggest: <?= $rec['recommended'] ?>
                                                            </span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <!-- Phase 6: Advanced Information Column -->
                                                <div class="small">
                                                    <div class="text-info mb-1" title="Payment Schedule">
                                                        <i class="fas fa-calendar-alt"></i>
                                                        <?= htmlspecialchars($booking->PaymentScheduleSummary) ?>
                                                    </div>
                                                    <div class="text-muted" title="Audit Trail">
                                                        <i class="fas fa-history"></i> <?= htmlspecialchars($booking->AuditTrailCount) ?> changes
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-column gap-1">
                                                    <div class="btn-group">
                                                        <button type="button" class="btn btn-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown" data-bs-strategy="fixed" aria-expanded="false">
                                                            <i class="fas fa-cog"></i> Actions
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                            <li>
                                                                <button class="dropdown-item" type="button" data-bs-toggle="modal" data-bs-target="#updateBookingModal" data-booking-id="<?= $booking->BookingID ?>" data-resort-id="<?= $booking->ResortID ?>">
                                                                    <i class="fas fa-edit fa-fw me-2"></i>Update
                                                                </button>
                                                            </li>
                                                            <li>
                                                                <button class="dropdown-item" type="button" data-bs-toggle="modal" data-bs-target="#paymentsModal" data-booking-id="<?= $booking->BookingID ?>">
                                                                    <i class="fas fa-credit-card fa-fw me-2"></i>Payments
                                                                </button>
                                                            </li>
                                                            <li>
                                                                <button class="dropdown-item" type="button" data-bs-toggle="modal" data-bs-target="#onSiteEditModal" data-booking-id="<?= $booking->BookingID ?>">
                                                                    <i class="fas fa-store fa-fw me-2"></i>On-Site Edit
                                                                </button>
                                                            </li>
                                                            <li>
                                                                <button class="dropdown-item" type="button" onclick="showAuditTrail(<?= $booking->BookingID ?>)">
                                                                    <i class="fas fa-history fa-fw me-2"></i>Audit
                                                                </button>
                                                            </li>
                                                        </ul>
                                                    </div>

                                                    <?php if (!empty($recommendations)): ?>
                                                        <?php if (count($recommendations) === 1): ?>
                                                            <?php $rec = $recommendations[0]; ?>
                                                            <button type="button" class="btn btn-outline-info btn-sm" onclick="applyRecommendation(<?= $booking->BookingID ?>, '<?= htmlspecialchars($rec['recommended'], ENT_QUOTES, 'UTF-8') ?>')" title="Suggest: <?= htmlspecialchars($rec['reason']) ?>">
                                                                <i class="fas fa-lightbulb"></i> Suggest
                                                            </button>
                                                        <?php else: ?>
                                                            <div class="btn-group">
                                                                <button type="button" class="btn btn-outline-info btn-sm dropdown-toggle" data-bs-toggle="dropdown" data-bs-strategy="fixed" aria-expanded="false">
                                                                    <i class="fas fa-lightbulb"></i> Suggestions
                                                                </button>
                                                                <ul class="dropdown-menu dropdown-menu-end">
                                                                    <?php foreach ($recommendations as $rec): ?>
                                                                    <li>
                                                                        <button class="dropdown-item" type="button" onclick="applyRecommendation(<?= $booking->BookingID ?>, '<?= htmlspecialchars($rec['recommended'], ENT_QUOTES, 'UTF-8') ?>')" title="<?= htmlspecialchars($rec['reason']) ?>">
                                                                            Suggest: <?= htmlspecialchars($rec['recommended']) ?>
                                                                        </button>
                                                                    </li>
                                                                    <?php endforeach; ?>
                                                                </ul>
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </div>
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


<!-- Phase 6: Audit Trail Modal -->
<div class="modal fade" id="auditTrailModal" tabindex="-1" aria-labelledby="auditTrailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="auditTrailModalLabel">
                    <i class="fas fa-history"></i> Booking Audit Trail
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="auditTrailContent">
                    <div class="text-center">
                        <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                        <p class="mt-2">Loading audit trail...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Phase 6: Payment Schedule Modal -->
<div class="modal fade" id="paymentScheduleModal" tabindex="-1" aria-labelledby="paymentScheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="paymentScheduleModalLabel">
                    <i class="fas fa-calendar-alt"></i> Payment Schedule
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="paymentScheduleContent">
                    <div class="text-center">
                        <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                        <p class="mt-2">Loading payment schedule...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Update Booking Modal -->
<div class="modal fade" id="updateBookingModal" tabindex="-1" aria-labelledby="updateBookingModalLabel" aria-hidden="true">
   <div class="modal-dialog">
       <div class="modal-content">
           <form id="updateBookingForm" method="POST" action="?controller=admin&action=adminUpdateBooking">
               <div class="modal-header">
                   <h5 class="modal-title" id="updateBookingModalLabel">Update Booking</h5>
                   <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
               </div>
               <div class="modal-body">
                   <input type="hidden" id="updateModalBookingId" name="booking_id">
                   <input type="hidden" id="updateModalResortId" name="resort_id">
                   
                   <div class="mb-3">
                       <label for="updateBookingStatus" class="form-label">Booking Status</label>
                       <select class="form-select" id="updateBookingStatus" name="booking_status">
                           <option value="Pending">Pending</option>
                           <option value="Confirmed">Confirmed</option>
                           <option value="Completed">Completed</option>
                           <option value="Cancelled">Cancelled</option>
                       </select>
                   </div>

                   <hr>
                   <h6>Existing Payments</h6>
                   <div id="existingPaymentsSection" class="mb-3">
                       <p class="text-muted small">Loading payment history...</p>
                   </div>

               </div>
               <div class="modal-footer">
                   <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                   <button type="submit" class="btn btn-primary">Save Changes</button>
               </div>
           </form>
       </div>
   </div>
</div>

<!-- Payments Modal -->
<div class="modal fade" id="paymentsModal" tabindex="-1" aria-labelledby="paymentsModalLabel" aria-hidden="true">
   <div class="modal-dialog modal-lg">
       <div class="modal-content">
           <div class="modal-header">
               <h5 class="modal-title" id="paymentsModalLabel">Payments</h5>
               <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
           </div>
           <div class="modal-body">
               <!-- Content will be loaded via JS -->
           </div>
           <div class="modal-footer">
               <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
           </div>
       </div>
   </div>
</div>

<!-- On-Site Edit Modal -->
<div class="modal fade" id="onSiteEditModal" tabindex="-1" aria-labelledby="onSiteEditModalLabel" aria-hidden="true">
   <div class="modal-dialog modal-lg">
       <div class="modal-content">
           <div class="modal-header">
               <h5 class="modal-title" id="onSiteEditModalLabel">On-Site Edit</h5>
               <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
           </div>
           <div class="modal-body">
               <!-- Content will be loaded via JS -->
           </div>
           <div class="modal-footer">
               <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
            
            // Scroll to the element. The `scroll-margin-top` CSS property will handle the offset.
            targetRow.scrollIntoView({ behavior: 'smooth', block: 'start' });
            
            // Remove the highlight after a few seconds
            setTimeout(() => {
                targetRow.classList.remove('table-info');
            }, 3000);
        }
    }

   const updateModal = document.getElementById('updateBookingModal');
   
   updateModal.addEventListener('show.bs.modal', function (event) {
       const button = event.relatedTarget;
       const bookingId = button.getAttribute('data-booking-id');
       const resortId = button.getAttribute('data-resort-id');

       // Reset form state
       const form = document.getElementById('updateBookingForm');
       form.reset();
       document.getElementById('existingPaymentsSection').innerHTML = '<p class="text-muted small">Loading payment history...</p>';

       // Set basic modal data
       document.getElementById('updateModalBookingId').value = bookingId;
       document.getElementById('updateModalResortId').value = resortId;
       document.getElementById('updateBookingModalLabel').textContent = 'Update Booking #' + bookingId;

       // Fetch booking details for the modal
       fetch(`?controller=admin&action=getBookingDetailsForManagement&booking_id=${bookingId}`)
           .then(response => response.json())
           .then(data => {
               if (data.success) {
                   const booking = data.booking;
                   document.getElementById('updateBookingStatus').value = booking.status;
                   const paymentsSection = document.getElementById('existingPaymentsSection');
                   if (booking.Payments && booking.Payments.length > 0) {
                       let paymentsHtml = '<table class="table table-sm table-striped"><thead><tr><th>ID</th><th>Amount</th><th>Method</th><th>Date</th><th>Status</th></tr></thead><tbody>';
                       booking.Payments.forEach(p => {
                           // Determine badge color based on status
                           let statusColor = 'secondary';
                           switch (p.Status) {
                               case 'Verified':
                               case 'Paid':
                                   statusColor = 'success';
                                   break;
                               case 'Pending':
                                   statusColor = 'warning text-dark';
                                   break;
                           }

                           paymentsHtml += `<tr>
                               <td>${p.PaymentID}</td>
                               <td>₱${parseFloat(p.Amount).toFixed(2)}</td>
                               <td>${p.PaymentMethod}</td>
                               <td>${formatDateTime(p.PaymentDate)}</td>
                               <td><span class="badge bg-${statusColor}">${p.Status}</span></td>
                           </tr>`;
                       });
                       paymentsHtml += '</tbody></table>';
                       paymentsSection.innerHTML = paymentsHtml;
                   } else {
                       paymentsSection.innerHTML = '<p class="text-muted small">No payment history found for this booking.</p>';
                   }
               } else {
                   document.getElementById('existingPaymentsSection').innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
               }
           })
           .catch(error => {
               console.error('Error fetching booking details:', error);
               document.getElementById('existingPaymentsSection').innerHTML = '<div class="alert alert-danger">Failed to load booking details.</div>';
           });
   });


    const onSiteEditModal = document.getElementById('onSiteEditModal');
    onSiteEditModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const bookingId = button.getAttribute('data-booking-id');
        const modalBody = onSiteEditModal.querySelector('.modal-body');
        modalBody.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i><p class="mt-2">Loading booking data...</p></div>';
        document.getElementById('onSiteEditModalLabel').textContent = 'On-Site Edit for Booking #' + bookingId;

        fetch(`?controller=admin&action=getBookingDetailsForManagement&booking_id=${bookingId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const booking = data.booking;

                    // Validation
                    if (booking.status !== 'Confirmed' || booking.remainingBalance > 0) {
                        modalBody.innerHTML = '<div class="alert alert-danger">On-site edits are only allowed for Confirmed and fully paid bookings.</div>';
                        return;
                    }

                    let formHtml = `<form id="onSiteEditForm" method="POST" action="?controller=admin&action=onSiteUpdateBooking">
                        <input type="hidden" name="booking_id" value="${bookingId}">
                        <h6>Modify Facilities</h6>
                        <div id="onSiteFacilitiesSection">Loading facilities...</div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-warning">Apply Edits</button>
                        </div>
                    </form>`;
                    modalBody.innerHTML = formHtml;
                    
                    fetchFacilitiesForOnSiteEdit(booking.resortId, booking.BookedFacilities);

                } else {
                    modalBody.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
                }
            })
            .catch(error => {
                console.error('Error fetching booking data:', error);
                modalBody.innerHTML = '<div class="alert alert-danger">Failed to load booking data.</div>';
            });
    });

    function fetchFacilitiesForOnSiteEdit(resortId, bookedFacilities) {
        const container = document.getElementById('onSiteFacilitiesSection');
        fetch(`?controller=resort&action=getFacilitiesJson&resort_id=${resortId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.facilities) {
                    let facilitiesHtml = '';
                    data.facilities.forEach(facility => {
                        const isChecked = bookedFacilities.some(bf => bf.facilityId == facility.facilityId);
                        facilitiesHtml += `<div class="form-check">
                            <input class="form-check-input" type="checkbox" name="facilities[]" value="${facility.facilityId}" id="onsite_facility_${facility.facilityId}" ${isChecked ? 'checked' : ''}>
                            <label class="form-check-label" for="onsite_facility_${facility.facilityId}">
                                ${facility.name} (+₱${parseFloat(facility.rate).toFixed(2)})
                            </label>
                        </div>`;
                    });
                    container.innerHTML = facilitiesHtml;
                } else {
                    container.innerHTML = '<p class="text-muted">No facilities available for this resort.</p>';
                }
            })
            .catch(error => {
                console.error('Error fetching facilities:', error);
                container.innerHTML = '<div class="alert alert-danger">Failed to load facilities.</div>';
            });
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

   const paymentsModal = document.getElementById('paymentsModal');
   paymentsModal.addEventListener('show.bs.modal', function (event) {
       const button = event.relatedTarget;
       const bookingId = button.getAttribute('data-booking-id');
       const modalBody = paymentsModal.querySelector('.modal-body');

       modalBody.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i><p class="mt-2">Loading payment data...</p></div>';
       document.getElementById('paymentsModalLabel').textContent = 'Payments for Booking #' + bookingId;

       fetch(`?controller=admin&action=getPaymentsData&booking_id=${bookingId}`)
           .then(response => response.json())
           .then(data => {
               if (data.success) {
                   let modalHtml = '';
                   const booking = data.booking;

                   // Existing Payments
                   modalHtml += '<h6>Existing Payments</h6>';
                   if (data.payments && data.payments.length > 0) {
                       modalHtml += '<table class="table table-sm table-striped"><thead><tr><th>ID</th><th>Amount</th><th>Method</th><th>Date</th><th>Status</th></tr></thead><tbody>';
                       data.payments.forEach(p => {
                           modalHtml += `<tr><td>${p.PaymentID}</td><td>₱${parseFloat(p.Amount).toFixed(2)}</td><td>${p.PaymentMethod}</td><td>${formatDateTime(p.PaymentDate)}</td><td><span class="badge bg-success">${p.Status}</span></td></tr>`;
                       });
                       modalHtml += '</tbody></table>';
                   } else {
                       modalHtml += '<p class="text-muted">No payments recorded yet.</p>';
                   }

                   modalHtml += '<hr>';

                   // Payment Schedule
                   modalHtml += '<h6>Payment Schedule</h6>';
                    if (data.schedule && data.schedule.length > 0) {
                       modalHtml += '<table class="table table-sm table-bordered"><thead><tr><th>Installment</th><th>Due Date</th><th>Amount</th><th>Status</th></tr></thead><tbody>';
                       data.schedule.forEach(s => {
                            modalHtml += `<tr><td>${s.InstallmentNumber}</td><td>${formatDate(s.DueDate)}</td><td>₱${parseFloat(s.Amount).toFixed(2)}</td><td><span class="badge bg-${getStatusColor(s.Status)}">${s.Status}</span></td></tr>`;
                       });
                       modalHtml += '</tbody></table>';
                   } else {
                       modalHtml += '<p class="text-muted">No payment schedule defined.</p>';
                   }

                   // Invoice Section
                   modalHtml += '<hr><h6>Invoice</h6>';
                   modalHtml += `<div class="d-flex justify-content-between align-items-center">
                       <p class="mb-0">Generate an invoice for this booking.</p>
                       <a href="?controller=admin&action=generateInvoiceForAdmin&id=${bookingId}" target="_blank" class="btn btn-primary">
                           <i class="fas fa-file-pdf me-2"></i>Generate as PDF
                       </a>
                   </div>`;


                   // Conditional "Add New Payment" Form
                   if (booking.status !== 'Pending' && booking.remainingBalance > 0) {
                       modalHtml += '<hr><h6>Add New Payment</h6>';
                       
                       // Dynamic Payment Method Dropdown
                       let paymentOptionsHtml = '<option value="Cash">Cash</option>';
                       if (data.paymentMethods && data.paymentMethods.length > 0) {
                           data.paymentMethods.forEach(method => {
                               // Avoid adding 'Cash' again if it's already a configured method type
                               if (method.MethodType.toLowerCase() !== 'cash') {
                                   paymentOptionsHtml += `<option value="${method.MethodType}">${method.MethodType}</option>`;
                               }
                           });
                       }

                       modalHtml += `<form id="addPaymentForm" method="POST" action="?controller=payment&action=add">
                           <input type="hidden" name="booking_id" value="${bookingId}">
                           <div class="row">
                               <div class="col-md-4">
                                   <label class="form-label">Amount</label>
                                   <input type="number" name="amount" class="form-control" step="0.01" required value="${booking.remainingBalance}">
                               </div>
                               <div class="col-md-4">
                                   <label class="form-label">Payment Method</label>
                                   <select name="payment_method" class="form-select" required>
                                       ${paymentOptionsHtml}
                                   </select>
                               </div>
                               <div class="col-md-4 d-flex align-items-end">
                                   <button type="submit" class="btn btn-success">Add Payment</button>
                               </div>
                           </div>
                           <input type="hidden" name="status" value="Verified">
                       </form>`;
                   } else if (booking.status === 'Pending') {
                        modalHtml += '<div class="alert alert-warning mt-3">Cannot add new payments to a pending booking. Please confirm the booking first.</div>';
                   } else if (booking.remainingBalance <= 0) {
                       modalHtml += '<div class="alert alert-success mt-3">This booking is fully paid.</div>';
                   }

                   modalBody.innerHTML = modalHtml;
               } else {
                   modalBody.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
               }
           })
           .catch(error => {
               console.error('Error fetching payment data:', error);
               modalBody.innerHTML = '<div class="alert alert-danger">Failed to load payment data.</div>';
           });
   });


// Phase 6: Enhanced JavaScript functions
function showAuditTrail(bookingId) {
    const modal = new bootstrap.Modal(document.getElementById('auditTrailModal'));
    const content = document.getElementById('auditTrailContent');
    
    // Show loading
    content.innerHTML = `
        <div class="text-center">
            <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
            <p class="mt-2">Loading audit trail...</p>
        </div>
    `;
    
    modal.show();
    
    // Fetch actual audit trail data
    fetch(`?controller=admin&action=getBookingAuditTrail&booking_id=${bookingId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.auditTrail) {
                let auditHtml = `
                    <div class="mb-3">
                        <h6><i class="fas fa-history"></i> Booking #${bookingId} Audit Trail</h6>
                        <p class="text-muted">Total entries: ${data.totalEntries}</p>
                    </div>
                `;
                
                if (data.auditTrail.length === 0) {
                    auditHtml += `
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            No audit trail entries found for this booking.
                        </div>
                    `;
                } else {
                    auditHtml += '<div class="timeline-container" style="max-height: 400px; overflow-y: auto;">';
                    
                    data.auditTrail.forEach(entry => {
                        const actionColor = getActionColor(entry.action);
                        auditHtml += `
                            <div class="card mb-2">
                                <div class="card-body py-2">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <span class="badge bg-${actionColor} me-2">${entry.action}</span>
                                            <strong>${entry.description}</strong>
                                        </div>
                                        <small class="text-muted">${formatDateTime(entry.createdAt)}</small>
                                    </div>
                                    <div class="mt-1">
                                        <small class="text-muted">
                                            by ${entry.username} (${entry.role})
                                            ${entry.reason ? ` - ${entry.reason}` : ''}
                                        </small>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    
                    auditHtml += '</div>';
                }
                
                content.innerHTML = auditHtml;
            } else {
                content.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        Error loading audit trail: ${data.error || 'Unknown error'}
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            content.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    Failed to load audit trail data.
                </div>
            `;
        });
}

function showPaymentSchedule(bookingId) {
    const modal = new bootstrap.Modal(document.getElementById('paymentScheduleModal'));
    const content = document.getElementById('paymentScheduleContent');
    
    // Show loading
    content.innerHTML = `
        <div class="text-center">
            <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
            <p class="mt-2">Loading payment schedule...</p>
        </div>
    `;
    
    modal.show();
    
    // Fetch actual payment schedule data
    fetch(`?controller=admin&action=getPaymentScheduleData&booking_id=${bookingId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let scheduleHtml = `
                    <div class="mb-3">
                        <h6><i class="fas fa-calendar-alt"></i> Payment Schedule for Booking #${bookingId}</h6>
                    </div>
                `;
                
                // Summary section
                if (data.summary) {
                    scheduleHtml += `
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body p-3">
                                        <h6 class="card-title">Schedule Summary</h6>
                                        <ul class="list-unstyled mb-0">
                                            <li><strong>Total Installments:</strong> ${data.summary.totalInstallments}</li>
                                            <li><strong>Total Amount:</strong> ₱${data.summary.totalAmount}</li>
                                            <li><strong>Paid Amount:</strong> ₱${data.summary.paidAmount}</li>
                                            <li><strong>Remaining:</strong> ₱${data.summary.remainingAmount}</li>
                                            ${data.summary.overdueCount > 0 ? `<li class="text-danger"><strong>Overdue:</strong> ${data.summary.overdueCount}</li>` : ''}
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                ${data.nextPayment ? `
                                    <div class="card bg-warning bg-opacity-25">
                                        <div class="card-body p-3">
                                            <h6 class="card-title">Next Payment Due</h6>
                                            <ul class="list-unstyled mb-0">
                                                <li><strong>Installment #${data.nextPayment.installmentNumber}</strong></li>
                                                <li><strong>Due Date:</strong> ${formatDate(data.nextPayment.dueDate)}</li>
                                                <li><strong>Amount:</strong> ₱${data.nextPayment.amount}</li>
                                                <li><strong>Status:</strong> <span class="badge bg-${getStatusColor(data.nextPayment.status)}">${data.nextPayment.status}</span></li>
                                            </ul>
                                        </div>
                                    </div>
                                ` : `
                                    <div class="card bg-success bg-opacity-25">
                                        <div class="card-body p-3">
                                            <h6 class="card-title">Payment Status</h6>
                                            <p class="mb-0"><i class="fas fa-check-circle text-success"></i> All payments completed</p>
                                        </div>
                                    </div>
                                `}
                            </div>
                        </div>
                    `;
                }
                
                // Schedule table
                if (data.schedule && data.schedule.length > 0) {
                    scheduleHtml += `
                        <div class="table-responsive">
                            <table class="table table-sm table-striped">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Installment #</th>
                                        <th>Due Date</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                    `;
                    
                    data.schedule.forEach(item => {
                        const statusColor = getStatusColor(item.status);
                        const isOverdue = item.isOverdue;
                        
                        scheduleHtml += `
                            <tr ${isOverdue ? 'class="table-danger"' : ''}>
                                <td>${item.installmentNumber}</td>
                                <td>${formatDate(item.dueDate)} ${isOverdue ? '<i class="fas fa-exclamation-triangle text-danger" title="Overdue"></i>' : ''}</td>
                                <td>₱${item.amount}</td>
                                <td><span class="badge bg-${statusColor}">${item.status}</span></td>
                                <td>
                                    ${item.status === 'Pending' ? `
                                        <button class="btn btn-sm btn-outline-success" onclick="markSchedulePaid(${item.scheduleId})" title="Mark as Paid">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    ` : ''}
                                    ${item.paymentId ? `<small class="text-muted">Payment #${item.paymentId}</small>` : ''}
                                </td>
                            </tr>
                        `;
                    });
                    
                    scheduleHtml += `
                                </tbody>
                            </table>
                        </div>
                    `;
                } else {
                    scheduleHtml += `
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            No payment schedule found for this booking.
                        </div>
                    `;
                }
                
                content.innerHTML = scheduleHtml;
            } else {
                content.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        Error loading payment schedule: ${data.error || 'Unknown error'}
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            content.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    Failed to load payment schedule data.
                </div>
            `;
        });
}

function applyRecommendation(bookingId, newStatus) {
    if (confirm(`Apply lifecycle recommendation to change booking #${bookingId} status to "${newStatus}"?`)) {
        const formData = new FormData();
        formData.append('booking_id', bookingId);
        formData.append('new_status', newStatus);
        formData.append('reason', 'Lifecycle recommendation applied by admin');
        
        fetch('?controller=admin&action=applyLifecycleRecommendation', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Recommendation applied successfully!', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast('Failed to apply recommendation: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Error applying recommendation', 'error');
        });
    }
}

// Helper functions for formatting and UI
function getActionColor(action) {
    const colors = {
        'CREATE': 'success',
        'UPDATE': 'primary',
        'DELETE': 'danger',
        'STATUS_CHANGE': 'warning',
        'PAYMENT_UPDATE': 'info'
    };
    return colors[action] || 'secondary';
}

function getStatusColor(status) {
    const colors = {
        'Paid': 'success',
        'Pending': 'warning',
        'Overdue': 'danger',
        'Cancelled': 'secondary',
        'Verified': 'success'
    };
    return colors[status] || 'secondary';
}

function formatDateTime(dateTime) {
    return new Date(dateTime).toLocaleString();
}

function formatDate(date) {
    return new Date(date).toLocaleDateString();
}

function markSchedulePaid(scheduleId) {
    if (confirm('Mark this installment as paid?')) {
        const formData = new FormData();
        formData.append('schedule_id', scheduleId);
        formData.append('action', 'mark_paid');
        formData.append('payment_id', prompt('Enter Payment ID (if available):') || '0');
        
        fetch('?controller=admin&action=updatePaymentSchedule', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Payment schedule updated!', 'success');
                // Refresh the modal content
                const currentBookingId = document.getElementById('paymentScheduleModal').getAttribute('data-booking-id');
                if (currentBookingId) {
                    showPaymentSchedule(currentBookingId);
                }
            } else {
                showToast('Failed to update schedule: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Error updating payment schedule', 'error');
        });
    }
}

function showToast(message, type = 'info') {
    // Simple toast notification
    const toast = document.createElement('div');
    toast.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed`;
    toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    toast.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        if (toast.parentNode) {
            toast.parentNode.removeChild(toast);
        }
    }, 5000);
}
</script>
