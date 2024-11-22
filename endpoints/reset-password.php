<?php
session_start();
require_once('../conn/conn.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $token = $data['token'] ?? '';
    $newPassword = $data['new_password'] ?? '';

    // Validate inputs
    if (empty($token) || empty($newPassword)) {
        echo json_encode(['status' => 'error', 'message' => 'Token and new password are required.']);
        http_response_code(400);
        exit;
    }

    if (strlen($newPassword) < 6) {
        echo json_encode(['status' => 'error', 'message' => 'Password must be at least 6 characters long.']);
        http_response_code(400);
        exit;
    }

    // Hash the new password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    // Check token validity
    $query = "SELECT email FROM password_reset_tokens WHERE token = ? AND expires_at > NOW()";
    $stmt = $conn->prepare($query);

    if (!$stmt) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
        http_response_code(500);
        exit;
    }

    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid or expired token.']);
        http_response_code(400);
        exit;
    }

    $email = $result->fetch_assoc()['email'];

    // Begin transaction to ensure atomicity
    $conn->begin_transaction();
    try {
        // Update the password in `manager` table
        $updateManager = $conn->prepare("UPDATE manager SET password = ? WHERE email = ?");
        $updateOwner = $conn->prepare("UPDATE owner SET password = ? WHERE email = ?");

        if (!$updateManager || !$updateOwner) {
            throw new Exception('Database error: ' . $conn->error);
        }

        $updateManager->bind_param("ss", $hashedPassword, $email);
        $updateOwner->bind_param("ss", $hashedPassword, $email);

        // Check if at least one row was updated
        $managerUpdated = $updateManager->execute() && $updateManager->affected_rows > 0;
        $ownerUpdated = $updateOwner->execute() && $updateOwner->affected_rows > 0;

        if (!$managerUpdated && !$ownerUpdated) {
            throw new Exception('Failed to update password for the provided email.');
        }

        // Delete the token after password update
        $deleteToken = $conn->prepare("DELETE FROM password_reset_tokens WHERE email = ?");
        if (!$deleteToken) {
            throw new Exception('Database error: ' . $conn->error);
        }

        $deleteToken->bind_param("s", $email);
        $deleteToken->execute();

        // Commit transaction
        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'Password has been reset successfully.']);
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        http_response_code(500);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    http_response_code(405);
}
?>