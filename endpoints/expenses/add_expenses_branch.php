<?php
require_once '../../conn/conn.php';

header('Content-Type: application/json');
date_default_timezone_set('Asia/Manila');
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['branch_id'], $data['expense_type'], $data['amount'], $data['description'], $data['user_id'], $data['month'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$category = 'branch'; 
$branch_id = $data['branch_id']; 
$expense_type = $data['expense_type'];
$amount = $data['amount'];
$description = $data['description'];
$user_id = $data['user_id'];
$created_at = date('Y-m-d H:i:s'); 
$month = $data['month']; 

$branchQuery = "SELECT b.location, biz.name AS business_name 
                FROM branch b 
                JOIN business biz ON b.business_id = biz.id 
                WHERE b.id = ?";
$branchStmt = $conn->prepare($branchQuery);
$branchStmt->bind_param("i", $branch_id);
$branchStmt->execute();
$branchResult = $branchStmt->get_result();

if ($branchResult->num_rows > 0) {
    $branchRow = $branchResult->fetch_assoc();
    $branchName = $branchRow['location']; 
    $businessName = $branchRow['business_name']; 
} else {
    echo json_encode(['success' => false, 'message' => 'Branch not found']);
    exit;
}

$query = "INSERT INTO expenses (category, category_id, expense_type, amount, description, created_at, owner_id, month) 
          VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($query);
$stmt->bind_param("sissssii", $category, $branch_id, $expense_type, $amount, $description, $created_at, $user_id, $month);

try {
    if ($stmt->execute()) {
        
        $currentDateTime = date('Y-m-d H:i:s');
    
        $activityQuery = "INSERT INTO activity (message, created_at, status, user, user_id) 
                          VALUES (?, ?, 'Completed', 'owner', ?)";
        $activityMessage = "Expense Added into Branch: $branchName (Business: $businessName)";
        $activityStmt = $conn->prepare($activityQuery);
        $activityStmt->bind_param("ssi", $activityMessage, $currentDateTime, $user_id);
        $activityStmt->execute();
    
        echo json_encode(['success' => true, 'message' => 'Branch expense added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add branch expense']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$stmt->close();
$branchStmt->close();
$conn->close();
?>
