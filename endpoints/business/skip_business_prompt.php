<?php
header('Content-Type: application/json');
require_once '../../conn/conn.php';

try {
    // Decode JSON request body
    $data = json_decode(file_get_contents('php://input'), true);
    $owner_id = intval($data['owner_id']);

    // Validate required fields
    if (empty($owner_id)) {
        echo json_encode(['success' => false, 'message' => 'Owner ID is required.']);
        exit;
    }

    // Update is_new_owner to 0
    $updateOwnerQuery = "
        UPDATE owner 
        SET is_new_owner = 0 
        WHERE id = ?
    ";
    $stmt = $conn->prepare($updateOwnerQuery);
    $stmt->bind_param('i', $owner_id);

    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Error updating owner status.']);
        exit;
    }

    echo json_encode(['success' => true, 'message' => 'Owner status updated successfully.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred.', 'error' => $e->getMessage()]);
}
?>