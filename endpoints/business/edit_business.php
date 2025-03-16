<?php
require_once '../../conn/conn.php';

// Set the timezone to Asia/Manila
date_default_timezone_set('Asia/Manila');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $asset = $_POST['asset'];
    $employeeCount = $_POST['employeeCount'];
    $location = $_POST['location'];

    // Get the current timestamp in Asia/Manila timezone
    $updatedAt = date('Y-m-d H:i:s');

    // Update query including the updated_at field
    $query = "UPDATE business 
              SET name = ?, description = ?, asset = ?, employee_count = ?, location  = ?, updated_at = ? 
              WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssssssi", $name, $description, $asset, $employeeCount, $location, $updatedAt, $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }

    $stmt->close();
    $conn->close();
}
?>