<?php
session_start();
require_once '../../conn/conn.php';
require_once '../../conn/auth.php';

validateSession('owner');

$input = json_decode(file_get_contents('php://input'), true);
$owner_id = $_SESSION['user_id'];

if (!empty($input['ids'])) {
    if ($input['type'] === 'business') {
        // Existing business handling
    } elseif ($input['type'] === 'branch') {
        $ids = $input['ids'];
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $query = "UPDATE branch 
                  JOIN business ON branch.business_id = business.id
                  SET branch.is_viewed = 1 
                  WHERE branch.id IN ($placeholders) 
                  AND business.owner_id = ?";
        $stmt = $conn->prepare($query);

        // Bind parameters: branch ids + owner_id
        $types = str_repeat('i', count($ids)) . 'i';
        $params = array_merge($ids, [$owner_id]);
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }

        $stmt->close();
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}
?>