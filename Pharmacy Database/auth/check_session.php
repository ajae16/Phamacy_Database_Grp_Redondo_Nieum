<?php
session_start();

header('Content-Type: application/json');

$timeout_duration = 1800; // 30 minutes
$active = false;

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) <= $timeout_duration) {
        $_SESSION['last_activity'] = time(); // Update last activity
        $active = true;
    } else {
        // Session expired
        session_unset();
        session_destroy();
    }
} else {
    // Not logged in
    session_unset();
    session_destroy();
}

// Always include username and fullName for frontend
$roles = $_SESSION['roles'] ?? [];
$userId = $_SESSION['userId'] ?? null;
$username = $_SESSION['username'] ?? null;
$fullName = $_SESSION['fullName'] ?? null;
echo json_encode([
    'active' => $active,
    'roles' => $roles,
    'userId' => $userId,
    'username' => $username,
    'fullName' => $fullName
]);
?>

