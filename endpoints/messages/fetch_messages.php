<?php
session_start();
require_once '../../conn/auth.php';
require_once '../../conn/conn.php';

validateSession('owner');

$manager_id = $_GET['manager_id'] ?? null;
$owner_id = $_SESSION['user_id'];

if (!$manager_id) {
    echo json_encode(['status' => 'error', 'message' => 'Manager ID is required']);
    exit;
}

// Mark messages from the manager as read
$updateQuery = "UPDATE messages 
                SET is_read = 1 
                WHERE sender_id = ? AND receiver_id = ? AND sender_type = 'manager' AND is_read = 0";
$updateStmt = $conn->prepare($updateQuery);
$updateStmt->bind_param("ii", $manager_id, $owner_id);
$updateStmt->execute();

// Fetch messages between the owner and manager
$query = "SELECT * FROM messages 
          WHERE (sender_id = ? AND receiver_id = ? AND sender_type = 'owner') 
          OR (sender_id = ? AND receiver_id = ? AND sender_type = 'manager')
          ORDER BY timestamp ASC";
$stmt = $conn->prepare($query);
$stmt->bind_param("iiii", $owner_id, $manager_id, $manager_id, $owner_id);
$stmt->execute();
$result = $stmt->get_result();
$messages = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode($messages);
?>