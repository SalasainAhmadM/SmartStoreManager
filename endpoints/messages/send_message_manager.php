<?php
session_start();
require_once '../../conn/auth.php';
require_once '../../conn/conn.php';
validateSession('manager');

$manager_id = $_SESSION['user_id'];

// Validate POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['message'])) {
    echo json_encode(['error' => 'Invalid request.']);
    exit;
}

$message = trim($_POST['message']);

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

// Insert the message into the database
$query = "INSERT INTO messages (sender_id, receiver_id, message, sender_type) VALUES (?, ?, ?, 'manager')";
$stmt = $conn->prepare($query);
$stmt->bind_param("iis", $manager_id, $owner_id, $message);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Failed to send message.']);
}
?>