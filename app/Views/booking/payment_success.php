<?php
$pageTitle = "Payment Submitted Successfully";
require_once __DIR__ . '/../partials/header.php';
?>

<style>
    .invoice-card {
        width: auto;
        min-width: 200px;
        background: white;
        padding: 15px;
        border-radius: 5px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        float: right;
        margin-left: 20px;
    }
    @media (max-width: 768px) {
        .invoice-card {
            float: none;
            margin-bottom: 20px;
            margin-left: 0;
        }
    }
</style>

        <div class="position-relative">
            <div class="text-center mb-4">
                <i class="fas fa-check-circle fa-5x text-success mb-3"></i>
                <h1 class="text-success"><?= htmlspecialchars($pageTitle) ?></h1>
                <p class="lead text-muted">Your payment has been submitted and is being reviewed by our team.</p>
            </div>
            <div class="position-absolute top-0 end-0 me-1 mt-5">
                <div class="alert alert-success text-center" style="min-width: 200px; padding: 20px;">
                    <h5 class="mb-4"><strong>Invoice</strong></h5>
                    <div>
                        <a href="?controller=booking&action=generateInvoice&id=<?= $booking->bookingId ?>"
                           class="btn btn-success" target="_blank">
                            <i class="fas fa-download"></i> Generate as PDF
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Success Message -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-info-circle"></i> <?= htmlspecialchars($_SESSION['success_message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                <?php unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>

        <!-- Booking Summary Card -->
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-receipt"></i> Booking & Payment Summary</h5>
            </div>
            <div class="card-body">
                <!-- Customer Information Section -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h6 class="text-muted mb-3">Customer Information</h6>
                        <div class="mb-2">
                            <strong>Name:</strong> <?= htmlspecialchars($customer['FirstName'] . ' ' . $customer['LastName']) ?>
                        </div>
                        <div class="mb-2">
                            <strong>Contact Number:</strong> <?= htmlspecialchars($customer['PhoneNumber'] ?? 'N/A') ?>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-muted mb-3">Booking Details</h6>
                        <div class="mb-2">
                            <strong>Booking ID:</strong> #<?= $booking->bookingId ?>
                        </div>
                        <div class="mb-2">
                            <strong>Resort:</strong> <?= htmlspecialchars($resort->name ?? 'N/A') ?>
                        </div>
                        <div class="mb-2">
                            <strong>Date:</strong> <?= date('F j, Y', strtotime($booking->bookingDate)) ?>
                        </div>
                        <div class="mb-2">
                            <strong>Timeframe:</strong> <?= htmlspecialchars(Booking::getTimeSlotDisplay($booking->timeSlotType)) ?>
                        </div>

                        <?php if (!empty($facilities)): ?>
                            <div class="mb-2">
                                <strong>Facilities:</strong>
                                <ul class="mb-0">
                                    <?php foreach ($facilities as $facility): ?>
                                        <li><?= htmlspecialchars($facility->FacilityName) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted mb-3">Payment Information</h6>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Total Booking Amount:</span>
                            <span class="fw-bold">₱<?= number_format($booking->totalAmount, 2) ?></span>
                        </div>
                        <?php if ($booking->remainingBalance < $booking->totalAmount): ?>
                            <div class="d-flex justify-content-between mb-2 text-success">
                                <span>Amount Paid:</span>
                                <span>₱<?= number_format($booking->totalAmount - $booking->remainingBalance, 2) ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="d-flex justify-content-between mb-3">
                            <span class="fw-bold">Remaining Balance:</span>
                            <span class="fw-bold <?= $booking->remainingBalance > 0 ? 'text-warning' : 'text-success' ?>">
                                ₱<?= number_format($booking->remainingBalance, 2) ?>
                            </span>
                        </div>
                        
                        <?php if ($latestPayment && !empty($latestPayment->PaymentMethod)): ?>
                            <div class="mb-2">
                                <strong>Payment Method:</strong> <?= htmlspecialchars($latestPayment->PaymentMethod) ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($booking->paymentReference): ?>
                            <div class="mb-2">
                                <strong>Payment Reference:</strong> <?= htmlspecialchars($booking->paymentReference) ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mb-2">
                            <strong>Status:</strong>
                            <span class="badge bg-<?= $booking->status == 'Confirmed' ? 'success' : 'warning' ?>">
                                <?= htmlspecialchars($booking->status) ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Next Steps Card -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-list-check"></i> What Happens Next?</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-primary"><i class="fas fa-clock"></i> Payment Review Process</h6>
                        <ol class="list-unstyled">
                            <li class="mb-2">
                                <i class="fas fa-check text-success"></i> 
                                <strong>Payment Submitted</strong> - Your proof has been received
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-hourglass-half text-warning"></i> 
                                <strong>Under Review</strong> - Our team is verifying your payment
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-envelope text-info"></i> 
                                <strong>Notification</strong> - You'll receive email confirmation once verified
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-calendar-check text-success"></i> 
                                <strong>Booking Confirmed</strong> - Ready for your visit!
                            </li>
                        </ol>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-primary"><i class="fas fa-info-circle"></i> Important Information</h6>
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <i class="fas fa-clock text-muted"></i> 
                                Payment verification typically takes <strong>up to 24 hours</strong>
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-envelope text-muted"></i> 
                                Check your email for updates on payment status
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-phone text-muted"></i> 
                                Contact the resort directly if you have urgent questions
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-calendar text-muted"></i> 
                                Your booking date: <strong><?= date('F j, Y', strtotime($booking->bookingDate)) ?></strong>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Status Alert -->
        <?php if ($booking->remainingBalance > 0): ?>
            <div class="alert alert-warning alert-dismissible fade show">
                <h6><i class="fas fa-exclamation-triangle"></i> Partial Payment Received</h6>
                <p class="mb-2">You still have a remaining balance of <strong>₱<?= number_format($booking->remainingBalance, 2) ?></strong>.</p>
                <p class="mb-0">You can make additional payments anytime before your booking date.</p>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php else: ?>
            <div class="alert alert-success alert-dismissible fade show">
                <h6><i class="fas fa-check-circle"></i> Full Payment Complete!</h6>
                <p class="mb-0">Your booking has been paid in full. Once verified, your booking will be confirmed.</p>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Action Buttons -->
        <div class="d-grid gap-2 d-md-block text-center">
            <a href="?controller=booking&action=showMyBookings" class="btn btn-primary btn-lg me-2">
                <i class="fas fa-list"></i> View My Bookings
            </a>
            
            <?php if ($booking->remainingBalance > 0): ?>
                <a href="?controller=booking&action=showPaymentForm&id=<?= $booking->bookingId ?>" class="btn btn-warning btn-lg me-2">
                    <i class="fas fa-plus"></i> Make Additional Payment
                </a>
            <?php endif; ?>
            
            <a href="?" class="btn btn-secondary btn-lg">
                <i class="fas fa-home"></i> Back to Dashboard
            </a>
        </div>

        <!-- Contact Information -->
        <div class="card mt-4">
            <div class="card-body text-center">
                <h6><i class="fas fa-question-circle"></i> Need Help?</h6>
                <p class="text-muted mb-0">
                    If you have any questions about your payment or booking, please contact 
                    <strong><?= htmlspecialchars($resort->name ?? 'the resort') ?></strong> directly 
                    or check your booking status in "My Bookings".
                </p>
            </div>
        </div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
