<?php
$pageTitle = "News Management";
require_once __DIR__ . '/../../partials/header.php';
?>

<div class="container-fluid mt-4">
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= htmlspecialchars($_SESSION['success_message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= htmlspecialchars($_SESSION['error_message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3><i class="fas fa-bullhorn"></i> News Management</h3>
                    <a href="?controller=admin&action=createPromotion" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create New Promotion
                    </a>
                </div>

                <div class="card-body">
                    <?php if (empty($promotions)): ?>
                        <div class="text-center p-5">
                            <i class="fas fa-bullhorn fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">No Newss Yet</h4>
                            <p class="text-muted">Create your first news to engage customers!</p>
                            <a href="?controller=admin&action=createPromotion" class="btn btn-primary mt-3">
                                <i class="fas fa-plus"></i> Create Promotion
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Title</th>
                                        <th>Message Preview</th>
                                        <th>Created By</th>
                                        <th>Created At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($promotions as $promo): ?>
                                        <tr>
                                            <td><?= $promo->PromotionID ?></td>
                                            <td>
                                                <strong><?= htmlspecialchars($promo->Title) ?></strong>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?= htmlspecialchars(substr($promo->Message, 0, 100)) ?><?= strlen($promo->Message) > 100 ? '...' : '' ?>
                                                </small>
                                            </td>
                                            <td><?= htmlspecialchars($promo->CreatorName) ?></td>
                                            <td><?= date('M j, Y', strtotime($promo->CreatedAt)) ?></td>
                                            <td>
                                                <div class="d-flex gap-1">
                                                    <a href="?controller=admin&action=sendPromotion&id=<?= $promo->PromotionID ?>" class="btn btn-sm btn-success" title="Send to Users">
                                                        <i class="fas fa-paper-plane"></i> Send
                                                    </a>
                                                    <a href="?controller=admin&action=editPromotion&id=<?= $promo->PromotionID ?>" class="btn btn-sm btn-primary" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="?controller=admin&action=deletePromotion&id=<?= $promo->PromotionID ?>" 
                                                       class="btn btn-sm btn-danger" 
                                                       title="Delete"
                                                       onclick="return confirm('Are you sure you want to delete this news?\n\nTitle: <?= htmlspecialchars($promo->Title) ?>\n\nThis action cannot be undone.')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                    <a href="?controller=admin&action=previewPromotion&id=<?= $promo->PromotionID ?>" class="btn btn-sm btn-info" title="Preview Email" target="_blank">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
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

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
