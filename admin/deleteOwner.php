<?php
session_start();
require_once '../conn/auth.php';
require_once '../conn/conn.php';
validateSession('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'message' => 'Invalid request method']));
}

$owner_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

try {
    // Verify owner exists
    $stmt = $conn->prepare("SELECT * FROM owner WHERE id = ?");
    $stmt->bind_param('i', $owner_id);
    $stmt->execute();
    $owner = $stmt->get_result()->fetch_assoc();

    if (!$owner) {
        exit(json_encode(['success' => false, 'message' => 'Owner not found']));
    }

    // Delete owner
    $stmt = $conn->prepare("DELETE FROM owner WHERE id = ?");
    $stmt->bind_param('i', $owner_id);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        exit(json_encode(['success' => false, 'message' => 'No changes made']));
    }

    // Delete associated files
    if (!empty($owner['image']) && file_exists("../assets/profiles/" . $owner['image'])) {
        unlink("../assets/profiles/" . $owner['image']);
    }
    if (!empty($owner['valid_id']) && file_exists("../assets/valid_ids/" . $owner['valid_id'])) {
        unlink("../assets/valid_ids/" . $owner['valid_id']);
    }

    echo json_encode(['success' => true, 'message' => 'Owner deleted successfully']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}