<?php
// Include database configuration
define('DB_ACCESS', true);
require_once '../database/db_config.php';

// Set inactivity threshold (10 minutes)
$inactive_threshold = 10 * 60; // 10 minutes in seconds

// Get database connection
$conn = getDBConnection();
if (!$conn) {
    error_log('Database connection failed in cleanup_sessions.php');
    exit;
}

try {
    // Delete users who haven't been active for more than 10 minutes
    $stmt = $conn->prepare("DELETE FROM online_users WHERE TIMESTAMPDIFF(SECOND, lastActivity, NOW()) > ?");
    $stmt->bind_param("i", $inactive_threshold);
    $stmt->execute();

    $deleted_count = $stmt->affected_rows;
    $stmt->close();

    // Log cleanup activity
    if ($deleted_count > 0) {
        error_log("Cleanup: Removed $deleted_count inactive users from online_users table");
    }

} catch (Exception $e) {
    error_log("Cleanup Error: " . $e->getMessage());
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
