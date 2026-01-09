<?php
session_start();

// Include database configuration
define('DB_ACCESS', true);
require_once '../database/db_config.php';

header('Content-Type: application/json');

function sendJson($success, $message = '') {
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

// Only POST or GET allowed for heartbeat
if (!in_array($_SERVER['REQUEST_METHOD'], ['POST','GET'])) {
    sendJson(false, 'Invalid request method');
}

// Ensure session has user id
$accountId = $_SESSION['userId'] ?? null;
if (empty($accountId)) {
    sendJson(false, 'Not authenticated');
}

$conn = getDBConnection();
if (!$conn) {
    sendJson(false, 'Database connection failed');
}

try {
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $stmt = $conn->prepare("UPDATE online_users SET lastActivity = NOW(), ipAddress = ? WHERE accountId = ?");
    $stmt->bind_param("ss", $ip, $accountId);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();

    // If no row updated, attempt insert (in case row missing)
    if ($affected === 0) {
        $stmt = $conn->prepare("INSERT INTO online_users (accountId, username, ipAddress) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE lastActivity = NOW(), ipAddress = VALUES(ipAddress)");
        $username = $_SESSION['username'] ?? '';
        $stmt->bind_param("sss", $accountId, $username, $ip);
        $stmt->execute();
        $stmt->close();
    }

    sendJson(true, 'Heartbeat received');

} catch (Exception $e) {
    error_log('Heartbeat Error: ' . $e->getMessage());
    sendJson(false, 'Error updating heartbeat');
}

?>
