<?php
require_once '../../conn/conn.php'; // Include your database connection

$response = ['success' => false, 'message' => '', 'managers' => []];

$businessId = isset($_GET['business_id']) ? intval($_GET['business_id']) : null;
$branchId = isset($_GET['branch_id']) ? intval($_GET['branch_id']) : null;

if (!$businessId && !$branchId) {
    $response['message'] = 'Invalid request parameters.';
    echo json_encode($response);
    exit;
}

try {
    if ($branchId) {
        // Fetch managers for the branch
        $query = "
            SELECT m.id, m.user_name
            FROM manager m
            JOIN branch b ON b.manager_id = m.id
            WHERE b.id = $branchId
        ";
    } else {
        // Fetch the business manager if no branch is provided
        $query = "
            SELECT m.id, m.user_name
            FROM manager m
            JOIN business b ON b.manager_id = m.id
            WHERE b.id = $businessId
        ";
    }

    $result = $conn->query($query);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $response['managers'][] = [
                'id' => $row['id'],
                'user_name' => $row['user_name']
            ];
        }
        $response['success'] = true;
    } else {
        $response['message'] = 'No managers found.';
    }
} catch (Exception $e) {
    $response['message'] = 'An error occurred: ' . $e->getMessage();
}

echo json_encode($response);
?>