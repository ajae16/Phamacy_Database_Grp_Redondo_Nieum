<?php
$currentDate = date('l, F j, Y');
$currentTime = date('g:i A');
session_start();
$userRoles = $_SESSION['roles'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Echinacea Pharmacy MIS</title>
    <link rel="stylesheet" href="css/Dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="js/api-adapter.js"></script>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <main class="page-outer-box">
        <!-- Welcome Banner -->
        <div class="welcome-banner">
            <div class="welcome-banner-content">
                <div class="welcome-text">
                    <h1><i class="fas fa-hand-wave" style="color: #fbbf24;"></i> Welcome back, <?php echo htmlspecialchars($userName); ?>!</h1>
                    <p>Here's what's happening in your pharmacy today</p>
                </div>
                <div class="welcome-meta">
                    <div class="user-role-badge">
                        <i class="fas fa-user-shield"></i>
                        <?php if (!empty($userRoles)): ?>
                            <?php foreach ($userRoles as $role): ?>
                                <span class="role-badge"><?php echo htmlspecialchars(ucfirst($role)); ?></span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span class="role-badge">Employee</span>
                        <?php endif; ?>
                    </div>
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
                    <div class="date-time">
                        <i class="fas fa-calendar"></i> <?php echo $currentDate; ?><br>
                        <i class="fas fa-clock"></i> <span id="currentTime"><?php echo $currentTime; ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <div class="quick-actions-grid">
                <a href="Suppliers.php" class="quick-action-btn">
                    <i class="fas fa-truck-loading"></i>
                    <span>Record Supply</span>
                </a>
                <a href="Inventory.php" class="quick-action-btn">
                    <i class="fas fa-plus-circle"></i>
                    <span>Add Product</span>
                </a>
                <a href="Account Management.php" class="quick-action-btn">
                    <i class="fas fa-user-plus"></i>
                    <span>Add User</span>
                </a>
                <a href="Suppliers.php" class="quick-action-btn">
                    <i class="fas fa-building"></i>
                    <span>Manage Suppliers</span>
                </a>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-container">
            <div class="stat-card blue">
                <div class="stat-header">
                    <div class="stat-title">Total Products</div>
                    <div class="stat-icon"><i class="fas fa-pills"></i></div>
                </div>
                <div class="stat-value" id="totalProducts">0</div>
                <div class="stat-footer">
                    In inventory
                </div>
            </div>

            <div class="stat-card green">
                <div class="stat-header">
                    <div class="stat-title">Sales Today</div>
                    <div class="stat-icon"><i class="fas fa-peso-sign"></i></div>
                </div>
                <div class="stat-value" id="salesToday">₱0</div>
                <div class="stat-footer">
                    Total revenue today
                </div>
            </div>

            <div class="stat-card orange">
                <div class="stat-header">
                    <div class="stat-title">Low Stock Alert</div>
                    <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
                </div>
                <div class="stat-value" id="lowStock">0</div>
                <div class="stat-footer">
                    Items need restocking
                </div>
            </div>

            <div class="stat-card red">
                <div class="stat-header">
                    <div class="stat-title">Expiring Soon</div>
                    <div class="stat-icon"><i class="fas fa-calendar-times"></i></div>
                </div>
                <div class="stat-value" id="expiringSoon">0</div>
                <div class="stat-footer">
                    Within 100 days
                </div>
            </div>
        </div>

        <!-- Dashboard Grid -->
        <div class="dashboard-grid">
            <!-- Recent Activity -->
            <div class="section-card">
                <div class="section-header">
                    <h2><i class="fas fa-history"></i> Recent Activity</h2>
                    <a href="Suppliers.php" class="view-all-link">View All <i class="fas fa-arrow-right"></i></a>
                </div>
                <ul class="activity-list" id="activityList">
                    <li class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>No recent activity</p>
                    </li>
                </ul>
            </div>

            <!-- Alerts -->
            <div class="section-card">
                <div class="section-header">
                    <h2><i class="fas fa-bell"></i> Alerts</h2>
                </div>
                <div id="alertsList">
                    <div class="empty-state">
                        <i class="fas fa-check-circle"></i>
                        <p>No alerts</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Inventory -->
        <div class="section-card">
            <div class="section-header">
                <h2><i class="fas fa-box-open"></i> Recent Inventory</h2>
                <a href="Inventory.php" class="view-all-link">View All <i class="fas fa-arrow-right"></i></a>
            </div>
            <div id="inventoryGrid" class="inventory-grid">
                <!-- Inventory cards will be rendered here -->
            </div>
            <div class="pagination" id="pagination">
                <!-- Pagination will be rendered here -->
            </div>
        </div>
    </main>
    </div>

    <script>
        // Dashboard JavaScript functionality

        // Global variables for pagination
        let currentPagination = 1;
        const itemsPerPage = 8;
        let totalItems = 0;

// Update the current time display every second
setInterval(() => {
    const now = new Date();
    document.getElementById('currentTime').textContent = now.toLocaleTimeString('en-US', {
        hour: 'numeric',
        minute: '2-digit',
        hour12: true
    });
}, 1000);

// Initialize dashboard on page load
document.addEventListener('DOMContentLoaded', async function() {
    await loadDashboardStatistics();
    await renderDashboardInventory();
    await loadRecentActivity();
    await loadAlerts();

    // Refresh statistics every 30 seconds
    setInterval(async () => {
        await loadDashboardStatistics();
        await loadRecentActivity();
    }, 30000);
});

// Load dashboard statistics (total products, sales today, low stock, expiring soon)
async function loadDashboardStatistics() {
    try {
        // Fetch inventory data
        const invResponse = await fetch('api/inventory.php');
        const invResult = await invResponse.json();

        if (invResult.success) {
            const items = invResult.data || [];
            const today = new Date();
            const alertDays = 100;

            // Total products
            document.getElementById('totalProducts').textContent = items.length;

            // Low stock (less than 20)
            const lowStock = items.filter(item => item.stock < 20).length;
            document.getElementById('lowStock').textContent = lowStock;

            // Expiring soon
            const expiringSoon = items.filter(item => {
                const expDate = new Date(item.expirationDate);
                const daysToExpiration = Math.round((expDate - today) / (1000 * 60 * 60 * 24));
                return daysToExpiration <= alertDays && daysToExpiration >= 0;
            }).length;
            document.getElementById('expiringSoon').textContent = expiringSoon;
        }

        // Fetch sales data for today
        const salesResponse = await fetch('api/sales.php?period=today');
        const salesResult = await salesResponse.json();

        if (salesResult.success) {
            const totalSales = salesResult.data.reduce((sum, sale) => sum + parseFloat(sale.totalAmount || 0), 0);
            document.getElementById('salesToday').textContent = '₱' + totalSales.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }

    } catch (error) {
        console.error('Error loading dashboard statistics:', error);
    }
}

        // Render inventory cards for the dashboard with pagination
        async function renderDashboardInventory(page = 1) {
            try {
                currentPagination = page;

                // First, get total count
                const countResponse = await fetch('api/inventory.php');
                const countResult = await countResponse.json();
                if (countResult.success) {
                    totalItems = countResult.data.length;
                }

                // Then get paginated data
                const offset = (page - 1) * itemsPerPage;
                const response = await fetch(`api/inventory.php?limit=${itemsPerPage}&offset=${offset}`);
                const result = await response.json();

                if (result.success && result.data && result.data.length > 0) {
                    const items = result.data;
                    const grid = document.getElementById('inventoryGrid');
                    grid.innerHTML = '';

                    const today = new Date();

                    items.forEach(item => {
                        const expDate = new Date(item.expirationDate);
                        const daysToExpiration = Math.round((expDate - today) / (1000 * 60 * 60 * 24));
                        const isLowStock = item.status && item.status.includes('Low Stock');
                        const isExpiring = daysToExpiration <= 100 && daysToExpiration >= 0;

                        let cardClass = 'inventory-card';
                        let badgeHTML = '';

                        if (isLowStock) {
                            cardClass += ' low-stock';
                            badgeHTML = '<span class="inventory-badge low">LOW STOCK</span>';
                        } else if (isExpiring) {
                            cardClass += ' expiring';
                            badgeHTML = '<span class="inventory-badge expiring">EXPIRING SOON</span>';
                        }

                        const card = document.createElement('div');
                        card.className = cardClass;

                        // Handle supplier display - show name or "Not specified"
                        const supplierDisplay = (item.supplier && item.supplier !== '0' && item.supplier.trim() !== '')
                            ? item.supplier
                            : 'Not specified';

                        card.innerHTML = `
                            <div class="inventory-header">
                                <div>
                                    <div class="inventory-title">${item.brand}</div>
                                    <div class="inventory-generic">${item.genericName}</div>
                                </div>
                                ${badgeHTML}
                            </div>
                            <div class="inventory-details">
                                <div class="inventory-detail">
                                    <div class="inventory-detail-label">Stock</div>
                                    <div class="inventory-detail-value">${item.stock} units</div>
                                </div>
                                <div class="inventory-detail">
                                    <div class="inventory-detail-label">Price</div>
                                    <div class="inventory-detail-value">₱${parseFloat(item.price).toFixed(2)}</div>
                                </div>
                                <div class="inventory-detail">
                                    <div class="inventory-detail-label">Expires</div>
                                    <div class="inventory-detail-value">${new Date(item.expirationDate).toLocaleDateString()}</div>
                                </div>
                                <div class="inventory-detail">
                                    <div class="inventory-detail-label">Supplier</div>
                                    <div class="inventory-detail-value">${supplierDisplay}</div>
                                </div>
                            </div>
                        `;
                        grid.appendChild(card);
                    });

                    // Generate pagination
                    generatePagination();
                } else {
                    document.getElementById('inventoryGrid').innerHTML = `
                        <div class="empty-state" style="grid-column: 1 / -1;">
                            <i class="fas fa-box-open"></i>
                            <p>No inventory items yet</p>
                        </div>
                    `;
                    document.getElementById('pagination').innerHTML = '';
                }
            } catch (error) {
                console.error('Error loading inventory:', error);
            }
        }

        // Generate pagination controls
        function generatePagination() {
            const totalPages = Math.ceil(totalItems / itemsPerPage);
            const pagination = document.getElementById('pagination');
            pagination.innerHTML = '';

            if (totalPages <= 1) return;

            // Previous button
            const prevBtn = document.createElement('a');
            prevBtn.href = '#';
            prevBtn.textContent = '← Prev';
            prevBtn.className = currentPagination === 1 ? 'disabled' : '';
            prevBtn.onclick = (e) => {
                e.preventDefault();
                if (currentPagination > 1) renderDashboardInventory(currentPagination - 1);
            };
            pagination.appendChild(prevBtn);

            // Page numbers (show max 5 pages around current)
            const startPage = Math.max(1, currentPagination - 2);
            const endPage = Math.min(totalPages, currentPagination + 2);

            for (let i = startPage; i <= endPage; i++) {
                const pageBtn = document.createElement('a');
                pageBtn.href = '#';
                pageBtn.textContent = i;
                pageBtn.className = i === currentPagination ? 'active' : '';
                pageBtn.onclick = (e) => {
                    e.preventDefault();
                    renderDashboardInventory(i);
                };
                pagination.appendChild(pageBtn);
            }

            // Next button
            const nextBtn = document.createElement('a');
            nextBtn.href = '#';
            nextBtn.textContent = 'Next →';
            nextBtn.className = currentPagination === totalPages ? 'disabled' : '';
            nextBtn.onclick = (e) => {
                e.preventDefault();
                if (currentPagination < totalPages) renderDashboardInventory(currentPagination + 1);
            };
            pagination.appendChild(nextBtn);
        }

// Load recent activity (sales transactions)
async function loadRecentActivity() {
    try {
        const response = await fetch('api/sales.php?limit=5');
        const result = await response.json();

        if (result.success && result.data && result.data.length > 0) {
            const activityList = document.getElementById('activityList');
            activityList.innerHTML = '';

            result.data.slice(0, 5).forEach(sale => {
                const li = document.createElement('li');
                li.className = 'activity-item';
                li.innerHTML = `
                    <div class="activity-icon sale">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-title">Sale Transaction - ₱${parseFloat(sale.totalAmount).toFixed(2)}</div>
                        <div class="activity-time">${new Date(sale.createdAt).toLocaleString()}</div>
                    </div>
                `;
                activityList.appendChild(li);
            });
        }
    } catch (error) {
        console.error('Error loading activity:', error);
    }
}

// Load alerts (low stock and expiring items)
async function loadAlerts() {
    try {
        const response = await fetch('api/inventory.php');
        const result = await response.json();

        if (result.success && result.data) {
            const items = result.data;
            const today = new Date();
            const alertsList = document.getElementById('alertsList');
            alertsList.innerHTML = '';

            let alertCount = 0;

            items.forEach(item => {
                const expDate = new Date(item.expirationDate);
                const daysToExpiration = Math.round((expDate - today) / (1000 * 60 * 60 * 24));

                if (item.stock < 20 && alertCount < 5) {
                    const div = document.createElement('div');
                    div.className = 'alert-item critical';
                    div.innerHTML = `
                        <i class="fas fa-exclamation-circle"></i>
                        <div class="alert-content">
                            <div class="alert-title">Low Stock: ${item.brand}</div>
                            <div class="alert-description">Only ${item.stock} units remaining</div>
                        </div>
                    `;
                    alertsList.appendChild(div);
                    alertCount++;
                } else if (daysToExpiration <= 30 && daysToExpiration >= 0 && alertCount < 5) {
                    const div = document.createElement('div');
                    div.className = 'alert-item';
                    div.innerHTML = `
                        <i class="fas fa-clock"></i>
                        <div class="alert-content">
                            <div class="alert-title">Expiring Soon: ${item.brand}</div>
                            <div class="alert-description">Expires in ${daysToExpiration} days</div>
                        </div>
                    `;
                    alertsList.appendChild(div);
                    alertCount++;
                }
            });

            if (alertCount === 0) {
                alertsList.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-check-circle"></i>
                        <p>No alerts</p>
                    </div>
                `;
            }
        }
    } catch (error) {
        console.error('Error loading alerts:', error);
    }
}

// Auto-logout functionality disabled
// No automatic session timeout or browser close logout

    </script>
</body>
</html>