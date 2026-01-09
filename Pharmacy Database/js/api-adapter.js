/**
 * API Adapter - Replaces localStorage functions with API calls
 * This file bridges the old localStorage code with the new PHP backend
 */

// ============================================
// INVENTORY FUNCTIONS
// ============================================

async function readInventory(filter = null) {
    try {
        const url = filter ? `api/inventory.php?id=${filter}` : 'api/inventory.php';
        const response = await fetch(url);
        const result = await response.json();
        
        if (result.success) {
            return filter ? [result.data] : (result.data || []);
        }
        console.error('Read inventory failed:', result.message);
        return filter ? [] : [];
    } catch (error) {
        console.error('Error reading inventory:', error);
        return filter ? [] : [];
    }
}

async function createInventory(item) {
    try {
        const response = await fetch('api/inventory.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(item)
        });
        const result = await response.json();
        
        if (result.success) {
            return result.data;
        }
        throw new Error(result.message);
    } catch (error) {
        console.error('Error creating inventory:', error);
        throw error;
    }
}

async function updateInventory(id, updates) {
    try {
        const response = await fetch('api/inventory.php', {
            method: 'PUT',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({id, ...updates})
        });
        const result = await response.json();
        
        if (result.success) {
            return true;
        }
        throw new Error(result.message);
    } catch (error) {
        console.error('Error updating inventory:', error);
        throw error;
    }
}

async function deleteInventory(id) {
    try {
        const response = await fetch(`api/inventory.php?id=${id}`, {
            method: 'DELETE'
        });
        const result = await response.json();
        return result.success;
    } catch (error) {
        console.error('Error deleting inventory:', error);
        return false;
    }
}

// ============================================
// SALES FUNCTIONS
// ============================================

async function readSales(filter = null) {
    try {
        const url = filter ? `api/sales.php?id=${filter}` : 'api/sales.php';
        const response = await fetch(url);
        const result = await response.json();
        
        if (result.success) {
            return filter ? [result.data] : (result.data || []);
        }
        return [];
    } catch (error) {
        console.error('Error reading sales:', error);
        return [];
    }
}

async function createSale(saleData) {
    try {
        const response = await fetch('api/sales.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(saleData)
        });
        const result = await response.json();
        
        if (result.success) {
            return result.data;
        }
        throw new Error(result.message);
    } catch (error) {
        console.error('Error creating sale:', error);
        throw error;
    }
}

async function updateSale(id, updates) {
    try {
        const response = await fetch('api/sales.php', {
            method: 'PUT',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({id, ...updates})
        });
        const result = await response.json();
        
        if (result.success) {
            return true;
        }
        throw new Error(result.message);
    } catch (error) {
        console.error('Error updating sale:', error);
        throw error;
    }
}

async function deleteSale(id) {
    try {
        const response = await fetch(`api/sales.php?id=${id}`, {
            method: 'DELETE'
        });
        const result = await response.json();
        return result.success;
    } catch (error) {
        console.error('Error deleting sale:', error);
        return false;
    }
}

// ============================================
// EMPLOYEE RECORDS FUNCTIONS (REMOVED)
// ============================================
/*
async function readRecords(filter = null) {
    console.warn('readRecords is deprecated. Use readEmployees instead.');
    return await readEmployees(filter);
}

async function createRecord(recordData) {
    console.warn('createRecord is deprecated. Use createEmployee instead.');
    return await createEmployee(recordData);
}

async function updateRecord(id, updates) {
    console.warn('updateRecord is deprecated. Use updateEmployee instead.');
    return await updateEmployee(id, updates);
}

async function deleteRecord(id) {
    console.warn('deleteRecord is deprecated. Use deleteEmployee instead.');
    return await deleteEmployee(id);
}
*/

// ============================================
// EMPLOYEE ACCOUNTS FUNCTIONS
// ============================================

async function readEmployees(filter = null) {
    try {
        const url = filter ? `api/accounts.php?id=${filter}` : 'api/accounts.php';
        const response = await fetch(url);
        const result = await response.json();
        
        if (result.success) {
            return filter ? [result.data] : (result.data || []);
        }
        return [];
    } catch (error) {
        console.error('Error reading employees:', error);
        return [];
    }
}

async function createEmployee(employeeData) {
    try {
        const response = await fetch('api/accounts.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(employeeData)
        });
        let result;
        try {
            result = await response.json();
        } catch (jsonError) {
            console.error('Error parsing JSON response from createEmployee:', jsonError);
            alert('Server error: Could not create user. Please check your input or try again later.');
            throw new Error('Invalid JSON response from server');
        }
        if (result.success) {
            return result.data;
        }
        throw new Error(result.message);
    } catch (error) {
        console.error('Error creating employee:', error);
        alert('Error creating user: ' + (error.message || error));
        throw error;
    }
}

async function updateEmployee(id, updates) {
    try {
        const response = await fetch('api/accounts.php', {
            method: 'PUT',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({id, ...updates})
        });
        const result = await response.json();
        
        if (result.success) {
            return true;
        }
        throw new Error(result.message);
    } catch (error) {
        console.error('Error updating employee:', error);
        throw error;
    }
}

async function deleteEmployee(id) {
    try {
        const response = await fetch(`api/accounts.php?id=${id}`, {
            method: 'DELETE'
        });
        const result = await response.json();
        return result.success;
    } catch (error) {
        console.error('Error deleting employee:', error);
        return false;
    }
}

// ============================================
// SUPPLIER FUNCTIONS
// ============================================

async function readSuppliers(filter = null) {
    try {
        const url = filter ? `api/suppliers.php?id=${filter}` : 'api/suppliers.php';
        const response = await fetch(url);
        const result = await response.json();

        if (result.success) {
            return filter ? [result.data] : (result.data || []);
        }
        console.error('Read suppliers failed:', result.message);
        return filter ? [] : [];
    } catch (error) {
        console.error('Error reading suppliers:', error);
        return filter ? [] : [];
    }
}

async function createSupplier(supplierData) {
    try {
        const response = await fetch('api/suppliers.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(supplierData)
        });
        const result = await response.json();

        if (result.success) {
            return result.data;
        }
        throw new Error(result.message);
    } catch (error) {
        console.error('Error creating supplier:', error);
        throw error;
    }
}

async function updateSupplier(id, updates) {
    try {
        const response = await fetch(`api/suppliers.php?id=${id}`, {
            method: 'PUT',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(updates)
        });
        const result = await response.json();

        if (result.success) {
            return true;
        }
        throw new Error(result.message);
    } catch (error) {
        console.error('Error updating supplier:', error);
        throw error;
    }
}

async function deleteSupplier(id) {
    try {
        const response = await fetch(`api/suppliers.php?id=${id}`, {
            method: 'DELETE'
        });
        const result = await response.json();
        return result.success;
    } catch (error) {
        console.error('Error deleting supplier:', error);
        return false;
    }
}

// ============================================
// DELIVERIES FUNCTIONS
// ============================================

async function readDeliveries(filter = null) {
    try {
        const url = filter ? `api/deliveries.php?id=${filter}` : 'api/deliveries.php';
        const response = await fetch(url);
        const result = await response.json();

        if (result.success) {
            return filter ? [result.data] : (result.data || []);
        }
        console.error('Read deliveries failed:', result.message);
        return filter ? [] : [];
    } catch (error) {
        console.error('Error reading deliveries:', error);
        return filter ? [] : [];
    }
}

async function createDelivery(deliveryData) {
    try {
        const response = await fetch('api/deliveries.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(deliveryData)
        });
        const result = await response.json();

        if (result.success) {
            return result.data;
        }
        throw new Error(result.message);
    } catch (error) {
        console.error('Error creating delivery:', error);
        throw error;
    }
}

async function updateDelivery(id, updates) {
    try {
        const response = await fetch(`api/deliveries.php?id=${id}`, {
            method: 'PUT',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(updates)
        });
        const result = await response.json();

        if (result.success) {
            return true;
        }
        throw new Error(result.message);
    } catch (error) {
        console.error('Error updating delivery:', error);
        throw error;
    }
}

async function deleteDelivery(id) {
    try {
        const response = await fetch(`api/deliveries.php?id=${id}`, {
            method: 'DELETE'
        });
        const result = await response.json();
        return result.success;
    } catch (error) {
        console.error('Error deleting delivery:', error);
        return false;
    }
}

// ============================================
// HELPER FUNCTIONS
// ============================================

function loadData(key, defaultValue = []) {
    console.warn('loadData() is deprecated. Use async functions instead.');
    return defaultValue;
}

function saveData(key, data) {
    console.warn('saveData() is deprecated. Use async API functions instead.');
}

async function fetchAvailableProducts() {
    try {
        const response = await fetch('api/inventory.php');
        const result = await response.json();
        if (result.success) {
            return (result.data || []).filter(item => item.stock > 0);
        }
        return [];
    } catch (error) {
        console.error('Error fetching available products:', error);
        return [];
    }
}

async function fetchEmployees() {
    try {
        const response = await fetch('api/accounts.php');
        const result = await response.json();
        return result.success ? (result.data || []) : [];
    } catch (error) {
        console.error('Error fetching accounts:', error);
        return [];
    }
}

// ============================================
// API OBJECT FOR CATALOGS.JS
// ============================================

const API = {
    get: async function(url) {
        try {
            const response = await fetch(url);
            const result = await response.json();
            if (result.success) {
                return result.data;
            } else {
                throw new Error(result.message || 'API request failed');
            }
        } catch (error) {
            console.error('API GET error:', error);
            throw error;
        }
    }
};
