<?php
session_start();
include '../../conn/conn.php';
$owner_id = $_SESSION['user_id'];
$type = $_POST['type'];
$year = isset($_POST['year']) ? $_POST['year'] : null;
$month = isset($_POST['month']) ? $_POST['month'] : null;

$query = "SELECT e.*, b.name as business_name, br.location as branch_location 
          FROM expenses e
          LEFT JOIN business b ON e.category_id = b.id AND e.category = 'business'
          LEFT JOIN branch br ON e.category_id = br.id AND e.category = 'branch'
          WHERE e.owner_id = ?";

if ($year) {
    $query .= " AND YEAR(e.created_at) = ?";
}
if ($month) {
    $query .= " AND MONTH(e.created_at) = ?";
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