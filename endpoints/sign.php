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
    session_start(); // Start the session
    $email = $_POST['email'] ?? null;
    $password = $_POST['password'] ?? null;

    if (!$email || !$password) {
        echo json_encode(['status' => 'error', 'message' => 'Email and password are required']);
        return;
    }

    $stmt = $conn->prepare("SELECT * FROM owner WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid email or password']);
        return;
    }

    $user = $result->fetch_assoc();

    if (!password_verify($password, $user['password'])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid email or password']);
        return;
    }

    // Set a session variable for successful login
    $_SESSION['login_success'] = true;

    // Respond with success
    echo json_encode(['status' => 'success', 'message' => 'Login successful']);
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

    // Check if the user already exists
    $stmt = $conn->prepare("SELECT * FROM owner WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'User already exists']);
        return;
    }

    // Hash the password for security
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // Insert the new user
    $stmt = $conn->prepare("
        INSERT INTO owner (user_name, email, first_name, middle_name, last_name, gender, age, address, contact_number, created_at, image, password)
        VALUES (?, ?, '', '', '', '', '', '', '', NOW(), '', ?)
    ");
    $stmt->bind_param("sss", $userName, $email, $hashedPassword);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Registration successful']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to register user']);
    }
}
?>