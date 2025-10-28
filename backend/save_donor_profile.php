<?php
// backend/save_donor_profile.php

// Include the database connection file
require_once 'db_connect.php';

header('Content-Type: application/json');

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the raw POST data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // Validate required fields
    if (!isset($data['user_id'], $data['name'], $data['blood_group'], $data['age'], $data['gender'], $data['location'], $data['donation_availability'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required donor profile fields.']);
        exit();
    }

    $user_id = $data['user_id'];
    $name = trim($data['name']);
    $blood_group = trim($data['blood_group']);
    $age = (int)$data['age'];
    $gender = trim($data['gender']);
    $location = trim($data['location']);
    $health_status = isset($data['health_status']) ? trim($data['health_status']) : null;
    $last_donation_date = isset($data['last_donation_date']) && !empty($data['last_donation_date']) ? $data['last_donation_date'] : null;
    $donation_availability = (int)$data['donation_availability']; // 1 for true, 0 for false

    // Update user's name in the users table (if it's different)
    $stmt_user_update = $conn->prepare("UPDATE users SET name = ? WHERE id = ?");
    $stmt_user_update->bind_param("si", $name, $user_id);
    $stmt_user_update->execute();
    $stmt_user_update->close();

    // Check if a donor profile already exists for this user
    $stmt_check = $conn->prepare("SELECT id FROM donors WHERE user_id = ?");
    $stmt_check->bind_param("i", $user_id);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        // Donor profile exists, update it
        $stmt = $conn->prepare("UPDATE donors SET blood_group = ?, age = ?, gender = ?, location = ?, health_status = ?, last_donation_date = ?, donation_availability = ? WHERE user_id = ?");
        $stmt->bind_param("sissssii", $blood_group, $age, $gender, $location, $health_status, $last_donation_date, $donation_availability, $user_id);
    } else {
        // No donor profile exists, insert a new one
        $stmt = $conn->prepare("INSERT INTO donors (user_id, blood_group, age, gender, location, health_status, last_donation_date, donation_availability) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isissssi", $user_id, $blood_group, $age, $gender, $location, $health_status, $last_donation_date, $donation_availability);
    }

    if ($stmt->execute()) {
        // Update the user's role to 'donor' if they were not already
        $update_role_stmt = $conn->prepare("UPDATE users SET role = 'donor' WHERE id = ? AND role != 'donor'");
        $update_role_stmt->bind_param("i", $user_id);
        $update_role_stmt->execute();
        $update_role_stmt->close();

        echo json_encode(['success' => true, 'message' => 'Donor profile saved successfully.']);
    } else {
        error_log("Error saving donor profile: " . $stmt->error);
        echo json_encode(['success' => false, 'message' => 'Failed to save donor profile. Please try again.']);
    }

    $stmt->close();
    $stmt_check->close();

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}

$conn->close();
?>
