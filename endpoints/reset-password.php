<?php
require_once('../conn/conn.php');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $token = $input['token'] ?? '';
    $newPassword = $input['new_password'] ?? '';

    if (empty($token) || empty($newPassword)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid input.']);
        http_response_code(400);
        exit;
    }

    // Validate token and expiration
    $query = "SELECT email FROM password_reset_tokens WHERE token = ? AND expires_at > NOW()";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $email = $result->fetch_assoc()['email'];
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

        // Update the password in the user's table (manager or owner)
        $updateQuery = "
            UPDATE manager SET password = ? WHERE email = ?
            UNION
            UPDATE owner SET password = ? WHERE email = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("ssss", $hashedPassword, $email, $hashedPassword, $email);
        $stmt->execute();

        // Delete the token after successful password reset
        $deleteTokenQuery = "DELETE FROM password_reset_tokens WHERE token = ?";
        $stmt = $conn->prepare($deleteTokenQuery);
        $stmt->bind_param("s", $token);
        $stmt->execute();

        echo json_encode(['status' => 'success', 'message' => 'Password reset successful.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid or expired token.']);
        http_response_code(400);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    http_response_code(405);
}
?>