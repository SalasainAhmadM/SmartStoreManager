<?php
require_once '../../conn/conn.php';

date_default_timezone_set('Asia/Manila');

$data = json_decode(file_get_contents('php://input'), true);

$id = $data['id'];
$name = $data['name'];
$type = $data['type'];
$price = $data['price'];
$description = $data['description'];
$updated_at = date('Y-m-d H:i:s');

$query = "UPDATE products SET name = ?, type = ?, price = ?, description = ?, updated_at = ? WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('sssssi', $name, $type, $price, $description, $updated_at, $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}

$stmt->close();
$conn->close();
?>