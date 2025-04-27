<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Adjust CORS as needed
require_once '../conn/conn.php';

session_start();
$owner_id = $_SESSION['user_id']; // Adjust based on your session structure

// Get filter parameters
$selectedBusiness = isset($_GET['business']) ? $_GET['business'] : 'all';
$selectedBranch = isset($_GET['branch']) ? $_GET['branch'] : 'all';

// Build filter condition (same logic as original)
$filterCondition = "";
if ($selectedBranch !== 'all') {
    $filterCondition = " AND e.category = 'branch' AND e.category_id = " . intval($selectedBranch);
} elseif ($selectedBusiness !== 'all') {
    $filterCondition = " AND e.category = 'business' AND e.category_id = " . intval($selectedBusiness);
}

// Fetch Category-Wise Expenses
$categoryQuery = "
SELECT 
    CASE 
        WHEN e.category = 'business' THEN b.name
        WHEN e.category = 'branch' THEN br.location
    END AS category,
    SUM(e.amount) AS total_amount
FROM expenses e
LEFT JOIN business b ON e.category_id = b.id AND e.category = 'business'
LEFT JOIN branch br ON e.category_id = br.id AND e.category = 'branch'
WHERE e.owner_id = $owner_id
$filterCondition
GROUP BY e.category, e.category_id
";

$categoryResult = $conn->query($categoryQuery);
$categoryData = [];
while ($row = $categoryResult->fetch_assoc()) {
    $categoryData[$row['category']] = '₱' . number_format($row['total_amount'], 2);
}

// Fetch Recurring vs One-Time Expenses
$recurringQuery = "
SELECT 
    e.month,
    e.expense_type,
    CASE 
        WHEN e.category = 'business' THEN b.name
        WHEN e.category = 'branch' THEN br.location
        ELSE 'Other'
    END AS category_name,
    SUM(e.amount) AS total_amount
FROM expenses e
LEFT JOIN business b ON e.category_id = b.id AND e.category = 'business'
LEFT JOIN branch br ON e.category_id = br.id AND e.category = 'branch'
WHERE e.owner_id = $owner_id
$filterCondition
GROUP BY e.month, e.expense_type, e.category, e.category_id
";

$recurringResult = $conn->query($recurringQuery);
$recurringData = [];
while ($row = $recurringResult->fetch_assoc()) {
    $month = $row['month'];
    $expenseType = $row['expense_type'];
    $categoryName = $row['category_name'];
    $totalAmount = '₱' . number_format($row['total_amount'], 2);

    if (!isset($recurringData[$month])) {
        $recurringData[$month] = [];
    }
    if (!isset($recurringData[$month][$categoryName])) {
        $recurringData[$month][$categoryName] = [
            'recurring' => [],
            'oneTime' => []
        ];
    }

    if ($expenseType === 'recurring') {
        $recurringData[$month][$categoryName]['recurring'][] = $totalAmount;
    } else {
        $recurringData[$month][$categoryName]['oneTime'][] = $totalAmount;
    }
}

// Prepare and send response
$expenseDataBreakdown = [
    'categories' => $categoryData,
    'recurringByMonth' => $recurringData
];

echo json_encode($expenseDataBreakdown);

$conn->close();
?>