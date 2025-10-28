<?php
// backend/db_connect.php
// --- START DEBUGGING SETTINGS ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// --- END DEBUGGING SETTINGS ---
// Database configuration
define('DB_SERVER', 'localhost'); // Your database server (e.g., localhost)
define('DB_USERNAME', 'root');   // Your database username
define('DB_PASSWORD', '');       // Your database password (empty for XAMPP default)
define('DB_NAME', 'bloodlink_db'); // The name of your database
define('DB_PORT', 3307);         // Your MySQL port (changed to 3307 as per your request)

// Attempt to connect to MySQL database
// The port is now included as the fifth parameter in the mysqli constructor
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME, DB_PORT);

// Check connection
if ($conn->connect_error) {
    // Log the error for debugging, but don't expose sensitive info to the user
    error_log("Failed to connect to MySQL: " . $conn->connect_error);
    // Return a generic error message to the frontend
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection failed. Please try again later.']);
    exit();
}

// Set character set to UTF-8 for proper handling of various characters
$conn->set_charset("utf8mb4");

// Start session for managing user login state
session_start();
?>
