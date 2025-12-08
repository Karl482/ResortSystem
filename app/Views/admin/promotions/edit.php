<?php
$pageTitle = "Edit Promotion";
require_once __DIR__ . '/../../partials/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-edit"></i> Edit Promotion</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="?controller=admin&action=editPromotion&id=<?= $promotion->PromotionID ?>" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label">Promotion Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" required value="<?= htmlspecialchars($promotion->Title) ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Message <span class="text-danger">*</span></label>
                            <textarea name="message" class="form-control" rows="8" required><?= htmlspecialchars($promotion->Message) ?></textarea>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update News
                            </button>
                            <a href="?controller=admin&action=previewPromotion&id=<?= $promotion->PromotionID ?>" class="btn btn-info" target="_blank">
                                <i class="fas fa-eye"></i> Preview
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
                    <h5><i class="fas fa-info-circle"></i> News Info</h5>
                </div>
                <div class="card-body">
                    <p><strong>Created:</strong><br><?= date('F j, Y g:i A', strtotime($promotion->CreatedAt)) ?></p>
                    <p><strong>Last Updated:</strong><br><?= date('F j, Y g:i A', strtotime($promotion->UpdatedAt)) ?></p>
                    <?php
                    require_once __DIR__ . '/../../Models/PromotionNotification.php';
                    $stats = PromotionNotification::getSentStats($promotion->PromotionID);
                    if ($stats && $stats->total_sent > 0):
                    ?>
                        <p><strong>Sent to:</strong><br><?= $stats->total_sent ?> customers</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
