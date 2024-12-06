<?php
session_start();
require_once '../conn/conn.php';
validateSession('manager');

$manager_id = $_SESSION['user_id'];
$response = ['status' => 'error', 'data' => []];

if (isset($_POST['assignment_type']) && isset($_POST['assignment_id'])) {
    $assignment_type = $_POST['assignment_type']; // branch or business
    $assignment_id = $_POST['assignment_id'];

    // Fetch products based on assignment
    $sql = "SELECT id, name, price FROM products WHERE type = ? AND business_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $assignment_type, $assignment_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $response['data'][] = $row;
    }

    $response['status'] = 'success';
}

header('Content-Type: application/json');
echo json_encode($response);
?>