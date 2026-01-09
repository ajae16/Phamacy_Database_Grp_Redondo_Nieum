<?php
session_start();
require_once '../database/db_config.php';
// Handle assign/unassign actions BEFORE any method/input logic to guarantee no fallthrough
if (isset($_GET['action'])) {
        // DEBUG: Output session userId and currentRoles
        $logFile = __DIR__ . '/roles_error.log';
        file_put_contents($logFile, 'DEBUG userId: ' . ($_SESSION['userId'] ?? 'not set') . "\n", FILE_APPEND);
        $debugRoles = getUserRoles();
        file_put_contents($logFile, 'DEBUG roles: ' . json_encode($debugRoles) . "\n", FILE_APPEND);
    $action = $_GET['action'];
    if ($action === 'assign') {
        $data = json_decode(file_get_contents('php://input'), true);
        requireRole(['Super Admin', 'Admin']);
            if (empty($data['accountId']) || empty($data['roleId'])) sendJSON(false, null, 'accountId and roleId are required');
        // Get the role name being assigned
        $roleRow = executeQuery("SELECT roleName FROM roles WHERE roleId = ?", [(int)$data['roleId']], 'i');
        $roleName = $roleRow && isset($roleRow[0]['roleName']) ? $roleRow[0]['roleName'] : '';
        $currentRoles = getUserRoles();
        $isSuperAdmin = in_array('Super Admin', $currentRoles);
        $isAdmin = in_array('Admin', $currentRoles);
        if (strcasecmp($roleName, 'Super Admin') === 0 && !$isSuperAdmin) {
            sendJSON(false, null, 'Only Super Admin can assign Super Admin role');
        }
        if (strcasecmp($roleName, 'Super Admin') !== 0 && !$isSuperAdmin && !$isAdmin) {
            sendJSON(false, null, 'Unauthorized to assign this role');
        }
        // Prevent Admin from assigning Super Admin
        if ($isAdmin && strcasecmp($roleName, 'Super Admin') === 0) {
            sendJSON(false, null, 'Admin cannot assign Super Admin role');
        }
        // Check if user already has this role
            $existing = executeQuery("SELECT * FROM user_roles WHERE accountId = ? AND roleId = ?", [$data['accountId'], (int)$data['roleId']], 'si');
        if ($existing && count($existing) > 0) {
            sendJSON(true, null, 'Role already assigned');
        } else {
            // Remove any existing roles for this account (optional, if you want only one role per account)
                $res = executeQuery("INSERT INTO user_roles (accountId, roleId) VALUES (?, ?)", [$data['accountId'], (int)$data['roleId']], 'si');
            if ($res !== false) sendJSON(true, null, 'Assigned');
            sendJSON(false, null, 'Failed to assign');
        }
    }
    if ($action === 'unassign') {
        $data = json_decode(file_get_contents('php://input'), true);
        requireRole(['Super Admin', 'Admin']);
            if (empty($data['accountId']) || empty($data['roleId'])) sendJSON(false, null, 'accountId and roleId are required');
            $res = executeQuery("DELETE FROM user_roles WHERE accountId = ? AND roleId = ?", [$data['accountId'], (int)$data['roleId']], 'si');
        if ($res !== false) sendJSON(true, null, 'Unassigned');
        sendJSON(false, null, 'Failed to unassign');
    }
    exit; // Prevent further processing
}

/**
 * Roles management API
 */
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

require_once '../database/db_config.php';
file_put_contents(__DIR__ . '/roles_error.log', "DEBUG roles.php session: " . print_r($_SESSION, true) . "\n", FILE_APPEND);
file_put_contents(__DIR__ . '/roles_error.log', "DEBUG roles.php POST: " . print_r($_POST, true) . "\n", FILE_APPEND);

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

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

function handleGet() {
    // list roles
    $roles = executeQuery("SELECT * FROM roles ORDER BY roleId ASC");
    sendJSON(true, $roles ?: []);
}

function handlePost($data) {
    // Only admins/super admins can create roles
    requireRole(['Super Admin', 'Admin']);

    if (empty($data['roleName'])) {
        sendJSON(false, null, 'roleName is required');
    }

    $roleName = sanitizeInput($data['roleName']);
    $displayName = sanitizeInput($data['displayName'] ?? $roleName);
    $description = sanitizeInput($data['description'] ?? '');

    $sql = "INSERT INTO roles (roleName, displayName, description) VALUES (?, ?, ?)";
    $res = executeQuery($sql, [$roleName, $displayName, $description], 'sss');
    if ($res !== false) {
        sendJSON(true, ['roleId' => getLastInsertId()], 'Role created');
    }
    sendJSON(false, null, 'Failed to create role');
}

function handlePut($data) {
    requireRole(['Super Admin', 'Admin']);
    if (empty($data['roleId'])) sendJSON(false, null, 'roleId is required');

    $roleId = sanitizeInput($data['roleId']);
    $fields = [];
    $params = [];
    $types = '';

    if (isset($data['roleName'])) { $fields[] = 'roleName = ?'; $params[] = sanitizeInput($data['roleName']); $types .= 's'; }
    if (isset($data['displayName'])) { $fields[] = 'displayName = ?'; $params[] = sanitizeInput($data['displayName']); $types .= 's'; }
    if (isset($data['description'])) { $fields[] = 'description = ?'; $params[] = sanitizeInput($data['description']); $types .= 's'; }

    if (empty($fields)) sendJSON(false, null, 'No fields to update');

    $params[] = $roleId; $types .= 'i';
    $sql = "UPDATE roles SET " . implode(', ', $fields) . " WHERE roleId = ?";
    $res = executeQuery($sql, $params, $types);
    if ($res !== false) sendJSON(true, null, 'Role updated');
    sendJSON(false, null, 'Failed to update role');
}

function handleDelete($data) {
    requireRole(['Super Admin']);
    $roleId = $_GET['id'] ?? ($data['roleId'] ?? null);
    if (!$roleId) sendJSON(false, null, 'roleId is required');

    $sql = "DELETE FROM roles WHERE roleId = ?";
    $res = executeQuery($sql, [(int)$roleId], 'i');
    if ($res !== false) sendJSON(true, null, 'Role deleted');
    sendJSON(false, null, 'Failed to delete role');
}

// Assign/unassign users via action param
// Handle assign/unassign actions BEFORE main switch to prevent falling through to handlePost
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    if ($action === 'assign') {
        $data = json_decode(file_get_contents('php://input'), true);
        requireRole(['Super Admin', 'Admin']);
        if (empty($data['accountId']) || empty($data['roleId'])) sendJSON(false, null, 'accountId and roleId are required');
        // Get the role name being assigned
        $roleRow = executeQuery("SELECT roleName FROM roles WHERE roleId = ?", [(int)$data['roleId']], 'i');
        $roleName = $roleRow && isset($roleRow[0]['roleName']) ? $roleRow[0]['roleName'] : '';
        $currentRoles = getUserRoles();
        $isSuperAdmin = in_array('Super Admin', $currentRoles);
        $isAdmin = in_array('Admin', $currentRoles);
        if (strcasecmp($roleName, 'Super Admin') === 0 && !$isSuperAdmin) {
            sendJSON(false, null, 'Only Super Admin can assign Super Admin role');
        }
        if (strcasecmp($roleName, 'Super Admin') !== 0 && !$isSuperAdmin && !$isAdmin) {
            sendJSON(false, null, 'Unauthorized to assign this role');
        }
        // Prevent Admin from assigning Super Admin
        if ($isAdmin && strcasecmp($roleName, 'Super Admin') === 0) {
            sendJSON(false, null, 'Admin cannot assign Super Admin role');
        }
            $res = executeQuery("INSERT INTO user_roles (accountId, roleId) VALUES (?, ?)", [$data['accountId'], (int)$data['roleId']], 'si');
        if ($res !== false) sendJSON(true, null, 'Assigned');
        sendJSON(false, null, 'Failed to assign');
    }
    if ($action === 'unassign') {
        $data = json_decode(file_get_contents('php://input'), true);
        requireRole(['Super Admin', 'Admin']);
        if (empty($data['accountId']) || empty($data['roleId'])) sendJSON(false, null, 'accountId and roleId are required');
            $res = executeQuery("DELETE FROM user_roles WHERE accountId = ? AND roleId = ?", [$data['accountId'], (int)$data['roleId']], 'si');
        if ($res !== false) sendJSON(true, null, 'Unassigned');
        sendJSON(false, null, 'Failed to unassign');
    }
    exit; // Prevent further processing
}

?>