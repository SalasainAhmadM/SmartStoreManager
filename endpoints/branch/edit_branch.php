<?php
header('Content-Type: application/json');
require_once '../../conn/conn.php';

// Directory where permits are stored
$permitDir = "../../assets/branch_permits/";

// Create directory if it doesn't exist
if (!file_exists($permitDir)) {
    mkdir($permitDir, 0777, true);
}

try {
    // Get the current permit filename from database
    $stmt = $conn->prepare("SELECT business_permit FROM branch WHERE id = ?");
    $stmt->bind_param("i", $_POST['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $currentPermit = $result->fetch_assoc()['business_permit'];
    $stmt->close();

    $newPermit = $currentPermit; // Default to current permit

    // Handle file upload if a new permit was provided
    if (!empty($_FILES['permit']['name'])) {
        // Generate unique filename
        $fileExt = pathinfo($_FILES['permit']['name'], PATHINFO_EXTENSION);
        $newPermit = 'branch_permit_' . $_POST['id'] . '_' . time() . '.' . $fileExt;

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

    // Prepare the SQL query to update the branch
    $sql = "UPDATE branch SET location = ?, business_permit = ?, updated_at = NOW() WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssi', $_POST['location'], $newPermit, $_POST['id']);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Branch updated successfully']);
    } else {
        throw new Exception($conn->error);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($stmt))
        $stmt->close();
    $conn->close();
}
?>