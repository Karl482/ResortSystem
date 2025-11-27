<!-- Edit Facility Modal -->
<div class="modal fade" id="editFacilityModal" tabindex="-1" aria-labelledby="editFacilityModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editFacilityModalLabel">Edit Facility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="?controller=admin&action=editFacility" method="POST" id="editFacilityForm" enctype="multipart/form-data">
                    <input type="hidden" id="editFacilityId" name="facilityId">
                    <input type="hidden" id="editFacilityResortId" name="resort_id">
                    <div class="mb-3">
                        <label for="editFacilityName" class="form-label">Facility Name</label>
                        <input type="text" class="form-control" id="editFacilityName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="editFacilityRate" class="form-label">Price per Booking (₱)</label>
                        <input type="number" step="0.01" class="form-control" id="editFacilityRate" name="rate" required>
                    </div>
                    <div class="mb-3">
                        <label for="editFacilityShortDescription" class="form-label">Short Description</label>
                        <textarea class="form-control" id="editFacilityShortDescription" name="short_description" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="editFacilityFullDescription" class="form-label">Full Description</label>
                        <textarea class="form-control" id="editFacilityFullDescription" name="full_description" rows="4"></textarea>
                    </div>
                    <hr>
                    <h5>Manage Photos</h5>
                    <div id="facilityPhotoGallery" class="mb-3">
                        <!-- Photo gallery will be populated by JavaScript -->
                    </div>
                    <div class="mb-3">
                        <label for="newFacilityPhotos" class="form-label">Upload New Photos</label>
                        <input type="file" class="form-control" id="newFacilityPhotos" name="photos[]" multiple>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary" form="editFacilityForm">Save Changes</button>
            </div>
        </div>
    </div>
</div>