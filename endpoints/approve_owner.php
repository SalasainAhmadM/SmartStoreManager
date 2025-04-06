<?php
require_once '../conn/conn.php';
require '../vendor/autoload.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';
require '../PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

try {
    // Get JSON input
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['owner_id'])) {
        throw new Exception('Owner ID is required');
    }

    $ownerId = $data['owner_id'];

    $stmt = $conn->prepare("UPDATE owner SET is_approved = 1 WHERE id = ?");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("i", $ownerId);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    if ($stmt->affected_rows === 0) {
        throw new Exception('No owner found with the provided ID');
    }
    $stmt->close();

    $stmt = $conn->prepare("SELECT email FROM owner WHERE id = ?");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("i", $ownerId);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $stmt->bind_result($ownerEmail);
    if (!$stmt->fetch()) {
        throw new Exception('Owner not found');
    }
    $stmt->close();

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'slythelang@gmail.com';
        $mail->Password = 'febhdvuapwmtagdt';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->SMTPDebug = 0;

        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        $mail->setFrom('slythelang@gmail.com', 'Smart Store Manager');
        $mail->addAddress($ownerEmail);

        $mail->isHTML(true);
        $mail->Subject = 'Account Approved';
        $mail->Body = '
            <h3>Your Account Has Been Approved!</h3>
            <p>You can now access your account.</p>
            <p><a href="https://lightslategrey-stork-969980.hostingersite.com">Click here to login</a></p>
        ';
        $mail->AltBody = 'Your account has been approved. You can now login at https://lightslategrey-stork-969980.hostingersite.com';

        $mail->send();
        $emailStatus = ['sent' => true];
    } catch (Exception $e) {
        error_log('Mailer Error: ' . $e->getMessage());
        $emailStatus = [
            'sent' => false,
            'error' => $e->getMessage()
        ];
    }

    echo json_encode([
        'success' => true,
        'email_status' => $emailStatus
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
