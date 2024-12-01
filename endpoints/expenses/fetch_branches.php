<?php
session_start();
require_once '../../conn/conn.php';

// Validate request parameters
if (!isset($_GET['business_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing business_id parameter']);
    exit;
}

$business_id = intval($_GET['business_id']);

try {
    // Query to fetch branches for the specified business
    $query = "SELECT id, location FROM branch WHERE business_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $business_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $branches = [];
    while ($row = $result->fetch_assoc()) {
        $branches[] = $row;
    }
    $stmt->close();

    // Return the branches or an empty array
    echo json_encode(['success' => true, 'data' => $branches]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>