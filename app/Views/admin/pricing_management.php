<?php
$pageTitle = "Pricing Management";
require_once __DIR__ . '/../partials/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3><i class="fas fa-tags"></i> Pricing Management</h3>
                    <div class="d-flex gap-2">
                        <a href="?controller=admin&action=management" class="btn btn-secondary">
                            <i class="fas fa-building"></i> Resort Management
                        </a>
                        <a href="?controller=admin&action=dashboard" class="btn btn-primary">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </div>
                </div>

                <!-- Resort Selection -->
                <div class="card-body border-bottom">
                    <form method="GET" class="row g-3">
                        <input type="hidden" name="controller" value="admin">
                        <input type="hidden" name="action" value="pricingManagement">
                        
                        <div class="col-md-6">
                            <label class="form-label">Select Resort to Manage Pricing</label>
                            <select name="resort_id" class="form-select" onchange="this.form.submit()" required>
                                <option value="">Choose a resort...</option>
                                <?php foreach ($resorts as $resort): ?>
                                    <option value="<?= $resort->resortId ?>" 
                                        <?= (isset($_GET['resort_id']) && $_GET['resort_id'] == $resort->resortId) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($resort->name) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                </div>

                <?php if (!empty($_GET['resort_id'])): ?>
                <div class="card-body">
                    <div class="row">
                        <!-- Timeframe Pricing Management -->
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-clock"></i> Timeframe Pricing</h5>
                                    <p class="text-muted mb-0">Base pricing for different booking timeframes</p>
                                </div>
                                <div class="card-body">
                                    <?php 
                                    $timeframes = ResortTimeframePricing::getTimeframeTypes();
                                    foreach ($timeframes as $timeframe): 
                                        $existingPricing = null;
                                        foreach ($resortPricing as $pricing) {
                                            if ($pricing->TimeframeType === $timeframe) {
                                                $existingPricing = $pricing;
                                                break;
                                            }
                                        }
                                    ?>
                                        <form method="POST" action="?controller=admin&action=updateResortPricing" class="border p-3 mb-3 rounded">
                                            <input type="hidden" name="resort_id" value="<?= $_GET['resort_id'] ?>">
                                            <input type="hidden" name="timeframe_type" value="<?= $timeframe ?>">
                                            
                                            <div class="row align-items-end">
                                                <div class="col-md-3">
                                                    <label class="form-label fw-bold">
                                                        <?= ResortTimeframePricing::getTimeframeDisplay($timeframe) ?>
                                                    </label>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">Base Price (₱)</label>
                                                    <input type="number" step="0.01" class="form-control" name="base_price" 
                                                           value="<?= $existingPricing ? $existingPricing->BasePrice : '' ?>" 
                                                           placeholder="0.00" required>
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label">Weekend +₱</label>
                                                    <input type="number" step="0.01" class="form-control" name="weekend_surcharge" 
                                                           value="<?= $existingPricing ? $existingPricing->WeekendSurcharge : '0' ?>" 
                                                           placeholder="0.00">
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label">Holiday +₱</label>
                                                    <input type="number" step="0.01" class="form-control" name="holiday_surcharge" 
                                                           value="<?= $existingPricing ? $existingPricing->HolidaySurcharge : '0' ?>" 
                                                           placeholder="0.00">
                                                </div>
                                                <div class="col-md-2">
                                                    <button type="submit" class="btn btn-success w-100">
                                                        <i class="fas fa-save"></i> Save
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Pricing Status -->
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-calculator"></i> Pricing Status</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Timeframe</th>
                                                    <th>Regular</th>
                                                    <th>Weekend</th>
                                                    <th>Holiday</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($timeframes as $timeframe): 
                                                    $pricing = null;
                                                    foreach ($resortPricing as $p) {
                                                        if ($p->TimeframeType === $timeframe) {
                                                            $pricing = $p;
                                                            break;
                                                        }
                                                    }
                                                ?>
                                                    <tr>
                                                        <td><small><?= str_replace('_', ' ', ucfirst($timeframe)) ?></small></td>
                                                        <td>
                                                            <?php if ($pricing): ?>
                                                                <strong>₱<?= number_format($pricing->BasePrice, 0) ?></strong>
                                                            <?php else: ?>
                                                                <small class="text-muted">Not set</small>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if ($pricing): ?>
                                                                <strong>₱<?= number_format($pricing->BasePrice + $pricing->WeekendSurcharge, 0) ?></strong>
                                                            <?php else: ?>
                                                                <small class="text-muted">-</small>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if ($pricing): ?>
                                                                <strong>₱<?= number_format($pricing->BasePrice + $pricing->HolidaySurcharge, 0) ?></strong>
                                                            <?php else: ?>
                                                                <small class="text-muted">-</small>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <div class="card mt-3">
                                <div class="card-header">
                                    <h6><i class="fas fa-info-circle"></i> Pricing Notes</h6>
                                </div>
                                <div class="card-body">
                                    <ul class="small">
                                        <li><strong>Base Price:</strong> Regular weekday pricing</li>
                                        <li><strong>Weekend Surcharge:</strong> Additional cost for Saturdays & Sundays</li>
                                        <li><strong>Holiday Surcharge:</strong> Extra cost for Philippine holidays</li>
                                        <li><strong>Facilities:</strong> Added as fixed costs to any timeframe</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Facility Pricing Management -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-swimming-pool"></i> Facility Add-on Pricing</h5>
                                    <p class="text-muted mb-0">Fixed pricing for facility add-ons (applies to any timeframe)</p>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($facilityPricing)): ?>
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle"></i> No facilities found for this resort. 
                                            <a href="?controller=admin&action=management">Add facilities</a> to manage their pricing.
                                        </div>
                                    <?php else: ?>
                                        <div class="row">
                                            <?php foreach ($facilityPricing as $facility): ?>
                                                <div class="col-md-6 col-lg-4 mb-3">
                                                    <form method="POST" action="?controller=admin&action=updateFacilityPricing" class="card h-100">
                                                        <div class="card-body">
                                                            <input type="hidden" name="facility_id" value="<?= $facility->facilityId ?>">
                                                            
                                                            <h6 class="card-title">
                                                                <i class="fas fa-building"></i> <?= htmlspecialchars($facility->name) ?>
                                                            </h6>
                                                            
                                                            <div class="input-group">
                                                                <span class="input-group-text">₱</span>
                                                                <input type="number" step="0.01" class="form-control" name="rate" 
                                                                       value="<?= $facility->rate ?>" required>
                                                                <button type="submit" class="btn btn-primary">
                                                                    <i class="fas fa-save"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Methods Management -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-credit-card"></i> Payment Method Management</h5>
                                    <p class="text-muted mb-0">Manage accepted payment methods for this resort</p>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-lg-7">
                                            <h6>Existing Methods</h6>
                                            <?php if (empty($paymentMethods)): ?>
                                                <div class="alert alert-info">No payment methods configured for this resort.</div>
                                            <?php else: ?>
                                                <ul class="list-group">
                                                    <?php foreach ($paymentMethods as $method): ?>
                                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                                            <div>
                                                                <strong><?= htmlspecialchars($method->MethodType) ?></strong>
                                                                <ul class="list-unstyled small ps-3">
                                                                    <li><strong>Name:</strong> <?= htmlspecialchars($method->AccountName) ?></li>
                                                                    <li><strong>Number:</strong> <?= htmlspecialchars($method->AccountNumber) ?></li>
                                                                    <li>
                                                                        <strong>QR Code:</strong>
                                                                        <?php if ($method->QrCodeURL): ?>
                                                                            <a href="<?= BASE_URL . '/' . htmlspecialchars($method->QrCodeURL) ?>" target="_blank">View QR</a>
                                                                        <?php else: ?>
                                                                            <span class="text-muted">Not set</span>
                                                                        <?php endif; ?>
                                                                    </li>
                                                                </ul>
                                                            </div>
                                                            <a href="?controller=admin&action=deletePaymentMethod&id=<?= $method->PaymentMethodID ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this payment method?');">
                                                                <i class="fas fa-trash"></i>
                                                            </a>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-lg-5">
                                            <h6>Add New Method</h6>
                                            <form method="POST" action="?controller=admin&action=addPaymentMethod" enctype="multipart/form-data">
                                               <input type="hidden" name="resort_id" value="<?= $_GET['resort_id'] ?>">
                                               
                                               <div class="mb-3">
                                                   <label for="method_type" class="form-label">Payment Method</label>
                                                   <select class="form-select" id="method_type" name="method_type" required>
                                                       <option value="">Select a method...</option>
                                                       <?php
                                                       $availableTypes = ResortPaymentMethods::getAvailableMethodTypes();
                                                       foreach ($availableTypes as $type):
                                                           // Check if this method type is already configured
                                                           $isConfigured = false;
                                                           foreach ($paymentMethods as $existingMethod) {
                                                               if ($existingMethod->MethodType === $type) {
                                                                   $isConfigured = true;
                                                                   break;
                                                               }
                                                           }
                                                       ?>
                                                           <option value="<?= htmlspecialchars($type) ?>" <?= $isConfigured ? 'disabled' : '' ?>>
                                                               <?= htmlspecialchars($type) ?> <?= $isConfigured ? '(Already configured)' : '' ?>
                                                           </option>
                                                       <?php endforeach; ?>
                                                   </select>
                                               </div>

                                               <div class="mb-3">
                                                   <label for="account_name" class="form-label">Account Name</label>
                                                   <input type="text" class="form-control" id="account_name" name="account_name" placeholder="e.g., Juan Dela Cruz" required>
                                               </div>

                                               <div class="mb-3">
                                                   <label for="account_number" class="form-label">Account Number</label>
                                                   <input type="text" class="form-control" id="account_number" name="account_number" placeholder="e.g., 09123456789" required>
                                               </div>

                                               <div class="mb-3">
                                                   <label for="qr_code" class="form-label">QR Code Image <span class="text-danger">*</span></label>
                                                   <input type="file" class="form-control" id="qr_code" name="qr_code" accept="image/*" required>
                                                   <div class="form-text">A QR code image is required.</div>
                                               </div>

                                               <button type="submit" class="btn btn-primary w-100">
                                                   <i class="fas fa-plus"></i> Add Payment Method
                                               </button>
                                           </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Overview and Comparison -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-receipt"></i> Overview and Comparison</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <h6>Regular Day (Weekday)</h6>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>Package</th>
                                                            <th>Price</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($timeframes as $timeframe):
                                                            $pricing = null;
                                                            foreach ($resortPricing as $p) {
                                                                if ($p->TimeframeType === $timeframe) {
                                                                    $pricing = $p;
                                                                    break;
                                                                }
                                                            }
                                                        ?>
                                                            <tr>
                                                                <td><?= str_replace('_', ' ', ucfirst($timeframe)) ?></td>
                                                                <td>
                                                                    <?php if ($pricing): ?>
                                                                        ₱<?= number_format($pricing->BasePrice, 2) ?>
                                                                    <?php else: ?>
                                                                        <span class="text-muted">Not set</span>
                                                                    <?php endif; ?>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <h6>Weekend Day (Saturday/Sunday)</h6>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>Package</th>
                                                            <th>Price</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($timeframes as $timeframe):
                                                            $pricing = null;
                                                            foreach ($resortPricing as $p) {
                                                                if ($p->TimeframeType === $timeframe) {
                                                                    $pricing = $p;
                                                                    break;
                                                                }
                                                            }
                                                        ?>
                                                            <tr>
                                                                <td><?= str_replace('_', ' ', ucfirst($timeframe)) ?></td>
                                                                <td>
                                                                    <?php if ($pricing): ?>
                                                                        ₱<?= number_format($pricing->BasePrice + $pricing->WeekendSurcharge, 2) ?>
                                                                        <?php if ($pricing->WeekendSurcharge > 0): ?>
                                                                            <small class="text-success">(+₱<?= number_format($pricing->WeekendSurcharge, 0) ?>)</small>
                                                                        <?php endif; ?>
                                                                    <?php else: ?>
                                                                        <span class="text-muted">Not set</span>
                                                                    <?php endif; ?>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <h6>Holiday</h6>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>Package</th>
                                                            <th>Price</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($timeframes as $timeframe):
                                                            $pricing = null;
                                                            foreach ($resortPricing as $p) {
                                                                if ($p->TimeframeType === $timeframe) {
                                                                    $pricing = $p;
                                                                    break;
                                                                }
                                                            }
                                                        ?>
                                                            <tr>
                                                                <td><?= str_replace('_', ' ', ucfirst($timeframe)) ?></td>
                                                                <td>
                                                                    <?php if ($pricing): ?>
                                                                        ₱<?= number_format($pricing->BasePrice + $pricing->HolidaySurcharge, 2) ?>
                                                                        <?php if ($pricing->HolidaySurcharge > 0): ?>
                                                                            <small class="text-primary">(+₱<?= number_format($pricing->HolidaySurcharge, 0) ?>)</small>
                                                                        <?php endif; ?>
                                                                    <?php else: ?>
                                                                        <span class="text-muted">Not set</span>
                                                                    <?php endif; ?>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="card-body">
                    <div class="text-center p-5">
                        <i class="fas fa-hand-point-up fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">Select a Resort</h4>
                        <p class="text-muted">Choose a resort from the dropdown above to manage its pricing.</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
