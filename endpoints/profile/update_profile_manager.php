<?php
session_start();
require_once '../../conn/conn.php';
require_once '../../conn/auth.php';

validateSession('manager');

$manager_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Decode incoming JSON data or handle file upload
    if (isset($_FILES['file'])) {
        // Handle profile picture upload
        $upload_dir = '../../assets/profiles/';
        $file = $_FILES['file'];

        // Validate file type
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowed_types)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid file type']);
            exit;
        }

        // Generate unique file name
        $filename = $manager_id . '_' . time() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
        $upload_path = $upload_dir . $filename;

        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            $query = "UPDATE manager SET image = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("si", $filename, $manager_id);

            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Profile picture updated successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to update profile picture in database']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to save uploaded file']);
        }
        exit;
    }

    // Handle other fields
    $data = json_decode(file_get_contents('php://input'), true);
    $field = $data['field'] ?? null;
    $value = $data['value'] ?? null;

    $allowed_fields = ['first_name', 'middle_name', 'last_name', 'email', 'password', 'contact_number', 'barangay', 'city', 'province', 'region', 'gender', 'age', 'birthday'];

    if (!in_array($field, $allowed_fields)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid field']);
        exit;
    }

    // Special handling for sensitive fields
    if ($field === 'password') {
        if (strlen($value) < 6) {
            echo json_encode(['status' => 'error', 'message' => 'Password must be at least 6 characters long']);
            exit;
        }
        $value = password_hash($value, PASSWORD_BCRYPT);
    }

    // Prepare and execute the update query
    $query = "UPDATE manager SET $field = ? WHERE id = ?";
    $stmt = $conn->prepare($query);

    if (!$stmt) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to prepare the update query']);
        exit;
    }

    $stmt->bind_param("si", $value, $manager_id);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Profile updated successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update profile']);
    }
    exit;
}
?>