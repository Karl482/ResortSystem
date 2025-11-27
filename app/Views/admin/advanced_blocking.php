<?php
$pageTitle = "Advanced Blocking System";
require_once __DIR__ . '/../partials/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3><i class="fas fa-ban"></i> Advanced Blocking System</h3>
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
                    <?php if (isset($_SESSION['success_message'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?= $_SESSION['success_message'] ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            <?php unset($_SESSION['success_message']); ?>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['error_message'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= $_SESSION['error_message'] ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            <?php unset($_SESSION['error_message']); ?>
                        </div>
                    <?php endif; ?>
                    <form method="GET" class="row g-3">
                        <input type="hidden" name="controller" value="admin">
                        <input type="hidden" name="action" value="advancedBlocking">
                        
                        <div class="col-md-6">
                            <label class="form-label">Select Resort for Blocking Management</label>
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

                <?php if (!empty($_GET['resort_id'])):
                    $selectedResortId = $_GET['resort_id'];
                    $facilities = Facility::findByResortId($selectedResortId);
                $activeTab = $_GET['tab'] ?? 'resort-blocking';
                ?>
                <div class="card-body">
                    <ul class="nav nav-tabs" id="blockingTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?= $activeTab === 'resort-blocking' ? 'active' : '' ?>" id="resort-blocking-tab" data-bs-toggle="tab" data-bs-target="#resort-blocking" type="button" role="tab">
                                <i class="fas fa-building"></i> Resort Blocking
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?= $activeTab === 'facility-blocking' ? 'active' : '' ?>" id="facility-blocking-tab" data-bs-toggle="tab" data-bs-target="#facility-blocking" type="button" role="tab">
                                <i class="fas fa-swimming-pool"></i> Facility Blocking
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="blockingTabsContent">
                        <!-- Resort Blocking Pane -->
                        <div class="tab-pane fade <?= $activeTab === 'resort-blocking' ? 'show active' : '' ?>" id="resort-blocking" role="tabpanel">
                            <div class="row mt-3">
                                <!-- Preset Blocking Options -->
                                <div class="col-lg-8">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5><i class="fas fa-magic"></i> Preset Blocking Options</h5>
                                            <p class="text-muted mb-0">Quick block multiple dates using preset rules</p>
                                        </div>
                                        <div class="card-body">
                                            <form method="POST" action="?controller=admin&action=applyPresetBlocking" onsubmit="return confirm('Are you sure you want to apply this preset blocking?');">
                                                <input type="hidden" name="resort_id" value="<?= $selectedResortId ?>">
                                                
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">Preset Type</label>
                                                            <select class="form-select" name="preset_type" id="presetTypeSelect" required>
                                                                <option value="">Choose preset...</option>
                                                                <option value="weekends">Weekends Only (Sat & Sun)</option>
                                                                <option value="philippine_holidays">Philippine Holidays</option>
                                                                <option value="all_dates">Full Block (All Dates)</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">Reason</label>
                                                            <input type="text" class="form-control" name="reason" placeholder="e.g., Maintenance, Private Event" required>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="d-flex" id="dateRangeContainer">
                                                    <div class="mb-3 me-2 flex-fill">
                                                        <label class="form-label">Start Date</label>
                                                        <input type="date" class="form-control" name="start_date" min="<?= date('Y-m-d') ?>" required>
                                                    </div>
                                                    <div class="mb-3 ms-2 flex-fill">
                                                        <label class="form-label">End Date</label>
                                                        <input type="date" class="form-control" name="end_date" min="<?= date('Y-m-d') ?>" required>
                                                    </div>
                                                </div>

                                                <div id="holidayCheckboxContainer" class="mb-3 d-none">
                                                    <label class="form-label">Select Holidays to Block (for the current year)</label>
                                                    <div class="row">
                                                        <?php
                                                        $holidayChunks = array_chunk($holidays, ceil(count($holidays) / 2), true);
                                                        foreach ($holidayChunks as $chunk):
                                                        ?>
                                                            <div class="col-md-6">
                                                                <?php foreach ($chunk as $monthDay => $name): ?>
                                                                    <div class="clickable-checkbox p-1 mb-1 bg-light border border-info rounded" onclick="toggleHolidayCheckbox(this)">
                                                                        <input class="form-check-input border-2" type="checkbox" name="holidays[]" value="<?= $monthDay ?>" id="holiday_<?= str_replace('-', '', $monthDay) ?>" style="transform: scale(1.1);">
                                                                        <label class="form-check-label fw-bold text-dark" for="holiday_<?= str_replace('-', '', $monthDay) ?>">
                                                                            <?= htmlspecialchars($name) ?> (<?= date("M j", strtotime(date('Y') . '-' . $monthDay)) ?>)
                                                                        </label>
                                                                    </div>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>

                                                <div class="d-grid">
                                                    <button type="submit" class="btn btn-warning btn-lg">
                                                        <i class="fas fa-ban"></i> Apply Preset Blocking
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>

                                    <!-- Manual Individual Date Blocking -->
                                    <div class="card mt-4">
                                        <div class="card-header">
                                            <h5><i class="fas fa-calendar-plus"></i> Manual Date Blocking</h5>
                                            <p class="text-muted mb-0">Block individual dates manually</p>
                                        </div>
                                        <div class="card-body">
                                            <form method="POST" action="?controller=admin&action=blockResortAvailability" onsubmit="return confirm('Are you sure you want to block this date?');">
                                                <input type="hidden" name="resortId" value="<?= $selectedResortId ?>">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">Block Date</label>
                                                            <input type="date" class="form-control" name="blockDate" min="<?= date('Y-m-d') ?>" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">Reason</label>
                                                            <input type="text" class="form-control" name="reason" placeholder="e.g., Maintenance, Private Event" required>
                                                        </div>
                                                    </div>
                                                </div>
                                                <button type="submit" class="btn btn-danger">
                                                    <i class="fas fa-ban"></i> Block Single Date
                                                </button>
                                            </form>
                                        </div>
                                    </div>

                                   <!-- Deblocking Options -->
                                   <div class="card mt-4">
                                       <div class="card-header">
                                           <h5><i class="fas fa-key"></i> Deblocking Options</h5>
                                           <p class="text-muted mb-0">Remove existing blocks by date range or all at once</p>
                                       </div>
                                       <div class="card-body">
                                           <!-- Deblock by Date Range Section -->
                                           <div class="mb-4">
                                               <h6 class="card-title mb-3 text-success"><i class="fas fa-calendar-days"></i> Deblock by Date Range</h6>
                                               <p class="text-muted small mb-3">Remove blocks for a specific range of dates</p>
                                               <form method="POST" action="?controller=admin&action=deblockByDateRange" onsubmit="return confirm('Are you sure you want to deblock the selected date range?');">
                                                   <input type="hidden" name="resort_id" value="<?= $selectedResortId ?>">
                                                   <div class="row">
                                                       <div class="col-md-6">
                                                           <label class="form-label">Start Date</label>
                                                           <input type="date" class="form-control" name="start_date" required>
                                                       </div>
                                                       <div class="col-md-6">
                                                           <label class="form-label">End Date</label>
                                                           <input type="date" class="form-control" name="end_date" required>
                                                       </div>
                                                   </div>
                                                   <div class="d-flex justify-content-start mt-3">
                                                       <button type="submit" class="btn btn-success">
                                                           <i class="fas fa-key"></i> Deblock Date Range
                                                       </button>
                                                   </div>
                                               </form>
                                           </div>

                                           <!-- Separator -->
                                           <div class="border-bottom my-4"></div>

                                           <!-- Remove All Blocks Section -->
                                           <div class="mt-4">
                                               <h6 class="card-title mb-3 text-warning"><i class="fas fa-exclamation-triangle"></i> Remove All Blocks</h6>
                                               <p class="text-muted small mb-3">Remove all blocked dates for this resort. This action cannot be undone.</p>
                                               <form method="POST" action="?controller=admin&action=deblockAll" onsubmit="return confirm('Are you sure you want to remove ALL blocks for this resort? This action cannot be undone.');">
                                                   <input type="hidden" name="resort_id" value="<?= $selectedResortId ?>">
                                                   <div class="d-flex justify-content-end">
                                                       <button type="submit" class="btn btn-warning">
                                                           <i class="fas fa-globe"></i> Deblock All Dates
                                                       </button>
                                                   </div>
                                               </form>
                                           </div>
                                       </div>
                                   </div>
                                </div>

                                <!-- Preset Information & Calendar Preview -->
                                <div class="col-lg-4">
                                    <div class="card">
                                        <div class="card-header"><h6><i class="fas fa-info-circle"></i> Preset Information</h6></div>
                                        <div class="card-body">
                                            <div class="mb-3"><h6><i class="fas fa-calendar-week text-primary"></i> Weekends</h6><p class="small text-muted">Automatically blocks all Saturdays and Sundays within the selected date range.</p></div>
                                            <div class="mb-3"><h6><i class="fas fa-flag text-success"></i> Philippine Holidays</h6><p class="small text-muted">Blocks major Philippine holidays. The list is dynamically generated.</p></div>
                                            <div class="mb-3"><h6><i class="fas fa-ban text-danger"></i> Full Block</h6><p class="small text-muted">Blocks every single day within the selected date range. Useful for maintenance periods or temporary closure.</p></div>
                                        </div>
                                    </div>
                                    <div class="card mt-3">
                                        <div class="card-header"><h6><i class="fas fa-exclamation-triangle"></i> Important Notes</h6></div>
                                        <div class="card-body">
                                            <ul class="small">
                                                <li><strong>Existing Bookings:</strong> Blocking dates will not cancel existing confirmed bookings</li>
                                                <li><strong>Future Bookings:</strong> Blocked dates will prevent new bookings from being made</li>
                                                <li><strong>Bulk Operations:</strong> Large date ranges may take a few moments to process</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Facility Blocking Pane -->
                        <div class="tab-pane fade <?= $activeTab === 'facility-blocking' ? 'show active' : '' ?>" id="facility-blocking" role="tabpanel">
                            <div class="row mt-3">
                                <div class="col-lg-8">
                                    <!-- Preset Facility Blocking -->
                                    <div class="card">
                                        <div class="card-header">
                                            <h5><i class="fas fa-magic"></i> Preset Facility Blocking</h5>
                                            <p class="text-muted mb-0">Apply preset rules to one or more facilities</p>
                                        </div>
                                        <div class="card-body">
                                            <form method="POST" action="?controller=admin&action=applyFacilityPresetBlocking" onsubmit="return confirm('Are you sure you want to apply this preset to the selected facilities? Please ensure you have selected at least one facility.');">
                                                <input type="hidden" name="resort_id" value="<?= $selectedResortId ?>">
                                                <input type="hidden" name="tab" value="facility-blocking">
                                                <div class="mb-3">
                                                    <label class="form-label">Select Facilities (multiple allowed)</label>
                                                     <div class="row">
                                                        <?php foreach ($facilities as $facility): ?>
                                                            <div class="col-md-6">
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="checkbox" name="facility_ids[]" value="<?= $facility->facilityId ?>" id="facility_preset_<?= $facility->facilityId ?>">
                                                                    <label class="form-check-label" for="facility_preset_<?= $facility->facilityId ?>">
                                                                        <?= htmlspecialchars($facility->name) ?>
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">Preset Type</label>
                                                            <select class="form-select" name="preset_type" id="facilityPresetTypeSelect" required>
                                                                <option value="">Choose preset...</option>
                                                                <option value="weekends">Weekends Only</option>
                                                                <option value="philippine_holidays">Philippine Holidays</option>
                                                                <option value="all_dates">Full Block (All Dates)</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">Reason</label>
                                                            <input type="text" class="form-control" name="reason" placeholder="e.g., Pool Maintenance" required>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="d-flex" id="facilityDateRangeContainer">
                                                    <div class="me-2 flex-fill">
                                                        <label class="form-label">Start Date</label>
                                                        <input type="date" class="form-control" name="start_date" min="<?= date('Y-m-d') ?>">
                                                    </div>
                                                    <div class="ms-2 flex-fill">
                                                        <label class="form-label">End Date</label>
                                                        <input type="date" class="form-control" name="end_date" min="<?= date('Y-m-d') ?>">
                                                    </div>
                                                </div>
                                                <div id="facilityHolidayCheckboxContainer" class="mb-3 d-none">
                                                    <label class="form-label">Select Holidays to Block (for the current year)</label>
                                                    <div class="row">
                                                        <?php
                                                        $holidayChunks = array_chunk($holidays, ceil(count($holidays) / 2), true);
                                                        foreach ($holidayChunks as $chunk):
                                                        ?>
                                                            <div class="col-md-6">
                                                                <?php foreach ($chunk as $monthDay => $name): ?>
                                                                    <div class="clickable-checkbox p-1 mb-1 bg-light border border-info rounded" onclick="toggleHolidayCheckbox(this)">
                                                                        <input class="form-check-input border-2" type="checkbox" name="holidays[]" value="<?= $monthDay ?>" id="facility_holiday_<?= str_replace('-', '', $monthDay) ?>" style="transform: scale(1.1);">
                                                                        <label class="form-check-label fw-bold text-dark" for="facility_holiday_<?= str_replace('-', '', $monthDay) ?>">
                                                                            <?= htmlspecialchars($name) ?> (<?= date("M j", strtotime(date('Y') . '-' . $monthDay)) ?>)
                                                                        </label>
                                                                    </div>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                                <div class="mt-3 d-grid">
                                                    <button type="submit" class="btn btn-warning btn-lg"><i class="fas fa-ban"></i> Apply Facility Preset</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>

                                    <!-- Manual Individual Date Blocking -->
                                    <div class="card mt-4">
                                        <div class="card-header">
                                            <h5><i class="fas fa-calendar-plus"></i> Manual Facility Date Blocking</h5>
                                            <p class="text-muted mb-0">Block an individual date for a specific facility</p>
                                        </div>
                                        <div class="card-body">
                                            <form id="blockFacilityForm" action="?controller=admin&action=blockFacilityAvailability" method="POST" onsubmit="return confirm('Are you sure you want to block this date for the selected facility?');">
                                                <input type="hidden" name="resort_id" value="<?= $selectedResortId ?>">
                                                <input type="hidden" name="tab" value="facility-blocking">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="facilitySelect" class="form-label">Select Facility</label>
                                                            <select class="form-select" id="facilitySelect" name="facilityId" required>
                                                                <option value="">Choose a facility...</option>
                                                                <?php foreach ($facilities as $facility): ?>
                                                                    <option value="<?= $facility->facilityId ?>"><?= htmlspecialchars($facility->name) ?></option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="facilityBlockDate" class="form-label">Block Date</label>
                                                            <input type="date" class="form-control" id="facilityBlockDate" name="blockDate" min="<?= date('Y-m-d') ?>" required>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="facilityBlockReason" class="form-label">Reason</label>
                                                    <input type="text" class="form-control" id="facilityBlockReason" name="reason" placeholder="e.g., Cleaning, Repair" required>
                                                </div>
                                                <button type="submit" class="btn btn-danger"><i class="fas fa-ban"></i> Block Facility Date</button>
                                            </form>
                                        </div>
                                    </div>

                                    <!-- Deblocking Options -->
                                    <div class="card mt-4">
                                        <div class="card-header">
                                            <h5><i class="fas fa-key"></i> Facility Deblocking</h5>
                                            <p class="text-muted mb-0">Remove blocks from a specific facility</p>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label class="form-label">Select Facility to Deblock</label>
                                                <select class="form-select" id="deblockFacilitySelect">
                                                    <option value="">Choose a facility...</option>
                                                    <?php foreach ($facilities as $facility): ?>
                                                        <option value="<?= $facility->facilityId ?>"><?= htmlspecialchars($facility->name) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <!-- Date Range Deblocking Section -->
                                            <div class="mb-4" id="deblockFacilityDateRangeContainer" style="display: none;">
                                                <h6 class="card-title mb-3 text-success"><i class="fas fa-calendar-days"></i> Deblock by Date Range</h6>
                                                <p class="text-muted small mb-3">Remove blocks for a specific range of dates from the selected facility</p>
                                                <form method="POST" action="?controller=admin&action=deblockFacilityByDateRange" id="deblockFacilityDateRangeForm" onsubmit="return confirm('Are you sure you want to deblock this date range for the selected facility?');">
                                                    <input type="hidden" name="resort_id" value="<?= $selectedResortId ?>">
                                                    <input type="hidden" name="tab" value="facility-blocking">
                                                    <input type="hidden" name="facility_id" id="deblockFacilityIdDateRange">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <label class="form-label">Start Date</label>
                                                            <input type="date" class="form-control" name="start_date" required>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label">End Date</label>
                                                            <input type="date" class="form-control" name="end_date" required>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex justify-content-start mt-3">
                                                        <button type="submit" class="btn btn-success">
                                                            <i class="fas fa-key"></i> Deblock Date Range
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>

                                            <!-- Separator -->
                                            <div class="border-bottom my-4" id="facilityDeblockSeparator" style="display: none;"></div>

                                            <!-- Remove All Blocks Section -->
                                            <div class="mt-4" id="deblockFacilityAllContainer" style="display: none;">
                                                <h6 class="card-title mb-3 text-warning"><i class="fas fa-exclamation-triangle"></i> Remove All Blocks</h6>
                                                <p class="text-muted small mb-3">Remove all blocked dates for the selected facility. This action cannot be undone.</p>
                                                <form method="POST" action="?controller=admin&action=deblockFacilityAll" onsubmit="return confirm('Are you sure you want to remove ALL blocks for this facility?');" id="deblockFacilityAllForm">
                                                    <input type="hidden" name="resort_id" value="<?= $selectedResortId ?>">
                                                    <input type="hidden" name="tab" value="facility-blocking">
                                                    <input type="hidden" name="facility_id" id="deblockFacilityIdAll">
                                                    <div class="d-flex justify-content-end">
                                                        <button type="submit" class="btn btn-warning">
                                                            <i class="fas fa-globe"></i> Deblock All Dates for Selected Facility
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="card">
                                        <div class="card-header"><h6><i class="fas fa-info-circle"></i> Preset Information</h6></div>
                                        <div class="card-body">
                                            <div class="mb-3"><h6><i class="fas fa-calendar-week text-primary"></i> Weekends</h6><p class="small text-muted">Automatically blocks all Saturdays and Sundays within the selected date range for the chosen facilities.</p></div>
                                            <div class="mb-3"><h6><i class="fas fa-flag text-success"></i> Philippine Holidays</h6><p class="small text-muted">Blocks major Philippine holidays for the chosen facilities.</p></div>
                                            <div class="mb-3"><h6><i class="fas fa-ban text-danger"></i> Full Block</h6><p class="small text-muted">Blocks every single day within the selected date range for the chosen facilities.</p></div>
                                        </div>
                                    </div>
                                    <div class="card mt-3">
                                        <div class="card-header"><h6><i class="fas fa-exclamation-triangle"></i> Important Notes</h6></div>
                                        <div class="card-body">
                                            <ul class="small">
                                                <li><strong>Existing Bookings:</strong> Blocking dates will not cancel existing confirmed bookings.</li>
                                                <li><strong>Selection:</strong> You can apply presets to multiple facilities by checking their boxes. Manual blocking and deblocking apply to one facility at a time.</li>
                                                <li><strong>Availability:</strong> A blocked facility cannot be included in new bookings for that day.</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Current Blocks Display -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5><i class="fas fa-list"></i> Current Blocked Dates</h5>
                                    <button class="btn btn-outline-primary btn-sm" id="refreshBlocksBtn" data-resort-id="<?= $selectedResortId ?>">
                                        <i class="fas fa-sync"></i> Refresh
                                    </button>
                                </div>
                                <div class="card-body">
                                    <div id="resortBlocksList" class="display-area" style="<?= $activeTab === 'resort-blocking' ? '' : 'display: none;' ?>"></div>
                                    <div id="facilityBlocksList" class="display-area" style="<?= $activeTab === 'facility-blocking' ? '' : 'display: none;' ?>"></div>
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
                        <p class="text-muted">Choose a resort from the dropdown above to manage its blocking settings.</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        </div>
    </div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const resortId = '<?= $selectedResortId ?? '' ?>';
    
    if (resortId) {
        loadResortBlocks(resortId);
        loadAllFacilityBlocks(resortId); // Load facility blocks on page load
    }

    // Handle tab persistence
    document.getElementById('refreshBlocksBtn')?.addEventListener('click', function() {
        const resortId = this.getAttribute('data-resort-id');
        const activeTab = document.querySelector('#blockingTabs .nav-link.active').id;
        if (activeTab === 'resort-blocking-tab') {
            loadResortBlocks(resortId);
        } else {
            loadAllFacilityBlocks(resortId);
        }
    });

    document.querySelectorAll('#blockingTabs .nav-link').forEach(tab => {
        tab.addEventListener('show.bs.tab', function(event) {
            const resortBlocks = document.getElementById('resortBlocksList');
            const facilityBlocks = document.getElementById('facilityBlocksList');
            if (event.target.id === 'resort-blocking-tab') {
                resortBlocks.style.display = 'block';
                facilityBlocks.style.display = 'none';
            } else {
                resortBlocks.style.display = 'none';
                facilityBlocks.style.display = 'block';
            }
        });
    });

    document.getElementById('presetTypeSelect')?.addEventListener('change', function() {
       const holidayContainer = document.getElementById('holidayCheckboxContainer');
       const dateRangeContainer = document.getElementById('dateRangeContainer');
       const startDateInput = dateRangeContainer.querySelector('input[name="start_date"]');
       const endDateInput = dateRangeContainer.querySelector('input[name="end_date"]');

       if (this.value === 'philippine_holidays') {
           holidayContainer.classList.remove('d-none');
           dateRangeContainer.classList.add('d-none');
           startDateInput.required = false;
           endDateInput.required = false;
       } else {
           holidayContainer.classList.add('d-none');
           dateRangeContainer.classList.remove('d-none');
           startDateInput.required = true;
           endDateInput.required = true;
       }
    });

    document.getElementById('deblockFacilitySelect')?.addEventListener('change', function() {
       const facilityId = this.value;
       const dateRangeContainer = document.getElementById('deblockFacilityDateRangeContainer');
       const deblockAllContainer = document.getElementById('deblockFacilityAllContainer');
       const separator = document.getElementById('facilityDeblockSeparator');
       const facilityIdDateRangeInput = document.getElementById('deblockFacilityIdDateRange');
       const facilityIdAllInput = document.getElementById('deblockFacilityIdAll');

       if (facilityId) {
           facilityIdDateRangeInput.value = facilityId;
           facilityIdAllInput.value = facilityId;
           dateRangeContainer.style.display = 'block';
           separator.style.display = 'block';
           deblockAllContainer.style.display = 'block';
       } else {
           dateRangeContainer.style.display = 'none';
           separator.style.display = 'none';
           deblockAllContainer.style.display = 'none';
       }
   });

   document.getElementById('facilityPresetTypeSelect')?.addEventListener('change', function() {
      const holidayContainer = document.getElementById('facilityHolidayCheckboxContainer');
      const dateRangeContainer = document.getElementById('facilityDateRangeContainer');
      const startDateInput = dateRangeContainer.querySelector('input[name="start_date"]');
      const endDateInput = dateRangeContainer.querySelector('input[name="end_date"]');

      if (this.value === 'philippine_holidays') {
          holidayContainer.classList.remove('d-none');
          dateRangeContainer.classList.add('d-none');
          startDateInput.required = false;
          endDateInput.required = false;
      } else {
          holidayContainer.classList.add('d-none');
          dateRangeContainer.classList.remove('d-none');
          startDateInput.required = true;
          endDateInput.required = true;
      }
   });

    function loadResortBlocks(resortId) {
        const blocksList = document.getElementById('resortBlocksList');
        if (!blocksList) return;
        blocksList.innerHTML = '<div class="text-center p-3"><i class="fas fa-spinner fa-spin"></i> Loading resort blocks...</div>';
        
        fetch(`?controller=admin&action=getResortScheduleJson&id=${resortId}`)
            .then(response => response.json())
            .then(data => renderBlocks(data, blocksList, 'resort', resortId))
            .catch(error => console.error('Error loading resort blocks:', error));
    }

    function loadAllFacilityBlocks(resortId) {
        const blocksList = document.getElementById('facilityBlocksList');
        if (!blocksList) return;
        blocksList.innerHTML = '<div class="text-center p-3"><i class="fas fa-spinner fa-spin"></i> Loading facility blocks...</div>';

        const facilities = <?= json_encode($facilities ?? []) ?>;
        let allBlocks = [];
        let completed = 0;

        if (facilities.length === 0) {
            renderBlocks([], blocksList, 'facility', resortId);
            return;
        }

        facilities.forEach(facility => {
            fetch(`?controller=admin&action=getFacilityScheduleJson&id=${facility.facilityId}`)
                .then(response => response.json())
                .then(data => {
                    data.forEach(block => {
                        block.facilityName = facility.name;
                        allBlocks.push(block);
                    });
                })
                .finally(() => {
                    completed++;
                    if (completed === facilities.length) {
                        renderBlocks(allBlocks, blocksList, 'facility', resortId);
                    }
                });
        });
    }

    function renderBlocks(data, container, type, resortId) {
        if (data.error) {
            container.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
            return;
        }
        if (data.length === 0) {
            container.innerHTML = `<div class="alert alert-info"><i class="fas fa-info-circle"></i> No blocked dates found for this ${type}.</div>`;
            return;
        }

        data.sort((a, b) => new Date(a.BlockDate) - new Date(b.BlockDate));
        
        let listHtml = '<div class="row">';
        data.forEach(block => {
            const blockDate = new Date(block.BlockDate);
            const formattedDate = blockDate.toLocaleDateString('en-US', { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' });
            const title = type === 'facility' ? `<strong>${block.facilityName}</strong> - ${formattedDate}` : formattedDate;
            const removeFunction = type === 'resort'
                ? `removeResortBlock(${block.BlockedAvailabilityID}, ${resortId})`
                : `removeFacilityBlock(${block.BlockedAvailabilityID}, ${resortId})`;

            listHtml += `
                <div class="col-md-6 col-lg-4 mb-2">
                    <div class="card border-warning">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="card-title mb-1">${title}</h6>
                                    <p class="card-text small text-muted mb-0">${block.Reason}</p>
                                </div>
                                <button class="btn btn-outline-danger btn-sm" onclick="${removeFunction}" title="Remove block">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>`;
        });
        listHtml += '</div>';
        container.innerHTML = listHtml;
    }

    window.removeResortBlock = function(blockId, resortId) {
        if (!confirm('Are you sure you want to remove this resort block?')) return;
        fetch(`?controller=admin&action=deleteResortAvailabilityBlock&block_id=${blockId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadResortBlocks(resortId);
                } else {
                    alert('Failed to remove block.');
                }
            });
    }

    window.removeFacilityBlock = function(blockId, resortId) {
        if (!confirm('Are you sure you want to remove this facility block?')) return;
        fetch(`?controller=admin&action=deleteFacilityAvailabilityBlock&block_id=${blockId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadAllFacilityBlocks(resortId);
                } else {
                    alert('Failed to remove block.');
                }
            });
    }

    window.toggleHolidayCheckbox = function(container) {
        const checkbox = container.querySelector('input[type="checkbox"]');
        if (checkbox) {
            checkbox.checked = !checkbox.checked;
        }
    }
});
</script>
