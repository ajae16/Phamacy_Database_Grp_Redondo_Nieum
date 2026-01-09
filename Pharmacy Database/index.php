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
    <link href="css/index.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <title>Echinacea Pharmacy - Login & Register</title>
</head>
<body>
<div class="container" id="container">
        <!-- Login Form -->
        <div class="form-container login-container">
            <form id="login-form">
                <div class="pharmacy-logo">
                    <img src="Pharmacy Icons/echinacea Logo.png" alt="Echinacea Logo" style="width: 80px; height: 80px;">
                </div>
                <h2>Welcome Back</h2>
                <p class="subtitle">Sign in to Echinacea Pharmacy</p>
                
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" name="username" placeholder="Username or Email" required autocomplete="username">
                </div>
                
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="login-password" name="password" placeholder="Password" required autocomplete="current-password">
                    <span class="toggle-password" onclick="togglePassword('login-password')">
                        <i class="fas fa-eye"></i>
                    </span>
                </div>
                
                <div class="options">
                    <label class="checkbox">
                        <input type="checkbox" name="remember">
                        <span>Remember me</span>
                    </label>
                    <a href="Forgot Password.php" class="link">Forgot Password?</a>
                </div>
                
                <button type="submit" class="btn">Sign In</button>
                
                <p class="form-footer">
                    Don't have an account? <a href="#" class="link" onclick="switchToRegister(event)">Sign Up</a>
                </p>
            </form>
        </div>
        
        <!-- Register Form -->
        <div class="form-container register-container">
            <form id="register-form">
                <h2>Create Account</h2>
                <p class="subtitle">Join Echinacea Pharmacy Team</p>
                
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" name="firstName" placeholder="First Name" required>
                </div>
                
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" name="lastName" placeholder="Last Name" required>
                </div>
                
                <div class="input-group">
                    <i class="fas fa-user-circle"></i>
                    <input type="text" name="username" placeholder="Username" required autocomplete="username">
                </div>
                
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="register-password" name="password" placeholder="Password" required autocomplete="new-password">
                    <span class="toggle-password" onclick="togglePassword('register-password')">
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
                    <input type="password" id="confirm-password" name="confirm_password" placeholder="Confirm Password" required autocomplete="new-password">
                    <span class="toggle-password" onclick="togglePassword('confirm-password')">
                        <i class="fas fa-eye"></i>
                    </span>
                </div>

                <div class="input-group">
                    <i class="fas fa-briefcase"></i>
                    <select name="role" required style="width: 100%; padding: 12px 12px 12px 42px; border: 1px solid #ddd; border-radius: 10px; font-size: 14px; outline: none; background: #f8f9fa;">
                        <option value="">Select Role</option>
                        <option value="Employee">Employee</option>
                    </select>
                </div>
                
                <label class="checkbox">
                    <input type="checkbox" name="terms" required>
                    <span>I agree to the <a href="#" class="link">Terms & Conditions</a></span>
                </label>
                
                <button type="submit" class="btn">Create Account</button>
                
                <p class="form-footer">
                    Already have an account? <a href="#" class="link" onclick="switchToLogin(event)">Sign In</a>
                </p>
            </form>
        </div>
        
        <!-- Overlay Panel -->
        <div class="overlay-container">
            <div class="overlay">
                <div class="overlay-panel overlay-left">
                    <h2>Welcome Back!</h2>
                    <p>Sign in to access your pharmacy management dashboard and continue your work</p>
                    <button class="btn-ghost" onclick="switchToLogin(event)">Sign In</button>
                </div>
                <div class="overlay-panel overlay-right">
                    <h2>Join Our Team!</h2>
                    <p>Create an account to access the Echinacea Pharmacy Management System</p>
                    <button class="btn-ghost" onclick="switchToRegister(event)">Sign Up</button>
                </div>
            </div>
        </div>
    </div>

    <div class="circle"></div>
    <div class="circle"></div>
    <div class="circle"></div>
    <div class="circle"></div>
    
    <script src="js/auth-script.js"></script>
</body>
</html>

