<?php
session_start();
require_once '../../conn/conn.php';
require_once '../../conn/auth.php';

validateSession('owner');

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Read and decode the JSON input
    $input = json_decode(file_get_contents('php://input'), true);

    // Validate input
    if (!isset($input['field']) || $input['field'] !== 'full_name' || !isset($input['value'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid request parameters.']);
        exit;
    }

    $owner_id = $_SESSION['user_id'];
    $full_name = trim($input['value']);

    // Split the full name into components
    $name_parts = explode(' ', $full_name, 3);
    $first_name = $name_parts[0] ?? '';
    $middle_name = $name_parts[1] ?? '';
    $last_name = $name_parts[2] ?? '';

    // Ensure first and last names are provided
    if (empty($first_name) || empty($last_name)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'First and last names are required.']);
        exit;
    }

    // Update query
    $query = "UPDATE owner SET first_name = ?, middle_name = ?, last_name = ? WHERE id = ?";
    $stmt = $conn->prepare($query);

    if ($stmt) {
        $stmt->bind_param('sssi', $first_name, $middle_name, $last_name, $owner_id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Full name updated successfully.']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error: Unable to update name.']);
        }

        $stmt->close();
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: Failed to prepare statement.']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
}

?>