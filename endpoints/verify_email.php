<?php
require_once("../conn/conn.php");
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';
require '../PHPMailer/src/Exception.php';
function handleEmailVerification($conn)
{
    $token = $_GET['token'] ?? null;

    if (!$token) {
        header("Location: http://localhost/smartstoremanager/home/index.php?verification=invalid");
        exit;
    }

    $stmt = $conn->prepare("SELECT id FROM owner WHERE verification_token = ? AND is_verified = 0");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        header("Location: http://localhost/smartstoremanager/home/index.php?verification=invalid");
        exit;
    }

    $row = $result->fetch_assoc();
    $ownerId = $row['id'];

    $stmt = $conn->prepare("UPDATE owner SET is_verified = 1, verification_token = NULL WHERE verification_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();

    header("Location: http://localhost/smartstoremanager/home/index.php?id=$ownerId&verification=success");
    exit;
}
// Call the function
handleEmailVerification($conn);
?>