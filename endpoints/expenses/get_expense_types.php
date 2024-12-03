<?php
session_start();
require_once '../../conn/conn.php';
require_once '../../conn/auth.php';

validateSession('owner');

$owner_id = $_SESSION['user_id']; // Owner's ID from session

header('Content-Type: application/json');

$sql = "
    SELECT type_name, is_custom
    FROM expense_type
    WHERE is_custom = 0 OR (is_custom = 1 AND owner_id = ?)
    ORDER BY is_custom DESC, created_at ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $owner_id);
$stmt->execute();
$result = $stmt->get_result();

$expenseTypes = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $expenseTypes[] = $row['type_name'];
    }
}

echo json_encode(['success' => true, 'types' => $expenseTypes]);

$stmt->close();
$conn->close();
?>