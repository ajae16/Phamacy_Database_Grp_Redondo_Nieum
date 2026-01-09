<?php
// Error logging helper
function logAccountApiError($msg, $data = null) {
    $logFile = __DIR__ . '/accounts_error.log';
    $entry = '[' . date('Y-m-d H:i:s') . "] $msg\n";
    if ($data !== null) {
        $entry .= print_r($data, true) . "\n";
    }
    file_put_contents($logFile, $entry, FILE_APPEND);
}

/**
 * Employee Accounts API Endpoint
 */

// Disable error display to prevent HTML output in API responses
error_reporting(0);
ini_set('display_errors', 0);

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

function handleGet() {
    $id = $_GET['id'] ?? null;
    $username = $_GET['username'] ?? null;

    if ($id) {
        $sql = "SELECT accountId, firstName, middleName, lastName, fullName, contact, email, status, username, image, schedule, birthday, address, sex, createdAt
                FROM accounts WHERE accountId = ?";
        $result = executeQuery($sql, [$id], 's');
        if ($result && isset($result[0]['accountId'])) {
            $roles = executeQuery("SELECT r.roleName FROM user_roles ur JOIN roles r ON ur.roleId = r.roleId WHERE ur.accountId = ?", [$id], 's');
            $result[0]['roles'] = $roles ? array_column($roles, 'roleName') : [];
        }
        sendJSON(true, $result[0] ?? null);
    } elseif ($username) {
        $sql = "SELECT accountId, firstName, middleName, lastName, fullName, contact, email, status, username, image, schedule, birthday, address, sex, createdAt
                FROM accounts WHERE username = ?";
        $result = executeQuery($sql, [$username], 's');
        if ($result && isset($result[0]['accountId'])) {
            $roles = executeQuery("SELECT r.roleName FROM user_roles ur JOIN roles r ON ur.roleId = r.roleId WHERE ur.accountId = ?", [$result[0]['accountId']], 's');
            $result[0]['roles'] = $roles ? array_column($roles, 'roleName') : [];
        }
        sendJSON(true, $result[0] ?? null);
    } else {
        $sql = "SELECT accountId, firstName, middleName, lastName, fullName, contact, email, status, username, image, schedule, birthday, address, sex, createdAt
                FROM accounts WHERE isActive = TRUE ORDER BY createdAt DESC";
        $result = executeQuery($sql);
        if ($result) {
            foreach ($result as &$row) {
                $roles = executeQuery("SELECT r.roleName FROM user_roles ur JOIN roles r ON ur.roleId = r.roleId WHERE ur.accountId = ?", [$row['accountId']], 's');
                $row['roles'] = $roles ? array_column($roles, 'roleName') : [];
            }
        }
        sendJSON(true, $result ?: []);
    }
}

function handlePost($data) {
    try {
        requireRole(['Super Admin', 'Admin']);
        $required = ['firstName', 'lastName', 'email', 'role', 'username', 'password'];
        $missing = validateRequired($data, $required);
        if (!empty($missing)) {
            logAccountApiError('Missing required fields', $missing);
            sendJSON(false, null, 'Missing required fields: ' . implode(', ', $missing));
        }
        // Check if username already exists
        $username = sanitizeInput($data['username']);
        $sql = "SELECT accountId FROM accounts WHERE username = ?";
        $existing = executeQuery($sql, [$username], 's');
        if (!empty($existing)) {
            logAccountApiError('Username already exists', $username);
            sendJSON(false, null, 'Username already exists');
        }
        // Check if email already exists
        $email = sanitizeInput($data['email']);
        $sql = "SELECT accountId FROM accounts WHERE email = ?";
        $existing = executeQuery($sql, [$email], 's');
        if (!empty($existing)) {
            logAccountApiError('Email already exists', $email);
            sendJSON(false, null, 'Email already exists');
        }
        $firstName = sanitizeInput($data['firstName']);
        $middleName = sanitizeInput($data['middleName'] ?? '');
        $lastName = sanitizeInput($data['lastName']);
        $fullName = sanitizeInput($data['fullName'] ?? "$firstName $middleName $lastName");
        $contact = sanitizeInput($data['contact'] ?? '');
        $role = sanitizeInput($data['role']);
        $status = sanitizeInput($data['status'] ?? 'active');
        $password = password_hash($data['password'], PASSWORD_BCRYPT);
        $image = sanitizeInput($data['image'] ?? 'Pharmacy Icons/profile icon.png');
        $schedule = sanitizeInput($data['schedule'] ?? '');
        $birthday = sanitizeInput($data['birthday'] ?? null);
        $address = sanitizeInput($data['address'] ?? '');
        $sex = sanitizeInput($data['sex'] ?? null);
        $employeeId = generateAccountId();
        $sql = "INSERT INTO accounts (accountId, firstName, middleName, lastName, fullName,
                contact, email, status, username, password, image, schedule, birthday, address, sex)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $result = executeQuery($sql, [
            $employeeId, $firstName, $middleName, $lastName, $fullName,
            $contact, $email, $status, $username, $password, $image, $schedule, $birthday, $address, $sex
        ], 'sssssssssssssss');
        // Assign role (RBAC)
        if ($result !== false) {
            executeQuery("INSERT INTO user_roles (accountId, roleId) VALUES (?, ?)", [$employeeId, 4], 'si');
            $userId = $_SESSION['userId'] ?? 'system';
            $sessionUsername = $_SESSION['username'] ?? 'system';
            logActivity($userId, $sessionUsername, 'CREATE', 'ACCOUNTS', $employeeId, 
                   "Created account: $username");
            sendJSON(true, ['id' => $employeeId], 'Account created successfully');
        } else {
            logAccountApiError('Failed to create account', $data);
            sendJSON(false, null, 'Failed to create account');
        }
    } catch (Exception $e) {
        logAccountApiError('Exception in handlePost', $e->getMessage());
        sendJSON(false, null, 'Server error: ' . $e->getMessage());
    }
// Removed extra closing brace that caused syntax error
}

function handlePut($data) {
    requireRole(['Super Admin', 'Admin']);
    if (empty($data['id'])) {
        sendJSON(false, null, 'Account ID is required');
    }
    
    $id = sanitizeInput($data['id']);
    
    $updateFields = [];
    $params = [];
    $types = '';
    
    $allowedFields = ['firstName', 'middleName', 'lastName', 'fullName', 'contact', 'email', 'status', 'username', 'image', 'schedule', 'birthday', 'address', 'sex'];
    
    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            // Check username uniqueness if updating username
            if ($field === 'username') {
                $sql = "SELECT accountId FROM accounts WHERE username = ? AND accountId != ?";
                $existing = executeQuery($sql, [sanitizeInput($data[$field]), $id], 'ss');
                if (!empty($existing)) {
                    sendJSON(false, null, 'Username already exists');
                }
            }

            // Check email uniqueness if updating email
            if ($field === 'email') {
                $sql = "SELECT accountId FROM accounts WHERE email = ? AND accountId != ?";
                $existing = executeQuery($sql, [sanitizeInput($data[$field]), $id], 'ss');
                if (!empty($existing)) {
                    sendJSON(false, null, 'Email already exists');
                }
            }
            
            $updateFields[] = "$field = ?";
            $params[] = sanitizeInput($data[$field]);
            $types .= 's';
        }
    }
    
    // Handle password update separately with current password verification
    if (isset($data['password']) && !empty($data['password'])) {
        // If currentPassword is provided, verify it first
        if (isset($data['currentPassword'])) {
            $sql = "SELECT password FROM accounts WHERE accountId = ?";
            $result = executeQuery($sql, [$id], 's');
            
            if (empty($result)) {
                sendJSON(false, null, 'User not found');
            }
            
            $currentHash = $result[0]['password'];
            
            if (!password_verify($data['currentPassword'], $currentHash)) {
                sendJSON(false, null, 'Current password is incorrect');
            }
        }
        
        $updateFields[] = "password = ?";
        $params[] = password_hash($data['password'], PASSWORD_BCRYPT);
        $types .= 's';
    }
    
    if (empty($updateFields)) {
        sendJSON(false, null, 'No fields to update');
    }
    
    $params[] = $id;
    $types .= 's';
    
    $sql = "UPDATE accounts SET " . implode(', ', $updateFields) . " WHERE accountId = ?";
    $result = executeQuery($sql, $params, $types);
    
    if ($result !== false) {
        $userId = $_SESSION['userId'] ?? 'system';
        $username = $_SESSION['username'] ?? 'system';
        logActivity($userId, $username, 'UPDATE', 'ACCOUNTS', $id, "Updated account");
        
        sendJSON(true, null, 'Account updated successfully');
    } else {
        sendJSON(false, null, 'Failed to update account');
    }
}

function handleDelete($data) {
    requireRole(['Super Admin', 'Admin']);
    $id = $data['id'] ?? $_GET['id'] ?? null;
    
    if (!$id) {
        sendJSON(false, null, 'Account ID is required');
    }
    
    $id = sanitizeInput($id);
    $userId = $_SESSION['userId'] ?? 'system';
    $username = $_SESSION['username'] ?? 'system';
    
    beginTransaction();
    
    try {
            // Archive account (no userRole)
            $sql = "INSERT INTO archive_accounts
                SELECT accountId, firstName, middleName, lastName, fullName, contact, email, username, NOW(), ?
                FROM accounts WHERE accountId = ?";
            executeQuery($sql, [$username, $id], 'ss');

        $sql = "DELETE FROM accounts WHERE accountId = ?";
        executeQuery($sql, [$id], 's');
        
        commitTransaction();
        
        logActivity($userId, $username, 'ARCHIVE', 'ACCOUNTS', $id, "Archived account");
        sendJSON(true, null, 'Account archived successfully');
        
    } catch (Exception $e) {
        rollbackTransaction();
        sendJSON(false, null, 'Failed to archive account');
    }
}

function generateAccountId() {
    $date = date('Ymd');
    
    $conn = getDBConnection();
    $stmt = $conn->prepare("CALL GetNextCounter('Acc', @counter)");
    $stmt->execute();
    $stmt->close();
    
    $result = $conn->query("SELECT @counter AS counter");
    $row = $result->fetch_assoc();
    $counter = str_pad($row['counter'], 4, '0', STR_PAD_LEFT);
    
    return "Acc{$date}{$counter}";
}
?>