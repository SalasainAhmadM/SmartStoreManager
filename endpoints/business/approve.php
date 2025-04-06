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
    $businessId = $_GET['id'] ?? null;

    if (!$businessId) {
        echo json_encode(['success' => false, 'message' => 'Invalid business ID']);
        exit;
    }

    try {
        $conn->begin_transaction();

        // Approve business
        $stmt = $conn->prepare("UPDATE business SET is_approved = 1 WHERE id = ?");
        $stmt->bind_param("i", $businessId);
        $stmt->execute();

        $conn->commit();

        $emailStatus = ['sent' => false, 'error' => ''];

        try {
            // Get owner ID from business
            $stmt = $conn->prepare("SELECT owner_id FROM business WHERE id = ?");
            $stmt->bind_param("i", $businessId);
            $stmt->execute();
            $stmt->bind_result($ownerId);

            if (!$stmt->fetch()) {
                throw new Exception('Business not found');
            }
            $stmt->close();

            // Get business name
            $stmt = $conn->prepare("SELECT name FROM business WHERE id = ?");
            $stmt->bind_param("s", $businessId);
            $stmt->execute();
            $stmt->bind_result($businessName);

            if (!$stmt->fetch()) {
                throw new Exception('Business not found');
            }
            $stmt->close();

            // Get owner email
            $stmt = $conn->prepare("SELECT email FROM owner WHERE id = ?");
            $stmt->bind_param("i", $ownerId);
            $stmt->execute();
            $stmt->bind_result($ownerEmail);

            if (!$stmt->fetch()) {
                throw new Exception('Owner not found');
            }
            $stmt->close();

            // Send approval email
            $mail = new PHPMailer(true);
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
            $mail->Subject = 'Business Approved';
            $mail->Body = "
                <h3>Your business <strong>{$businessName}<strong> has been approved!</h3>
                <p>Your business can now be accessed through the Smart Store Manager Website.</p>
                <p><a href=\"https://lightslategrey-stork-969980.hostingersite.com\">Click here to login</a></p>
            ";
            $mail->AltBody = 'Your business has been approved. You can now login at https://lightslategrey-stork-969980.hostingersite.com';


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