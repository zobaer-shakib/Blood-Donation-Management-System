<?php
// backend/fetch_requests_for_donor.php

header('Content-Type: application/json');

// 1. Include the connection file. This establishes $conn and calls session_start()
require_once 'db_connect.php'; 

$response = ['success' => false, 'message' => 'An unknown error occurred.', 'requests' => []];

// 2. CHECK LOGIN/AUTHORIZATION
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    http_response_code(401);
    $response['message'] = 'Unauthorized. Please log in to search requests.';
    echo json_encode($response);
    $conn->close();
    exit();
}

// 3. Get Filters from Query Parameters
$donor_blood_type_filter = $_GET['blood_type'] ?? '';
$location = $_GET['location'] ?? ''; // This is the user-inputted city/location

try {
    // 4. Base SQL Query
    $sql = "
        SELECT 
            recipient_id, 
            blood_type, 
            units_required, 
            urgency_level, 
            hospital_location, 
            contact_info, 
            request_date
        FROM blood_requests
        WHERE status = 'Pending' 
    ";
            
    $types = '';
    $params = [];
    
    // 5. Apply Blood Type Filter
    if (!empty($donor_blood_type_filter)) {
        $sql .= " AND blood_type = ?";
        $types .= 's';
        $params[] = $donor_blood_type_filter;
    }
    
    // 6. Apply Location/City Filter (SIMPLIFIED LOGIC using LIKE)
    if (!empty($location)) {
        // --- LOGIC TO MATCH ENDING PART OF hospital_location (City) ---
        
        // This LIKE pattern checks if the hospital_location ends with the city input,
        // preceded by EITHER a wildcard or a space/comma/underscore.
        // We look for any text followed by the city input.
        
        // This should match: '_cumilla', ' cumilla', ',cumilla', or just 'cumilla'
        $sql .= " AND hospital_location LIKE ?"; 
        $types .= 's';
        
        // The pattern: Starts with anything (%), followed by the user input (city)
        // This is a robust way to match the last part of a string without complex regex.
        $params[] = '%' . $location;
    }
    
    // 7. Sorting Logic
    $sql .= " 
        ORDER BY 
            CASE urgency_level
                WHEN 'emergency' THEN 1
                WHEN 'urgent' THEN 2
                WHEN 'normal' THEN 3
                ELSE 4
            END,
            request_date ASC";

    
    // 8. Prepare and Execute the Statement
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        throw new Exception("SQL prepare failed: " . $conn->error);
    }
    
    // 9. Bind Parameters only if they exist
    if (!empty($params)) {
        $bind_args = [$types];
        foreach ($params as &$param) {
            $bind_args[] = &$param;
        }
        
        if (!call_user_func_array([$stmt, 'bind_param'], $bind_args)) {
            throw new Exception("Binding parameters failed: " . $stmt->error);
        }
    }
    
    if (!$stmt->execute()) {
        throw new Exception("Execution failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    $requests = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $requests[] = $row;
        }
    }
    
    $stmt->close();
    
    // 10. Prepare the Final Response
    if (!empty($requests)) {
        $response['success'] = true;
        $response['message'] = 'Requests fetched successfully.';
        $response['requests'] = $requests;
    } else {
        $response['message'] = 'No open blood requests found matching your criteria.';
    }

} catch (Exception $e) {
    error_log("Donor Request Fetch Error: " . $e->getMessage());
    $response['message'] = 'A fatal server error occurred during the search. Check PHP logs.';
    $response['debug_error'] = $e->getMessage(); 
}

$conn->close();

echo json_encode($response);
?>