<?php
// Prevent direct access to this file
if (!defined('APP_LOADED')) {
    http_response_code(403);
    require_once __DIR__ . '/../../errors/403.php';
    exit;
}
?>

<!-- Add Resort Modal -->
<div class="modal fade" id="addResortModal" tabindex="-1" aria-labelledby="addResortModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addResortModalLabel">Add New Resort</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="?controller=admin&action=storeResort" method="POST" id="addResortForm" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="name" class="form-label">Resort Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <input type="text" class="form-control" id="address" name="address" required>
                    </div>
                    <div class="mb-3">
                        <label for="contactPerson" class="form-label">Contact Person</label>
                        <input type="text" class="form-control" id="contactPerson" name="contactPerson" required>
                    </div>
                    <div class="mb-3">
                        <label for="shortDescription" class="form-label">Short Description</label>
                        <textarea class="form-control" id="shortDescription" name="shortDescription" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="fullDescription" class="form-label">Full Description</label>
                        <textarea class="form-control" id="fullDescription" name="fullDescription" rows="4"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="googleMapsLink" class="form-label">Google Maps Link</label>
                        <input type="url" class="form-control" id="googleMapsLink" name="googleMapsLink" placeholder="https://www.google.com/maps/embed?...">
                    </div>
                    <div class="mb-3">
                        <label for="photos" class="form-label">Upload Photos</label>
                        <input type="file" class="form-control" id="photos" name="photos[]" multiple>
                        <small class="form-text text-muted">You can select multiple files. The first photo uploaded will be set as the main photo.</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary" form="addResortForm">Add Resort</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Resort Modal -->
<div class="modal fade" id="editResortModal" tabindex="-1" aria-labelledby="editResortModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editResortModalLabel">Edit Resort</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="?controller=admin&action=updateResort" method="POST" id="editResortForm" enctype="multipart/form-data">
                    <input type="hidden" id="editResortId" name="resortId">
                    <div class="mb-3">
                        <label for="editName" class="form-label">Resort Name</label>
                        <input type="text" class="form-control" id="editName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="editAddress" class="form-label">Address</label>
                        <input type="text" class="form-control" id="editAddress" name="address" required>
                    </div>
                    <div class="mb-3">
                        <label for="editContactPerson" class="form-label">Contact Person</label>
                        <input type="text" class="form-control" id="editContactPerson" name="contactPerson" required>
                    </div>
                    <div class="mb-3">
                        <label for="editShortDescription" class="form-label">Short Description</label>
                        <textarea class="form-control" id="editShortDescription" name="shortDescription" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="editFullDescription" class="form-label">Full Description</label>
                        <textarea class="form-control" id="editFullDescription" name="fullDescription" rows="4"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="editGoogleMapsLink" class="form-label">Google Maps Link</label>
                        <input type="url" class="form-control" id="editGoogleMapsLink" name="googleMapsLink" placeholder="https://www.google.com/maps/embed?...">
                    </div>
                    <hr>
                    <h5>Manage Photos</h5>
                    <div id="photoGallery" class="mb-3">
                        <!-- Photo gallery will be populated by JavaScript -->
                    </div>
                     <div class="mb-3">
                        <label for="newPhotos" class="form-label">Upload New Photos</label>
                        <input type="file" class="form-control" id="newPhotos" name="photos[]" multiple>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary" form="editResortForm">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Resort Modal -->
<div class="modal fade" id="deleteResortModal" tabindex="-1" aria-labelledby="deleteResortModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteResortModalLabel">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this resort? This action cannot be undone.
                <br><br>
                <strong class="text-danger">Note:</strong> This will fail if the resort has any associated facilities.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a id="confirmDeleteResortBtn" href="#" class="btn btn-danger">Delete</a>
            </div>
        </div>
    </div>
</div>
<!-- Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="successModalLabel">Resort Created Successfully</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>To make this resort available for booking, the next step is to configure its pricing.</p>
                <div class="alert alert-warning">
                    <strong>Note:</strong> Customers will not be able to book this resort until its pricing has been set up.
                </div>
                <p>Do you want to proceed to pricing management now?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Later</button>
                <a id="proceedToPricingBtn" href="#" class="btn btn-primary">Proceed to Pricing</a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const addResortForm = document.getElementById('addResortForm');
    const addResortModal = new bootstrap.Modal(document.getElementById('addResortModal'));
    const successModal = new bootstrap.Modal(document.getElementById('successModal'));

    addResortForm.addEventListener('submit', function (event) {
        event.preventDefault();
        const formData = new FormData(addResortForm);

        fetch('?controller=admin&action=storeResort', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                addResortModal.hide();
                const proceedBtn = document.getElementById('proceedToPricingBtn');
                proceedBtn.href = `?controller=admin&action=pricingManagement&resort_id=${data.resortId}`;
                successModal.show();
                
                // Optional: Reload the page when the success modal is closed to show the new resort in the list
                document.getElementById('successModal').addEventListener('hidden.bs.modal', function () {
                    window.location.reload();
                });
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An unexpected error occurred. Please try again.');
        });
    });

    const editResortModal = document.getElementById('editResortModal');
    if(editResortModal) {
        editResortModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const resortId = button.getAttribute('data-resort-id');
            const form = editResortModal.querySelector('#editResortForm');
            
            // Clear previous data
            form.reset();
            document.getElementById('photoGallery').innerHTML = '';

            if (resortId) {
                fetch(`?controller=resort&action=getResortJson&id=${resortId}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if(data.error) {
                            alert(data.error);
                        } else {
                            form.querySelector('#editResortId').value = data.resortId;
                            form.querySelector('#editName').value = data.name;
                            form.querySelector('#editAddress').value = data.address;
                            form.querySelector('#editContactPerson').value = data.contactPerson;
                            form.querySelector('#editShortDescription').value = data.shortDescription || '';
                            form.querySelector('#editFullDescription').value = data.fullDescription || '';
                            form.querySelector('#editGoogleMapsLink').value = data.googleMapsLink || '';

                            // Populate photo gallery
                            const photoGallery = document.getElementById('photoGallery');
                            if(data.photos && data.photos.length > 0) {
                                let photosHtml = '<div class="row">';
                                data.photos.forEach(photo => {
                                    photosHtml += `
                                        <div class="col-md-4 mb-3">
                                            <div class="card">
                                                <img src="${window.BASE_URL}/${photo.PhotoURL}" class="card-img-top" alt="Resort Photo">
                                                <div class="card-body text-center">
                                                    <a href="?controller=admin&action=deleteResortPhoto&photo_id=${photo.PhotoID}" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                                                    ${photo.PhotoURL !== data.mainPhotoURL ? `<a href="?controller=admin&action=setMainResortPhoto&resort_id=${data.resortId}&photo_id=${photo.PhotoID}" class="btn btn-sm btn-secondary">Set as Main</a>` : '<span class="badge bg-success">Main Photo</span>'}
                                                </div>
                                            </div>
                                        </div>
                                    `;
                                });
                                photosHtml += '</div>';
                                photoGallery.innerHTML = photosHtml;
                            } else {
                                photoGallery.innerHTML = '<p>No photos uploaded for this resort.</p>';
                            }
                        }
                    })
                    .catch(err => {
                        console.error('Error loading resort data:', err);
                        alert('Failed to load resort data. Please try again.');
                    });
            }
        });
    }

    const deleteResortModal = document.getElementById('deleteResortModal');
    if(deleteResortModal){
        deleteResortModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const deleteUrl = button.getAttribute('data-delete-url');
            const confirmBtn = document.getElementById('confirmDeleteResortBtn');
            confirmBtn.setAttribute('href', deleteUrl);
        });
    }

});
</script>
