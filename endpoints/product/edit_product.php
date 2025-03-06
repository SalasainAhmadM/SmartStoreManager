<?php
require_once '../../conn/conn.php';

date_default_timezone_set('Asia/Manila');

$data = json_decode(file_get_contents('php://input'), true);

$id = $data['id'];
$name = $data['name'];
$type = $data['type'];
$size = $data['size'];
$price = $data['price'];
$description = $data['description'];
// $status = $data['status'];
$updated_at = date('Y-m-d H:i:s');

$conn->begin_transaction();

try {
    // Update product details
    $query = "UPDATE products SET name = ?, type = ?, size = ?, price = ?, description = ?, updated_at = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ssssssi', $name, $type, $size, $price, $description, $updated_at, $id);
    $stmt->execute();
    $stmt->close();


    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>