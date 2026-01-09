<?php
/**
 * Suppliers API Endpoint
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

// Handle input data - support both JSON and form data
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (strpos($contentType, 'application/json') !== false) {
    $input = json_decode(file_get_contents('php://input'), true);
} else {
    $input = $_POST;
}

$id = $_GET['id'] ?? null;

switch ($method) {
    case 'GET':
        handleGet($id);
        break;
    case 'POST':
        handlePost($input);
        break;
    case 'PUT':
        handlePut($id, $input);
        break;
    case 'DELETE':
        handleDelete($id);
        break;
    default:
        sendJSON(false, null, 'Invalid request method');
}

function handleGet($id) {
    if ($id) {
        // Get single supplier
        $sql = "SELECT * FROM suppliers WHERE supplierId = ? AND isActive = TRUE";
        $result = executeQuery($sql, [$id], 's');
        $supplier = $result ? $result[0] : null;
        sendJSON(true, $supplier);
    } else {
        // Get all suppliers
        $sql = "SELECT * FROM suppliers WHERE isActive = TRUE ORDER BY name ASC";
        $result = executeQuery($sql);
        sendJSON(true, $result ?: []);
    }
}

function handlePost($data) {
    if (empty($data['name']) || empty($data['contactPerson']) || empty($data['phone']) ||
        empty($data['email']) || empty($data['address'])) {
        sendJSON(false, null, 'All fields are required');
    }
        requireRole(['Super Admin', 'Admin']);

    // Validate email uniqueness
    $checkEmail = executeQuery("SELECT supplierId FROM suppliers WHERE email = ? AND isActive = TRUE", [$data['email']], 's');
    if ($checkEmail) {
        sendJSON(false, null, 'Email already exists');
    }

    // Generate supplier ID
    $counter = getNextCounter('Sup');
    $date = date('YmdHi');
    $random = str_pad(rand(0, 99), 2, '0', STR_PAD_LEFT);
    $supplierId = "SUP{$date}{$random}";

    $sql = "INSERT INTO suppliers (supplierId, name, contactPerson, phone, email, address, status) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $result = executeQuery($sql, [
        $supplierId,
        sanitizeInput($data['name']),
        sanitizeInput($data['contactPerson']),
        sanitizeInput($data['phone']),
        sanitizeInput($data['email']),
        sanitizeInput($data['address']),
        $data['status'] ?? 'active'
    ], 'sssssss');

    if ($result !== false) {
        $userId = $_SESSION['userId'] ?? 'system';
        $username = $_SESSION['username'] ?? 'system';
        logActivity($userId, $username, 'CREATE', 'SUPPLIERS', $supplierId, "Created supplier: {$data['name']}");

        sendJSON(true, ['id' => $supplierId], 'Supplier created successfully');
    } else {
        sendJSON(false, null, 'Failed to create supplier');
    }
}

function handlePut($id, $data) {
    if (!$id) {
        sendJSON(false, null, 'Supplier ID is required');
    }
        requireRole(['Super Admin', 'Admin']);

    // Check if supplier exists and is active
    $checkSql = "SELECT supplierId FROM suppliers WHERE supplierId = ? AND isActive = TRUE";
    $existing = executeQuery($checkSql, [$id], 's');
    if (!$existing) {
        sendJSON(false, null, 'Supplier not found or inactive');
    }

    $sql = "UPDATE suppliers SET name = ?, contactPerson = ?, phone = ?, email = ?, address = ?, status = ?, updatedAt = NOW() WHERE supplierId = ? AND isActive = TRUE";
    $result = executeQuery($sql, [
        sanitizeInput($data['name']),
        sanitizeInput($data['contactPerson']),
        sanitizeInput($data['phone']),
        sanitizeInput($data['email']),
        sanitizeInput($data['address']),
        $data['status'] ?? 'active',
        $id
    ], 'sssssss');

    if ($result > 0) {
        $userId = $_SESSION['userId'] ?? 'system';
        $username = $_SESSION['username'] ?? 'system';
        logActivity($userId, $username, 'UPDATE', 'SUPPLIERS', $id, "Updated supplier: {$data['name']}");

        sendJSON(true, null, 'Supplier updated successfully');
    } else {
        sendJSON(false, null, 'Failed to update supplier or no changes made');
    }
}

function handleDelete($id) {
    if (!$id) {
        sendJSON(false, null, 'Supplier ID is required');
    }
        requireRole(['Super Admin', 'Admin']);

    // Check user role - restrict to Admin/Manager only
    $userRole = $_SESSION['userRole'] ?? 'employee';
    if (strtolower($userRole) !== 'admin' && strtolower($userRole) !== 'manager') {
        sendJSON(false, null, 'Access restricted: Only Admin or Manager can delete suppliers.');
    }

    $userId = $_SESSION['userId'] ?? 'system';
    $username = $_SESSION['username'] ?? 'system';

    // Use stored procedure for archiving
    $sql = "CALL ArchiveSupplier(?, ?, @success, @message)";
    executeQuery($sql, [$id, $username], 'ss');

    // Get the result
    $result = executeQuery("SELECT @success as success, @message as message");
    if ($result && $result[0]['success']) {
        logActivity($userId, $username, 'ARCHIVE', 'SUPPLIERS', $id, "Archived supplier");
        sendJSON(true, null, $result[0]['message']);
    } else {
        sendJSON(false, null, $result[0]['message'] ?? 'Failed to archive supplier');
    }
}

function getNextCounter($type) {
    $sql = "CALL GetNextCounter(?, @nextVal)";
    executeQuery($sql, [$type], 's');

    $result = executeQuery("SELECT @nextVal as counter");
    return $result ? $result[0]['counter'] : 1;
}
?>
