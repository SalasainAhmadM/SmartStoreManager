<?php
session_start();
require_once '../../conn/auth.php';
require_once '../../conn/conn.php';

validateSession('owner'); // Ensure only authorized users access this endpoint

$data = json_decode(file_get_contents('php://input'), true);
$receiver_id = $data['receiver_id'] ?? null;
$message = $data['message'] ?? null;
$sender_id = $_SESSION['user_id'];
$sender_type = 'owner';

if (!$receiver_id || !$message) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
    exit;
}

// Insert the message into the database
$query = "INSERT INTO messages (sender_id, receiver_id, message, sender_type) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($query);
$stmt->bind_param("iiss", $sender_id, $receiver_id, $message, $sender_type);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to send message']);
}
?>