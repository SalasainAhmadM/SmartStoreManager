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

        // Approve branch
        $stmt = $conn->prepare("UPDATE branch SET is_approved = 1 WHERE id = ?");
        $stmt->bind_param("i", $branchId);
        $stmt->execute();
        $stmt->close();

        // Get branch location and business_id
        $stmt = $conn->prepare("SELECT location, business_id FROM branch WHERE id = ?");
        $stmt->bind_param("i", $branchId);
        $stmt->execute();
        $stmt->bind_result($branchName, $businessId);
        if (!$stmt->fetch()) {
            throw new Exception('Branch not found');
        }
        $stmt->close();

        // Get business name and owner_id
        $stmt = $conn->prepare("SELECT name, owner_id FROM business WHERE id = ?");
        $stmt->bind_param("i", $businessId);
        $stmt->execute();
        $stmt->bind_result($businessName, $ownerId);
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

        $conn->commit();

        // Send Email
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
        $mail->Subject = 'Branch Approved';

        $mail->Body = "
            <h3>Your branch <strong>{$branchName}</strong> from <strong>{$businessName}</strong> has been approved!</h3>
            <p>You can now manage this branch through the Smart Store Manager Website.</p>
            <p><a href=\"https://lightslategrey-stork-969980.hostingersite.com\">Click here to login</a></p>
        ";
        $mail->AltBody = "Your Branch {$branchName} from {$businessName} has been approved! Login at https://lightslategrey-stork-969980.hostingersite.com";

        $mail->send();

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        error_log('Branch approval error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>