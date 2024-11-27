<?php
header('Content-Type: application/json');
require_once '../../conn/conn.php';

// Decode the JSON payload
$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

// Extract and validate the input data
$business_id = $data['business_id'];
$location = $data['location'];

if (!$business_id || !$location) {
    echo json_encode(['success' => false, 'message' => 'Business ID and location are required']);
    exit;
}

// Prepare the SQL query to insert a new branch
$sql = "INSERT INTO branch (location, business_id, created_at) 
        VALUES (?, ?, NOW())";

$stmt = $conn->prepare($sql);
$stmt->bind_param('si', $location, $business_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to add branch']);
}

// Close the statement and connection
$stmt->close();
$conn->close();
?>