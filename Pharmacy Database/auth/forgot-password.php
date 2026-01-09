<?php
session_start();
// Turn off display of errors to avoid HTML output breaking JSON responses
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);

// Start output buffering to capture any accidental output (libraries, warnings)
ob_start();

// Include database configuration
define('DB_ACCESS', true);
require_once '../database/db_config.php';

header('Content-Type: application/json');

// Helper functions
function sendError($message) {
    // Clean any previous output that might break JSON
    $buf = ob_get_clean();
    if (!empty($buf)) {
        error_log("Forgot Password - suppressed output before JSON: " . $buf);
    }
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

function sendSuccess($message, $data = []) {
    // Clean any previous output that might break JSON
    $buf = ob_get_clean();
    if (!empty($buf)) {
        error_log("Forgot Password - suppressed output before JSON: " . $buf);
    }
    echo json_encode(array_merge(['success' => true, 'message' => $message], $data));
    exit;
}

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Invalid request method');
}

// Get and sanitize input
$email = trim($_POST['email'] ?? '');

// Validation: Check empty email
if (empty($email)) {
    sendError('Email is required');
}

// Validation: Email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendError('Please enter a valid email address');
}

// Get database connection
$conn = getDBConnection();
if (!$conn) {
    sendError('Database connection failed. Please try again later.');
}

try {
    // Check if user exists with this email and is active
    $stmt = $conn->prepare("SELECT accountId, username, firstName, lastName FROM accounts WHERE email = ? AND isActive = TRUE");
    if (!$stmt) {
        throw new Exception('Database prepare failed: ' . $conn->error);
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $stmt->close();
        // Don't reveal if email exists or not for security
        sendSuccess('If an account with this email exists, an OTP has been sent.');
    }

    $user = $result->fetch_assoc();
    $stmt->close();

    // Generate OTP
    $otp = sprintf('%06d', mt_rand(0, 999999));
    $otpHash = password_hash($otp, PASSWORD_DEFAULT);
    $expiredAt = date('Y-m-d H:i:s', strtotime('+5 minutes'));

    // Generate OTP ID
    $otpId = 'OTP' . date('YmdHis') . rand(1000, 9999);

    // Store OTP in database
    $stmt = $conn->prepare("INSERT INTO otp_request (otpId, accountId, otpHash, expiredAt) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        throw new Exception('Database prepare failed: ' . $conn->error);
    }

    $stmt->bind_param("ssss", $otpId, $user['accountId'], $otpHash, $expiredAt);

    if (!$stmt->execute()) {
        throw new Exception('Failed to store OTP: ' . $stmt->error);
    }
    $stmt->close();

    // Send OTP email
    require_once '../phpmailer/otp-mailer.php';
    $mailer = new ForgotPasswordMailer();

    // Log email send attempt
    logActivity($user['accountId'], $user['username'], 'CREATE', 'AUTH', "OTP send attempt for password reset to: {$email}");

    if ($mailer->sendOTPEmail($email, $otp)) {
        // Store email in session for verification step
        $_SESSION['reset_email'] = $email;
        $_SESSION['reset_account_id'] = $user['accountId'];

        // Log successful OTP send
        logActivity($user['accountId'], $user['username'], 'CREATE', 'AUTH', "OTP sent successfully for password reset to: {$email}");

        sendSuccess('OTP sent successfully. Please check your email.');
    } else {
        // Log failed OTP send
        logActivity($user['accountId'], $user['username'], 'CREATE', 'AUTH', "OTP send failed for password reset to: {$email}");

        // If email fails, remove the OTP from database
        $stmt = $conn->prepare("DELETE FROM otp_request WHERE otpId = ?");
        $stmt->bind_param("s", $otpId);
        $stmt->execute();
        $stmt->close();

        sendError('Failed to send OTP email. Please try again.');
    }

} catch (Exception $e) {
    error_log("Forgot Password Error: " . $e->getMessage());
    sendError('An error occurred. Please try again.');
}
?>
