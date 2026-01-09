<?php
session_start();

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: Dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="css/auth-styles.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <title>Forgot Password - Echinacea Pharmacy</title>
    <style>
        .step-container {
            display: none;
        }
        .step-container.active {
            display: block;
        }
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }
        .step {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #ddd;
            color: #666;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin: 0 5px;
            transition: all 0.3s ease;
        }
        .step.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .step.completed {
            background: #48bb78;
            color: white;
        }
        .step-line {
            width: 40px;
            height: 2px;
            background: #ddd;
            margin: 0 5px;
        }
        .step-line.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .back-link {
            position: absolute;
            top: 20px;
            left: 20px;
            color: #000000ff;
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .back-link:hover {
            color: #220c9eff;
        }
        .resend-otp {
            background: none;
            border: none;
            color: #667eea;
            text-decoration: underline;
            cursor: pointer;
            font-size: 14px;
            margin-top: 10px;
        }
        .resend-otp:hover {
            color: #764ba2;
        }
        .resend-otp:disabled {
            color: #ccc;
            cursor: not-allowed;
        }
        .countdown {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <a href="index.php" class="back-link">
        <i class="fas fa-arrow-left"></i> Back to Login
    </a>

    <div class="container">
        <div class="form-container">
            <!-- Step 1: Email Input -->
            <div id="step1" class="step-container active">
                <div class="pharmacy-logo">
                    <img src="Pharmacy Icons/echinacea Logo.png" alt="Echinacea Logo" style="width: 80px; height: 80px;">
                </div>
                <h2>Forgot Password</h2>
                <p class="subtitle">Enter your email address to reset your password</p>

                <form id="email-form">
                    <div class="input-group">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" placeholder="Email Address" required>
                    </div>

                    <button type="submit" class="btn">Send OTP</button>
                </form>
            </div>

            <!-- Step 2: OTP Verification -->
            <div id="step2" class="step-container">
                <div class="pharmacy-logo">
                    <img src="Pharmacy Icons/echinacea Logo.png" alt="Echinacea Logo" style="width: 80px; height: 80px;">
                </div>
                <h2>Verify OTP</h2>
                <p class="subtitle">Enter the 6-digit code sent to your email</p>

                <div class="step-indicator">
                    <div class="step completed">1</div>
                    <div class="step-line active"></div>
                    <div class="step active">2</div>
                    <div class="step-line"></div>
                    <div class="step">3</div>
                </div>

                <form id="otp-form">
                    <div class="input-group">
                        <i class="fas fa-key"></i>
                        <input type="text" name="otp" placeholder="Enter 6-digit OTP" maxlength="6" required>
                    </div>

                    <button type="submit" class="btn">Verify OTP</button>

                    <button type="button" class="resend-otp" id="resend-btn">Resend OTP</button>
                    <div class="countdown" id="countdown"></div>
                </form>
            </div>

            <!-- Step 3: New Password -->
            <div id="step3" class="step-container">
                <div class="pharmacy-logo">
                    <img src="Pharmacy Icons/echinacea Logo.png" alt="Echinacea Logo" style="width: 80px; height: 80px;">
                </div>
                <h2>Reset Password</h2>
                <p class="subtitle">Enter your new password</p>

                <div class="step-indicator">
                    <div class="step completed">1</div>
                    <div class="step-line active"></div>
                    <div class="step completed">2</div>
                    <div class="step-line active"></div>
                    <div class="step active">3</div>
                </div>

                <form id="password-form">
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="new-password" name="password" placeholder="New Password" required>
                        <span class="toggle-password" onclick="togglePassword('new-password')">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>

                    <!-- Password Strength Checklist -->
                    <div id="password-checklist" class="password-checklist">
                        <div class="checklist-item" data-type="lower">
                            <i class="fas fa-times"></i>
                            <span>One lowercase letter (a-z)</span>
                        </div>
                        <div class="checklist-item" data-type="upper">
                            <i class="fas fa-times"></i>
                            <span>One uppercase letter (A-Z)</span>
                        </div>
                        <div class="checklist-item" data-type="number">
                            <i class="fas fa-times"></i>
                            <span>One number (0-9)</span>
                        </div>
                    </div>

                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="confirm-password" name="confirm_password" placeholder="Confirm New Password" required>
                        <span class="toggle-password" onclick="togglePassword('confirm-password')">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>

                    <button type="submit" class="btn">Reset Password</button>
                </form>
            </div>
        </div>
    </div>

    <script src="js/auth-script.js"></script>
    <script>
        // Forgot Password specific JavaScript
        // Make countdown timers global so helper functions can access them
        var countdownInterval;
        var resendTimeout;

        document.addEventListener('DOMContentLoaded', function() {

            // Step 1: Email Form
            const emailForm = document.getElementById('email-form');
            emailForm.addEventListener('submit', function(e) {
                e.preventDefault();

                const formData = new FormData(this);
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;

                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';

                fetch('auth/forgot-password.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message, 'success');
                        showStep(2);
                        startResendCountdown();
                    } else {
                        showNotification(data.message, 'error');
                    }
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('An error occurred. Please try again.', 'error');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                });
            });

            // Step 2: OTP Form
            const otpForm = document.getElementById('otp-form');
            otpForm.addEventListener('submit', function(e) {
                e.preventDefault();

                const formData = new FormData(this);
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;

                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying...';

                fetch('auth/verify-otp.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message, 'success');
                        showStep(3);
                    } else {
                        showNotification(data.message, 'error');
                    }
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('An error occurred. Please try again.', 'error');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                });
            });

            // Resend OTP
            const resendBtn = document.getElementById('resend-btn');
            resendBtn.addEventListener('click', function() {
                if (this.disabled) return;

                const email = document.querySelector('input[name="email"]').value;
                if (!email) {
                    showNotification('Please enter your email first', 'error');
                    return;
                }

                const formData = new FormData();
                formData.append('email', email);

                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';

                fetch('auth/forgot-password.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('OTP sent successfully', 'success');
                        startResendCountdown();
                    } else {
                        showNotification(data.message, 'error');
                        this.disabled = false;
                        this.innerHTML = 'Resend OTP';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('An error occurred. Please try again.', 'error');
                    this.disabled = false;
                    this.innerHTML = 'Resend OTP';
                });
            });

            // Step 3: Password Form
            const passwordForm = document.getElementById('password-form');
            passwordForm.addEventListener('submit', function(e) {
                e.preventDefault();

                const password = document.getElementById('new-password').value;
                const confirmPassword = document.getElementById('confirm-password').value;

                if (password !== confirmPassword) {
                    showNotification('Passwords do not match!', 'error');
                    return;
                }

                if (password.length < 6) {
                    showNotification('Password must be at least 6 characters long', 'error');
                    return;
                }

                const hasLower = /[a-z]/.test(password);
                const hasUpper = /[A-Z]/.test(password);
                const hasNumber = /\d/.test(password);

                if (!hasLower || !hasUpper || !hasNumber) {
                    showNotification('Password must contain at least one uppercase letter, one lowercase letter, and one number', 'error');
                    return;
                }

                const formData = new FormData(this);
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;

                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Resetting...';

                fetch('auth/reset-password.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message, 'success');
                        setTimeout(() => {
                            window.location.href = 'index.php';
                        }, 2000);
                    } else {
                        showNotification(data.message, 'error');
                    }
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('An error occurred. Please try again.', 'error');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                });
            });

            // Password strength checklist update
            const newPasswordInput = document.getElementById('new-password');
            newPasswordInput.addEventListener('input', function() {
                updatePasswordChecklist(this.value);
            });

            // OTP input validation (only numbers)
            const otpInput = document.querySelector('input[name="otp"]');
            otpInput.addEventListener('input', function() {
                this.value = this.value.replace(/\D/g, '').substring(0, 6);
            });
        });

        function showStep(stepNumber) {
            document.querySelectorAll('.step-container').forEach(container => {
                container.classList.remove('active');
            });
            document.getElementById('step' + stepNumber).classList.add('active');
        }

        function startResendCountdown() {
            const resendBtn = document.getElementById('resend-btn');
            const countdownEl = document.getElementById('countdown');
            let countdown = 60;

            resendBtn.disabled = true;
            resendBtn.innerHTML = 'Resend OTP';

            clearInterval(countdownInterval);
            countdownInterval = setInterval(() => {
                countdownEl.textContent = `Resend available in ${countdown} seconds`;
                countdown--;

                if (countdown < 0) {
                    clearInterval(countdownInterval);
                    resendBtn.disabled = false;
                    countdownEl.textContent = '';
                }
            }, 1000);

            clearTimeout(resendTimeout);
            resendTimeout = setTimeout(() => {
                resendBtn.disabled = false;
                countdownEl.textContent = '';
            }, 60000);
        }
    </script>
</body>
</html>
