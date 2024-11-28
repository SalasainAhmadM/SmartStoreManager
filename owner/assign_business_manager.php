<?php
session_start();
require_once '../conn/auth.php';
require_once '../conn/conn.php';

validateSession('owner');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $business_id = $_POST['business_id'] ?? null;
    $manager_id = $_POST['manager_id'] ?? null;

    if (!$business_id || !$manager_id) {
        echo json_encode(['success' => false, 'message' => 'Business ID and Manager ID are required.']);
        exit;
    }

    // Update the manager for the business
    $query = "UPDATE business SET manager_id = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $manager_id, $business_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Manager assigned to business successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to assign manager to business.']);
    }
}
