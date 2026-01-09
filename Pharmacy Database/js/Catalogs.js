// Utility functions for DOM manipulation
const $ = (selector) => document.querySelector(selector);
const $$ = (selector) => document.querySelectorAll(selector);

// DOM utility functions
const dom = {
    show: (element) => element.style.display = '',
    hide: (element) => element.style.display = 'none',
    empty: (element) => element.innerHTML = '',
    text: (element, text) => element.textContent = text,
    html: (element, html) => element.innerHTML = html,
    append: (parent, child) => parent.appendChild(child),
    create: (tag) => document.createElement(tag),
    addClass: (element, className) => element.classList.add(className),
    removeClass: (element, className) => element.classList.remove(className),
    toggleClass: (element, className) => element.classList.toggle(className),
    hasClass: (element, className) => element.classList.contains(className),
    removeAllClass: (elements, className) => elements.forEach(el => el.classList.remove(className)),
    on: (element, event, handler) => element.addEventListener(event, handler)
};

// Global variables
let allProducts = [];
let filteredProducts = [];
let activeFilters = {
    brand: [],
    genericName: [],
    category: [],
    supplier: [],
    requiresPrescription: []
};

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    loadFilterOptions();
    loadInventory();

    // Search functionality
    const searchInput = $('#search-input');
    const searchButton = $('#search-button');
    const clearAllFiltersBtn = $('#clear-all-filters');

    if (searchInput) {
        dom.on(searchInput, 'input', applyFilters);
    }
    if (searchButton) {
        dom.on(searchButton, 'click', applyFilters);
    }
    if (clearAllFiltersBtn) {
        dom.on(clearAllFiltersBtn, 'click', clearAllFilters);
    }
});

// Load filter options from API
function loadFilterOptions() {
    // Load brands
    API.get('/api/inventory.php?unique=brands')
        .then(data => {
            displayFilterButtons('brand-filters', data, 'brand');
        })
        .catch(error => console.error('Error loading brands:', error));

    // Load generic names
    API.get('/api/inventory.php?unique=genericNames')
        .then(data => {
            displayFilterButtons('generic-name-filters', data, 'genericName');
        })
        .catch(error => console.error('Error loading generic names:', error));

    // Load categories
    API.get('/api/inventory.php?unique=categories')
        .then(data => {
            displayFilterButtons('category-filters', data, 'category');
        })
        .catch(error => console.error('Error loading categories:', error));

    // Load suppliers
    API.get('/api/inventory.php?unique=suppliers')
        .then(data => {
            displayFilterButtons('supplier-filters', data, 'supplier');
        })
        .catch(error => console.error('Error loading suppliers:', error));

    // Load prescription requirements
    API.get('/api/inventory.php?unique=prescriptionRequirements')
        .then(data => {
            displayFilterButtons('prescription-filters', data, 'requiresPrescription');
        })
        .catch(error => console.error('Error loading prescription requirements:', error));
}

// Display filter buttons
function displayFilterButtons(containerId, data, filterType) {
    const container = $(`#${containerId}`);
    if (!container) return;

    dom.empty(container);

    if (data && data.length > 0) {
        data.forEach(item => {
            const value = filterType === 'requiresPrescription' ? item.requiresPrescription : item.value;
            const displayValue = filterType === 'requiresPrescription' ? item.displayValue : item.displayValue;

            const button = dom.create('button');
            button.className = 'filter-btn';
            button.dataset.value = value;
            button.dataset.type = filterType;
            button.textContent = displayValue;

            dom.on(button, 'click', () => toggleFilter(filterType, value, displayValue));
            dom.append(container, button);
        });
    } else {
        const p = dom.create('p');
        p.className = 'no-filters';
        p.textContent = 'No options available';
        dom.append(container, p);
    }
}

// Toggle filter
function toggleFilter(type, value, displayValue) {
    const index = activeFilters[type].indexOf(value);

    if (index > -1) {
        // Remove filter
        activeFilters[type].splice(index, 1);
    } else {
        // Add filter
        activeFilters[type].push(value);
    }

    updateFilterButtons();
    updateActiveFiltersDisplay();
    applyFilters();
}

// Update filter button states
function updateFilterButtons() {
    const filterBtns = $$('.filter-btn');
    dom.removeAllClass(filterBtns, 'active');

    Object.keys(activeFilters).forEach(type => {
        activeFilters[type].forEach(value => {
            const button = $(`.filter-btn[data-type="${type}"][data-value="${value}"]`);
            if (button) {
                dom.addClass(button, 'active');
            }
        });
    });
}

// Update active filters display
function updateActiveFiltersDisplay() {
    const tagsContainer = $('#filter-tags');
    const activeFiltersDiv = $('#active-filters');

    if (!tagsContainer || !activeFiltersDiv) return;

    dom.empty(tagsContainer);
    let hasActiveFilters = false;

    Object.keys(activeFilters).forEach(type => {
        activeFilters[type].forEach(value => {
            hasActiveFilters = true;

            // Get display value
            let displayValue = value;
            if (type === 'requiresPrescription') {
                displayValue = value == 1 ? 'Yes' : 'No';
            } else {
                // Find the display value from the button
                const button = $(`.filter-btn[data-type="${type}"][data-value="${value}"]`);
                if (button) {
                    displayValue = button.textContent.trim();
                }
            }

            const tag = dom.create('span');
            tag.className = 'filter-tag';

            const label = dom.create('span');
            label.className = 'filter-label';
            label.textContent = `${type}: ${displayValue}`;
            dom.append(tag, label);

            const removeBtn = dom.create('button');
            removeBtn.className = 'remove-filter';
            removeBtn.dataset.type = type;
            removeBtn.dataset.value = value;
            removeBtn.innerHTML = '<i class="fas fa-times"></i>';

            dom.on(removeBtn, 'click', () => toggleFilter(type, value));
            dom.append(tag, removeBtn);

            dom.append(tagsContainer, tag);
        });
    });

    if (hasActiveFilters) {
        dom.show(activeFiltersDiv);
    } else {
        dom.hide(activeFiltersDiv);
    }
}

// Clear all filters
function clearAllFilters() {
    activeFilters = {
        brand: [],
        genericName: [],
        category: [],
        supplier: [],
        requiresPrescription: []
    };

    updateFilterButtons();
    updateActiveFiltersDisplay();
    applyFilters();
}

// Load inventory data
function loadInventory() {
    const loadingState = $('#loading-state');
    const catalogGrid = $('#catalog-grid');

    if (loadingState) dom.show(loadingState);
    if (catalogGrid) dom.hide(catalogGrid);

    API.get('/api/inventory.php')
        .then(data => {
            allProducts = data;
            if (loadingState) dom.hide(loadingState);
            if (catalogGrid) dom.show(catalogGrid);
            applyFilters();
        })
        .catch(error => {
            console.error('Error loading inventory:', error);
            if (loadingState) dom.hide(loadingState);
            const emptyState = $('#empty-state');
            if (emptyState) dom.show(emptyState);
        });
}

// Apply filters and search
function applyFilters() {
    const searchInput = $('#search-input');
    const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';

    filteredProducts = allProducts.filter(product => {
        // Search filter
        if (searchTerm) {
            const supplierText = product.supplierName || product.supplier || '';
            const searchableText = `${product.brand} ${product.genericName} ${product.category} ${supplierText}`.toLowerCase();
            if (!searchableText.includes(searchTerm)) {
                return false;
            }
        }

        // Active filters
        for (const [type, values] of Object.entries(activeFilters)) {
            if (values.length > 0) {
                if (type === 'requiresPrescription') {
                    if (!values.includes(product[type])) {
                        return false;
                    }
                } else {
                    const productValue = product[type].toLowerCase();
                    const hasMatch = values.some(value => value.toLowerCase() === productValue);
                    if (!hasMatch) {
                        return false;
                    }
                }
            }
        }

        return true;
    });

    displayProducts(filteredProducts);
}

// Display products as cards
function displayProducts(products) {
    const catalogGrid = $('#catalog-grid');
    const emptyState = $('#empty-state');

    if (!catalogGrid) return;

    dom.empty(catalogGrid);

    if (products.length === 0) {
        if (emptyState) dom.show(emptyState);
        if (catalogGrid) dom.hide(catalogGrid);
        updateResultsCount(0, allProducts.length);
        return;
    }

    if (emptyState) dom.hide(emptyState);
    if (catalogGrid) dom.show(catalogGrid);

    products.forEach(product => {
        const card = createProductCard(product);
        dom.append(catalogGrid, card);
    });

    updateResultsCount(products.length, allProducts.length);
}

// Create a product card element
function createProductCard(product) {
    const card = dom.create('div');
    card.className = 'product-card';
    dom.on(card, 'click', () => toggleCardExpansion(card));

    // Card Header
    const cardHeader = dom.create('div');
    cardHeader.className = 'card-header';

    const cardBrand = dom.create('div');
    cardBrand.className = 'card-brand';
    cardBrand.textContent = product.brand;
    dom.append(cardHeader, cardBrand);

    const cardStatus = dom.create('div');
    cardStatus.className = 'card-status';
    const statusClass = getStatusClass(product.status);
    const statusSpan = dom.create('span');
    statusSpan.className = `status ${statusClass}`;
    statusSpan.textContent = product.status;
    dom.append(cardStatus, statusSpan);
    dom.append(cardHeader, cardStatus);

    dom.append(card, cardHeader);

    // Card Body
    const cardBody = dom.create('div');
    cardBody.className = 'card-body';

    const cardGenericName = dom.create('div');
    cardGenericName.className = 'card-generic-name';
    cardGenericName.textContent = product.genericName;
    dom.append(cardBody, cardGenericName);

    const cardCategory = dom.create('div');
    cardCategory.className = 'card-category';
    cardCategory.textContent = product.category;
    dom.append(cardBody, cardCategory);

    const cardSupplier = dom.create('div');
    cardSupplier.className = 'card-supplier';
    cardSupplier.textContent = `Supplier: ${product.supplierName || product.supplier || ''}`;
    dom.append(cardBody, cardSupplier);

    // Card Details
    const cardDetails = dom.create('div');
    cardDetails.className = 'card-details';

    const expirationDate = new Date(product.expirationDate);
    const today = new Date();
    const daysToExpiry = Math.ceil((expirationDate - today) / (1000 * 60 * 60 * 24));
    let expiryClass = '';
    if (daysToExpiry < 0) {
        expiryClass = 'expired';
    } else if (daysToExpiry <= 30) {
        expiryClass = 'expiring-soon';
    }

    const details = [
        { label: 'Stock', value: product.stock },
        { label: 'Price', value: `₱${parseFloat(product.price).toFixed(2)}` },
        { label: 'Expiry', value: product.expirationDate, class: expiryClass }
    ];

    details.forEach(detail => {
        const detailItem = dom.create('div');
        detailItem.className = 'detail-item';

        const detailLabel = dom.create('div');
        detailLabel.className = 'detail-label';
        detailLabel.textContent = detail.label;
        dom.append(detailItem, detailLabel);

        const detailValue = dom.create('div');
        detailValue.className = 'detail-value';
        if (detail.class) {
            detailValue.classList.add(detail.class);
        }
        detailValue.textContent = detail.value;
        dom.append(detailItem, detailValue);

        dom.append(cardDetails, detailItem);
    });

    dom.append(cardBody, cardDetails);
    dom.append(card, cardBody);

    // Card Footer
    const cardFooter = dom.create('div');
    cardFooter.className = 'card-footer';

    const prescriptionIndicator = dom.create('div');
    prescriptionIndicator.className = 'prescription-indicator';

    const prescriptionIcon = dom.create('i');
    prescriptionIcon.className = product.requiresPrescription ?
        'fas fa-prescription-bottle-medical' :
        'fas fa-prescription-bottle';
    dom.append(prescriptionIndicator, prescriptionIcon);

    const prescriptionText = dom.create('span');
    prescriptionText.textContent = product.requiresPrescription ? 'Prescription Required' : 'No Prescription Required';
    dom.append(prescriptionIndicator, prescriptionText);

    dom.append(cardFooter, prescriptionIndicator);
    dom.append(card, cardFooter);

    return card;
}

// Get status class for styling
function getStatusClass(status) {
    switch (status) {
        case 'Good': return 'status-good';
        case 'Low Stock': return 'status-low-stock';
        case 'Expiring Soon': return 'status-expiring';
        case 'Low Stock & Expiring Soon': return 'status-critical';
        case 'Expired': return 'status-expired';
        case 'No Stock': return 'status-no-stock';
        default: return '';
    }
}

// Toggle card expansion
function toggleCardExpansion(card) {
    const allCards = $$('.product-card');
    const isExpanded = card.classList.contains('expanded');

    // Minimize all cards first
    allCards.forEach(c => {
        c.classList.remove('expanded');
    });

    // If the clicked card wasn't expanded, expand it
    if (!isExpanded) {
        card.classList.add('expanded');
    }
}

// Update results count
function updateResultsCount(current, total) {
    const currentCount = $('#current-count');
    const totalCount = $('#total-count');

    if (currentCount) dom.text(currentCount, current);
    if (totalCount) dom.text(totalCount, total);
}
