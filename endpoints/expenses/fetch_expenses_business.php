<?php
session_start();
require_once '../../conn/conn.php';

header('Content-Type: application/json');
date_default_timezone_set('Asia/Manila');
if (!isset($_GET['category_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing category_id parameter']);
    exit;
}

$category_id = intval($_GET['category_id']);
$selected_month = isset($_GET['month']) ? intval($_GET['month']) : date('m');
$user_id = $_SESSION['user_id'];

try {
    // Query to fetch expenses for the specified business and month
    $query = "SELECT id, expense_type, description, amount, created_at
              FROM expenses 
              WHERE category = 'business' AND category_id = ? AND owner_id = ? 
              AND MONTH(created_at) = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iii", $category_id, $user_id, $selected_month);
    $stmt->execute();

    $result = $stmt->get_result();
    $expenses = [];
    while ($row = $result->fetch_assoc()) {
        $expenses[] = $row;
    }

    echo json_encode(['success' => true, 'data' => $expenses]);
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}

$conn->close();
?>