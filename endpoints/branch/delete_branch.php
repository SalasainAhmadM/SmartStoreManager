<?php
require_once '../../conn/conn.php';
$data = json_decode(file_get_contents('php://input'), true);

$id = $data['id'];

$query = "DELETE FROM branch WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}

$stmt->close();
$conn->close();
?>