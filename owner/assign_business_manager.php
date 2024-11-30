<?php
require_once '../conn/conn.php';

$data = json_decode(file_get_contents('php://input'), true);
$businessId = $data['business_id'] ?? null;
$managerId = $data['manager_id'] ?? null;

if ($businessId && $managerId) {
    $query = "UPDATE business SET manager_id = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'ii', $managerId, $businessId);

        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['success' => true, 'message' => 'Manager assigned to business successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to assign manager to business.']);
        }

        mysqli_stmt_close($stmt);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare statement.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid input data.']);
}

mysqli_close($conn);
?>