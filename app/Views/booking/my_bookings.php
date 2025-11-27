<?php
$pageTitle = "My Bookings";
require_once __DIR__ . '/../partials/header.php';
?>

<h1><?= htmlspecialchars($pageTitle) ?></h1>

<?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($_SESSION['success_message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        <?php unset($_SESSION['success_message']); ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($_SESSION['error_message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        <?php unset($_SESSION['error_message']); ?>
    </div>
<?php endif; ?>

<?php if (empty($activeBookings)): ?>
    <div class="alert alert-info" role="alert">
        You have no confirmed bookings. Check <a href="?controller=booking&action=showMyReservations">My Reservations</a> for pending bookings or <a href="?controller=booking&action=showBookingForm">make a new booking</a>!
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Creation Date</th>
                    <th>Resort</th>
                    <th>Date & Time</th>
                    <th>Facilities</th>
                    <th>Total Price</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($activeBookings as $booking): ?>
                    <tr>
                        <td>
                            <div><strong><?= htmlspecialchars(date('F j, Y', strtotime($booking->CreatedAt))) ?></strong></div>
                            <small class="text-muted"><?= htmlspecialchars(date('g:i A', strtotime($booking->CreatedAt))) ?></small>
                        </td>
                        <td>
                            <strong><?= htmlspecialchars($booking->ResortName ?? 'Unknown Resort') ?></strong>
                        </td>
                        <td>
                            <div><strong><?= htmlspecialchars(date('F j, Y', strtotime($booking->BookingDate))) ?></strong></div>
                            <small class="text-muted">
                                <?php
                                    $timeSlotDisplay = [
                                        '12_hours' => '12 Hours - Check In: 7:00 AM Check Out: 5:00 PM',
                                        '24_hours' => '24 Hours - Check In: 7:00 AM Check Out: 5:00 AM Next Day',
                                        'overnight' => 'Overnight - Check In: 7:00 PM Check Out: 5:00 AM'
                                    ];
                                    echo htmlspecialchars($timeSlotDisplay[$booking->TimeSlotType] ?? 'N/A');
                                ?>
                            </small>
                        </td>
                        <td>
                            <?php if (!empty($booking->FacilityNames)): ?>
                                <span class="badge bg-info text-dark"><?= htmlspecialchars($booking->FacilityNames) ?></span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Resort access only</span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?php if (!empty($booking->TotalAmount)): ?>
                                <strong class="text-success">₱<?= number_format($booking->TotalAmount, 2) ?></strong>
                                <?php if (!empty($booking->RemainingBalance) && $booking->RemainingBalance > 0): ?>
                                    <br><small class="text-warning">Balance: ₱<?= number_format($booking->RemainingBalance, 2) ?></small>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                                $statusColors = [
                                    'Pending' => 'bg-warning text-dark',
                                    'Confirmed' => 'bg-success',
                                    'Cancelled' => 'bg-danger',
                                    'Completed' => 'bg-primary'
                                ];
                                $statusClass = $statusColors[$booking->Status] ?? 'bg-secondary';
                            ?>
                            <span class="badge <?= $statusClass ?>"><?= htmlspecialchars($booking->Status) ?></span>
                        </td>
                        <td>
                            <div class="btn-group-vertical btn-group-sm" role="group">
                                <?php if ($booking->Status === 'Completed'): ?>
                                    <?php if ($booking->hasFeedback): ?>
                                        <span class="badge bg-primary text-white">Feedback Submitted</span>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-success btn-sm feedback-btn" data-bs-toggle="modal" data-bs-target="#feedbackModal" data-booking-id="<?= htmlspecialchars($booking->BookingID) ?>" data-booking-date="<?= htmlspecialchars(date('F j, Y', strtotime($booking->BookingDate))) ?>" data-resort-name="<?= htmlspecialchars($booking->ResortName ?? 'Unknown Resort') ?>">
                                            <i class="fas fa-star"></i> Leave Feedback
                                        </button>
                                    <?php endif; ?>
                                <?php elseif ($booking->Status === 'Cancelled'): ?>
                                    <span class="text-muted small">No actions available</span>
                                <?php else: ?>
                                    <!-- Payment Actions -->
                                    <?php if (!empty($booking->RemainingBalance) && $booking->RemainingBalance > 0): ?>
                                        <button type="button" class="btn btn-primary btn-sm mb-1 payment-modal-btn"
                                                data-bs-toggle="modal"
                                                data-bs-target="#paymentModal"
                                                data-booking-id="<?= htmlspecialchars($booking->BookingID) ?>"
                                                data-booking-date="<?= htmlspecialchars(date('F j, Y', strtotime($booking->BookingDate))) ?>"
                                                data-total-amount="<?= htmlspecialchars($booking->TotalAmount) ?>"
                                                data-remaining-balance="<?= htmlspecialchars($booking->RemainingBalance) ?>"
                                                data-resort-name="<?= htmlspecialchars($booking->ResortName ?? 'Unknown Resort') ?>"
                                                data-facility-names="<?= htmlspecialchars($booking->FacilityNames ?? 'Resort access only') ?>">
                                            <i class="fas fa-credit-card"></i>
                                            <?php if ($booking->RemainingBalance < $booking->TotalAmount): ?>
                                                Complete Payment
                                            <?php else: ?>
                                                Submit Payment
                                            <?php endif; ?>
                                        </button>
                                    <?php elseif ($booking->Status === 'Pending'): ?>
                                        <span class="badge bg-warning text-dark">Payment Under Review</span>
                                    <?php endif; ?>

                                    <!-- Contact Admin Action -->
                                    <?php if ($booking->Status === 'Pending' && !empty($booking->RemainingBalance) && $booking->RemainingBalance > 0): ?>
                                    <button type="button" class="btn btn-outline-info btn-sm mt-1" data-bs-toggle="modal" data-bs-target="#contactAdminModal">
                                        <i class="fas fa-headset"></i> Contact Admin
                                    </button>
                                    <?php endif; ?>

                                    <!-- Placeholder for fully confirmed bookings with no actions -->
                                    <?php if (empty($booking->RemainingBalance) || $booking->RemainingBalance <= 0): ?>
                                        <?php if ($booking->Status === 'Confirmed'): ?>
                                            <span class="badge bg-secondary">Booking confirmed</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <a href="?" class="btn btn-secondary mt-3">Back to Dashboard</a>
<?php endif; ?>

<!-- Feedback Modal -->
<div class="modal fade" id="feedbackModal" tabindex="-1" aria-labelledby="feedbackModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="feedbackModalLabel">Leave Feedback</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Your feedback is important to us. Please rate your experience for your booking at <strong id="modalResortName"></strong> on <strong id="modalBookingDate"></strong>.</p>
                <form id="feedbackForm" action="?controller=feedback&action=submitFeedback" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="bookingId" id="modalBookingId">
                    <input type="hidden" name="redirect_url" value="?controller=booking&action=showMyBookings">
                    
                    <!-- Resort Feedback -->
                    <div class="feedback-section mb-4 p-3 border rounded bg-light">
                        <h5>Resort Feedback</h5>
                        <div class="mb-3">
                            <label for="resort_rating" class="form-label"><strong>Rating (1 to 5)</strong></label>
                            <div class="rating-stars">
                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                    <input type="radio" id="resort_star<?= $i ?>" name="resort_rating" value="<?= $i ?>" required>
                                    <label for="resort_star<?= $i ?>">&starf;</label>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="resort_comment" class="form-label"><strong>Comments</strong></label>
                            <textarea class="form-control" id="resort_comment" name="resort_comment" rows="3" placeholder="Tell us more about your experience at the resort..."></textarea>
                        </div>
                    </div>

                    <!-- Media Upload -->
                    <div class="mb-4">
                        <label for="media" class="form-label"><strong>Upload Images or Videos (Optional)</strong></label>
                        <input type="file" class="form-control" id="media" name="media[]" multiple accept="image/*,video/*">
                    </div>

                    <!-- Facility Feedback Section (Dynamically Populated) -->
                    <div id="facilityFeedbackSection">
                        <!-- Facility feedback forms will be inserted here -->
                    </div>

                    <button type="submit" class="btn btn-primary">Submit Feedback</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="paymentModalLabel">
                    <i class="fas fa-credit-card"></i> Submit Payment
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Booking Summary -->
                <div class="card mb-4 border-primary">
                    <div class="card-header bg-primary-subtle">
                        <h6 class="mb-0 text-primary">
                            <i class="fas fa-receipt"></i> Booking Summary - <span id="paymentResortName"></span>
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-muted">Booking Details</h6>
                                <p><strong>Date:</strong> <span id="paymentBookingDate"></span></p>
                                <p><strong>Status:</strong> <span class="badge bg-warning text-dark">Pending</span></p>
                                <p><strong>Facilities:</strong> <span id="paymentFacilityNames" class="badge bg-info text-dark"></span></p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted">Payment Information</h6>
                                <p><strong>Total Amount:</strong> <span id="paymentTotalAmount" class="fw-bold text-success"></span></p>
                                <p><strong>Remaining Balance:</strong> <span id="paymentRemainingBalance" class="fw-bold text-danger"></span></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Methods -->
                <div id="paymentMethodsSection">
                    <div class="card mb-4 border-success">
                        <div class="card-header bg-success-subtle">
                            <h6 class="mb-0 text-success">
                                <i class="fas fa-credit-card"></i> Available Payment Methods
                            </h6>
                        </div>
                        <div class="card-body" id="paymentMethodsList">
                            <!-- Payment methods will be loaded here -->
                            <div class="text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading payment methods...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Form -->
                <form id="paymentForm" action="?controller=booking&action=submitPayment" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="booking_id" id="paymentBookingId">
                    <input type="hidden" name="payment_method" id="selectedPaymentMethod">

                    <div id="paymentFormFields" style="display: none;">
                        <!-- Amount Being Paid -->
                        <div class="mb-3">
                            <label for="modalAmountPaid" class="form-label fw-bold">
                                <i class="fas fa-peso-sign text-primary"></i> Amount Being Paid <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-peso-sign"></i></span>
                                <input type="number" class="form-control" id="modalAmountPaid" name="amount_paid" min="1" step="0.01" required readonly>
                            </div>
                            <div class="form-text">
                                <i class="fas fa-info-circle"></i> Please use the buttons below to select a payment amount.
                            </div>
                            <div class="mt-2 d-flex gap-2 flex-wrap">
                                <button type="button" class="btn btn-success btn-sm" id="payFullBtn">
                                    <i class="fas fa-money-bill"></i> Full Amount
                                </button>
                                <button type="button" class="btn btn-outline-primary btn-sm" id="payHalfBtn">
                                    <i class="fas fa-percentage"></i> Half Amount
                                </button>
                            </div>
                        </div>

                        <!-- Payment Reference -->
                        <div class="mb-3">
                            <label for="modalPaymentReference" class="form-label fw-bold">
                                <i class="fas fa-hashtag text-primary"></i> Payment Reference Number <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-receipt"></i></span>
                                <input type="text" class="form-control" id="modalPaymentReference" name="payment_reference" required>
                            </div>
                            <div class="form-text">
                                <i class="fas fa-info-circle"></i> Enter the transaction ID, reference number, or confirmation code from your payment.
                            </div>
                        </div>

                        <!-- Payment Proof Upload -->
                        <div class="mb-3">
                            <label for="modalPaymentProof" class="form-label fw-bold">
                                <i class="fas fa-camera text-primary"></i> Payment Proof <span class="text-danger">*</span>
                            </label>

                            <div class="upload-area border-2 border-dashed rounded-3 p-3 text-center" id="modalUploadArea">
                                <div class="upload-content">
                                    <i class="fas fa-cloud-upload-alt fa-2x text-primary mb-2"></i>
                                    <p class="mb-2"><strong>Drop your payment proof here</strong></p>
                                    <p class="text-muted small mb-2">or click to browse files</p>
                                    <input type="file" class="form-control d-none" id="modalPaymentProof" name="payment_proof" accept="image/*" required>
                                    <button type="button" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-folder-open"></i> Browse Files
                                    </button>
                                </div>
                            </div>

                            <div class="form-text mt-2">
                                <i class="fas fa-info-circle"></i> Upload a screenshot or photo of your payment confirmation (JPG, PNG, GIF | Max 5MB)
                            </div>

                            <div id="modalImagePreview" class="mt-2" style="display: none;">
                                <div class="card border-success">
                                    <div class="card-body text-center">
                                        <img id="modalPreviewImg" src="" alt="Payment proof preview" class="img-fluid rounded" style="max-height: 200px;">
                                        <div class="mt-2">
                                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeModalImage()">
                                                <i class="fas fa-trash"></i> Remove Image
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Important Notice -->
                        <div class="alert alert-warning">
                            <h6><i class="fas fa-exclamation-triangle"></i> Important Notice</h6>
                            <ul class="mb-0 small">
                                <li>Your booking will be confirmed only after payment verification</li>
                                <li>Please ensure the payment proof clearly shows the transaction amount and reference number</li>
                                <li>Verification may take up to 24 hours</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="d-flex gap-2 justify-content-end">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success" id="modalSubmitBtn" disabled>
                            <span id="modalSubmitText">
                                <i class="fas fa-paper-plane"></i> Submit Payment Proof
                            </span>
                            <span id="modalLoadingText" style="display: none;">
                                <i class="fas fa-spinner fa-spin"></i> Submitting...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Payment Confirmation Modal -->
<div class="modal fade" id="paymentConfirmationModal" tabindex="-1" aria-labelledby="paymentConfirmationModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="paymentConfirmationModalLabel">
                    <i class="fas fa-file-invoice-dollar"></i> Please Verify Your Payment Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <h6 class="fw-bold"><i class="fas fa-info-circle"></i> Final Review</h6>
                    <p>Please take a moment to review your payment details carefully before submitting. This action cannot be undone.</p>
                </div>

                <div class="card mb-3 border-success">
                    <div class="card-header bg-success-subtle">
                        <h6 class="mb-0 text-success"><i class="fas fa-money-check-dollar"></i> Payment Summary</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12">
                                <dl class="row">
                                    <dt class="col-sm-5">Amount Paid:</dt>
                                    <dd class="col-sm-7"><strong class="text-success" id="summaryAmount"></strong></dd>

                                    <dt class="col-sm-5">Payment Method:</dt>
                                    <dd class="col-sm-7" id="summaryPaymentMethod"></dd>

                                    <dt class="col-sm-5">Reference #:</dt>
                                    <dd class="col-sm-7" id="summaryReference"></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Proof Section (Full Width) -->
                <div class="card mb-3 border-info">
                    <div class="card-header bg-info-subtle">
                        <h6 class="mb-0 text-info"><i class="fas fa-camera"></i> Payment Proof Image</h6>
                    </div>
                    <div class="card-body text-center">
                        <img id="summaryProofImageFull" src="" alt="Payment Proof Preview" class="img-fluid rounded border" style="max-height: 400px; width: auto;">
                        <p class="mt-2 text-muted small">Preview of the uploaded payment proof.</p>
                    </div>
                </div>

                <div class="card mb-3 border-primary">
                     <div class="card-header bg-primary-subtle">
                        <h6 class="mb-0 text-primary">
                            <i class="fas fa-receipt"></i> Booking Summary - <span id="summaryResortName"></span>
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-muted">Booking Details</h6>
                                <p><strong>Date:</strong> <span id="summaryBookingDate"></span></p>
                                <p><strong>Facilities:</strong> <span id="summaryFacilityNames" class="badge"></span></p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted">Original Payment Info</h6>
                                <p><strong>Total Amount:</strong> <span id="summaryTotalAmount" class="fw-bold text-success"></span></p>
                                <p><strong>Remaining Balance:</strong> <span id="summaryRemainingBalance" class="fw-bold text-danger"></span></p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="goBackToPaymentModal">Go Back & Edit</button>
                <button type="button" class="btn btn-success" id="finalSubmitBtn" disabled>
                    <span id="finalSubmitText">
                        <i class="fas fa-check-circle"></i> I Verify This is Correct & Submit Payment <span id="countdownTimerSpan" class="fw-bold"></span>
                    </span>
                    <span id="finalLoadingText" style="display: none;">
                        <i class="fas fa-spinner fa-spin"></i> Submitting...
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .rating-stars {
        display: inline-block;
        direction: rtl;
        font-size: 2.5rem;
    }
    .rating-stars input[type="radio"] {
        display: none;
    }
    .rating-stars label {
        color: #ddd;
        cursor: pointer;
        transition: color 0.2s;
    }
    .rating-stars input[type="radio"]:checked ~ label,
    .rating-stars label:hover,
    .rating-stars label:hover ~ label {
        color: #ffc107;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Feedback Modal Handler
    var feedbackModal = document.getElementById('feedbackModal');
    feedbackModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var bookingId = button.getAttribute('data-booking-id');
        var bookingDate = button.getAttribute('data-booking-date');
        var resortName = button.getAttribute('data-resort-name');

        var modalBookingDate = feedbackModal.querySelector('#modalBookingDate');
        var modalBookingIdInput = feedbackModal.querySelector('#modalBookingId');
        var modalResortName = feedbackModal.querySelector('#modalResortName');
        var facilityFeedbackSection = feedbackModal.querySelector('#facilityFeedbackSection');

        modalBookingDate.textContent = bookingDate;
        modalBookingIdInput.value = bookingId;
        modalResortName.textContent = resortName;

        // Clear previous facility feedback forms
        facilityFeedbackSection.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>';

        // Fetch and display facility feedback forms
        fetch('?controller=booking&action=getFacilitiesForBooking&booking_id=' + bookingId)
            .then(response => response.json())
            .then(facilities => {
                facilityFeedbackSection.innerHTML = ''; // Clear spinner
                if (facilities.length > 0) {
                    var facilityHtml = '<h5>Optional Facilities Feedback</h5>';
                    facilities.forEach(facility => {
                        facilityHtml += `
                            <div class="feedback-section mb-3 p-3 border rounded">
                                <h6>${facility.Name}</h6>
                                <input type="hidden" name="facilities[${facility.FacilityID}][id]" value="${facility.FacilityID}">
                                <div class="mb-3">
                                    <label for="facility_rating_${facility.FacilityID}" class="form-label"><strong>Rating (1 to 5)</strong></label>
                                    <div class="rating-stars">
                                        <input type="radio" id="facility_star_${facility.FacilityID}_5" name="facilities[${facility.FacilityID}][rating]" value="5" required><label for="facility_star_${facility.FacilityID}_5">&starf;</label>
                                        <input type="radio" id="facility_star_${facility.FacilityID}_4" name="facilities[${facility.FacilityID}][rating]" value="4" required><label for="facility_star_${facility.FacilityID}_4">&starf;</label>
                                        <input type="radio" id="facility_star_${facility.FacilityID}_3" name="facilities[${facility.FacilityID}][rating]" value="3" required><label for="facility_star_${facility.FacilityID}_3">&starf;</label>
                                        <input type="radio" id="facility_star_${facility.FacilityID}_2" name="facilities[${facility.FacilityID}][rating]" value="2" required><label for="facility_star_${facility.FacilityID}_2">&starf;</label>
                                        <input type="radio" id="facility_star_${facility.FacilityID}_1" name="facilities[${facility.FacilityID}][rating]" value="1" required><label for="facility_star_${facility.FacilityID}_1">&starf;</label>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="facility_comment_${facility.FacilityID}" class="form-label"><strong>Comments</strong></label>
                                    <textarea class="form-control" id="facility_comment_${facility.FacilityID}" name="facilities[${facility.FacilityID}][comment]" rows="2" placeholder="Feedback for ${facility.Name}..."></textarea>
                                </div>
                            </div>
                        `;
                    });
                    facilityFeedbackSection.innerHTML = facilityHtml;
                }
            })
            .catch(error => {
                console.error('Error fetching facilities:', error);
                facilityFeedbackSection.innerHTML = '<div class="alert alert-warning">Could not load facilities for feedback.</div>';
            });
    });

    let isTransitioningToConfirmation = false;
    let isReturningFromConfirmation = false;
    // Payment Modal Handler
    var paymentModal = document.getElementById('paymentModal');
    paymentModal.addEventListener('show.bs.modal', function (event) {
        if (isReturningFromConfirmation) {
            isReturningFromConfirmation = false; // Reset flag
            return; // Skip re-initialization on the way back
        }
        var button = event.relatedTarget;
        var bookingId = button.getAttribute('data-booking-id');
        var bookingDate = button.getAttribute('data-booking-date');
        var totalAmount = parseFloat(button.getAttribute('data-total-amount'));
        var remainingBalance = parseFloat(button.getAttribute('data-remaining-balance'));
        var resortName = button.getAttribute('data-resort-name');
        var facilityNames = button.getAttribute('data-facility-names');

        // Populate modal data
        document.getElementById('paymentBookingId').value = bookingId;
        document.getElementById('paymentBookingDate').textContent = bookingDate;
        document.getElementById('paymentTotalAmount').textContent = '₱' + totalAmount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('paymentRemainingBalance').textContent = '₱' + remainingBalance.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('paymentResortName').textContent = resortName;

        // Handle facility names display and styling
        var facilityBadge = document.getElementById('paymentFacilityNames');
        facilityBadge.textContent = facilityNames;
        facilityBadge.classList.remove('bg-info', 'text-dark', 'bg-secondary'); // Reset classes

        if (facilityNames === 'Resort access only') {
            facilityBadge.classList.add('bg-secondary');
        } else {
            facilityBadge.classList.add('bg-info', 'text-dark');
        }

        // Set input constraints
        var amountInput = document.getElementById('modalAmountPaid');
        amountInput.max = remainingBalance;
        amountInput.value = remainingBalance; // Default to remaining balance

        // Load payment methods
        loadPaymentMethods(bookingId, resortName);
        
        // Hide the main form fields initially
        document.getElementById('paymentFormFields').style.display = 'none';

        // Reset form validation
        validateModalForm();
    });

    // Reset modal when hidden
    paymentModal.addEventListener('hidden.bs.modal', function () {
        // Don't reset the form if we are just moving to the confirmation modal
        if (isTransitioningToConfirmation) {
            isTransitioningToConfirmation = false; // Reset flag
            return;
        }

        // Reset form on normal close
        document.getElementById('paymentForm').reset();
        document.getElementById('modalImagePreview').style.display = 'none';
        document.getElementById('modalUploadArea').style.display = 'block';
        document.getElementById('paymentFormFields').style.display = 'none';
        document.getElementById('modalSubmitBtn').disabled = true;
    });

    // Load payment methods via AJAX
    function loadPaymentMethods(bookingId, resortName) {
        // First get resort ID from booking (we need to make a request to get resort info)
        fetch('?controller=booking&action=getPaymentMethods&booking_id=' + bookingId, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            displayPaymentMethods(data);
        })
        .catch(error => {
            console.error('Error loading payment methods:', error);
            document.getElementById('paymentMethodsList').innerHTML = '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Unable to load payment methods. Please contact the resort directly at <strong>' + resortName + '</strong> for payment instructions.</div>';
        });
    }

    // Display payment methods in modal
    function displayPaymentMethods(methods) {
        var container = document.getElementById('paymentMethodsList');
        var paymentForm = document.getElementById('paymentForm');
        var modalSubmitBtn = document.getElementById('modalSubmitBtn');

        if (!methods || methods.length === 0) {
            container.innerHTML = `
                <div class="alert alert-danger">
                    <h6 class="fw-bold"><i class="fas fa-times-circle"></i> Payment Not Available</h6>
                    <p class="mb-2">This resort has not configured any payment methods yet.</p>
                    <p class="mb-3"><strong>Please contact the resort directly to arrange payment outside of this system.</strong></p>
                </div>
            `;
            paymentForm.style.display = 'none'; // Hide the form completely
            return;
        }

        var html = '<div class="row justify-content-center">'; // Center the columns if there's only one
        methods.forEach(function(method, index) {
            const qrCodeHtml = method.qrCodeUrl ?
                `<div class="qr-code-container text-center mb-3">
                    <img src="${method.qrCodeUrl}" alt="${method.name} QR Code" class="img-fluid rounded" style="max-height: 220px; height: auto; width: 100%; object-fit: contain;">
                    <a href="${method.qrCodeUrl}" class="btn btn-sm btn-outline-secondary mt-2" download><i class="fas fa-download"></i> Download QR</a>
                </div>` :
                `<div class="qr-code-container text-center mb-3 p-5 border rounded bg-light">
                    <i class="fas fa-qrcode fa-3x text-muted"></i>
                    <p class="mt-2 text-muted small">QR Code Not Available</p>
                </div>`;

            html += `
                <div class="col-md-6 mb-3">
                    <label for="payment_method_option_${index}" class="card h-100 payment-method-card" style="cursor: pointer;">
                        <div class="card-body d-flex flex-column text-center">
                            <div class="form-check mb-2">
                                <input type="radio" name="payment_method_option" value="${method.name}" id="payment_method_option_${index}" class="form-check-input" required>
                                <label class="form-check-label" for="payment_method_option_${index}">
                                    <h5 class="card-title text-success fw-bold">${method.name}</h5>
                                </label>
                            </div>
                            
                            ${qrCodeHtml}
                            
                            <div class="payment-details mt-auto">
                                <p class="card-text mb-1"><strong>Account Name:</strong><br>${method.accountName}</p>
                                <p class="card-text"><strong>Account Number:</strong><br>${method.accountNumber}</p>
                            </div>
                        </div>
                    </label>
                </div>
            `;
        });
        html += '</div>';
        container.innerHTML = html;

        // Add event listeners to the radio buttons
        var paymentRadios = container.querySelectorAll('input[name="payment_method_option"]');
        paymentRadios.forEach(function(radio) {
            radio.addEventListener('change', function() {
                if (this.checked) {
                    document.getElementById('selectedPaymentMethod').value = this.value;
                    document.getElementById('paymentFormFields').style.display = 'block';
                    document.querySelectorAll('.payment-method-card').forEach(card => card.classList.remove('border-primary', 'border-3'));
                    this.closest('.payment-method-card').classList.add('border-primary', 'border-3');
                    validateModalForm();
                }
            });
        });
    }

    // Quick pay buttons
    document.getElementById('payFullBtn').addEventListener('click', function() {
        var remainingBalance = parseFloat(document.getElementById('modalAmountPaid').max);
        document.getElementById('modalAmountPaid').value = remainingBalance;
        document.getElementById('modalAmountPaid').classList.add('pulse');
        setTimeout(() => document.getElementById('modalAmountPaid').classList.remove('pulse'), 1000);
        validateModalForm();
    });

    document.getElementById('payHalfBtn').addEventListener('click', function() {
        var remainingBalance = parseFloat(document.getElementById('modalAmountPaid').max);
        var halfAmount = remainingBalance / 2;
        document.getElementById('modalAmountPaid').value = halfAmount.toFixed(2);
        document.getElementById('modalAmountPaid').classList.add('pulse');
        setTimeout(() => document.getElementById('modalAmountPaid').classList.remove('pulse'), 1000);
        validateModalForm();
    });

    // File upload handling
    var modalUploadArea = document.getElementById('modalUploadArea');
    var modalFileInput = document.getElementById('modalPaymentProof');
    var modalPreview = document.getElementById('modalImagePreview');
    var modalPreviewImg = document.getElementById('modalPreviewImg');

    // Click to upload
    modalUploadArea.addEventListener('click', function(e) {
        if (e.target !== modalFileInput) {
            modalFileInput.click();
        }
    });

    // Drag and drop
    modalUploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        modalUploadArea.classList.add('dragover');
    });

    modalUploadArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        modalUploadArea.classList.remove('dragover');
    });

    modalUploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        modalUploadArea.classList.remove('dragover');

        var files = e.dataTransfer.files;
        if (files.length > 0) {
            modalFileInput.files = files;
            handleModalFileSelection(files[0]);
        }
    });

    // File input change
    modalFileInput.addEventListener('change', function(e) {
        var file = e.target.files[0];
        if (file) {
            handleModalFileSelection(file);
        } else {
            modalPreview.style.display = 'none';
            modalUploadArea.style.display = 'block';
        }
    });

    function handleModalFileSelection(file) {
        // Immediately check for a file
        if (!file) {
            removeModalImage(); // Reset if no file is selected
            return;
        }

        const validExtensions = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        const maxFileSize = 5 * 1024 * 1024; // 5MB

        // --- Step 1: Basic file validation (Client-side) ---
        // Validate file type based on MIME type for better accuracy
        if (!validExtensions.includes(file.type)) {
            showModalAlert('Invalid file type. Please select a JPG, PNG, GIF, or WebP image.', 'danger');
            removeModalImage();
            return;
        }

        // Validate file size
        if (file.size > maxFileSize) {
            showModalAlert(`File is too large (${(file.size / 1024 / 1024).toFixed(2)}MB). Maximum size is 5MB.`, 'danger');
            removeModalImage();
            return;
        }

        // --- Step 2: Advanced validation using FileReader and Image object ---
        const reader = new FileReader();

        // Show a loading/processing state to the user
        showModalAlert('Validating image...', 'info');

        reader.onload = function(e) {
            const img = new Image();
            img.onload = function() {
                // This confirms the file is a valid, renderable image
                // All validations passed, proceed to show preview
                modalPreviewImg.src = e.target.result;
                modalPreview.style.display = 'block';
                modalUploadArea.style.display = 'none';
                validateModalForm();
                showModalAlert('Image accepted and ready for upload!', 'success');
            };

            img.onerror = function() {
                // This triggers if the file data is corrupted or not a real image
                showModalAlert('The selected file is not a valid or supported image. Please try another file.', 'danger');
                removeModalImage();
            };

            // Set the src to trigger the image load/error events
            img.src = e.target.result;
        };

        reader.onerror = function() {
            // This triggers if the browser cannot read the file at all
            showModalAlert('There was an error reading the file. Please try selecting it again.', 'danger');
            removeModalImage();
        };

        // Start reading the file
        reader.readAsDataURL(file);
    }


    // Remove image function
    window.removeModalImage = function() {
        modalFileInput.value = '';
        modalPreview.style.display = 'none';
        modalUploadArea.style.display = 'block';
        validateModalForm();
    };

    // Form validation
    function validateModalForm() {
        var amount = parseFloat(document.getElementById('modalAmountPaid').value) || 0;
        var reference = document.getElementById('modalPaymentReference').value.trim();
        var file = modalFileInput.files.length > 0;
        var maxAmount = parseFloat(document.getElementById('modalAmountPaid').max);
        var paymentMethodSelected = document.getElementById('selectedPaymentMethod').value;

        var isValid = paymentMethodSelected && amount > 0 && amount <= maxAmount && reference.length > 0 && file;
        document.getElementById('modalSubmitBtn').disabled = !isValid;
    }

    // Add validation listeners
    document.getElementById('modalAmountPaid').addEventListener('input', validateModalForm);
    document.getElementById('modalPaymentReference').addEventListener('input', validateModalForm);

    // Form submission
    document.getElementById('paymentForm').addEventListener('submit', function(e) {
        e.preventDefault(); // Always prevent default to show our confirmation modal

        var amount = parseFloat(document.getElementById('modalAmountPaid').value);
        var maxAmount = parseFloat(document.getElementById('modalAmountPaid').max);

        // Re-validate amount before proceeding
        if (amount <= 0 || amount > maxAmount) {
            showModalAlert('Please enter a valid payment amount between ₱1.00 and ₱' + maxAmount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}), 'danger');
            return false;
        }

        isTransitioningToConfirmation = true; // Set flag to prevent form reset

        // Hide the first modal
        const paymentModalInstance = bootstrap.Modal.getInstance(paymentModal);
        if(paymentModalInstance) {
            paymentModalInstance.hide();
        }
        
        // --- Populate Confirmation Modal ---
        const confirmationModalEl = document.getElementById('paymentConfirmationModal');
        const confirmationModal = new bootstrap.Modal(confirmationModalEl);

        // --- Payment Summary ---
        document.getElementById('summaryAmount').textContent = '₱' + amount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('summaryPaymentMethod').textContent = document.getElementById('selectedPaymentMethod').value;
        document.getElementById('summaryReference').textContent = document.getElementById('modalPaymentReference').value;
        document.getElementById('summaryProofImageFull').src = document.getElementById('modalPreviewImg').src;

        // --- Booking Summary ---
        document.getElementById('summaryResortName').textContent = document.getElementById('paymentResortName').textContent;
        document.getElementById('summaryBookingDate').textContent = document.getElementById('paymentBookingDate').textContent;
        document.getElementById('summaryTotalAmount').textContent = document.getElementById('paymentTotalAmount').textContent;
        document.getElementById('summaryRemainingBalance').textContent = document.getElementById('paymentRemainingBalance').textContent;

        const facilityBadge = document.getElementById('summaryFacilityNames');
        const originalFacilityBadge = document.getElementById('paymentFacilityNames');
        facilityBadge.textContent = originalFacilityBadge.textContent;
        facilityBadge.className = originalFacilityBadge.className; // Copy all classes


        // Show the confirmation modal
        confirmationModal.show();

        // Start the countdown
        startConfirmationCountdown();
    });

    // Confirmation Modal Logic
    function startConfirmationCountdown() {
        const finalSubmitBtn = document.getElementById('finalSubmitBtn');
        const countdownTimerSpan = document.getElementById('countdownTimerSpan');
        let countdown = 10;
        let interval;

        // Reset state
        finalSubmitBtn.disabled = true;
        countdownTimerSpan.textContent = `(${countdown})`;

        interval = setInterval(() => {
            countdown--;
            countdownTimerSpan.textContent = `(${countdown})`;

            if (countdown <= 0) {
                clearInterval(interval);
                finalSubmitBtn.disabled = false;
                countdownTimerSpan.textContent = ''; // Clear timer text
            }
        }, 1000);
        
        // Also handle modal close to stop the timer
        const confirmationModalEl = document.getElementById('paymentConfirmationModal');
        confirmationModalEl.addEventListener('hidden.bs.modal', () => {
            clearInterval(interval);
            // Reset button and timer for next time
            finalSubmitBtn.disabled = true;
            countdownTimerSpan.textContent = `(${10})`;
        }, { once: true });
    }

    document.getElementById('finalSubmitBtn').addEventListener('click', function() {
        // Show loading state on the final button
        this.disabled = true;
        document.getElementById('finalSubmitText').style.display = 'none';
        document.getElementById('finalLoadingText').style.display = 'inline';
        
        // Show loading state on the original submit button (for when user navigates back)
        document.getElementById('modalSubmitText').style.display = 'none';
        document.getElementById('modalLoadingText').style.display = 'inline';
        document.getElementById('modalSubmitBtn').disabled = true;
        
        showModalAlert('Submitting payment proof... Please wait.', 'info');
        
        // Hide confirmation modal
        const confirmationModal = bootstrap.Modal.getInstance(document.getElementById('paymentConfirmationModal'));
        if (confirmationModal) {
            confirmationModal.hide();
        }

        // Add a small delay to allow the modal to finish hiding before submitting
        setTimeout(() => {
            // Actually submit the form (this bypasses the 'submit' event listener)
            document.getElementById('paymentForm').submit();
        }, 500);
    });

    document.getElementById('goBackToPaymentModal').addEventListener('click', function() {
        isReturningFromConfirmation = true; // Set flag to prevent re-initialization

        const confirmationModalEl = document.getElementById('paymentConfirmationModal');
        const paymentModalEl = document.getElementById('paymentModal');
        
        const confirmationModal = bootstrap.Modal.getInstance(confirmationModalEl);
        
        // Add a one-time event listener to show the payment modal after this one is hidden
        confirmationModalEl.addEventListener('hidden.bs.modal', function () {
            const paymentModal = bootstrap.Modal.getOrCreateInstance(paymentModalEl);
            paymentModal.show();
        }, { once: true });
        
        // Now, hide the confirmation modal
        if (confirmationModal) {
            confirmationModal.hide();
        }
    });

    // Modal alert function
    function showModalAlert(message, type) {
        // Remove existing alerts
        var existingAlerts = paymentModal.querySelectorAll('.alert');
        existingAlerts.forEach(function(alert) {
            alert.remove();
        });

        // Create new alert
        var alert = document.createElement('div');
        alert.className = 'alert alert-' + type + ' alert-dismissible fade show';
        alert.style.cssText = 'position: absolute; top: 10px; left: 10px; right: 10px; z-index: 9999;';
        alert.innerHTML = message + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';

        paymentModal.querySelector('.modal-body').prepend(alert);

        // Auto remove after 3 seconds
        if (type !== 'danger') {
            setTimeout(function() {
                if (alert.parentNode) {
                    alert.remove();
                }
            }, 3000);
        }
    }
});
</script>
<!-- Contact Admin Modal -->
<div class="modal fade" id="contactAdminModal" tabindex="-1" aria-labelledby="contactAdminModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="contactAdminModalLabel">Contact Administrator</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    To modify or inquire about your booking, please contact the resort administration directly.
                </div>
                <?php if (isset($adminContact) && $adminContact): ?>
                    <p><strong>Admin Contact:</strong> <?= htmlspecialchars($adminContact['FirstName'] . ' ' . $adminContact['LastName']) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($adminContact['Email']) ?></p>
                    <p><strong>Phone:</strong> <?= htmlspecialchars($adminContact['PhoneNumber'] ?? 'N/A') ?></p>
                <?php else: ?>
                    <p>Administrator contact information is not available at this time.</p>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>
