<?php
require_once '../../conn/conn.php';
session_start();

$data = json_decode(file_get_contents('php://input'), true);
$type_name = $data['type_name'];
$is_custom = $data['is_custom'];
$owner_id = $data['owner_id'];

$stmt = $conn->prepare("INSERT INTO expense_type (type_name, is_custom, created_at, owner_id) VALUES (?, ?, NOW(), ?)");
$stmt->bind_param("sii", $type_name, $is_custom, $owner_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to add expense type']);
}

$stmt->close();
$conn->close();
?>