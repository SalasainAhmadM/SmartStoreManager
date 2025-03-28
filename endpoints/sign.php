<?php

header('Content-Type: application/json');

require_once("../conn/conn.php");
require '../vendor/autoload.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';
require '../PHPMailer/src/Exception.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$action = $_POST['action'] ?? null;

if (!$action) {
    echo json_encode(['status' => 'error', 'message' => 'No action provided']);
    exit;
}

try {
    switch ($action) {
        case 'login':
            handleLogin($conn);
            break;
        case 'register':
            handleRegister($conn);
            break;
        case 'verify_email':
            handleEmailVerification($conn);
            break;
        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
}

$conn->close();

function handleLogin($conn)
{
    session_start();
    $emailOrUsername = $_POST['email'] ?? null;
    $password = $_POST['password'] ?? null;

    if (!$emailOrUsername || !$password) {
        echo json_encode(['status' => 'error', 'message' => 'Email/Username and password are required']);
        return;
    }

    // Check in the admin table first
    $stmt = $conn->prepare("
        SELECT id, password, 'admin' AS role, 1 AS is_verified, 1 AS is_approved
        FROM admin 
        WHERE email = ? OR user_name = ?
    ");
    $stmt->bind_param("ss", $emailOrUsername, $emailOrUsername);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        // Check in the owner table if not admin
        $stmt = $conn->prepare("
            SELECT id, password, 'owner' AS role, is_verified, is_approved
            FROM owner 
            WHERE email = ? OR user_name = ?
        ");
        $stmt->bind_param("ss", $emailOrUsername, $emailOrUsername);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            // Check in the manager table if not owner
            $stmt = $conn->prepare("
                SELECT id, password, 'manager' AS role, 1 AS is_verified, 1 AS is_approved 
                FROM manager 
                WHERE email = ? OR user_name = ?
            ");
            $stmt->bind_param("ss", $emailOrUsername, $emailOrUsername);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid email/username or password']);
                return;
            }
        }
    }

    $user = $result->fetch_assoc();

    if (!password_verify($password, $user['password'])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid email/username or password']);
        return;
    }

    // Check if owner is verified and approved
    if ($user['role'] === 'owner') {
        if (!$user['is_verified']) {
            echo json_encode(['status' => 'error', 'message' => 'Please verify your email first']);
            return;
        }

        if (!$user['is_approved']) {
            echo json_encode(['status' => 'unapproved', 'message' => 'Wait for the admin to approve your account']);
            return;
        }
    }

    // Set session variables on successful login
    $_SESSION['login_success'] = true;
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_role'] = $user['role'];

    echo json_encode(['status' => 'success', 'role' => $user['role'], 'id' => $user['id']]);
}

function handleRegister($conn)
{
    $userName = $_POST['userName'] ?? null;
    $email = $_POST['email'] ?? null;
    $password = $_POST['password'] ?? null;

    if (!$userName || !$email || !$password) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
        return;
    }

    // Check if the email exists in the owner or manager table
    $stmt = $conn->prepare("
        SELECT id FROM owner WHERE email = ? 
        UNION 
        SELECT id FROM manager WHERE email = ?
    ");
    $stmt->bind_param("ss", $email, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Email is already registered']);
        return;
    }

    // Check if the user_name exists in the owner table
    $stmt = $conn->prepare("
        SELECT id FROM owner WHERE user_name = ?
        UNION 
        SELECT id FROM manager WHERE user_name = ?
    ");
    $stmt->bind_param("ss", $userName, $userName);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Username is already taken']);
        return;
    }

    // Hash the password for security
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // Generate verification token
    $verificationToken = bin2hex(random_bytes(32));
    $verificationLink = "http://" . $_SERVER['HTTP_HOST'] . "/smartstoremanager/endpoints/verify_email.php?token=" . $verificationToken;

    // Insert the new owner with is_verified set to 0 (false)
    $stmt = $conn->prepare("
        INSERT INTO owner (user_name, email, first_name, middle_name, last_name, gender, age, contact_number, created_at, image, password, verification_token, is_verified)
        VALUES (?, ?, '', '', '', '', '', '', NOW(), '', ?, ?, 0)
    ");
    $stmt->bind_param("ssss", $userName, $email, $hashedPassword, $verificationToken);

    if ($stmt->execute()) {
        // Send verification email
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'slythelang@gmail.com';
            $mail->Password = 'febhdvuapwmtagdt';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );

            $mail->setFrom('slythelang@gmail.com', 'Smart Store Manager');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Email Verification';
            $mail->Body = "Please click the following link to verify your email: <a href='$verificationLink'>Verify Email</a>";
            $mail->AltBody = "Please click the following link to verify your email: $verificationLink";

            $mail->send();

            echo json_encode([
                'status' => 'success',
                'message' => 'Registration successful. Please check your email for verification instructions.'
            ]);
        } catch (Exception $e) {
            // If email fails, delete the user record
            $conn->query("DELETE FROM owner WHERE email = '$email'");
            echo json_encode(['status' => 'error', 'message' => 'Failed to send verification email. Please try again.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to register user']);
    }
}

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