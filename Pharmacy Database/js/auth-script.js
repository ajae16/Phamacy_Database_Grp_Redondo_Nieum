// Switch to Register Form
function switchToRegister(event) {
    event.preventDefault();
    const container = document.getElementById('container');
    container.classList.add('active');
}

// Switch to Login Form
function switchToLogin(event) {
    event.preventDefault();
    const container = document.getElementById('container');
    container.classList.remove('active');
}

// Toggle Password Visibility
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const icon = event.target.closest('.toggle-password').querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Show notification
function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existingNotification = document.querySelector('.notification');
    if (existingNotification) {
        existingNotification.remove();
    }
    
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
        <span>${message}</span>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('show');
    }, 100);
    
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 4000);
}

// Form Validation and Submission
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');
    
    // Login Form Submission
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            // Disable button and show loading
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing In...';
            
            fetch('auth/login.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    setTimeout(() => {
                        window.location.href = 'Dashboard.php';
                    }, 1500);
                } else {
                    showNotification(data.message, 'error');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred. Please try again.', 'error');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        });
    }
    
    // Register Form Submission
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const password = document.getElementById('register-password').value;
            const confirmPassword = document.getElementById('confirm-password').value;
            const terms = this.querySelector('input[name="terms"]');
            
            // Client-side validation
            if (password !== confirmPassword) {
                showNotification('Passwords do not match!', 'error');
                return false;
            }
            
            if (terms && !terms.checked) {
                showNotification('Please accept the Terms & Conditions', 'error');
                return false;
            }
            
            if (password.length < 6) {
                showNotification('Password must be at least 6 characters long', 'error');
                return false;
            }

            // Check password format
            const hasLower = /[a-z]/.test(password);
            const hasUpper = /[A-Z]/.test(password);
            const hasNumber = /\d/.test(password);

            if (!hasLower || !hasUpper || !hasNumber) {
                showNotification('Password must contain at least one uppercase letter, one lowercase letter, and one number', 'error');
                return false;
            }
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            // Disable button and show loading
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Account...';
            
            fetch('auth/register.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    setTimeout(() => {
                        switchToLogin(new Event('click'));
                        registerForm.reset();
                    }, 1500);
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
    }
    
    // Input Focus Animation
    const inputs = document.querySelectorAll('.input-group input');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });
        
        input.addEventListener('blur', function() {
            if (!this.value) {
                this.parentElement.classList.remove('focused');
            }
        });
    });
    
    // Password strength checklist update
    const registerPasswordInput = document.getElementById('register-password');
    if (registerPasswordInput) {
        registerPasswordInput.addEventListener('input', function() {
            updatePasswordChecklist(this.value);
        });
    }

    // Check for logout success message
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('logout') === 'success') {
        showNotification('You have been successfully logged out', 'success');
        // Clear localStorage on logout
        localStorage.removeItem('pharmacy_login_credentials');
        // Clean URL
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    // Auto-populate login form with saved credentials
    const savedCredentials = localStorage.getItem('pharmacy_login_credentials');
    if (savedCredentials && loginForm) {
        try {
            const credentials = JSON.parse(savedCredentials);
            const usernameInput = loginForm.querySelector('input[name="username"]');
            if (usernameInput && credentials.identifier) {
                usernameInput.value = credentials.identifier;
            }
        } catch (error) {
            console.error('Error parsing saved credentials:', error);
            localStorage.removeItem('pharmacy_login_credentials');
        }
    }
});

// Function to update password strength checklist
function updatePasswordChecklist(password) {
    const checklist = document.getElementById('password-checklist');
    if (!checklist) return;

    const hasLower = /[a-z]/.test(password);
    const hasUpper = /[A-Z]/.test(password);
    const hasNumber = /\d/.test(password);

    const lowerItem = checklist.querySelector('.checklist-item[data-type="lower"]');
    const upperItem = checklist.querySelector('.checklist-item[data-type="upper"]');
    const numberItem = checklist.querySelector('.checklist-item[data-type="number"]');

    if (lowerItem) {
        lowerItem.classList.toggle('valid', hasLower);
    }
    if (upperItem) {
        upperItem.classList.toggle('valid', hasUpper);
    }
    if (numberItem) {
        numberItem.classList.toggle('valid', hasNumber);
    }
}



