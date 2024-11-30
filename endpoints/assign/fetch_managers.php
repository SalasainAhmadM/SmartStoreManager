<?php
session_start();
require_once '../../conn/auth.php';
require_once '../../conn/conn.php';

validateSession('owner');

$owner_id = $_SESSION['user_id']; // Assuming owner_id is stored in session

header('Content-Type: application/json');

try {
    // Query to fetch managers under the given owner_id
    $query = "SELECT id, first_name, middle_name, last_name FROM manager WHERE owner_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $owner_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch all managers
    $managers = [];
    while ($row = $result->fetch_assoc()) {
        $managers[] = [
            'id' => $row['id'],
            'name' => $row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name']
        ];
    }

    // Return managers as JSON
    echo json_encode(['success' => true, 'managers' => $managers]);
} catch (Exception $e) {
    // Handle errors
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>