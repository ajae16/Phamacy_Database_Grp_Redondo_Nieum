<?php
session_start();

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
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Invalid request method');
}

// Check if user has initiated password reset
if (!isset($_SESSION['reset_email']) || !isset($_SESSION['reset_account_id'])) {
    sendError('Password reset session expired. Please start over.');
}

// Get and sanitize input
$otp = trim($_POST['otp'] ?? '');

// Validation: Check empty OTP
if (empty($otp)) {
    sendError('OTP is required');
}

// Validation: OTP format (6 digits)
if (!preg_match('/^\d{6}$/', $otp)) {
    sendError('Please enter a valid 6-digit OTP');
}

// Get database connection
$conn = getDBConnection();
if (!$conn) {
    sendError('Database connection failed. Please try again later.');
}

try {
    $accountId = $_SESSION['reset_account_id'];

    // Get the latest unused OTP for this account
    $stmt = $conn->prepare("SELECT otpId, otpHash, expiredAt FROM otp_request WHERE accountId = ? AND isUsed = FALSE ORDER BY createdAt DESC LIMIT 1");
    if (!$stmt) {
        throw new Exception('Database prepare failed: ' . $conn->error);
    }

    $stmt->bind_param("s", $accountId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $stmt->close();
        sendError('No valid OTP found. Please request a new one.');
    }

    $otpRecord = $result->fetch_assoc();
    $stmt->close();

    // Check if OTP has expired
    $currentTime = date('Y-m-d H:i:s');
    if ($currentTime > $otpRecord['expiredAt']) {
        sendError('OTP has expired. Please request a new one.');
    }

    // Verify OTP
    if (!password_verify($otp, $otpRecord['otpHash'])) {
        sendError('Invalid OTP. Please try again.');
    }

    // Mark OTP as used
    $stmt = $conn->prepare("UPDATE otp_request SET isUsed = TRUE WHERE otpId = ?");
    if (!$stmt) {
        throw new Exception('Database prepare failed: ' . $stmt->error);
    }

    $stmt->bind_param("s", $otpRecord['otpId']);
    $stmt->execute();
    $stmt->close();

    // Set session for password reset
    $_SESSION['otp_verified'] = true;

    sendSuccess('OTP verified successfully. You can now reset your password.');

} catch (Exception $e) {
    error_log("OTP Verification Error: " . $e->getMessage());
    sendError('An error occurred. Please try again.');
}
?>
