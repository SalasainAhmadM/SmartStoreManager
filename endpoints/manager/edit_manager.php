<?php
require_once '../../conn/conn.php';

$data = json_decode(file_get_contents("php://input"), true);
if (!$data || !isset($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

$id = $data['id'];
$email = $data['email'];
$username = $data['username'];
$firstName = $data['firstName'];
$middleName = $data['middleName'];
$lastName = $data['lastName'];
$phone = $data['phone'];
$address = $data['address'];

// Update the manager record
$query = "UPDATE manager SET email = ?, user_name = ?, first_name = ?, middle_name = ?, last_name = ?, contact_number = ?, address = ? WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("sssssssi", $email, $username, $firstName, $middleName, $lastName, $phone, $address, $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update manager']);
}
?>