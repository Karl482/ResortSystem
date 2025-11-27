<?php
$pageTitle = "On-Site Booking";
require_once __DIR__ . '/../partials/header.php';

$selectedResortId = filter_input(INPUT_GET, 'resort_id', FILTER_VALIDATE_INT);
$selectedFacilityId = filter_input(INPUT_GET, 'facility_id', FILTER_VALIDATE_INT);
?>

<!-- Enhanced Page Header -->
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <h1 class="mb-1"><i class="fas fa-calendar-plus text-primary"></i> <?= htmlspecialchars($pageTitle) ?></h1>
                <p class="text-muted mb-0">Create a confirmed, fully paid booking for an existing customer.</p>
            </div>
            <a href="?controller=admin&action=unifiedBookingManagement" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Management
            </a>
        </div>
    </div>
</div>

<!-- Progressive Step Indicators -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card bg-light">
            <div class="card-body py-3">
                <div class="row text-center">
                    <div class="col step-indicator active" id="step1">
                        <div class="step-circle">1</div>
                        <small>Customer</small>
                    </div>
                    <div class="col step-indicator" id="step2">
                        <div class="step-circle">2</div>
                        <small>Resort</small>
                    </div>
                    <div class="col step-indicator" id="step3">
                        <div class="step-circle">3</div>
                        <small>Timeframe</small>
                    </div>
                    <div class="col step-indicator" id="step4">
                        <div class="step-circle">4</div>
                        <small>Date</small>
                    </div>
                    <div class="col step-indicator" id="step5">
                        <div class="step-circle">5</div>
                        <small>Facilities</small>
                    </div>
                    <div class="col step-indicator" id="step6">
                        <div class="step-circle">6</div>
                        <small>Summary</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['error_message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                <?php unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>

        <!-- Pricing Incomplete Notice Card -->
        <div class="row mb-4" id="pricingIncompleteNotice" style="display: none;">
            <div class="col-12">
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <h6 class="alert-heading mb-2"><i class="fas fa-exclamation-triangle"></i> Pricing Setup Incomplete</h6>
                    <p class="mb-2">This resort has not completed setting up prices yet. Please configure pricing before creating a booking.</p>
                    <a href="?controller=admin&action=pricingManagement" class="btn btn-warning">Go to Pricing Management</a>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        </div>

        <form action="?controller=admin&action=createOnSiteBooking" method="POST" id="bookingForm">
            
            <!-- Step 1: Customer Selection -->
            <div class="mb-4">
                <label for="customer_id" class="form-label fw-bold">
                    <i class="fas fa-user-check text-primary"></i> Select Customer <span class="text-danger">*</span>
                </label>
                <select name="customer_id" id="customer_id" class="form-select" required>
                    <option value="" disabled selected>-- Choose a customer --</option>
                    <?php foreach ($customers as $customer): ?>
                        <option value="<?= $customer['UserID'] ?>" <?= (isset($_SESSION['old_input']['customer_id']) && $_SESSION['old_input']['customer_id'] == $customer['UserID']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($customer['FirstName'] . ' ' . $customer['LastName']) ?> (<?= htmlspecialchars($customer['Email']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Step 2: Enhanced Resort Selection (Required) -->
            <fieldset id="step2-fieldset">
            <div class="mb-4">
                <label for="resort" class="form-label fw-bold">
                    <i class="fas fa-map-marker-alt text-primary"></i> Select Resort <span class="text-danger">*</span>
                </label>
                <div class="row g-3" id="resort-selection-container">
                    <?php if (empty($resorts)): ?>
                        <div class="col-12">
                            <div class="alert alert-warning text-center">No resorts are currently available.</div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($resorts as $resort): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="card resort-card h-100">
                                    <label for="resort_<?= $resort->resortId ?>" class="form-check-label w-100 mb-0">
                                        <input type="radio" class="form-check-input resort-radio" name="resort_id"
                                               id="resort_<?= $resort->resortId ?>" value="<?= $resort->resortId ?>"
                                               <?= ($selectedResortId == $resort->resortId) ? 'checked' : '' ?>>
                                        <img src="<?= htmlspecialchars($resort->mainPhotoURL) ?>" class="card-img-top" alt="<?= htmlspecialchars($resort->name) ?>">
                                        <div class="card-body">
                                            <h5 class="card-title">
                                                <i class="<?= htmlspecialchars($resort->icon) ?> text-primary me-2"></i>
                                                <?= htmlspecialchars($resort->name) ?>
                                            </h5>
                                            <p class="card-text small text-muted"><?= nl2br(htmlspecialchars($resort->shortDescription)) ?></p>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div class="form-text">
                    <i class="fas fa-info-circle"></i> Your resort selection will determine available facilities and pricing
                </div>
            </div>
            </fieldset>

            <!-- Step 3: Enhanced Timeframe Selection (Required) -->
            <fieldset id="step3-fieldset">
            <div class="mb-4">
                <label class="form-label fw-bold">
                    <i class="fas fa-clock text-primary"></i> Select Timeframe <span class="text-danger">*</span>
                </label>
                <div class="row g-3" id="timeframe-selection-container">
                    <div class="col-md-4 col-sm-6">
                        <div class="card timeframe-card h-100 bg-warning-subtle">
                            <label for="timeframe_12_hours" class="form-check-label w-100 mb-0">
                                <input type="radio" class="form-check-input timeframe-radio" name="timeframe"
                                       id="timeframe_12_hours" value="12_hours"
                                       <?= (isset($_SESSION['old_input']['timeframe']) && $_SESSION['old_input']['timeframe'] == '12_hours') ? 'checked' : '' ?> required>
                                <div class="card-body text-center">
                                    <h5 class="card-title">
                                        <i class="fas fa-sun text-warning me-2"></i>
                                        12 Hours <span id="booked-12_hours" class="text-danger fw-bold small" style="display: none;">(Already Booked)</span>
                                    </h5>
                                    <div class="small text-muted">
                                        <div class="mb-1"><strong>Check In:</strong> 7:00 AM</div>
                                        <div><strong>Check Out:</strong> 5:00 PM</div>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-6">
                        <div class="card timeframe-card h-100 bg-info-subtle">
                            <label for="timeframe_24_hours" class="form-check-label w-100 mb-0">
                                <input type="radio" class="form-check-input timeframe-radio" name="timeframe"
                                       id="timeframe_24_hours" value="24_hours"
                                       <?= (isset($_SESSION['old_input']['timeframe']) && $_SESSION['old_input']['timeframe'] == '24_hours') ? 'checked' : '' ?>>
                                <div class="card-body text-center">
                                    <h5 class="card-title">
                                        <i class="fas fa-clock text-info me-2"></i>
                                        24 Hours <span id="booked-24_hours" class="text-danger fw-bold small" style="display: none;">(Already Booked)</span>
                                    </h5>
                                    <div class="small text-muted">
                                        <div class="mb-1"><strong>Check In:</strong> 7:00 AM</div>
                                        <div><strong>Check Out:</strong> 5:00 AM (next day)</div>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-12">
                        <div class="card timeframe-card h-100 bg-dark-subtle">
                            <label for="timeframe_overnight" class="form-check-label w-100 mb-0">
                                <input type="radio" class="form-check-input timeframe-radio" name="timeframe"
                                       id="timeframe_overnight" value="overnight"
                                       <?= (isset($_SESSION['old_input']['timeframe']) && $_SESSION['old_input']['timeframe'] == 'overnight') ? 'checked' : '' ?>>
                                <div class="card-body text-center">
                                    <h5 class="card-title">
                                        <i class="fas fa-moon text-secondary me-2"></i>
                                        Overnight <span id="booked-overnight" class="text-danger fw-bold small" style="display: none;">(Already Booked)</span>
                                    </h5>
                                    <div class="small text-muted">
                                        <div class="mb-1"><strong>Check In:</strong> 7:00 PM</div>
                                        <div><strong>Check Out:</strong> 5:00 AM (next day)</div>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="form-text">
                    <i class="fas fa-info-circle"></i> Select your preferred timeframe to enable date browsing and see pricing
                </div>
                <div id="timeframePricing" class="mt-2" style="display: none;">
                    <div class="card border-primary">
                        <div class="card-body py-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div>
                                        <strong class="text-primary">Base Price:</strong>
                                        <span id="basePriceDisplay" class="fs-5 fw-bold text-success">₱0.00</span>
                                    </div>
                                    <div id="surchargeBreakdown" class="mt-2 small">
                                        <!-- Additional surcharges will be populated here -->
                                    </div>
                                </div>
                                <div id="pricingNotices">
                                    <div id="weekendNotice" style="display: none;" class="badge bg-warning text-dark mb-1">
                                        <i class="fas fa-calendar-week"></i> Weekend Rate
                                    </div>
                                    <div id="holidayNotice" style="display: none;" class="badge bg-info text-dark">
                                        <i class="fas fa-star"></i> Holiday Rate
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </fieldset>

            <!-- Step 4: Enhanced Date Selection (Required) -->
            <fieldset id="step4-fieldset">
            <div class="mb-4">
                <label for="date" class="form-label fw-bold">
                    <i class="fas fa-calendar-alt text-primary"></i> Select Date <span class="text-danger">*</span>
                </label>
                <div class="row g-3">
                    <div class="col-md-6 col-sm-12">
                        <div class="card date-card h-100 bg-primary-subtle">
                            <div class="card-body text-center clickable-card">
                                <i class="fas fa-calendar-alt fa-2x text-primary mb-2"></i>
                                <h6 class="card-title text-primary fw-bold mb-1">Navigate Calendar</h6>
                                <p class="card-text small text-muted mb-0">Click to browse and select your date</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-text">
                    <i class="fas fa-info-circle"></i> Use the calendar modal to select your preferred date with real-time availability
                </div>
                <div id="dateAvailabilityInfo" class="mt-2" style="display: none;">
                    <div class="alert alert-info mb-0">
                        <small><span id="selectedDateInfo"></span></small>
                    </div>
                </div>
                <!-- Hidden date input for form submission -->
                <input type="hidden" id="date" name="booking_date"
                       value="<?= htmlspecialchars($_SESSION['old_input']['booking_date'] ?? '') ?>">
            </div>
            </fieldset>

            <!-- Step 5: Enhanced Facility Selection -->
            <fieldset id="step5-fieldset">
            <div class="mb-4">
                <label class="form-label fw-bold">
                    <i class="fas fa-swimming-pool text-primary"></i> Additional Facilities
                    <span class="text-muted">(Optional)</span>
                </label>
                <div class="form-text mb-3">
                    <i class="fas fa-plus-circle"></i> Select any additional facilities to enhance your booking experience
                </div>
                <div id="facilitiesContainer" class="row">
                    <div class="col-12">
                        <div class="alert alert-secondary text-center" id="noFacilitiesMessage">
                            <i class="fas fa-info-circle"></i> Please select a resort first to view available facilities
                        </div>
                    </div>
                </div>
            </div>
            </fieldset>

            <!-- Step 6: Enhanced Booking Summary -->
            <div class="mb-4">
                <div class="card border-primary shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-receipt"></i> Booking Summary</h5>
                    </div>
                    <div class="card-body">
                        <div id="bookingDetails" class="mb-3" style="display: none;">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item px-0">
                                    <div class="row align-items-center justify-content-center">
                                        <div class="col-auto">
                                            <span><i class="fas fa-user text-muted me-2"></i>Customer:</span>
                                        </div>
                                        <div class="col-auto">
                                            <strong id="summaryCustomer">N/A</strong>
                                        </div>
                                    </div>
                                </li>
                                <li class="list-group-item px-0">
                                    <div class="row align-items-center justify-content-center">
                                        <div class="col-auto">
                                            <span><i class="fas fa-building text-muted me-2"></i>Resort:</span>
                                        </div>
                                        <div class="col-auto">
                                            <strong id="summaryResort">N/A</strong>
                                        </div>
                                    </div>
                                </li>
                                <li class="list-group-item px-0">
                                    <div class="row align-items-center justify-content-center">
                                        <div class="col-auto">
                                            <span><i class="fas fa-clock text-muted me-2"></i>Timeframe:</span>
                                        </div>
                                        <div class="col-auto">
                                            <strong id="summaryTimeframeText">N/A</strong>
                                        </div>
                                    </div>
                                </li>
                                <li class="list-group-item px-0">
                                    <div class="row align-items-center justify-content-center">
                                        <div class="col-auto">
                                            <span><i class="fas fa-calendar-alt text-muted me-2"></i>Date:</span>
                                        </div>
                                        <div class="col-auto">
                                            <strong id="summaryDate">N/A</strong>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        <div id="pricingBreakdown" style="display: none;">
                            <h6 class="text-muted text-center">Pricing Details:</h6>
                            <div id="summaryPricingDetails">
                                <!-- Base price and surcharges will be populated here -->
                                <div class="row mb-2 py-2 justify-content-center">
                                    <div class="col-auto">
                                        <span><i class="fas fa-clock text-muted"></i> Base Price (<span id="summaryTimeframe"></span>):</span>
                                    </div>
                                    <div class="col-auto">
                                        <span id="summaryBasePrice" class="fw-bold text-success">₱0.00</span>
                                    </div>
                                </div>
                                <div id="summarySurchargeBreakdown">
                                    <!-- Surcharge breakdown will be populated here -->
                                </div>
                            </div>
                            <div id="facilityPricing">
                                <!-- Facility prices will be populated here -->
                            </div>
                            <hr class="my-3">
                            <div class="row align-items-center bg-light p-3 rounded justify-content-center">
                                <div class="col-auto">
                                    <span class="fs-5 fw-bold"><i class="fas fa-tag text-primary"></i> Total Price (Paid via Cash):</span>
                                </div>
                                <div class="col-auto">
                                    <span id="totalPriceDisplay" class="fs-4 fw-bold text-primary">₱0.00</span>
                                </div>
                            </div>
                        </div>
                        <div id="noPricingMessage" class="text-muted text-center py-4">
                            <i class="fas fa-calculator fa-2x mb-2 opacity-50"></i>
                            <p class="mb-0">Complete your selections to see your booking summary</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Enhanced Submit Section -->
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-success btn-lg shadow" id="submitBtn" disabled>
                    <i class="fas fa-check-circle"></i> Create Confirmed Booking
                </button>
                <a href="?controller=admin&action=unifiedBookingManagement" class="btn btn-outline-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>

<!-- Enhanced Calendar Modal -->
<div class="modal fade" id="calendarModal" tabindex="-1" aria-labelledby="calendarModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white py-2">
                <h5 class="modal-title" id="calendarModalLabel">
                    <i class="fas fa-calendar-alt"></i> Select Date - Availability Calendar
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="calendarLoading" class="text-center py-4">
                    <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                    <p class="mt-2">Loading availability...</p>
                </div>
                <div id="calendarContent" style="display: none;">
                    <div class="row mb-3">
                        <div class="col-12">
                            <input type="month" class="form-control form-control-sm" id="calendarMonth" value="<?= date('Y-m') ?>">
                        </div>
                    </div>
                    <div class="legend text-center mb-2">
                        <div class="d-flex flex-wrap gap-1 justify-content-center">
                            <span class="badge bg-success-subtle text-success-emphasis">Weekday</span>
                            <span class="badge bg-warning-subtle text-warning-emphasis">Weekend</span>
                            <span class="badge bg-info-subtle text-info-emphasis">Holiday</span>
                            <span class="badge bg-primary-subtle text-primary-emphasis">Partially Booked</span>
                            <span class="badge bg-danger-subtle text-danger-emphasis">Fully Booked</span>
                            <span class="badge bg-secondary-subtle text-secondary-emphasis">Blocked</span>
                        </div>
                    </div>
                    <div id="calendarGrid" class="calendar-grid">
                        <!-- Calendar will be populated here -->
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Close
                </button>
                <button type="button" class="btn btn-primary" id="selectDateBtn" disabled>
                    <i class="fas fa-check"></i> Select Date
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Add CSS for enhanced UI components -->
<style>
/* Hide native date picker calendar icon */
input[type="date"]::-webkit-calendar-picker-indicator {
    display: none;
    -webkit-appearance: none;
}

input[type="date"]::-webkit-inner-spin-button,
input[type="date"]::-webkit-clear-button {
    display: none;
    -webkit-appearance: none;
}

/* Firefox date input styling */
input[type="date"] {
    -moz-appearance: textfield;
}

/* Restore and always show number input arrows (spinners) */
/* For WebKit browsers (Chrome, Safari, Edge) */
input[type="number"].form-control::-webkit-inner-spin-button,
input[type="number"].form-control::-webkit-outer-spin-button {
    -webkit-appearance: inner-spin-button;
    opacity: 1;
}

/* For Firefox */
input[type="number"].form-control {
    -moz-appearance: number-input;
}

/* Step Indicators */
.step-indicator {
    transition: all 0.3s ease;
}

.step-circle {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background-color: #e9ecef;
    color: #6c757d;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin: 0 auto 5px;
    transition: all 0.3s ease;
}

.step-indicator.active .step-circle {
    background-color: #0d6efd;
    color: white;
    transform: scale(1.1);
}

.step-indicator.completed .step-circle {
    background-color: #198754;
    color: white;
}

.step-indicator.completed .step-circle::before {
    content: "✓";
}

/* Calendar Grid */
.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 1px;
    font-size: 0.7rem;
}

.calendar-day {
    aspect-ratio: 1 / 1.2;
    border: 1px solid #f0f0f0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    background-color: white;
    position: relative;
    padding: 1px;
    text-align: center;
}

.calendar-day:hover:not(.disabled) {
    transform: scale(1.05);
    z-index: 1;
}

.calendar-day.available {
    background-color: #d1e7dd;
    border-color: #198754;
    color: #0f5132;
}

.calendar-day.weekend {
    background-color: #fff3cd;
    border-color: #ffc107;
    color: #664d03;
}

.calendar-day.holiday {
   background-color: #cff4fc;
   border-color: #0dcaf0;
   color: #055160;
}

.calendar-day.unavailable {
    background-color: #f8d7da;
    border-color: #dc3545;
    color: #721c24;
}

.calendar-day.partially_booked {
   background-color: #dbe4ff;
   border-color: #0d6efd;
   color: #0a58ca;
}

.calendar-day.fully_booked {
   background-color: #f8d7da;
   border-color: #dc3545;
   color: #721c24;
}

.calendar-day.blocked {
    background-color: #e2e3e5;
    border-color: #6c757d;
    color: #495057;
}

.calendar-day.past {
    background-color: #f8f9fa;
    color: #adb5bd;
    cursor: not-allowed;
}

.calendar-day.selected {
    background-color: #0d6efd !important;
    color: white !important;
    border-color: #0d6efd !important;
    font-weight: bold;
}

.calendar-day.disabled {
    cursor: not-allowed;
    opacity: 0.5;
}

.calendar-day-header {
    background-color: #495057;
    color: white;
    font-weight: bold;
    border: 1px solid #495057;
    justify-content: center;
    padding: 5px;
}

.calendar-day .day-number {
    font-size: 1.1em;
    font-weight: bold;
}

.calendar-day .day-status {
    font-size: 0.65em;
    text-transform: uppercase;
    margin-top: 1px;
    font-weight: 500;
    line-height: 1.1;
}

/* Enhanced Resort Cards */
.resort-card {
    transition: all 0.3s ease;
    border: 2px solid transparent;
    cursor: pointer;
    position: relative;
}

.resort-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    border-color: #0d6efd;
}

.resort-card .card-img-top {
    height: 150px;
    object-fit: cover;
}

.resort-card .form-check-input {
    position: absolute;
    top: 10px;
    left: 10px;
    width: 1.5em;
    height: 1.5em;
    z-index: 10;
}

.resort-card.selected {
    border-color: #0d6efd;
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.25);
}

/* Enhanced Timeframe Cards */
.timeframe-card {
    transition: all 0.3s ease;
    border: 2px solid transparent;
    cursor: pointer;
    position: relative;
}

.timeframe-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    border-color: #0d6efd;
}

.timeframe-card .form-check-input {
    position: absolute;
    top: 10px;
    left: 10px;
    width: 1.2em;
    height: 1.2em;
    z-index: 10;
}

.timeframe-card.selected {
    border-color: #0d6efd;
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.25);
}

.timeframe-card.timeframe-disabled {
    opacity: 0.6;
    cursor: not-allowed;
    background-color: #e9ecef !important;
    border-color: #dee2e6;
}

.timeframe-card.timeframe-disabled:hover {
    transform: none;
    box-shadow: none;
}

/* Enhanced Date Cards */
.date-card {
    transition: all 0.3s ease;
    border: 2px solid transparent;
    cursor: pointer;
    position: relative;
}

.date-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    border-color: #0d6efd;
}

.date-card.selected {
    border-color: #0d6efd;
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.25);
}

/* Enhanced Facility Cards */
.facility-card {
    transition: all 0.3s ease;
    border: 1px solid #dee2e6;
}

.facility-card:hover:not(.facility-blocked) {
    transform: translateY(-5px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    border-color: #0d6efd;
}

.facility-card.facility-blocked {
    opacity: 0.7;
    cursor: not-allowed;
    background-color: #f8f9fa;
    border-color: #adb5bd;
}

.facility-card.facility-blocked:hover {
    transform: none;
    box-shadow: none;
    border-color: #adb5bd;
}

.facility-card .card-img-top {
    height: 150px;
    object-fit: cover;
}

.facility-card .form-check-input {
    width: 1.2em;
    height: 1.2em;
}

.facility-card .form-check-label {
    cursor: pointer;
}

.facility-card.selected {
    border-color: #0d6efd;
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.25);
}

/* Blocked Facility Overlay */
.blocked-overlay {
    position: absolute;
    top: 10px;
    right: 10px;
    z-index: 10;
    background-color: rgba(0, 0, 0, 0.7);
    color: white;
    padding: 1px 1px;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: bold;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

/* Enhanced form animations */
.form-control:focus, .form-select:focus {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(13, 110, 253, 0.25);
}

.btn {
    transition: all 0.2s ease;
}

.btn:hover {
    transform: translateY(-1px);
}

/* Shake animation for feedback */
@keyframes shake {
  10%, 90% { transform: translate3d(-1px, 0, 0); }
  20%, 80% { transform: translate3d(2px, 0, 0); }
  30%, 50%, 70% { transform: translate3d(-4px, 0, 0); }
  40%, 60% { transform: translate3d(4px, 0, 0); }
}

.shake {
  animation: shake 0.82s cubic-bezier(.36,.07,.19,.97) both;
}

/* Loading states */
.loading {
    position: relative;
    overflow: hidden;
}

.loading::after {
    content: "";
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
    animation: shimmer 1.5s infinite;
}

@keyframes shimmer {
    0% { left: -100%; }
    100% { left: 100%; }
}

/* Mobile responsiveness improvements */
@media (max-width: 768px) {
    .step-indicator {
        margin-bottom: 10px;
    }
    
    .step-circle {
        width: 25px;
        height: 25px;
        font-size: 0.8rem;
    }
    
    .calendar-grid {
        font-size: 0.8rem;
    }
}
</style>

<script>
// Admin contact information (passed from PHP)
const adminPhone = '<?= htmlspecialchars($adminContact['PhoneNumber'] ?? '') ?>';
const adminEmail = '<?= htmlspecialchars($adminContact['Email'] ?? '') ?>';

// Resort pricing completeness data (passed from PHP)
const resortPricingData = {
    <?php foreach ($resorts as $resort): ?>
        '<?= $resort->resortId ?>': {
            hasCompletePricing: <?= $resort->hasCompletePricing ? 'true' : 'false' ?>,
            name: '<?= htmlspecialchars($resort->name) ?>'
        },
    <?php endforeach; ?>
};

document.addEventListener('DOMContentLoaded', function() {
    // Form elements
    const customerSelect = document.getElementById('customer_id');
    const resortRadios = document.querySelectorAll('input[name="resort_id"]');
    const timeframeRadios = document.querySelectorAll('input[name="timeframe"]');
    const timeframeSelectionContainer = document.getElementById('timeframe-selection-container');
    const dateInput = document.getElementById('date');
    const facilitiesContainer = document.getElementById('facilitiesContainer');
    const submitBtn = document.getElementById('submitBtn');

    // Pricing incomplete notice elements
    const pricingIncompleteNotice = document.getElementById('pricingIncompleteNotice');

    // Fieldsets for steps
    const step2Fieldset = document.getElementById('step2-fieldset');
    const step3Fieldset = document.getElementById('step3-fieldset');
    const step4Fieldset = document.getElementById('step4-fieldset');
    const step5Fieldset = document.getElementById('step5-fieldset');

    // Display elements
    const timeframePricing = document.getElementById('timeframePricing');
    const basePriceDisplay = document.getElementById('basePriceDisplay');
    const weekendNotice = document.getElementById('weekendNotice');
    const holidayNotice = document.getElementById('holidayNotice');
    const pricingBreakdown = document.getElementById('pricingBreakdown');
    const noPricingMessage = document.getElementById('noPricingMessage');
    const totalPriceDisplay = document.getElementById('totalPriceDisplay');
    const summaryTimeframe = document.getElementById('summaryTimeframe');
    const summaryBasePrice = document.getElementById('summaryBasePrice');
    const facilityPricing = document.getElementById('facilityPricing');
    const noFacilitiesMessage = document.getElementById('noFacilitiesMessage');
    const bookingDetails = document.getElementById('bookingDetails');
    const summaryCustomer = document.getElementById('summaryCustomer');
    const summaryResort = document.getElementById('summaryResort');
    const summaryDate = document.getElementById('summaryDate');
    const summaryTimeframeText = document.getElementById('summaryTimeframeText');

    // Enhanced UI elements
    const dateCard = document.querySelector('.date-card');
    const calendarModal = new bootstrap.Modal(document.getElementById('calendarModal'));
    const calendarMonth = document.getElementById('calendarMonth');
    const calendarGrid = document.getElementById('calendarGrid');
    const calendarLoading = document.getElementById('calendarLoading');
    const calendarContent = document.getElementById('calendarContent');
    const selectDateBtn = document.getElementById('selectDateBtn');
    const dateAvailabilityInfo = document.getElementById('dateAvailabilityInfo');
    const selectedDateInfo = document.getElementById('selectedDateInfo');

    // Step indicators
    const stepIndicators = document.querySelectorAll('.step-indicator');

    // State tracking
    let currentBasePrice = 0;
    let currentPricingData = null; // Store current pricing data
    let availableFacilities = [];
    let selectedFacilities = [];
    let calendarData = {};
    let selectedCalendarDate = null;
    let currentStep = 1;
    let selectedCustomerId = null;
    let selectedResortId = null; // Track selected resort ID
    let selectedTimeframe = null; // Track selected timeframe
    let fullTimeframeDescription = 'N/A';

    // Event listeners
    customerSelect.addEventListener('change', handleCustomerChange);
    resortRadios.forEach(radio => {
        radio.addEventListener('change', handleResortChange);
    });
    timeframeRadios.forEach(radio => {
        radio.addEventListener('change', handleTimeframeChange);
    });
    dateInput.addEventListener('change', handleDateOrTimeframeChange);

    // Enhanced calendar modal events
    if (dateCard) {
        dateCard.addEventListener('click', openCalendarModal);
    }
    calendarMonth.addEventListener('change', loadCalendarData);
    selectDateBtn.addEventListener('click', selectCalendarDate);

    function formatTimeframeDescription(description) {
        return description.replace('Check In:', 'CI:').replace('Check Out:', 'CO:').replace(' Next Day', '');
    }

    function initializeFormState() {
        step2Fieldset.disabled = true;
        step3Fieldset.disabled = true;
        step4Fieldset.disabled = true;
        step5Fieldset.disabled = true;
        
        if (timeframeSelectionContainer) {
            timeframeSelectionContainer.classList.add('opacity-50', 'pe-none');
        }
        if (dateCard) {
            dateCard.classList.add('opacity-50', 'pe-none');
        }
    }
    
    initializeFormState();

    // Initialize form if there are pre-selected values
    updateStepIndicators();
    
    const preSelectedCustomer = document.querySelector('#customer_id option:checked');
    if (preSelectedCustomer && preSelectedCustomer.value) {
        selectedCustomerId = preSelectedCustomer.value;
        handleCustomerChange();
    }

    const preSelectedResortRadio = document.querySelector('.resort-radio:checked');
    if (preSelectedResortRadio) {
        selectedResortId = preSelectedResortRadio.value;
        handleResortChange({target: preSelectedResortRadio});
    }

    // Enhanced step management
    function updateStepIndicators() {
        stepIndicators.forEach((indicator, index) => {
            const stepNum = index + 1;
            indicator.classList.remove('active', 'completed');
            
            if (stepNum < currentStep) {
                indicator.classList.add('completed');
            } else if (stepNum === currentStep) {
                indicator.classList.add('active');
            }
        });
    }

    function advanceToStep(step) {
        if (step > currentStep) {
            currentStep = step;
            updateStepIndicators();
        }
    }
    
    function handleCustomerChange(event) {
        selectedCustomerId = customerSelect.value;
        if (selectedCustomerId) {
            step2Fieldset.disabled = false;
            advanceToStep(2);
        }
        updateBookingSummaryDetails();
        validateForm();
    }

    function handleTimeframeChange(event) {
        selectedTimeframe = event.target.value;
        highlightSelectedTimeframe(selectedTimeframe);
        handleDateOrTimeframeChange();
        advanceToStep(4); // Advance to Date step when timeframe is selected
    }

    function highlightSelectedTimeframe(timeframe) {
        document.querySelectorAll('.timeframe-card').forEach(card => {
            card.classList.remove('selected');
        });
        const selectedCard = document.querySelector(`input[name="timeframe"][value="${timeframe}"]`).closest('.timeframe-card');
        if (selectedCard) {
            selectedCard.classList.add('selected');
        }
    }

    function handleResortChange(event) {
        const newlySelectedResortId = event ? event.target.value : selectedResortId;
        if (!newlySelectedResortId) {
            pricingIncompleteNotice.style.display = 'none';
            resetFacilities();
            resetPricing();
            if (dateCard) {
                dateCard.classList.add('opacity-50', 'pe-none');
            }
            step3Fieldset.disabled = true;
            step4Fieldset.disabled = true;
            step5Fieldset.disabled = true;
            return;
        }

        selectedResortId = newlySelectedResortId;
        highlightSelectedResort(selectedResortId);

        const resortData = resortPricingData[selectedResortId];
        if (resortData && !resortData.hasCompletePricing) {
            pricingIncompleteNotice.style.display = 'block';
            deselectIncompleteResort(selectedResortId);
            resetFacilities();
            resetPricing();
            step3Fieldset.disabled = true;
            step4Fieldset.disabled = true;
            step5Fieldset.disabled = true;
            highlightSelectedResort(null);
            if (dateCard) {
                dateCard.classList.add('opacity-50', 'pe-none');
            }
            updateBookingSummaryDetails();
            return;
        } else {
            pricingIncompleteNotice.style.display = 'none';
        }

        step3Fieldset.disabled = false;
        if (timeframeSelectionContainer) {
            timeframeSelectionContainer.classList.remove('opacity-50', 'pe-none');
        }
        step4Fieldset.disabled = false;
        step5Fieldset.disabled = false;

        advanceToStep(3);
        if (dateCard) {
            dateCard.classList.remove('opacity-50', 'pe-none');
        }
        loadFacilities(selectedResortId);
        handleDateOrTimeframeChange();
        updateBookingSummaryDetails();
    }

    function deselectIncompleteResort(resortId) {
        const resortRadio = document.getElementById(`resort_${resortId}`);
        const resortCard = resortRadio ? resortRadio.closest('.resort-card') : null;

        if (resortCard) {
            resortCard.classList.add('opacity-50');
            resortCard.style.pointerEvents = 'none';
        }

        if (resortRadio) {
            resortRadio.checked = false;
        }
    }

    function highlightSelectedResort(resortId) {
        document.querySelectorAll('.resort-card').forEach(card => {
            card.classList.remove('selected');
        });
        if(resortId) {
            const selectedCard = document.querySelector(`#resort_${resortId}`).closest('.resort-card');
            if (selectedCard) {
                selectedCard.classList.add('selected');
            }
        }
    }

    function openCalendarModal() {
        if (!selectedResortId || !selectedTimeframe) {
            alert('Please select a resort and timeframe first');
            return;
        }
        calendarModal.show();
        loadCalendarData();
    }

    function loadCalendarData() {
        const resortId = selectedResortId;
        const timeframe = selectedTimeframe;
        const month = calendarMonth.value;

        if (!resortId || !timeframe) return;

        calendarLoading.style.display = 'block';
        calendarContent.style.display = 'none';

        fetch(`?controller=booking&action=getCalendarAvailability&resort_id=${resortId}&timeframe=${timeframe}&month=${month}`)
            .then(response => response.json())
            .then(data => {
                calendarData = data.availability;
                renderCalendar(data.month);
                calendarLoading.style.display = 'none';
                calendarContent.style.display = 'block';
            })
            .catch(error => {
                console.error('Error loading calendar:', error);
                calendarLoading.innerHTML = '<div class="alert alert-danger">Error loading calendar data</div>';
            });
    }

    function renderCalendar(month) {
        const [year, monthNum] = month.split('-');
        const firstDay = new Date(year, monthNum - 1, 1);
        let html = '';
        const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        dayNames.forEach(day => {
            html += `<div class="calendar-day-header">${day}</div>`;
        });
        const startOfMonth = new Date(Date.UTC(parseInt(year), parseInt(monthNum) - 1, 1));
        const startCalendarDate = new Date(startOfMonth);
        startCalendarDate.setUTCDate(startCalendarDate.getUTCDate() - startOfMonth.getUTCDay());
        for (let i = 0; i < 42; i++) {
            const currentCalendarDate = new Date(startCalendarDate);
            currentCalendarDate.setUTCDate(startCalendarDate.getUTCDate() + i);
            const dateStr = currentCalendarDate.toISOString().split('T')[0];
            const dayNum = currentCalendarDate.getUTCDate();
            const isCurrentMonth = currentCalendarDate.getUTCMonth() === startOfMonth.getUTCMonth();
            let dayClass = 'calendar-day';
            let dayData = calendarData[dateStr];
            if (!isCurrentMonth) {
                dayClass += ' disabled text-muted';
            } else if (dayData) {
                dayClass += ` ${dayData.status}`;
                if (!dayData.available) {
                    dayClass += ' disabled';
                }
            } else {
                dayClass += ' disabled';
            }
            let tooltipContent = '';
            if (dayData && dayData.status === 'partially_booked' && dayData.availableTimeframes.length > 0) {
                const timeframeLabels = { '12_hours': '12 Hours', 'overnight': 'Overnight', '24_hours': '24 Hours' };
                const availableLabels = dayData.availableTimeframes.map(tf => timeframeLabels[tf] || tf);
                const tooltipTitle = `Available:<br>${availableLabels.join(', ')}`;
                tooltipContent = `data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top" title="${tooltipTitle}"`;
            }
            let statusText = dayData ? dayData.statusText : '';
            if (statusText === 'Partially Booked' || statusText === 'Fully Booked') {
                statusText = statusText.replace(' ', '<br>');
            }
            html += `<div class="${dayClass}" data-date="${dateStr}" ${tooltipContent}><div class="day-number">${dayNum}</div><div class="day-status">${statusText}</div></div>`;
        }
        calendarGrid.innerHTML = html;
        calendarGrid.addEventListener('click', function(e) {
            const dayElement = e.target.closest('.calendar-day');
            if (!dayElement || dayElement.classList.contains('calendar-day-header')) return;
            const dateStr = dayElement.dataset.date;
            if (dateStr && !dayElement.classList.contains('disabled')) {
                document.querySelectorAll('.calendar-day.selected').forEach(day => day.classList.remove('selected'));
                dayElement.classList.add('selected');
                selectedCalendarDate = dateStr;
                selectDateBtn.disabled = false;
                selectDateBtn.innerHTML = '<i class="fas fa-check"></i> Select';
            }
        }, true);
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
          return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    function selectCalendarDate() {
        if (selectedCalendarDate) {
            dateInput.value = selectedCalendarDate;
            calendarModal.hide();
            const dayData = calendarData[selectedCalendarDate];
            updateTimeframeStates(dayData);
            handleDateOrTimeframeChange();
        }
    }

    function updateTimeframeStates(dayData) {
        timeframeRadios.forEach(radio => {
            radio.disabled = false;
            radio.closest('.timeframe-card').classList.remove('timeframe-disabled');
            const bookedLabel = document.getElementById(`booked-${radio.value}`);
            if (bookedLabel) bookedLabel.style.display = 'none';
        });
        if (dayData && dayData.status === 'partially_booked' && dayData.availableTimeframes.length > 0) {
            const available = dayData.availableTimeframes[0];
            let alreadyBookedShown = false;
            timeframeRadios.forEach(radio => {
                const card = radio.closest('.timeframe-card');
                if (radio.value === available) {
                    radio.checked = true;
                    handleTimeframeChange({ target: radio });
                } else {
                    radio.disabled = true;
                    card.classList.add('timeframe-disabled');
                    const bookedLabel = document.getElementById(`booked-${radio.value}`);
                    if (bookedLabel && !alreadyBookedShown) {
                        bookedLabel.style.display = 'block';
                        alreadyBookedShown = true;
                    } else if (bookedLabel) {
                        bookedLabel.style.display = 'none';
                    }
                }
            });
        }
    }

    function loadFacilities(resortId, date = null) {
        facilitiesContainer.innerHTML = '<div class="col-12"><div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading facilities...</div></div>';
        let url = `?controller=booking&action=getFacilitiesByResort&resort_id=${resortId}`;
        if (date) {
            url += `&date=${date}`;
        }
        fetch(url)
            .then(response => response.json())
            .then(facilities => {
                availableFacilities = facilities;
                renderFacilities(facilities);
            })
            .catch(error => {
                console.error('Error fetching facilities:', error);
                facilitiesContainer.innerHTML = '<div class="col-12"><div class="alert alert-danger">Error loading facilities</div></div>';
            });
    }

    function renderFacilities(facilities) {
        if (facilities.length === 0) {
            facilitiesContainer.innerHTML = '<div class="col-12"><div class="alert alert-info text-center">No additional facilities available for this resort</div></div>';
            return;
        }
        let html = '';
        facilities.forEach(facility => {
            const isBlocked = facility.isBlocked;
            const cardClasses = `card h-100 facility-card ${isBlocked ? 'facility-blocked' : ''}`;
            const checkboxDisabled = isBlocked ? 'disabled' : '';
            const overlay = isBlocked ? '<div class="blocked-overlay"><span class="badge bg-secondary">Unavailable</span></div>' : '';
            html += `<div class="col-6 col-md-4 col-lg-3 mb-3"><div class="${cardClasses}"><img src="${facility.mainPhotoURL || 'assets/images/default-facility.jpg'}" class="card-img-top" alt="${facility.name}" style="${isBlocked ? 'filter: grayscale(50%) opacity(0.5);' : ''}">${overlay}<div class="card-body d-flex flex-column"><div class="form-check"><input class="form-check-input facility-checkbox" type="checkbox" value="${facility.facilityId}" id="facility_${facility.facilityId}" data-price="${facility.rate}" ${checkboxDisabled}><label class="form-check-label" for="facility_${facility.facilityId}"><i class="${facility.icon} me-2 ${isBlocked ? 'text-muted' : 'text-primary'}"></i><strong class="${isBlocked ? 'text-muted' : ''}">${facility.name}</strong>${isBlocked ? '<small class="text-muted d-block">(Blocked)</small>' : ''}</label></div><div class="mt-2 small text-muted"><p class="mb-1 ${isBlocked ? 'text-muted' : ''}">${facility.shortDescription || ''}</p><div class="fw-bold fs-5 ${isBlocked ? 'text-muted' : 'text-primary'}">${facility.priceDisplay}</div></div></div></div></div>`;
        });
        facilitiesContainer.innerHTML = html;
        document.querySelectorAll('.facility-checkbox:not([disabled])').forEach(checkbox => {
            checkbox.addEventListener('change', handleFacilitySelection);
        });
        document.querySelectorAll('.facility-card:not(.facility-blocked)').forEach(card => {
            card.addEventListener('click', (event) => {
                if (event.target.classList.contains('facility-checkbox') || event.target.closest('.form-check-label')) return;
                const checkbox = card.querySelector('.facility-checkbox');
                if (checkbox && !checkbox.disabled) {
                    checkbox.checked = !checkbox.checked;
                    checkbox.dispatchEvent(new Event('change'));
                }
            });
        });
        const urlParams = new URLSearchParams(window.location.search);
        const preSelectedFacilityId = urlParams.get('facility_id');
        if (preSelectedFacilityId) {
            const facilityCheckbox = document.querySelector(`#facility_${preSelectedFacilityId}`);
            if (facilityCheckbox && !facilityCheckbox.disabled) {
                facilityCheckbox.checked = true;
                facilityCheckbox.dispatchEvent(new Event('change'));
            }
        }
    }

    function handleFacilitySelection() {
        selectedFacilities = [];
        document.querySelectorAll('.facility-checkbox').forEach(checkbox => {
            const card = checkbox.closest('.facility-card');
            if (checkbox.checked) {
                selectedFacilities.push({ id: checkbox.value, price: parseFloat(checkbox.dataset.price), name: checkbox.nextElementSibling.textContent.trim() });
                card.classList.add('selected');
            } else {
                card.classList.remove('selected');
            }
        });
        if (selectedFacilities.length > 0) {
            advanceToStep(6);
        }
        updatePricingDisplay();
        updateBookingSummaryDetails();
        validateForm();
    }

    function handleDateOrTimeframeChange() {
        const resortId = selectedResortId;
        const date = dateInput.value;
        const timeframe = selectedTimeframe;
        if (selectedResortId && selectedTimeframe) {
            if (dateCard) dateCard.classList.remove('opacity-50', 'pe-none');
        } else {
            if (dateCard) dateCard.classList.add('opacity-50', 'pe-none');
        }
        if (resortId && date && timeframe) {
            currentPricingData = null;
            loadFacilities(resortId, date);
            loadTimeframePricing(resortId, timeframe, date);
            updateTotalPrice();
            updateDateAvailabilityInfo(date);
        } else {
            resetPricing();
            currentPricingData = null;
            if (dateAvailabilityInfo) dateAvailabilityInfo.style.display = 'none';
        }
        updateBookingSummaryDetails();
        checkAvailability();
        validateForm();
    }

    function checkAvailability() {
        const resortId = selectedResortId;
        const date = dateInput.value;
        const timeframe = selectedTimeframe;
        const facilityIds = selectedFacilities.map(f => f.id);
        if (!resortId || !date || !timeframe) return;
        let url = `?controller=booking&action=checkAvailability&resort_id=${resortId}&date=${date}&timeframe=${timeframe}`;
        facilityIds.forEach(id => { url += `&facility_ids[]=${id}`; });
        fetch(url)
            .then(response => response.json())
            .then(data => {
                const availabilityResultDiv = document.getElementById('dateAvailabilityInfo');
                const detailed = selectedDateInfo ? selectedDateInfo.textContent.trim() : '';
                if (data.available) {
                    availabilityResultDiv.innerHTML = `<div class="alert alert-info mb-0"><small>${detailed}</small></div>`;
                    submitBtn.disabled = false;
                } else {
                    let errorMessage = 'This date is not available.';
                    if (data.detailed_result && data.detailed_result.conflicts && data.detailed_result.conflicts.length > 0) {
                        errorMessage = data.detailed_result.conflicts.map(c => c.message).join('<br>');
                    } else if (data.detailed_result && data.detailed_result.blocking_issues && data.detailed_result.blocking_issues.length > 0) {
                        errorMessage = data.detailed_result.blocking_issues.map(b => b.message).join('<br>');
                    }
                    availabilityResultDiv.innerHTML = `<div class="alert alert-danger mb-0"><small>${errorMessage}</small></div>`;
                    submitBtn.disabled = true;
                }
                availabilityResultDiv.style.display = 'block';
            })
            .catch(error => console.error('Error checking availability:', error));
    }

    function loadTimeframePricing(resortId, timeframe, date) {
        fetch(`?controller=booking&action=getResortPricing&resort_id=${resortId}&timeframe=${timeframe}&date=${date}`)
            .then(response => response.json())
            .then(pricing => {
                advanceToStep(4);
                currentBasePrice = pricing.basePrice;
                currentPricingData = pricing;
                basePriceDisplay.textContent = pricing.basePriceDisplay;
                fullTimeframeDescription = formatTimeframeDescription(pricing.timeframeDisplay);
                const simpleTimeframe = pricing.timeframeDisplay.split(' - ')[0]; // Extract "12 Hours" etc.
                summaryTimeframe.textContent = simpleTimeframe; // Use simple version for base price line
                summaryBasePrice.textContent = pricing.basePriceDisplay;
                const surchargeBreakdown = document.getElementById('surchargeBreakdown');
                if (pricing.appliedSurcharges && pricing.appliedSurcharges.length > 0) {
                    let surchargeHtml = '<small class="text-muted">Additional Charges:</small>';
                    pricing.appliedSurcharges.forEach(surcharge => {
                        const iconClass = surcharge.type === 'weekend' ? 'fa-calendar-week' :
                                        surcharge.type === 'holiday' ? 'fa-star' : 'fa-plus';
                        const badgeClass = surcharge.type === 'weekend' ? 'text-warning' :
                                         surcharge.type === 'holiday' ? 'text-info' : 'text-primary';

                        surchargeHtml += `
                            <div class="row mb-1 justify-content-center">
                                <div class="col-auto">
                                    <span class="small ps-3 ${badgeClass}">
                                        <i class="fas ${iconClass} me-1"></i>${surcharge.type.charAt(0).toUpperCase() + surcharge.type.slice(1)} Surcharge:
                                    </span>
                                </div>
                                <div class="col-auto">
                                    <span class="small fw-bold ${badgeClass}">+ ₱${parseFloat(surcharge.amount).toLocaleString()}</span>
                                </div>
                            </div>
                        `;
                    });
                    surchargeBreakdown.innerHTML = surchargeHtml;
                } else {
                    surchargeBreakdown.innerHTML = '<div class="text-center"><small class="text-muted">No additional charges</small></div>';
                }
                if (pricing.isHoliday) {
                    holidayNotice.style.display = 'block';
                    weekendNotice.style.display = 'none';
                } else if (pricing.isWeekend) {
                    holidayNotice.style.display = 'none';
                    weekendNotice.style.display = 'block';
                } else {
                    holidayNotice.style.display = 'none';
                    weekendNotice.style.display = 'none';
                }
                timeframePricing.style.display = 'block';
                updatePricingDisplay();
            })
            .catch(error => {
                console.error('Error fetching pricing:', error);
                resetPricing();
            });
    }

    function updateTotalPrice() {
        const resortId = selectedResortId;
        const timeframe = selectedTimeframe;
        const date = dateInput.value;
        const facilityIds = selectedFacilities.map(f => f.id);
        if (!resortId || !timeframe || !date) return;
        const formData = new FormData();
        formData.append('resort_id', resortId);
        formData.append('timeframe', timeframe);
        formData.append('date', date);
        facilityIds.forEach(id => formData.append('facility_ids[]', id));
        fetch('?controller=booking&action=calculateBookingPrice', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(result => { totalPriceDisplay.textContent = result.totalPriceDisplay; })
            .catch(error => console.error('Error calculating total price:', error));
    }

    function updatePricingDisplay() {
        updateBookingSummaryDetails();
        if (currentBasePrice > 0) {
            let facilityHtml = '';
            if (selectedFacilities.length > 0) {
                facilityHtml += '<div class="text-center"><small class="text-muted">Additional Facilities:</small></div>';
                selectedFacilities.forEach(facility => {
                    facilityHtml += `
                        <div class="row mb-1 justify-content-center">
                            <div class="col-auto">
                                <span class="small ps-3"><i class="fas fa-swimming-pool text-muted me-2"></i>${facility.name}:</span>
                            </div>
                            <div class="col-auto">
                                <span class="small">+ ₱${facility.price.toLocaleString()}</span>
                            </div>
                        </div>
                    `;
                });
            }
            facilityPricing.innerHTML = facilityHtml;
            updateSummarySurchargeBreakdown();
            pricingBreakdown.style.display = 'block';
            noPricingMessage.style.display = 'none';
            updateTotalPrice();
        } else {
            pricingBreakdown.style.display = 'none';
            noPricingMessage.style.display = 'block';
        }
    }

    function updateSummarySurchargeBreakdown() {
        const summarySurchargeBreakdown = document.getElementById('summarySurchargeBreakdown');
        if (currentPricingData && currentPricingData.appliedSurcharges && currentPricingData.appliedSurcharges.length > 0) {
            let surchargeHtml = '<div class="text-center"><small class="text-muted">Additional Charges:</small></div>';
            currentPricingData.appliedSurcharges.forEach(surcharge => {
                const iconClass = surcharge.type === 'weekend' ? 'fa-calendar-week' :
                                surcharge.type === 'holiday' ? 'fa-star' : 'fa-plus';
                const badgeClass = surcharge.type === 'weekend' ? 'text-warning' :
                                 surcharge.type === 'holiday' ? 'text-info' : 'text-primary';

                surchargeHtml += `
                    <div class="row mb-1 justify-content-center">
                        <div class="col-auto">
                            <span class="small ps-3 ${badgeClass}">
                                <i class="fas ${iconClass} me-1"></i>${surcharge.type.charAt(0).toUpperCase() + surcharge.type.slice(1)} Surcharge:
                            </span>
                        </div>
                        <div class="col-auto">
                            <span class="small fw-bold ${badgeClass}">+ ₱${parseFloat(surcharge.amount).toLocaleString()}</span>
                        </div>
                    </div>
                `;
            });
            summarySurchargeBreakdown.innerHTML = surchargeHtml;
        } else {
            summarySurchargeBreakdown.innerHTML = '<div class="text-center"><small class="text-muted">No additional charges</small></div>';
        }
    }

    function validateForm() {
        const customerValid = selectedCustomerId !== null;
        const resortValid = selectedResortId !== null;
        const timeframeValid = selectedTimeframe;
        const dateValid = dateInput.value;
        const isValid = customerValid && resortValid && timeframeValid && dateValid;
        submitBtn.disabled = !isValid;
        if (isValid) {
            submitBtn.innerHTML = '<i class="fas fa-check-circle"></i> Create Confirmed Booking';
            submitBtn.classList.remove('btn-secondary');
            submitBtn.classList.add('btn-success');
        } else {
            submitBtn.innerHTML = '<i class="fas fa-calendar-check"></i> Create Confirmed Booking';
            submitBtn.classList.remove('btn-success');
            submitBtn.classList.add('btn-secondary');
        }
        updateStepProgress(customerValid, resortValid, timeframeValid, dateValid, selectedFacilities.length > 0);
    }

    function updateStepProgress(customerValid, resortValid, timeframeValid, dateValid, facilitiesSelected) {
        stepIndicators.forEach((indicator, index) => indicator.classList.remove('active', 'completed'));
        if (customerValid) {
            markStepCompleted(1);
            if (resortValid) {
                markStepCompleted(2);
                if (timeframeValid) {
                    markStepCompleted(3);
                    if (dateValid) {
                        markStepCompleted(4);
                        markStepCompleted(6);
                    }
                }
            }
        }
        if (facilitiesSelected) {
            markStepCompleted(5);
        }
    }

    function markStepCompleted(stepNumber) {
        if (stepIndicators[stepNumber - 1]) {
            stepIndicators[stepNumber - 1].classList.add('completed');
            stepIndicators[stepNumber - 1].classList.remove('active');
        }
    }

    function updateDateAvailabilityInfo(date) {
        const dayOfWeek = new Date(date).getDay();
        const isWeekend = (dayOfWeek === 0 || dayOfWeek === 6);
        const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        let info = `Selected: ${dayNames[dayOfWeek]}, ${new Date(date).toLocaleDateString()}`;
        const dateStr = new Date(date).toISOString().split('T')[0];
        const dayData = calendarData[dateStr];
        const isHoliday = dayData ? dayData.isHoliday : false;
        if (isHoliday) {
           info += ' (Holiday rates may apply)';
        } else if (isWeekend) {
            info += ' (Weekend rates may apply)';
        }
        if (selectedDateInfo) {
            selectedDateInfo.textContent = info;
            dateAvailabilityInfo.style.display = 'block';
        }
    }

    function resetFacilities() {
        facilitiesContainer.innerHTML = '<div class="col-12">' + noFacilitiesMessage.outerHTML + '</div>';
        selectedFacilities = [];
    }

    function updateBookingSummaryDetails() {
        let customerName = 'N/A';
        if(selectedCustomerId) {
            const selectedOption = customerSelect.querySelector(`option[value="${selectedCustomerId}"]`);
            if(selectedOption) {
                customerName = selectedOption.textContent.trim();
            }
        }
        summaryCustomer.textContent = customerName;

        let resortName = 'N/A';
        if (selectedResortId) {
            const selectedResortRadio = document.querySelector(`input[name="resort_id"][value="${selectedResortId}"]`);
            if (selectedResortRadio) {
                const resortCard = selectedResortRadio.closest('.resort-card');
                if (resortCard) {
                    resortName = resortCard.querySelector('.card-title').textContent.trim();
                }
            }
        }
        summaryResort.textContent = resortName;
        const timeframeValue = selectedTimeframe ? fullTimeframeDescription : 'N/A';
        const dateValue = dateInput.value ? new Date(dateInput.value + 'T00:00:00').toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : 'N/A';
        summaryTimeframeText.textContent = timeframeValue;
        summaryDate.textContent = dateValue;

        if (selectedCustomerId && selectedResortId) {
            bookingDetails.style.display = 'block';
            noPricingMessage.innerHTML = '<i class="fas fa-info-circle fa-2x mb-2 opacity-50"></i><p class="mb-0">Continue selections for pricing</p>';
        } else {
            bookingDetails.style.display = 'none';
            noPricingMessage.innerHTML = '<i class="fas fa-calculator fa-2x mb-2 opacity-50"></i><p class="mb-0">Complete your selections to see your booking summary</p>';
        }
    }

    function resetPricing() {
        timeframePricing.style.display = 'none';
        pricingBreakdown.style.display = 'none';
        noPricingMessage.style.display = 'block';
        currentBasePrice = 0;
        fullTimeframeDescription = 'N/A';
    }

    const bookingForm = document.getElementById('bookingForm');
    submitBtn.addEventListener('click', function(e) {
        e.preventDefault();
        // Add hidden inputs for selected facility IDs before submitting
        bookingForm.querySelectorAll('input[name="facility_ids[]"]').forEach(input => input.remove());
        selectedFacilities.forEach(facility => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'facility_ids[]';
            input.value = facility.id;
            bookingForm.appendChild(input);
        });
        bookingForm.submit();
    });
});
</script>

<?php 
unset($_SESSION['old_input']);
require_once __DIR__ . '/../partials/footer.php'; 
?>