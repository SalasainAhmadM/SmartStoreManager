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

    // Directory where permits are stored
    $permitDir = "../../assets/permits/";

    try {
        // First, get the current permit filename from database
        $stmt = $conn->prepare("SELECT business_permit FROM business WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $currentPermit = $result->fetch_assoc()['business_permit'];
        $stmt->close();

        $newPermit = $currentPermit; // Default to current permit

        // Handle file upload if a new permit was provided
        if (!empty($_FILES['permit']['name'])) {
            // Generate unique filename
            $fileExt = pathinfo($_FILES['permit']['name'], PATHINFO_EXTENSION);
            $newPermit = 'permit_' . $id . '_' . time() . '.' . $fileExt;

            // Move uploaded file
            if (move_uploaded_file($_FILES['permit']['tmp_name'], $permitDir . $newPermit)) {
                // Delete old permit file if it exists (only after successful upload)
                if ($currentPermit && file_exists($permitDir . $currentPermit)) {
                    unlink($permitDir . $currentPermit);
                }
            } else {
                throw new Exception("Failed to upload permit file");
            }
        }

        // Update query including the updated_at field and permit
        $query = "UPDATE business 
                  SET name = ?, description = ?, asset = ?, employee_count = ?, 
                      location = ?, updated_at = ?, business_permit = ?
                  WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param(
            "sssssssi",
            $name,
            $description,
            $asset,
            $employeeCount,
            $location,
            $updatedAt,
            $newPermit,
            $id
        );

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            throw new Exception($conn->error);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    } finally {
        if (isset($stmt))
            $stmt->close();
        $conn->close();
    }
}
?>