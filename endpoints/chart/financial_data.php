<?php

require '../../conn/conn.php';

$owner_id = $_SESSION['user_id'] ?? 0;
$business = $_GET['business'] ?? 'all';
$branch = $_GET['branch'] ?? 'all';

// Fetch Business IDs based on selection
$businessIDs = [];
if ($business === 'all') {
    $stmt = $conn->prepare("SELECT id FROM business WHERE owner_id = ?");
    $stmt->bind_param("i", $owner_id);
} else {
    $stmt = $conn->prepare("SELECT id FROM business WHERE owner_id = ? AND name = ?");
    $stmt->bind_param("is", $owner_id, $business);
}
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $businessIDs[] = $row['id'];
}

if (empty($businessIDs)) {
    echo json_encode(['sales' => array_fill(0, 12, 0), 'expenses' => array_fill(0, 12, 0)]);
    exit;
}

// Fetch Branch IDs based on selection
$branchIDs = [];
$isSpecificBranch = ($branch !== 'all');
if (!$isSpecificBranch) {
    // Fetch all branches under selected businesses if branch is 'all'
    $stmt = $conn->prepare("SELECT id FROM branch WHERE business_id IN (" . implode(',', $businessIDs) . ")");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $branchIDs[] = $row['id'];
    }
} else {
    // Validate specific branch belongs to selected business
    $branch = intval($branch);
    $stmt = $conn->prepare("SELECT id FROM branch WHERE id = ? AND business_id IN (" . implode(',', $businessIDs) . ")");
    $stmt->bind_param("i", $branch);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $branchIDs[] = $row['id'];
    }
}

// Prepare sales data
$salesData = array_fill(0, 12, 0);
if ($branch === 'all') {
    // Include both business and branch sales
    $salesQuery = "SELECT MONTH(date) AS month, SUM(CAST(total_sales AS DECIMAL(10,2))) AS total 
                   FROM sales 
                   WHERE owner_id = ? 
                   AND (
                       (type = 'business' AND product_id IN (
                           SELECT id FROM products WHERE business_id IN (" . implode(',', $businessIDs) . ")
                       ))
                       " . (empty($branchIDs) ? "" : "OR (type = 'branch' AND branch_id IN (" . implode(',', $branchIDs) . "))") . "
                   )
                   GROUP BY MONTH(date)";
    $stmt = $conn->prepare($salesQuery);
    $stmt->bind_param("i", $owner_id);
} else {
    // Only include specific branch sales
    $salesQuery = "SELECT MONTH(date) AS month, SUM(CAST(total_sales AS DECIMAL(10,2))) AS total 
                   FROM sales 
                   WHERE owner_id = ? 
                   AND type = 'branch' 
                   AND branch_id = ?
                   GROUP BY MONTH(date)";
    $stmt = $conn->prepare($salesQuery);
    $stmt->bind_param("ii", $owner_id, $branch);
}
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $month = intval($row['month']) - 1;
    $salesData[$month] = floatval($row['total']);
}

// Prepare expenses data
$expensesData = array_fill(0, 12, 0);
if ($branch === 'all') {
    // Include both business and branch expenses
    $expensesQuery = "SELECT MONTH(created_at) AS month, SUM(CAST(amount AS DECIMAL(10,2))) AS total 
                      FROM expenses 
                      WHERE owner_id = ? 
                      AND (
                          (category = 'business' AND category_id IN (" . implode(',', $businessIDs) . "))
                          " . (empty($branchIDs) ? "" : "OR (category = 'branch' AND category_id IN (" . implode(',', $branchIDs) . "))") . "
                      )
                      GROUP BY MONTH(created_at)";
    $stmt = $conn->prepare($expensesQuery);
    $stmt->bind_param("i", $owner_id);
} else {
    // Only include specific branch expenses
    $expensesQuery = "SELECT MONTH(created_at) AS month, SUM(CAST(amount AS DECIMAL(10,2))) AS total 
                      FROM expenses 
                      WHERE owner_id = ? 
                      AND category = 'branch' 
                      AND category_id = ?
                      GROUP BY MONTH(created_at)";
    $stmt = $conn->prepare($expensesQuery);
    $stmt->bind_param("ii", $owner_id, $branch);
}
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $month = intval($row['month']) - 1;
    $expensesData[$month] = floatval($row['total']);
}

echo json_encode([
    'sales' => $salesData,
    'expenses' => $expensesData
]);
?>