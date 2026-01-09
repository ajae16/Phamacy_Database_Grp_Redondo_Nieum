<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

// Get user information from session (support both old and new variable names for compatibility)
$userId = $_SESSION['userId'] ?? $_SESSION['user_id'] ?? '';
$userName = $_SESSION['fullName'] ?? $_SESSION['full_name'] ?? $_SESSION['username'];
$userRole = ucfirst($_SESSION['userRole'] ?? $_SESSION['user_role'] ?? 'User');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Echinacea Pharmacy MIS</title>
    <link rel="stylesheet" href="css/Account Profile.css">
    <link rel="stylesheet" href="css/Pharmacy.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <main class="page-outer-box">
        <!-- Page Header -->
        <div class="page-header">
            <div class="page-header-content">
                <div class="page-title-section">
                    <h1><i class="fas fa-user-circle"></i> My Profile</h1>
                    <p>Manage your personal information and account settings</p>
                </div>
            </div>
        </div>

        <!-- Profile Grid -->
        <div class="profile-grid">
            <!-- Profile Card -->
            <div class="profile-card">
                <div class="profile-avatar" id="profileAvatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="profile-name" id="profileName">Loading...</div>
                <div class="profile-role">
                    <i class="fas fa-shield-alt"></i>
                    <span id="profileRole"><?php echo htmlspecialchars($userRole); ?></span>
                </div>
                <div class="profile-id">
                    <i class="fas fa-id-badge"></i> User ID: <strong id="profileId"><?php echo htmlspecialchars($userId); ?></strong>
                </div>
                <div class="profile-actions">
                    <button class="btn-profile btn-primary" onclick="showEditModal()">
                        <i class="fas fa-user-edit"></i>
                        Edit Profile
                    </button>
                    <button class="btn-profile btn-primary" onclick="showPasswordModal()">
                        <i class="fas fa-key"></i>
                        Change Password
                    </button>
                    <button class="btn-profile btn-secondary" onclick="window.location.href='Dashboard.php'">
                        <i class="fas fa-arrow-left"></i>
                        Back to Dashboard
                    </button>
                </div>
            </div>

            <!-- Information Card -->
            <div class="info-card">
                <div class="info-header">
                    <h2><i class="fas fa-info-circle"></i> Personal Information</h2>
                    <button class="btn-edit" onclick="showEditModal()">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                </div>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label"><i class="fas fa-user"></i> First Name</div>
                        <div class="info-value" id="firstName">-</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label"><i class="fas fa-user"></i> Middle Name</div>
                        <div class="info-value" id="middleName">-</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label"><i class="fas fa-user"></i> Last Name</div>
                        <div class="info-value" id="lastName">-</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label"><i class="fas fa-envelope"></i> Email Address</div>
                        <div class="info-value" id="email">-</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label"><i class="fas fa-phone"></i> Contact Number</div>
                        <div class="info-value" id="contact">-</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label"><i class="fas fa-user-tag"></i> Username</div>
                        <div class="info-value" id="username">-</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label"><i class="fas fa-calendar"></i> Schedule</div>
                        <div class="info-value" id="schedule">-</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label"><i class="fas fa-birthday-cake"></i> Birthday</div>
                        <div class="info-value" id="birthday">-</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label"><i class="fas fa-map-marker-alt"></i> Address</div>
                        <div class="info-value" id="address">-</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label"><i class="fas fa-calendar-plus"></i> Member Since</div>
                        <div class="info-value" id="createdAt">-</div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    </div>

    <!-- Edit Profile Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-user-edit"></i> Edit Profile</h2>
                <span class="close" onclick="closeEditModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div id="editAlert" class="alert"></div>
                <form id="editForm" onsubmit="event.preventDefault(); submitProfile();">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="editFirstName"><i class="fas fa-user"></i> First Name *</label>
                            <input type="text" id="editFirstName" required>
                        </div>
                        <div class="form-group">
                            <label for="editMiddleName"><i class="fas fa-user"></i> Middle Name</label>
                            <input type="text" id="editMiddleName" placeholder="Optional">
                        </div>
                        <div class="form-group">
                            <label for="editLastName"><i class="fas fa-user"></i> Last Name *</label>
                            <input type="text" id="editLastName" required>
                        </div>
                        <div class="form-group">
                            <label for="editEmail"><i class="fas fa-envelope"></i> Email Address *</label>
                            <input type="email" id="editEmail" required pattern="[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}">
                        </div>
                        <div class="form-group">
                            <label for="editContact"><i class="fas fa-phone"></i> Contact Number *</label>
                            <input type="tel" id="editContact" required pattern="[0-9]{10,11}" placeholder="09XXXXXXXXX or 10 digits">
                        </div>
                        <div class="form-group">
                            <label for="editBirthday"><i class="fas fa-birthday-cake"></i> Birthday</label>
                            <input type="date" id="editBirthday">
                        </div>
                        <div class="form-group">
                            <label for="editAddress"><i class="fas fa-map-marker-alt"></i> Address</label>
                            <input type="text" id="editAddress" placeholder="Optional">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="btn-submit" form="editForm">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div id="passwordModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-key"></i> Change Password</h2>
                <span class="close" onclick="closePasswordModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div id="passwordAlert" class="alert"></div>
                <form id="passwordForm" onsubmit="event.preventDefault(); submitPassword();">
                    <div class="form-grid">
                        <div class="form-group password-toggle">
                            <label for="currentPassword"><i class="fas fa-lock"></i> Current Password *</label>
                            <input type="password" id="currentPassword" required>
                            <button type="button" class="password-toggle-btn" onclick="togglePassword('currentPassword')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="form-group password-toggle">
                            <label for="newPassword"><i class="fas fa-lock"></i> New Password *</label>
                            <input type="password" id="newPassword" required minlength="6">
                            <button type="button" class="password-toggle-btn" onclick="togglePassword('newPassword')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="form-group password-toggle">
                            <label for="confirmPassword"><i class="fas fa-lock"></i> Confirm New Password *</label>
                            <input type="password" id="confirmPassword" required minlength="6">
                            <button type="button" class="password-toggle-btn" onclick="togglePassword('confirmPassword')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div style="padding: 12px; background: #fef3c7; border-left: 4px solid #f59e0b; border-radius: 8px; font-size: 13px; color: #92400e;">
                            <i class="fas fa-info-circle"></i> Password must be at least 6 characters long
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closePasswordModal()">Cancel</button>
                <button type="submit" class="btn-submit" form="passwordForm">
                    <i class="fas fa-key"></i> Change Password
                </button>
            </div>
        </div>
    </div>

    <script src="js/api-adapter.js"></script>
    <script>
        let currentUserData = null;

        // Load user profile on page load
        document.addEventListener('DOMContentLoaded', async function() {
            await loadProfile();
        });

        // Load user profile
        async function loadProfile() {
            try {
                const userId = '<?php echo $userId; ?>';
                console.log('Loading profile for user ID:', userId);

                // Load account data
                const accountResponse = await fetch(`api/accounts.php?id=${userId}`);
                const accountResult = await accountResponse.json();

                console.log('Account API Response:', accountResult);

                if (accountResult.success && accountResult.data) {
                    // API returns single object when querying by ID, not array
                    currentUserData = accountResult.data;

                    console.log('User data loaded:', currentUserData);
                    displayProfile(currentUserData);
                } else {
                    console.error('Failed to load profile:', accountResult);
                    document.getElementById('profileName').textContent = 'Error loading profile';
                    alert('Failed to load profile data. Please try refreshing the page.');
                }
            } catch (error) {
                console.error('Error loading profile:', error);
                document.getElementById('profileName').textContent = 'Error';
                alert('Error loading profile: ' + error.message);
            }
        }

        // Display profile information
        function displayProfile(user) {
            console.log('Displaying profile:', user);
            
            // Update profile card
            const firstName = user.firstName || '';
            const middleName = user.middleName || '';
            const lastName = user.lastName || '';
            const fullName = `${firstName} ${middleName} ${lastName}`.replace(/\s+/g, ' ').trim();
            
            document.getElementById('profileName').textContent = fullName || 'No Name';
            
            // Update avatar with initials
            const initials = `${firstName.charAt(0) || ''}${lastName.charAt(0) || ''}`.toUpperCase();
            document.getElementById('profileAvatar').textContent = initials || '?';

            // Update information fields
            document.getElementById('firstName').textContent = firstName || '-';
            
            const middleNameEl = document.getElementById('middleName');
            middleNameEl.textContent = middleName || 'Not specified';
            if (!middleName) {
                middleNameEl.classList.add('empty');
            } else {
                middleNameEl.classList.remove('empty');
            }
            
            document.getElementById('lastName').textContent = lastName || '-';
            document.getElementById('email').textContent = user.email || '-';
            
            const contactEl = document.getElementById('contact');
            contactEl.textContent = user.contact || 'Not specified';
            if (!user.contact) {
                contactEl.classList.add('empty');
            } else {
                contactEl.classList.remove('empty');
            }
            
            document.getElementById('username').textContent = user.username || '-';

            // Display schedule
            const scheduleEl = document.getElementById('schedule');
            scheduleEl.textContent = user.schedule || 'Not specified';
            if (!user.schedule) {
                scheduleEl.classList.add('empty');
            } else {
                scheduleEl.classList.remove('empty');
            }

            // Format birthday
            if (user.birthday) {
                try {
                    const date = new Date(user.birthday);
                    document.getElementById('birthday').textContent = date.toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    });
                } catch (e) {
                    document.getElementById('birthday').textContent = user.birthday;
                }
            } else {
                document.getElementById('birthday').textContent = 'Not specified';
            }

            // Display address
            const addressEl = document.getElementById('address');
            addressEl.textContent = user.address || 'Not specified';
            if (!user.address) {
                addressEl.classList.add('empty');
            } else {
                addressEl.classList.remove('empty');
            }

            // Format created date
            if (user.createdAt) {
                try {
                    const date = new Date(user.createdAt);
                    document.getElementById('createdAt').textContent = date.toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    });
                } catch (e) {
                    document.getElementById('createdAt').textContent = user.createdAt;
                }
            } else {
                document.getElementById('createdAt').textContent = '-';
            }

            console.log('Profile displayed successfully');
        }

        // Show edit profile modal
        function showEditModal() {
            if (!currentUserData) {
                alert('Profile data not loaded yet. Please wait and try again.');
                return;
            }

            console.log('Opening edit modal with data:', currentUserData);
            
            document.getElementById('editFirstName').value = currentUserData.firstName || '';
            document.getElementById('editMiddleName').value = currentUserData.middleName || '';
            document.getElementById('editLastName').value = currentUserData.lastName || '';
            document.getElementById('editEmail').value = currentUserData.email || '';
            document.getElementById('editContact').value = currentUserData.contact || '';
            document.getElementById('editBirthday').value = currentUserData.birthday || '';
            document.getElementById('editAddress').value = currentUserData.address || '';

            // Clear any previous alerts
            document.getElementById('editAlert').classList.remove('show');
            
            document.getElementById('editModal').style.display = 'block';
        }

        // Close edit modal
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
            document.getElementById('editAlert').classList.remove('show');
        }

        // Show password modal
        function showPasswordModal() {
            if (!currentUserData) {
                alert('Profile data not loaded yet. Please wait and try again.');
                return;
            }
            
            document.getElementById('passwordForm').reset();
            
            // Clear any previous alerts
            document.getElementById('passwordAlert').classList.remove('show');
            
            document.getElementById('passwordModal').style.display = 'block';
        }

        // Close password modal
        function closePasswordModal() {
            document.getElementById('passwordModal').style.display = 'none';
            document.getElementById('passwordAlert').classList.remove('show');
        }

        // Toggle password visibility
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const button = field.nextElementSibling;
            const icon = button.querySelector('i');

            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Submit profile changes
        async function submitProfile() {
            const firstName = document.getElementById('editFirstName').value.trim();
            const middleName = document.getElementById('editMiddleName').value.trim();
            const lastName = document.getElementById('editLastName').value.trim();
            const email = document.getElementById('editEmail').value.trim();
            const contact = document.getElementById('editContact').value.trim();
            const birthday = document.getElementById('editBirthday').value;
            const address = document.getElementById('editAddress').value.trim();

            console.log('Submitting profile update:', { firstName, middleName, lastName, email, contact, birthday, address });

            // Validation patterns
            const namePattern = /^[a-zA-Z\s\-']+$/;
            const emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
            const phonePattern = /^[0-9]{10,11}$/;

            // Validation
            if (!firstName || !lastName || !email || !contact) {
                showAlert('editAlert', 'Please fill in all required fields', 'error');
                return;
            }

            // First Name validation
            if (firstName.length < 2) {
                showAlert('editAlert', 'First Name must be at least 2 characters', 'error');
                return;
            }
            if (firstName.length > 50) {
                showAlert('editAlert', 'First Name must not exceed 50 characters', 'error');
                return;
            }
            if (!namePattern.test(firstName)) {
                showAlert('editAlert', 'First Name can only contain letters, spaces, hyphens, and apostrophes', 'error');
                return;
            }

            // Middle Name validation (optional but if provided)
            if (middleName) {
                if (middleName.length > 50) {
                    showAlert('editAlert', 'Middle Name must not exceed 50 characters', 'error');
                    return;
                }
                if (!namePattern.test(middleName)) {
                    showAlert('editAlert', 'Middle Name can only contain letters, spaces, hyphens, and apostrophes', 'error');
                    return;
                }
            }

            // Last Name validation
            if (lastName.length < 2) {
                showAlert('editAlert', 'Last Name must be at least 2 characters', 'error');
                return;
            }
            if (lastName.length > 50) {
                showAlert('editAlert', 'Last Name must not exceed 50 characters', 'error');
                return;
            }
            if (!namePattern.test(lastName)) {
                showAlert('editAlert', 'Last Name can only contain letters, spaces, hyphens, and apostrophes', 'error');
                return;
            }

            // Email validation
            if (!emailPattern.test(email)) {
                showAlert('editAlert', 'Please enter a valid email address (e.g., user@example.com)', 'error');
                return;
            }
            if (email.length > 100) {
                showAlert('editAlert', 'Email address must not exceed 100 characters', 'error');
                return;
            }

            // Phone validation
            if (!phonePattern.test(contact)) {
                showAlert('editAlert', 'Contact number must be 10-11 digits (e.g., 09171234567)', 'error');
                return;
            }

            try {
                const userId = '<?php echo $userId; ?>';
                const fullName = `${firstName} ${middleName} ${lastName}`.replace(/\s+/g, ' ').trim();
                
                const requestData = {
                    id: userId,
                    firstName: firstName,
                    middleName: middleName,
                    lastName: lastName,
                    fullName: fullName,
                    email: email,
                    contact: contact,
                    birthday: birthday,
                    address: address
                };
                
                console.log('Sending update request:', requestData);
                
                const response = await fetch('api/accounts.php', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(requestData)
                });

                const result = await response.json();
                console.log('Update response:', result);

                if (result.success) {
                    showAlert('editAlert', 'Profile updated successfully!', 'success');
                    setTimeout(() => {
                        closeEditModal();
                        // Reload page to update session data and sidebar
                        location.reload();
                    }, 1500);
                } else {
                    showAlert('editAlert', result.message || 'Failed to update profile', 'error');
                }
            } catch (error) {
                console.error('Error updating profile:', error);
                showAlert('editAlert', 'Error: ' + error.message, 'error');
            }
        }

        // Submit password change
        async function submitPassword() {
            const currentPassword = document.getElementById('currentPassword').value;
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;

            console.log('Attempting password change');

            // Validation
            if (!currentPassword || !newPassword || !confirmPassword) {
                showAlert('passwordAlert', 'Please fill in all fields', 'error');
                return;
            }

            if (newPassword.length < 6) {
                showAlert('passwordAlert', 'New password must be at least 6 characters', 'error');
                return;
            }

            if (newPassword !== confirmPassword) {
                showAlert('passwordAlert', 'New passwords do not match', 'error');
                return;
            }

            if (currentPassword === newPassword) {
                showAlert('passwordAlert', 'New password must be different from current password', 'error');
                return;
            }

            try {
                const userId = '<?php echo $userId; ?>';
                
                console.log('Sending password change request for user:', userId);
                
                const response = await fetch('api/accounts.php', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id: userId,
                        currentPassword: currentPassword,
                        password: newPassword
                    })
                });

                const result = await response.json();
                console.log('Password change response:', result);

                if (result.success) {
                    showAlert('passwordAlert', 'Password changed successfully!', 'success');
                    document.getElementById('passwordForm').reset();
                    setTimeout(() => {
                        closePasswordModal();
                    }, 1500);
                } else {
                    showAlert('passwordAlert', result.message || 'Failed to change password', 'error');
                }
            } catch (error) {
                console.error('Error changing password:', error);
                showAlert('passwordAlert', 'Error: ' + error.message, 'error');
            }
        }

        // Show alert message
        function showAlert(alertId, message, type) {
            const alert = document.getElementById(alertId);
            alert.textContent = message;
            alert.className = `alert ${type} show`;
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const editModal = document.getElementById('editModal');
            const passwordModal = document.getElementById('passwordModal');
            
            if (event.target === editModal) {
                closeEditModal();
            }
            if (event.target === passwordModal) {
                closePasswordModal();
            }
        }
    </script>
</body>
</html>

