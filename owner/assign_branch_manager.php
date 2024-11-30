<?php
require_once '../conn/conn.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['branch_id'], $data['manager_id'])) {
    $branch_id = $data['branch_id'];
    $manager_id = $data['manager_id'];

    // Check if the manager is already assigned to this branch
    $checkQuery = "SELECT manager_id FROM branch WHERE id = ?";
    $stmt = $conn->prepare($checkQuery);

    if ($stmt) {
        $stmt->bind_param("i", $branch_id);
        $stmt->execute();
        $stmt->bind_result($existingManagerId);
        $stmt->fetch();
        $stmt->close();

        // Check if there is already a manager assigned to this branch
        if ($existingManagerId) {
            if ($existingManagerId == $manager_id) {
                echo json_encode([
                    'success' => false,
                    'message' => 'This manager is already assigned to the branch.'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'A manager is already assigned to this branch.'
                ]);
            }
            exit;
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to check current assignment.'
        ]);
        exit;
    }

    // Check if the manager is already assigned to another branch
    $checkOtherQuery = "SELECT id FROM branch WHERE manager_id = ?";
    $stmt = $conn->prepare($checkOtherQuery);

    if ($stmt) {
        $stmt->bind_param("i", $manager_id);
        $stmt->execute();
        $stmt->bind_result($otherBranchId);
        $stmt->fetch();
        $stmt->close();

        if ($otherBranchId && $otherBranchId != $branch_id) {
            echo json_encode([
                'success' => false,
                'message' => 'This manager is already assigned to another branch.'
            ]);
            exit;
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to check if the manager is assigned to another branch.'
        ]);
        exit;
    }

    // Proceed with the assignment
    $query = "UPDATE branch SET manager_id = ?, updated_at = NOW() WHERE id = ?";
    $stmt = $conn->prepare($query);

    if ($stmt) {
        $stmt->bind_param("ii", $manager_id, $branch_id);

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

$conn->close();
?>