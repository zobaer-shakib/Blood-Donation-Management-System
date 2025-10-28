<?php
// backend/search_donors.php

// Include the database connection file
require_once 'db_connect.php';

header('Content-Type: application/json');

$response = ['success' => false, 'donors' => [], 'message' => 'No donors found.'];

// Check if the request method is GET
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get search parameters from GET request
    $blood_group = isset($_GET['blood_group']) ? trim($_GET['blood_group']) : '';
    $location = isset($_GET['location']) ? trim($_GET['location']) : '';
    $last_donation_date = isset($_GET['last_donation_date']) ? trim($_GET['last_donation_date']) : '';

    // Build the WHERE clause dynamically
    $where_clauses = ["d.donation_availability = 1"]; // Only search for available donors
    $params = [];
    $param_types = "";

    if (!empty($blood_group)) {
        $where_clauses[] = "d.blood_group = ?";
        $params[] = $blood_group;
        $param_types .= "s";
    }

    if (!empty($location)) {
        // Use LIKE for partial location matches
        $where_clauses[] = "d.location LIKE ?";
        $params[] = "%" . $location . "%";
        $param_types .= "s";
    }

    if (!empty($last_donation_date)) {
        // Find donors whose last donation date is before the specified date (meaning they are eligible to donate again)
        $where_clauses[] = "d.last_donation_date <= ?";
        $params[] = $last_donation_date;
        $param_types .= "s";
    }

    $sql = "SELECT u.id AS user_id, u.name, u.email, u.phone, d.blood_group, d.age, d.gender, d.location, d.last_donation_date, d.donation_availability
            FROM donors d
            JOIN users u ON d.user_id = u.id";

    if (!empty($where_clauses)) {
        $sql .= " WHERE " . implode(" AND ", $where_clauses);
    }

    $stmt = $conn->prepare($sql);

    // Bind parameters if they exist
    if (!empty($params)) {
        $stmt->bind_param($param_types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $donors = [];
        while ($row = $result->fetch_assoc()) {
            // Ensure donation_availability is boolean for frontend
            $row['donation_availability'] = (bool)$row['donation_availability'];
            $donors[] = $row;
        }
        $response = ['success' => true, 'donors' => $donors, 'message' => 'Donors found.'];
    } else {
        $response['message'] = 'No matching donors found based on your criteria.';
    }

    $stmt->close();
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);

$conn->close();
?>
