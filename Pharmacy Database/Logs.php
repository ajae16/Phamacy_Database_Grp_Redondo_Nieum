<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

// Get user information
$userName = $_SESSION['fullName'] ?? $_SESSION['username'];
$userRoles = $_SESSION['roles'] ?? [];

// Include database configuration
require_once 'database/db_config.php';

// Function to get logs by category
function getLogsByCategory($categoryModules, $limit = 10) {
    $conn = getDBConnection();
    if (!$conn) {
        return [];
    }

    $placeholders = str_repeat('?,', count($categoryModules) - 1) . '?';
    $sql = "SELECT timestamp, username, action, module, details, ipAddress
            FROM activity_logs
            WHERE module IN ($placeholders)
            ORDER BY timestamp DESC
            LIMIT ?";

    $params = array_merge($categoryModules, [$limit]);
    $types = str_repeat('s', count($categoryModules)) . 'i';

    return executeQuery($sql, $params, $types);
}

// Get logs for each category
$userActivityLogs = getLogsByCategory(['AUTH', 'ACCOUNTS']);
$inventoryLogs = getLogsByCategory(['INVENTORY', 'SALES', 'RECORDS']);
$systemLogs = getLogsByCategory(['SYSTEM']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs - Echinacea Pharmacy MIS</title>
    <link rel="stylesheet" href="css/Dashboard.css">
    <link rel="stylesheet" href="css/Logs.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
    <main class="page-outer-box">
        <!-- Page Header -->
        <div class="page-header">
            <div class="page-header-content">
                <div class="page-title-section">
                    <h1><i class="fas fa-history"></i> Activity Logs</h1>
                    <p>Monitor system activities and user interactions</p>
                </div>
            </div>
        </div>

        <!-- Activity Categories -->
        <div class="logs-container">
            <div class="logs-grid">
                <!-- User Activities Column -->
                <div class="logs-column">
                    <div class="logs-card">
                        <div class="logs-header">
                            <h3><i class="fas fa-user"></i> User Activities</h3>
                        </div>
                        <div class="logs-content">
                            <?php if (!empty($userActivityLogs)): ?>
                                <?php foreach ($userActivityLogs as $log): ?>
                                    <div class="activity-item">
                                        <div class="activity-icon <?php echo strtolower($log['action']); ?>">
                                            <i class="fas fa-<?php
                                                echo match($log['action']) {
                                                    'LOGIN' => 'sign-in-alt',
                                                    'LOGOUT' => 'sign-out-alt',
                                                    'UPDATE' => 'user-edit',
                                                    'CREATE' => 'user-plus',
                                                    'DELETE' => 'user-minus',
                                                    default => 'user'
                                                };
                                            ?>"></i>
                                        </div>
                                        <div class="activity-details">
                                            <div class="activity-title"><?php echo htmlspecialchars($log['username']); ?> - <?php echo htmlspecialchars($log['action']); ?></div>
                                            <div class="activity-description"><?php echo htmlspecialchars($log['details']); ?> <small>(<?php echo date('M d, H:i', strtotime($log['timestamp'])); ?>)</small></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="activity-item">
                                    <div class="activity-icon">
                                        <i class="fas fa-info-circle"></i>
                                    </div>
                                    <div class="activity-details">
                                        <div class="activity-title">No Recent Activities</div>
                                        <div class="activity-description">No user activities logged yet.</div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Inventory & Management Column -->
                <div class="logs-column">
                    <div class="logs-card">
                        <div class="logs-header">
                            <h3><i class="fas fa-cogs"></i> Inventory & Management</h3>
                        </div>
                        <div class="logs-content">
                            <?php if (!empty($inventoryLogs)): ?>
            <div class="user-role-badge" style="margin-bottom: 10px;">
                <i class="fas fa-user-shield"></i>
                <?php if (!empty($userRoles)): ?>
                    <?php foreach ($userRoles as $role): ?>
                        <span class="role-badge"><?php echo htmlspecialchars(ucfirst($role)); ?></span>
                    <?php endforeach; ?>
                <?php else: ?>
                    <span class="role-badge">User</span>
                <?php endif; ?>
            </div>
                                <?php foreach ($inventoryLogs as $log): ?>
                                    <div class="activity-item">
                                        <div class="activity-icon <?php echo strtolower($log['module']); ?>">
                                            <i class="fas fa-<?php
                                                echo match($log['module']) {
                                                    'INVENTORY' => 'boxes',
                                                    'SALES' => 'shopping-cart',
                                                    'RECORDS' => 'file-alt',
                                                    default => 'cogs'
                                                };
                                            ?>"></i>
                                        </div>
                                        <div class="activity-details">
                                            <div class="activity-title"><?php echo htmlspecialchars($log['username']); ?> - <?php echo htmlspecialchars($log['action']); ?> (<?php echo htmlspecialchars($log['module']); ?>)</div>
                                            <div class="activity-description"><?php echo htmlspecialchars($log['details']); ?> <small>(<?php echo date('M d, H:i', strtotime($log['timestamp'])); ?>)</small></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="activity-item">
                                    <div class="activity-icon">
                                        <i class="fas fa-info-circle"></i>
                                    </div>
                                    <div class="activity-details">
                                        <div class="activity-title">No Recent Activities</div>
                                        <div class="activity-description">No inventory or management activities logged yet.</div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- System & Communication Column -->
                <div class="logs-column">
                    <div class="logs-card">
                        <div class="logs-header">
                            <h3><i class="fas fa-server"></i> System & Communication</h3>
                        </div>
                        <div class="logs-content">
                            <?php if (!empty($systemLogs)): ?>
                                <?php foreach ($systemLogs as $log): ?>
                                    <div class="activity-item">
                                        <div class="activity-icon system">
                                            <i class="fas fa-server"></i>
                                        </div>
                                        <div class="activity-details">
                                            <div class="activity-title"><?php echo htmlspecialchars($log['username']); ?> - <?php echo htmlspecialchars($log['action']); ?> (<?php echo htmlspecialchars($log['module']); ?>)</div>
                                            <div class="activity-description"><?php echo htmlspecialchars($log['details']); ?> <small>(<?php echo date('M d, H:i', strtotime($log['timestamp'])); ?>)</small></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="activity-item">
                                    <div class="activity-icon">
                                        <i class="fas fa-info-circle"></i>
                                    </div>
                                    <div class="activity-details">
                                        <div class="activity-title">No Recent Activities</div>
                                        <div class="activity-description">No system activities logged yet.</div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
<style>
    .user-role-badge .role-badge {
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
