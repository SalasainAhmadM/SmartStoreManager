<?php
header('Content-Type: application/json');
require_once '../../conn/conn.php';

// Create permits directory if it doesn't exist
$permitDir = "../../assets/branch_permits/";
if (!file_exists($permitDir)) {
    mkdir($permitDir, 0777, true);
}

try {
    // Validate input
    if (!isset($_POST['business_id'], $_POST['location'], $_FILES['permit'])) {
        throw new Exception('Business ID, location, and permit are required');
    }

    $business_id = intval($_POST['business_id']);
    $location = trim($_POST['location']);
    $permitFile = $_FILES['permit'];

    if (!$business_id || !$location) {
        throw new Exception('Business ID and location are required');
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

    // Check for duplicate branch location for the same business
    $check_sql = "SELECT id FROM branch WHERE business_id = ? AND location = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param('is', $business_id, $location);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        throw new Exception('Branch location already exists for this business');
    }
    $check_stmt->close();

    // Generate unique filename for permit
    $fileExt = pathinfo($permitFile['name'], PATHINFO_EXTENSION);
    $filename = 'branch_permit_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $fileExt;
    $filepath = $permitDir . $filename;

    // Move uploaded file
    if (!move_uploaded_file($permitFile['tmp_name'], $filepath)) {
        throw new Exception('Failed to upload business permit');
    }

    // Check if the business has a manager
    $manager_id = null;
    $manager_check_sql = "SELECT manager_id FROM business WHERE id = ?";
    $manager_check_stmt = $conn->prepare($manager_check_sql);
    $manager_check_stmt->bind_param('i', $business_id);
    $manager_check_stmt->execute();
    $manager_check_stmt->bind_result($manager_id);
    $manager_check_stmt->fetch();
    $manager_check_stmt->close();

    // Insert the new branch
    $sql = "INSERT INTO branch (location, business_id, manager_id, business_permit, created_at) 
            VALUES (?, ?, ?, ?, NOW())";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('siis', $location, $business_id, $manager_id, $filename);

    if (!$stmt->execute()) {
        // Delete the uploaded file if DB insert fails
        if (file_exists($filepath)) {
            unlink($filepath);
        }
        throw new Exception('Failed to add branch');
    }

    // If the business had a manager, clear it from the business table
    $message = 'Branch added successfully! Pending for approval';
    if ($manager_id) {
        $clear_manager_sql = "UPDATE business SET manager_id = NULL WHERE id = ?";
        $clear_manager_stmt = $conn->prepare($clear_manager_sql);
        $clear_manager_stmt->bind_param('i', $business_id);
        $clear_manager_stmt->execute();
        $clear_manager_stmt->close();
        $message .= ' The business manager has been reassigned here.';
    }

    echo json_encode([
        'success' => true,
        'message' => $message
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>