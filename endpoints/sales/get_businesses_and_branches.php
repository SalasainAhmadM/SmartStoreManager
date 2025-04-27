<?php
session_start();
require_once '../../conn/auth.php';
require_once '../../conn/conn.php';
validateSession('owner');

$owner_id = $_SESSION['user_id'];

$query = "
    SELECT 
        b.id AS business_id,
        b.name AS business_name,
        br.id AS branch_id,
        br.location AS branch_location
    FROM business b
    LEFT JOIN branch br ON b.id = br.business_id
    WHERE b.owner_id = ?
    ORDER BY b.name, br.location
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
exit;
?>