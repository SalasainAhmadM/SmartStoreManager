<?php
session_start();
require_once '../../conn/auth.php';
require_once '../../conn/conn.php';
validateSession('owner');

$owner_id = $_SESSION['user_id'];

// Fetch businesses owned by the logged-in user
$query = "SELECT id, name FROM business WHERE owner_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$result = $stmt->get_result();

$businesses = [];
while ($row = $result->fetch_assoc()) {
    $businesses[] = $row;
}

header('Content-Type: application/json');
echo json_encode($businesses);
exit;
?>