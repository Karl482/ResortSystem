<?php
// Prevent direct access to this file
if (!defined('APP_LOADED')) {
    http_response_code(403);
    require_once __DIR__ . '/../../errors/403.php';
    exit;
}

$pageTitle = "Manage Facilities";
require_once __DIR__ . '/../../partials/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Manage Facilities</h3>
                    <button type="button" class="btn btn-primary float-end" data-bs-toggle="modal" data-bs-target="#addFacilityModal">
                        Add New Facility
                    </button>
                </div>
                <div class="card-body">
                    <?php if (isset($_GET['status']) && $_GET['status'] == 'facility_added'): ?>
                        <div class="alert alert-success">Facility added successfully!</div>
                    <?php elseif (isset($_GET['status']) && $_GET['status'] == 'facility_updated'): ?>
                        <div class="alert alert-success">Facility updated successfully!</div>
                    <?php elseif (isset($_GET['status']) && $_GET['status'] == 'facility_deleted'): ?>
                        <div class="alert alert-success">Facility deleted successfully!</div>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-primary">
                                <tr>
                                    <th>ID</th>
                                    <th>Photo</th>
                                    <th>Name</th>
                                    <th>Resort</th>
                                    <th>Capacity</th>
                                    <th>Rate</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($facilities)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No facilities found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($facilities as $facility): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($facility->FacilityID) ?></td>
                                            <td>
                                               <?php if (!empty($facility->MainPhotoURL)): ?>
                                                   <img src="<?= BASE_URL . '/' . htmlspecialchars($facility->MainPhotoURL) ?>" alt="<?= htmlspecialchars($facility->Name) ?>" style="width: 100px; height: auto;">
                                               <?php else: ?>
                                                   No Photo
                                               <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($facility->Name) ?></td>
                                            <td><?= htmlspecialchars($facility->ResortName) ?></td>
                                            <td><?= htmlspecialchars($facility->Capacity) ?></td>
                                            <td><?= htmlspecialchars(number_format($facility->Rate, 2)) ?></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-info schedule-btn" data-bs-toggle="modal" data-bs-target="#scheduleModal" data-facility-id="<?= $facility->FacilityID ?>">
                                                    Manage Schedule
                                                </button>
                                                <button type="button" class="btn btn-sm btn-warning edit-btn" data-bs-toggle="modal" data-bs-target="#editFacilityModal" data-facility-id="<?= $facility->FacilityID ?>">
                                                    Edit
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger delete-btn" data-bs-toggle="modal" data-bs-target="#deleteFacilityModal" data-facility-id="<?= $facility->FacilityID ?>" data-delete-url="?controller=admin&action=deleteFacility&id=<?= $facility->FacilityID ?>">
                                                    Delete
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/facility_modals.php'; ?>
<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const scheduleModal = document.getElementById('scheduleModal');
    if(scheduleModal) {
        scheduleModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const facilityId = button.getAttribute('data-facility-id');
            const modalBody = scheduleModal.querySelector('.modal-body');
            const modalTitle = scheduleModal.querySelector('.modal-title');

            modalBody.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>';
            modalTitle.textContent = 'Manage Schedule';

            fetch(`?controller=admin&action=getScheduleView&id=${facilityId}`)
                .then(response => response.text())
                .then(html => {
                    modalBody.innerHTML = html;
                    const facilityName = scheduleModal.querySelector('h4').textContent;
                    modalTitle.textContent = facilityName;
                })
                .catch(err => {
                    modalBody.innerHTML = '<div class="alert alert-danger">Failed to load schedule. Please try again.</div>';
                    console.error('Error loading schedule:', err);
                });
        });
    }

    const editFacilityModal = document.getElementById('editFacilityModal');
    if(editFacilityModal) {
        editFacilityModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const facilityId = button.getAttribute('data-facility-id');
            const modalBody = editFacilityModal.querySelector('.modal-body');
            const modalTitle = editFacilityModal.querySelector('.modal-title');

            modalBody.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>';
            modalTitle.textContent = 'Edit Facility';
            
            fetch(`?controller=admin&action=getFacilityEditForm&id=${facilityId}`)
                .then(response => response.text())
                .then(html => {
                    modalBody.innerHTML = html;
                     const facilityName = modalBody.querySelector('input[name="name"]').value;
                    modalTitle.textContent = `Edit: ${facilityName}`;
                })
                .catch(err => {
                    modalBody.innerHTML = '<div class="alert alert-danger">Failed to load facility data. Please try again.</div>';
                    console.error('Error loading facility data:', err);
                });
        });
    }

    const deleteFacilityModal = document.getElementById('deleteFacilityModal');
    if(deleteFacilityModal){
        deleteFacilityModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const deleteUrl = button.getAttribute('data-delete-url');
            const confirmBtn = document.getElementById('confirmDeleteBtn');
            confirmBtn.setAttribute('href', deleteUrl);
        });
    }
});
</script>