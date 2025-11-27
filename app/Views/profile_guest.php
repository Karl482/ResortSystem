<?php
$pageTitle = "My Profile";
require_once __DIR__ . '/partials/guest_header.php';
?>

<div class="row">
    <div class="col-12 mb-4">
        <h1><?= htmlspecialchars($pageTitle) ?></h1>
    </div>

    <div class="col-12">
        <div class="alert alert-info d-flex align-items-center mb-4">
            <i class="fas fa-info-circle me-3 fa-lg"></i>
            <div>
                Please <a href="?action=login" class="alert-link">login</a> or <a href="?action=showRegisterForm" class="alert-link">register</a> to view and manage your profile.
            </div>
        </div>

        <form>
            <fieldset disabled>
                <div class="row g-4">
                    <!-- Left Column: Profile Preview & Account Info -->
                    <div class="col-12 col-lg-4">
                        <!-- Profile Preview Card -->
                        <div class="card mb-4">
                            <div class="card-body text-center">
                                <div class="rounded-circle bg-secondary d-inline-flex align-items-center justify-content-center mb-3" style="width: 140px; height: 140px;">
                                    <i class="fas fa-user fa-4x text-white"></i>
                                </div>
                                <h5 class="text-muted mb-1">Guest User</h5>
                                <p class="text-muted mb-0"><small>Please login to see your profile</small></p>
                            </div>
                        </div>
                        
                        <!-- Account Info Card -->
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="fas fa-user-circle me-2"></i>Account Information</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="username" class="form-label small">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" placeholder="Your Username">
                                </div>
                                <div class="mb-0">
                                    <label for="email" class="form-label small">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" placeholder="your.email@example.com">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Right Column: Personal Info & Security -->
                    <div class="col-12 col-lg-8">
                        <!-- Personal Information Card -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="fas fa-id-card me-2"></i>Personal Information</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <label for="firstName" class="form-label small">First Name</label>
                                        <input type="text" class="form-control" id="firstName" name="firstName" placeholder="First Name">
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label for="lastName" class="form-label small">Last Name</label>
                                        <input type="text" class="form-control" id="lastName" name="lastName" placeholder="Last Name">
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label for="phoneNumber" class="form-label small">Phone Number</label>
                                        <input type="text" class="form-control" id="phoneNumber" name="phoneNumber" placeholder="Phone Number">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Security Card -->
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="fas fa-lock me-2"></i>Security</h6>
                            </div>
                            <div class="card-body">
                                <p class="text-muted small mb-3">Password settings are available after login</p>
                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <label for="password" class="form-label small">New Password</label>
                                        <input type="password" class="form-control" id="password" name="password" placeholder="••••••••">
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label for="confirm_password" class="form-label small">Confirm New Password</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="••••••••">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </fieldset>
            
            <!-- Action Buttons -->
            <div class="d-flex flex-wrap gap-2 mt-4 pt-3 border-top">
                <a href="?action=login" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt me-2"></i>Login
                </a>
                <a href="?action=showRegisterForm" class="btn btn-outline-primary">
                    <i class="fas fa-user-plus me-2"></i>Register
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>