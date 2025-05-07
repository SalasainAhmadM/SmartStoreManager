<?php
session_start();
require_once '../../conn/auth.php';
require_once '../../conn/conn.php';
validateSession('owner');

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$sales_id = $data['sales_id'] ?? null;

if (!$sales_id) {
    echo json_encode(['success' => false, 'message' => 'Missing sales ID']);
    exit;
}

$ownerId = $_SESSION['user_id'];

// Ensure the sale belongs to this owner before updating
$query = "
    UPDATE sales s
    JOIN products p ON s.product_id = p.id
    JOIN business b ON p.business_id = b.id
    SET s.unregistered = 0
    WHERE s.id = ? AND b.owner_id = ?
    LIMIT 1
";

$stmt = $conn->prepare($query);
$stmt->bind_param('ii', $sales_id, $ownerId);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Update failed or already confirmed']);
}
exit;
