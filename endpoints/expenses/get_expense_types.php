<?php
require_once '../../conn/conn.php';
header('Content-Type: application/json');

$sql = "SELECT type_name FROM expense_type";
$result = $conn->query($sql);

$expenseTypes = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $expenseTypes[] = $row['type_name'];
    }
}

echo json_encode(['success' => true, 'types' => $expenseTypes]);

$conn->close();
?>