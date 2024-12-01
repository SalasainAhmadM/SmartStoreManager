<?php
session_start();
require_once '../../conn/conn.php';

header('Content-Type: application/json');

if (!isset($_GET['branch_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing branch_id parameter']);
    exit;
}

// Fetch and sanitize the branch ID
$branch_id = intval($_GET['branch_id']);

// Optional month filter
$selected_month = isset($_GET['month']) ? intval($_GET['month']) : date('m'); // Default to current month

// Get the user ID from the session
$user_id = $_SESSION['user_id'];

try {
    // Query to fetch branch expenses filtered by month
    $query = "SELECT id, expense_type, description, amount, created_at
              FROM expenses 
              WHERE category = 'branch' AND category_id = ? AND owner_id = ? 
              AND MONTH(created_at) = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iii", $branch_id, $user_id, $selected_month);
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