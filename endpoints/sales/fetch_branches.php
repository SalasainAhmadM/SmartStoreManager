<?php
require_once '../../conn/auth.php';
require_once '../../conn/conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $business_id = filter_var($data['business_id'], FILTER_VALIDATE_INT);

    if ($business_id === false) {
        echo json_encode(['success' => false, 'message' => 'Invalid business ID.']);
        exit;
    }

    $query = "SELECT id, location FROM branch WHERE business_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $business_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $branches = [];
    while ($row = $result->fetch_assoc()) {
        $branches[] = $row;
    }

    echo json_encode(['success' => true, 'branches' => $branches]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
