<?php
require_once '../conn/conn.php';

$id = $_GET['id'];

$query = "SELECT * FROM products WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode($row);
} else {
    echo json_encode(['success' => false, 'error' => 'Product not found']);
}

$stmt->close();
$conn->close();
?>