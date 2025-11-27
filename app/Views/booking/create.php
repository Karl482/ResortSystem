<?php
$pageTitle = "New Reservation";
// Conditionally load the header based on user session
if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../partials/header.php';
} else {
    require_once __DIR__ . '/../partials/guest_header.php';
}

// Get pre-selected resort ID and facility ID from URL if available
$selectedResortId = filter_input(INPUT_GET, 'resort_id', FILTER_VALIDATE_INT);
$selectedFacilityId = filter_input(INPUT_GET, 'facility_id', FILTER_VALIDATE_INT);
?>

<!-- Guest Notice -->
<?php if (!isset($_SESSION['user_id'])): ?>
<div class="alert alert-info" role="alert">
    Please <a href="?action=login">login</a> or <a href="?action=showRegisterForm">register</a> to create a new reservation.
</div>
<?php endif; ?>

<!-- Error Messages -->
<?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?= htmlspecialchars($_SESSION['error_message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        <?php unset($_SESSION['error_message']); ?>
    </div>
<?php endif; ?>

<!-- Pricing Incomplete Notice -->
<div class="alert alert-warning alert-dismissible fade show mb-4" id="pricingIncompleteNotice" style="display: none;">
    <h6 class="alert-heading mb-2"><i class="fas fa-exclamation-triangle me-2"></i>Pricing Setup Incomplete</h6>
    <p class="mb-2">This resort has not completed setting up prices yet. Please contact them directly to arrange your reservation outside of this system.</p>
    <p class="mb-1"><i class="fas fa-phone text-muted me-2"></i><strong>Phone:</strong> <span id="noticePhone"></span></p>
    <p class="mb-0"><i class="fas fa-envelope text-muted me-2"></i><strong>Email:</strong> <span id="noticeEmail"></span></p>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>

<!-- Modern Wizard Container -->
<div class="wizard-container">
    <!-- Progress Steps Indicator -->
    <div class="progress-steps-wrapper">
        <div class="progress-bar-track"></div>
        <div class="progress-bar-fill" id="progressBarFill"></div>
        <div class="progress-steps">
            <div class="step-item active" data-step="1">
                <div class="step-circle">
                    <i class="fas fa-map-marker-alt"></i>
                    <span class="step-number">1</span>
                </div>
                <div class="step-label">Resort</div>
            </div>
            <div class="step-item" data-step="2">
                <div class="step-circle">
                    <i class="fas fa-clock"></i>
                    <span class="step-number">2</span>
                </div>
                <div class="step-label">Timeframe</div>
            </div>
            <div class="step-item" data-step="3">
                <div class="step-circle">
                    <i class="fas fa-calendar-alt"></i>
                    <span class="step-number">3</span>
                </div>
                <div class="step-label">Date</div>
            </div>
            <div class="step-item" data-step="4">
                <div class="step-circle">
                    <i class="fas fa-swimming-pool"></i>
                    <span class="step-number">4</span>
                </div>
                <div class="step-label">Facilities</div>
            </div>
            <div class="step-item" data-step="5">
                <div class="step-circle">
                    <i class="fas fa-receipt"></i>
                    <span class="step-number">5</span>
                </div>
                <div class="step-label">Summary</div>
            </div>
        </div>
    </div>

    <!-- Main Form -->
    <form action="?controller=booking&action=createBooking" method="POST" id="bookingForm">
        <!-- Wizard Content Area -->
        <div class="wizard-content">
            <!-- Step 1: Resort Selection -->
            <div class="wizard-step active" data-step="1">
                <div class="step-header">
                    <h2><i class="fas fa-map-marker-alt text-primary me-2"></i>Choose Your Resort</h2>
                    <p class="text-muted">Select the resort where you'd like to stay</p>
                </div>
                <div class="step-body">
                    <div class="resort-grid">
                        <?php if (empty($resorts)): ?>
                            <div class="col-12">
                                <div class="alert alert-warning text-center">
                                    <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                                    <p class="mb-0">No resorts are currently available.</p>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($resorts as $resort): ?>
                                <div class="resort-option" data-resort-id="<?= $resort->resortId ?>">
                                    <input type="radio" class="resort-radio" name="resort_id" 
                                           id="resort_<?= $resort->resortId ?>" 
                                           value="<?= $resort->resortId ?>"
                                           <?= ($selectedResortId == $resort->resortId) ? 'checked' : '' ?>>
                                    <label for="resort_<?= $resort->resortId ?>" class="resort-card">
                                        <div class="resort-image">
                                            <img src="<?= htmlspecialchars($resort->mainPhotoURL) ?>" 
                                                 alt="<?= htmlspecialchars($resort->name) ?>">
                                            <div class="resort-overlay">
                                                <i class="fas fa-check-circle"></i>
                                            </div>
                                        </div>
                                        <div class="resort-details">
                                            <h4>
                                                <i class="<?= htmlspecialchars($resort->icon) ?> me-2"></i>
                                                <?= htmlspecialchars($resort->name) ?>
                                            </h4>
                                            <p><?= nl2br(htmlspecialchars($resort->shortDescription)) ?></p>
                                        </div>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="step-footer">
                    <div class="step-hint">
                        <i class="fas fa-lightbulb text-warning me-2"></i>
                        <span>Click on a resort card to select it and continue</span>
                    </div>
                </div>
            </div>

            <!-- Step 2: Timeframe Selection -->
            <div class="wizard-step" data-step="2">
                <div class="step-header">
                    <h2><i class="fas fa-clock text-primary me-2"></i>Select Your Timeframe</h2>
                    <p class="text-muted">Choose how long you'd like to stay</p>
                </div>
                <div class="step-body">
                    <div class="timeframe-grid">
                        <div class="timeframe-option">
                            <input type="radio" class="timeframe-radio" name="timeframe" 
                                   id="timeframe_12_hours" value="12_hours"
                                   <?= (isset($_SESSION['old_input']['timeSlotType']) && $_SESSION['old_input']['timeSlotType'] == '12_hours') ? 'checked' : '' ?>>
                            <label for="timeframe_12_hours" class="timeframe-card">
                                <div class="timeframe-icon">
                                    <i class="fas fa-sun fa-3x text-warning"></i>
                                </div>
                                <h4>12 Hours</h4>
                                <div class="timeframe-time">
                                    <div><i class="fas fa-sign-in-alt me-2"></i><strong>Check In:</strong> 7:00 AM</div>
                                    <div><i class="fas fa-sign-out-alt me-2"></i><strong>Check Out:</strong> 5:00 PM</div>
                                </div>
                                <div class="timeframe-price" id="price_12_hours">
                                    <span class="price-label">Starting at</span>
                                    <span class="price-value">₱0.00</span>
                                </div>
                                <div class="booked-badge" id="booked_12_hours" style="display: none;">
                                    <i class="fas fa-exclamation-circle me-1"></i>Already Booked
                                </div>
                            </label>
                        </div>

                        <div class="timeframe-option">
                            <input type="radio" class="timeframe-radio" name="timeframe" 
                                   id="timeframe_24_hours" value="24_hours"
                                   <?= (isset($_SESSION['old_input']['timeSlotType']) && $_SESSION['old_input']['timeSlotType'] == '24_hours') ? 'checked' : '' ?>>
                            <label for="timeframe_24_hours" class="timeframe-card">
                                <div class="timeframe-icon">
                                    <i class="fas fa-clock fa-3x text-info"></i>
                                </div>
                                <h4>24 Hours</h4>
                                <div class="timeframe-time">
                                    <div><i class="fas fa-sign-in-alt me-2"></i><strong>Check In:</strong> 7:00 AM</div>
                                    <div><i class="fas fa-sign-out-alt me-2"></i><strong>Check Out:</strong> 5:00 AM <small>(next day)</small></div>
                                </div>
                                <div class="timeframe-price" id="price_24_hours">
                                    <span class="price-label">Starting at</span>
                                    <span class="price-value">₱0.00</span>
                                </div>
                                <div class="booked-badge" id="booked_24_hours" style="display: none;">
                                    <i class="fas fa-exclamation-circle me-1"></i>Already Booked
                                </div>
                            </label>
                        </div>

                        <div class="timeframe-option">
                            <input type="radio" class="timeframe-radio" name="timeframe" 
                                   id="timeframe_overnight" value="overnight"
                                   <?= (isset($_SESSION['old_input']['timeSlotType']) && $_SESSION['old_input']['timeSlotType'] == 'overnight') ? 'checked' : '' ?>>
                            <label for="timeframe_overnight" class="timeframe-card">
                                <div class="timeframe-icon">
                                    <i class="fas fa-moon fa-3x text-secondary"></i>
                                </div>
                                <h4>Overnight</h4>
                                <div class="timeframe-time">
                                    <div><i class="fas fa-sign-in-alt me-2"></i><strong>Check In:</strong> 7:00 PM</div>
                                    <div><i class="fas fa-sign-out-alt me-2"></i><strong>Check Out:</strong> 5:00 AM <small>(next day)</small></div>
                                </div>
                                <div class="timeframe-price" id="price_overnight">
                                    <span class="price-label">Starting at</span>
                                    <span class="price-value">₱0.00</span>
                                </div>
                                <div class="booked-badge" id="booked_overnight" style="display: none;">
                                    <i class="fas fa-exclamation-circle me-1"></i>Already Booked
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="step-footer">
                    <div class="step-hint">
                        <i class="fas fa-lightbulb text-warning me-2"></i>
                        <span>Select a timeframe to see pricing and continue</span>
                    </div>
                </div>
            </div>

            <!-- Step 3: Date Selection -->
            <div class="wizard-step" data-step="3">
                <div class="step-header">
                    <h2><i class="fas fa-calendar-alt text-primary me-2"></i>Pick Your Date</h2>
                    <p class="text-muted">Select your preferred reservation date</p>
                </div>
                <div class="step-body">
                    <div class="calendar-section">
                        <div class="calendar-controls">
                            <button type="button" class="btn btn-outline-primary" id="prevMonth">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <div class="calendar-month-display">
                                <input type="month" class="form-control" id="calendarMonth" 
                                       value="<?= date('Y-m') ?>" min="<?= date('Y-m') ?>" max="<?= date('Y-12') ?>">
                            </div>
                            <button type="button" class="btn btn-outline-primary" id="nextMonth">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                        
                        <div class="calendar-legend">
                            <span class="legend-item"><span class="legend-color weekday"></span> Weekday</span>
                            <span class="legend-item"><span class="legend-color weekend"></span> Weekend</span>
                            <span class="legend-item"><span class="legend-color holiday"></span> Holiday</span>
                            <span class="legend-item"><span class="legend-color partial"></span> Partially Booked</span>
                            <span class="legend-item"><span class="legend-color unavailable"></span> Unavailable</span>
                        </div>

                        <div id="calendarLoading" class="text-center py-5">
                            <i class="fas fa-spinner fa-spin fa-3x text-primary"></i>
                            <p class="mt-3">Loading availability...</p>
                        </div>

                        <div id="calendarGrid" class="calendar-grid" style="display: none;">
                            <!-- Calendar will be populated here -->
                        </div>

                        <input type="hidden" id="date" name="booking_date" 
                               value="<?= htmlspecialchars($_SESSION['old_input']['bookingDate'] ?? '') ?>">
                    </div>

                    <div id="selectedDateDisplay" class="selected-date-card" style="display: none;">
                        <i class="fas fa-check-circle text-success me-2"></i>
                        <div class="selected-date-info">
                            <strong>Selected Date:</strong>
                            <span id="selectedDateText"></span>
                        </div>
                    </div>
                </div>
                <div class="step-footer">
                    <div class="step-hint">
                        <i class="fas fa-lightbulb text-warning me-2"></i>
                        <span>Click on an available date to select it</span>
                    </div>
                </div>
            </div>

            <!-- Step 4: Facilities Selection -->
            <div class="wizard-step" data-step="4">
                <div class="step-header">
                    <h2><i class="fas fa-swimming-pool text-primary me-2"></i>Additional Facilities</h2>
                    <p class="text-muted">Enhance your experience with optional facilities (Optional)</p>
                </div>
                <div class="step-body">
                    <div id="facilitiesContainer" class="facilities-grid">
                        <div class="text-center py-5">
                            <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Loading available facilities...</p>
                        </div>
                    </div>
                </div>
                <div class="step-footer">
                    <div class="step-hint">
                        <i class="fas fa-lightbulb text-warning me-2"></i>
                        <span>Click on facilities to add them, or skip to continue</span>
                    </div>
                </div>
            </div>

            <!-- Step 5: Summary -->
            <div class="wizard-step" data-step="5">
                <div class="step-header">
                    <h2><i class="fas fa-receipt text-primary me-2"></i>Reservation Summary</h2>
                    <p class="text-muted">Review your reservation details</p>
                </div>
                <div class="step-body">
                    <div class="summary-container">
                        <div class="summary-section">
                            <h5><i class="fas fa-info-circle text-primary me-2"></i>Reservation Details</h5>
                            <div class="summary-grid">
                                <div class="summary-item">
                                    <span class="summary-label"><i class="fas fa-map-marker-alt me-2"></i>Resort:</span>
                                    <span class="summary-value" id="summaryResort">-</span>
                                </div>
                                <div class="summary-item">
                                    <span class="summary-label"><i class="fas fa-clock me-2"></i>Timeframe:</span>
                                    <span class="summary-value" id="summaryTimeframe">-</span>
                                </div>
                                <div class="summary-item">
                                    <span class="summary-label"><i class="fas fa-calendar-alt me-2"></i>Date:</span>
                                    <span class="summary-value" id="summaryDate">-</span>
                                </div>
                            </div>
                        </div>

                        <div class="summary-section" id="facilitiesSummarySection" style="display: none;">
                            <h5><i class="fas fa-swimming-pool text-primary me-2"></i>Selected Facilities</h5>
                            <div id="facilitiesSummaryList" class="facilities-summary-list">
                                <!-- Facilities will be listed here -->
                            </div>
                        </div>

                        <div class="summary-section">
                            <h5><i class="fas fa-dollar-sign text-primary me-2"></i>Price Breakdown</h5>
                            <div class="price-breakdown">
                                <div class="price-item">
                                    <span>Base Price:</span>
                                    <span id="summaryBasePrice" class="price-value">₱0.00</span>
                                </div>
                                <div id="summarySurcharges" class="surcharges-list">
                                    <!-- Surcharges will be listed here -->
                                </div>
                                <div id="summaryFacilityPrices" class="facility-prices-list">
                                    <!-- Facility prices will be listed here -->
                                </div>
                                <div class="price-item total">
                                    <span>Total Amount:</span>
                                    <span id="summaryTotalPrice" class="price-value">₱0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="step-footer">
                    <div class="step-hint">
                        <i class="fas fa-check-circle text-success me-2"></i>
                        <span>Review your details and click "Complete Reservation" to proceed</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Buttons -->
        <div class="wizard-navigation">
            <button type="button" class="btn-nav btn-prev" id="prevBtn" style="visibility: hidden;">
                <i class="fas fa-chevron-left me-2"></i>
                <span>Back</span>
            </button>
            
            <div class="nav-center">
                <div class="step-indicator-dots">
                    <span class="dot active" data-step="1"></span>
                    <span class="dot" data-step="2"></span>
                    <span class="dot" data-step="3"></span>
                    <span class="dot" data-step="4"></span>
                    <span class="dot" data-step="5"></span>
                </div>
            </div>

            <button type="button" class="btn-nav btn-next" id="nextBtn">
                <span>Continue</span>
                <i class="fas fa-chevron-right ms-2"></i>
            </button>

            <button type="button" class="btn-nav btn-submit" id="submitBtn" style="display: none;" 
                    <?php if (isset($_SESSION['user_id'])): ?>data-bs-toggle="modal" data-bs-target="#termsModal"<?php endif; ?>>
                <i class="fas fa-check-circle me-2"></i>
                <span>Complete Reservation</span>
            </button>
        </div>
    </form>
</div>

<!-- Calendar Modal (Simplified for inline calendar) -->
<div class="modal fade" id="calendarModal" tabindex="-1" aria-labelledby="calendarModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="calendarModalLabel">
                    <i class="fas fa-calendar-alt me-2"></i>Select Date
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Modal content if needed -->
            </div>
        </div>
    </div>
</div>

<!-- Timeframe Conflict Modal -->
<div class="modal fade" id="timeframeConflictModal" tabindex="-1" aria-labelledby="timeframeConflictModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="timeframeConflictModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>Timeframe Not Available
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-light border mb-3">
                    <div class="d-flex align-items-center mb-2">
                        <i class="fas fa-times-circle text-danger me-2"></i>
                        <strong>Selected Timeframe:</strong>
                    </div>
                    <div class="ps-4" id="conflictOldTimeframe">12 Hours (7:00 AM - 5:00 PM)</div>
                </div>
                
                <p class="text-muted mb-3">
                    <i class="fas fa-info-circle me-1"></i>
                    This timeframe is not available on your selected date.
                </p>
                
                <div class="alert alert-success border mb-0">
                    <div class="d-flex align-items-center mb-2">
                        <i class="fas fa-check-circle text-success me-2"></i>
                        <strong>We'll Switch You To:</strong>
                    </div>
                    <div class="ps-4 fw-bold" id="conflictNewTimeframe">Overnight (7:00 PM - 5:00 AM next day)</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancel
                </button>
                <button type="button" class="btn btn-primary" id="confirmTimeframeSwitch">
                    <i class="fas fa-check me-2"></i>Continue with New Timeframe
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Terms and Conditions Modal -->
<?php if (isset($_SESSION['user_id'])): ?>
<div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="termsModalLabel">
                    <i class="fas fa-file-contract me-2"></i>Terms and Conditions
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><strong>Last Updated: October 28, 2025</strong></p>
                <p>Welcome to our resort booking platform. By completing your reservation through this system, you <strong id="termsGuestName" class="text-primary"></strong> agree to be bound by the following Terms and Conditions. This document constitutes a legally binding agreement between you and the resort property you are booking <strong id="termsResortName" class="text-primary"></strong>. Please read these terms carefully.</p>

                <h6>1. Introduction</h6>
                <p>This platform provides an integrated digital management system for private pool resorts. Our service is to facilitate the booking and reservation process between you and the Resort. The specific policies of each Resort are determined by its management, and by booking, you agree to adhere to them.</p>

                <h6>2. Booking and Reservations</h6>
                <ul>
                    <li><strong>Accuracy of Information:</strong> You are responsible for providing complete and accurate information for your booking, including your name, contact details, and the number of guests. Any inaccuracies may lead to the cancellation of your reservation or additional charges.</li>
                    <li><strong>Booking Confirmation:</strong> A reservation is considered tentative until the required payment is made and confirmed by the system. You will receive an official booking confirmation via email or our platform's notification system once the payment is successfully processed and verified. Please present this confirmation upon arrival at the Resort.</li>
                    <li><strong>Platform's Role:</strong> Our system acts as an intermediary to manage reservations. The contract for the resort stay and any services provided is directly between you and the Resort.</li>
                </ul>

                <h6>3. Payment Policies</h6>
                <ul>
                    <li><strong>Down Payment:</strong> A down payment is required to secure your booking. The specific amount and deadline will be indicated during the booking process. Failure to pay the down payment by the specified deadline will result in the automatic cancellation of your tentative reservation.</li>
                    <li><strong>Full Payment:</strong> The remaining balance must be paid on or before the date specified in your booking confirmation. The Resort reserves the right to cancel your booking if the full payment is not received on time.</li>
                    <li><strong>Payment Methods:</strong> We accept payments through Gcash, bank transfer, and other methods as specified on the payment page. You are responsible for any transaction fees that may be charged by the payment provider.</li>
                    <li><strong>Refunds:</strong> All payments are generally non-refundable. Refunds will only be considered under specific circumstances, such as cancellations initiated by the Resort or as outlined in the "Cancellation and Modifications" section below.</li>
                </ul>

                <h6>4. Cancellation and Modifications</h6>
                <ul>
                    <li><strong>Guest-Initiated Cancellation:</strong> If you wish to cancel your booking, you must do so through the system or by contacting the Resort directly.
                        <ul>
                            <li>Cancellations made more than <strong>14 days</strong> prior to the check-in date may be eligible for a partial refund, subject to a cancellation fee determined by the Resort.</li>
                            <li>Cancellations made within <strong>14 days</strong> of the check-in date are strictly non-refundable.</li>
                        </ul>
                    </li>
                    <li><strong>Booking Modifications:</strong> Any request to change your booking dates or other details is subject to availability and the Resort's approval. Additional charges may apply.</li>
                    <li><strong>No-Show Policy:</strong> If you do not arrive for your booking on the scheduled check-in date (a "no-show"), your booking will be canceled without any refund.</li>
                </ul>

                <h6>5. Resort Rules and Guest Conduct</h6>
                <ul>
                    <li><strong>Adherence to Rules:</strong> You and all members of your party agree to abide by the specific rules and regulations of the Resort, which may include policies on noise levels, visitor access, use of facilities, and safety procedures. These rules will be provided by the Resort.</li>
                    <li><strong>Liability for Damages:</strong> The registered Guest is personally liable for any damages caused to the resort property, facilities, or equipment by you or any member of your party. The cost of repairs or replacement will be charged to you.</li>
                    <li><strong>Right to Refuse Service:</strong> The Resort management reserves the right to refuse entry or evict any guest or party who acts in a disorderly, disruptive, or unsafe manner, without any refund.</li>
                </ul>

                <h6>6. Limitation of Liability</h6>
                <ul>
                    <li><strong>At the Resort:</strong> The booking platform and its operators are not liable for any personal injury, accidents, loss of property, or any other damages that may occur during your stay at the Resort. All activities and the use of facilities (such as the swimming pool) are at your own risk.</li>
                    <li><strong>System Availability:</strong> While we strive to ensure the system is available 24/7, we do not guarantee that it will be uninterrupted or error-free. We are not liable for any loss or inconvenience caused by technical issues, system downtime, or booking errors.</li>
                </ul>

                <h6>7. Data Privacy</h6>
                <p>We are committed to protecting your privacy. The personal information you provide during the booking process will be collected, stored, and used in compliance with the Data Privacy Act. Your data will be used to process and manage your booking, communicate important information about your reservation, and maintain a record of your booking history. Your information will not be shared with third parties for marketing purposes without your explicit consent.</p>

                <h6>8. Acceptance of Terms</h6>
                <p>By checking the "I accept the Terms and Conditions" box and proceeding with your booking, you acknowledge that you have read, understood, and agree to be bound by all the terms and conditions outlined above.</p>
                
                <div id="scroll-notice" class="alert alert-info text-center sticky-bottom mb-0 p-2 rounded-0" role="alert" style="display: none;">
                    <i class="fas fa-arrow-down me-2"></i>Scroll down to read all terms and enable the checkbox.
                </div>
            </div>
            <div class="modal-footer">
                <div class="form-check me-auto">
                    <input class="form-check-input" type="checkbox" value="" id="termsCheckbox" disabled>
                    <label class="form-check-label fw-bold ms-2" for="termsCheckbox">
                        I have read and accept the Terms and Conditions
                    </label>
                </div>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Decline</button>
                <button type="button" class="btn btn-primary" id="acceptTermsBtn" disabled>I Accept</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Enhanced Styles -->
<style>
/* Wizard Container */
.wizard-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0;
}

/* Progress Steps */
.progress-steps-wrapper {
    position: relative;
    margin-bottom: 1.5rem;
    padding: 1rem 0;
}

.progress-bar-track {
    position: absolute;
    top: 50%;
    left: 10%;
    right: 10%;
    height: 4px;
    background: #e9ecef;
    transform: translateY(-50%);
    z-index: 0;
}

.progress-bar-fill {
    position: absolute;
    top: 50%;
    left: 10%;
    height: 4px;
    background: linear-gradient(90deg, #0d6efd 0%, #0dcaf0 100%);
    transform: translateY(-50%);
    z-index: 1;
    width: 0;
    transition: width 0.5s ease;
}

.progress-steps {
    display: flex;
    justify-content: space-between;
    position: relative;
    z-index: 2;
    padding: 0 5%;
}

.step-item {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.step-circle {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    background: white;
    border: 2px solid #e9ecef;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.step-circle i {
    font-size: 1.1rem;
    color: #6c757d;
    transition: all 0.3s ease;
}

.step-circle .step-number {
    position: absolute;
    bottom: -3px;
    right: -3px;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: white;
    border: 2px solid #e9ecef;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.65rem;
    font-weight: bold;
    color: #6c757d;
}

.step-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: #6c757d;
    text-align: center;
    transition: all 0.3s ease;
}

.step-item.active .step-circle {
    border-color: #0d6efd;
    background: #0d6efd;
    transform: scale(1.1);
}

.step-item.active .step-circle i {
    color: white;
}

.step-item.active .step-circle .step-number {
    border-color: #0d6efd;
    background: white;
    color: #0d6efd;
}

.step-item.active .step-label {
    color: #0d6efd;
    font-weight: 700;
}

.step-item.completed .step-circle {
    border-color: #198754;
    background: #198754;
}

.step-item.completed .step-circle i {
    color: white;
}

.step-item.completed .step-circle .step-number {
    border-color: #198754;
    background: white;
    color: #198754;
}

.step-item.completed .step-circle::before {
    content: "\f00c";
    font-family: "Font Awesome 5 Free";
    font-weight: 900;
    position: absolute;
    top: -8px;
    right: -8px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: #198754;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

/* Wizard Content */
.wizard-content {
    min-height: 350px;
    position: relative;
    overflow: hidden;
}

.wizard-step {
    position: absolute;
    width: 100%;
    opacity: 0;
    transform: translateX(100%);
    transition: all 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    pointer-events: none;
}

.wizard-step.active {
    opacity: 1;
    transform: translateX(0);
    position: relative;
    pointer-events: all;
}

.wizard-step.prev {
    transform: translateX(-100%);
}

.step-header {
    text-align: center;
    margin-bottom: 1.5rem;
    padding: 0 1rem;
}

.step-header h2 {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
    color: #212529;
}

.step-header p {
    font-size: 0.95rem;
    margin: 0;
}

.step-body {
    padding: 0 1rem;
    margin-bottom: 1rem;
}

.step-footer {
    text-align: center;
    padding: 0.75rem 1rem;
    background: #f8f9fa;
    border-radius: 0.5rem;
    margin-top: 1rem;
}

.step-hint {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    font-size: 0.95rem;
    color: #6c757d;
}

/* Resort Grid */
.resort-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1rem;
    margin: 0 auto;
}

.resort-option {
    position: relative;
}

.resort-option input[type="radio"] {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.resort-card {
    display: flex;
    flex-direction: column;
    height: 100%;
    cursor: pointer;
    border: 2px solid #dee2e6;
    border-radius: 0.75rem;
    overflow: hidden;
    background: white;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.resort-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    border-color: #0d6efd;
}

.resort-option input[type="radio"]:checked + .resort-card {
    border-color: #0d6efd;
    box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.25);
    background: linear-gradient(135deg, #f8f9ff 0%, #ffffff 100%);
}

.resort-image {
    position: relative;
    width: 100%;
    height: 150px;
    overflow: hidden;
}

.resort-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.resort-card:hover .resort-image img {
    transform: scale(1.1);
}

.resort-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(13, 110, 253, 0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.resort-overlay i {
    font-size: 3rem;
    color: white;
}

.resort-option input[type="radio"]:checked + .resort-card .resort-overlay {
    opacity: 1;
}

.resort-details {
    padding: 1rem;
    flex-grow: 1;
}

.resort-details h4 {
    font-size: 1.1rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    color: #212529;
}

.resort-details p {
    font-size: 0.85rem;
    color: #6c757d;
    margin: 0;
    line-height: 1.4;
}

/* Timeframe Grid */
.timeframe-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
    max-width: 900px;
    margin: 0 auto;
}

.timeframe-option {
    position: relative;
}

.timeframe-option input[type="radio"] {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.timeframe-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 1.25rem 1rem;
    border: 2px solid #dee2e6;
    border-radius: 0.75rem;
    background: white;
    cursor: pointer;
    transition: all 0.3s ease;
    text-align: center;
    min-height: 240px;
    position: relative;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.timeframe-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    border-color: #0d6efd;
}

.timeframe-option input[type="radio"]:checked + .timeframe-card {
    border-color: #0d6efd;
    box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.25);
    background: linear-gradient(135deg, #f8f9ff 0%, #ffffff 100%);
}

.timeframe-option input[type="radio"]:disabled + .timeframe-card {
    opacity: 0.6;
    cursor: not-allowed;
    background: #f8f9fa;
}

.timeframe-icon {
    margin-bottom: 0.75rem;
}

.timeframe-icon i {
    font-size: 2.5rem !important;
}

.timeframe-card h4 {
    font-size: 1.25rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    color: #212529;
}

.timeframe-time {
    width: 100%;
    margin-bottom: 0.75rem;
}

.timeframe-time div {
    padding: 0.25rem;
    font-size: 0.85rem;
    color: #495057;
}

.timeframe-price {
    margin-top: auto;
    padding-top: 0.75rem;
    border-top: 2px solid #e9ecef;
    width: 100%;
}

.timeframe-price .price-label {
    display: block;
    font-size: 0.75rem;
    color: #6c757d;
    margin-bottom: 0.15rem;
}

.timeframe-price .price-value {
    display: block;
    font-size: 1.4rem;
    font-weight: 700;
    color: #198754;
}

.booked-badge {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: #dc3545;
    color: white;
    padding: 0.4rem 0.8rem;
    border-radius: 0.5rem;
    font-size: 0.85rem;
    font-weight: 600;
}

/* Calendar Section */
.calendar-section {
    max-width: 750px;
    margin: 0 auto;
}

.calendar-controls {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
}

.calendar-month-display {
    flex: 0 0 160px;
}

.calendar-month-display .form-control {
    font-size: 0.85rem;
    padding: 0.375rem 0.5rem;
}

.calendar-legend {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    justify-content: center;
    margin-bottom: 0.75rem;
    padding: 0.5rem;
    background: #f8f9fa;
    border-radius: 0.375rem;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.7rem;
}

.legend-color {
    width: 14px;
    height: 14px;
    border-radius: 0.2rem;
    border: 1px solid #dee2e6;
}

.legend-color.weekday { background: #d1e7dd; }
.legend-color.weekend { background: #fff3cd; }
.legend-color.holiday { background: #cff4fc; }
.legend-color.partial { background: #dbe4ff; }
.legend-color.unavailable { background: #f8d7da; }

.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 0.3rem;
    padding: 0.5rem;
    background: white;
    border-radius: 0.4rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.calendar-day-header {
    padding: 0.4rem 0.2rem;
    text-align: center;
    font-weight: 700;
    background: #495057;
    color: white;
    border-radius: 0.2rem;
    font-size: 0.7rem;
}

.calendar-day {
    aspect-ratio: 1;
    border: 2px solid #dee2e6;
    border-radius: 0.3rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    padding: 0.3rem;
    background: white;
    min-height: 55px;
}

.calendar-day:hover:not(.disabled) {
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    z-index: 10;
}

.calendar-day.available {
    background: #d1e7dd;
    border-color: #198754;
}

.calendar-day.weekend {
    background: #fff3cd;
    border-color: #ffc107;
}

.calendar-day.holiday {
    background: #cff4fc;
    border-color: #0dcaf0;
}

.calendar-day.partially_booked {
    background: #dbe4ff;
    border-color: #0d6efd;
    position: relative;
}

.calendar-day.partially_booked:hover::after {
    content: attr(data-tooltip);
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(0, 0, 0, 0.9);
    color: white;
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 0.75rem;
    white-space: pre-wrap;
    z-index: 1000;
    margin-bottom: 5px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    pointer-events: none;
}

.calendar-day.partially_booked:hover::before {
    content: '';
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    border: 6px solid transparent;
    border-top-color: rgba(0, 0, 0, 0.9);
    z-index: 999;
    margin-bottom: -6px;
}

.calendar-day.fully_booked,
.calendar-day.unavailable {
    background: #f8d7da;
    border-color: #dc3545;
}

.calendar-day.blocked {
    background: #e2e3e5;
    border-color: #6c757d;
}

.calendar-day.past {
    background: #f8f9fa;
    color: #adb5bd;
    cursor: not-allowed;
    opacity: 0.5;
}

.calendar-day.selected {
    background: #0d6efd !important;
    color: white !important;
    border-color: #0d6efd !important;
    transform: scale(1.1);
    box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.25);
}

.calendar-day.disabled {
    cursor: not-allowed;
    opacity: 0.5;
}

.calendar-day .day-number {
    font-size: 1rem;
    font-weight: 800;
    color: #212529;
}

.calendar-day .day-status {
    font-size: 0.55rem;
    text-transform: uppercase;
    margin-top: 0.15rem;
    text-align: center;
    line-height: 1.1;
    font-weight: 600;
    color: #495057;
}

.selected-date-card {
    margin-top: 0.75rem;
    padding: 0.6rem 0.85rem;
    background: linear-gradient(135deg, #d1e7dd 0%, #ffffff 100%);
    border: 2px solid #198754;
    border-radius: 0.4rem;
    display: flex;
    align-items: center;
    gap: 0.6rem;
}

.selected-date-card i {
    font-size: 1.1rem;
}

.selected-date-info {
    font-size: 0.875rem;
}

.selected-date-info strong {
    display: block;
    margin-bottom: 0.1rem;
}

/* Facilities Grid */
.facilities-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
    gap: 1rem;
}

.facility-option {
    position: relative;
}

.facility-card {
    display: block;
    cursor: pointer;
    border: 2px solid #dee2e6;
    border-radius: 0.75rem;
    overflow: hidden;
    background: white;
    transition: all 0.3s ease;
    height: 100%;
}

.facility-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 16px rgba(0,0,0,0.15);
    border-color: #0d6efd;
}

.facility-card.selected {
    border-color: #0d6efd;
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.25);
}

.facility-card.blocked {
    opacity: 0.6;
    cursor: not-allowed;
    background: #f8f9fa;
}

.facility-image {
    position: relative;
    width: 100%;
    height: 120px;
    overflow: hidden;
}

.facility-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.facility-checkbox-wrapper {
    position: absolute;
    top: 0.75rem;
    left: 0.75rem;
    z-index: 10;
}

.facility-checkbox {
    width: 1.5rem;
    height: 1.5rem;
    cursor: pointer;
}

.facility-details {
    padding: 0.75rem;
}

.facility-details h5 {
    font-size: 1rem;
    font-weight: 700;
    margin-bottom: 0.35rem;
}

.facility-details p {
    font-size: 0.8rem;
    color: #6c757d;
    margin-bottom: 0.5rem;
}

.facility-price {
    font-size: 1.1rem;
    font-weight: 700;
    color: #198754;
}

.blocked-badge {
    position: absolute;
    top: 0.75rem;
    right: 0.75rem;
    background: rgba(0, 0, 0, 0.7);
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 0.5rem;
    font-size: 0.85rem;
    font-weight: 600;
}

/* Summary Container */
.summary-container {
    max-width: 800px;
    margin: 0 auto;
}

.summary-section {
    background: white;
    border: 2px solid #dee2e6;
    border-radius: 0.5rem;
    padding: 1rem;
    margin-bottom: 1rem;
}

.summary-section h5 {
    font-size: 1.1rem;
    font-weight: 700;
    margin-bottom: 0.75rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #e9ecef;
}

.summary-grid {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.summary-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.6rem;
    background: #f8f9fa;
    border-radius: 0.4rem;
}

.summary-label {
    font-weight: 600;
    color: #495057;
}

.summary-value {
    font-weight: 700;
    color: #212529;
}

.facilities-summary-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.facility-summary-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.6rem;
    background: #f8f9fa;
    border-radius: 0.4rem;
}

.facility-summary-item i {
    font-size: 1.25rem;
    color: #0d6efd;
}

.facility-summary-info {
    flex: 1;
}

.facility-summary-info strong {
    display: block;
    margin-bottom: 0.25rem;
}

.facility-summary-info small {
    color: #6c757d;
}

.facility-summary-price {
    font-weight: 700;
    color: #198754;
}

.price-breakdown {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.price-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.6rem;
    background: #f8f9fa;
    border-radius: 0.4rem;
}

.price-item.total {
    background: linear-gradient(135deg, #0d6efd 0%, #0dcaf0 100%);
    color: white;
    font-size: 1.1rem;
    font-weight: 700;
    padding: 0.75rem;
}

.price-item.total .price-value {
    color: white;
}

.price-value {
    font-weight: 700;
    color: #198754;
    font-size: 1rem;
}

.surcharges-list,
.facility-prices-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.surcharge-item,
.facility-price-item {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0.75rem;
    font-size: 0.95rem;
    color: #495057;
}

/* Navigation Buttons */
.wizard-navigation {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 1.5rem;
    padding-top: 1rem;
    border-top: 2px solid #e9ecef;
}

.btn-nav {
    padding: 0.65rem 1.5rem;
    font-size: 0.95rem;
    font-weight: 600;
    border: none;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.4rem;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.btn-prev {
    background: #6c757d;
    color: white;
}

.btn-prev:hover {
    background: #5a6268;
    transform: translateX(-3px);
    box-shadow: 0 3px 8px rgba(0,0,0,0.2);
}

.btn-next {
    background: #0d6efd;
    color: white;
}

.btn-next:hover:not(:disabled) {
    background: #0b5ed7;
    transform: translateX(3px);
    box-shadow: 0 3px 8px rgba(0,0,0,0.2);
}

.btn-submit {
    background: linear-gradient(135deg, #198754 0%, #20c997 100%);
    color: white;
}

.btn-submit:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(25, 135, 84, 0.4);
}

.btn-nav:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.nav-center {
    flex: 1;
    display: flex;
    justify-content: center;
}

.step-indicator-dots {
    display: flex;
    gap: 0.75rem;
}

.dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #dee2e6;
    cursor: pointer;
    transition: all 0.3s ease;
}

.dot.active {
    background: #0d6efd;
    transform: scale(1.3);
}

.dot.completed {
    background: #198754;
}

/* Responsive Design */
@media (max-width: 768px) {
    .progress-steps {
        padding: 0 2%;
    }
    
    .step-circle {
        width: 40px;
        height: 40px;
    }
    
    .step-circle i {
        font-size: 1rem;
    }
    
    .step-label {
        font-size: 0.7rem;
    }
    
    .step-header h2 {
        font-size: 1.25rem;
    }
    
    .step-header p {
        font-size: 0.85rem;
    }
    
    .resort-grid,
    .timeframe-grid,
    .facilities-grid {
        grid-template-columns: 1fr;
    }
    
    .calendar-grid {
        gap: 0.2rem;
        padding: 0.5rem;
    }
    
    .calendar-day {
        padding: 0.2rem;
    }
    
    .calendar-day .day-number {
        font-size: 0.85rem;
    }
    
    .calendar-day .day-status {
        font-size: 0.5rem;
    }
    
    .wizard-navigation {
        flex-wrap: wrap;
        gap: 0.75rem;
    }
    
    .btn-nav {
        flex: 1;
        min-width: 110px;
        justify-content: center;
        padding: 0.6rem 1rem;
        font-size: 0.9rem;
    }
    
    .nav-center {
        order: -1;
        width: 100%;
    }
}

/* Loading Animation */
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Smooth Transitions */
* {
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}
</style>

<!-- JavaScript -->
<script>
// Admin contact information
const adminPhone = '<?= htmlspecialchars($adminContact['PhoneNumber'] ?? '') ?>';
const adminEmail = '<?= htmlspecialchars($adminContact['Email'] ?? '') ?>';

// Resort pricing data
const resortPricingData = {
    <?php foreach ($resorts as $resort): ?>
        '<?= $resort->resortId ?>': {
            hasCompletePricing: <?= $resort->hasCompletePricing ? 'true' : 'false' ?>,
            name: '<?= htmlspecialchars($resort->name) ?>'
        },
    <?php endforeach; ?>
};

document.addEventListener('DOMContentLoaded', function() {
    // State management
    const state = {
        currentStep: 1,
        totalSteps: 5,
        selectedResortId: null,
        selectedTimeframe: null,
        selectedDate: null,
        selectedFacilities: [],
        pricingData: null,
        calendarData: {},
        isGuest: <?= !isset($_SESSION['user_id']) ? 'true' : 'false' ?>
    };

    // DOM Elements
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const submitBtn = document.getElementById('submitBtn');
    const steps = document.querySelectorAll('.wizard-step');
    const stepItems = document.querySelectorAll('.step-item');
    const dots = document.querySelectorAll('.dot');
    const progressBarFill = document.getElementById('progressBarFill');
    const bookingForm = document.getElementById('bookingForm');

    // Initialize
    init();

    function init() {
        setupEventListeners();
        checkPreselection();
        updateUI();
    }

    function setupEventListeners() {
        // Navigation buttons
        prevBtn.addEventListener('click', () => changeStep(state.currentStep - 1));
        nextBtn.addEventListener('click', () => validateAndProceed());
        
        // Resort selection
        document.querySelectorAll('.resort-radio').forEach(radio => {
            radio.addEventListener('change', handleResortSelection);
        });
        
        // Timeframe selection
        document.querySelectorAll('.timeframe-radio').forEach(radio => {
            radio.addEventListener('change', handleTimeframeSelection);
        });
        
        // Calendar controls
        document.getElementById('prevMonth')?.addEventListener('click', () => changeMonth(-1));
        document.getElementById('nextMonth')?.addEventListener('click', () => changeMonth(1));
        document.getElementById('calendarMonth')?.addEventListener('change', loadCalendarData);
        
        // Dots navigation
        dots.forEach(dot => {
            dot.addEventListener('click', function() {
                const step = parseInt(this.dataset.step);
                if (canNavigateToStep(step)) {
                    changeStep(step);
                }
            });
        });
        
        // Form submission
        if (!state.isGuest) {
            const termsModal = document.getElementById('termsModal');
            if (termsModal) {
                setupTermsModal();
            }
        } else {
            submitBtn.addEventListener('click', handleGuestSubmit);
        }
    }

    function checkPreselection() {
        <?php if ($selectedResortId): ?>
            const resortRadio = document.querySelector('input[name="resort_id"][value="<?= $selectedResortId ?>"]');
            if (resortRadio) {
                resortRadio.checked = true;
                state.selectedResortId = '<?= $selectedResortId ?>';
                
                // Load all timeframe pricing for pre-selected resort
                loadAllTimeframePricing('<?= $selectedResortId ?>');
                
                // Visual feedback
                document.querySelectorAll('.resort-option').forEach(opt => {
                    opt.classList.remove('selected');
                });
                resortRadio.closest('.resort-option').classList.add('selected');
            }
        <?php endif; ?>
    }

    function handleResortSelection(e) {
        const resortId = e.target.value;
        state.selectedResortId = resortId;
        
        // Check pricing completeness
        const resortData = resortPricingData[resortId];
        if (resortData && !resortData.hasCompletePricing) {
            showPricingIncompleteNotice(resortData.name);
            state.selectedResortId = null;
            e.target.checked = false;
            return;
        }
        
        hidePricingIncompleteNotice();
        
        // Visual feedback
        document.querySelectorAll('.resort-option').forEach(opt => {
            opt.classList.remove('selected');
        });
        e.target.closest('.resort-option').classList.add('selected');
        
        // Load pricing for all timeframes when resort is selected
        loadAllTimeframePricing(resortId);
        
        // Auto-advance after selection
        setTimeout(() => {
            if (state.currentStep === 1) {
                changeStep(2);
            }
        }, 500);
        
        updateUI();
    }

    function handleTimeframeSelection(e) {
        const timeframe = e.target.value;
        state.selectedTimeframe = timeframe;
        
        // Visual feedback
        document.querySelectorAll('.timeframe-option').forEach(opt => {
            opt.classList.remove('selected');
        });
        e.target.closest('.timeframe-option').classList.add('selected');
        
        // Load pricing
        if (state.selectedResortId) {
            loadTimeframePricing(state.selectedResortId, timeframe);
        }
        
        // Auto-advance after selection
        setTimeout(() => {
            if (state.currentStep === 2) {
                changeStep(3);
            }
        }, 500);
        
        updateUI();
    }

    function handleDateSelection(dateStr) {
        state.selectedDate = dateStr;
        
        const dateInput = document.getElementById('date');
        if (dateInput) {
            dateInput.value = dateStr;
        }
        
        // Show selected date
        const selectedDateDisplay = document.getElementById('selectedDateDisplay');
        const selectedDateText = document.getElementById('selectedDateText');
        if (selectedDateDisplay && selectedDateText) {
            const dateObj = new Date(dateStr + 'T00:00:00');
            const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
            
            const formattedDate = `${dayNames[dateObj.getDay()]}, ${monthNames[dateObj.getMonth()]} ${dateObj.getDate()}, ${dateObj.getFullYear()}`;
            selectedDateText.textContent = formattedDate;
            selectedDateDisplay.style.display = 'flex';
        }
        
        // Load facilities for the selected date
        loadFacilities(state.selectedResortId, dateStr);
        
        // Auto-advance after selection
        setTimeout(() => {
            if (state.currentStep === 3) {
                changeStep(4);
            }
        }, 500);
        
        updateUI();
    }

    function changeStep(newStep) {
        if (newStep < 1 || newStep > state.totalSteps) return;
        if (!canNavigateToStep(newStep)) return;
        
        const oldStep = state.currentStep;
        state.currentStep = newStep;
        
        // Update step classes
        steps.forEach((step, index) => {
            step.classList.remove('active', 'prev');
            if (index + 1 === newStep) {
                step.classList.add('active');
            } else if (index + 1 < newStep) {
                step.classList.add('prev');
            }
        });
        
        // Load calendar when entering step 3
        if (newStep === 3 && oldStep !== 3) {
            loadCalendarData();
        }
        
        updateUI();
        
        // Scroll to top
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function canNavigateToStep(step) {
        // Can always go back
        if (step < state.currentStep) return true;
        
        // Can go to next step only if current is valid
        switch (step) {
            case 2:
                return state.selectedResortId !== null;
            case 3:
                return state.selectedResortId !== null && state.selectedTimeframe !== null;
            case 4:
                return state.selectedResortId !== null && state.selectedTimeframe !== null && state.selectedDate !== null;
            case 5:
                return state.selectedResortId !== null && state.selectedTimeframe !== null && state.selectedDate !== null;
            default:
                return true;
        }
    }

    function validateAndProceed() {
        let isValid = false;
        
        switch (state.currentStep) {
            case 1:
                isValid = state.selectedResortId !== null;
                if (!isValid) showError('Please select a resort');
                break;
            case 2:
                isValid = state.selectedTimeframe !== null;
                if (!isValid) showError('Please select a timeframe');
                break;
            case 3:
                isValid = state.selectedDate !== null;
                if (!isValid) showError('Please select a date');
                break;
            case 4:
                // Facilities are optional
                isValid = true;
                break;
            case 5:
                isValid = true;
                break;
        }
        
        if (isValid) {
            changeStep(state.currentStep + 1);
        }
    }

    function updateUI() {
        // Update progress bar - calculate width to stay within the track (10% to 90%)
        const progress = ((state.currentStep - 1) / (state.totalSteps - 1)) * 80;
        progressBarFill.style.width = `${progress}%`;
        
        // Update step indicators
        stepItems.forEach((item, index) => {
            const stepNum = index + 1;
            item.classList.remove('active', 'completed');
            
            if (stepNum < state.currentStep) {
                item.classList.add('completed');
            } else if (stepNum === state.currentStep) {
                item.classList.add('active');
            }
        });
        
        // Update dots
        dots.forEach((dot, index) => {
            const stepNum = index + 1;
            dot.classList.remove('active', 'completed');
            
            if (stepNum < state.currentStep) {
                dot.classList.add('completed');
            } else if (stepNum === state.currentStep) {
                dot.classList.add('active');
            }
        });
        
        // Update navigation buttons
        prevBtn.style.visibility = state.currentStep > 1 ? 'visible' : 'hidden';
        
        if (state.currentStep < state.totalSteps) {
            nextBtn.style.display = 'flex';
            submitBtn.style.display = 'none';
            nextBtn.disabled = !canNavigateToStep(state.currentStep + 1);
        } else {
            nextBtn.style.display = 'none';
            submitBtn.style.display = 'flex';
            submitBtn.disabled = !isFormValid();
        }
        
        // Update summary on final step
        if (state.currentStep === 5) {
            updateSummary();
        }
    }

    function isFormValid() {
        return state.selectedResortId !== null &&
               state.selectedTimeframe !== null &&
               state.selectedDate !== null;
    }

    function loadTimeframePricing(resortId, timeframe) {
        // Load pricing for the selected timeframe and store it
        fetch(`?controller=booking&action=getResortPricing&resort_id=${resortId}&timeframe=${timeframe}&date=${new Date().toISOString().split('T')[0]}`)
            .then(response => response.json())
            .then(data => {
                state.pricingData = data;
                
                // Update the selected timeframe price
                const priceElement = document.querySelector(`#price_${timeframe} .price-value`);
                if (priceElement) {
                    priceElement.textContent = data.basePriceDisplay;
                }
            })
            .catch(error => {
                console.error('Error loading pricing:', error);
                showError('Failed to load pricing information');
            });
    }
    
    function loadAllTimeframePricing(resortId) {
        const timeframes = ['12_hours', '24_hours', 'overnight'];
        const currentDate = new Date().toISOString().split('T')[0];
        
        // Load pricing for all timeframes
        timeframes.forEach(timeframe => {
            fetch(`?controller=booking&action=getResortPricing&resort_id=${resortId}&timeframe=${timeframe}&date=${currentDate}`)
                .then(response => response.json())
                .then(data => {
                    const priceElement = document.querySelector(`#price_${timeframe} .price-value`);
                    if (priceElement) {
                        priceElement.textContent = data.basePriceDisplay;
                    }
                })
                .catch(error => {
                    console.error(`Error loading pricing for ${timeframe}:`, error);
                    const priceElement = document.querySelector(`#price_${timeframe} .price-value`);
                    if (priceElement) {
                        priceElement.textContent = '₱0.00';
                    }
                });
        });
    }

    function loadCalendarData() {
        if (!state.selectedResortId || !state.selectedTimeframe) return;
        
        const calendarMonth = document.getElementById('calendarMonth').value;
        const calendarLoading = document.getElementById('calendarLoading');
        const calendarGrid = document.getElementById('calendarGrid');
        
        calendarLoading.style.display = 'block';
        calendarGrid.style.display = 'none';
        
        fetch(`?controller=booking&action=getCalendarAvailability&resort_id=${state.selectedResortId}&timeframe=${state.selectedTimeframe}&month=${calendarMonth}`)
            .then(response => response.json())
            .then(data => {
                state.calendarData = data.availability;
                renderCalendar(data.month);
                calendarLoading.style.display = 'none';
                calendarGrid.style.display = 'grid';
            })
            .catch(error => {
                console.error('Error loading calendar:', error);
                calendarLoading.innerHTML = '<div class="alert alert-danger">Failed to load calendar</div>';
            });
    }

    function renderCalendar(month) {
        const [year, monthNum] = month.split('-');
        const calendarGrid = document.getElementById('calendarGrid');
        
        let html = '';
        
        // Day headers
        const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        dayNames.forEach(day => {
            html += `<div class="calendar-day-header">${day}</div>`;
        });
        
        // Generate calendar days
        const startOfMonth = new Date(Date.UTC(parseInt(year), parseInt(monthNum) - 1, 1));
        const startCalendarDate = new Date(startOfMonth);
        startCalendarDate.setUTCDate(startCalendarDate.getUTCDate() - startOfMonth.getUTCDay());
        
        for (let i = 0; i < 42; i++) {
            const currentDate = new Date(startCalendarDate);
            currentDate.setUTCDate(startCalendarDate.getUTCDate() + i);
            
            const dateStr = currentDate.toISOString().split('T')[0];
            const dayNum = currentDate.getUTCDate();
            const isCurrentMonth = currentDate.getUTCMonth() === startOfMonth.getUTCMonth();
            
            let dayClass = 'calendar-day';
            let dayData = state.calendarData[dateStr];
            
            if (!isCurrentMonth) {
                dayClass += ' disabled';
            } else if (dayData) {
                dayClass += ` ${dayData.status}`;
                if (!dayData.available) {
                    dayClass += ' disabled';
                }
            } else {
                dayClass += ' disabled';
            }
            
            if (dateStr === state.selectedDate) {
                dayClass += ' selected';
            }
            
            const statusText = dayData ? dayData.statusText : '';
            
            // Build tooltip for partially booked days
            let tooltipText = '';
            if (dayData && dayData.status === 'partially_booked' && dayData.availableTimeframes) {
                const timeframeLabels = {
                    '12_hours': '12 Hours (7AM-5PM)',
                    '24_hours': '24 Hours (7AM-5AM)',
                    'overnight': 'Overnight (7PM-5AM)'
                };
                // Replace all spaces with non-breaking spaces and replace hyphen with a non-breaking hyphen (\u2011) to prevent wrapping within the time slot label
                const availableLabels = dayData.availableTimeframes.map(tf => (timeframeLabels[tf] || tf)
                    .replace(/ /g, '\u00A0')
                    .replace(/-/g, '\u2011')
                );
                tooltipText = 'Available:\n' + availableLabels.join('\n');
            }
            
            html += `
                <div class="${dayClass}" data-date="${dateStr}" ${tooltipText ? `data-tooltip="${tooltipText}"` : ''}>
                    <div class="day-number">${dayNum}</div>
                    <div class="day-status">${statusText}</div>
                </div>
            `;
        }
        
        calendarGrid.innerHTML = html;
        
        // Add click handlers
        calendarGrid.querySelectorAll('.calendar-day:not(.disabled)').forEach(day => {
            day.addEventListener('click', function() {
                const dateStr = this.dataset.date;
                const dayData = state.calendarData[dateStr];
                
                // Check if this is a partially booked date
                if (dayData && dayData.status === 'partially_booked') {
                    // Check if the user's selected timeframe is available
                    if (state.selectedTimeframe && !dayData.availableTimeframes.includes(state.selectedTimeframe)) {
                        const timeframeLabels = {
                            '12_hours': '12 Hours (7:00 AM - 5:00 PM)',
                            '24_hours': '24 Hours (7:00 AM - 5:00 AM next day)',
                            'overnight': 'Overnight (7:00 PM - 5:00 AM next day)'
                        };
                        
                        const oldSelectedLabel = timeframeLabels[state.selectedTimeframe];
                        const availableTimeframe = dayData.availableTimeframes[0]; // Auto-select first available
                        const newSelectedLabel = timeframeLabels[availableTimeframe];
                        
                        // Show modal instead of confirm dialog
                        showTimeframeConflictModal(oldSelectedLabel, newSelectedLabel, availableTimeframe, dateStr);
                        return;
                    }
                }
                
                // Remove previous selection
                calendarGrid.querySelectorAll('.calendar-day').forEach(d => {
                    d.classList.remove('selected');
                });
                
                // Add new selection
                this.classList.add('selected');
                
                handleDateSelection(dateStr);
            });
        });
    }

    function changeMonth(direction) {
        const calendarMonth = document.getElementById('calendarMonth');
        const [year, month] = calendarMonth.value.split('-');
        const date = new Date(year, parseInt(month) - 1 + direction, 1);
        
        const newYear = date.getFullYear();
        const newMonth = String(date.getMonth() + 1).padStart(2, '0');
        const newValue = `${newYear}-${newMonth}`;
        
        // Get min and max values from the input element
        const minValue = calendarMonth.getAttribute('min');
        const maxValue = calendarMonth.getAttribute('max');
        
        // Validate the new value is within the allowed range
        if (newValue < minValue || newValue > maxValue) {
            // Don't change if out of range
            return;
        }
        
        calendarMonth.value = newValue;
        loadCalendarData();
    }

    function loadFacilities(resortId, date) {
        const facilitiesContainer = document.getElementById('facilitiesContainer');
        facilitiesContainer.innerHTML = '<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x"></i><p class="mt-3">Loading facilities...</p></div>';
        
        fetch(`?controller=booking&action=getFacilitiesByResort&resort_id=${resortId}&date=${date}`)
            .then(response => response.json())
            .then(facilities => {
                renderFacilities(facilities);
            })
            .catch(error => {
                console.error('Error loading facilities:', error);
                facilitiesContainer.innerHTML = '<div class="alert alert-danger">Failed to load facilities</div>';
            });
    }

    function renderFacilities(facilities) {
        const facilitiesContainer = document.getElementById('facilitiesContainer');
        
        if (facilities.length === 0) {
            facilitiesContainer.innerHTML = `
                <div class="col-12 text-center py-5">
                    <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No additional facilities available for this resort</p>
                    <p class="text-muted small">Click "Continue" to proceed to the summary</p>
                </div>
            `;
            return;
        }
        
        let html = '';
        facilities.forEach(facility => {
            const isBlocked = facility.isBlocked;
            const cardClass = isBlocked ? 'facility-card blocked' : 'facility-card';
            const checkboxDisabled = isBlocked ? 'disabled' : '';
            
            html += `
                <div class="facility-option">
                    <div class="${cardClass}" data-facility-id="${facility.facilityId}">
                        <div class="facility-image">
                            <img src="${facility.mainPhotoURL}" alt="${facility.name}">
                            <div class="facility-checkbox-wrapper">
                                <input type="checkbox" class="facility-checkbox" 
                                       value="${facility.facilityId}" 
                                       data-price="${facility.rate}"
                                       data-name="${facility.name}"
                                       ${checkboxDisabled}>
                            </div>
                            ${isBlocked ? '<div class="blocked-badge"><i class="fas fa-ban me-1"></i>Unavailable</div>' : ''}
                        </div>
                        <div class="facility-details">
                            <h5><i class="${facility.icon} me-2"></i>${facility.name}</h5>
                            <p>${facility.shortDescription || ''}</p>
                            <div class="facility-price">${facility.priceDisplay}</div>
                        </div>
                    </div>
                </div>
            `;
        });
        
        facilitiesContainer.innerHTML = html;
        
        // Add event listeners
        facilitiesContainer.querySelectorAll('.facility-checkbox:not([disabled])').forEach(checkbox => {
            checkbox.addEventListener('change', handleFacilitySelection);
        });
        
        facilitiesContainer.querySelectorAll('.facility-card:not(.blocked)').forEach(card => {
            card.addEventListener('click', function(e) {
                if (e.target.classList.contains('facility-checkbox')) return;
                
                const checkbox = this.querySelector('.facility-checkbox');
                if (checkbox && !checkbox.disabled) {
                    checkbox.checked = !checkbox.checked;
                    checkbox.dispatchEvent(new Event('change'));
                }
            });
        });
    }

    function handleFacilitySelection() {
        state.selectedFacilities = [];
        
        document.querySelectorAll('.facility-checkbox').forEach(checkbox => {
            const card = checkbox.closest('.facility-card');
            
            if (checkbox.checked) {
                state.selectedFacilities.push({
                    id: checkbox.value,
                    name: checkbox.dataset.name,
                    price: parseFloat(checkbox.dataset.price)
                });
                card.classList.add('selected');
            } else {
                card.classList.remove('selected');
            }
        });
        
        updateUI();
    }

    function updateSummary() {
        // Update resort
        const summaryResort = document.getElementById('summaryResort');
        if (state.selectedResortId && summaryResort) {
            const resortRadio = document.querySelector(`input[name="resort_id"][value="${state.selectedResortId}"]`);
            const resortCard = resortRadio?.closest('.resort-option');
            const resortName = resortCard?.querySelector('h4')?.textContent.trim() || '-';
            summaryResort.textContent = resortName;
        }
        
        // Update timeframe
        const summaryTimeframe = document.getElementById('summaryTimeframe');
        if (state.selectedTimeframe && summaryTimeframe) {
            const timeframeLabels = {
                '12_hours': '12 Hours (7:00 AM - 5:00 PM)',
                '24_hours': '24 Hours (7:00 AM - 5:00 AM next day)',
                'overnight': 'Overnight (7:00 PM - 5:00 AM next day)'
            };
            summaryTimeframe.textContent = timeframeLabels[state.selectedTimeframe] || '-';
        }
        
        // Update date
        const summaryDate = document.getElementById('summaryDate');
        if (state.selectedDate && summaryDate) {
            const dateObj = new Date(state.selectedDate + 'T00:00:00');
            const options = { year: 'numeric', month: 'long', day: 'numeric', weekday: 'long' };
            summaryDate.textContent = dateObj.toLocaleDateString('en-US', options);
        }
        
        // Update facilities section
        const facilitiesSummarySection = document.getElementById('facilitiesSummarySection');
        const facilitiesSummaryList = document.getElementById('facilitiesSummaryList');
        
        if (state.selectedFacilities.length > 0) {
            facilitiesSummarySection.style.display = 'block';
            
            let facilitiesHtml = '';
            state.selectedFacilities.forEach(facility => {
                facilitiesHtml += `
                    <div class="facility-summary-item">
                        <i class="fas fa-swimming-pool"></i>
                        <div class="facility-summary-info">
                            <strong>${facility.name}</strong>
                        </div>
                        <div class="facility-summary-price">₱${facility.price.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                    </div>
                `;
            });
            facilitiesSummaryList.innerHTML = facilitiesHtml;
        } else {
            facilitiesSummarySection.style.display = 'none';
        }
        
        // Calculate and update prices
        calculateTotalPrice();
    }

    function calculateTotalPrice() {
        if (!state.selectedResortId || !state.selectedTimeframe || !state.selectedDate) return;
        
        // First, reload pricing data with the actual selected date to get correct surcharges
        fetch(`?controller=booking&action=getResortPricing&resort_id=${state.selectedResortId}&timeframe=${state.selectedTimeframe}&date=${state.selectedDate}`)
            .then(response => response.json())
            .then(pricingData => {
                // Update state with correct pricing data for the selected date
                state.pricingData = pricingData;
                
                // Update base price
                const summaryBasePrice = document.getElementById('summaryBasePrice');
                if (summaryBasePrice) {
                    summaryBasePrice.textContent = pricingData.basePriceDisplay;
                }
                
                // Update surcharges
                const summarySurcharges = document.getElementById('summarySurcharges');
                if (pricingData.appliedSurcharges && summarySurcharges) {
                    let surchargesHtml = '';
                    pricingData.appliedSurcharges.forEach(surcharge => {
                        const iconClass = surcharge.type === 'weekend' ? 'fa-calendar-week' : 'fa-star';
                        surchargesHtml += `
                            <div class="surcharge-item">
                                <span><i class="fas ${iconClass} me-2"></i>${surcharge.type.charAt(0).toUpperCase() + surcharge.type.slice(1)} Surcharge</span>
                                <span>+₱${parseFloat(surcharge.amount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                            </div>
                        `;
                    });
                    summarySurcharges.innerHTML = surchargesHtml;
                } else {
                    summarySurcharges.innerHTML = '';
                }
            
                // Now calculate the total booking price
                const formData = new FormData();
                formData.append('resort_id', state.selectedResortId);
                formData.append('timeframe', state.selectedTimeframe);
                formData.append('date', state.selectedDate);
                state.selectedFacilities.forEach(f => formData.append('facility_ids[]', f.id));
                
                return fetch('?controller=booking&action=calculateBookingPrice', {
                    method: 'POST',
                    body: formData
                });
            })
            .then(response => response.json())
            .then(data => {
                // Update facility prices
                const summaryFacilityPrices = document.getElementById('summaryFacilityPrices');
                if (state.selectedFacilities.length > 0 && summaryFacilityPrices) {
                    let facilitiesHtml = '';
                    state.selectedFacilities.forEach(facility => {
                        facilitiesHtml += `
                            <div class="facility-price-item">
                                <span><i class="fas fa-swimming-pool me-2"></i>${facility.name}</span>
                                <span>+₱${facility.price.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                            </div>
                        `;
                    });
                    summaryFacilityPrices.innerHTML = facilitiesHtml;
                } else {
                    summaryFacilityPrices.innerHTML = '';
                }
                
                // Update total price
                const summaryTotalPrice = document.getElementById('summaryTotalPrice');
                if (summaryTotalPrice) {
                    summaryTotalPrice.textContent = data.totalPriceDisplay;
                }
            })
            .catch(error => {
                console.error('Error calculating price:', error);
            });
    }

    function setupTermsModal() {
        const termsModal = document.getElementById('termsModal');
        const termsCheckbox = document.getElementById('termsCheckbox');
        const acceptTermsBtn = document.getElementById('acceptTermsBtn');
        const modalBody = termsModal.querySelector('.modal-body');
        const scrollNotice = document.getElementById('scroll-notice');
        
        termsModal.addEventListener('show.bs.modal', function() {
            // Reset state
            modalBody.scrollTop = 0;
            termsCheckbox.checked = false;
            termsCheckbox.disabled = true;
            acceptTermsBtn.disabled = true;
            
            // Show scroll notice
            if (scrollNotice) {
                scrollNotice.style.display = 'block';
            }
            
            // Fill in dynamic data
            const guestName = '<?= htmlspecialchars($_SESSION['user_name'] ?? 'Valued Customer') ?>';
            const resortRadio = document.querySelector(`input[name="resort_id"]:checked`);
            const resortName = resortRadio?.closest('.resort-option')?.querySelector('h4')?.textContent.trim() || 'Selected Resort';
            
            document.getElementById('termsGuestName').textContent = guestName;
            document.getElementById('termsResortName').textContent = resortName;
        });
        
        modalBody.addEventListener('scroll', function() {
            const isScrolledToBottom = modalBody.scrollHeight - modalBody.scrollTop <= modalBody.clientHeight + 5;
            
            if (isScrolledToBottom) {
                termsCheckbox.disabled = false;
                if (scrollNotice) {
                    scrollNotice.style.display = 'none';
                }
            } else {
                if (scrollNotice) {
                    scrollNotice.style.display = 'block';
                }
            }
        });
        
        termsCheckbox.addEventListener('change', function() {
            acceptTermsBtn.disabled = !this.checked;
        });
        
        acceptTermsBtn.addEventListener('click', function() {
            // Add hidden inputs for facilities
            bookingForm.querySelectorAll('input[name="facility_ids[]"]').forEach(input => input.remove());
            state.selectedFacilities.forEach(facility => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'facility_ids[]';
                input.value = facility.id;
                bookingForm.appendChild(input);
            });
            
            // Submit form
            bookingForm.submit();
        });
    }

    function handleGuestSubmit() {
        const confirmation = confirm("You need to be logged in to complete the reservation. Would you like to log in or register now?");
        if (confirmation) {
            const bookingDetails = {
                resort_id: state.selectedResortId,
                timeframe: state.selectedTimeframe,
                booking_date: state.selectedDate,
                facility_ids: state.selectedFacilities.map(f => f.id)
            };
            sessionStorage.setItem('pendingBooking', JSON.stringify(bookingDetails));
            window.location.href = '?action=login';
        }
    }

    function showError(message) {
        // Simple alert for now, could be enhanced with a toast notification
        alert(message);
    }

    function showPricingIncompleteNotice(resortName) {
        const notice = document.getElementById('pricingIncompleteNotice');
        document.getElementById('noticePhone').textContent = adminPhone || 'Not available';
        document.getElementById('noticeEmail').textContent = adminEmail || 'Not available';
        notice.style.display = 'block';
    }

    function hidePricingIncompleteNotice() {
        const notice = document.getElementById('pricingIncompleteNotice');
        notice.style.display = 'none';
    }

    // Timeframe conflict modal handler
    function showTimeframeConflictModal(oldTimeframe, newTimeframe, newTimeframeValue, dateStr) {
        // Update modal content
        document.getElementById('conflictOldTimeframe').textContent = oldTimeframe;
        document.getElementById('conflictNewTimeframe').textContent = newTimeframe;
        
        // Store context for confirmation
        const modal = new bootstrap.Modal(document.getElementById('timeframeConflictModal'));
        
        // Set up one-time confirmation handler
        const confirmBtn = document.getElementById('confirmTimeframeSwitch');
        const newHandler = function() {
            // Remove this handler
            confirmBtn.removeEventListener('click', newHandler);
            
            // Hide modal
            modal.hide();
            
            // Automatically switch to the new timeframe
            state.selectedTimeframe = newTimeframeValue;
            
            // Update UI to reflect the new timeframe selection
            document.querySelectorAll('.timeframe-radio').forEach(radio => {
                radio.checked = (radio.value === newTimeframeValue);
            });
            document.querySelectorAll('.timeframe-option').forEach(opt => {
                opt.classList.remove('selected');
            });
            const newTimeframeOption = document.querySelector(`input[value="${newTimeframeValue}"]`)?.closest('.timeframe-option');
            if (newTimeframeOption) {
                newTimeframeOption.classList.add('selected');
            }
            
            // Load pricing for the new timeframe
            if (state.selectedResortId) {
                loadTimeframePricing(state.selectedResortId, newTimeframeValue);
            }
            
            // Select the date and proceed
            const calendarGrid = document.getElementById('calendarGrid');
            calendarGrid.querySelectorAll('.calendar-day').forEach(d => {
                d.classList.remove('selected');
            });
            const selectedDay = calendarGrid.querySelector(`[data-date="${dateStr}"]`);
            if (selectedDay) {
                selectedDay.classList.add('selected');
            }
            
            handleDateSelection(dateStr);
            
            // Flash the timeframe card to draw attention
            setTimeout(() => {
                const selectedCard = document.querySelector(`input[value="${newTimeframeValue}"]`)?.closest('.timeframe-card');
                if (selectedCard) {
                    selectedCard.style.transition = 'all 0.3s ease';
                    selectedCard.style.transform = 'scale(1.05)';
                    selectedCard.style.boxShadow = '0 0 20px rgba(13, 110, 253, 0.5)';
                    setTimeout(() => {
                        selectedCard.style.transform = '';
                        selectedCard.style.boxShadow = '';
                    }, 600);
                }
            }, 100);
        };
        
        confirmBtn.addEventListener('click', newHandler);
        
        // Show modal
        modal.show();
    }
});
</script>

<?php 
unset($_SESSION['old_input']);
require_once __DIR__ . '/../partials/footer.php'; 
?>