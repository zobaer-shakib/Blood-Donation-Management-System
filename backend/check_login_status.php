<?php
// backend/check_login_status.php

// Include the database connection file (to start session if not already started)
require_once 'db_connect.php'; // This also starts the session_start()

header('Content-Type: application/json');

$response = ['loggedIn' => false, 'user' => null, 'message' => 'Not logged in.'];

// Check if user_id is set in the session
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // Fetch user details from the database to ensure data is fresh and complete
    // This also helps to get donor-specific info if it was updated elsewhere
    $stmt = $conn->prepare("SELECT id, name, email, phone, role FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($id, $name, $email, $phone, $role);
        $stmt->fetch();

        // Fetch donor-specific information if the user is a donor
        $is_donor = false;
        $donor_data = [];
        $donor_stmt = $conn->prepare("SELECT blood_group, age, gender, location, health_status, last_donation_date, donation_availability FROM donors WHERE user_id = ?");
        $donor_stmt->bind_param("i", $id);
        $donor_stmt->execute();
        $donor_stmt->store_result();

        if ($donor_stmt->num_rows === 1) {
            $is_donor = true;
            $donor_stmt->bind_result($blood_group, $age, $gender, $location, $health_status, $last_donation_date, $donation_availability);
            $donor_stmt->fetch();
            $donor_data = [
                'blood_group' => $blood_group,
                'age' => $age,
                'gender' => $gender,
                'location' => $location,
                'health_status' => $health_status,
                'last_donation_date' => $last_donation_date,
                'donation_availability' => (bool)$donation_availability // Convert tinyint(1) to boolean
            ];
        }
        $donor_stmt->close();

        // Fetch user's blood requests (if any)
        $blood_requests = [];
        $requests_stmt = $conn->prepare("SELECT id, blood_type, units_required, urgency_level, hospital_location, contact_info, request_date, status FROM blood_requests WHERE recipient_id = ? ORDER BY request_date DESC");
        $requests_stmt->bind_param("i", $id);
        $requests_stmt->execute();
        $requests_result = $requests_stmt->get_result();
        while ($row = $requests_result->fetch_assoc()) {
            $blood_requests[] = $row;
        }
        $requests_stmt->close();

        // Construct user array to send to frontend
        $user_info = [
            'id' => $id,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'role' => $role,
            'isDonor' => $is_donor,
            'blood_requests' => $blood_requests // Include blood requests
        ];
        // Merge donor data if available
        if ($is_donor) {
            $user_info = array_merge($user_info, $donor_data);
        }

        $response = ['loggedIn' => true, 'user' => $user_info, 'message' => 'User logged in.'];
    } else {
        // User ID in session but not found in DB (shouldn't happen normally, but good to handle)
        session_unset(); // Unset all session variables
        session_destroy(); // Destroy the session
        $response['message'] = 'Session invalid. Please log in again.';
    }
    $stmt->close();
}

echo json_encode($response);

$conn->close();
?>
