<?php
require_once("./conn/conn.php");

$token = $_GET['token'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = $_POST['password'] ?? '';
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    // Validate the token and fetch the email
    $stmt = $conn->prepare("SELECT email FROM password_reset_tokens WHERE token = ? AND expires_at > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $email = $user['email'];

        // Update the user's password in the appropriate table
        $stmt = $conn->prepare("UPDATE manager SET password = ? WHERE email = ? UNION UPDATE owner SET password = ? WHERE email = ?");
        $stmt->bind_param("ssss", $hashedPassword, $email, $hashedPassword, $email);
        $stmt->execute();

        // Delete the used token
        $stmt = $conn->prepare("DELETE FROM password_reset_tokens WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();

        echo "Password reset successful!";
    } else {
        echo "Invalid or expired token.";
    }
    exit;
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Reset Password</title>
</head>

<body>
    <form method="POST">
        <input type="password" name="password" placeholder="Enter new password" required>
        <button type="submit">Reset Password</button>
    </form>
</body>

</html>