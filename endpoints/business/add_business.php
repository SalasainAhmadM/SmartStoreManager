<?php
require_once '../../conn/conn.php';
session_start();

// Create permits directory if it doesn't exist
$permitDir = "../../assets/permits/";
if (!file_exists($permitDir)) {
    mkdir($permitDir, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Check for missing fields
        if (!isset($_POST['name'], $_POST['asset'], $_POST['employeeCount'], $_FILES['permit'])) {
            throw new Exception('Missing required fields');
        }

        $name = trim($_POST['name']);
        $description = trim($_POST['description'] ?? '');
        $asset = trim($_POST['asset']);
        $employeeCount = trim($_POST['employeeCount']);
        $location = trim($_POST['location']);
        $owner_id = $_SESSION['user_id'];
        $permitFile = $_FILES['permit'];

        // Check for empty fields
        if (empty($name) || empty($asset) || empty($employeeCount) || empty($location)) {
            throw new Exception('All fields are required');
        }

        // Validate asset size
        if ($asset > 15000000) {
            throw new Exception('Asset size must not exceed 15,000,000');
        }

        // Validate employee count
        if ($employeeCount > 99) {
            throw new Exception('Employee count must not exceed 99');
        }

        // Validate file upload
        if ($permitFile['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Business permit file is required');
        }

        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
        if (!in_array($permitFile['type'], $allowedTypes)) {
            throw new Exception('Only JPG, PNG, GIF images or PDF files are allowed');
        }

        // Validate file size (max 5MB)
        if ($permitFile['size'] > 5 * 1024 * 1024) {
            throw new Exception('File size must be less than 5MB');
        }

        // Generate unique filename
        $fileExt = pathinfo($permitFile['name'], PATHINFO_EXTENSION);
        $filename = 'permit_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $fileExt;
        $filepath = $permitDir . $filename;

        // Move uploaded file
        if (!move_uploaded_file($permitFile['tmp_name'], $filepath)) {
            throw new Exception('Failed to upload business permit');
        }

        // Insert into database
        $query = "INSERT INTO business (name, description, asset, employee_count, location, owner_id, business_permit) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssssis", $name, $description, $asset, $employeeCount, $location, $owner_id, $filename);

        if (!$stmt->execute()) {
            // Delete the uploaded file if DB insert fails
            if (file_exists($filepath)) {
                unlink($filepath);
            }
            throw new Exception('Error adding business to database');
        }

        echo json_encode(['success' => true, 'message' => 'Business added successfully']);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}
?>