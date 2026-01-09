<?php
/**
 * Archive API Endpoint
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

define('DB_ACCESS', true);
require_once '../database/db_config.php';

session_start();

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);
$type = $input['type'] ?? $_GET['type'] ?? 'inventory';

switch ($method) {
    case 'GET':
        handleGet($type);
        break;
    case 'POST':
        handleRestore($type, $input);
        break;
    case 'DELETE':
        handlePermanentDelete($type, $input);
        break;
    default:
        sendJSON(false, null, 'Invalid request method');
}

function handleGet($type) {
    $validTypes = ['inventory', 'sales', 'records', 'accounts', 'suppliers'];

    if (!in_array($type, $validTypes)) {
        sendJSON(false, null, 'Invalid archive type');
    }

    $table = "archive_$type";
    $sql = "SELECT * FROM $table ORDER BY archivedAt DESC";
    $result = executeQuery($sql);

    sendJSON(true, $result ?: []);
}

function handleRestore($type, $data) {
        requireRole(['Super Admin', 'Admin']);
    if (empty($data['id'])) {
        sendJSON(false, null, 'ID is required');
    }
    
    $id = sanitizeInput($data['id']);
    $userId = $_SESSION['userId'] ?? 'system';
    $username = $_SESSION['username'] ?? 'system';
    
    beginTransaction();
    
    try {
        switch ($type) {
            case 'inventory':
                $sql = "INSERT INTO inventory SELECT id, brand, genericName, category,
                        manufacturedDate, expirationDate, stock, batchNo, price, supplier, contact,
                        TRUE, NOW(), NOW() FROM archive_inventory WHERE id = ?";
                break;
            case 'sales':
                $sql = "INSERT INTO sales SELECT id, date, productId, employeeId, quantitySold,
                        totalAmount, batchNo, 'Cash', NOW(), NOW() FROM archive_sales WHERE id = ?";
                break;
            case 'records':
                $sql = "INSERT INTO employee_records (id, firstName, middleName, lastName, fullName,
                        contact, role, schedule, birthday, address, isActive, createdAt, updatedAt)
                        SELECT id, firstName, middleName, lastName, fullName, contact, role, schedule,
                        birthday, address, TRUE, NOW(), NOW() FROM archive_records WHERE id = ?";
                break;
                case 'accounts':
                // For accounts, restore without userRole (RBAC)
                $sql = "INSERT INTO accounts (accountId, firstName, middleName, lastName, fullName, contact, username, password)
                    SELECT accountId, firstName, middleName, lastName, fullName, contact, username,
                    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
                    FROM archive_accounts WHERE accountId = ?";
                break;
            case 'suppliers':
                $sql = "INSERT INTO suppliers SELECT id, name, contactPerson, phone, email, address,
                        TRUE, NOW(), NOW() FROM archive_suppliers WHERE id = ?";
                break;
            default:
                throw new Exception('Invalid type');
        }
        
        executeQuery($sql, [$id], 's');
        
        $archiveTable = "archive_$type";
        $deleteSql = "DELETE FROM $archiveTable WHERE id = ?";
        executeQuery($deleteSql, [$id], 's');
        
        commitTransaction();
        
        logActivity($userId, $username, 'RESTORE', strtoupper($type), $id, "Restored from archive");
        sendJSON(true, null, 'Item restored successfully');
        
    } catch (Exception $e) {
        rollbackTransaction();
        sendJSON(false, null, 'Failed to restore item: ' . $e->getMessage());
    }
}

function handlePermanentDelete($type, $data) {
        requireRole(['Super Admin', 'Admin']);
    $id = $data['id'] ?? $_GET['id'] ?? null;
    
    if (!$id) {
        sendJSON(false, null, 'ID is required');
    }
    
    $id = sanitizeInput($id);
    $userId = $_SESSION['userId'] ?? 'system';
    $username = $_SESSION['username'] ?? 'system';
    
    $table = "archive_$type";
    $sql = "DELETE FROM $table WHERE id = ?";
    $result = executeQuery($sql, [$id], 's');
    
    if ($result !== false) {
        logActivity($userId, $username, 'DELETE', strtoupper($type), $id, "Permanently deleted from archive");
        sendJSON(true, null, 'Item permanently deleted');
    } else {
        sendJSON(false, null, 'Failed to delete item');
    }
}
?>

