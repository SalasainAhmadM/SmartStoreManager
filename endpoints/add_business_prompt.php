<?php
header('Content-Type: application/json');

require_once '../conn/conn.php';

try {
    // Retrieve form data
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $asset = trim($_POST['asset']);
    $employeeCount = trim($_POST['employeeCount']);
    $owner_id = intval($_POST['owner_id']);

    // Validate required fields
    if (empty($name) || empty($asset) || empty($employeeCount) || empty($owner_id)) {
        echo json_encode(['success' => false, 'message' => 'All required fields must be filled.']);
        exit;
    }

    // Insert business into the database
    $query = "
        INSERT INTO business (name, branch, asset, employee_count, description, created_at, owner_id)
        VALUES (?, NULL, ?, ?, ?, NOW(), ?)
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ssssi', $name, $asset, $employeeCount, $description, $owner_id);

    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Error adding business.']);
        exit;
    }

    // Update is_new_owner to 0 for the owner
    $updateOwnerQuery = "
        UPDATE owner 
        SET is_new_owner = 0 
        WHERE id = ?
    ";
    $updateStmt = $conn->prepare($updateOwnerQuery);
    $updateStmt->bind_param('i', $owner_id);

    if (!$updateStmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Error updating owner status.']);
        exit;
    }

    echo json_encode(['success' => true, 'message' => 'Business added successfully!']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred.', 'error' => $e->getMessage()]);
}
?>