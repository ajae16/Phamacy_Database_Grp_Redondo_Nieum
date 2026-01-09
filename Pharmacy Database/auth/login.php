
<?php
// DEBUG: Log script entry
$logFile = __DIR__ . '/login_error.log';
file_put_contents($logFile, "DEBUG login.php start\n", FILE_APPEND);

// DEBUG: Before session_start
file_put_contents($logFile, "DEBUG before session_start\n", FILE_APPEND);
session_start();
// DEBUG: After session_start
file_put_contents($logFile, "DEBUG after session_start\n", FILE_APPEND);

// Include database configuration
define('DB_ACCESS', true);
require_once '../database/db_config.php';

header('Content-Type: application/json');

// Helper functions
function sendError($message) {
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

function sendSuccess($message, $data = []) {
    echo json_encode(array_merge(['success' => true, 'message' => $message], $data));
    exit;
}

// Check if request is POST
file_put_contents(__DIR__ . '/login_error.log', "DEBUG REQUEST_METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'not set') . "\n", FILE_APPEND);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    file_put_contents(__DIR__ . '/login_error.log', "DEBUG not POST, exiting\n", FILE_APPEND);
    sendError('Invalid request method');
}

// Get and sanitize input

file_put_contents(__DIR__ . '/login_error.log', "DEBUG POST DATA: " . json_encode($_POST) . "\n", FILE_APPEND);
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

// Use a single identifier for login (username or email)
$login_identifier = $username;

// Validation: Check empty fields
file_put_contents(__DIR__ . '/login_error.log', "DEBUG username value: '" . $username . "', length: " . strlen($username) . ", empty: " . (empty($username) ? 'yes' : 'no') . "\n", FILE_APPEND);
file_put_contents(__DIR__ . '/login_error.log', "DEBUG password value: '" . $password . "', length: " . strlen($password) . ", empty: " . (empty($password) ? 'yes' : 'no') . "\n", FILE_APPEND);
if (empty($username) || empty($password)) {
    file_put_contents(__DIR__ . '/login_error.log', "DEBUG empty username or password\n", FILE_APPEND);
    sendError('Username and password are required');
}

// Get database connection
file_put_contents(__DIR__ . '/login_error.log', "DEBUG before getDBConnection\n", FILE_APPEND);
$conn = getDBConnection();
if (!$conn) {
    file_put_contents(__DIR__ . '/login_error.log', "DEBUG db connection failed\n", FILE_APPEND);
    sendError('Database connection failed. Please try again later.');
}
file_put_contents(__DIR__ . '/login_error.log', "DEBUG got DB connection\n", FILE_APPEND);

try {
    // Check if user exists and is active (by username or email)
    file_put_contents(__DIR__ . '/login_error.log', "DEBUG before prepare stmt\n", FILE_APPEND);
    $stmt = $conn->prepare("SELECT accountId, firstName, lastName, fullName, username, password FROM accounts WHERE (BINARY username = ? OR BINARY email = ?) AND isActive = TRUE");
    if (!$stmt) {
        file_put_contents(__DIR__ . '/login_error.log', "DEBUG prepare failed: " . $conn->error . "\n", FILE_APPEND);
        throw new Exception('Database prepare failed: ' . $conn->error);
    }
    file_put_contents(__DIR__ . '/login_error.log', "DEBUG prepared stmt\n", FILE_APPEND);

    $stmt->bind_param("ss", $login_identifier, $login_identifier);
    file_put_contents(__DIR__ . '/login_error.log', "DEBUG after bind_param\n", FILE_APPEND);
    $stmt->execute();
    file_put_contents(__DIR__ . '/login_error.log', "DEBUG after execute\n", FILE_APPEND);
    $result = $stmt->get_result();
    file_put_contents(__DIR__ . '/login_error.log', "DEBUG after get_result\n", FILE_APPEND);
    file_put_contents(__DIR__ . '/login_error.log', "DEBUG num_rows: " . ($result ? $result->num_rows : 'result is false') . "\n", FILE_APPEND);
    if (!$result) {
        file_put_contents(__DIR__ . '/login_error.log', "DEBUG SQL error: " . $stmt->error . "\n", FILE_APPEND);
    }

    if ($result->num_rows === 0) {
        $stmt->close();
        // Log failed login attempt
        logActivity('', $login_identifier, 'LOGIN', 'AUTH', '', 'Failed login attempt - user not found');
        file_put_contents(__DIR__ . '/login_error.log', "DEBUG user not found or inactive\n", FILE_APPEND);
        sendError('Invalid username/email or password');
    }

    $user = $result->fetch_assoc();
    file_put_contents(__DIR__ . '/login_error.log', "DEBUG fetched user: " . json_encode($user) . "\n", FILE_APPEND);
    $stmt->close();

    // Verify password
    file_put_contents(__DIR__ . '/login_error.log', "DEBUG db password hash: '" . ($user['password'] ?? 'not set') . "'\n", FILE_APPEND);
    $verifyResult = password_verify($password, $user['password']);
    file_put_contents(__DIR__ . '/login_error.log', "DEBUG password_verify result: " . ($verifyResult ? 'true' : 'false') . "\n", FILE_APPEND);
    if (!$verifyResult) {
        // Log failed login attempt
        logActivity('', $login_identifier, 'LOGIN', 'AUTH', '', 'Failed login attempt - invalid password');
        file_put_contents(__DIR__ . '/login_error.log', "DEBUG invalid password\n", FILE_APPEND);
        sendError('Invalid username/email or password');
    }

    // Set session variables
    $_SESSION['logged_in'] = true;
    $_SESSION['userId'] = $user['accountId'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['fullName'] = $user['fullName'];
    file_put_contents(__DIR__ . '/login_error.log', "DEBUG session set: userId=" . ($_SESSION['userId'] ?? 'not set') . ", username=" . ($_SESSION['username'] ?? 'not set') . ", fullName=" . ($_SESSION['fullName'] ?? 'not set') . "\n", FILE_APPEND);
    // Fetch roles for session (RBAC)
    $roleSql = $conn->prepare("SELECT r.roleName FROM user_roles ur JOIN roles r ON ur.roleId = r.roleId WHERE ur.accountId = ?");
    $roleSql->bind_param("s", $user['accountId']);
    $roleSql->execute();
    $roleRes = $roleSql->get_result();
    $_SESSION['roles'] = $roleRes ? array_column($roleRes->fetch_all(MYSQLI_ASSOC), 'roleName') : [];
    $roleSql->close();
    file_put_contents(__DIR__ . '/login_error.log', "DEBUG session roles: " . json_encode($_SESSION['roles']) . "\n", FILE_APPEND);

    // Handle "Remember Me" functionality
    if (isset($_POST['remember']) && $_POST['remember'] == 'on') {
        // Generate selector and validator
        $selector = bin2hex(random_bytes(32)); // 64 character hex string
        $validator = bin2hex(random_bytes(32)); // 64 character hex string
        $hashedValidator = password_hash($validator, PASSWORD_DEFAULT);

        // Store token in database
        $stmt = $conn->prepare("INSERT INTO employee_token (selector, employeeId, validator) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $selector, $user['accountId'], $hashedValidator);
        $stmt->execute();
        $stmt->close();

        // Set cookie with selector:validator (expires in 30 days)
        $cookieValue = $selector . ':' . $validator;
        setcookie('remember_me', $cookieValue, time() + (30 * 24 * 60 * 60), '/', '', true, true); // HttpOnly, Secure
    }

    // Update online users table — store accountId as primary key
    $stmt = $conn->prepare("INSERT INTO online_users (accountId, username, ipAddress) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE lastActivity = NOW(), ipAddress = VALUES(ipAddress), username = VALUES(username)");
    $stmt->bind_param("sss", $user['accountId'], $user['username'], $_SERVER['REMOTE_ADDR']);
    $stmt->execute();
    $stmt->close();

    // Log successful login
    logActivity($user['accountId'], $user['username'], 'LOGIN', 'AUTH', $user['accountId'], 'User logged in successfully');
    file_put_contents(__DIR__ . '/login_error.log', "DEBUG before sendSuccess\n", FILE_APPEND);
    sendSuccess('Login successful', [
        'user' => [
            'id' => $user['accountId'],
            'username' => $user['username'],
            'fullName' => $user['fullName'],
            'roles' => $_SESSION['roles']
        ]
    ]);

} catch (Exception $e) {
    error_log("Login Error: " . $e->getMessage());
    sendError('An error occurred during login. Please try again.');
}
?>
