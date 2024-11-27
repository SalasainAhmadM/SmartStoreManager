<?php
session_start();
require_once '../../conn/auth.php';
require_once '../../conn/conn.php';
validateSession('manager');

$manager_id = $_SESSION['user_id'];

// Fetch the owner ID for this manager
$query = "SELECT owner_id FROM manager WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $manager_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$owner_id = $row['owner_id'];

if (!$owner_id) {
    echo json_encode(['error' => 'No owner linked to this manager.']);
    exit;
}

// Fetch messages between the manager and the owner
$query = "SELECT * FROM messages 
          WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
          ORDER BY timestamp ASC";
$stmt = $conn->prepare($query);
$stmt->bind_param("iiii", $manager_id, $owner_id, $owner_id, $manager_id);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}

echo json_encode($messages);
?>