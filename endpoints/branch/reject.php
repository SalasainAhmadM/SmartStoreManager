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
    $branchId = $data['branchId'] ?? null;
    $feedback = trim($data['feedback'] ?? '');

    if (!$branchId) {
        echo json_encode(['success' => false, 'message' => 'Invalid branch ID']);
        exit;
    }

    try {
        $conn->begin_transaction();

        // Get branch info
        $stmt = $conn->prepare("SELECT location, business_id FROM branch WHERE id = ?");
        $stmt->bind_param("i", $branchId);
        $stmt->execute();
        $stmt->bind_result($branchName, $businessId);
        if (!$stmt->fetch()) {
            throw new Exception('Branch not found');
        }
        $stmt->close();

        // Get business name and owner ID
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

        // Delete the branch
        $stmt = $conn->prepare("DELETE FROM branch WHERE id = ?");
        $stmt->bind_param("i", $branchId);
        $stmt->execute();
        $stmt->close();

        $conn->commit();

        // Send rejection email
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
            <h3>Your branch <strong>{$branchName}</strong> from <strong>{$businessName}</strong> has been rejected.</h3>
            " . ($feedback ? "<p><strong>Reason:</strong> {$feedback}</p>" : "") . "
            <p>If you think this was a mistake, please contact our support team immediately.</p>
        ";

        $mail->AltBody = "Your branch '{$branchName}' from '{$businessName}' was rejected." . ($feedback ? " Reason: {$feedback}" : '');

        $mail->send();

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        error_log('Branch rejection error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>