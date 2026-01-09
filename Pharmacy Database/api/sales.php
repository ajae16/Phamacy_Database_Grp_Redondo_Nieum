<?php
/**
 * Sales API Endpoint
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

define('DB_ACCESS', true);
require_once '../database/db_config.php';

session_start();

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

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
 * Handle GET requests
 */
function handleGet() {
    $id = $_GET['id'] ?? null;
    $date = $_GET['date'] ?? null;
    $startDate = $_GET['startDate'] ?? null;
    $endDate = $_GET['endDate'] ?? null;
    $details = $_GET['details'] ?? null;
    
    if ($id) {
        // Get specific sale
        if ($details) {
            $sql = "SELECT * FROM view_sales_details WHERE transactionId = ?";
        } else {
            $sql = "SELECT * FROM sales WHERE salesId = ?";
        }
        $result = executeQuery($sql, [$id], 's');
        sendJSON(true, $result[0] ?? null);
    } elseif ($date) {
        // Get sales by specific date
        $sql = "SELECT * FROM view_sales_details WHERE date = ? ORDER BY createdAt DESC";
        $result = executeQuery($sql, [$date], 's');
        sendJSON(true, $result ?: []);
    } elseif ($startDate && $endDate) {
        // Get sales in date range
        $sql = "SELECT * FROM view_sales_details WHERE date BETWEEN ? AND ? ORDER BY date DESC";
        $result = executeQuery($sql, [$startDate, $endDate], 'ss');
        sendJSON(true, $result ?: []);
    } else {
        // Get all sales with details
        $sql = "SELECT * FROM view_sales_details ORDER BY createdAt DESC LIMIT 1000";
        $result = executeQuery($sql);
        sendJSON(true, $result ?: []);
    }
}

/**
 * Handle POST requests - Create sale
 */
function handlePost($data) {
        requireRole(['Super Admin', 'Admin', 'Manager']);
    $required = ['productId', 'employeeId', 'quantitySold'];
    $missing = validateRequired($data, $required);
    
    if (!empty($missing)) {
        sendJSON(false, null, 'Missing required fields: ' . implode(', ', $missing));
    }
    
    $productId = sanitizeInput($data['productId']);
    $employeeId = sanitizeInput($data['employeeId']);
    $quantitySold = (int)$data['quantitySold'];
    $paymentMethod = sanitizeInput($data['paymentMethod'] ?? 'Cash');
    
    if ($quantitySold <= 0) {
        sendJSON(false, null, 'Quantity must be greater than 0');
    }
    
    // Use stored procedure to create sale with stock update
    $conn = getDBConnection();
    $stmt = $conn->prepare("CALL CreateSaleTransaction(?, ?, ?, ?, @transactionId, @success, @message)");
    $stmt->bind_param('ssis', $productId, $employeeId, $quantitySold, $paymentMethod);
    $stmt->execute();
    $stmt->close();
    
    // Get output variables
    $result = $conn->query("SELECT @transactionId AS transactionId, @success AS success, @message AS message");
    $output = $result->fetch_assoc();
    
    if ($output['success']) {
        $userId = $_SESSION['userId'] ?? $employeeId;
        $username = $_SESSION['username'] ?? 'system';
        logActivity($userId, $username, 'CREATE', 'SALES', $output['transactionId'], 
                   "Sale: Product $productId, Qty: $quantitySold");
        
        sendJSON(true, ['transactionId' => $output['transactionId']], $output['message']);
    } else {
        sendJSON(false, null, $output['message']);
    }
}

/**
 * Handle PUT requests - Update sale
 */
function handlePut($data) {
        requireRole(['Super Admin', 'Admin', 'Manager']);
    if (empty($data['id'])) {
        sendJSON(false, null, 'Transaction ID is required');
    }
    
    $id = sanitizeInput($data['id']);

    // Get old sale data
    $sql = "SELECT * FROM sales WHERE salesId = ?";
    $oldSale = executeQuery($sql, [$id], 's');
    
    if (empty($oldSale)) {
        sendJSON(false, null, 'Sale not found');
    }
    
    $oldSale = $oldSale[0];
    
    beginTransaction();
    
    try {
        // If product changed, restore old stock and decrease new stock
        if (isset($data['productId']) && $data['productId'] !== $oldSale['productId']) {
            // Restore old product stock
            $sql = "UPDATE inventory SET stock = stock + ? WHERE productId = ?";
            executeQuery($sql, [$oldSale['quantitySold'], $oldSale['productId']], 'is');

            // Check new product stock
            $sql = "SELECT stock, price, batchNo FROM inventory WHERE productId = ?";
            $newProduct = executeQuery($sql, [$data['productId']], 's');
            
            if (empty($newProduct)) {
                throw new Exception('New product not found');
            }
            
            $newProduct = $newProduct[0];
            $newQty = isset($data['quantitySold']) ? (int)$data['quantitySold'] : $oldSale['quantitySold'];
            
            if ($newProduct['stock'] < $newQty) {
                throw new Exception('Insufficient stock for new product');
            }
            
            // Decrease new product stock
            $sql = "UPDATE inventory SET stock = stock - ? WHERE productId = ?";
            executeQuery($sql, [$newQty, $data['productId']], 'is');
            
            // Update sale
            $totalAmount = $newProduct['price'] * $newQty;
            $sql = "UPDATE sales SET productId = ?, quantitySold = ?, totalAmount = ?, batchNo = ? WHERE salesId = ?";
            executeQuery($sql, [$data['productId'], $newQty, $totalAmount, $newProduct['batchNo'], $id], 'sidss');
            
        } elseif (isset($data['quantitySold']) && $data['quantitySold'] != $oldSale['quantitySold']) {
            // Same product, different quantity
            $newQty = (int)$data['quantitySold'];
            $qtyDiff = $newQty - $oldSale['quantitySold'];
            
            // Check if enough stock for increase
            if ($qtyDiff > 0) {
                $sql = "SELECT stock, price FROM inventory WHERE productId = ?";
                $product = executeQuery($sql, [$oldSale['productId']], 's')[0];

                if ($product['stock'] < $qtyDiff) {
                    throw new Exception('Insufficient stock');
                }
            }

            // Update inventory
            $sql = "UPDATE inventory SET stock = stock - ? WHERE productId = ?";
            executeQuery($sql, [$qtyDiff, $oldSale['productId']], 'is');

            // Get price for new total
            $sql = "SELECT price FROM inventory WHERE productId = ?";
            $product = executeQuery($sql, [$oldSale['productId']], 's')[0];
            $totalAmount = $product['price'] * $newQty;

            // Update sale
            $sql = "UPDATE sales SET quantitySold = ?, totalAmount = ? WHERE salesId = ?";
            executeQuery($sql, [$newQty, $totalAmount, $id], 'ids');
        }
        
        commitTransaction();
        
        $userId = $_SESSION['userId'] ?? 'system';
        $username = $_SESSION['username'] ?? 'system';
        logActivity($userId, $username, 'UPDATE', 'SALES', $id, "Updated sale $id");
        
        sendJSON(true, null, 'Sale updated successfully');
        
    } catch (Exception $e) {
        rollbackTransaction();
        sendJSON(false, null, $e->getMessage());
    }
}

/**
 * Handle DELETE requests - Archive sale
 */
function handleDelete($data) {
        requireRole(['Super Admin', 'Admin']);
    $id = $data['id'] ?? $_GET['id'] ?? null;
    
    if (!$id) {
        sendJSON(false, null, 'Transaction ID is required');
    }
    
    $id = sanitizeInput($id);
    $userId = $_SESSION['userId'] ?? 'system';
    $username = $_SESSION['username'] ?? 'system';
    
    // Get sale data
    $sql = "SELECT * FROM sales WHERE salesId = ?";
    $sale = executeQuery($sql, [$id], 's');

    if (empty($sale)) {
        sendJSON(false, null, 'Sale not found');
    }

    $sale = $sale[0];

    beginTransaction();

    try {
        // Move to archive
        $sql = "INSERT INTO archive_sales SELECT *, NOW(), ? FROM sales WHERE salesId = ?";
        executeQuery($sql, [$username, $id], 'ss');

        // Delete from sales
        $sql = "DELETE FROM sales WHERE salesId = ?";
        executeQuery($sql, [$id], 's');
        
        commitTransaction();
        
        logActivity($userId, $username, 'ARCHIVE', 'SALES', $id, "Archived sale $id");
        sendJSON(true, null, 'Sale archived successfully');
        
    } catch (Exception $e) {
        rollbackTransaction();
        sendJSON(false, null, 'Failed to archive sale');
    }
}
?>

