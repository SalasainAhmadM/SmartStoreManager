<?php
require_once '../../conn/conn.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

// Validate input data
if (!isset($data['expense_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$expense_id = $data['expense_id'];

$query = "DELETE FROM expenses WHERE id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $expense_id);

try {
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Expense deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Expense not found']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete expense']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$stmt->close();
$conn->close();
?>