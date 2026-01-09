<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    file_put_contents(__DIR__ . '/sidebar_session.log', "DEBUG sidebar session started\n", FILE_APPEND);
    file_put_contents(__DIR__ . '/sidebar_session.log', "DEBUG SESSION: " . print_r($_SESSION, true) . "\n", FILE_APPEND);
}

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

// Session timeout disabled - no auto-logout
// $_SESSION['last_activity'] = time();

// Get user information - use same session variables as Dashboard
$userName = $_SESSION['fullName'] ?? $_SESSION['username'] ?? 'User';
$userRoles = $_SESSION['roles'] ?? [];
$userRoleDisplay = $userRoles ? implode(', ', array_map('ucfirst', $userRoles)) : 'Employee';
$userId = $_SESSION['userId'] ?? '';
$userInitial = strtoupper(substr($userName, 0, 1));
?>

<style>
    /* Content Style */
     .page-outer-box {
     padding: 0 20px 20px 20px;
     max-width: 77%;
     margin: 0 auto;
     margin-left: 280px;
    }
    
    /* Modern Sidebar Styles */
    .sidebar {
        position: fixed;
        left: 0;
        top: 0;
        height: 100%;
        width: 13%;
        background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
        padding: 0;
        z-index: 1000;
        box-shadow: 4px 0 10px rgba(0, 0, 0, 0.1);
        overflow-y: auto;
        transition: width 0.3s ease;
    }

    .sidebar.collapsed {
        width: 70px;
    }

    .sidebar.collapsed .sidebar-brand-text,
    .sidebar.collapsed .user-details,
    .sidebar.collapsed .menu-text {
        display: none;
    }

    .sidebar.collapsed .sidebar-header {
        padding: 15px 10px 12px 10px;
    }

    .sidebar.collapsed .sidebar-user {
        padding: 0 10px;
        margin-bottom: 10px;
    }

    .sidebar.collapsed .menu-link {
        justify-content: center;
        padding: 10px;
    }

    .sidebar.collapsed .menu-icon {
        margin-right: 0;
    }

    .sidebar-header {
        padding: 15px 20px 12px 20px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        margin-bottom: 10px;
    }

    .sidebar-brand {
        display: flex;
        align-items: center;
        gap: 12px;
        color: white;
        text-decoration: none;
    }

    .sidebar-brand img {
        width: 40px;
        height: 40px;
        border-radius: 10px;
    }

    .sidebar-brand-text {
        font-size: 18px;
        font-weight: 700;
    }

    .sidebar-user {
        padding: 0 20px;
        margin-bottom: 10px;
    }

    .user-info {
        background: rgba(255, 255, 255, 0.05);
        padding: 12px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: linear-gradient(135deg, #667eea, #764ba2);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 700;
        font-size: 16px;
        flex-shrink: 0;
    }

    .user-details {
        flex: 1;
        overflow: hidden;
    }

    .user-name {
        color: white;
        font-weight: 600;
        font-size: 14px;
        margin-bottom: 2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .user-role {
        color: #94a3b8;
        font-size: 12px;
    }

    .sidebar-menu {
        list-style: none;
        padding: 0 10px;
        margin: 0;
    }

    .menu-item {
        margin-bottom: 4px;
    }

    .menu-link {
        display: flex !important;
        flex-direction: row !important;
        align-items: center !important;
        gap: 14px;
        padding: 10px 15px;
        color: #94a3b8;
        text-decoration: none;
        border-radius: 10px;
        transition: all 0.3s ease;
        position: relative;
    }

    .menu-link:hover {
        background: rgba(102, 126, 234, 0.1);
        color: #667eea;
    }

    .menu-link.active {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
    }

    .menu-link.active::before {
        content: '';
        position: absolute;
        left: 0;
        top: 50%;
        transform: translateY(-50%);
        width: 4px;
        height: 24px;
        background: white;
        border-radius: 0 4px 4px 0;
    }

    .menu-icon {
        width: 20px;
        height: 20px;
        font-size: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .menu-icon i {
        line-height: 1;
    }

    .menu-text {
        font-size: 14px;
        font-weight: 500;
        line-height: 1;
        flex: 1;
    }

    .menu-separator {
        height: 1px;
        background: rgba(255, 255, 255, 0.1);
        margin: 8px 20px;
    }

    /* Scrollbar styling for sidebar */
    .sidebar::-webkit-scrollbar {
        width: 6px;
    }

    .sidebar::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.05);
    }

    .sidebar::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.2);
        border-radius: 3px;
    }

    .sidebar::-webkit-scrollbar-thumb:hover {
        background: rgba(255, 255, 255, 0.3);
    }

    /* Logout Modal Styles */
    .logout-modal {
        display: none;
        position: fixed;
        z-index: 2000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        animation: logout-fadeIn 0.3s ease-out;
    }

    .logout-modal-content {
        background-color: white;
        margin: 15% auto;
        padding: 20px;
        border-radius: 10px;
        width: 90%;
        max-width: 400px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        animation: logout-slideIn 0.3s ease-out;
    }

    .logout-modal-header {
        display: flex;
        align-items: center;
        margin-bottom: 15px;
    }

    .logout-modal-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea, #764ba2);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        margin-right: 15px;
        flex-shrink: 0;
    }

    .logout-modal-title {
        font-size: 18px;
        font-weight: 600;
        color: #1e293b;
        margin: 0;
    }

    .logout-modal-body {
        margin-bottom: 20px;
    }

    .logout-modal-message {
        color: #64748b;
        font-size: 14px;
        line-height: 1.5;
    }

    .logout-modal-footer {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }

    .btn {
        padding: 8px 16px;
        border: none;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .btn-cancel {
        background: #f1f5f9;
        color: #64748b;
    }

    .btn-cancel:hover {
        background: #e2e8f0;
    }

    .btn-confirm {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
    }

    .btn-confirm:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }

    @keyframes logout-fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    @keyframes logout-slideIn {
        from { transform: translateY(-50px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }

    /* Responsive */
    @media (max-width: 768px) {
        .sidebar {
            left: -260px;
        }

        .sidebar.mobile-open {
            left: 0;
        }

        .logout-modal-content {
            margin: 20% auto;
            width: 95%;
        }
    }
</style>

<!-- Modern Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="Dashboard.php" class="sidebar-brand">
            <img src="Pharmacy Icons/echinacea Logo.png" alt="Echinacea Logo">
            <span class="sidebar-brand-text">Echinacea</span>
        </a>
    </div>

    <div class="sidebar-user">
        <div class="user-info">
            <div class="user-avatar">
                <?php echo $userInitial; ?>
            </div>
            <div class="user-details">
                <div class="user-name"><?php echo htmlspecialchars($userName); ?></div>
                <div class="user-role">
                    <?php if (!empty($userRoles)): ?>
                        <?php foreach ($userRoles as $role): ?>
                            <span class="role-badge"><?php echo htmlspecialchars(ucfirst($role)); ?></span>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <span class="role-badge">Employee</span>
                    <?php endif; ?>
                </div>
            <style>
                .user-role .role-badge {
                    display: inline-block;
                    background: #64748b;
                    color: #fff;
                    border-radius: 8px;
                    padding: 2px 8px;
                    font-size: 11px;
                    margin-right: 4px;
                    margin-bottom: 2px;
                }
            </style>
            </div>
        </div>
    </div>

    <ul class="sidebar-menu">
        <li class="menu-item">
            <a href="Dashboard.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'Dashboard.php' ? 'active' : ''; ?>">
                <span class="menu-icon"><i class="fas fa-home"></i></span>
                <span class="menu-text">Dashboard</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="Inventory.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'Inventory.php' ? 'active' : ''; ?>">
                <span class="menu-icon"><i class="fas fa-boxes"></i></span>
                <span class="menu-text">Inventory</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="Catalogs.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'Catalogs.php' ? 'active' : ''; ?>">
                <span class="menu-icon"><i class="fas fa-book"></i></span>
                <span class="menu-text">Catalogs</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="Suppliers.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'Suppliers.php' ? 'active' : ''; ?>">
                <span class="menu-icon"><i class="fas fa-truck-loading"></i></span>
                <span class="menu-text">Suppliers & Supply</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="Account Management.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'Account Management.php' ? 'active' : ''; ?>">
                <span class="menu-icon"><i class="fas fa-users-cog"></i></span>
                <span class="menu-text">User Management</span>
            </a>
        </li>

            <li class="menu-item">
                <a href="Role Management.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'Role Management.php' ? 'active' : ''; ?>">
                    <span class="menu-icon"><i class="fas fa-user-shield"></i></span>
                    <span class="menu-text">Role Management</span>
                </a>
            </li>

        <div class="menu-separator"></div>
        
        <li class="menu-item">
            <a href="Account Profile.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'Account Profile.php' ? 'active' : ''; ?>">
                <span class="menu-icon"><i class="fas fa-user-circle"></i></span>
                <span class="menu-text">My Profile</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="Archive.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'Archive.php' ? 'active' : ''; ?>">
                <span class="menu-icon"><i class="fas fa-archive"></i></span>
                <span class="menu-text">Archive</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="Logs.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'Logs.php' ? 'active' : ''; ?>">
                <span class="menu-icon"><i class="fas fa-history"></i></span>
                <span class="menu-text">Logs</span>
            </a>
        </li>

        <div class="menu-separator"></div>
        
        <li class="menu-item">
            <a href="auth/logout.php" class="menu-link" id="logout-link">
                <span class="menu-icon"><i class="fas fa-sign-out-alt"></i></span>
                <span class="menu-text">Logout</span>
            </a>
        </li>
    </ul>
</aside>

<!-- Logout Confirmation Modal -->
<div id="logout-modal" class="logout-modal">
    <div class="logout-modal-content">
        <div class="logout-modal-header">
            <div class="logout-modal-icon">
                <i class="fas fa-sign-out-alt"></i>
            </div>
            <h2 class="logout-modal-title">Confirm Logout</h2>
        </div>
        <div class="logout-modal-body">
            <p class="logout-modal-message">Are you sure you want to log out?</p>
        </div>
        <div class="logout-modal-footer">
            <button id="cancel-logout" class="btn btn-cancel">Cancel</button>
            <button id="confirm-logout" class="btn btn-confirm">Logout</button>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Sidebar toggle functionality
        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.getElementById('sidebar-toggle');

        // Load sidebar state from localStorage
        const sidebarVisible = localStorage.getItem('sidebarVisible') !== 'false'; // Default to true
        if (!sidebarVisible) {
            sidebar.classList.add('collapsed');
        }

        // Toggle sidebar on button click
        toggleBtn.addEventListener('click', function(e) {
            e.preventDefault();
            sidebar.classList.toggle('collapsed');
            const isCollapsed = sidebar.classList.contains('collapsed');
            localStorage.setItem('sidebarVisible', !isCollapsed);
        });

        // Active menu item based on current page
        const currentPage = window.location.pathname.split('/').pop();
        const menuLinks = document.querySelectorAll('.menu-link');

        menuLinks.forEach(link => {
            const href = link.getAttribute('href');
            if (href === currentPage || (currentPage === '' && href === 'Dashboard.php')) {
                link.classList.add('active');
            }
        });

        // Logout Modal Functionality
        const logoutLink = document.getElementById('logout-link');
        const logoutModal = document.getElementById('logout-modal');
        const cancelLogout = document.getElementById('cancel-logout');
        const confirmLogout = document.getElementById('confirm-logout');

        // Show modal on logout link click
        logoutLink.addEventListener('click', function(e) {
            e.preventDefault();
            logoutModal.style.display = 'block';
        });

        // Hide modal on cancel
        cancelLogout.addEventListener('click', function() {
            logoutModal.style.display = 'none';
        });

        // Proceed with logout on confirm
        confirmLogout.addEventListener('click', function() {
            window.location.href = 'auth/logout.php';
        });

        // Close modal when clicking outside
        window.addEventListener('click', function(e) {
            if (e.target === logoutModal) {
                logoutModal.style.display = 'none';
            }
        });

        // Heartbeat: ping server every 60 seconds to keep lastActivity updated
        function sendHeartbeat() {
            fetch('auth/heartbeat.php', {
                method: 'POST',
                credentials: 'same-origin'
            }).catch(function() {
                // Silently ignore network errors
            });
        }

        // Initial ping and interval
        sendHeartbeat();
        setInterval(sendHeartbeat, 60000);
    });
</script>

