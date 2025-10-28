<?php
// backend/register.php

// Include the database connection file
require_once 'db_connect.php';

header('Content-Type: application/json');

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the raw POST data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // Validate input data
    if (!isset($data['name'], $data['email'], $data['phone'], $data['password'], $data['role'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
        exit();
    }

    $name = trim($data['name']);
    $email = trim($data['email']);
    $phone = trim($data['phone']);
    $password = $data['password'];
    $role = $data['role'];

    // Basic validation
    if (empty($name) || empty($email) || empty($phone) || empty($password) || empty($role)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required.']);
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
        exit();
    }

    // Password hashing
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Check if email or phone already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR phone = ?");
    $stmt->bind_param("ss", $email, $phone);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Email or phone number already registered.']);
        $stmt->close();
        $conn->close();
        exit();
    }
    $stmt->close();

    // Insert new user into the database
    $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $name, $email, $phone, $hashed_password, $role);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Registration successful!']);
    } else {
        error_log("Error inserting user: " . $stmt->error);
        echo json_encode(['success' => false, 'message' => 'Registration failed. Please try again.']);
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}

$conn->close();
?>
