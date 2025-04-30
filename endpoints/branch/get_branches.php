<?php
session_start();
require_once '../../conn/auth.php';
require_once '../../conn/conn.php';
validateSession('admin');

header('Content-Type: application/json');

try {
    if (!isset($_GET['business_id'])) {
        throw new Exception('Invalid request');
    }

    $businessId = $conn->real_escape_string($_GET['business_id']);

    // Get business details
    $businessQuery = "SELECT * FROM business WHERE id = '$businessId'";
    $businessResult = $conn->query($businessQuery);

    if (!$businessResult || $businessResult->num_rows === 0) {
        throw new Exception('Business not found');
    }

    $business = $businessResult->fetch_assoc();

    // Get branches
    $branchQuery = "SELECT * FROM branch 
                   WHERE business_id = '$businessId' 
                   ORDER BY is_approved ASC, created_at DESC";
    $branchResult = $conn->query($branchQuery);

    $branches = [];
    while ($branch = $branchResult->fetch_assoc()) {
        $branches[] = [
            'id' => htmlspecialchars($branch['id']),
            'location' => htmlspecialchars($branch['location']),
            'is_approved' => (bool) $branch['is_approved'],
            'created_at' => date('M d, Y h:i A', strtotime($branch['created_at'])),
            'business_permit' => $branch['business_permit'] ? htmlspecialchars($branch['business_permit']) : null
        ];
    }

    $response = [
        'business' => [
            'name' => htmlspecialchars($business['name']),
            'description' => htmlspecialchars($business['description']),
            'business_permit' => htmlspecialchars($business['business_permit']),
            'location' => htmlspecialchars($business['location']),
            'is_approved' => (bool) $business['is_approved'],
            'created_at' => date('M d, Y h:i A', strtotime($business['created_at']))
        ],
        'branches' => $branches
    ];

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
?>