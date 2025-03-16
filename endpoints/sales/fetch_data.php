<?php
session_start();
require_once '../../conn/conn.php';
$owner_id = $_SESSION['user_id'];
$type = $_POST['type'];
$query = "SELECT s.*, p.name as product_name, b.name as business_name, br.location as branch_location 
          FROM sales s
          LEFT JOIN products p ON s.product_id = p.id
          LEFT JOIN business b ON p.business_id = b.id
          LEFT JOIN branch br ON s.branch_id = br.id
          WHERE b.owner_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
?>