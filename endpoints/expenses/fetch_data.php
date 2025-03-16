<?php
session_start();
include '../../conn/conn.php';
$owner_id = $_SESSION['user_id'];
$type = $_POST['type'];

// Assuming you have a database connection $conn
$query = "SELECT e.*, b.name as business_name, br.location as branch_location 
          FROM expenses e
          LEFT JOIN business b ON e.category_id = b.id AND e.category = 'business'
          LEFT JOIN branch br ON e.category_id = br.id AND e.category = 'branch'
          WHERE e.owner_id = ?";
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