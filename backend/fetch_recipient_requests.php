<?php
// backend/fetch_recipient_requests.php

header('Content-Type: application/json');

// Include your connection file which starts the session and provides $conn
require_once 'db_connect.php'; 

$response = ['success' => false, 'message' => 'An unknown error occurred.', 'requests' => []];

// 1. CHECK LOGIN/AUTHORIZATION
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    http_response_code(401);
    $response['message'] = 'Unauthorized. Please log in to view your requests.';
    echo json_encode($response);
    $conn->close();
    exit();
}

$recipient_id = $_SESSION['user_id'];

try {
    // 2. SQL Query to fetch requests for the current user
    $sql = "
        SELECT 
            id, 
            blood_type, 
            units_required, 
            urgency_level, 
            hospital_location, 
            request_date
        FROM blood_requests
        WHERE recipient_id = ? AND status = 'Pending'
        ORDER BY request_date DESC";
            
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        throw new Exception("SQL prepare failed: " . $conn->error);
    }
    
    // 3. Bind the recipient_id
    $stmt->bind_param('i', $recipient_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Execution failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    $requests = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Map 'id' to 'request_id' for consistency
            $row['request_id'] = $row['id'];
            unset($row['id']); 
            $requests[] = $row;
        }
    }
    
    $stmt->close();
    
    $response['success'] = true;
    $response['message'] = 'Recipient requests fetched.';
    $response['requests'] = $requests;

} catch (Exception $e) {
    error_log("Recipient Request Fetch Error: " . $e->getMessage());
    $response['message'] = 'Server Error fetching your requests. ' . $e->getMessage();
}

$conn->close();
echo json_encode($response);
?>