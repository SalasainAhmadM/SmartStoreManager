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
$id = $data['id'];
$location = $data['location'];

if (!$id || !$location) {
    echo json_encode(['success' => false, 'message' => 'Branch ID and location are required']);
    exit;
}

// Prepare the SQL query to update the branch
$sql = "UPDATE branch SET location = ?, updated_at = NOW() WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('si', $location, $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Branch updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update branch']);
}

// Close the statement and connection
$stmt->close();
$conn->close();
?>