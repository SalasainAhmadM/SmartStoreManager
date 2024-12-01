<?php
require_once '../../conn/conn.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

// Validate input data
if (!isset($data['expense_id'], $data['expense_type'], $data['amount'], $data['description'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$expense_id = $data['expense_id'];
$expense_type = $data['expense_type'];
$amount = $data['amount'];
$description = $data['description'];

$query = "UPDATE expenses 
          SET expense_type = ?, amount = ?, description = ? 
          WHERE id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("sdsi", $expense_type, $amount, $description, $expense_id);

try {
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Expense updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update expense']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$stmt->close();
$conn->close();
?>