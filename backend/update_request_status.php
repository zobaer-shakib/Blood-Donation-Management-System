<?php
// backend/update_request_status.php

header('Content-Type: application/json');

// Include your connection file which starts the session and provides $conn
require_once 'db_connect.php'; 

$response = ['success' => false, 'message' => 'An unknown error occurred.'];

// 1. CHECK LOGIN/AUTHORIZATION
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    http_response_code(401);
    $response['message'] = 'Unauthorized. Please log in.';
    echo json_encode($response);
    $conn->close();
    exit();
}

// 2. Get POST data
$data = json_decode(file_get_contents("php://input"), true);
$request_id = $data['request_id'] ?? null;
// Status as received from JavaScript: 'Found' or 'Terminated'
$new_status_from_js = $data['status'] ?? null; 
$recipient_id = $_SESSION['user_id'];

// 3. Status Mapping and Validation
// Database ENUM allows: 'Pending', 'Fulfilled', 'Canceled'
$valid_js_to_db_map = [
    'Found'      => 'Fulfilled', // Map front-end 'Found' button to DB 'Fulfilled'
    'Terminated' => 'Canceled'  // Map front-end 'Terminated' button to DB 'Canceled'
];

$final_db_status = $valid_js_to_db_map[$new_status_from_js] ?? null;

// Final validation check after mapping
if (empty($request_id) || !is_numeric($request_id) || !isset($final_db_status)) {
    $response['message'] = 'Invalid request ID or status mapping failed.';
    echo json_encode($response);
    $conn->close();
    exit();
}

try {
    // 4. Update SQL Query
    // CRITICAL: Only allow the update if the recipient_id matches the session user_id
    $sql = "UPDATE blood_requests SET status = ? WHERE id = ? AND recipient_id = ?";
            
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        throw new Exception("SQL prepare failed: " . $conn->error);
    }
    
    // Bind the final, mapped status
    $stmt->bind_param('sii', $final_db_status, $request_id, $recipient_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Execution failed: " . $stmt->error);
    }
    
    if ($stmt->affected_rows > 0) {
        $response['success'] = true;
        $response['message'] = "Request ID {$request_id} successfully updated to {$final_db_status}.";
    } else {
        $response['message'] = 'Request not found, already updated, or you do not have permission to update it.';
    }
    
    $stmt->close();

} catch (Exception $e) {
    error_log("Status Update Error: " . $e->getMessage());
    $response['message'] = 'Server Error updating request status. ' . $e->getMessage();
}

$conn->close();
echo json_encode($response);
?>