<?php
session_start();
require_once '../../conn/conn.php';

// Check for valid JSON input
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

// Extract data
$email = $data['email'];
$username = $data['username'];
$firstName = $data['firstName'];
$middleName = $data['middleName'];
$lastName = $data['lastName'];
$phone = $data['phone'];
$address = $data['address'];
$password = password_hash($data['password'], PASSWORD_BCRYPT);
$ownerId = $data['ownerId'];

// Check if email already exists in manager or owner table
$checkQuery = "SELECT id FROM manager WHERE email = ? UNION SELECT id FROM owner WHERE email = ?";
$checkStmt = $conn->prepare($checkQuery);
$checkStmt->bind_param("ss", $email, $email);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Email is already registered']);
    exit;
}

// Insert into manager table
$query = "INSERT INTO manager (email, user_name, first_name, middle_name, last_name, contact_number, address, password, owner_id) 
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($query);
$stmt->bind_param("ssssssssi", $email, $username, $firstName, $middleName, $lastName, $phone, $address, $password, $ownerId);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Manager created successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to create manager']);
}
?>