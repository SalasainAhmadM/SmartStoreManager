<?php
session_start();
require_once '../conn/auth.php';
require_once '../conn/conn.php';

validateSession('owner');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $branch_id = $_POST['branch_id'] ?? null;
    $manager_id = $_POST['manager_id'] ?? null;

    if (!$branch_id || !$manager_id) {
        echo json_encode(['success' => false, 'message' => 'Branch ID and Manager ID are required.']);
        exit;
    }

    // Update the manager for the branch
    $query = "UPDATE branch SET manager_id = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $manager_id, $branch_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Manager assigned to branch successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to assign manager to branch.']);
    }
}
