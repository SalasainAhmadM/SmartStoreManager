<?php
require_once '../conn/conn.php';

header('Content-Type: application/json');

// Get the input data
$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['branch_id'], $data['manager_id'])) {
    $branch_id = $data['branch_id'];
    $manager_id = $data['manager_id'];

    // Prepare the SQL query
    $query = "UPDATE branch SET manager_id = ?, updated_at = NOW() WHERE id = ?";
    $stmt = $conn->prepare($query);

    if ($stmt) {
        // Bind parameters
        $stmt->bind_param("ii", $manager_id, $branch_id);

        // Execute the query
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Manager assigned successfully to the branch.'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to assign the manager. Please try again.'
            ]);
        }

        // Close the statement
        $stmt->close();
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to prepare the query.'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid data provided.'
    ]);
}

// Close the database connection
$conn->close();
?>