<?php
require_once '../../conn/conn.php'; // Include your database connection

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

$data = json_decode(file_get_contents('php://input'), true);

$managerId = isset($data['manager_id']) ? intval($data['manager_id']) : null;
$type = isset($data['type']) ? $data['type'] : null;
$id = isset($data['id']) ? intval($data['id']) : null;

if (!$managerId || !$type || !$id) {
    $response['message'] = 'Invalid parameters.';
    echo json_encode($response);
    exit;
}

try {
    if ($type === 'business') {
        // Unassign the manager from the business
        $query = "UPDATE business SET manager_id = NULL WHERE id = $id AND manager_id = $managerId";
    } elseif ($type === 'branch') {
        // Unassign the manager from the branch
        $query = "UPDATE branch SET manager_id = NULL WHERE id = $id AND manager_id = $managerId";
    } else {
        $response['message'] = 'Invalid type specified.';
        echo json_encode($response);
        exit;
    }

    if ($conn->query($query) && $conn->affected_rows > 0) {
        $response['success'] = true;
        $response['message'] = 'Manager unassigned successfully.';
    } else {
        $response['message'] = 'Failed to unassign manager or manager not found.';
    }
} catch (Exception $e) {
    $response['message'] = 'An error occurred: ' . $e->getMessage();
}

echo json_encode($response);
?>