<?php
require_once '../../conn/conn.php';

header('Content-Type: application/json');
date_default_timezone_set('Asia/Manila');
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['category'], $data['category_id'], $data['expense_type'], $data['amount'], $data['description'], $data['month'], $data['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$category = $data['category'];
$category_id = $data['category_id'];
$expense_type = $data['expense_type'];
$amount = $data['amount'];
$description = $data['description'];
$month = $data['month'];
$owner_id = $data['user_id'];

$query = "INSERT INTO expenses (category, category_id, expense_type, amount, description, month, owner_id) 
          VALUES (?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($query);
$stmt->bind_param("sisssii", $category, $category_id, $expense_type, $amount, $description, $month, $owner_id);

try {
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Expense added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add expense']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$stmt->close();
$conn->close();


?>