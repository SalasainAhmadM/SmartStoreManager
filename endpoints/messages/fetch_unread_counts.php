<?php
session_start();
require_once '../../conn/auth.php';
require_once '../../conn/conn.php';

validateSession('owner');

$owner_id = $_SESSION['user_id'];

// Fetch unread message counts for each manager
$query = "SELECT sender_id AS manager_id, COUNT(*) AS unread_count 
          FROM messages 
          WHERE receiver_id = ? AND sender_type = 'manager' AND is_read = 0 
          GROUP BY sender_id";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$result = $stmt->get_result();
$unreadCounts = [];

while ($row = $result->fetch_assoc()) {
    $unreadCounts[$row['manager_id']] = $row['unread_count'];
}

echo json_encode($unreadCounts);

?>