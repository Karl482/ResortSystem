<?php
// Prevent direct access to this file
if (!defined('APP_LOADED')) {
    http_response_code(403);
    require_once __DIR__ . '/../../errors/403.php';
    exit;
}
?>
<div class="container-fluid">
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger">Failed to update facility. Please try again.</div>
    <?php endif; ?>

    <form action="?controller=admin&action=editFacility&id=<?= htmlspecialchars($facility->facilityId) ?>" method="POST" id="editFacilityForm">
        <div class="mb-3">
            <label for="resortId" class="form-label">Resort</label>
            <select class="form-select" id="resortId" name="resortId" required>
                <option value="">Select a Resort</option>
                <?php foreach ($resorts as $resort): ?>
                    <option value="<?= htmlspecialchars($resort->resortId) ?>" <?= ($facility->resortId == $resort->resortId) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($resort->name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label for="name" class="form-label">Facility Name</label>
            <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($facility->name) ?>" required>
        </div>
        <div class="mb-3">
            <label for="rate" class="form-label">Price (₱)</label>
            <input type="number" step="0.01" class="form-control" id="rate" name="rate" value="<?= htmlspecialchars($facility->rate) ?>" required>
        </div>
        <div class="mb-3">
            <label for="shortDescription" class="form-label">Short Description</label>
            <textarea class="form-control" id="shortDescription" name="shortDescription" rows="2"><?= htmlspecialchars($facility->shortDescription) ?></textarea>
        </div>
        <div class="mb-3">
            <label for="fullDescription" class="form-label">Full Description</label>
            <textarea class="form-control" id="fullDescription" name="fullDescription" rows="5"><?= htmlspecialchars($facility->fullDescription) ?></textarea>
        </div>
    </form>

    <hr>

    <h5>Manage Photos</h5>
    
    <!-- Photo Upload Form -->
    <form action="?controller=admin&action=uploadPhoto&id=<?= $facility->facilityId ?>" method="POST" enctype="multipart/form-data" class="mb-3">
        <div class="input-group">
            <input type="file" class="form-control" name="photo" id="photo" required>
            <button class="btn btn-outline-secondary" type="submit">Upload Photo</button>
        </div>
    </form>

    <!-- Photo Gallery -->
    <div class="row">
        <?php if (empty($facility->photos) && !$facility->mainPhotoURL): ?>
            <p class="text-center">No photos uploaded yet.</p>
        <?php else: ?>
            <!-- Display Main Photo First -->
            <?php if ($facility->mainPhotoURL): ?>
                <div class="col-md-3 mb-3">
                    <div class="card">
                        <img src="<?= BASE_URL . '/' . htmlspecialchars($facility->mainPhotoURL) ?>" class="card-img-top" alt="Main Photo">
                        <div class="card-body text-center">
                            <span class="badge bg-success">Main Photo</span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Display Other Photos -->
            <?php foreach ($facility->photos as $photo): ?>
                <?php if ($photo['PhotoURL'] !== $facility->mainPhotoURL): ?>
                <div class="col-md-3 mb-3">
                    <div class="card">
                        <img src="<?= BASE_URL . '/' . htmlspecialchars($photo['PhotoURL']) ?>" class="card-img-top" alt="Facility Photo">
                        <div class="card-body text-center">
                            <a href="?controller=admin&action=setMainPhoto&id=<?= $facility->facilityId ?>&photoId=<?= $photo['PhotoID'] ?>" class="btn btn-sm btn-primary">Set as Main</a>
                            <a href="?controller=admin&action=deletePhoto&id=<?= $facility->facilityId ?>&photoId=<?= $photo['PhotoID'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this photo?');">Delete</a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary" form="editFacilityForm">Update Facility Details</button>
    </div>
</div>