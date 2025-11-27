<!-- Add Facility Modal -->
<div class="modal fade" id="addFacilityModal" tabindex="-1" aria-labelledby="addFacilityModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addFacilityModalLabel">Add New Facility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="?controller=admin&action=addFacility" method="POST" id="addFacilityForm" enctype="multipart/form-data">
                    <input type="hidden" name="resort_id" id="addFacilityResortId">
                    <div class="mb-3">
                        <label for="name" class="form-label">Facility Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="rate" class="form-label">Price per Booking (₱)</label>
                        <input type="number" step="0.01" class="form-control" id="rate" name="rate" required>
                    </div>
                    <div class="mb-3">
                        <label for="shortDescription" class="form-label">Short Description</label>
                        <textarea class="form-control" id="shortDescription" name="short_description" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="fullDescription" class="form-label">Full Description</label>
                        <textarea class="form-control" id="fullDescription" name="description" rows="4"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="facilityPhotos" class="form-label">Upload Photos</label>
                        <input type="file" class="form-control" id="facilityPhotos" name="photos[]" multiple>
                        <small class="form-text text-muted">You can select multiple files. The first photo uploaded will be set as the main photo.</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary" form="addFacilityForm">Save Facility</button>
            </div>
        </div>
    </div>
</div>
<!-- Manage Payment Methods Modal -->
<div class="modal fade" id="managePaymentsModal" tabindex="-1" aria-labelledby="managePaymentsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="managePaymentsModalLabel">Manage Payment Methods for <span id="resortNameLabel"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6>Existing Payment Methods</h6>
                <div id="paymentMethodsList" class="mb-3"></div>
                <hr>
                <h6>Add New Payment Method</h6>
                <form id="addPaymentMethodForm" action="?controller=admin&action=addPaymentMethod" method="POST" enctype="application/x-www-form-urlencoded">
                    <input type="hidden" id="paymentResortId" name="resort_id" value="">
                    <div class="mb-3">
                        <label for="methodName" class="form-label">Method Name (e.g., GCash, BPI)</label>
                        <input type="text" class="form-control" id="methodName" name="method_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="methodDetails" class="form-label">Details (e.g., Account Number, QR Code instructions)</label>
                        <textarea class="form-control" id="methodDetails" name="method_details" rows="3" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Method</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Delete Facility Modal -->
<div class="modal fade" id="deleteFacilityModal" tabindex="-1" aria-labelledby="deleteFacilityModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteFacilityModalLabel">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this facility? This action cannot be undone.
                <br><br>
                <strong class="text-danger">Note:</strong> This will fail if the facility is part of any existing bookings.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="confirmDeleteFacilityBtn" class="btn btn-danger">Delete</a>
            </div>
        </div>
    </div>
</div>
