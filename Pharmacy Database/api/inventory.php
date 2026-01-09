<?php
/**
 * Inventory API Endpoint
 */

// Enable CORS for development
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Include database configuration
define('DB_ACCESS', true);
require_once '../database/db_config.php';

// Start session for user tracking
session_start();

// Disable error display for API responses
ini_set('display_errors', 0);
error_reporting(E_ALL);
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    $log = __DIR__ . '/inventory_error.log';
    $msg = date('Y-m-d H:i:s') . " ERROR [$errno] $errstr in $errfile on line $errline\n";
    file_put_contents($log, $msg, FILE_APPEND);
});
set_exception_handler(function($e) {
    $log = __DIR__ . '/inventory_error.log';
    $msg = date('Y-m-d H:i:s') . " EXCEPTION: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine() . "\n";
    file_put_contents($log, $msg, FILE_APPEND);
});

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// Get request data
$input = json_decode(file_get_contents('php://input'), true);

// Route to appropriate function
switch ($method) {
    case 'GET':
        handleGet();
        break;
    case 'POST':
        handlePost($input);
        break;
    case 'PUT':
        handlePut($input);
        break;
    case 'DELETE':
        handleDelete($input);
        break;
    default:
        sendJSON(false, null, 'Invalid request method');
}

/**
 * Handle GET requests - Read inventory
 */
function handleGet() {
    $id = $_GET['id'] ?? null;
    $search = $_GET['search'] ?? null;
    $lowStock = $_GET['lowStock'] ?? null;
    $expiringSoon = $_GET['expiringSoon'] ?? null;
    $expired = $_GET['expired'] ?? null;
    $noStock = $_GET['noStock'] ?? null;
    $unique = $_GET['unique'] ?? null;
    $brand = $_GET['brand'] ?? null;
    $genericName = $_GET['genericName'] ?? null;
    $category = $_GET['category'] ?? null;
    $supplier = $_GET['supplier'] ?? null;
    $requiresPrescription = $_GET['requiresPrescription'] ?? null;
    $limit = $_GET['limit'] ?? null;
    $offset = $_GET['offset'] ?? null;

    if ($id) {
        // Get specific item
        $sql = "SELECT i.*, s.supplierId as supplierId, s.name as supplierName
                FROM inventory i
                LEFT JOIN suppliers s ON i.supplierId = s.supplierId
                WHERE i.productId = ? AND i.isActive = TRUE";
        $result = executeQuery($sql, [$id], 's');

        if (!empty($result)) {
            sendJSON(true, $result[0]);
        } else {
            sendJSON(false, null, 'Item not found');
        }
    } elseif ($lowStock) {
        // Get low stock items
        $sql = "SELECT * FROM view_low_stock";
        $result = executeQuery($sql);
        sendJSON(true, $result);
    } elseif ($expiringSoon) {
        // Get expiring soon items
        $sql = "SELECT * FROM view_expiring_soon";
        $result = executeQuery($sql);
        sendJSON(true, $result);
    } elseif ($search) {
        // Search inventory (exclude inactive products for statistics)
        $searchTerm = "%$search%";
        $sql = "SELECT * FROM inventory
                WHERE isActive = TRUE
                AND (brand LIKE ? OR genericName LIKE ? OR category LIKE ?)
                ORDER BY brand ASC";
        if ($limit) {
            $sql .= " LIMIT ?";
            if ($offset) {
                $sql .= " OFFSET ?";
                $result = executeQuery($sql, [$searchTerm, $searchTerm, $searchTerm, (int)$limit, (int)$offset], 'sssii');
            } else {
                $result = executeQuery($sql, [$searchTerm, $searchTerm, $searchTerm, (int)$limit], 'sssi');
            }
        } else {
            $result = executeQuery($sql, [$searchTerm, $searchTerm, $searchTerm], 'sss');
        }
        sendJSON(true, $result);
    } elseif ($unique) {
        // Get unique values for filter categories
        $fieldMap = [
            'brands' => 'brand',
            'genericNames' => 'genericName',
            'categories' => 'category',
            'suppliers' => 'supplier',
            'prescriptionRequirements' => 'requiresPrescription'
        ];

        if (isset($fieldMap[$unique])) {
            $field = $fieldMap[$unique];
            if ($field === 'requiresPrescription') {
                $sql = "SELECT DISTINCT $field, CASE WHEN $field = 1 THEN 'Yes' ELSE 'No' END as displayValue FROM inventory WHERE isActive = TRUE ORDER BY $field";
            } else {
                $sql = "SELECT DISTINCT LOWER($field) as value, $field as displayValue FROM inventory WHERE isActive = TRUE ORDER BY $field";
            }
            $result = executeQuery($sql);
            sendJSON(true, $result);
        } else {
            sendJSON(false, null, 'Invalid unique parameter');
        }
    } elseif ($brand || $genericName || $category || $supplier || $requiresPrescription !== null) {
        // Filter inventory based on provided parameters
        $whereConditions = ["isActive = TRUE"];
        $params = [];
        $types = '';

        if ($brand) {
            $whereConditions[] = "brand = ?";
            $params[] = $brand;
            $types .= 's';
        }
        if ($genericName) {
            $whereConditions[] = "genericName = ?";
            $params[] = $genericName;
            $types .= 's';
        }
        if ($category) {
            $whereConditions[] = "category = ?";
            $params[] = $category;
            $types .= 's';
        }
        if ($supplier) {
            $whereConditions[] = "supplier = ?";
            $params[] = $supplier;
            $types .= 's';
        }
        if ($requiresPrescription !== null) {
            $whereConditions[] = "requiresPrescription = ?";
            $params[] = (int)$requiresPrescription;
            $types .= 'i';
        }

        $sql = "SELECT i.*, s.supplierId as supplierId, s.name as supplierName
            FROM inventory i
            LEFT JOIN suppliers s ON i.supplierId = s.supplierId
            WHERE " . implode(' AND ', $whereConditions) . " ORDER BY i.createdAt DESC";

        if ($limit) {
            $sql .= " LIMIT ?";
            $params[] = (int)$limit;
            $types .= 'i';
            if ($offset) {
                $sql .= " OFFSET ?";
                $params[] = (int)$offset;
                $types .= 'i';
            }
        }

        $result = executeQuery($sql, $params, $types);

        // Get status data
        $lowStockIds = array_column(executeQuery("SELECT id FROM view_low_stock"), 'id');
        $expiringSoonIds = array_column(executeQuery("SELECT id FROM view_expiring_soon"), 'id');
        $expiredIds = array_column(executeQuery("SELECT productId FROM inventory WHERE expirationDate < CURDATE() AND isActive = TRUE"), 'productId');
        $noStockIds = array_column(executeQuery("SELECT productId FROM inventory WHERE stock = 0 AND isActive = TRUE"), 'productId');

        // Add status to each item
        foreach ($result as &$item) {
            $status = 'Good';
            $isLowStock = in_array($item['productId'], $lowStockIds);
            $isExpiringSoon = in_array($item['productId'], $expiringSoonIds);
            $isExpired = in_array($item['productId'], $expiredIds);
            $isNoStock = in_array($item['productId'], $noStockIds);

            if ($isNoStock) {
                $status = 'No Stock';
            } elseif ($isExpired) {
                $status = 'Expired';
            } elseif ($isLowStock && $isExpiringSoon) {
                $status = 'Low Stock & Expiring Soon';
            } elseif ($isLowStock) {
                $status = 'Low Stock';
            } elseif ($isExpiringSoon) {
                $status = 'Expiring Soon';
            }

            $item['status'] = $status;
        }

        sendJSON(true, $result ?: []);
    } else {
        // Get all active items
        $sql = "SELECT i.*, s.supplierId as supplierId, s.name as supplierName
            FROM inventory i
            LEFT JOIN suppliers s ON i.supplierId = s.supplierId
            WHERE i.isActive = TRUE ORDER BY i.createdAt DESC";
        if ($limit) {
            $sql .= " LIMIT ?";
            if ($offset) {
                $sql .= " OFFSET ?";
                $result = executeQuery($sql, [(int)$limit, (int)$offset], 'ii');
            } else {
                $result = executeQuery($sql, [(int)$limit], 'i');
            }
        } else {
            $result = executeQuery($sql);
        }

        // Get status data
        $lowStockIds = array_column(executeQuery("SELECT id FROM view_low_stock"), 'id');
        $expiringSoonIds = array_column(executeQuery("SELECT id FROM view_expiring_soon"), 'id');
        $expiredIds = array_column(executeQuery("SELECT productId FROM inventory WHERE expirationDate < CURDATE() AND isActive = TRUE"), 'productId');
        $noStockIds = array_column(executeQuery("SELECT productId FROM inventory WHERE stock = 0 AND isActive = TRUE"), 'productId');

        // Add status to each item
        foreach ($result as &$item) {
            $status = 'Good';
            $isLowStock = in_array($item['productId'], $lowStockIds);
            $isExpiringSoon = in_array($item['productId'], $expiringSoonIds);
            $isExpired = in_array($item['productId'], $expiredIds);
            $isNoStock = in_array($item['productId'], $noStockIds);

            if ($isNoStock) {
                $status = 'No Stock';
            } elseif ($isExpired) {
                $status = 'Expired';
            } elseif ($isLowStock && $isExpiringSoon) {
                $status = 'Low Stock & Expiring Soon';
            } elseif ($isLowStock) {
                $status = 'Low Stock';
            } elseif ($isExpiringSoon) {
                $status = 'Expiring Soon';
            }

            $item['status'] = $status;
        }

        sendJSON(true, $result ?: []);
    }
}

/**
 * Handle POST requests - Create new item
 */
function handlePost($data) {
        // Log all POST data for debugging supply new product issues
        $logFile = __DIR__ . '/inventory_post_debug.log';
        $logEntry = sprintf("[%s] INVENTORY POST: %s\n", date('Y-m-d H:i:s'), json_encode($data));
        @file_put_contents($logFile, $logEntry, FILE_APPEND);
    // Only privileged roles can create inventory items
    requireRole(['Super Admin','Admin','Manager']);
    // Validate required fields
    $required = ['brand', 'genericName', 'category', 'manufacturedDate',
                 'expirationDate', 'stock', 'lowStockThreshold', 'price', 'contact', 'supplierId'];

    $missing = validateRequired($data, $required);

    // Remove legacy supplier check, only require supplierId
    // Already handled by $required above

    if (!empty($missing)) {
        sendJSON(false, null, 'Missing required fields: ' . implode(', ', $missing));
    }

    // Sanitize input
    $brand = sanitizeInput($data['brand']);
    $genericName = sanitizeInput($data['genericName']);
    $category = sanitizeInput($data['category']);
    $manufacturedDate = sanitizeInput($data['manufacturedDate']);
    $expirationDate = sanitizeInput($data['expirationDate']);
    $stock = (int)$data['stock'];
    $lowStockThreshold = (int)$data['lowStockThreshold'];
    $price = (float)$data['price'];
    $supplierId = sanitizeInput($data['supplierId']);
    $contact = sanitizeInput($data['contact']);
    $requiresPrescription = isset($data['requiresPrescription']) ? (int)$data['requiresPrescription'] : 0;

    // Validate dates
    if (strtotime($expirationDate) <= strtotime($manufacturedDate)) {
        sendJSON(false, null, 'Expiration date must be after manufactured date');
    }

    // Generate Product ID
    $productId = generateProductId();

    // Generate Batch Number
    $batchNo = generateBatchNo($brand, $genericName);

    // Insert into database
    $sql = "INSERT INTO inventory (productId, brand, genericName, category, manufacturedDate,
            expirationDate, stock, batchNo, price, supplierId, contact, requiresPrescription)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    // Detailed SQL insert logging for debugging supplierId issues
    $sqlLogFile = __DIR__ . '/inventory_sql_debug.log';
    $sqlLogEntry = sprintf("[%s] SQL INSERT: %s\nPARAMS: %s\nTYPES: %s\n", date('Y-m-d H:i:s'),
        "INSERT INTO inventory (productId, brand, genericName, category, manufacturedDate, expirationDate, stock, batchNo, price, supplierId, contact, requiresPrescription) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
        json_encode([
            $productId, $brand, $genericName, $category, $manufacturedDate,
            $expirationDate, $stock, $batchNo, $price, $supplierId, $contact, $requiresPrescription
        ]),
        'ssssssissdsi'
    );
    @file_put_contents($sqlLogFile, $sqlLogEntry, FILE_APPEND);

    // Extra debug: log supplierId and all params before insert
    $debugLogFile = __DIR__ . '/inventory_post_debug.log';
    $debugLogEntry = sprintf("[%s] DEBUG: About to insert with supplierId=%s, params=%s\n", date('Y-m-d H:i:s'), $supplierId, json_encode([
        $productId, $brand, $genericName, $category, $manufacturedDate,
        $expirationDate, $stock, $batchNo, $price, $supplierId, $contact, $requiresPrescription
    ]));
    @file_put_contents($debugLogFile, $debugLogEntry, FILE_APPEND);

    $result = executeQuery($sql, [
        $productId, $brand, $genericName, $category, $manufacturedDate,
        $expirationDate, $stock, $batchNo, $price, $supplierId, $contact, $requiresPrescription
    ], 'ssssssissssi');

    $sqlResultLog = sprintf("[%s] SQL RESULT: %s\n", date('Y-m-d H:i:s'), var_export($result, true));
    @file_put_contents($sqlLogFile, $sqlResultLog, FILE_APPEND);

    // Extra debug: fetch and log the just-inserted row
    $fetchDebug = executeQuery("SELECT * FROM inventory WHERE productId = ?", [$productId], 's');
    $fetchDebugLog = sprintf("[%s] POST-INSERT ROW: %s\n", date('Y-m-d H:i:s'), json_encode($fetchDebug));
    @file_put_contents($debugLogFile, $fetchDebugLog, FILE_APPEND);
    
    if ($result !== false) {
        // Log activity
        $userId = $_SESSION['userId'] ?? 'system';
        $username = $_SESSION['username'] ?? 'system';
        logActivity($userId, $username, 'CREATE', 'INVENTORY', $productId, 
                   "Created product: $brand - $genericName");
        
        // Return `productId` key to match frontend expectations
        sendJSON(true, ['productId' => $productId], 'Product created successfully');
    } else {
        sendJSON(false, null, 'Failed to create product');
    }
}

/**
 * Handle PUT requests - Update existing item
 */
function handlePut($data) {
    // Only privileged roles can update inventory
    requireRole(['Super Admin','Admin','Manager']);
    if (empty($data['id'])) {
        sendJSON(false, null, 'Product ID is required');
    }

    $id = sanitizeInput($data['id']);
    
    // Build update query dynamically based on provided fields
    $updateFields = [];
    $params = [];
    $types = '';
    
    // Only allow updating supplierId, not legacy 'supplier'
    $allowedFields = ['brand', 'genericName', 'category', 'manufacturedDate',
                      'expirationDate', 'stock', 'lowStockThreshold', 'price', 'supplierId', 'contact'];
    
    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            // Map supplierId to correct column
            $col = ($field === 'supplierId') ? 'supplierId' : $field;
            $updateFields[] = "$col = ?";
            $params[] = sanitizeInput($data[$field]);
            $types .= in_array($field, ['stock']) ? 'i' : (in_array($field, ['price']) ? 'd' : 's');
        }
    }
    
    if (empty($updateFields)) {
        sendJSON(false, null, 'No fields to update');
    }
    
    // Add ID to params
    $params[] = $id;
    $types .= 's';
    
    $sql = "UPDATE inventory SET " . implode(', ', $updateFields) . " WHERE productId = ?";
    $result = executeQuery($sql, $params, $types);
    
    if ($result !== false) {
        // Log activity
        $userId = $_SESSION['userId'] ?? 'system';
        $username = $_SESSION['username'] ?? 'system';
        logActivity($userId, $username, 'UPDATE', 'INVENTORY', $id, 
                   "Updated product: " . ($data['brand'] ?? 'N/A'));
        
        sendJSON(true, null, 'Product updated successfully');
    } else {
        sendJSON(false, null, 'Failed to update product');
    }
}

/**
 * Handle DELETE requests - Archive item
 */
function handleDelete($data) {
    // Only admins can archive/delete products
    requireRole(['Super Admin','Admin']);
    if (empty($data['productId'])) {
        // Try to get from query string
        $id = $_GET['id'] ?? null;
        if (!$id) {
            sendJSON(false, null, 'Product ID is required');
        }
    } else {
        $id = $data['productId'];
    }
    
    $id = sanitizeInput($id);
    $userId = $_SESSION['userId'] ?? 'system';
    $username = $_SESSION['username'] ?? 'system';
    
    // Call stored procedure to archive
    $conn = getDBConnection();
    $stmt = $conn->prepare("CALL ArchiveInventoryItem(?, ?, @success, @message)");
    $stmt->bind_param('ss', $id, $username);
    $stmt->execute();
    $stmt->close();
    
    // Get output variables
    $result = $conn->query("SELECT @success AS success, @message AS message");
    $output = $result->fetch_assoc();
    
    if ($output['success']) {
        logActivity($userId, $username, 'ARCHIVE', 'INVENTORY', $id, 
                   "Archived product: $id");
        sendJSON(true, null, $output['message']);
    } else {
        sendJSON(false, null, $output['message']);
    }
}

/**
 * Generate Product ID
 * Format: Prod{YYYYMMDD}{counter}
 */
function generateProductId() {
    $date = date('Ymd');
    
    $conn = getDBConnection();
    $stmt = $conn->prepare("CALL GetNextCounter('Prod', @counter)");
    $stmt->execute();
    $stmt->close();
    
    $result = $conn->query("SELECT @counter AS counter");
    $row = $result->fetch_assoc();
    $counter = str_pad($row['counter'], 4, '0', STR_PAD_LEFT);
    
    return "Prod{$date}{$counter}";
}

/**
 * Generate Batch Number
 * Format: {YYYY}{counter}
 */
function generateBatchNo($brand, $genericName) {
    $year = date('Y');
    $key = "batch_{$year}_" . preg_replace('/[^a-zA-Z0-9]/', '', $brand . $genericName);
    
    // Get or create counter (simplified version)
    $sql = "SELECT currentValue FROM counters WHERE counterType = ? AND counterDate = CURDATE()";
    $result = executeQuery($sql, [$key], 's');
    
    if (!empty($result)) {
        $counter = $result[0]['currentValue'] + 1;
        $sql = "UPDATE counters SET currentValue = ? WHERE counterType = ? AND counterDate = CURDATE()";
        executeQuery($sql, [$counter, $key], 'is');
    } else {
        $counter = 1;
        $sql = "INSERT INTO counters (counterType, counterDate, currentValue) VALUES (?, CURDATE(), ?)";
        executeQuery($sql, [$key, $counter], 'si');
    }
    
    return $year . str_pad($counter, 4, '0', STR_PAD_LEFT);
}
?>

