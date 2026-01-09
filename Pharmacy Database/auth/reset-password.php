<?php
session_start();

define('DB_ACCESS', true);
require_once '../database/db_config.php';

header('Content-Type: application/json');

// Ensure request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(false, null, 'Invalid request method');
}

// Check session for reset_account_id
$accountId = $_SESSION['reset_account_id'] ?? null;
if (empty($accountId)) {
    sendJSON(false, null, 'Session expired or invalid. Please restart the password reset process.');
}

$password = $_POST['password'] ?? '';
$confirm = $_POST['confirm_password'] ?? $_POST['confirmPassword'] ?? '';

if (empty($password) || empty($confirm)) {
    sendJSON(false, null, 'Password and confirmation are required');
}

if ($password !== $confirm) {
    sendJSON(false, null, 'Passwords do not match');
}

if (strlen($password) < 6) {
    sendJSON(false, null, 'Password must be at least 6 characters long');
}

// Additional password policy checks (uppercase, lowercase, number)
if (!preg_match('/[a-z]/', $password) || !preg_match('/[A-Z]/', $password) || !preg_match('/\d/', $password)) {
    sendJSON(false, null, 'Password must contain at least one uppercase letter, one lowercase letter, and one number');
}

try {
    $hashed = password_hash($password, PASSWORD_BCRYPT);

    $sql = "UPDATE accounts SET password = ? WHERE accountId = ?";
    $result = executeQuery($sql, [$hashed, $accountId], 'ss');

    if ($result === false) {
        sendJSON(false, null, 'Failed to update password');
    }

    // Log activity
    $userId = $accountId;
    $username = $_SESSION['reset_email'] ?? 'unknown';
    logActivity($userId, $username, 'UPDATE', 'AUTH', 'Password reset via forgot-password flow');

    // Clear reset session vars
    unset($_SESSION['reset_email']);
    unset($_SESSION['reset_account_id']);

    sendJSON(true, null, 'Password reset successfully');

} catch (Exception $e) {
    error_log('Reset Password Error: ' . $e->getMessage());
    sendJSON(false, null, 'An error occurred. Please try again later.');
}

?>
