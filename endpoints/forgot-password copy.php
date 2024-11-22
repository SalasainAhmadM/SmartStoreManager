<?php
require_once("../conn/conn.php");
require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$action = $_POST['action'] ?? '';

if ($action === 'forgot_password') {
    $email = $_POST['email'] ?? null;

    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid email address']);
        exit;
    }

    $stmt = $conn->prepare("SELECT * FROM manager WHERE email = ? UNION SELECT * FROM owner WHERE email = ?");
    $stmt->bind_param("ss", $email, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $token = generateToken($email, $conn);

        if ($token) {
            sendResetEmail($email, $user['name'] ?? 'User', $token);
            echo json_encode(['status' => 'success', 'message' => 'Password reset email sent']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to generate reset token']);
        }
        exit;
    }

    echo json_encode(['status' => 'error', 'message' => 'No account found with this email']);
    exit;
}

function generateToken($email, $conn)
{
    try {
        $token = bin2hex(random_bytes(64));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $stmt = $conn->prepare("DELETE FROM password_reset_tokens WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();

        $stmt = $conn->prepare("INSERT INTO password_reset_tokens (email, token, expires_at) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $email, $token, $expiresAt);
        $stmt->execute();

        return $token;
    } catch (Exception $e) {
        error_log("Error generating token: " . $e->getMessage());
        return null;
    }
}

function sendResetEmail($email, $name, $token)
{
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'jj16092024@gmail.com';
        $mail->Password = 'jkeosxbmlwsrulor';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        $mail->setFrom('jj16092024@gmail.com', 'Smart Store Manager');
        $mail->addAddress($email, $name);

        $resetLink = "http://localhost/smartstoremanager/index.php?token=$token";
        $mail->isHTML(true);
        $mail->Subject = 'Reset your password';
        $mail->Body = "
            <h3>Reset Password Request</h3>
            <p>We received a request to reset your password. Click the link below to reset it:</p>
            <a href='$resetLink'>Reset Password</a>
            <p>If you did not request a password reset, please ignore this email.</p>
        ";

        $mail->send();
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        echo json_encode(['status' => 'error', 'message' => 'Failed to send email']);
        exit;
    }
}
?>