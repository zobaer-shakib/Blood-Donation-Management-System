<?php
// backend/login.php

// Include the database connection file
require_once 'db_connect.php';

header('Content-Type: application/json');

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the raw POST data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // Validate input data
    if (!isset($data['email'], $data['password'])) {
        echo json_encode(['success' => false, 'message' => 'Email and password are required.']);
        exit();
    }

    $email = trim($data['email']);
    $password = $data['password'];

    // Prepare a select statement to retrieve user data
    $stmt = $conn->prepare("SELECT id, name, email, phone, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($id, $name, $email, $phone, $hashed_password, $role);
        $stmt->fetch();

        // Verify password
        if (password_verify($password, $hashed_password)) {
            // Password is correct, start a new session
            session_regenerate_id(true); // Regenerate session ID to prevent session fixation attacks

            $_SESSION['user_id'] = $id;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_phone'] = $phone;
            $_SESSION['user_role'] = $role;

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

            // Construct user array to send to frontend
            $user_info = [
                'id' => $id,
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'role' => $role,
                'isDonor' => $is_donor
            ];
            // Merge donor data if available
            if ($is_donor) {
                $user_info = array_merge($user_info, $donor_data);
            }

            echo json_encode(['success' => true, 'message' => 'Login successful!', 'user' => $user_info]);
        } else {
            // Password is not valid
            echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
        }
    } else {
        // No user found with that email
        echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}

$conn->close();
?>
