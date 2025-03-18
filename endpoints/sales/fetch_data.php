<?php
session_start();
require_once '../../conn/conn.php';
$owner_id = $_SESSION['user_id'];
$type = $_POST['type'];
$year = isset($_POST['year']) ? $_POST['year'] : null;
$month = isset($_POST['month']) ? $_POST['month'] : null;

$query = "SELECT s.*, p.name as product_name, b.name as business_name, br.location as branch_location 
          FROM sales s
          LEFT JOIN products p ON s.product_id = p.id
          LEFT JOIN business b ON p.business_id = b.id
          LEFT JOIN branch br ON s.branch_id = br.id
          WHERE b.owner_id = ?";

if ($year) {
    $query .= " AND YEAR(s.date) = ?";
}
if ($month) {
    $query .= " AND MONTH(s.date) = ?";
}

$stmt = $conn->prepare($query);

if ($year && $month) {
    $stmt->bind_param("iii", $owner_id, $year, $month);
} elseif ($year) {
    $stmt->bind_param("ii", $owner_id, $year);
} else {
    $stmt->bind_param("i", $owner_id);
}

$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
?>