<?php
header('Content-Type: application/json');
require_once '../conn/conn.php';

// Get the branch ID from the query string
$branch_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($branch_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid branch ID']);
    exit;
}

// Fetch branch details
$sql = "SELECT id, location FROM branch WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $branch_id);

if ($stmt->execute()) {
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $branch = $result->fetch_assoc();
        echo json_encode(['success' => true, 'data' => $branch]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Branch not found']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to fetch branch details']);
}

$stmt->close();
$conn->close();
?>