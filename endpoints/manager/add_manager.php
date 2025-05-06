<?php
session_start();
require_once '../../conn/conn.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$email = $data['email'];
$username = $data['username'];
$firstName = $data['firstName'];
$middleName = $data['middleName'];
$lastName = $data['lastName'];
$phone = $data['phone'];
$city = $data['city'];
$barangay = $data['barangay'];
$province = $data['province'];
$region = $data['region'];
$password = password_hash($data['password'], PASSWORD_BCRYPT);
$ownerId = $data['ownerId'];

// Check if email already exists
$checkQuery = "SELECT id FROM manager WHERE email = ? UNION SELECT id FROM owner WHERE email = ?";
$checkStmt = $conn->prepare($checkQuery);
$checkStmt->bind_param("ss", $email, $email);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Email is already registered']);
    exit;
}

// Check if username already exists
$checkUsernameQuery = "SELECT id FROM manager WHERE user_name = ? UNION SELECT id FROM owner WHERE user_name = ?";
$checkUsernameStmt = $conn->prepare($checkUsernameQuery);
$checkUsernameStmt->bind_param("ss", $username, $username);
$checkUsernameStmt->execute();
$checkUsernameResult = $checkUsernameStmt->get_result();

if ($checkUsernameResult->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Username is already taken']);
    exit;
}

// Insert into manager table
$query = "INSERT INTO manager (email, user_name, first_name, middle_name, last_name, contact_number, barangay, city, province, region, password, owner_id) 
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($query);
$stmt->bind_param("sssssssssssi", $email, $username, $firstName, $middleName, $lastName, $phone, $barangay, $city, $province, $region, $password, $ownerId);

if ($stmt->execute()) {

    $currentDateTime = date('Y-m-d H:i:s');

    $fullName = trim("$firstName $middleName $lastName");

    $activityQuery = "INSERT INTO activity (message, created_at, status, user, user_id) 
                      VALUES (?, ?, 'Completed', 'owner', ?)";

    $activityMessage = "New Manager Added: $fullName";
    $activityStmt = $conn->prepare($activityQuery);
    $activityStmt->bind_param("ssi", $activityMessage, $currentDateTime, $ownerId);
    $activityStmt->execute();

    echo json_encode(['success' => true, 'message' => 'Manager created successfully and activity logged']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to create manager']);
}
?>