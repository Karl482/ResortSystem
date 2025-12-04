<?php
// Set the title for the layout
$title = 'Register - Resort Management';
$bodyClass = 'register-page';

// Define the content for the form section
$formContent = function() {
    $oldInput = isset($_SESSION['old_input']) ? $_SESSION['old_input'] : [];
    unset($_SESSION['old_input']);
?>
    <h2 class="card-title text-center">Create an Account</h2>

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
            echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . $errorMsg . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
        }
    ?>
    
    <!-- Registration Form -->
    <form action="?action=register" method="POST" class="needs-validation" novalidate>
        <input type="hidden" name="role" value="Customer">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="firstName" class="form-label">First Name</label>
                <input type="text" class="form-control" id="firstName" name="firstName" value="<?php echo htmlspecialchars($oldInput['firstName'] ?? ''); ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label for="lastName" class="form-label">Last Name</label>
                <input type="text" class="form-control" id="lastName" name="lastName" value="<?php echo htmlspecialchars($oldInput['lastName'] ?? ''); ?>">
            </div>
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">Email Address</label>
            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($oldInput['email'] ?? ''); ?>" required>
            <div class="invalid-feedback">Please provide a valid email.</div>
            <small class="form-text text-white">Gmail accounts require a quick verification via email.</small>
        </div>
        <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($oldInput['username'] ?? ''); ?>" required>
            <div class="invalid-feedback">Please choose a username.</div>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" id="password" name="password" required>
            <div class="invalid-feedback">Password must be at least 8 characters long.</div>
        </div>
        <div class="mb-3">
            <label for="confirm_password" class="form-label">Confirm Password</label>
            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
            <div class="invalid-feedback">Passwords do not match.</div>
        </div>
        <div class="mb-3">
            <label for="phoneNumber" class="form-label">Phone Number</label>
            <input type="tel" class="form-control" id="phoneNumber" name="phoneNumber" value="<?php echo htmlspecialchars($oldInput['phoneNumber'] ?? ''); ?>">
        </div>
        <div class="d-grid mt-4">
            <button type="submit" class="btn btn-primary" id="registerSubmitBtn">
                <span class="btn-text">Register</span>
                <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
            </button>
        </div>
        <div class="alert alert-danger d-none mt-3" id="gmailInlineAlert"></div>
    </form>
    <div class="mt-3 text-center">
        <p class="mt-2">Already have an account? <a href="?action=login">Login here</a></p>
        <p class="mt-2">Or, <a href="?">continue as a guest</a> to preview the site.</p>
    </div>
<?php
};

// Include the layout
include __DIR__ . '/partials/auth_layout.php';
?>
<div class="modal fade" id="gmailVerificationModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Verify your Gmail</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>We sent a 6-digit code to <strong id="gmailEmailDisplay"></strong>. Enter it below to finish registering.</p>
                <div class="mb-3">
                    <label for="gmailVerificationCode" class="form-label">Verification Code</label>
                    <input type="text" class="form-control" id="gmailVerificationCode" maxlength="6" inputmode="numeric" autocomplete="one-time-code" placeholder="Enter code">
                </div>
                <div class="alert alert-warning d-none" id="gmailVerificationAlert"></div>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-link" id="resendGmailCode">
                    <span class="btn-text">Resend code</span>
                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                </button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="submitVerificationCode">
                        <span class="btn-text">Confirm</span>
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
    #registerSubmitBtn .spinner-border,
    #submitVerificationCode .spinner-border,
    #resendGmailCode .spinner-border {
        margin-left: 0.5rem;
        vertical-align: middle;
    }
    #registerSubmitBtn .btn-text,
    #submitVerificationCode .btn-text,
    #resendGmailCode .btn-text {
        display: inline-block;
    }
</style>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const username = document.getElementById('username');
        const email = document.getElementById('email');
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');
        const form = document.querySelector('.needs-validation');
        const inlineAlert = document.getElementById('gmailInlineAlert');

        const modalEl = document.getElementById('gmailVerificationModal');
        const bootstrapAvailable = typeof bootstrap !== 'undefined';
        const verificationModal = modalEl && bootstrapAvailable ? new bootstrap.Modal(modalEl) : null;
        const verificationCodeInput = document.getElementById('gmailVerificationCode');
        const verificationAlert = document.getElementById('gmailVerificationAlert');
        const gmailEmailDisplay = document.getElementById('gmailEmailDisplay');
        const resendButton = document.getElementById('resendGmailCode');
        const confirmCodeButton = document.getElementById('submitVerificationCode');
        const registerSubmitBtn = document.getElementById('registerSubmitBtn');
        let pendingEmail = '';
        let gmailRequestInFlight = false;

        function setButtonLoading(button, isLoading, loadingText = null) {
            if (!button) return;
            const btnText = button.querySelector('.btn-text');
            const spinner = button.querySelector('.spinner-border');
            
            if (isLoading) {
                button.disabled = true;
                if (spinner) spinner.classList.remove('d-none');
                if (btnText && loadingText) {
                    btnText.textContent = loadingText;
                } else if (btnText) {
                    btnText.style.opacity = '0.7';
                }
            } else {
                button.disabled = false;
                if (spinner) spinner.classList.add('d-none');
                if (btnText) {
                    if (button.id === 'registerSubmitBtn') {
                        btnText.textContent = 'Register';
                    } else if (button.id === 'resendGmailCode') {
                        btnText.textContent = 'Resend code';
                    } else if (button.id === 'submitVerificationCode') {
                        btnText.textContent = 'Confirm';
                    }
                    btnText.style.opacity = '1';
                }
            }
        }

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

        function validatePasswords() {
            validateField(password, value => value.length >= 8);
            validateField(confirmPassword, value => value === password.value && value.length > 0);
        }

        username.addEventListener('input', () => validateField(username, value => value.trim().length > 0, checkIfExists));
        email.addEventListener('input', () => validateField(email, value => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value), checkIfExists));
        password.addEventListener('input', validatePasswords);
        confirmPassword.addEventListener('input', validatePasswords);

        function isGmailAddress(value) {
            return value && value.toLowerCase().endsWith('@gmail.com');
        }

        function setInlineAlert(message, variant = 'danger') {
            if (!inlineAlert) return;
            if (!message) {
                inlineAlert.classList.add('d-none');
                inlineAlert.textContent = '';
                return;
            }
            inlineAlert.className = 'alert alert-' + variant + ' mt-3';
            inlineAlert.textContent = message;
            inlineAlert.classList.remove('d-none');
        }

        function setModalAlert(message, variant = 'danger') {
            if (!verificationAlert) return;
            if (!message) {
                verificationAlert.classList.add('d-none');
                verificationAlert.textContent = '';
                return;
            }
            verificationAlert.className = 'alert alert-' + variant;
            verificationAlert.textContent = message;
            verificationAlert.classList.remove('d-none');
        }

        function toggleModalButtons(disabled) {
            setButtonLoading(resendButton, disabled, disabled ? 'Sending...' : null);
            setButtonLoading(confirmCodeButton, disabled, disabled ? 'Verifying...' : null);
        }

        async function startGmailVerification(showInlineFeedback = true) {
            if (!verificationModal || gmailRequestInFlight) {
                return;
            }
            gmailRequestInFlight = true;
            setButtonLoading(registerSubmitBtn, true, 'Sending verification code...');
            toggleModalButtons(true);
            setModalAlert('');
            if (showInlineFeedback) {
                setInlineAlert('');
            }

            const formData = new FormData(form);

            try {
                const response = await fetch('?action=initiateGmailVerification', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                if (data.success) {
                    pendingEmail = data.email;
                    if (gmailEmailDisplay) {
                        gmailEmailDisplay.textContent = pendingEmail;
                    }
                    if (verificationCodeInput) {
                        verificationCodeInput.value = '';
                        verificationCodeInput.focus();
                    }
                    setModalAlert('Enter the code we sent to your Gmail inbox.', 'info');
                    if (showInlineFeedback) {
                        setInlineAlert('A verification code was sent to your Gmail inbox. Enter it to complete registration.', 'success');
                    }
                    verificationModal.show();
                } else {
                    setInlineAlert(data.message || 'Unable to send the verification code. Please try again.');
                }
            } catch (error) {
                setInlineAlert('Network error. Please try again.');
            } finally {
                gmailRequestInFlight = false;
                setButtonLoading(registerSubmitBtn, false);
                toggleModalButtons(false);
            }
        }

        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }

            validateField(username, value => value.trim().length > 0);
            validateField(email, value => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value));
            validatePasswords();

            if (form.checkValidity() && verificationModal && isGmailAddress(email.value)) {
                event.preventDefault();
                event.stopPropagation();
                startGmailVerification(true);
            } else if (form.checkValidity() && !isGmailAddress(email.value)) {
                // For non-Gmail addresses, show loading on the submit button
                setButtonLoading(registerSubmitBtn, true, 'Creating account...');
            }

            form.classList.add('was-validated');
        }, false);

        if (confirmCodeButton) {
            confirmCodeButton.addEventListener('click', async () => {
                if (!pendingEmail) {
                    setModalAlert('Please request a verification code first.');
                    return;
                }

                const code = verificationCodeInput ? verificationCodeInput.value.trim() : '';
                if (!/^\d{6}$/.test(code)) {
                    setModalAlert('Enter the 6-digit code from your email.');
                    return;
                }

                toggleModalButtons(true);
                setModalAlert('');

                try {
                    const response = await fetch('?action=completeGmailRegistration', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({ email: pendingEmail, code }).toString()
                    });
                    const data = await response.json();
                    if (data.success) {
                        verificationModal.hide();
                        window.location.href = data.redirect || '?action=login&registration=success';
                        return;
                    }
                    setModalAlert(data.message || 'Verification failed.');
                    if (data.message && data.message.toLowerCase().includes('expired')) {
                        setInlineAlert(data.message);
                    }
                } catch (error) {
                    setModalAlert('Network error. Please try again.');
                } finally {
                    toggleModalButtons(false);
                }
            });
        }

        if (resendButton) {
            resendButton.addEventListener('click', () => {
                setButtonLoading(resendButton, true, 'Resending...');
                startGmailVerification(false);
            });
        }
    });
</script>
