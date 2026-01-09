<?php
session_start();

/* Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
} elseif ($_SESSION['userRole'] === 'employee') {
    echo "Access restricted to Admin/Manager only. Redirecting to Dashboard...";
    header("Refresh: 2; url=Dashboard.php");
    exit;
}*/

// Session timeout disabled - no auto-logout
// $_SESSION['last_activity'] = time();

// Get user information
$userName = $_SESSION['fullName'] ?? $_SESSION['username'];
$userRole = ucfirst($_SESSION['userRole'] ?? 'User');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Echinacea Pharmacy MIS</title>
    <link rel="stylesheet" href="css/Account Management.css">
    <link rel="stylesheet" href="css/Pharmacy.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <main class="page-outer-box">
        
        <!-- Statistics Cards -->
        <div class="stats-container">
            <div class="stat-card blue">
                <div class="stat-header">
                    <div class="stat-title">Total Users</div>
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                </div>
                <div class="stat-value" id="totalUsers">0</div>
                <div class="stat-footer">Registered in system</div>
            </div>

            <div class="stat-card purple">
                <div class="stat-header">
                    <div class="stat-title">Administrators</div>
                    <div class="stat-icon"><i class="fas fa-user-shield"></i></div>
                </div>
                <div class="stat-value" id="totalAdmins">0</div>
                <div class="stat-footer">Admin accounts</div>
            </div>

            <div class="stat-card green">
                <div class="stat-header">
                    <div class="stat-title">Employees</div>
                    <div class="stat-icon"><i class="fas fa-user-tie"></i></div>
                </div>
                <div class="stat-value" id="totalEmployees">0</div>
                <div class="stat-footer">Employee accounts</div>
            </div>

            <div class="stat-card orange">
                <div class="stat-header">
                    <div class="stat-title">Active Account</div>
                    <div class="stat-icon"><i class="fas fa-user-clock"></i></div>
                </div>
                <div class="stat-value" id="activeAccount">0</div>
                <div class="stat-footer">Active sessions</div>
            </div>
        </div>

        <!-- Search and Filter -->
        <div class="search-filter-section">
            <div class="search-filter-grid">
                <div class="input-group">
                    <label><i class="fas fa-search"></i> Search Users</label>
                    <input type="text" id="searchInput" placeholder="Search by username, full name, or email...">
                </div>
                <div class="input-group">
                    <label><i class="fas fa-filter"></i> Filter by Role</label>
                    <select id="roleFilter">
                        <option value="">All Roles</option>
                        <option value="admin">Administrator</option>
                        <option value="employee">Employee</option>
                    </select>
                </div>
                <div class="input-group">
                    <label>&nbsp;</label>
                    <button class="btn-secondary" onclick="applyFilters()">
                        <i class="fas fa-sync-alt"></i> Apply
                    </button>
                </div>
            </div>
        </div>

        <!-- Users Table -->
        <div class="users-section">
            <div class="section-header">
                <h2><i class="fas fa-list"></i> System Users</h2>
                <div class="header-actions">
                    <button class="btn-primary" onclick="showCreateModal()">
                        <i class="fas fa-user-plus"></i>
                        Add New User
                    </button>
                </div>
                <span id="userCount" style="color: #64748b; font-size: 14px;">0 users</span>
            </div>
            <!-- User and Roles Table Grid -->
            <div class="tables-grid">
                <div class="user-table-card">
                    <div class="section-header">
                        <h2><i class="fas fa-list"></i> System Users</h2>
                        <div class="header-actions">
                            <button class="btn-primary" onclick="showCreateModal()">
                                <i class="fas fa-user-plus"></i>
                                Add New User
                            </button>
                        </div>
                        <span id="userCount" style="color: #64748b; font-size: 14px;">0 users</span>
                    </div>
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>User ID</th>
                                <th>Username</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="accountUsersTableBody">
                            <tr>
                                <td colspan="8" class="empty-state">
                                    <i class="fas fa-users"></i>
                                    <p>Loading users...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="roles-table-card">
                    <div class="section-header">
                        <h2><i class="fas fa-user-shield"></i> Roles</h2>
                        <div class="header-actions">
                            <button class="btn-primary" onclick="showRoleModal()">
                                <i class="fas fa-plus"></i> Add Role
                            </button>
                        </div>
                        <span id="roleCount" style="color: #64748b; font-size: 14px;"></span>
                    </div>
                    <table class="roles-table">
                        <thead>
                            <tr>
                                <th>Role Name</th>
                                <th>Description</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="rolesTableBody">
                            <tr>
                                <td colspan="3" class="empty-state">
                                    <i class="fas fa-user-shield"></i>
                                    <p>Loading roles...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- User Modal -->
    <div id="userModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Add New User</h2>
                <span id="modalClose" class="close" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="userForm" onsubmit="event.preventDefault(); submitUser();">
                    <div class="form-columns">
                        <div class="form-column">
                            <h3><i class="fas fa-user-shield"></i> Account Information</h3>
                            <div class="form-grid">
                                <div class="form-group full-width">
                                    <label for="username"><i class="fas fa-user"></i> Username *</label>
                                    <input type="text" id="username" required>
                                </div>
                                <div class="form-group">
                                    <label for="firstName"><i class="fas fa-id-card"></i> First Name *</label>
                                    <input type="text" id="firstName" required>
                                </div>
                                <div class="form-group">
                                    <label for="middleName"><i class="fas fa-id-card"></i> Middle Name</label>
                                    <input type="text" id="middleName">
                                </div>
                                <div class="form-group">
                                    <label for="lastName"><i class="fas fa-id-card"></i> Last Name *</label>
                                    <input type="text" id="lastName" required>
                                </div>
                                <div class="form-group full-width">
                                    <label for="email"><i class="fas fa-envelope"></i> Email Address *</label>
                                    <input type="email" id="email" required pattern="[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$">
                                </div>
                                <!-- User Role input removed: role will be assigned by admin/manager only -->
                                <div class="form-group">
                                    <label for="status"><i class="fas fa-toggle-on"></i> Status *</label>
                                    <select id="status" required>
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                                <div class="form-group password-toggle">
                                    <label for="password"><i class="fas fa-lock"></i> Password *</label>
                                    <input type="password" id="password" required minlength="6">
                                    <button type="button" class="password-toggle-btn" onclick="togglePassword('password')">
                                        <i class="fas fa-eye" id="password-icon"></i>
                                    </button>
                                </div>
                                <div class="form-group password-toggle">
                                    <label for="confirmPassword"><i class="fas fa-lock"></i> Confirm Password *</label>
                                    <input type="password" id="confirmPassword" required minlength="6">
                                    <button type="button" class="password-toggle-btn" onclick="togglePassword('confirmPassword')">
                                        <i class="fas fa-eye" id="confirmPassword-icon"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="form-column">
                            <h3><i class="fas fa-id-card"></i> Additional Information</h3>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="contact"><i class="fas fa-phone"></i> Contact</label>
                                    <input type="text" id="contact">
                                </div>
                                <div class="form-group">
                                    <label for="schedule"><i class="fas fa-calendar"></i> Schedule</label>
                                    <input type="text" id="schedule">
                                </div>
                                <div class="form-group">
                                    <label for="birthday"><i class="fas fa-birthday-cake"></i> Birthday</label>
                                    <input type="date" id="birthday">
                                </div>
                                <div class="form-group">
                                    <label for="sex"><i class="fas fa-venus-mars"></i> Sex</label>
                                    <select id="sex">
                                        <option value="">Select Sex</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="form-group full-width">
                                    <label for="address"><i class="fas fa-map-marker-alt"></i> Address</label>
                                    <textarea id="address"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" id="modalCancel" class="btn-cancel" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn-submit" form="userForm">
                    <i class="fas fa-save"></i> Save User
                </button>
            </div>
        </div>
    </div>

    <script src="js/api-adapter.js"></script>
    <script src="js/Account Management.js"></script>
</body>
</html>