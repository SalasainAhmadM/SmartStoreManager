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
$location = trim($data['location']);

if (!$business_id || !$location) {
    echo json_encode(['success' => false, 'message' => 'Business ID and location are required']);
    exit;
}

// Check for duplicate branch location for the same business
$check_sql = "SELECT id FROM branch WHERE business_id = ? AND location = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param('is', $business_id, $location);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Branch location already exists for this business']);
    $check_stmt->close();
    $conn->close();
    exit;
}
$check_stmt->close();

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