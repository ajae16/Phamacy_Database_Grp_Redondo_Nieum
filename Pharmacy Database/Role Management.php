<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Role Management - Echinacea Pharmacy MIS</title>
    <link rel="stylesheet" href="css/Role Management.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <main class="page-outer-box">
        <!-- Search and Filter -->
        <div class="search-filter-section">
            <div class="search-filter-grid">
                <div class="input-group">
                    <label><i class="fas fa-search"></i> Search Users</label>
                    <input type="text" id="searchUserInput" placeholder="Search by name or email...">
                </div>
                <div class="input-group">
                    <label><i class="fas fa-filter"></i> Filter by Role</label>
                    <select id="roleUserFilter">
                        <option value="">All Roles</option>
                    </select>
                </div>
                <div class="input-group">
                    <label>&nbsp;</label>
                    <button class="btn-secondary" onclick="applyUserFilters()">
                        <i class="fas fa-sync-alt"></i> Apply
                    </button>
                </div>
            </div>
        </div>

        <!-- Roles Table -->
        <div class="roles-section">
            <div class="section-header">
                <h2><i class="fas fa-user-shield"></i> Roles</h2>
                <div class="header-actions">
                    <button class="btn-primary" onclick="showRoleModal()">
                        <i class="fas fa-plus"></i> Add Role
                    </button>
                </div>
                <span id="roleCount" style="color: #64748b; font-size: 14px;"></span>
            </div>
            <div id="rolesTableContainer">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>Role Name</th>
                            <th>Description</th>
                            <th>Permissions</th>
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

        <!-- Users Table -->
        <div class="users-section">
            <div class="section-header">
                <h2><i class="fas fa-users"></i> Users</h2>
                <span id="userCount" style="color: #64748b; font-size: 14px;">0 users</span>
            </div>
            <div id="usersTableContainer">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>Full Name</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="usersTableBody">
                        <tr>
                            <td colspan="4" class="empty-state">
                                <i class="fas fa-users"></i>
                                <p>Loading users...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
        <!-- Assign Role Modal -->
        <div id="assignRoleModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Assign Role</h2>
                    <span class="close" onclick="closeAssignRoleModal()">&times;</span>
                </div>
                <div class="modal-body">
                    <form id="assignRoleForm" onsubmit="event.preventDefault(); submitAssignRole();">
                        <input type="hidden" id="assignUserId">
                        <div class="form-group">
                            <label for="assignRoleSelect">Select Role</label>
                            <select id="assignRoleSelect" required></select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeAssignRoleModal()">Cancel</button>
                    <button type="submit" class="btn-submit" form="assignRoleForm"><i class="fas fa-save"></i> Assign Role</button>
                </div>
            </div>
        </div>
    <!-- Role Modal -->
    <div id="roleModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="roleModalTitle">Add Role</h2>
                <span class="close" onclick="closeRoleModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="roleForm" onsubmit="event.preventDefault(); submitRole();">
                    <div class="form-group">
                        <label for="roleName">Role Name *</label>
                        <input type="text" id="roleName" required>
                    </div>
                    <div class="form-group">
                        <label for="displayName">Display Name</label>
                        <input type="text" id="displayName">
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeRoleModal()">Cancel</button>
                <button type="submit" class="btn-submit" form="roleForm"><i class="fas fa-save"></i> Save Role</button>
            </div>
        </div>
    </div>
    <script src="js/Role Management.js"></script>
</body>
</html>
