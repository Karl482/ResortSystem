<?php
$pageTitle = "Create Promotion";
require_once __DIR__ . '/../../partials/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-plus"></i> Create New Promotion</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="?controller=admin&action=createPromotion" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label">Promotion Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" required placeholder="e.g., Summer Beach Getaway Sale!">
                            <small class="text-muted">This will be the main headline of your news</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Message <span class="text-danger">*</span></label>
                            <textarea name="message" class="form-control" rows="8" required placeholder="Write your news or announcement here..."></textarea>
                            <small class="text-muted">Share important information with your users</small>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Create Promotion
                            </button>
                            <a href="?controller=admin&action=promotions" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card bg-light">
                <div class="card-header">
                    <h5><i class="fas fa-info-circle"></i> Tips for Great Promotions</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled">
                        <li class="mb-3">
                            <i class="fas fa-check-circle text-success"></i>
                            <strong>Clear Title:</strong> Make it descriptive and attention-grabbing
                        </li>
                        <li class="mb-3">
                            <i class="fas fa-check-circle text-success"></i>
                            <strong>Concise Message:</strong> Keep it brief and to the point
                        </li>
                        <li class="mb-3">
                            <i class="fas fa-check-circle text-success"></i>
                            <strong>Eye-catching Image:</strong> Use high-quality resort photos
                        </li>
                        <li class="mb-3">
                            <i class="fas fa-check-circle text-success"></i>
                            <strong>Strong CTA:</strong> Use action words like "Book Now"
                        </li>
                        <li class="mb-3">
                            <i class="fas fa-check-circle text-success"></i>
                            <strong>Limited Time:</strong> Create urgency with expiration dates
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
