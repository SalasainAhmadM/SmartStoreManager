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
    $branchId = $_GET['id'] ?? null;

    if (!$branchId) {
        echo json_encode(['success' => false, 'message' => 'Invalid branch ID']);
        exit;
    }

    try {
        $conn->begin_transaction();

        // Get owner email and branch details before rejection
        $stmt = $conn->prepare("SELECT o.email, b.location, b.business_id FROM branch b JOIN business bu ON b.business_id = bu.id JOIN owner o ON bu.owner_id = o.id WHERE b.id = ?");
        $stmt->bind_param("i", $branchId);
        $stmt->execute();
        $stmt->bind_result($ownerEmail, $branchLocation, $businessId);
        if (!$stmt->fetch()) {
            throw new Exception('Branch or owner not found.');
        }
        $stmt->close();

        // Reject the branch
        $stmt = $conn->prepare("DELETE FROM branch WHERE id = ?");
        $stmt->bind_param("i", $branchId);
        $stmt->execute();

        $conn->commit();

        // Get business name
        $stmt = $conn->prepare("SELECT name FROM business WHERE id = ?");
        $stmt->bind_param("i", $businessId);
        $stmt->execute();
        $stmt->bind_result($businessName);
        $stmt->fetch();
        $stmt->close();

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
            $mail->Subject = 'Branch Rejected';
            $mail->Body = "
                <h3>Your branch located at <strong>{$branchLocation}</strong> for the business <strong>{$businessName}</strong> has been rejected.</h3>
                <p>If you think this was a mistake, please contact our support team immediately.</p>
            ";
            $mail->AltBody = "Your branch located at {$branchLocation} for the business {$businessName} has been rejected. Contact support if this was a mistake.";

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