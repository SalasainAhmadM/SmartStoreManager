<?php
require_once '../../conn/conn.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check for missing fields
    if (!isset($_POST['name'], $_POST['asset'], $_POST['employeeCount'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $asset = trim($_POST['asset']);
    $employeeCount = trim($_POST['employeeCount']);
    $owner_id = $_SESSION['user_id'];

    // Check for empty fields
    if (empty($name) || empty($asset) || empty($employeeCount)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }

    // Insert into database
    $query = "INSERT INTO business (name, description, asset, employee_count, owner_id) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssssi", $name, $description, $asset, $employeeCount, $owner_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Business added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error adding business']);
    }

    exit;
}
?>