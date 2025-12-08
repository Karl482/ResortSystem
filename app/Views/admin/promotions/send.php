<?php
$pageTitle = "Send News";
require_once __DIR__ . '/../../partials/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-paper-plane"></i> Send News to Users</h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>News:</strong> <?= htmlspecialchars($promotion->Title) ?>
                    </div>

                    <form method="POST" action="?controller=admin&action=sendPromotion&id=<?= $promotion->PromotionID ?>">
                        <div class="mb-3">
                            <label class="form-label">Select Recipients <span class="text-danger">*</span></label>
                            
                            <!-- Quick Select Buttons -->
                            <div class="btn-group mb-3 w-100" role="group">
                                <button type="button" class="btn btn-outline-primary" id="selectAll">
                                    <i class="fas fa-check-double"></i> Select All
                                </button>
                                <button type="button" class="btn btn-outline-secondary" id="selectCustomers">
                                    <i class="fas fa-users"></i> All Customers
                                </button>
                                <button type="button" class="btn btn-outline-secondary" id="selectStaff">
                                    <i class="fas fa-user-tie"></i> All Staff
                                </button>
                                <button type="button" class="btn btn-outline-secondary" id="selectAdmins">
                                    <i class="fas fa-user-shield"></i> All Admins
                                </button>
                                <button type="button" class="btn btn-outline-danger" id="deselectAll">
                                    <i class="fas fa-times"></i> Clear
                                </button>
                            </div>
                            
                            <div style="max-height: 450px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 4px; padding: 15px;">
                                <?php 
                                $totalUsers = 0;
                                foreach ($usersByRole as $role => $users): 
                                    if (empty($users)) continue;
                                    $totalUsers += count($users);
                                ?>
                                    <div class="mb-3">
                                        <h6 class="text-primary border-bottom pb-2">
                                            <i class="fas fa-<?= $role === 'Customer' ? 'users' : ($role === 'Staff' ? 'user-tie' : 'user-shield') ?>"></i>
                                            <?= $role ?>s (<?= count($users) ?>)
                                        </h6>
                                        <?php foreach ($users as $user): ?>
                                            <div class="form-check mb-2 ms-3">
                                                <input class="form-check-input user-checkbox <?= strtolower($role) ?>-checkbox" 
                                                       type="checkbox" 
                                                       name="customer_ids[]" 
                                                       value="<?= $user['UserID'] ?>" 
                                                       id="user<?= $user['UserID'] ?>">
                                                <label class="form-check-label" for="user<?= $user['UserID'] ?>">
                                                    <?= htmlspecialchars($user['FirstName'] . ' ' . $user['LastName']) ?>
                                                    <small class="text-muted">(<?= htmlspecialchars($user['Email']) ?>)</small>
                                                    <?php if ($user['Role'] === 'Admin' && $user['UserID'] == $_SESSION['user_id']): ?>
                                                        <span class="badge bg-info">You</span>
                                                    <?php endif; ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="mt-2">
                                <small class="text-muted">
                                    Selected: <strong><span id="selectedCount">0</span></strong> out of <?= $totalUsers ?> users
                                </small>
                            </div>
                        </div>

                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Note:</strong> This will send the news email to all selected users. This action cannot be undone.
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary" id="sendBtn" disabled>
                                <i class="fas fa-paper-plane"></i> Send to Selected Users
                            </button>
                            <a href="?controller=admin&action=previewPromotion&id=<?= $promotion->PromotionID ?>" class="btn btn-info" target="_blank">
                                <i class="fas fa-eye"></i> Preview Email
                            </a>
                            <a href="?controller=admin&action=promotions" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-chart-bar"></i> News Details</h5>
                </div>
                <div class="card-body">
                    <p><strong>Title:</strong><br><?= htmlspecialchars($promotion->Title) ?></p>
                    <p><strong>Message:</strong><br>
                        <small class="text-muted"><?= htmlspecialchars(substr($promotion->Message, 0, 150)) ?><?= strlen($promotion->Message) > 150 ? '...' : '' ?></small>
                    </p>
                    <hr>
                    <p><strong>Available Recipients:</strong><br>
                        <?php 
                        $totalCount = 0;
                        foreach ($usersByRole as $users) {
                            $totalCount += count($users);
                        }
                        echo $totalCount;
                        ?>
                    </p>
                    <hr>
                    <p class="small mb-1"><strong>By Role:</strong></p>
                    <?php foreach ($usersByRole as $role => $users): ?>
                        <?php if (!empty($users)): ?>
                            <p class="small mb-1">
                                <i class="fas fa-<?= $role === 'Customer' ? 'users' : ($role === 'Staff' ? 'user-tie' : 'user-shield') ?>"></i>
                                <?= $role ?>s: <?= count($users) ?>
                            </p>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAllBtn = document.getElementById('selectAll');
    const selectCustomersBtn = document.getElementById('selectCustomers');
    const selectStaffBtn = document.getElementById('selectStaff');
    const selectAdminsBtn = document.getElementById('selectAdmins');
    const deselectAllBtn = document.getElementById('deselectAll');
    const userCheckboxes = document.querySelectorAll('.user-checkbox');
    const selectedCountSpan = document.getElementById('selectedCount');
    const sendBtn = document.getElementById('sendBtn');

    function updateSelectedCount() {
        const checkedCount = document.querySelectorAll('.user-checkbox:checked').length;
        selectedCountSpan.textContent = checkedCount;
        sendBtn.disabled = checkedCount === 0;
    }

    // Select All
    selectAllBtn.addEventListener('click', function() {
        userCheckboxes.forEach(checkbox => {
            checkbox.checked = true;
        });
        updateSelectedCount();
    });

    // Select All Customers
    selectCustomersBtn.addEventListener('click', function() {
        document.querySelectorAll('.customer-checkbox').forEach(checkbox => {
            checkbox.checked = true;
        });
        updateSelectedCount();
    });

    // Select All Staff
    selectStaffBtn.addEventListener('click', function() {
        document.querySelectorAll('.staff-checkbox').forEach(checkbox => {
            checkbox.checked = true;
        });
        updateSelectedCount();
    });

    // Select All Admins
    selectAdminsBtn.addEventListener('click', function() {
        document.querySelectorAll('.admin-checkbox').forEach(checkbox => {
            checkbox.checked = true;
        });
        updateSelectedCount();
    });

    // Deselect All
    deselectAllBtn.addEventListener('click', function() {
        userCheckboxes.forEach(checkbox => {
            checkbox.checked = false;
        });
        updateSelectedCount();
    });

    // Update count when individual checkboxes change
    userCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectedCount);
    });

    updateSelectedCount();
});
</script>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
