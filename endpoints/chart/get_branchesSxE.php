<?php
require_once '../../conn/conn.php';
header('Content-Type: application/json');

$businessName = $_GET['business'] ?? '';
$ownerId = $_GET['owner_id'] ?? 0;

// Fetch business ID
$stmt = $conn->prepare("SELECT id FROM business WHERE name = ? AND owner_id = ?");
$stmt->bind_param("si", $businessName, $ownerId);
$stmt->execute();
$business = $stmt->get_result()->fetch_assoc();

if (!$business)
    die(json_encode([]));

// Fetch branches (including main)
$branches = [
    ['id' => 'main', 'location' => 'Main Branch']
];

$stmt = $conn->prepare("SELECT id, location FROM branch WHERE business_id = ?");
$stmt->bind_param("i", $business['id']);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $branches[] = $row;
}

echo json_encode($branches);
?>