// Pharmacy Suppliers & Supply Management
// Version: 2.4 - API Integration for Inventory
console.log('Loading Pharmacy Suppliers & Supply Management v2.4 - API Integration for Inventory');

// Global variables
let allSuppliers = [];
let allDeliveries = [];
let allInventory = [];
let editingSupplier = null;
let editingDelivery = null;

// Load data on page load
document.addEventListener('DOMContentLoaded', async function() {
    try {
        await loadInventory();
        await loadSuppliers();
        // Normalize allSuppliers: ensure .id and .name are always set
        allSuppliers = (allSuppliers || []).map(s => ({
            ...s,
            id: s.supplierId || s.id || '',
            name: s.name || s.supplierName || ''
        }));
        await loadDeliveries();
        await loadStatistics();
        setupEventListeners();
    } catch (error) {
        console.error('Error initializing page:', error);
    }
});

// Setup event listeners
function setupEventListeners() {
    // Search filters
    const supplierSearch = document.getElementById('supplierSearch');
    if (supplierSearch) {
        supplierSearch.addEventListener('input', filterSuppliers);
    }

    const deliverySearch = document.getElementById('deliverySearch');
    if (deliverySearch) {
        deliverySearch.addEventListener('input', filterDeliveries);
    }

    // Set default delivery date to today
    const deliveryDate = document.getElementById('deliveryDate');
    if (deliveryDate) {
        deliveryDate.valueAsDate = new Date();
    }

    // Add event listener for delivery quantity input (for real-time total cost calculation)
    const deliveryQuantityInput = document.getElementById('deliveryQuantity');
    if (deliveryQuantityInput) {
        deliveryQuantityInput.addEventListener('input', calculateTotalCost);
    }

    // Toggle delivery date fields based on status in supply modal
    const deliveryStatus = document.getElementById('deliveryStatus');
    if (deliveryStatus) {
        deliveryStatus.addEventListener('change', function() {
            toggleDeliveryDateFields(this.value);
        });
        // initialize
        toggleDeliveryDateFields(deliveryStatus.value);
    }
}

// Show/hide delivery/delivered date fields depending on status
function toggleDeliveryDateFields(status) {
    const supplyModal = document.getElementById('supplyModal');
    const deliveryDateEl = supplyModal ? supplyModal.querySelector('#deliveryDate') : document.getElementById('deliveryDate');
    const deliveredDateEl = supplyModal ? supplyModal.querySelector('#deliveredDate') : document.getElementById('deliveredDate');
    if (!deliveryDateEl || !deliveredDateEl) return;

    if (status === 'pending') {
        // Only delivery date visible/required
        deliveryDateEl.closest('.form-group').style.display = '';
        deliveryDateEl.required = true;

        deliveredDateEl.closest('.form-group').style.display = 'none';
        deliveredDateEl.required = false;
        deliveredDateEl.value = '';
    } else if (status === 'delivered') {
        // Both dates visible and required
        deliveryDateEl.closest('.form-group').style.display = '';
        deliveryDateEl.required = true;

        deliveredDateEl.closest('.form-group').style.display = '';
        deliveredDateEl.required = true;
    } else {
        // cancelled or other: show delivery date, hide delivered
        deliveryDateEl.closest('.form-group').style.display = '';
        deliveryDateEl.required = true;

        deliveredDateEl.closest('.form-group').style.display = 'none';
        deliveredDateEl.required = false;
    }
}

// Load inventory from API (not localStorage!)
async function loadInventory() {
    try {
        // Use the API to get inventory data (from database)
        if (typeof readInventory === 'function') {
            allInventory = await readInventory();
        } else {
            console.error('readInventory function not found. Make sure api-adapter.js is loaded.');
            allInventory = [];
            return;
        }
        
        console.log('Loaded inventory:', allInventory.length, 'products');
        
        // Debug: Show all inventory items with their suppliers
        if (allInventory.length > 0) {
            console.log('Inventory items:', allInventory.map(p => ({
                id: p.id,
                brand: p.brand,
                supplierId: p.supplierId || p.supplier,
                supplierName: p.supplierName || ''
            })));
        }
    } catch (error) {
        console.error('Error loading inventory:', error);
        allInventory = [];
    }
}

// Tab Switching
function switchTab(tab) {
    // Update tab buttons
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    event.target.closest('.tab-btn').classList.add('active');

    // Update tab content
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
    if (tab === 'suppliers') {
        document.getElementById('suppliersTab').classList.add('active');
    } else if (tab === 'deliveries') {
        document.getElementById('deliveriesTab').classList.add('active');
    }
}

// Load and display statistics
async function loadStatistics() {
    try {
        const suppliers = allSuppliers;
        const deliveries = allDeliveries;

        // Total suppliers
        const activeSuppliers = suppliers.filter(s => s.status === 'active').length;
        const totalSuppliersEl = document.getElementById('totalSuppliers');
        if (totalSuppliersEl) {
            totalSuppliersEl.textContent = activeSuppliers;
        }

        // Deliveries this month
        const today = new Date();
        const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
        const deliveriesThisMonth = deliveries.filter(d => {
            const deliveryDate = new Date(d.deliveryDate);
            return deliveryDate >= firstDayOfMonth;
        }).length;
        const deliveriesMonthEl = document.getElementById('deliveriesMonth');
        if (deliveriesMonthEl) {
            deliveriesMonthEl.textContent = deliveriesThisMonth;
        }

        // Pending deliveries
        const pending = deliveries.filter(d => d.status === 'pending').length;
        const pendingDeliveriesEl = document.getElementById('pendingDeliveries');
        if (pendingDeliveriesEl) {
            pendingDeliveriesEl.textContent = pending;
        }

        // Total value this month
        const totalValue = deliveries
            .filter(d => {
                const deliveryDate = new Date(d.deliveryDate);
                return deliveryDate >= firstDayOfMonth;
            })
            .reduce((sum, d) => sum + (parseFloat(d.totalCost) || 0), 0);
        const totalValueEl = document.getElementById('totalValue');
        if (totalValueEl) {
            totalValueEl.textContent = '₱' + totalValue.toLocaleString('en-PH', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

    } catch (error) {
        console.error('Error loading statistics:', error);
    }
}

// ============================================
// SUPPLIER MANAGEMENT
// ============================================

// Load suppliers
async function loadSuppliers() {
    try {
        const suppliers = await readSuppliers();
        // Normalize supplier objects so `id` refers to the DB `supplierId` (varchar(50))
        allSuppliers = (suppliers || []).map(s => ({ ...s, id: s.supplierId ?? s.id }));
        displaySuppliers(allSuppliers);
        populateSupplierDropdown();
    } catch (error) {
        console.error('Error loading suppliers:', error);
        allSuppliers = [];
        displaySuppliers(allSuppliers);
        populateSupplierDropdown();
    }
}

// Display suppliers in table
function displaySuppliers(suppliers) {
    const tableBody = document.getElementById('suppliersTableBody');
    const supplierCount = document.getElementById('supplierCount');

    if (!tableBody) {
        console.warn('Suppliers table body not found');
        return;
    }

    if (suppliers.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="8" class="empty-state">
                    <i class="fas fa-building"></i>
                    <p>No suppliers found</p>
                    <button class="btn-primary" onclick="showSupplierModal()" style="margin-top: 15px;">
                        <i class="fas fa-plus-circle"></i> Add Your First Supplier
                    </button>
                </td>
            </tr>
        `;
        if (supplierCount) {
            supplierCount.textContent = '0 suppliers';
        }
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
            <td><span class="badge ${supplier.status || 'active'}">${(supplier.status || 'active').toUpperCase()}</span></td>
            <td>
                <div class="action-buttons">
                    <button class="btn-edit" onclick="editSupplier('${supplier.id}')">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button class="btn-delete" onclick="deleteSupplier('${supplier.id}')">
                        <i class="fas fa-trash"></i> Archive
                    </button>
                </div>
            </td>
        `;
        tableBody.appendChild(row);
    });

    if (supplierCount) {
        supplierCount.textContent = `${suppliers.length} supplier${suppliers.length !== 1 ? 's' : ''}`;
    }
}

// Filter suppliers
function filterSuppliers() {
    const searchTerm = document.getElementById('supplierSearch').value.toLowerCase();
    const statusFilter = document.getElementById('supplierStatusFilter').value.toLowerCase();

    let filtered = allSuppliers.filter(supplier => {
        const matchesSearch = !searchTerm || 
            (supplier.name && supplier.name.toLowerCase().includes(searchTerm)) ||
            (supplier.contactPerson && supplier.contactPerson.toLowerCase().includes(searchTerm)) ||
            (supplier.phone && supplier.phone.toLowerCase().includes(searchTerm)) ||
            (supplier.email && supplier.email.toLowerCase().includes(searchTerm));

        const matchesStatus = !statusFilter || 
            (supplier.status && supplier.status.toLowerCase() === statusFilter);

        return matchesSearch && matchesStatus;
    });

    displaySuppliers(filtered);
}

// Show supplier modal
function showSupplierModal() {
    editingSupplier = null;
    document.getElementById('supplierModalTitle').innerText = 'Add New Supplier';
    document.getElementById('supplierForm').reset();
    document.getElementById('supplierModal').style.display = 'block';
}

// Edit supplier
function editSupplier(id) {
    const supplier = allSuppliers.find(s => s.id === id);
    if (supplier) {
        editingSupplier = id;
        document.getElementById('supplierModalTitle').innerText = 'Update Supplier';
        document.getElementById('supplierName').value = supplier.name || '';
        document.getElementById('contactPerson').value = supplier.contactPerson || '';
        document.getElementById('phone').value = supplier.phone || '';
        document.getElementById('email').value = supplier.email || '';
        document.getElementById('address').value = supplier.address || '';
        document.getElementById('supplierStatus').value = supplier.status || 'active';
        document.getElementById('supplierModal').style.display = 'block';
    }
}

// Validate email format
function validateEmail(email) {
    const re = /^[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$/i;
    return re.test(String(email).toLowerCase());
}

// Validate phone number
function validatePhone(phone) {
    const re = /^[0-9+\-\(\)\s]{7,20}$/;
    return re.test(phone);
}

// Validate supplier form
function validateSupplierForm() {
    let isValid = true;
    const errors = [];

    // Validate Supplier Name
    const name = document.getElementById('supplierName').value.trim();
    if (!name) {
        errors.push('Supplier Name is required');
        isValid = false;
    }

    // Validate Contact Person
    const contactPerson = document.getElementById('contactPerson').value.trim();
    if (!contactPerson) {
        errors.push('Contact Person is required');
        isValid = false;
    }

    // Validate Phone
    const phone = document.getElementById('phone').value.trim();
    if (!phone) {
        errors.push('Phone Number is required');
        isValid = false;
    } else if (!validatePhone(phone)) {
        errors.push('Phone Number is invalid (must be 7-20 characters, numbers and +, -, (), spaces)');
        isValid = false;
    }

    // Validate Email
    const email = document.getElementById('email').value.trim();
    if (!email) {
        errors.push('Email is required');
        isValid = false;
    } else if (!validateEmail(email)) {
        errors.push('Email format is invalid (e.g., example@gmail.com)');
        isValid = false;
    }

    // Validate Address
    const address = document.getElementById('address').value.trim();
    if (!address) {
        errors.push('Address is required');
        isValid = false;
    }

    // Show errors if any
    if (!isValid) {
        alert('Please fix the following errors:\n\n' + errors.join('\n'));
    }

    return isValid;
}

// Submit supplier
async function submitSupplier() {
    // Validate form first
    if (!validateSupplierForm()) {
        return;
    }

    const formData = {
        name: document.getElementById('supplierName').value.trim(),
        contactPerson: document.getElementById('contactPerson').value.trim(),
        phone: document.getElementById('phone').value.trim(),
        email: document.getElementById('email').value.trim().toLowerCase(),
        address: document.getElementById('address').value.trim(),
        status: document.getElementById('supplierStatus').value
    };

    try {
        if (editingSupplier) {
            // Update existing supplier
            await updateSupplier(editingSupplier, formData);
            alert('Supplier updated successfully!');
        } else {
            // Create new supplier
            const newSupplier = {
                id: generateSupplierId(),
                ...formData,
                createdAt: new Date().toISOString()
            };
            await createSupplier(newSupplier);
            alert('Supplier added successfully!');
        }

        closeSupplierModal();
        await loadSuppliers();
        await loadStatistics();
    } catch (error) {
        alert('Error: ' + error.message);
    }
}

// Delete supplier
async function deleteSupplier(id) {
    if (confirm('Are you sure you want to delete this supplier? This action cannot be undone.')) {
        try {
            await window.deleteSupplier(id);  // Call API deleteSupplier
            alert('Supplier deleted successfully!');
            await loadSuppliers();
            await loadStatistics();
        } catch (error) {
            alert('Error: ' + error.message);
        }
    }
}

// Close supplier modal
function closeSupplierModal() {
    document.getElementById('supplierModal').style.display = 'none';
}

// Generate supplier ID
function generateSupplierId() {
    const date = new Date();
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const time = String(date.getHours()).padStart(2, '0') + String(date.getMinutes()).padStart(2, '0');
    const random = String(Math.floor(Math.random() * 100)).padStart(2, '0');
    return `SUP${year}${month}${day}${time}${random}`;
}

// Populate supplier dropdown
function populateSupplierDropdown() {
    const select = document.getElementById('supplierId');
    if (!select) return;
    
    select.innerHTML = '<option value="">Select Supplier</option>';
    const activeSuppliers = allSuppliers.filter(s => s.status === 'active');
    
    activeSuppliers.forEach(supplier => {
        const option = document.createElement('option');
        option.value = supplier.supplierId || '';
        option.textContent = supplier.name;
        select.appendChild(option);
    });
}

// Filter products by selected supplier
function filterProductsBySupplier() {
    const supplierSelect = document.getElementById('supplierId');
    const productSelect = document.getElementById('productId');
    
    if (!supplierSelect || !productSelect) return;
    
    const selectedSupplierId = supplierSelect.value;
    
    // Clear product dropdown
    productSelect.innerHTML = '<option value="">Select Product</option>';

    if (!selectedSupplierId) {
        productSelect.innerHTML = '<option value="">Select a supplier first</option>';
        // Clear other fields
        document.getElementById('batchNo').value = '';
        document.getElementById('currentStock').value = '';
        document.getElementById('deliveryQuantity').value = '';
        document.getElementById('unitCost').value = '';
        document.getElementById('totalCost').value = '';
        return;
    }
    
    // Find supplier name for messages
    const supplier = allSuppliers.find(s => s.id === selectedSupplierId);
    const supplierName = supplier ? supplier.name.trim() : '';

    // Filter products by supplierId (prefer exact id match)
    const supplierProducts = allInventory.filter(p => {
        const prodSupplierId = p.supplierId || p.supplier || '';
        return prodSupplierId.toString() === selectedSupplierId.toString();
    });
    
    console.log('Filtered Products:', supplierProducts.length, supplierProducts);
    
    if (supplierProducts.length === 0) {
        const option = document.createElement('option');
        option.value = '';
        option.textContent = `No products from ${supplierName} (Add in Inventory page)`;
        option.disabled = true;
        productSelect.appendChild(option);
        return;
    }
    
    // Add products to dropdown
    supplierProducts.forEach(product => {
        const option = document.createElement('option');
        option.value = product.productId;
        option.textContent = `${product.brand} - ${product.genericName} (Stock: ${product.stock})`;
        option.setAttribute('data-brand', product.brand || '');
        option.setAttribute('data-generic', product.genericName || '');
        option.setAttribute('data-stock', product.stock || 0);
        option.setAttribute('data-price', product.price || 0);
        productSelect.appendChild(option);
    });
}

// Auto-fill product details when product is selected
function autoFillProductDetails() {
    const productSelect = document.getElementById('productId');

    if (!productSelect || !productSelect.value) {
        // Clear fields if no product selected
        document.getElementById('batchNo').value = '';
        document.getElementById('currentStock').value = '';
        document.getElementById('deliveryQuantity').value = '';
        document.getElementById('unitCost').value = '';
        document.getElementById('totalCost').value = '';
        return;
    }

    // Find the selected product
    const productId = productSelect.value;
    const product = allInventory.find(p => p.productId === productId);

    if (!product) return;

    // Auto-fill fields
    document.getElementById('batchNo').value = product.batchNo || '';
    document.getElementById('currentStock').value = product.stock || 0;
    document.getElementById('unitCost').value = product.price || 0;

    // Calculate total cost in case quantity is already entered
    calculateTotalCost();

    // Clear delivery quantity and total cost (user must input delivery quantity)
    document.getElementById('deliveryQuantity').value = '';
    document.getElementById('totalCost').value = '';
}

// Calculate total cost based on delivery quantity
function calculateTotalCost() {
    const deliveryQuantity = parseFloat(document.getElementById('deliveryQuantity').value) || 0;
    const unitCost = parseFloat(document.getElementById('unitCost').value) || 0;
    const totalCost = deliveryQuantity * unitCost;
    document.getElementById('totalCost').value = totalCost.toFixed(2);
}

// Calculate total value for product modal
function calculateProductTotal() {
    const modal = document.getElementById('productModal');
    const qtyEl = modal ? modal.querySelector('#deliveryQuantity') : document.getElementById('deliveryQuantity');
    const priceEl = modal ? modal.querySelector('#price') : document.getElementById('price');
    const totalEl = modal ? modal.querySelector('#totalValue') : document.getElementById('totalValue');

    const qty = qtyEl ? (parseFloat(qtyEl.value) || 0) : 0;
    const price = priceEl ? (parseFloat(priceEl.value) || 0) : 0;
    const total = qty * price;

    if (totalEl) totalEl.value = total.toFixed(2);
}

// Populate batch numbers for selected product from inventory
async function populateBatchNumbersFromInventory(product) {
    const batchSelect = document.getElementById('batchNo');
    if (!batchSelect) return;

    // Clear existing options
    batchSelect.innerHTML = '<option value="">Select batch</option>';

    try {
        // Get selected supplier
        const supplierSelect = document.getElementById('supplierId');
        const supplierId = supplierSelect.value;
        const supplier = allSuppliers.find(s => s.id === supplierId);
        if (!supplier) {
            console.error('Supplier not found');
            return;
        }
        const supplierIdParam = supplier.id;

        // Fetch inventory items matching supplierId, brand, generic name
        const url = `api/inventory.php?supplier=${encodeURIComponent(supplierIdParam)}&brand=${encodeURIComponent(product.brand)}&genericName=${encodeURIComponent(product.genericName)}`;
        const response = await fetch(url);
        const result = await response.json();

        if (result.success && result.data) {
            // Extract unique batch numbers
            const batchNumbers = [...new Set(result.data.map(p => p.batchNo).filter(b => b && b.trim()))];

            // Add existing batch numbers to dropdown
            batchNumbers.forEach(batchNo => {
                const option = document.createElement('option');
                option.value = batchNo;
                option.textContent = batchNo;
                batchSelect.appendChild(option);
            });

            // If no existing batches, show message
            if (batchNumbers.length === 0) {
                const option = document.createElement('option');
                option.value = '';
                option.textContent = 'No existing batches - create new';
                option.disabled = true;
                batchSelect.appendChild(option);
            }
        } else {
            // No matching inventory
            const option = document.createElement('option');
            option.value = '';
            option.textContent = 'No existing batches - create new';
            option.disabled = true;
            batchSelect.appendChild(option);
        }

    } catch (error) {
        console.error('Error loading batch numbers from inventory:', error);
        const option = document.createElement('option');
        option.value = '';
        option.textContent = 'Error loading batches';
        option.disabled = true;
        batchSelect.appendChild(option);
    }
}

// Populate batch numbers for selected product (deprecated, kept for compatibility)
async function populateBatchNumbers(productId) {
    const batchSelect = document.getElementById('batchNo');
    if (!batchSelect) return;

    // Clear existing options
    batchSelect.innerHTML = '<option value="">Select or create batch</option>';

    try {
        // Get existing deliveries for this product to find batch numbers
        const deliveries = await readDeliveries();
        const productDeliveries = deliveries.filter(d => d.productId === productId);

        // Extract unique batch numbers
        const batchNumbers = [...new Set(productDeliveries.map(d => d.batchNo).filter(b => b && b.trim()))];

        // Add existing batch numbers to dropdown
        batchNumbers.forEach(batchNo => {
            const option = document.createElement('option');
            option.value = batchNo;
            option.textContent = batchNo;
            batchSelect.appendChild(option);
        });

        // If no existing batches, show message
        if (batchNumbers.length === 0) {
            const option = document.createElement('option');
            option.value = '';
            option.textContent = 'No existing batches - create new';
            option.disabled = true;
            batchSelect.appendChild(option);
        }

    } catch (error) {
        console.error('Error loading batch numbers:', error);
        const option = document.createElement('option');
        option.value = '';
        option.textContent = 'Error loading batches';
        option.disabled = true;
        batchSelect.appendChild(option);
    }
}



// ============================================
// SUPPLY DELIVERY MANAGEMENT
// ============================================

// Load deliveries from API
async function loadDeliveries() {
    try {
        // Use the API adapter function
        if (typeof readDeliveries === 'function') {
            const deliveries = await readDeliveries();
            // Map supplyId to id for consistency
            allDeliveries = (deliveries || []).map(d => ({ ...d, id: d.supplyId ?? d.id }));
            displayDeliveries(allDeliveries);
        } else {
            console.error('readDeliveries function not found. Make sure api-adapter.js is loaded.');
            allDeliveries = [];
            displayDeliveries(allDeliveries);
            return;
        }
    } catch (error) {
        console.error('Error loading deliveries:', error);
        allDeliveries = [];
        displayDeliveries(allDeliveries);
    }
}

// Display deliveries in table
function displayDeliveries(deliveries) {
    const tableBody = document.getElementById('deliveriesTableBody');
    const deliveryCount = document.getElementById('deliveryCount');
    
    if (!tableBody) {
        console.warn('Deliveries table body not found');
        return;
    }
    
    if (deliveries.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="9" class="empty-state">
                    <i class="fas fa-truck"></i>
                    <p>No deliveries recorded</p>
                    <button class="btn-primary" onclick="showSupplyModal()" style="margin-top: 15px;">
                        <i class="fas fa-plus-circle"></i> Record Your First Delivery
                    </button>
                </td>
            </tr>
        `;
        if (deliveryCount) {
            deliveryCount.textContent = '0 deliveries';
        }
        return;
    }

  tableBody.innerHTML = '';
    deliveries.forEach(delivery => {
        const supplier = allSuppliers.find(s => s.id === delivery.supplierId);
        const product = allInventory.find(p => p.productId === delivery.productId);

    const row = document.createElement('tr');
    row.innerHTML = `
            <td>#${delivery.id || ''}</td>
            <td>${new Date(delivery.deliveryDate).toLocaleDateString()}</td>
            <td>${delivery.deliveredDate ? new Date(delivery.deliveredDate).toLocaleDateString() : '-'}</td>
            <td>${supplier ? supplier.name : 'Unknown'}</td>
            <td>${product ? `${product.brand} (${product.genericName})` : 'Unknown Product'}</td>
            <td>${delivery.batchNo || ''}</td>
            <td><strong>${delivery.quantity || 0}</strong> units</td>
            <td>₱${parseFloat(delivery.totalAmount || 0).toFixed(2)}</td>
            <td><span class="badge ${delivery.status || 'pending'}">${(delivery.status || 'pending').toUpperCase()}</span></td>
            <td>
                <div class="action-buttons">
                    <button class="btn-view" onclick="viewDeliveryNotes('${delivery.id}')">
                        <i class="fas fa-eye"></i> View
                    </button>
                    ${['delivered','received'].includes((delivery.status||'').toLowerCase()) ? '' : `
                    <button class="btn-edit" onclick="editDelivery('${delivery.id}')">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    `}
                    <button class="btn-delete" onclick="deleteDelivery('${delivery.id}')">
                        <i class="fas fa-trash"></i> Archive
                    </button>
                </div>
      </td>
    `;
    tableBody.appendChild(row);
  });

    if (deliveryCount) {
        deliveryCount.textContent = `${deliveries.length} deliver${deliveries.length !== 1 ? 'ies' : 'y'}`;
    }
}

// Filter deliveries
function filterDeliveries() {
    const searchTerm = document.getElementById('deliverySearch').value.toLowerCase();
    const statusFilter = document.getElementById('deliveryStatusFilter').value.toLowerCase();

    let filtered = allDeliveries.filter(delivery => {
        const supplier = allSuppliers.find(s => s.id === delivery.supplierId);
        const product = allInventory.find(p => p.id === delivery.productId);
        
        const matchesSearch = !searchTerm || 
            (supplier && supplier.name.toLowerCase().includes(searchTerm)) ||
            (product && product.brand.toLowerCase().includes(searchTerm)) ||
            (product && product.genericName.toLowerCase().includes(searchTerm)) ||
            (delivery.batchNo && delivery.batchNo.toLowerCase().includes(searchTerm));

        const matchesStatus = !statusFilter || 
            (delivery.status && delivery.status.toLowerCase() === statusFilter);

        return matchesSearch && matchesStatus;
    });

    displayDeliveries(filtered);
}

// Show supply modal
function showSupplyModal() {
    editingDelivery = null;
    document.getElementById('supplyModalTitle').innerText = 'Record Supply Delivery';
    document.getElementById('supplyForm').reset();
    document.getElementById('deliveryDate').valueAsDate = new Date();
    // default status to pending and toggle date fields
    const statusEl = document.getElementById('deliveryStatus');
    if (statusEl) {
        statusEl.value = 'pending';
        toggleDeliveryDateFields('pending');
    }
    
    // Populate supplier dropdown
    populateSupplierDropdown();
    
    // Reset product dropdown to initial state
    const productSelect = document.getElementById('productId');
    if (productSelect) {
        productSelect.innerHTML = '<option value="">Select a supplier first</option>';
    }
    
    // Clear readonly fields
    document.getElementById('batchNo').value = '';
    document.getElementById('currentStock').value = '';
    document.getElementById('deliveryQuantity').value = '';
    document.getElementById('unitCost').value = '';
    document.getElementById('totalCost').value = '';
    document.getElementById('deliveredDate').value = '';
    
    document.getElementById('supplyModal').style.display = 'block';
}

// Edit delivery
function editDelivery(id) {
    const delivery = allDeliveries.find(d => d.id === id);

    if (delivery) {
        editingDelivery = id;
        document.getElementById('supplyModalTitle').innerText = 'Update Supply Delivery';

        // Populate supplier dropdown first
        populateSupplierDropdown();

        // Set supplier
        document.getElementById('supplierId').value = delivery.supplierId || '';

        // Filter products by supplier (wait for dropdown to populate)
        setTimeout(() => {
            filterProductsBySupplier();
            // Wait a bit for product dropdown to populate, then set product and details
            setTimeout(() => {
                document.getElementById('productId').value = delivery.productId || '';
                autoFillProductDetails();
                // Set batch number
                document.getElementById('batchNo').value = delivery.batchNo || '';
                // Set delivery quantity from the delivery record and ensure it is editable
                const dqEl = document.getElementById('deliveryQuantity');
                dqEl.value = delivery.quantity || '';
                dqEl.readOnly = false;
                // Calculate total cost based on the delivery quantity
                calculateTotalCost();
            }, 100);
        }, 0);

        // Set other delivery details
        document.getElementById('deliveryDate').value = delivery.deliveryDate || '';
        document.getElementById('deliveredDate').value = delivery.deliveredDate || '';
        document.getElementById('deliveryStatus').value = delivery.status || 'pending';
        document.getElementById('notes').value = delivery.notes || '';

        // Toggle date fields based on loaded status
        toggleDeliveryDateFields(document.getElementById('deliveryStatus').value);

        document.getElementById('supplyModal').style.display = 'block';
    }
}

// Validate supply delivery form
function validateSupplyForm() {
    let isValid = true;
    const errors = [];

    // Validate Supplier
    const supplierId = document.getElementById('supplierId').value;
    if (!supplierId) {
        errors.push('Please select a Supplier');
        isValid = false;
    }

    // Validate Product
    const productId = document.getElementById('productId').value;
    if (!productId) {
        errors.push('Please select a Product');
        isValid = false;
    }

    // Validate Delivery Quantity
    const deliveryQuantityStr = document.getElementById('deliveryQuantity').value.trim();
    const deliveryQuantity = parseFloat(deliveryQuantityStr);
    if (!deliveryQuantityStr || isNaN(deliveryQuantity) || deliveryQuantity <= 0) {
        errors.push('Delivery Quantity must be a valid number greater than 0');
        isValid = false;
    }



    // Validate Status
    const status = document.getElementById('deliveryStatus').value;
    if (!status) {
        errors.push('Please select a Status');
        isValid = false;
    }

    // Validate Delivery Date always
    const deliveryDateVal = document.getElementById('deliveryDate').value;
    if (!deliveryDateVal) {
        errors.push('Delivery Date is required');
        isValid = false;
    }

    // Validate Delivered Date when status is 'delivered'
    if (status === 'delivered') {
        const deliveredDate = document.getElementById('deliveredDate').value;
        if (!deliveredDate) {
            errors.push('Delivered Date is required when status is Delivered');
            isValid = false;
        } else {
            // Ensure deliveredDate is not earlier than deliveryDate
            const d1 = new Date(deliveryDateVal);
            const d2 = new Date(deliveredDate);
            if (d2 < d1) {
                errors.push('Delivered Date cannot be before Delivery Date');
                isValid = false;
            }
        }
    }

    // Show errors if any
    if (!isValid) {
        alert('Please fix the following errors:\n\n' + errors.join('\n'));
    }

    return isValid;
}

// Submit supply delivery
async function submitSupply() {
    // Validate form first
    if (!validateSupplyForm()) {
        return;
    }

    // Ensure total cost is calculated before submitting
    calculateTotalCost();

    const formData = {
        supplierId: document.getElementById('supplierId').value,
        productId: document.getElementById('productId').value,
        batchNo: document.getElementById('batchNo').value.trim(),
        quantity: parseInt(document.getElementById('deliveryQuantity').value),
        unitPrice: parseFloat(document.getElementById('unitCost').value),
        totalAmount: parseFloat(document.getElementById('totalCost').value),
        deliveryDate: document.getElementById('deliveryDate').value,
        deliveredDate: document.getElementById('deliveredDate').value,
        status: document.getElementById('deliveryStatus').value,
        notes: document.getElementById('notes').value.trim()
    };

    try {
        let response;
        let successMessage;

        if (editingDelivery) {
            // Update existing delivery
            response = await fetch(`api/deliveries.php?id=${editingDelivery}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            });
            successMessage = 'Supply delivery updated successfully!';
        } else {
            // Create new delivery
            response = await fetch('api/deliveries.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            });
            successMessage = 'Supply delivery recorded successfully!';
        }

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const result = await response.json();
        if (result.success) {
            alert(successMessage);
            closeSupplyModal();
            await loadDeliveries();
            await loadStatistics();
        } else {
            throw new Error(result.message || 'Failed to save delivery');
        }
    } catch (error) {
        console.error('Error submitting delivery:', error);
        alert('Error: ' + error.message);
    }
}

// Delete delivery
async function deleteDelivery(id) {
    if (confirm('Are you sure you want to delete this delivery record?')) {
        try {
            const response = await fetch(`api/deliveries.php?id=${id}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();
            if (result.success) {
                alert('Delivery record archived successfully!');
                await loadDeliveries();
                await loadStatistics();
            } else {
                throw new Error(result.message || 'Failed to archive delivery');
            }
        } catch (error) {
            console.error('Error deleting delivery:', error);
            alert('Error: ' + error.message);
        }
    }
}

// View delivery notes
function viewDeliveryNotes(id) {
    const delivery = allDeliveries.find(d => d.id === id);
    if (delivery) {
        const notes = delivery.notes || 'No notes available for this delivery.';
        alert(`Delivery Notes (#${delivery.id}):\n\n${notes}`);
    }
}

// Close supply modal
function closeSupplyModal() {
    document.getElementById('supplyModal').style.display = 'none';
}

// Generate delivery ID
function generateDeliveryId() {
    const date = new Date();
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const time = String(date.getHours()).padStart(2, '0') + String(date.getMinutes()).padStart(2, '0');
    const random = String(Math.floor(Math.random() * 100)).padStart(2, '0');
    return `DEL${year}${month}${day}${time}${random}`;
}


// Update inventory stock via API
async function updateInventoryStock(productId, quantity) {
    try {
        // Find the product in allInventory
        const product = allInventory.find(p => p.id === productId);
        
        if (product) {
            const newStock = (product.stock || 0) + quantity;
            
            // Update via API
            if (typeof updateInventory === 'function') {
                await updateInventory(productId, { stock: newStock });
                console.log(`Updated stock for ${product.brand}: ${product.stock} → ${newStock}`);
                
                // Reload inventory to reflect changes
                await loadInventory();
            } else {
                console.error('updateInventory function not found');
            }
        }
    } catch (error) {
        console.error('Error updating inventory stock:', error);
    }
}




// Close modal when clicking outside
window.onclick = function(event) {
    const supplierModal = document.getElementById('supplierModal');
    const supplyModal = document.getElementById('supplyModal');
    const productModal = document.getElementById('productModal');

    if (event.target === supplierModal) {
        closeSupplierModal();
    }
    if (event.target === supplyModal) {
        closeSupplyModal();
    }
    if (event.target === productModal) {
        closeProductModal();
    }
}

// ============================================
// PRODUCT MODAL FUNCTIONS
// ============================================

// Supply new product from delivery modal
function supplyNewProduct() {
    const supplierSelect = document.getElementById('supplierId');
    if (!supplierSelect || !supplierSelect.value) {
        alert('Please select a supplier first');
        return;
    }

    showProductModal();
    // Pre-fill supplier
    const productSupplierSelect = document.getElementById('productSupplier');
    if (productSupplierSelect) {
        productSupplierSelect.value = supplierSelect.value;
        updateProductSupplierContact();
    }
    // Set default delivery date
    const deliveryDateInput = document.getElementById('productDeliveryDate');
    if (deliveryDateInput) {
        deliveryDateInput.valueAsDate = new Date();
    }
    // If opening via Supply New Product flow, ensure stock defaults to 0 and is readonly/hidden
    const productModalEl = document.getElementById('productModal');
    const stockEl = productModalEl ? productModalEl.querySelector('#stock') : document.getElementById('stock');
    if (stockEl) {
        stockEl.value = '0';
        stockEl.readOnly = true;
        // Try to hide the entire form-group if available
        const fg = stockEl.closest('.form-group');
        if (fg) fg.style.display = 'none';
    }
}

// Show product modal
function showProductModal() {
    document.getElementById('modalTitle').innerText = 'Add New Product';
    document.getElementById('productForm').reset();
    populateProductSupplierDropdown();
    populateCategoryList();
    // Add event listeners for total calculation
    const modal = document.getElementById('productModal');
    const dq = modal ? modal.querySelector('#deliveryQuantity') : document.getElementById('deliveryQuantity');
    const priceEl = modal ? modal.querySelector('#price') : document.getElementById('price');
    if (dq) dq.addEventListener('input', calculateProductTotal);
    if (priceEl) priceEl.addEventListener('input', calculateProductTotal);
    if (modal) modal.style.display = 'block';
}

// Close product modal
function closeProductModal() {
    document.getElementById('productModal').style.display = 'none';
}

// Populate product supplier dropdown
function populateProductSupplierDropdown() {
    const select = document.getElementById('productSupplier');
    if (!select) return;

    // Normalize allSuppliers before populating
    allSuppliers = (allSuppliers || []).map(s => ({
        ...s,
        id: s.supplierId || s.id || '',
        name: s.name || s.supplierName || ''
    }));

    select.innerHTML = '<option value="">Select Supplier</option>';
    const activeSuppliers = allSuppliers.filter(s => s.status === 'active');

    activeSuppliers.forEach(supplier => {
        const option = document.createElement('option');
        option.value = supplier.supplierId || '';
        option.setAttribute('data-phone', supplier.phone || '');
        option.setAttribute('data-contact', supplier.contactPerson || '');
        option.textContent = supplier.name;
        select.appendChild(option);
    });
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

// Update product supplier contact
function updateProductSupplierContact() {
    const supplierSelect = document.getElementById('productSupplier');
    const contactInput = document.getElementById('productContact');

    if (!supplierSelect || !contactInput) return;

    const selectedOption = supplierSelect.options[supplierSelect.selectedIndex];
    const phone = selectedOption.getAttribute('data-phone') || '';

    contactInput.value = phone;
}

// Validate product form
function validateProductForm() {
    let isValid = true;
    const errors = [];

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
    const productModalForStock = document.getElementById('productModal');
    const stockEl = productModalForStock ? productModalForStock.querySelector('#stock') : document.getElementById('stock');
    const stockRaw = stockEl ? stockEl.value : '';
    const deliveryQuantityElForStock = productModalForStock ? productModalForStock.querySelector('#deliveryQuantity') : document.getElementById('deliveryQuantity');
    const isDeliveryVisibleForStock = deliveryQuantityElForStock && deliveryQuantityElForStock.offsetParent !== null;

    // If stock is empty but delivery fields are present (supply-new-product flow), treat stock as 0
    let stockNum;
    if ((stockRaw === '' || stockRaw === null) && isDeliveryVisibleForStock) {
        stockNum = 0;
    } else {
        stockNum = stockRaw === '' || stockRaw === null ? null : parseInt(stockRaw, 10);
    }

    if ((stockRaw === '' || stockRaw === null) && !isDeliveryVisibleForStock) {
        errors.push('Stock Quantity is required');
        isValid = false;
    } else if (stockNum === null || isNaN(stockNum)) {
        errors.push('Stock Quantity must be a whole number');
        isValid = false;
    } else {
        if (stockNum < 0) {
            errors.push('Stock Quantity cannot be negative');
            isValid = false;
        } else if (stockNum > 999999) {
            errors.push('Stock Quantity must not exceed 999,999');
            isValid = false;
        }
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
    const supplier = document.getElementById('productSupplier').value;
    if (!supplier) {
        errors.push('Please select a Supplier');
        isValid = false;
    }

    // Validate Contact
    const contact = document.getElementById('productContact').value.trim();
    if (!contact) {
        errors.push('Contact Number is required');
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

        if (mfgDate > today) {
            errors.push('Manufactured Date cannot be in the future');
            isValid = false;
        }

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

        const daysDiff = Math.floor((expDate - mfgDate) / (1000 * 60 * 60 * 24));
        if (daysDiff < 30) {
            errors.push('Shelf life seems too short (less than 30 days). Please verify dates.');
            isValid = false;
        }
    }

    // Validate delivery fields if present (for supply new product)
    const productModal = document.getElementById('productModal');
    const deliveryQuantityEl = productModal ? productModal.querySelector('#deliveryQuantity') : document.getElementById('deliveryQuantity');
    if (deliveryQuantityEl && deliveryQuantityEl.offsetParent !== null) { // Check if visible
        // Ensure total is calculated before validation
        calculateProductTotal();

        const deliveryQuantityStr = (deliveryQuantityEl.value || '').toString().trim();
        const deliveryQuantity = parseFloat(deliveryQuantityStr);
        if (!deliveryQuantityStr || isNaN(deliveryQuantity) || deliveryQuantity <= 0) {
            errors.push('Delivery Quantity must be a valid number greater than 0');
            isValid = false;
        }

        const totalValueEl = productModal ? productModal.querySelector('#totalValue') : document.getElementById('totalValue');
        const totalValueStr = totalValueEl ? (totalValueEl.value || '').toString().trim() : '';
        const totalValue = parseFloat(totalValueStr);
        if (!totalValueStr || isNaN(totalValue) || totalValue <= 0) {
            errors.push('Total Value must be a valid number greater than 0');
            isValid = false;
        }

        const deliveryDateEl = productModal ? productModal.querySelector('#productDeliveryDate') : document.getElementById('productDeliveryDate');
        const deliveryDate = deliveryDateEl ? (deliveryDateEl.value || '') : '';
        if (!deliveryDate) {
            errors.push('Delivery Date is required');
            isValid = false;
        }
    }

    if (!isValid) {
        alert('Please fix the following errors:\n\n' + errors.join('\n'));
    }

    return isValid;
}

// Submit product
async function submitProduct() {
    if (!validateProductForm()) {
        return;
    }

    const manufacturedDate = document.getElementById('manufacturedDate').value;
    const expirationDate = document.getElementById('expirationDate').value;

    const productModalElForSubmit = document.getElementById('productModal');
    const stockElForSubmit = productModalElForSubmit ? productModalElForSubmit.querySelector('#stock') : document.getElementById('stock');
    const stockRawForSubmit = stockElForSubmit ? stockElForSubmit.value : '';
    const deliveryQuantityElForSubmit = productModalElForSubmit ? productModalElForSubmit.querySelector('#deliveryQuantity') : document.getElementById('deliveryQuantity');
    const isDeliveryVisibleForSubmit = deliveryQuantityElForSubmit && deliveryQuantityElForSubmit.offsetParent !== null;
    const stockToSend = (stockRawForSubmit === '' || stockRawForSubmit === null) && isDeliveryVisibleForSubmit ? 0 : parseInt(stockRawForSubmit, 10);

    let supplierIdValue = document.getElementById('productSupplier').value;
    if (!supplierIdValue || supplierIdValue === '0') {
        // Try to get from selected option's data-id
        const supplierSelect = document.getElementById('productSupplier');
        const selectedOption = supplierSelect.options[supplierSelect.selectedIndex];
        supplierIdValue = selectedOption ? (selectedOption.value || selectedOption.getAttribute('data-id') || '') : '';
    }
    const formData = {
        brand: document.getElementById('brand').value.trim(),
        genericName: document.getElementById('genericName').value.trim(),
        category: document.getElementById('category').value.trim(),
        stock: isNaN(stockToSend) ? 0 : stockToSend,
        lowStockThreshold: parseInt(document.getElementById('lowStockThreshold').value),
        price: parseFloat(document.getElementById('price').value),
        supplierId: supplierIdValue,
        contact: document.getElementById('productContact').value.trim(),
        manufacturedDate: manufacturedDate,
        expirationDate: expirationDate
    };

    try {
        const result = await createInventory(formData);
        if (result && result.productId) {
            alert('Product added successfully!');

            // Check if this is from supply new product (has delivery fields)
            const productModal = document.getElementById('productModal');
            const deliveryQuantityEl = productModal ? productModal.querySelector('#deliveryQuantity') : document.getElementById('deliveryQuantity');
            const deliveryQuantity = deliveryQuantityEl ? deliveryQuantityEl.value : '';
            if (deliveryQuantity) {
                // Create delivery after product creation
                await createDeliveryAfterProduct(result.productId);
            }

            closeProductModal();
            // Reload inventory
            await loadInventory();
            // Update the product dropdown in supply modal if open
            const supplierSelect = document.getElementById('supplierId');
            if (!supplierSelect || !supplierSelect.value) {
                alert('Please select a supplier first');
                return;
            }

            showProductModal();
            // Pre-fill supplier
            const productSupplierSelect = document.getElementById('productSupplier');
            if (productSupplierSelect) {
                // Always set by supplierId, not id
                productSupplierSelect.value = supplierSelect.value;
                updateProductSupplierContact();
            }
        }
    } catch (error) {
        alert('Error adding product: ' + error.message);
    }
async function createDeliveryAfterProduct(productId) {
    try {
        // Fetch the created product to get batchNo
        const productResponse = await fetch(`api/inventory.php?id=${productId}`);
        const productResult = await productResponse.json();

        if (!productResult.success || !productResult.data) {
            throw new Error('Failed to fetch created product details');
        }

        const product = productResult.data;
        const batchNo = product.batchNo;

        // Get delivery data from form (scoped to product modal to avoid ID collisions)
        const productModalEl = document.getElementById('productModal');
        const supplierId = productModalEl ? (productModalEl.querySelector('#productSupplier')?.value || '') : (document.getElementById('productSupplier')?.value || '');
        const quantity = parseInt(productModalEl ? (productModalEl.querySelector('#deliveryQuantity')?.value || 0) : (document.getElementById('deliveryQuantity')?.value || 0));
        const totalValue = parseFloat(productModalEl ? (productModalEl.querySelector('#totalValue')?.value || 0) : (document.getElementById('totalValue')?.value || 0));
        const deliveryDate = productModalEl ? (productModalEl.querySelector('#productDeliveryDate')?.value || '') : (document.getElementById('productDeliveryDate')?.value || '');

        const unitPrice = totalValue / quantity;

        const deliveryData = {
            supplierId: supplierId,
            productId: productId,
            batchNo: batchNo,
            quantity: quantity,
            unitPrice: unitPrice,
            totalAmount: totalValue,
            deliveryDate: deliveryDate,
            // When creating via supply-new-product, mark as pending so it can be reviewed
            status: 'pending'
        };

        // Create delivery
        const deliveryResponse = await fetch('api/deliveries.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(deliveryData)
        });

        const deliveryResult = await deliveryResponse.json();
        if (deliveryResult.success) {
            alert('Delivery recorded successfully!');
            // Reload deliveries
            await loadDeliveries();
            await loadStatistics();
        } else {
            throw new Error(deliveryResult.message || 'Failed to create delivery');
        }
    } catch (error) {
        alert('Error creating delivery: ' + error.message);
    }
}
}

// Ensure functions are globally accessible (for onclick attributes)
window.showSupplierModal = showSupplierModal;
window.showSupplyModal = showSupplyModal;
window.closeSupplierModal = closeSupplierModal;
window.closeSupplyModal = closeSupplyModal;
window.submitSupplier = submitSupplier;
window.submitSupply = submitSupply;
window.editSupplier = editSupplier;
window.editDelivery = editDelivery;
window.deleteSupplier = deleteSupplier;
window.deleteDelivery = deleteDelivery;
window.filterSuppliers = filterSuppliers;
window.filterDeliveries = filterDeliveries;
window.switchTab = switchTab;
window.viewDeliveryNotes = viewDeliveryNotes;
window.filterProductsBySupplier = filterProductsBySupplier;
window.autoFillProductDetails = autoFillProductDetails;
window.supplyNewProduct = supplyNewProduct;
window.showProductModal = showProductModal;
window.closeProductModal = closeProductModal;
window.submitProduct = submitProduct;
window.updateProductSupplierContact = updateProductSupplierContact;
