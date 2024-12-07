<?php
require_once '../../conn/conn.php';

header('Content-Type: application/json');
date_default_timezone_set('Asia/Manila');
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['category'], $data['category_id'], $data['expense_type'], $data['amount'], $data['description'], $data['month'], $data['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$category = $data['category'];
$category_id = $data['category_id'];
$expense_type = $data['expense_type'];
$amount = $data['amount'];
$description = $data['description'];
$month = $data['month'];
$owner_id = $data['user_id'];
$user = 'Owner';

$query = "INSERT INTO expenses (category, category_id, expense_type, amount, description, month, owner_id, user_role) 
          VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($query);
$stmt->bind_param("sisssiis", $category, $category_id, $expense_type, $amount, $description, $month, $owner_id, $user);

try {
    if ($stmt->execute()) {
        $businessQuery = "SELECT biz.name AS business_name 
                          FROM branch b 
                          JOIN business biz ON b.business_id = biz.id 
                          WHERE b.id = ?";
        $businessStmt = $conn->prepare($businessQuery);
        $businessStmt->bind_param("i", $category_id);
        $businessStmt->execute();
        $businessResult = $businessStmt->get_result();

        if ($businessResult->num_rows > 0) {
            $businessRow = $businessResult->fetch_assoc();
            $businessName = $businessRow['business_name'];
        } else {
            echo json_encode(['success' => true, 'message' => 'Expense added successfully']);
            // echo json_encode(['success' => false, 'message' => 'Business not found']);
            exit;
        }

        $currentDateTime = date('Y-m-d H:i:s');
        $activityQuery = "INSERT INTO activity (message, created_at, status, user, user_id) 
                          VALUES (?, ?, 'Completed', 'owner', ?)";
        $activityMessage = "Expense Added to Business: $businessName for $expense_type amounting to $amount";
        $activityStmt = $conn->prepare($activityQuery);
        $activityStmt->bind_param("ssi", $activityMessage, $currentDateTime, $owner_id);
        $activityStmt->execute();

        echo json_encode(['success' => true, 'message' => 'Expense added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add expense']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$stmt->close();
$conn->close();
?>