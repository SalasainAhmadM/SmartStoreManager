<?php
require_once '../../conn/conn.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$userId = $_SESSION['user_id'];

$data = json_decode(file_get_contents("php://input"), true);
if (!$data || !isset($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

$id = $data['id'];

// Fetch the manager's details before deleting
$fetchQuery = "SELECT first_name, middle_name, last_name FROM manager WHERE id = ?";
$fetchStmt = $conn->prepare($fetchQuery);
$fetchStmt->bind_param("i", $id);
$fetchStmt->execute();
$fetchResult = $fetchStmt->get_result();

if ($fetchResult->num_rows > 0) {
    $manager = $fetchResult->fetch_assoc();
    $fullName = trim($manager['first_name'] . ' ' . $manager['middle_name'] . ' ' . $manager['last_name']);
    
    $deleteQuery = "DELETE FROM manager WHERE id = ?";
    $deleteStmt = $conn->prepare($deleteQuery);
    $deleteStmt->bind_param("i", $id);

    if ($deleteStmt->execute()) {

        $currentDateTime = date('Y-m-d H:i:s');

        $activityQuery = "INSERT INTO activity (message, created_at, status, user, user_id) 
                          VALUES (?, ?, 'Completed', 'owner', ?)";

        $activityMessage = "Manager Deleted: $fullName"; 
        $activityStmt = $conn->prepare($activityQuery);
        $activityStmt->bind_param("ssi", $activityMessage, $currentDateTime, $userId);
        $activityStmt->execute();

        echo json_encode(['success' => true, 'message' => 'Manager deleted successfully and activity logged']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete manager']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Manager not found']);
}
?>
