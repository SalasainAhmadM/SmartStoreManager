<?php
session_start();
require_once '../../conn/conn.php';

header('Content-Type: application/json');

// Validate request parameters
if (!isset($_GET['category_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing category_id parameter']);
    exit;
}

// Fetch the category_id and sanitize it
$category_id = intval($_GET['category_id']);

// Ensure the user is authorized to view these expenses
$user_id = $_SESSION['user_id'];

try {
    // Query to fetch expenses for the specified business
    $query = "SELECT expense_type, description, amount 
              FROM expenses 
              WHERE category = 'business' AND category_id = ? AND owner_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $category_id, $user_id);
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