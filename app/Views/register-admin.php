<?php
// Set the title for the layout
$title = 'Admin Registration - Resort Management';
$bodyClass = 'register-page admin-register-page';

// Define the content for the form section
$formContent = function() use ($mainAdminExists) { // Pass $mainAdminExists into the closure
    $formDisabled = $mainAdminExists ? 'disabled' : '';
    $oldInput = isset($_SESSION['old_input']) ? $_SESSION['old_input'] : [];
    unset($_SESSION['old_input']);
?>
    <h2 class="card-title text-center">Create an Admin Account</h2>

    <?php
        $errorMsg = '';
        if (isset($_SESSION['error_message']) && !empty($_SESSION['error_message'])) {
            $errorMsg = $_SESSION['error_message'];
            unset($_SESSION['error_message']);
        } elseif (isset($_GET['error'])) {
            switch ($_GET['error']) {
                case 'username_exists':
                    $errorMsg = 'Username already taken. Please choose another.';
                    break;
                case 'email_exists':
                    $errorMsg = 'An account with this email already exists.';
                    break;
                case 'password_mismatch':
                    $errorMsg = 'Passwords do not match. Please try again.';
                    break;
                default:
                    $errorMsg = 'Registration failed. Please try again.';
                    break;
            }
        }
        if (!empty($errorMsg)) {
            echo '<div class="alert alert-danger" role="alert">' . $errorMsg . '</div>';
        }

        if ($mainAdminExists) {
            echo '<div class="alert alert-warning text-center" role="alert"><strong>Registration Restricted:</strong> A Main Admin account already exists.</div>';
        }
    ?>
    
    <!-- Registration Form -->
    <form action="/ResortsSystem/public/index.php?action=registerAdmin" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
        <input type="hidden" name="role" value="Admin">
        <fieldset <?= $formDisabled ?>>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="firstName" class="form-label">First Name</label>
                    <input type="text" class="form-control" id="firstName" name="firstName" value="<?php echo htmlspecialchars($oldInput['firstName'] ?? ''); ?>" <?= $formDisabled ?>>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="lastName" class="form-label">Last Name</label>
                    <input type="text" class="form-control" id="lastName" name="lastName" value="<?php echo htmlspecialchars($oldInput['lastName'] ?? ''); ?>" <?= $formDisabled ?>>
                </div>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($oldInput['email'] ?? ''); ?>" required <?= $formDisabled ?>>
                <div class="invalid-feedback">Please provide a valid email.</div>
            </div>
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($oldInput['username'] ?? ''); ?>" required <?= $formDisabled ?>>
                <div class="invalid-feedback">Please choose a username.</div>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required <?= $formDisabled ?>>
                <div class="invalid-feedback">Password must be at least 8 characters long.</div>
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required <?= $formDisabled ?>>
                <div class="invalid-feedback">Passwords do not match.</div>
            </div>
            <div class="mt-1">
                <label for="phoneNumber" class="form-label">Phone Number</label>
                <input type="tel" class="form-control" id="phoneNumber" name="phoneNumber" value="<?php echo htmlspecialchars($oldInput['phoneNumber'] ?? ''); ?>" <?= $formDisabled ?>>
            </div>
            <div class="mt-1">
                <label for="socials" class="form-label">Socials</label>
                <textarea class="form-control" id="socials" name="socials" rows="2" required <?= $formDisabled ?>><?php echo htmlspecialchars($oldInput['socials'] ?? ''); ?></textarea>
                <div class="form-text">Enter social media links, one per line.</div>
            </div>
            <div class="mt-1">
                <label for="profileImage" class="form-label">Profile Image</label>
                <input class="form-control" type="file" id="profileImage" name="profileImage" accept="image/*" required <?= $formDisabled ?>>
            </div>
            <div class="d-grid mt-3">
                <button type="submit" class="btn btn-primary" <?= $formDisabled ?>>Register Admin</button>
                <div class="text-center mt-1"><a href="?action=loginAdmin">Go to admin login</a></div>
            </div>
        </fieldset>
    </form>
<?php
};

// Include the layout
include __DIR__ . '/partials/auth_layout.php';
?>
<script>
    // This script can also be moved to auth.js if it's generic enough
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.querySelector('.needs-validation');
        if (form.querySelector('[disabled]')) return; // Don't add listeners if form is disabled

        const username = document.getElementById('username');
        const email = document.getElementById('email');
        const password = document.getElementById('password');
        const confirm_password = document.getElementById('confirm_password');

        async function checkIfExists(field, value) {
            const response = await fetch('?controller=validation&action=checkUserExists', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ [field]: value })
            });
            const data = await response.json();
            return data.exists;
        }

        function validateField(field, validationRule, serverRule = null) {
            if (!validationRule(field.value)) {
                field.classList.add('is-invalid');
                field.classList.remove('is-valid');
                return;
            }

            if (serverRule) {
                serverRule(field.id, field.value).then(exists => {
                    if (exists) {
                        field.classList.add('is-invalid');
                        field.classList.remove('is-valid');
                        field.nextElementSibling.textContent = `${field.id.charAt(0).toUpperCase() + field.id.slice(1)} is already taken.`;
                    } else {
                        field.classList.add('is-valid');
                        field.classList.remove('is-invalid');
                    }
                });
            } else {
                field.classList.add('is-valid');
                field.classList.remove('is-invalid');
            }
        }

        username.addEventListener('input', () => validateField(username, value => value.trim().length > 0, checkIfExists));
        email.addEventListener('input', () => validateField(email, value => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value), checkIfExists));
        
        function validatePasswords() {
            validateField(password, value => value.length >= 8);
            validateField(confirm_password, value => value === password.value && value.length > 0);
        }

        password.addEventListener('input', validatePasswords);
        confirm_password.addEventListener('input', validatePasswords);

        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }

            validateField(username, value => value.trim().length > 0);
            validateField(email, value => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value));
            validatePasswords();

            form.classList.add('was-validated');
        }, false);
    });
</script>
