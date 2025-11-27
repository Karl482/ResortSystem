<?php
$pageTitle = "Pending Payments";
require_once __DIR__ . '/../../partials/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-clock"></i> <?= htmlspecialchars($pageTitle) ?> (<?= count($pendingPayments) ?>)</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="?controller=admin&action=dashboard">Dashboard</a></li>
                <li class="breadcrumb-item active"><?= htmlspecialchars($pageTitle) ?> (<?= count($pendingPayments) ?>)</li>
            </ol>
        </nav>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_SESSION['success_message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            <?php unset($_SESSION['success_message']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($_SESSION['error_message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            <?php unset($_SESSION['error_message']); ?>
        </div>
    <?php endif; ?>

    <!-- Resort Filter -->
    <div class="row mb-3">
        <div class="col-md-4">
            <form action="" method="GET" id="resortFilterForm">
                <input type="hidden" name="controller" value="payment">
                <input type="hidden" name="action" value="showPendingPayments">
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

    <?php if (empty($pendingPayments)): ?>
        <div class="alert alert-info text-center">
            <i class="fas fa-info-circle fa-2x mb-3"></i>
            <h4>No Pending Payments</h4>
            <p class="mb-0">All payment submissions have been processed. Great job!</p>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($pendingPayments as $payment): ?>
                <div class="col-lg-6 col-xl-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-header bg-warning text-dark">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0"><i class="fas fa-receipt"></i> Booking #<?= $payment->BookingID ?></h6>
                                <span class="badge bg-dark">Pending Review</span>
                            </div>
                        </div>
                        
                        <div class="card-body">
                            <!-- Customer Information -->
                            <h6 class="text-primary mb-2"><i class="fas fa-user"></i> Customer</h6>
                            <div class="mb-3">
                                <div><strong><?= htmlspecialchars($payment->CustomerName) ?></strong></div>
                                <div class="small text-muted"><?= htmlspecialchars($payment->CustomerEmail) ?></div>
                            </div>

                            <!-- Booking Details -->
                            <h6 class="text-primary mb-2"><i class="fas fa-calendar"></i> Booking Details</h6>
                            <div class="mb-3">
                                <div><strong>Resort:</strong> <?= htmlspecialchars($payment->ResortName) ?></div>
                                <div><strong>Date:</strong> <?= date('F j, Y', strtotime($payment->BookingDate)) ?></div>
                                <div><strong>Total:</strong> ₱<?= number_format($payment->TotalAmount, 2) ?></div>
                                <div><strong>Remaining:</strong> ₱<?= number_format($payment->RemainingBalance, 2) ?></div>
                            </div>

                            <!-- Included Facilities -->
                            <?php if (!empty($payment->IncludedFacilities)): ?>
                                <h6 class="text-primary mb-2"><i class="fas fa-swimming-pool"></i> Included Facilities</h6>
                                <div class="mb-3">
                                    <ul class="list-unstyled mb-0 small">
                                        <?php foreach ($payment->IncludedFacilities as $facility): ?>
                                            <li><i class="fas fa-check-circle text-success me-1"></i><?= htmlspecialchars($facility->Name) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <!-- Payment Information -->
                            <h6 class="text-primary mb-2"><i class="fas fa-money-bill"></i> Payment Details</h6>
                            <div class="mb-3">
                                <div><strong>Amount Paid:</strong> ₱<?= number_format($payment->Amount, 2) ?></div>
                                <div><strong>Method:</strong> <?= htmlspecialchars($payment->PaymentMethod) ?></div>
                                <div><strong>Submitted:</strong> <?= date('M j, Y g:i A', strtotime($payment->PaymentDate)) ?></div>
                            </div>

                            <!-- Payment Proof -->
                            <?php if ($payment->ProofOfPaymentURL): ?>
                                <h6 class="text-primary mb-2"><i class="fas fa-image"></i> Payment Proof</h6>
                                <div class="mb-3">
                                    <img src="<?= BASE_URL ?>/<?= htmlspecialchars($payment->ProofOfPaymentURL) ?>"
                                         alt="Payment Proof"
                                         class="img-fluid rounded border cursor-pointer"
                                         style="max-height: 150px; width: 100%; object-fit: cover;"
                                         onclick="showImageModal(this.src)">
                                    <div class="small text-muted mt-1">Click to view full size</div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="card-footer bg-light">
                            <div class="d-grid gap-2">
                                <!-- Verify Button -->
                                <button type="button" class="btn btn-success" 
                                        onclick="verifyPayment(<?= $payment->PaymentID ?>, <?= $payment->BookingID ?>)">
                                    <i class="fas fa-check"></i> Verify & Approve Payment
                                </button>
                                
                                <!-- Contact Customer Button -->
                                <button type="button" class="btn btn-outline-secondary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#contactCustomerModal"
                                        onclick="populateContactModal(
                                            '<?= htmlspecialchars($payment->CustomerName, ENT_QUOTES) ?>',
                                            '<?= htmlspecialchars($payment->CustomerEmail, ENT_QUOTES) ?>',
                                            '<?= htmlspecialchars($payment->CustomerPhone ?? 'N/A', ENT_QUOTES) ?>'
                                        )">
                                    <i class="fas fa-phone"></i> Contact Customer
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Image Modal -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imageModalLabel">Payment Proof</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img id="modalImage" src="" alt="Payment Proof" class="img-fluid">
            </div>
        </div>
    </div>
</div>

<!-- Contact Customer Modal -->
<div class="modal fade" id="contactCustomerModal" tabindex="-1" aria-labelledby="contactCustomerModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="contactCustomerModalLabel">Contact Customer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    Please contact the customer directly to resolve any issues with their payment submission.
                </div>
                <h6 class="text-primary"><i class="fas fa-user"></i> Customer Details</h6>
                <p><strong>Name:</strong> <span id="modalCustomerName"></span></p>
                <p><strong>Email:</strong> <span id="modalCustomerEmail"></span></p>
                <p><strong>Phone:</strong> <span id="modalCustomerPhone"></span></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
// Show image in modal
function showImageModal(src) {
    if (src && !src.startsWith('http')) {
        src = '<?= BASE_URL ?>/' + src.replace(/^\/+/, '');
    }
    document.getElementById('modalImage').src = src;
    new bootstrap.Modal(document.getElementById('imageModal')).show();
}

// Verify payment
function verifyPayment(paymentId, bookingId) {
    if (confirm('Are you sure you want to verify and approve this payment? This action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '?controller=payment&action=verifyPayment';
        
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'payment_id';
        input.value = paymentId;
        
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }
}

// Populate contact customer modal
function populateContactModal(name, email, phone) {
    document.getElementById('modalCustomerName').textContent = name;
    document.getElementById('modalCustomerEmail').textContent = email;
    document.getElementById('modalCustomerPhone').textContent = phone;
}

// Auto-refresh every 30 seconds if there are pending payments
<?php if (!empty($pendingPayments)): ?>
setTimeout(function() {
    // Only refresh if user hasn't interacted with modals
    if (!document.querySelector('.modal.show')) {
        location.reload();
    }
}, 30000);
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
