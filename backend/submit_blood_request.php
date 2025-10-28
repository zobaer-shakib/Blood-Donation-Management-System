<?php
// backend/submit_blood_request.php

// Include the database connection file
require_once 'db_connect.php';

header('Content-Type: application/json');

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the raw POST data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // Validate required fields
    if (!isset($data['recipient_id'], $data['blood_type'], $data['units_required'], $data['urgency_level'], $data['hospital_location'], $data['contact_info'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required blood request fields.']);
        exit();
    }

    $recipient_id = $data['recipient_id'];
    $blood_type = trim($data['blood_type']);
    $units_required = (int)$data['units_required'];
    $urgency_level = trim($data['urgency_level']);
    $hospital_location = trim($data['hospital_location']);
    $contact_info = trim($data['contact_info']);
    $request_date = date('Y-m-d H:i:s'); // Current timestamp
    $status = 'Pending'; // Default status for new requests

    // Insert new blood request into the database
    $stmt = $conn->prepare("INSERT INTO blood_requests (recipient_id, blood_type, units_required, urgency_level, hospital_location, contact_info, request_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssssss", $recipient_id, $blood_type, $units_required, $urgency_level, $hospital_location, $contact_info, $request_date, $status);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Blood request submitted successfully.']);
    } else {
        error_log("Error submitting blood request: " . $stmt->error);
        echo json_encode(['success' => false, 'message' => 'Failed to submit blood request. Please try again.']);
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}

$conn->close();
?>
