<?php
// This file will contain all the modals for user management.
// It will be included in the main users.php view.
?>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addUserModalLabel">Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addUserForm" action="?controller=admin&action=addUser" method="POST" class="needs-validation" novalidate>
                    <div style="max-height: 78vh; overflow-y: auto; padding-right: 15px;">
                        <div class="mb-3">
                            <label for="add-username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="add-username" name="username" required>
                            <div class="invalid-feedback">Please choose a username.</div>
                        </div>
                        <div class="mb-3">
                            <label for="add-email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="add-email" name="email" required>
                            <div class="invalid-feedback">Please provide a valid email.</div>
                        </div>
                        <div class="mb-3">
                            <label for="add-password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="add-password" name="password" required>
                            <div class="invalid-feedback">Password must be at least 8 characters long.</div>
                        </div>
                        <div class="mb-3">
                            <label for="add-confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="add-confirm_password" name="confirm_password" required>
                            <div class="invalid-feedback">Passwords do not match.</div>
                        </div>
                        <div class="mb-3">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-select" id="add-role" name="role" onchange="toggleAddAdminTypeField()">
                                <option value="Customer">Customer</option>
                                <option value="Staff">Staff</option>
                                <?php if (User::isMainAdmin($_SESSION['user_id'])): ?>
                                <option value="Admin">Admin</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="mb-3" id="add-adminType-container" style="display: none;">
                            <label for="adminType" class="form-label">Admin Type</label>
                            <select class="form-select" id="add-adminType" name="adminType">
                                <option value="BookingAdmin">Reservations Manager</option>
                                <option value="OperationsAdmin">Resort Manager</option>
                                <option value="ReportsAdmin">Finance Manager</option>
                            </select>
                            <small class="form-text text-muted">Select the type of admin permissions for this user.</small>
                        </div>
                        <div class="mb-3">
                            <label for="firstName" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="add-firstName" name="firstName">
                        </div>
                        <div class="mb-3">
                            <label for="lastName" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="add-lastName" name="lastName">
                        </div>
                        <div class="mb-3">
                            <label for="phoneNumber" class="form-label">Phone Number</label>
                            <input type="text" class="form-control" id="add-phoneNumber" name="phoneNumber">
                        </div>
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="add-notes" name="notes" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editUserForm" action="" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <div id="edit-profile-image-container" class="text-center mb-3" style="display: none;">
                        <img id="edit-profile-image-preview" src="" alt="Profile Image" class="img-thumbnail rounded-circle" style="width: 150px; height: 150px; object-fit: cover;">
                    </div>
                    <div class="mb-3" id="profile-image-upload-container" style="display: none;">
                        <label for="edit-profileImage" class="form-label">Update Profile Image</label>
                        <input class="form-control" type="file" id="edit-profileImage" name="profileImage" accept="image/*">
                    </div>
                    <div class="mb-3">
                        <label for="edit-username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="edit-username" name="username" required>
                        <div class="invalid-feedback">Please choose a username.</div>
                    </div>
                    <div class="mb-3">
                        <label for="edit-email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="edit-email" name="email" required>
                        <div class="invalid-feedback">Please provide a valid email.</div>
                    </div>
                    <div class="mb-3">
                        <label for="firstName" class="form-label">First Name</label>
                        <input type="text" class="form-control" id="edit-firstName" name="firstName">
                    </div>
                    <div class="mb-3">
                        <label for="lastName" class="form-label">Last Name</label>
                        <input type="text" class="form-control" id="edit-lastName" name="lastName">
                    </div>
                    <div class="mb-3">
                        <label for="phoneNumber" class="form-label">Phone Number</label>
                        <input type="text" class="form-control" id="edit-phoneNumber" name="phoneNumber">
                    </div>
                    <div class="mb-3">
                        <label for="edit-socials" class="form-label">Socials</label>
                        <textarea class="form-control" id="edit-socials" name="socials" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="edit-notes" name="notes" rows="3"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Delete User Modal -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteUserModalLabel">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this user?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a id="deleteUserConfirmBtn" href="#" class="btn btn-danger">Delete</a>
            </div>
        </div>
    </div>
</div>

<!-- View User Bookings Modal -->
<div class="modal fade" id="viewUserBookingsModal" tabindex="-1" aria-labelledby="viewUserBookingsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewUserBookingsModalLabel">User Bookings</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- User bookings will be loaded here via AJAX -->
            </div>
        </div>
    </div>
</div>

<!-- Assigned Resorts Modal -->
<div class="modal fade" id="assignedResortsModal" tabindex="-1" aria-labelledby="assignedResortsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="assignedResortsModalLabel">Assign Resorts</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="assignResortsForm" method="POST">
                    <input type="hidden" name="userId" value="">
                    <div id="resorts-checkbox-container">
                        <!-- Checkboxes will be loaded here by JavaScript -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Assignments</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>