<?php
require_once '../../conn/conn.php';

$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['id'])) {
    $stmt = $conn->prepare("UPDATE products SET unregistered = 0 WHERE id = ?");
    $stmt->bind_param("i", $data['id']);
    $success = $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => $success]);
} else {
    echo json_encode(['success' => false]);
}
?>