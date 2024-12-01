<?php
require_once '../../conn/conn.php';

header('Content-Type: application/json');

// Decode the incoming JSON payload
$data = json_decode(file_get_contents('php://input'), true);

// Check for required fields
if (!isset($data['branch_id'], $data['expense_type'], $data['amount'], $data['description'], $data['user_id'], $data['month'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$category = 'branch'; // Default category
$branch_id = $data['branch_id']; // Used as category_id
$expense_type = $data['expense_type'];
$amount = $data['amount'];
$description = $data['description'];
$user_id = $data['user_id'];
$created_at = date('Y-m-d H:i:s'); // Set current timestamp
$month = $data['month']; // Month field for reference

// Prepare the SQL query to insert the expense
$query = "INSERT INTO expenses (category, category_id, expense_type, amount, description, created_at, owner_id) 
          VALUES (?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($query);
$stmt->bind_param("sissssi", $category, $branch_id, $expense_type, $amount, $description, $created_at, $user_id);

try {
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Branch expense added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add branch expense']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// Close the statement and the connection
$stmt->close();
$conn->close();
?>