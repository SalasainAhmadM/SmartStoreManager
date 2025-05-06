<?php
session_start();
require_once '../../conn/auth.php';
require_once '../../conn/conn.php';
require '../../vendor/autoload.php';
require '../../PHPMailer/src/PHPMailer.php';
require '../../PHPMailer/src/SMTP.php';
require '../../PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

validateSession('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $businessId = $data['businessId'] ?? null;
    $feedback = trim($data['feedback'] ?? '');


    if (!$businessId) {
        echo json_encode(['success' => false, 'message' => 'Invalid business ID']);
        exit;
    }

    try {
        $conn->begin_transaction();

        // Get owner email and business name before deletion
        $stmt = $conn->prepare("SELECT o.email, b.name FROM business b JOIN owner o ON b.owner_id = o.id WHERE b.id = ?");
        $stmt->bind_param("i", $businessId);
        $stmt->execute();
        $stmt->bind_result($ownerEmail, $businessName);
        if (!$stmt->fetch()) {
            throw new Exception('Business or owner not found.');
        }
        $stmt->close();

        // Delete the business
        $stmt = $conn->prepare("DELETE FROM business WHERE id = ?");
        $stmt->bind_param("i", $businessId);
        $stmt->execute();

        $conn->commit();

        // Send email notification
        $emailStatus = ['sent' => false, 'error' => ''];
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'slythelang@gmail.com';
            $mail->Password = 'febhdvuapwmtagdt';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->SMTPDebug = 0;

            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];

            $mail->setFrom('slythelang@gmail.com', 'Smart Store Manager');
            $mail->addAddress($ownerEmail);

            $mail->isHTML(true);
            $mail->Subject = 'Business Rejected';
            $mail->Body = "
    <h3>Your business <strong>{$businessName}</strong> has been rejected.</h3>
    " . ($feedback ? "<p><strong>Reason:</strong> {$feedback}</p>" : "") . "
    <p>If you think this was a mistake, please contact our support team immediately.</p>
";

            $mail->AltBody = 'Your business has been rejected.' . ($feedback ? " Reason: $feedback" : '');


            $mail->send();
            $emailStatus['sent'] = true;
        } catch (Exception $e) {
            $emailStatus['error'] = $e->getMessage();
            error_log('Email Error: ' . $e->getMessage());
        }

        echo json_encode([
            'success' => true,
            'email_status' => $emailStatus
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>