<?php
header('Content-Type: application/json');
require_once '../../conn/conn.php';

// Create permits directory if it doesn't exist
$permitDir = "../../assets/permits/";
if (!file_exists($permitDir)) {
    mkdir($permitDir, 0777, true);
}

try {
    // Validate required fields
    $required = ['name', 'asset', 'employeeCount', 'location', 'owner_id'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("All required fields must be filled.");
        }
    }

    // Validate file upload
    if (!isset($_FILES['permit']) || $_FILES['permit']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Business permit file is required.");
    }

    $name = trim($_POST['name']);
    $description = trim($_POST['description'] ?? '');
    $asset = trim($_POST['asset']);
    $employeeCount = trim($_POST['employeeCount']);
    $location = trim($_POST['location']);
    $owner_id = intval($_POST['owner_id']);
    $permitFile = $_FILES['permit'];

    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
    if (!in_array($permitFile['type'], $allowedTypes)) {
        throw new Exception("Only JPG, PNG, GIF images or PDF files are allowed.");
    }

    // Validate file size (max 5MB)
    if ($permitFile['size'] > 5 * 1024 * 1024) {
        throw new Exception("File size must be less than 5MB.");
    }

    // Generate unique filename
    $fileExt = pathinfo($permitFile['name'], PATHINFO_EXTENSION);
    $filename = 'permit_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $fileExt;
    $filepath = $permitDir . $filename;

    // Move uploaded file
    if (!move_uploaded_file($permitFile['tmp_name'], $filepath)) {
        throw new Exception("Failed to upload business permit.");
    }

    // Insert business into the database
    $query = "
        INSERT INTO business 
        (name, asset, employee_count, description, location, created_at, owner_id, business_permit)
        VALUES (?, ?, ?, ?, ?, NOW(), ?, ?)
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('sssssis', $name, $asset, $employeeCount, $description, $location, $owner_id, $filename);

    if (!$stmt->execute()) {
        // Delete the uploaded file if DB insert fails
        if (file_exists($filepath)) {
            unlink($filepath);
        }
        throw new Exception("Error adding business to database.");
    }

    // Update is_new_owner to 0 for the owner
    $updateOwnerQuery = "UPDATE owner SET is_new_owner = 0 WHERE id = ?";
    $updateStmt = $conn->prepare($updateOwnerQuery);
    $updateStmt->bind_param('i', $owner_id);

    if (!$updateStmt->execute()) {
        throw new Exception("Error updating owner status.");
    }

    echo json_encode(['success' => true, 'message' => 'Business added successfully!']);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}