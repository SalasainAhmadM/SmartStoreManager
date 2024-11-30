<?php
require_once '../../conn/conn.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$businessId = $data['business_id'] ?? null;
$managerId = $data['manager_id'] ?? null;

if ($businessId && $managerId) {
    // Check if the manager is already assigned to this business
    $checkQuery = "SELECT manager_id FROM business WHERE id = ?";
    $stmt = $conn->prepare($checkQuery);

    if ($stmt) {
        $stmt->bind_param("i", $businessId);
        $stmt->execute();
        $stmt->bind_result($existingManagerId);
        $stmt->fetch();
        $stmt->close();

        if ($existingManagerId == $managerId) {
            echo json_encode([
                'success' => false,
                'message' => 'This manager is already assigned to the business.'
            ]);
            exit;
        } elseif ($existingManagerId && $existingManagerId != $managerId) {
            echo json_encode([
                'success' => false,
                'message' => 'A manager is already assigned to this business.'
            ]);
            exit;
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to check current assignment.'
        ]);
        exit;
    }

    // Check if the manager is already assigned to another business
    $checkOtherQuery = "SELECT id FROM business WHERE manager_id = ?";
    $stmt = $conn->prepare($checkOtherQuery);

    if ($stmt) {
        $stmt->bind_param("i", $managerId);
        $stmt->execute();
        $stmt->bind_result($otherBusinessId);
        $stmt->fetch();
        $stmt->close();

        if ($otherBusinessId && $otherBusinessId != $businessId) {
            echo json_encode([
                'success' => false,
                'message' => 'This manager is already assigned to another business.'
            ]);
            exit;
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to check if manager is assigned to another business.'
        ]);
        exit;
    }

    // Proceed with the assignment
    $query = "UPDATE business SET manager_id = ? WHERE id = ?";
    $stmt = $conn->prepare($query);

    if ($stmt) {
        $stmt->bind_param("ii", $managerId, $businessId);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Manager assigned to business successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to assign manager to business.']);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare statement.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid input data.']);
}

$conn->close();
?>