<?php

header('Content-Type: application/json');

require_once("../conn/conn.php");

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

    // Check in the owner table for both email and user_name
    $stmt = $conn->prepare("
        SELECT id, password, 'owner' AS role 
        FROM owner 
        WHERE email = ? OR user_name = ?
    ");
    $stmt->bind_param("ss", $emailOrUsername, $emailOrUsername);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        // Check in the manager table for both email and user_name
        $stmt = $conn->prepare("
            SELECT id, password, 'manager' AS role 
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

    $user = $result->fetch_assoc();

    if (!password_verify($password, $user['password'])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid email/username or password']);
        return;
    }

    // Set session variables on successful login
    $_SESSION['login_success'] = true;
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_role'] = $user['role'];

    echo json_encode(['status' => 'success', 'role' => $user['role']]);
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

    // Insert the new owner
    $stmt = $conn->prepare("
        INSERT INTO owner (user_name, email, first_name, middle_name, last_name, gender, age, address, contact_number, created_at, image, password)
        VALUES (?, ?, '', '', '', '', '', '', '', NOW(), '', ?)
    ");
    $stmt->bind_param("sss", $userName, $email, $hashedPassword);

    if ($stmt->execute()) {
        // Insert activity record
        $activityStmt = $conn->prepare("
            INSERT INTO activity (message, created_at, status, user, user_id) 
            VALUES ('New User Registered', NOW(), 'Completed', 'owner', NULL)
        ");
        $activityStmt->execute();
        echo json_encode(['status' => 'success', 'message' => 'Registration successful']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to register user']);
    }
}

?>