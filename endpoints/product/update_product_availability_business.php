<?php
header('Content-Type: application/json');
require_once '../../conn/conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $business_id = $_POST['business_id'] ?? null;
    $updates = json_decode($_POST['updates'], true); // Decode JSON updates

    if (!$business_id || empty($updates)) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters.']);
        exit;
    }

    $conn->begin_transaction();

    try {
        // Delete existing records where business_id matches and branch_id is NULL
        $deleteStmt = $conn->prepare("DELETE FROM product_availability WHERE business_id = ? AND branch_id IS NULL");
        $deleteStmt->bind_param('i', $business_id);
        $deleteStmt->execute();
        $deleteStmt->close();

        // Prepare statement for inserting new records
        $insertStmt = $conn->prepare("INSERT INTO product_availability (product_id, business_id, branch_id, status, created_at) 
                                      VALUES (?, ?, NULL, ?, NOW())");

        foreach ($updates as $update) {
            $product_id = $update['product_id'];
            $status = $update['status'];

            $insertStmt->bind_param('iis', $product_id, $business_id, $status);
            $insertStmt->execute();
        }

        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }

    $insertStmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>