<?php
// backend/get_donation_centers.php

// Include the database connection file
require_once 'db_connect.php';

header('Content-Type: application/json');

$response = ['success' => false, 'centers' => [], 'message' => 'No donation centers found.'];

// For simplicity, we'll return a static list of centers.
// In a real application, you would fetch these from a 'donation_centers' table in your database.

// Example static data (you would replace this with database queries)
$static_centers = [
    [
        'id' => 1,
        'name' => 'City Blood Bank',
        'address' => 'Cumilla, laksam road, 12/A',
        'contact' => '028963938638',
        'working_hours' => 'Mon-Fri: 9 AM - 5 PM, Sat: 10 AM - 2 PM'
    ],
    [
        'id' => 2,
        'name' => 'Community Donation Center',
        'address' => 'Dhaka, 27/B',
        'contact' => '058963843823',
        'working_hours' => 'Mon-Sun: 8 AM - 8 PM'
    ],
    [
        'id' => 3,
        'name' => 'Red Cross Donation Point',
        'address' => 'chittagong, 7/A',
        'contact' => '038892938263',
        'working_hours' => 'Tue-Sat: 9 AM - 4 PM'
    ]
];

// In a real scenario, you would query your database like this:
/*
$sql = "SELECT id, name, address, contact, working_hours FROM donation_centers";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $centers = [];
    while ($row = $result->fetch_assoc()) {
        $centers[] = $row;
    }
    $response = ['success' => true, 'centers' => $centers, 'message' => 'Donation centers retrieved successfully.'];
} else {
    $response['message'] = 'No donation centers currently available.';
}
*/

// For now, use the static data
if (!empty($static_centers)) {
    $response = ['success' => true, 'centers' => $static_centers, 'message' => 'Donation centers retrieved successfully.'];
} else {
    $response['message'] = 'No donation centers currently available.';
}


echo json_encode($response);

$conn->close();
?>
