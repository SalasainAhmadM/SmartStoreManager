<?php
require_once '../../conn/conn.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$sales_id = $data['sales_id'] ?? null;

if (!$sales_id) {
    echo json_encode(['success' => false, 'message' => 'Missing sales ID']);
    exit;
}

$query = "DELETE FROM sales WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $sales_id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Delete failed']);
}
exit;
