<?php
require_once("../conn/conn.php");

$action = $_POST['action'] ?? '';

if ($action === 'forgot_password') {
    $email = $_POST['email'] ?? null;

    if (!$email) {
        echo json_encode(['status' => 'error', 'message' => 'Email is required']);
        exit;
    }

    $stmt = $conn->prepare("SELECT * FROM manager WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $managerResult = $stmt->get_result();

    if ($managerResult->num_rows > 0) {
        $user = $managerResult->fetch_assoc();

        echo json_encode(['status' => 'success', 'message' => 'Password reset email sent to manager']);
        exit;
    }

    $stmt = $conn->prepare("SELECT * FROM owner WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $ownerResult = $stmt->get_result();

    if ($ownerResult->num_rows > 0) {
        $user = $ownerResult->fetch_assoc();

        echo json_encode(['status' => 'success', 'message' => 'Password reset email sent to owner']);
        exit;
    }

    echo json_encode(['status' => 'error', 'message' => 'No account found with this email']);
    exit;
}
?>