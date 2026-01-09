<?php
// Get user information
$userName = $_SESSION['fullName'] ?? $_SESSION['username'] ?? 'User';
$userRoles = $_SESSION['roles'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suppliers & Supply Management - Echinacea Pharmacy MIS</title>
    <link rel="stylesheet" href="css/Suppliers.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <?php include 'sidebar.php'; ?>
    <div class="user-role-badge" style="margin: 20px 0 0 0;">
        <i class="fas fa-user-shield"></i>
        <?php if (!empty($userRoles)): ?>
            <?php foreach ($userRoles as $role): ?>
                <span class="role-badge"><?php echo htmlspecialchars(ucfirst($role)); ?></span>
            <?php endforeach; ?>
        <?php else: ?>
            <span class="role-badge">User</span>
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

    <main class="page-outer-box">
        <!-- Statistics Cards -->
        <div class="stats-container">
            <div class="stat-card blue">
                <div class="stat-header">
                    <div class="stat-title">Total Suppliers</div>
                    <div class="stat-icon"><i class="fas fa-building"></i></div>
                </div>
                <div class="stat-value" id="totalSuppliers">0</div>
                <div class="stat-footer">Active suppliers</div>
            </div>

            <div class="stat-card green">
                <div class="stat-header">
                    <div class="stat-title">Deliveries This Month</div>
                    <div class="stat-icon"><i class="fas fa-box-open"></i></div>
                </div>
                <div class="stat-value" id="deliveriesMonth">0</div>
                <div class="stat-footer">Supply deliveries</div>
            </div>

            <div class="stat-card orange">
                <div class="stat-header">
                    <div class="stat-title">Pending Deliveries</div>
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                </div>
                <div class="stat-value" id="pendingDeliveries">0</div>
                <div class="stat-footer">Awaiting delivery</div>
            </div>

            <div class="stat-card purple">
                <div class="stat-header">
                    <div class="stat-title">Total Value (Month)</div>
                    <div class="stat-icon"><i class="fas fa-peso-sign"></i></div>
                </div>
                <div class="stat-value" id="totalValue">₱0</div>
                <div class="stat-footer">Supplies received</div>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="tab-navigation">
            <button class="tab-btn active" onclick="switchTab('suppliers')">
                <i class="fas fa-building"></i> Suppliers
            </button>
            <button class="tab-btn" onclick="switchTab('deliveries')">
                <i class="fas fa-truck"></i> Supply Deliveries
            </button>
        </div>

        <!-- Suppliers Tab -->
        <div id="suppliersTab" class="tab-content active">
            <!-- Search and Filter -->
            <div class="search-filter-section">
                <div class="search-filter-grid">
                    <div class="input-group">
                        <label><i class="fas fa-search"></i> Search Suppliers</label>
                        <input type="text" id="supplierSearch" placeholder="Search by name, contact, or email...">
                    </div>
                    <div class="input-group">
                        <label><i class="fas fa-filter"></i> Status</label>
                        <select id="supplierStatusFilter">
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="input-group">
                        <label>&nbsp;</label>
                        <button class="btn-secondary" onclick="filterSuppliers()">
                            <i class="fas fa-sync-alt"></i> Apply
                        </button>
                    </div>
                </div>
            </div>

            <!-- Suppliers Table -->
            <div class="table-section">
                <div class="section-header">
                    <h2><i class="fas fa-list"></i> Supplier List</h2>
                    <div class="header-actions">
                        <button class="btn-primary" onclick="showSupplierModal()">
                            <i class="fas fa-plus-circle"></i>
                            Add Supplier
                        </button>
                        <button class="btn-primary" onclick="showSupplyModal()">
                            <i class="fas fa-truck"></i>
                            Record Supply Delivery
                        </button>
                    </div>
                    <span id="supplierCount" style="color: #64748b; font-size: 14px;">0 suppliers</span>
                </div>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Supplier Name</th>
                            <th>Contact Person</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Address</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="suppliersTableBody">
                        <tr>
                            <td colspan="8" class="empty-state">
                                <i class="fas fa-building"></i>
                                <p>Loading suppliers...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Deliveries Tab -->
        <div id="deliveriesTab" class="tab-content">
            <!-- Search and Filter -->
            <div class="search-filter-section">
                <div class="search-filter-grid">
                    <div class="input-group">
                        <label><i class="fas fa-search"></i> Search Deliveries</label>
                        <input type="text" id="deliverySearch" placeholder="Search by supplier, product, or batch...">
                    </div>
                    <div class="input-group">
                        <label><i class="fas fa-filter"></i> Status</label>
                        <select id="deliveryStatusFilter">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="delivered">Delivered</option>
                        </select>
                    </div>
                    <div class="input-group">
                        <label>&nbsp;</label>
                        <button class="btn-secondary" onclick="filterDeliveries()">
                            <i class="fas fa-sync-alt"></i> Apply
                        </button>
                    </div>
                </div>
            </div>

            <!-- Deliveries Table -->
            <div class="table-section">
                <div class="section-header">
                    
                    <h2><i class="fas fa-list"></i> Supply Deliveries</h2>
                    <div class="header-actions">
                        <button class="btn-primary" onclick="showSupplierModal()">
                            <i class="fas fa-plus-circle"></i>
                            Add Supplier
                        </button>
                        <button class="btn-primary" onclick="showSupplyModal()">
                            <i class="fas fa-truck"></i>
                            Record Supply Delivery
                        </button>
                    </div>
                    <span id="deliveryCount" style="color: #64748b; font-size: 14px;">0 deliveries</span>
                </div>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Delivery Date</th>
                            <th>Delivered Date</th>
                            <th>Supplier</th>
                            <th>Product</th>
                            <th>Batch No</th>
                            <th>Quantity</th>
                            <th>Total Cost</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="deliveriesTableBody">
                        <tr>
                            <td colspan="10" class="empty-state">
                                <i class="fas fa-truck"></i>
                                <p>Loading deliveries...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Supplier Modal -->
    <div id="supplierModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="supplierModalTitle">Add New Supplier</h2>
                <span class="close" onclick="closeSupplierModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="supplierForm" onsubmit="event.preventDefault(); submitSupplier();">
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label for="supplierName"><i class="fas fa-building"></i> Supplier Name *</label>
                            <input type="text" id="supplierName" required>
                        </div>
                        <div class="form-group">
                            <label for="contactPerson"><i class="fas fa-user"></i> Contact Person *</label>
                            <input type="text" id="contactPerson" required>
                        </div>
                        <div class="form-group">
                            <label for="phone"><i class="fas fa-phone"></i> Phone Number *</label>
                            <input type="tel" id="phone" required pattern="[0-9+\-\(\)\s]{7,20}" title="Enter a valid phone number (7-20 characters, numbers, +, -, (), spaces allowed)">
                            <span class="error-message" id="phoneError">Please enter a valid phone number</span>
                        </div>
                        <div class="form-group full-width">
                            <label for="email"><i class="fas fa-envelope"></i> Email *</label>
                            <input type="email" id="email" required pattern="[a-z0-9._%+\-]+@[a-z0-9.\-]+.[a-z]{2,}$" title="Enter a valid email address">
                            <span class="error-message" id="emailError">Please enter a valid email address (e.g., example@gmail.com)</span>
                        </div>
                        <div class="form-group full-width">
                            <label for="address"><i class="fas fa-map-marker-alt"></i> Address *</label>
                            <textarea id="address" required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="supplierStatus"><i class="fas fa-toggle-on"></i> Status *</label>
                            <select id="supplierStatus" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeSupplierModal()">Cancel</button>
                <button type="submit" class="btn-submit" form="supplierForm">
                    <i class="fas fa-save"></i> Save Supplier
                </button>
            </div>
        </div>
    </div>

    <!-- Supply Delivery Modal -->
    <div id="supplyModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="supplyModalTitle">Record Supply Delivery</h2>
                <span class="close" onclick="closeSupplyModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="supplyForm" onsubmit="event.preventDefault(); submitSupply();">
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label for="supplierId"><i class="fas fa-building"></i> Supplier *</label>
                            <select id="supplierId" required onchange="filterProductsBySupplier()">
                                <option value="">Select Supplier</option>
                            </select>
                        </div>
                        <div class="form-group full-width">
                            <label for="productId"><i class="fas fa-pills"></i> Product *</label>
                            <div style="display: flex; gap: 10px; align-items: end;">
                                <select id="productId" required onchange="autoFillProductDetails()" style="flex: 1;">
                                    <option value="">Select a supplier first</option>
                                </select>
                                <button type="button" class="btn-secondary" onclick="supplyNewProduct()" style="font-size: 12px; padding: 8px 12px;">
                                    <i class="fas fa-plus"></i> Supply New Product
                                </button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="batchNo"><i class="fas fa-barcode"></i> Batch Number *</label>
                            <input type="text" id="batchNo" required readonly style="background: #f8fafc;">
                        </div>
                        <div class="form-group">
                            <label for="currentStock"><i class="fas fa-boxes"></i> Current Stock (Reference)</label>
                            <input type="number" id="currentStock" readonly style="background: #f8fafc;">
                        </div>
                        <div class="form-group">
                            <label for="deliveryQuantity"><i class="fas fa-plus-circle"></i> Delivery Quantity *</label>
                            <input type="number" id="deliveryQuantity" required min="1" placeholder="Enter quantity being delivered">
                        </div>
                        <div class="form-group">
                            <label for="unitCost"><i class="fas fa-peso-sign"></i> Unit Price (₱)</label>
                            <input type="number" id="unitCost" readonly step="0.01" style="background: #f8fafc;">
                        </div>
                        <div class="form-group">
                            <label for="totalCost"><i class="fas fa-calculator"></i> Total Value (₱)</label>
                            <input type="number" id="totalCost" readonly step="0.01" style="background: #f8fafc;">
                        </div>
                        <div class="form-group">
                            <label for="deliveryDate"><i class="fas fa-calendar"></i> Delivery Date *</label>
                            <input type="date" id="deliveryDate" required>
                        </div>
                        <div class="form-group">
                            <label for="deliveredDate"><i class="fas fa-calendar-check"></i> Delivered Date</label>
                            <input type="date" id="deliveredDate">
                        </div>
                        <div class="form-group">
                            <label for="deliveryStatus"><i class="fas fa-info-circle"></i> Status *</label>
                            <select id="deliveryStatus" required>
                                <option value="pending">Pending</option>
                                <option value="delivered">Delivered</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="form-group full-width">
                            <label for="notes"><i class="fas fa-sticky-note"></i> Notes</label>
                            <textarea id="notes" placeholder="Additional notes about this delivery..."></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeSupplyModal()">Cancel</button>
                <button type="submit" class="btn-submit" form="supplyForm">
                    <i class="fas fa-save"></i> Save Delivery
                </button>
            </div>
        </div>
    </div>

    </main>

    <!-- Product Modal -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Add New Product</h2>
                <span class="close" onclick="closeProductModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="productForm" onsubmit="event.preventDefault(); submitProduct();">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="brand"><i class="fas fa-tag"></i> Brand Name *</label>
                            <input type="text" id="brand" required>
                        </div>
                        <div class="form-group">
                            <label for="genericName"><i class="fas fa-pills"></i> Generic Name *</label>
                            <input type="text" id="genericName" required>
                        </div>
                        <div class="form-group">
                            <label for="category"><i class="fas fa-folder"></i> Category *</label>
                            <input type="text" id="category" required list="categoryList">
                            <datalist id="categoryList"></datalist>
                        </div>
                        <!-- Stock is managed by deliveries; keep a hidden 0 value for API but remove visible input -->
                        <input type="hidden" id="stock" value="0">
                        <div class="form-group">
                            <label for="lowStockThreshold"><i class="fas fa-exclamation-triangle"></i> Low Stock Threshold *</label>
                            <input type="number" id="lowStockThreshold" required min="0">
                        </div>
                        <div class="form-group">
                            <label for="price"><i class="fas fa-peso-sign"></i> Price *</label>
                            <input type="number" id="price" required min="0" step="0.01">
                        </div>
                        <div class="form-group">
                            <label for="productSupplier"><i class="fas fa-truck"></i> Supplier *</label>
                            <select id="productSupplier" required onchange="updateProductSupplierContact()">
                                <option value="">Select Supplier</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="productContact"><i class="fas fa-phone"></i> Contact Number *</label>
                            <input type="text" id="productContact" required readonly style="background: #f8fafc;">
                        </div>
                        <div class="form-group">
                            <label for="manufacturedDate"><i class="fas fa-calendar"></i> Manufactured Date *</label>
                            <input type="date" id="manufacturedDate" required>
                        </div>
                        <div class="form-group full-width">
                            <label for="expirationDate"><i class="fas fa-calendar-times"></i> Expiration Date *</label>
                            <input type="date" id="expirationDate" required>
                        </div>
                        <div class="form-group">
                            <label for="deliveryQuantity"><i class="fas fa-plus-circle"></i> Delivery Quantity *</label>
                            <input type="number" id="deliveryQuantity" required min="1" placeholder="Enter quantity being delivered">
                        </div>
                        <div class="form-group">
                            <label for="totalValue"><i class="fas fa-peso-sign"></i> Total Value (₱) *</label>
                            <input type="number" id="totalValue" readonly step="0.01" style="background: #f8fafc;" placeholder="Calculated automatically">
                        </div>
                        <div class="form-group">
                            <label for="productDeliveryDate"><i class="fas fa-calendar"></i> Delivery Date *</label>
                            <input type="date" id="productDeliveryDate" required>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeProductModal()">Cancel</button>
                <button type="submit" class="btn-submit" form="productForm">
                    <i class="fas fa-save"></i> Save Product
                </button>
            </div>
        </div>
    </div>

    </div>

    <script src="js/api-adapter.js"></script>
    <script src="js/Pharmacy Sales.js"></script>
</body>
</html>
