<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - Echinacea Pharmacy MIS</title>
    <link rel="stylesheet" href="css/Inventory.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>
            <!-- Page Header -->
    <main class="page-outer-box">


        <!-- Statistics Cards -->
        <div class="stats-container">
            <div class="stat-card blue">
                <div class="stat-header">
                    <div class="stat-title">Total Products</div>
                    <div class="stat-icon"><i class="fas fa-pills"></i></div>
                </div>
                <div class="stat-value" id="totalProducts">0</div>
                <div class="stat-footer">In inventory</div>
            </div>

            <div class="stat-card green">
                <div class="stat-header">
                    <div class="stat-title">Total Value</div>
                    <div class="stat-icon"><i class="fas fa-peso-sign"></i></div>
                </div>
                <div class="stat-value" id="totalValue">₱0</div>
                <div class="stat-footer">Inventory worth</div>
            </div>

            <div class="stat-card orange">
                <div class="stat-header">
                    <div class="stat-title">Low Stock</div>
                    <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
                </div>
                <div class="stat-value" id="lowStock">0</div>
                <div class="stat-footer">Need restocking</div>
            </div>

            <div class="stat-card red">
                <div class="stat-header">
                    <div class="stat-title">Expiring Soon</div>
                    <div class="stat-icon"><i class="fas fa-calendar-times"></i></div>
                </div>
                <div class="stat-value" id="expiringSoon">0</div>
                <div class="stat-footer">Within 100 days</div>
            </div>
        </div>

        <!-- Search and Filter -->
        <div class="search-filter-section">
            <div class="search-filter-grid">
                <div class="input-group">
                    <label><i class="fas fa-search"></i> Search Products</label>
                    <input type="text" id="searchInput" placeholder="Search by brand, generic name, or category...">
                </div>
                <div class="input-group">
                    <label><i class="fas fa-filter"></i> Category</label>
                    <select id="categoryFilter">
                        <option value="">All Categories</option>
                    </select>
                </div>
                <div class="input-group">
                    <label><i class="fas fa-sort"></i> Stock Status</label>
                    <select id="stockFilter">
                        <option value="">All Stock</option>
                        <option value="low">Low Stock</option>
                        <option value="expiring">Expiring Soon</option>
                        <option value="no">No Stock</option>
                        <option value="expired">Expired</option>
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

        <!-- Inventory Table -->
        <div class="inventory-section">
            <div class="section-header">
                <h2><i class="fas fa-list"></i> Product List</h2>
                <div class="header-actions">
                <button class="btn-primary" onclick="showCreateModal()">
                    <i class="fas fa-plus-circle"></i>
                    Add New Product
                </button>
                </div>
                <span id="productCount" style="color: #64748b; font-size: 14px;">0 products</span>
            </div>
            
            <div id="tableContainer">
                <table class="inventory-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Brand Name</th>
                            <th>Generic Name</th>
                            <th>Supplier</th>
                            <th>Category</th>
                            <th>Stock</th>
                            <th>Price</th>
                            <th>Expiration</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <tr>
                            <td colspan="9" class="empty-state">
                                <i class="fas fa-box-open"></i>
                                <p>Loading inventory...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Product Modal -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Add New Product</h2>
                <span class="close" onclick="closeModal()">&times;</span>
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
                            <label for="supplierId"><i class="fas fa-truck"></i> Supplier *</label>
                            <select id="supplierId" required onchange="updateSupplierContact()">
                                <option value="">Select Supplier</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="contact"><i class="fas fa-phone"></i> Contact Number *</label>
                            <input type="text" id="contact" required readonly style="background: #f8fafc;">
                        </div>
                        <div class="form-group">
                            <label for="manufacturedDate"><i class="fas fa-calendar"></i> Manufactured Date *</label>
                            <input type="date" id="manufacturedDate" required>
                        </div>
                        <div class="form-group full-width">
                            <label for="expirationDate"><i class="fas fa-calendar-times"></i> Expiration Date *</label>
                            <input type="date" id="expirationDate" required>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn-submit" form="productForm">
                    <i class="fas fa-save"></i> Save Product
                </button>
            </div>
        </div>
    </div>

    <script src="js/api-adapter.js"></script>
    <script>
        let editingId = null;
        let allInventory = [];
        let allSuppliers = [];

        // Load inventory on page load
        document.addEventListener('DOMContentLoaded', async function() {
            await loadInventory();
            await loadSuppliers();
            await populateCategoryFilter();
            setupEventListeners();
        });

        // Setup event listeners
        function setupEventListeners() {
            document.getElementById('searchInput').addEventListener('input', applyFilters);
            document.getElementById('categoryFilter').addEventListener('change', applyFilters);
            document.getElementById('stockFilter').addEventListener('change', applyFilters);
        }

        // Load suppliers from API
        async function loadSuppliers() {
            try {
                allSuppliers = await readSuppliers();
                console.log('Loaded suppliers:', allSuppliers.length);
            } catch (error) {
                console.error('Error loading suppliers:', error);
                allSuppliers = [];
            }
        }

        // Populate supplier dropdown (show name, submit supplierId)
        function populateSupplierDropdown() {
            const supplierSelect = document.getElementById('supplierId');
            if (!supplierSelect) return;

            // Clear existing options except the first one
            supplierSelect.innerHTML = '<option value="">Select Supplier</option>';

            // Normalize allSuppliers: ensure .id and .supplierId are always set
            allSuppliers = (allSuppliers || []).map(s => ({
                ...s,
                id: s.supplierId || s.id || '',
                supplierId: s.supplierId || s.id || ''
            }));

            // Filter only active suppliers
            const activeSuppliers = allSuppliers.filter(s => s.status === 'active');

            if (activeSuppliers.length === 0) {
                const option = document.createElement('option');
                option.value = '';
                option.textContent = 'No suppliers available (Add in Suppliers page)';
                option.disabled = true;
                supplierSelect.appendChild(option);
                return;
            }

            // Add suppliers to dropdown (value = supplierId, text = name)
            activeSuppliers.forEach(supplier => {
                const option = document.createElement('option');
                option.value = supplier.supplierId || supplier.id || '';
                option.setAttribute('data-name', supplier.name || supplier.supplierName || '');
                option.setAttribute('data-phone', supplier.phone || '');
                option.setAttribute('data-contact', supplier.contactPerson || '');
                option.textContent = supplier.name || supplier.supplierName || '';
                supplierSelect.appendChild(option);
            });
            // Debug: log supplier dropdown values
            console.log('Supplier dropdown options:', Array.from(supplierSelect.options).map(o => o.value));
        }

        // Update contact number when supplier is selected
        function updateSupplierContact() {
            const supplierSelect = document.getElementById('supplierId');
            const contactInput = document.getElementById('contact');

            if (!supplierSelect || !contactInput) return;

            const selectedOption = supplierSelect.options[supplierSelect.selectedIndex];
            const phone = selectedOption.getAttribute('data-phone') || '';

            contactInput.value = phone;
        }

        // Populate category datalist
        async function populateCategoryList() {
            const datalist = document.getElementById('categoryList');
            if (!datalist) return;

            try {
                const response = await fetch('api/inventory.php?unique=categories');
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const result = await response.json();
                if (result.success && result.data) {
                    datalist.innerHTML = '';
                    result.data.forEach(category => {
                        const option = document.createElement('option');
                        option.value = category.displayValue || category.value;
                        datalist.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Error loading categories:', error);
            }
        }

        // Load and display inventory
        async function loadInventory() {
            try {
                const inventory = await readInventory();
                allInventory = inventory;
                updateStatistics(inventory);
                displayInventory(inventory);
            } catch (error) {
                console.error('Error loading inventory:', error);
                document.getElementById('tableBody').innerHTML = `
                    <tr>
                        <td colspan="9" class="empty-state">
                            <i class="fas fa-exclamation-circle"></i>
                            <p>Error loading inventory</p>
                        </td>
                    </tr>
                `;
            }
        }

        // Update statistics
        function updateStatistics(inventory) {
            const today = new Date();
            const totalProducts = inventory.length;
            const totalValue = inventory.reduce((sum, item) => sum + (item.stock * item.price), 0);
            const lowStock = inventory.filter(item => item.stock < 20).length;
            const expiringSoon = inventory.filter(item => {
                const expDate = new Date(item.expirationDate);
                const daysToExpiration = Math.round((expDate - today) / (1000 * 60 * 60 * 24));
                return daysToExpiration <= 100 && daysToExpiration >= 0;
            }).length;

            document.getElementById('totalProducts').textContent = totalProducts;
            document.getElementById('totalValue').textContent = '₱' + totalValue.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            document.getElementById('lowStock').textContent = lowStock;
            document.getElementById('expiringSoon').textContent = expiringSoon;
        }

        // Display inventory in table
        function displayInventory(inventory) {
            const tableBody = document.getElementById('tableBody');

            if (inventory.length === 0) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="9" class="empty-state">
                            <i class="fas fa-box-open"></i>
                            <p>No products found</p>
                            <button class="btn-primary" onclick="showCreateModal()" style="margin-top: 15px;">
                                <i class="fas fa-plus-circle"></i> Add Your First Product
                            </button>
                        </td>
                    </tr>
                `;
                document.getElementById('productCount').textContent = '0 products';
                return;
            }

            const today = new Date();
            tableBody.innerHTML = '';

            inventory.forEach(item => {
                const row = document.createElement('tr');

                // Calculate expiration days
                const expDate = new Date(item.expirationDate);
                const daysToExpiration = Math.round((expDate - today) / (1000 * 60 * 60 * 24));

                // Determine row class and badge using API status
                let rowClass = '';
                let status = item.status || 'Good';
                let statusClass = 'normal';

                if (status === 'No Stock') {
                    statusClass = 'no-stock';
                } else if (status.includes('Low Stock')) {
                    statusClass = 'low';
                    rowClass = 'low-stock';
                } else if (status.includes('Expiring Soon') || status === 'Expired') {
                    statusClass = 'expiring';
                    rowClass = 'expiring-soon';
                }

                let badgeHTML = '<span class="badge ' + statusClass + '">' + status + '</span>';

                row.className = rowClass;
                row.innerHTML = `
                    <td>#${item.productId || ''}</td>
                    <td><strong>${item.brand || ''}</strong></td>
                    <td>${item.genericName || ''}</td>
                    <td>${item.supplierName || item.supplier || ''}</td>
                    <td>${item.category || ''}</td>
                    <td><strong>${item.stock || 0}</strong> units</td>
                    <td>₱${parseFloat(item.price || 0).toFixed(2)}</td>
                    <td>${new Date(item.expirationDate).toLocaleDateString()}</td>
                    <td>${badgeHTML}</td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn-edit" onclick="editProduct('${item.productId}')">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="btn-delete" onclick="deleteProduct('${item.productId}')">
                                <i class="fas fa-trash"></i> Archive
                            </button>
                        </div>
                    </td>
                `;
                
                tableBody.appendChild(row);
            });

            document.getElementById('productCount').textContent = `${inventory.length} product${inventory.length !== 1 ? 's' : ''}`;
        }

        // Apply filters
        function applyFilters() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const categoryFilter = document.getElementById('categoryFilter').value.toLowerCase();
            const stockFilter = document.getElementById('stockFilter').value;

            let filtered = allInventory.filter(item => {
                // Search filter
                const matchesSearch = !searchTerm || 
                    (item.brand && item.brand.toLowerCase().includes(searchTerm)) ||
                    (item.genericName && item.genericName.toLowerCase().includes(searchTerm)) ||
                    (item.category && item.category.toLowerCase().includes(searchTerm));

                // Category filter
                const matchesCategory = !categoryFilter || 
                    (item.category && item.category.toLowerCase() === categoryFilter);

                // Stock status filter
                let matchesStock = true;
                if (stockFilter === 'low') {
                    matchesStock = item.status && item.status.includes('Low Stock');
                } else if (stockFilter === 'expiring') {
                    matchesStock = item.status && (item.status.includes('Expiring Soon') || item.status === 'Expired');
                } else if (stockFilter === 'no') {
                    matchesStock = item.status && item.status === 'No Stock';
                } else if (stockFilter === 'expired') {
                    matchesStock = item.status && item.status === 'Expired';
                }

                return matchesSearch && matchesCategory && matchesStock;
            });

            displayInventory(filtered);
        }

        // Populate category filter
        async function populateCategoryFilter() {
            const categories = [...new Set(allInventory.map(item => item.category).filter(Boolean))];
            const select = document.getElementById('categoryFilter');
            
            categories.forEach(category => {
                const option = document.createElement('option');
                option.value = category.toLowerCase();
                option.textContent = category;
                select.appendChild(option);
            });
        }

        // Show create modal
        function showCreateModal() {
            editingId = null;
            document.getElementById('modalTitle').innerText = 'Add New Product';
            document.getElementById('productForm').reset();
            populateSupplierDropdown();
            populateCategoryList();
            document.getElementById('productModal').style.display = 'block';
        }

        // Edit product
        async function editProduct(id) {
            const items = await readInventory(id);
            const item = items[0];
            
            if (item) {
                editingId = id;
                document.getElementById('modalTitle').innerText = 'Update Product';
                
                // Populate supplier dropdown first
                populateSupplierDropdown();
                
                // Then set values
                document.getElementById('brand').value = item.brand || '';
                document.getElementById('genericName').value = item.genericName || '';
                document.getElementById('category').value = item.category || '';
                document.getElementById('stock').value = item.stock || '';
                document.getElementById('price').value = item.price || '';
                // Set supplierId (dropdown value), fallback to supplierName if needed
                document.getElementById('supplierId').value = item.supplierId || '';
                document.getElementById('contact').value = item.contact || '';
                document.getElementById('manufacturedDate').value = item.manufacturedDate || '';
                document.getElementById('expirationDate').value = item.expirationDate || '';
                document.getElementById('productModal').style.display = 'block';
            }
        }

        // Validation functions
        function validateProductForm() {
            let isValid = true;
            const errors = [];

            // Product name pattern (letters, numbers, spaces, common punctuation)
            const productNamePattern = /^[a-zA-Z0-9\s\-.,()\/&+]+$/;
            
            // Phone validation
            const phonePattern = /^\+?[0-9\s\-()]{10,20}$/;

            // Validate Brand Name
            const brand = document.getElementById('brand').value.trim();
            if (!brand) {
                errors.push('Brand Name is required');
                isValid = false;
            } else if (brand.length < 2) {
                errors.push('Brand Name must be at least 2 characters');
                isValid = false;
            } else if (brand.length > 100) {
                errors.push('Brand Name must not exceed 100 characters');
                isValid = false;
            } else if (!productNamePattern.test(brand)) {
                errors.push('Brand Name contains invalid characters');
                isValid = false;
            }

            // Validate Generic Name
            const genericName = document.getElementById('genericName').value.trim();
            if (!genericName) {
                errors.push('Generic Name is required');
                isValid = false;
            } else if (genericName.length < 2) {
                errors.push('Generic Name must be at least 2 characters');
                isValid = false;
            } else if (genericName.length > 100) {
                errors.push('Generic Name must not exceed 100 characters');
                isValid = false;
            } else if (!productNamePattern.test(genericName)) {
                errors.push('Generic Name contains invalid characters');
                isValid = false;
            }

            // Validate Category
            const category = document.getElementById('category').value.trim();
            if (!category) {
                errors.push('Category is required');
                isValid = false;
            } else if (category.length < 2) {
                errors.push('Category must be at least 2 characters');
                isValid = false;
            } else if (category.length > 50) {
                errors.push('Category must not exceed 50 characters');
                isValid = false;
            }

            // Validate Stock
            const stock = document.getElementById('stock').value;
            if (stock === '' || stock === null) {
                errors.push('Stock Quantity is required');
                isValid = false;
            } else if (stock < 0) {
                errors.push('Stock Quantity cannot be negative');
                isValid = false;
            } else if (stock > 999999) {
                errors.push('Stock Quantity must not exceed 999,999');
                isValid = false;
            } else if (!Number.isInteger(parseFloat(stock))) {
                errors.push('Stock Quantity must be a whole number');
                isValid = false;
            }

            // Validate Low Stock Threshold
            const lowStockThreshold = document.getElementById('lowStockThreshold').value;
            if (lowStockThreshold === '' || lowStockThreshold === null) {
                errors.push('Low Stock Threshold is required');
                isValid = false;
            } else if (lowStockThreshold < 0) {
                errors.push('Low Stock Threshold cannot be negative');
                isValid = false;
            } else if (lowStockThreshold > 999999) {
                errors.push('Low Stock Threshold must not exceed 999,999');
                isValid = false;
            } else if (!Number.isInteger(parseFloat(lowStockThreshold))) {
                errors.push('Low Stock Threshold must be a whole number');
                isValid = false;
            }

            // Validate Price
            const price = document.getElementById('price').value;
            if (price === '' || price === null) {
                errors.push('Price is required');
                isValid = false;
            } else if (price <= 0) {
                errors.push('Price must be greater than 0');
                isValid = false;
            } else if (price > 9999999.99) {
                errors.push('Price must not exceed ₱9,999,999.99');
                isValid = false;
            }

            // Validate Supplier
            const supplier = document.getElementById('supplierId').value;
            if (!supplier) {
                errors.push('Please select a Supplier');
                isValid = false;
            }

            // Validate Contact
            const contact = document.getElementById('contact').value.trim();
            if (!contact) {
                errors.push('Contact Number is required');
                isValid = false;
            } else if (!phonePattern.test(contact)) {
                errors.push('Contact Number format is invalid (10-20 digits)');
                isValid = false;
            }

            // Validate Manufactured Date
            const manufacturedDate = document.getElementById('manufacturedDate').value;
            if (!manufacturedDate) {
                errors.push('Manufactured Date is required');
                isValid = false;
            } else {
                const mfgDate = new Date(manufacturedDate);
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                
                // Manufactured date shouldn't be in the future
                if (mfgDate > today) {
                    errors.push('Manufactured Date cannot be in the future');
                    isValid = false;
                }
                
                // Manufactured date shouldn't be too old (more than 20 years)
                const twentyYearsAgo = new Date(today);
                twentyYearsAgo.setFullYear(twentyYearsAgo.getFullYear() - 20);
                
                if (mfgDate < twentyYearsAgo) {
                    errors.push('Manufactured Date seems too old (more than 20 years ago)');
                    isValid = false;
                }
            }

            // Validate Expiration Date
            const expirationDate = document.getElementById('expirationDate').value;
            if (!expirationDate) {
                errors.push('Expiration Date is required');
                isValid = false;
            } else {
                const expDate = new Date(expirationDate);
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                
                // Expiration date shouldn't be too far in the future (more than 50 years)
                const fiftyYearsAhead = new Date(today);
                fiftyYearsAhead.setFullYear(fiftyYearsAhead.getFullYear() + 50);
                
                if (expDate > fiftyYearsAhead) {
                    errors.push('Expiration Date seems too far in the future (more than 50 years)');
                    isValid = false;
                }
            }

            // Validate date logic
            if (manufacturedDate && expirationDate) {
                const mfgDate = new Date(manufacturedDate);
                const expDate = new Date(expirationDate);
                
                if (expDate <= mfgDate) {
                    errors.push('Expiration Date must be after Manufactured Date');
                    isValid = false;
                }
                
                // Check reasonable shelf life (at least 30 days)
                const daysDiff = Math.floor((expDate - mfgDate) / (1000 * 60 * 60 * 24));
                if (daysDiff < 30) {
                    errors.push('Shelf life seems too short (less than 30 days). Please verify dates.');
                    isValid = false;
                }
            }

            // Show errors if any
            if (!isValid) {
                alert('Please fix the following errors:\n\n' + errors.join('\n'));
            }

            return isValid;
        }

        // Submit product
        async function submitProduct() {

            // Validate form first
            if (!validateProductForm()) {
                return;
            }

            const manufacturedDate = document.getElementById('manufacturedDate').value;
            const expirationDate = document.getElementById('expirationDate').value;

            // Get supplierId robustly
            let supplierIdValue = document.getElementById('supplierId').value;
            if (!supplierIdValue || supplierIdValue === '0') {
                // Try to get from selected option's data-id or data-supplierid
                const supplierSelect = document.getElementById('supplierId');
                const selectedOption = supplierSelect.options[supplierSelect.selectedIndex];
                supplierIdValue = selectedOption ? (selectedOption.value || selectedOption.getAttribute('data-id') || selectedOption.getAttribute('data-supplierid') || '') : '';
            }
            // Debug: log supplierId being sent
            console.log('Submitting product with supplierId:', supplierIdValue);

            const formData = {
                brand: document.getElementById('brand').value.trim(),
                genericName: document.getElementById('genericName').value.trim(),
                category: document.getElementById('category').value.trim(),
                stock: parseInt(document.getElementById('stock').value),
                lowStockThreshold: parseInt(document.getElementById('lowStockThreshold').value),
                price: parseFloat(document.getElementById('price').value),
                supplierId: supplierIdValue ? String(supplierIdValue) : '',
                contact: document.getElementById('contact').value.trim(),
                manufacturedDate: manufacturedDate,
                expirationDate: expirationDate
            };

            try {
                if (editingId) {
                    await updateInventory(editingId, formData);
                    alert('Product updated successfully!');
                } else {
                    await createInventory(formData);
                    alert('Product added successfully!');
                }
                
                closeModal();
                await loadInventory();
                await populateCategoryFilter();
            } catch (error) {
                alert('Error: ' + error.message);
            }
        }

        // Delete product
        async function deleteProduct(id) {
            if (confirm('Are you sure you want to delete this product?')) {
                try {
                    await deleteInventory(id);
                    await loadInventory();
                } catch (error) {
                    alert('Error: ' + error.message);
                }
            }
        }

        // Close modal
        function closeModal() {
            document.getElementById('productModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('productModal');
            if (event.target === modal) {
                closeModal();
            }
        }

        // Session timeout check disabled
        // No auto-logout functionality
    </script>
</body>
</html>

