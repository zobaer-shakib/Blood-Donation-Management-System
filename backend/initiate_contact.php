<?php
// backend/initiate_contact.php

// Include the database connection file
require_once 'db_connect.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'An error occurred.'];

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the raw POST data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // Validate required fields
    if (!isset($data['donor_id'])) {
        echo json_encode(['success' => false, 'message' => 'Missing donor ID.']);
        exit();
    }

    // Ensure user is logged in (the one initiating contact)
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'You must be logged in to contact a donor.']);
        exit();
    }

    $donor_id = (int)$data['donor_id'];
    $recipient_id = (int)$_SESSION['user_id']; // The logged-in user is the recipient initiating contact

    // 1. Fetch Donor's Contact Information
    $stmt_donor = $conn->prepare("SELECT u.email, u.name FROM users u JOIN donors d ON u.id = d.user_id WHERE u.id = ?");
    $stmt_donor->bind_param("i", $donor_id);
    $stmt_donor->execute();
    $result_donor = $stmt_donor->get_result();

    if ($result_donor->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Donor not found.']);
        $stmt_donor->close();
        $conn->close();
        exit();
    }
    $donor_info = $result_donor->fetch_assoc();
    $donor_email = $donor_info['email'];
    $donor_name = $donor_info['name'];
    $stmt_donor->close();

    // 2. Fetch Recipient's (Logged-in User's) Information
    $stmt_recipient = $conn->prepare("SELECT name, email, phone FROM users WHERE id = ?");
    $stmt_recipient->bind_param("i", $recipient_id);
    $stmt_recipient->execute();
    $result_recipient = $stmt_recipient->get_result();

    if ($result_recipient->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Recipient user not found.']);
        $stmt_recipient->close();
        $conn->close();
        exit();
    }
    $recipient_info = $result_recipient->fetch_assoc();
    $recipient_name = $recipient_info['name'];
    $recipient_email = $recipient_info['email'];
    $recipient_phone = $recipient_info['phone'];
    $stmt_recipient->close();

    // 3. Fetch Recipient's Most Recent Blood Request (assuming they are contacting for their latest need)
    $stmt_request = $conn->prepare("SELECT blood_type, units_required, urgency_level, hospital_location FROM blood_requests WHERE recipient_id = ? ORDER BY request_date DESC LIMIT 1");
    $stmt_request->bind_param("i", $recipient_id);
    $stmt_request->execute();
    $result_request = $stmt_request->get_result();

    $request_details = "No specific request details found.";
    if ($result_request->num_rows > 0) {
        $latest_request = $result_request->fetch_assoc();
        $request_details = "Blood Type Needed: " . htmlspecialchars($latest_request['blood_type']) . "\n";
        $request_details .= "Units Required: " . htmlspecialchars($latest_request['units_required']) . "ml\n";
        $request_details .= "Urgency: " . htmlspecialchars($latest_request['urgency_level']) . "\n";
        $request_details .= "Hospital/Location: " . htmlspecialchars($latest_request['hospital_location']);
    }
    $stmt_request->close();

    // --- ACTUAL EMAIL SENDING LOGIC ---
    $to = $donor_email;
    $subject = 'BloodLink: Urgent Blood Donation Request from ' . $recipient_name;
    $message = "Dear " . htmlspecialchars($donor_name) . ",\n\n";
    $message .= "You have been contacted by " . htmlspecialchars($recipient_name) . " through BloodLink regarding a blood donation request.\n\n";
    $message .= "Here are the details of their request:\n\n";
    $message .= $request_details . "\n\n";
    $message .= "Recipient Contact Information:\n";
    $message .= "Name: " . htmlspecialchars($recipient_name) . "\n";
    $message .= "Email: " . htmlspecialchars($recipient_email) . "\n";
    $message .= "Phone: " . htmlspecialchars($recipient_phone) . "\n\n";
    $message .= "Please consider reaching out to them directly if you are able to help.\n\n";
    $message .= "Thank you for being a part of BloodLink and helping to save lives.\n\n";
    $message .= "Sincerely,\nThe BloodLink Team";

    // IMPORTANT: Replace 'your_email@example.com' with the email configured in your XAMPP sendmail.ini
    $from_email = 'your_email@example.com'; 
    $headers = 'From: ' . $from_email . "\r\n" .
               'Reply-To: ' . $recipient_email . "\r\n" . // Allow donor to reply directly to recipient
               'X-Mailer: PHP/' . phpversion();

    // Attempt to send the email
    if (mail($to, $subject, $message, $headers)) {
        error_log("Contact email sent to " . $donor_email . " for recipient " . $recipient_email);
        echo json_encode(['success' => true, 'message' => 'Contact request sent successfully to donor.', 'donor_email' => $donor_email]);
    } else {
        error_log("Failed to send contact email to " . $donor_email . ". Check mail server configuration.");
        echo json_encode(['success' => false, 'message' => 'Failed to send contact email. Please try again later.']);
    }
    // --- END ACTUAL EMAIL SENDING LOGIC ---

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}

$conn->close();
?>
