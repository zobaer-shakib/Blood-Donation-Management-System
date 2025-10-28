<?php
// backend/logout.php

// Include the database connection file to ensure session_start() is called
// and for consistent error reporting settings.
require_once 'db_connect.php'; 

// Set the content type to JSON for the frontend response
header('Content-Type: application/json');

// Check if a session is active before trying to destroy it
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Unset all session variables
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie.
// Note: This will destroy the session, and not just the session data!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally, destroy the session
session_destroy();

// Send a success response to the frontend
echo json_encode(['success' => true, 'message' => 'Logged out successfully.']);

// Close the database connection (if it was opened by db_connect.php)
// This is good practice, though script execution will end here anyway.
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
} elseif (isset($pdo) && $pdo instanceof PDO) {
    // If using PDO, you might not explicitly close it,
    // but setting it to null can release resources.
    $pdo = null;
}

exit(); // Ensure no further output is sent
?>
