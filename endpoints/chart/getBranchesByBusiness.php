<?php
require '../../conn/conn.php';

header('Content-Type: application/json');

if (isset($_GET['business_id'])) {
    $businessId = intval($_GET['business_id']);

    $stmt = $conn->prepare("SELECT id, location FROM branch WHERE business_id = ?");
    $stmt->bind_param("i", $businessId);
    $stmt->execute();
    $result = $stmt->get_result();

    $branches = [];
    while ($row = $result->fetch_assoc()) {
        $branches[] = $row;
    }

    echo json_encode($branches);
} else {
    echo json_encode([]);
}
?>