<?php
session_start();
include '../../conn/conn.php';

$businessName = $_GET['business'] ?? '';
$branches = [];

// Get business ID from name
$query = "SELECT id FROM business WHERE name = ? AND owner_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("si", $businessName, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $branchQuery = "SELECT id, location FROM branch WHERE business_id = ?";
    $branchStmt = $conn->prepare($branchQuery);
    $branchStmt->bind_param("i", $row['id']);
    $branchStmt->execute();
    $branchResult = $branchStmt->get_result();

    while ($branch = $branchResult->fetch_assoc()) {
        $branches[] = $branch;
    }
}

echo json_encode($branches);
?>