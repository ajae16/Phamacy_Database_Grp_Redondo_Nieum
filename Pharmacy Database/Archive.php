<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
} elseif ($_SESSION['userRole'] === 'employee') {
    echo "Access restricted to Admin/Manager only. Redirecting to Dashboard...";
    header("Refresh: 2; url=Dashboard.php");
    exit;
}

// Get user information
$userName = $_SESSION['fullName'] ?? $_SESSION['username'];
$userRole = ucfirst($_SESSION['userRole'] ?? 'User');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archive - Echinacea Pharmacy MIS</title>
    <link rel="stylesheet" href="css/Archive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <main class="page-outer-box">
        <!-- Page Header -->
        <div class="page-header">
            <div class="page-header-content">
                <div class="page-title-section">
                    <h1><i class="fas fa-archive"></i> Archive</h1>
                    <p>View and restore archived inventory, suppliers, and employees</p>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-container">
            <div class="stat-card blue">
                <div class="stat-header">
                    <div class="stat-title">Archived Products</div>
                    <div class="stat-icon"><i class="fas fa-box-open"></i></div>
                </div>
                <div class="stat-value" id="archivedInventoryCount">0</div>
                <div class="stat-footer">Inventory items</div>
            </div>

            <div class="stat-card orange">
                <div class="stat-header">
                    <div class="stat-title">Archived Suppliers</div>
                    <div class="stat-icon"><i class="fas fa-building"></i></div>
                </div>
                <div class="stat-value" id="archivedSuppliersCount">0</div>
                <div class="stat-footer">Supplier records</div>
            </div>

            <div class="stat-card green">
                <div class="stat-header">
                    <div class="stat-title">Archived Employees</div>
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                </div>
                <div class="stat-value" id="archivedEmployeesCount">0</div>
                <div class="stat-footer">Employee records</div>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="tab-navigation">
            <button class="tab-btn active" onclick="switchTab('inventory')">
                <i class="fas fa-boxes"></i> Inventory Archive
            </button>
            <button class="tab-btn" onclick="switchTab('suppliers')">
                <i class="fas fa-truck-loading"></i> Suppliers Archive
            </button>
            <button class="tab-btn" onclick="switchTab('employees')">
                <i class="fas fa-users"></i> Employees Archive
            </button>
        </div>

        <!-- Inventory Archive Tab -->
        <div id="inventoryTab" class="tab-content active">
            <div class="archive-section">
                <div class="section-header">
                    <h2><i class="fas fa-box-open"></i> Archived Inventory</h2>
                    <span id="inventoryCount" style="color: #64748b; font-size: 14px;">0 items</span>
                </div>
                
                <table class="archive-table">
                    <thead>
                        <tr>
                            <th>Product ID</th>
                            <th>Brand</th>
                            <th>Generic Name</th>
                            <th>Category</th>
                            <th>Stock</th>
                            <th>Price</th>
                            <th>Supplier</th>
                            <th>Expiration</th>
                            <th>Archived Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="inventoryTableBody">
                        <tr>
                            <td colspan="10" class="empty-state">
                                <i class="fas fa-box-open"></i>
                                <p>Loading archived inventory...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Suppliers Archive Tab -->
        <div id="suppliersTab" class="tab-content">
            <div class="archive-section">
                <div class="section-header">
                    <h2><i class="fas fa-building"></i> Archived Suppliers</h2>
                    <span id="suppliersCount" style="color: #64748b; font-size: 14px;">0 suppliers</span>
                </div>

                <table class="archive-table">
                    <thead>
                        <tr>
                            <th>Supplier ID</th>
                            <th>Supplier Name</th>
                            <th>Contact Person</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Address</th>
                            <th>Archived Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="suppliersTableBody">
                        <tr>
                            <td colspan="8" class="empty-state">
                                <i class="fas fa-building"></i>
                                <p>Loading archived suppliers...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Employees Archive Tab -->
        <div id="employeesTab" class="tab-content">
            <div class="archive-section">
                <div class="section-header">
                    <h2><i class="fas fa-users"></i> Archived Employees</h2>
                    <span id="employeesCount" style="color: #64748b; font-size: 14px;">0 employees</span>
                </div>

                <table class="archive-table">
                    <thead>
                        <tr>
                            <th>Employee ID</th>
                            <th>Full Name</th>
                            <th>Contact</th>
                            <th>Role</th>
                            <th>Schedule</th>
                            <th>Birthday</th>
                            <th>Address</th>
                            <th>Archived Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="employeesTableBody">
                        <tr>
                            <td colspan="9" class="empty-state">
                                <i class="fas fa-users"></i>
                                <p>Loading archived employees...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    </main>
    </div>

    <script src="js/api-adapter.js"></script>
    <script>
        let archivedInventory = [];
        let archivedSuppliers = [];
        let archivedEmployees = [];

        // Load archived data on page load
        document.addEventListener('DOMContentLoaded', async function() {
            await loadArchivedInventory();
            await loadArchivedSuppliers();
            await loadArchivedEmployees();
            updateStatistics();

        });

        // Switch tabs
        function switchTab(tab) {
            // Update tab buttons
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            event.target.closest('.tab-btn').classList.add('active');

            // Update tab content
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            if (tab === 'inventory') {
                document.getElementById('inventoryTab').classList.add('active');
            } else if (tab === 'suppliers') {
                document.getElementById('suppliersTab').classList.add('active');
            } else if (tab === 'employees') {
                document.getElementById('employeesTab').classList.add('active');
            }
        }

        // Update statistics
        function updateStatistics() {
            document.getElementById('archivedInventoryCount').textContent = archivedInventory.length;
            document.getElementById('archivedSuppliersCount').textContent = archivedSuppliers.length;
            document.getElementById('archivedEmployeesCount').textContent = archivedEmployees.length;
        }

        // ============================================
        // INVENTORY ARCHIVE
        // ============================================

        // Load archived inventory
        async function loadArchivedInventory() {
            try {
                const response = await fetch('api/archive.php?type=inventory');
                const result = await response.json();

                if (result.success && result.data) {
                    archivedInventory = result.data;
                    displayArchivedInventory(archivedInventory);
                } else {
                    archivedInventory = [];
                    displayArchivedInventory([]);
                }
            } catch (error) {
                console.error('Error loading archived inventory:', error);
                archivedInventory = [];
                displayArchivedInventory([]);
            }
        }

        // Display archived inventory
        function displayArchivedInventory(items) {
            const tableBody = document.getElementById('inventoryTableBody');
            const inventoryCount = document.getElementById('inventoryCount');

            if (items.length === 0) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="10" class="empty-state">
                            <i class="fas fa-box-open"></i>
                            <p>No archived inventory items</p>
                            <small>Deleted inventory items will appear here</small>
                        </td>
                    </tr>
                `;
                inventoryCount.textContent = '0 items';
                return;
            }

            tableBody.innerHTML = '';
            items.forEach(item => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>#${item.id || ''}</td>
                    <td><strong>${item.brand || ''}</strong></td>
                    <td>${item.genericName || ''}</td>
                    <td>${item.category || ''}</td>
                    <td><strong>${item.stock || 0}</strong> units</td>
                    <td>₱${parseFloat(item.price || 0).toFixed(2)}</td>
                    <td>${item.supplier || 'N/A'}</td>
                    <td>${item.expirationDate ? new Date(item.expirationDate).toLocaleDateString() : 'N/A'}</td>
                    <td>${item.archivedAt ? new Date(item.archivedAt).toLocaleDateString() : 'N/A'}</td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn-restore" onclick="restoreInventory('${item.id}')">
                                <i class="fas fa-undo"></i> Restore
                            </button>
                            <button class="btn-delete-permanent" onclick="deleteInventoryPermanent('${item.id}')">
                                <i class="fas fa-trash-alt"></i> Delete
                            </button>
                        </div>
                    </td>
                `;
                tableBody.appendChild(row);
            });

            inventoryCount.textContent = `${items.length} item${items.length !== 1 ? 's' : ''}`;
        }

        // Restore inventory item
        async function restoreInventory(id) {
            if (!confirm('Are you sure you want to restore this inventory item?')) {
                return;
            }

            try {
                const response = await fetch('api/archive.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'restore',
                        type: 'inventory',
                        id: id
                    })
                });

                const result = await response.json();

                if (result.success) {
                    alert('Inventory item restored successfully!');
                    await loadArchivedInventory();
                    updateStatistics();
                } else {
                    alert('Error: ' + (result.message || 'Failed to restore item'));
                }
            } catch (error) {
                console.error('Error restoring inventory:', error);
                alert('Error restoring inventory: ' + error.message);
            }
        }

        // Delete inventory item permanently
        async function deleteInventoryPermanent(id) {
            if (!confirm('Are you sure you want to PERMANENTLY delete this item? This action cannot be undone!')) {
                return;
            }

            if (!confirm('This will permanently remove the item from the database. Are you absolutely sure?')) {
                return;
            }

            try {
                const response = await fetch('api/archive.php', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        type: 'inventory',
                        id: id
                    })
                });

                const result = await response.json();

                if (result.success) {
                    alert('Inventory item permanently deleted!');
                    await loadArchivedInventory();
                    updateStatistics();
                } else {
                    alert('Error: ' + (result.message || 'Failed to delete item'));
                }
            } catch (error) {
                console.error('Error deleting inventory:', error);
                alert('Error deleting inventory: ' + error.message);
            }
        }

        // ============================================
        // SUPPLIERS ARCHIVE
        // ============================================

        // Load archived suppliers
         async function loadArchivedSuppliers() {
            try {
                // Load from localStorage (since suppliers are stored there)
                const suppliersData = localStorage.getItem('archive_suppliers');
                archivedSuppliers = suppliersData ? JSON.parse(suppliersData) : [];
                displayArchivedSuppliers(archivedSuppliers);
            } catch (error) {
                console.error('Error loading archived suppliers:', error);
                archivedSuppliers = [];
                displayArchivedSuppliers([]);
            }
        }

        // Display archived suppliers
        function displayArchivedSuppliers(suppliers) {
            const tableBody = document.getElementById('suppliersTableBody');
            const suppliersCount = document.getElementById('suppliersCount');

            if (suppliers.length === 0) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="8" class="empty-state">
                            <i class="fas fa-building"></i>
                            <p>No archived suppliers</p>
                            <small>Deleted suppliers will appear here</small>
                        </td>
                    </tr>
                `;
                suppliersCount.textContent = '0 suppliers';
                return;
            }

            tableBody.innerHTML = '';
            suppliers.forEach(supplier => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>#${supplier.id || ''}</td>
                    <td><strong>${supplier.name || ''}</strong></td>
                    <td>${supplier.contactPerson || ''}</td>
                    <td>${supplier.phone || ''}</td>
                    <td>${supplier.email || ''}</td>
                    <td>${supplier.address || ''}</td>
                    <td>${supplier.archivedAt ? new Date(supplier.archivedAt).toLocaleDateString() : 'N/A'}</td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn-restore" onclick="restoreSupplier('${supplier.id}')">
                                <i class="fas fa-undo"></i> Restore
                            </button>
                            <button class="btn-delete-permanent" onclick="deleteSupplierPermanent('${supplier.id}')">
                                <i class="fas fa-trash-alt"></i> Delete
                            </button>
                        </div>
                    </td>
                `;
                tableBody.appendChild(row);
            });

            suppliersCount.textContent = `${suppliers.length} supplier${suppliers.length !== 1 ? 's' : ''}`;
        }

        // Restore supplier
        async function restoreSupplier(id) {
            if (!confirm('Are you sure you want to restore this supplier?')) {
                return;
            }

            try {
                // Find supplier in archive
                const archive = JSON.parse(localStorage.getItem('archive_suppliers') || '[]');
                const supplierIndex = archive.findIndex(s => s.id === id);

                if (supplierIndex === -1) {
                    alert('Supplier not found in archive');
                    return;
                }

                const supplier = archive[supplierIndex];

                // Add back to active suppliers
                const suppliers = JSON.parse(localStorage.getItem('pharmacy_suppliers') || '[]');
                suppliers.unshift(supplier);
                localStorage.setItem('pharmacy_suppliers', JSON.stringify(suppliers));

                // Remove from archive
                archive.splice(supplierIndex, 1);
                localStorage.setItem('archive_suppliers', JSON.stringify(archive));

                alert('Supplier restored successfully!');
                await loadArchivedSuppliers();
                updateStatistics();
            } catch (error) {
                console.error('Error restoring supplier:', error);
                alert('Error restoring supplier: ' + error.message);
            }
        }

        // Delete supplier permanently
        async function deleteSupplierPermanent(id) {
            if (!confirm('Are you sure you want to PERMANENTLY delete this supplier? This action cannot be undone!')) {
                return;
            }

            if (!confirm('This will permanently remove the supplier from storage. Are you absolutely sure?')) {
                return;
            }

            try {
                const archive = JSON.parse(localStorage.getItem('archive_suppliers') || '[]');
                const updatedArchive = archive.filter(s => s.id !== id);
                localStorage.setItem('archive_suppliers', JSON.stringify(updatedArchive));

                alert('Supplier permanently deleted!');
                await loadArchivedSuppliers();
                updateStatistics();
            } catch (error) {
                console.error('Error deleting supplier:', error);
                alert('Error deleting supplier: ' + error.message);
            }
        }

        // ============================================
        // EMPLOYEES ARCHIVE
        // ============================================

        // Load archived employees
        async function loadArchivedEmployees() {
            try {
                // Load from API (database archive table)
                const response = await fetch('api/archive.php?type=records');
                const result = await response.json();

                if (result.success && result.data) {
                    archivedEmployees = result.data;
                    displayArchivedEmployees(archivedEmployees);
                } else {
                    archivedEmployees = [];
                    displayArchivedEmployees([]);
                }
            } catch (error) {
                console.error('Error loading archived employees:', error);
                archivedEmployees = [];
                displayArchivedEmployees([]);
            }
        }

        // Display archived employees
        function displayArchivedEmployees(employees) {
            const tableBody = document.getElementById('employeesTableBody');
            const employeesCount = document.getElementById('employeesCount');

            if (employees.length === 0) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="9" class="empty-state">
                            <i class="fas fa-users"></i>
                            <p>No archived employees</p>
                            <small>Deleted employees will appear here</small>
                        </td>
                    </tr>
                `;
                employeesCount.textContent = '0 employees';
                return;
            }

            tableBody.innerHTML = '';
            employees.forEach(employee => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>#${employee.id || ''}</td>
                    <td><strong>${employee.fullName || ''}</strong></td>
                    <td>${employee.contact || ''}</td>
                    <td>${employee.role || ''}</td>
                    <td>${employee.schedule || ''}</td>
                    <td>${employee.birthday ? new Date(employee.birthday).toLocaleDateString() : 'N/A'}</td>
                    <td>${employee.address || ''}</td>
                    <td>${employee.archivedAt ? new Date(employee.archivedAt).toLocaleDateString() : 'N/A'}</td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn-restore" onclick="restoreEmployee('${employee.id}')">
                                <i class="fas fa-undo"></i> Restore
                            </button>
                            <button class="btn-delete-permanent" onclick="deleteEmployeePermanent('${employee.id}')">
                                <i class="fas fa-trash-alt"></i> Delete
                            </button>
                        </div>
                    </td>
                `;
                tableBody.appendChild(row);
            });

            employeesCount.textContent = `${employees.length} employee${employees.length !== 1 ? 's' : ''}`;
        }

        // Restore employee
        async function restoreEmployee(id) {
            if (!confirm('Are you sure you want to restore this employee?')) {
                return;
            }

            try {
                const response = await fetch('api/archive.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'restore',
                        type: 'records',
                        id: id
                    })
                });

                const result = await response.json();

                if (result.success) {
                    alert('Employee restored successfully!');
                    await loadArchivedEmployees();
                    updateStatistics();
                } else {
                    alert('Error: ' + (result.message || 'Failed to restore employee'));
                }
            } catch (error) {
                console.error('Error restoring employee:', error);
                alert('Error restoring employee: ' + error.message);
            }
        }

        // Delete employee permanently
        async function deleteEmployeePermanent(id) {
            if (!confirm('Are you sure you want to PERMANENTLY delete this employee? This action cannot be undone!')) {
                return;
            }

            if (!confirm('This will permanently remove the employee from the database. Are you absolutely sure?')) {
                return;
            }

            try {
                const response = await fetch('api/archive.php', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        type: 'records',
                        id: id
                    })
                });

                const result = await response.json();

                if (result.success) {
                    alert('Employee permanently deleted!');
                    await loadArchivedEmployees();
                    updateStatistics();
                } else {
                    alert('Error: ' + (result.message || 'Failed to delete employee'));
                }
            } catch (error) {
                console.error('Error deleting employee:', error);
                alert('Error deleting employee: ' + error.message);
            }
        }

        // Make functions globally accessible
        window.switchTab = switchTab;
        window.restoreInventory = restoreInventory;
        window.deleteInventoryPermanent = deleteInventoryPermanent;
        window.restoreSupplier = restoreSupplier;
        window.deleteSupplierPermanent = deleteSupplierPermanent;
        window.restoreEmployee = restoreEmployee;
        window.deleteEmployeePermanent = deleteEmployeePermanent;
    </script>
</body>
</html>