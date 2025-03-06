<?php
header('Content-Type: application/json');
require_once '../../conn/conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $business_id = $_POST['business_id'] ?? null;
    $branch_id = $_POST['branch_id'] ?? null;
    $updates = json_decode($_POST['updates'], true); // Decode JSON updates

    if (!$business_id || !$branch_id || empty($updates)) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters.']);
        exit;
    }

    $conn->begin_transaction();

    try {
        $deleteStmt = $conn->prepare("DELETE FROM product_availability WHERE business_id = ? AND branch_id = ?");
        $deleteStmt->bind_param('ii', $business_id, $branch_id);
        $deleteStmt->execute();
        $deleteStmt->close();

        $insertStmt = $conn->prepare("INSERT INTO product_availability (product_id, business_id, branch_id, status, created_at) 
                                      VALUES (?, ?, ?, ?, NOW())");

        foreach ($updates as $update) {
            $product_id = $update['product_id'];
            $status = $update['status'];

            $insertStmt->bind_param('iiis', $product_id, $business_id, $branch_id, $status);
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