<?php
session_start();
require_once '../../conn/conn.php';
require_once '../../conn/auth.php';

validateSession('owner');

$owner_id = $_SESSION['user_id'];

try {
    // Fetch expense types: custom for this owner and default types
    $query = "SELECT id, type_name, is_custom, created_at, owner_id 
              FROM expense_type 
              WHERE is_custom = 0 OR owner_id = ? 
              ORDER BY is_custom DESC, created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $owner_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $expenseTypes = [];
    while ($row = $result->fetch_assoc()) {
        $expenseTypes[] = $row;
    }

    echo json_encode(['success' => true, 'expenseTypes' => $expenseTypes]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>