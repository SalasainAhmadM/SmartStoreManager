<?php
session_start();
require_once('../conn/conn.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $token = $data['token'] ?? '';
    $newPassword = $data['new_password'] ?? '';

    // Validate input
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

    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    // Validate token
    $query = "SELECT email FROM password_reset_tokens WHERE token = ? AND expires_at > NOW()";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        echo json_encode(['status' => 'error', 'message' => 'Database error.']);
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

    // Update password for manager and owner separately
    $updateManager = $conn->prepare("UPDATE manager SET password = ? WHERE email = ?");
    $updateManager->bind_param("ss", $hashedPassword, $email);

    $updateOwner = $conn->prepare("UPDATE owner SET password = ? WHERE email = ?");
    $updateOwner->bind_param("ss", $hashedPassword, $email);

    if ($updateManager->execute() || $updateOwner->execute()) {
        // Delete token
        $deleteToken = $conn->prepare("DELETE FROM password_reset_tokens WHERE email = ?");
        $deleteToken->bind_param("s", $email);
        $deleteToken->execute();

        echo json_encode(['status' => 'success', 'message' => 'Password has been reset successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to reset password.']);
        http_response_code(500);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    http_response_code(405);
}
?>