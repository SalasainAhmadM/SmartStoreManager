<?php
require_once '../../conn/conn.php';

$data = json_decode(file_get_contents('php://input'), true);

$id = $data['id'];
$type_name = $data['type_name'];

try {
    $stmt = $conn->prepare("UPDATE expense_type SET type_name = ? WHERE id = ?");
    $stmt->bind_param("si", $type_name, $id);
    $stmt->execute();

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>