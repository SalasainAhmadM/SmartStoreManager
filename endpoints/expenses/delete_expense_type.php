<?php
require_once '../../conn/conn.php';

$data = json_decode(file_get_contents('php://input'), true);

$id = $data['id'];

try {
    $stmt = $conn->prepare("DELETE FROM expense_type WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>