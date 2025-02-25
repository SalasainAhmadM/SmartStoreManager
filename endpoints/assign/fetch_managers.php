<?php
session_start();
require_once '../../conn/auth.php';
require_once '../../conn/conn.php';

validateSession('owner');

$owner_id = $_SESSION['user_id']; // Assuming owner_id is stored in session

header('Content-Type: application/json');

try {
    // Query to fetch all managers under the given owner_id
    $query = "SELECT id, user_name FROM manager WHERE owner_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $owner_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $managers = [];
    while ($row = $result->fetch_assoc()) {
        $managers[] = [
            'id' => $row['id'],
            'username' => $row['user_name']
        ];
    }

    // Fetch managers already assigned in the branch table
    $assignedManagers = [];
    $assignedQuery = "SELECT manager_id FROM branch WHERE manager_id IS NOT NULL 
                      UNION 
                      SELECT manager_id FROM business WHERE manager_id IS NOT NULL";
    $assignedStmt = $conn->prepare($assignedQuery);
    $assignedStmt->execute();
    $assignedResult = $assignedStmt->get_result();

    while ($assignedRow = $assignedResult->fetch_assoc()) {
        $assignedManagers[] = $assignedRow['manager_id'];
    }

    // Filter out managers that are already assigned
    $availableManagers = array_filter($managers, function ($manager) use ($assignedManagers) {
        return !in_array($manager['id'], $assignedManagers);
    });

    echo json_encode(['success' => true, 'managers' => array_values($availableManagers)]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>