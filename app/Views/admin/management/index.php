<?php
$pageTitle = "Manage Resort";
require_once __DIR__ . '/../../partials/header.php';
?>

<div class="container mt-4">
    <!-- Message Alerts -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['success_message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['error_message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Manage Resort</h3>
                                         <button class="btn btn-primary btn-sm float-end" data-bs-toggle="modal" data-bs-target="#addResortModal">
                        <i class="fas fa-plus"></i> Add New Resort
                    </button>
                </div>
                <div class="card-body">
                    <?php if (empty($resortsWithFacilities)): ?>
                        <div class="text-center p-4">
                            <h4>No resorts found.</h4>
                            <p>Get started by adding your first resort.</p>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addResortModal">
                                Add Your First Resort
                            </button>
                        </div>
                    <?php else: ?>
                    <div class="accordion" id="resortsAccordion">
                        <?php
                        $activeResortId = $_GET['active_resort_id'] ?? ($resortsWithFacilities[0]['resort']->resortId ?? null);
                        foreach ($resortsWithFacilities as $index => $resortData):
                            $isExpanded = $resortData['resort']->resortId == $activeResortId;
                        ?>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="heading<?= $resortData['resort']->resortId ?>">
                                    <button class="accordion-button <?= !$isExpanded ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $resortData['resort']->resortId ?>" aria-expanded="<?= $isExpanded ? 'true' : 'false' ?>" aria-controls="collapse<?= $resortData['resort']->resortId ?>">
                                        <strong><?= htmlspecialchars($resortData['resort']->name) ?></strong>
                                    </button>
                                </h2>
                                <div id="collapse<?= $resortData['resort']->resortId ?>" class="accordion-collapse collapse <?= $isExpanded ? 'show' : '' ?>" aria-labelledby="heading<?= $resortData['resort']->resortId ?>" data-bs-parent="#resortsAccordion">
                                    <div class="accordion-body">
                                        <div class="d-flex justify-content-end mb-3">
                                            <a href="?controller=admin&action=pricingManagement&resort_id=<?= $resortData['resort']->resortId ?>" class="btn btn-success btn-sm">Pricing Management</a>
                                            <a href="?controller=admin&action=advancedBlocking&resort_id=<?= $resortData['resort']->resortId ?>" class="btn btn-warning btn-sm ms-2">Advanced Blocking</a>
                                            <button class="btn btn-info btn-sm ms-2 edit-resort-btn" data-bs-toggle="modal" data-bs-target="#editResortModal" data-resort-id="<?= $resortData['resort']->resortId ?>">Edit Resort</button>
                                            <button class="btn btn-danger btn-sm ms-2 delete-resort-btn" data-bs-toggle="modal" data-bs-target="#deleteResortModal" data-resort-id="<?= $resortData['resort']->resortId ?>">Delete Resort</button>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h5 class="mb-0">Facilities</h5>
                                            <button class="btn btn-primary btn-sm add-facility-btn" data-bs-toggle="modal" data-bs-target="#addFacilityModal" data-resort-id="<?= $resortData['resort']->resortId ?>">
                                                <i class="fas fa-plus"></i> Add Facility
                                            </button>
                                        </div>
                                        <table class="table table-sm table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Name</th>
                                                    <th>Price</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($resortData['facilities'])): ?>
                                                    <tr>
                                                        <td colspan="5" class="text-center">No facilities found for this resort.</td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($resortData['facilities'] as $facility): ?>
                                                        <tr data-facility-id="<?= $facility->facilityId ?>">
                                                            <td><?= htmlspecialchars($facility->facilityId) ?></td>
                                                            <td><?= htmlspecialchars($facility->name) ?></td>
                                                            <td>₱<?= htmlspecialchars(number_format($facility->rate, 2)) ?></td>
                                                            <td>
                                                                <button class="btn btn-info btn-sm edit-facility-btn" data-bs-toggle="modal" data-bs-target="#editFacilityModal" data-facility-id="<?= $facility->facilityId ?>">Edit</button>
                                                                <button class="btn btn-danger btn-sm delete-facility-btn" data-bs-toggle="modal" data-bs-target="#deleteFacilityModal" data-facility-id="<?= $facility->facilityId ?>">Delete</button>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals -->
<?php
// The APP_LOADED constant is already defined in public/index.php
// Including the resort modals, which now handle add, edit, and delete
require_once __DIR__ . '/../resorts/resort_modals.php';
require_once __DIR__ . '/facility_modals.php';
require_once __DIR__ . '/edit_facility_modal.php';
?>



<?php require_once __DIR__ . '/../../partials/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {

    // Delete Facility Modal Handler
    var deleteFacilityModal = document.getElementById('deleteFacilityModal');
    if(deleteFacilityModal) {
        deleteFacilityModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var facilityId = button.getAttribute('data-facility-id');
            var confirmBtn = document.getElementById('confirmDeleteFacilityBtn');
            confirmBtn.href = '?controller=admin&action=deleteFacility&id=' + facilityId;
        });
    }

    // Edit Resort Modal Handler
    var editResortModal = document.getElementById('editResortModal');
    if(editResortModal) {
        editResortModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var resortId = button.getAttribute('data-resort-id');
            
            fetch('?controller=admin&action=getResortJson&id=' + resortId)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                        return;
                    }
                    
                    document.getElementById('editResortId').value = data.resortId;
                    document.getElementById('editName').value = data.name;
                    document.getElementById('editAddress').value = data.address;
                    document.getElementById('editContactPerson').value = data.contactPerson;
                    document.getElementById('editShortDescription').value = data.shortDescription || '';
                    document.getElementById('editFullDescription').value = data.fullDescription || '';

                    const photoGallery = document.getElementById('photoGallery');
                    photoGallery.innerHTML = ''; // Clear previous gallery

                    if (data.photos && data.photos.length > 0) {
                        const galleryRow = document.createElement('div');
                        galleryRow.className = 'row';

                        data.photos.forEach(photo => {
                            const col = document.createElement('div');
                            col.className = 'col-md-3 text-center mb-3';
                            
                            const imagePath = photo.PhotoURL.replace('/public/', '');
                            const isMain = data.mainPhotoURL === photo.PhotoURL;

                            col.innerHTML = `
                                <div class="img-thumbnail position-relative ${isMain ? 'border-primary border-3' : ''}">
                                    <img src="${imagePath}" class="img-fluid" alt="Resort Photo">
                                    <div class="caption mt-2">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="?controller=admin&action=setResortMainPhoto&resortId=${data.resortId}&photoId=${photo.PhotoID}" class="btn btn-primary ${isMain ? 'disabled' : ''}" title="Set as Main Photo"><i class="fas fa-star"></i> Set as Main</a>
                                            <a href="?controller=admin&action=deleteResortPhoto&resortId=${data.resortId}&photoId=${photo.PhotoID}" class="btn btn-danger" onclick="return confirm('Are you sure?')" title="Delete Photo"><i class="fas fa-trash"></i> Delete</a>
                                        </div>
                                    </div>
                                </div>
                            `;
                            galleryRow.appendChild(col);
                        });
                        photoGallery.appendChild(galleryRow);
                    } else {
                        photoGallery.innerHTML = '<p class="text-muted">No photos have been uploaded for this resort yet.</p>';
                    }
                });
        });
    }

    // Delete Resort Modal Handler
    var deleteResortModal = document.getElementById('deleteResortModal');
    if(deleteResortModal) {
        deleteResortModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var resortId = button.getAttribute('data-resort-id');
            var confirmBtn = document.getElementById('confirmDeleteResortBtn');
            confirmBtn.href = '?controller=admin&action=destroyResort&id=' + resortId;
        });
    }

    // Add Facility Modal Handler
    var addFacilityModal = document.getElementById('addFacilityModal');
    if(addFacilityModal) {
        addFacilityModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var resortId = button.getAttribute('data-resort-id');
            document.getElementById('addFacilityResortId').value = resortId;
        });
    }

    // Edit Facility Modal Handler
    var editFacilityModal = document.getElementById('editFacilityModal');
    if(editFacilityModal) {
        editFacilityModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var facilityId = button.getAttribute('data-facility-id');
            
            fetch('?controller=admin&action=getFacilityJson&id=' + facilityId)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                        return;
                    }
                    
                    document.getElementById('editFacilityId').value = data.facilityId;
                    document.getElementById('editFacilityName').value = data.name;
                    document.getElementById('editFacilityRate').value = data.rate;
                    document.getElementById('editFacilityResortId').value = data.resortId; // Ensure this hidden field is populated
                    document.getElementById('editFacilityShortDescription').value = data.shortDescription || '';
                    document.getElementById('editFacilityFullDescription').value = data.fullDescription || '';

                    const photoGallery = document.getElementById('facilityPhotoGallery');
                    photoGallery.innerHTML = ''; // Clear previous gallery

                    if (data.photos && data.photos.length > 0) {
                        const galleryRow = document.createElement('div');
                        galleryRow.className = 'row';

                        data.photos.forEach(photo => {
                            const col = document.createElement('div');
                            col.className = 'col-md-3 text-center mb-3';
                            
                            const imagePath = photo.PhotoURL.replace('/public/', '');
                            const isMain = data.mainPhotoURL === photo.PhotoURL;

                            col.innerHTML = `
                                <div class="img-thumbnail position-relative ${isMain ? 'border-primary border-3' : ''}">
                                    <img src="${imagePath}" class="img-fluid" alt="Facility Photo">
                                    <div class="caption mt-2">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="?controller=admin&action=setFacilityMainPhoto&facilityId=${data.facilityId}&photoId=${photo.PhotoID}" class="btn btn-primary ${isMain ? 'disabled' : ''}" title="Set as Main Photo"><i class="fas fa-star"></i> Set as Main</a>
                                            <a href="?controller=admin&action=deleteFacilityPhoto&facilityId=${data.facilityId}&photoId=${photo.PhotoID}" class="btn btn-danger" onclick="return confirm('Are you sure?')" title="Delete Photo"><i class="fas fa-trash"></i> Delete</a>
                                        </div>
                                    </div>
                                </div>
                            `;
                            galleryRow.appendChild(col);
                        });
                        photoGallery.appendChild(galleryRow);
                    } else {
                        photoGallery.innerHTML = '<p class="text-muted">No photos have been uploaded for this facility yet.</p>';
                    }
                });
        });
    }

});

</script>
