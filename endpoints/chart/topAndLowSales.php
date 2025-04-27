<?php
session_start();
require_once '../../conn/auth.php';
require_once '../../conn/conn.php';

validateSession('owner');

$owner_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
if (isset($_GET['id']) && intval($_GET['id']) > 0) {
    $owner_id = intval($_GET['id']);
}

// Get selected business and branch from GET parameters
$selectedBusiness = isset($_GET['business']) && $_GET['business'] !== 'all' ? intval($_GET['business']) : 'all';
$selectedBranch = isset($_GET['branch']) && $_GET['branch'] !== 'all' ? intval($_GET['branch']) : 'all';

// Validate selected business and branch
$businessIds = [];
if ($selectedBusiness !== 'all') {
    // Check if the selected business belongs to the owner
    $checkBusiness = "SELECT id FROM business WHERE id = ? AND owner_id = ?";
    $stmt = $conn->prepare($checkBusiness);
    $stmt->bind_param("ii", $selectedBusiness, $owner_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        die(json_encode(["error" => "Unauthorized access to business."]));
    }
    $businessIds[] = $selectedBusiness;
} else {
    // Get all businesses of the owner
    $businessQuery = "SELECT id FROM business WHERE owner_id = ?";
    $stmt = $conn->prepare($businessQuery);
    $stmt->bind_param("i", $owner_id);
    $stmt->execute();
    $businessResult = $stmt->get_result();
    while ($row = $businessResult->fetch_assoc()) {
        $businessIds[] = intval($row['id']);
    }
}

if (empty($businessIds)) {
    die(json_encode(["error" => "No businesses found for this owner."]));
}

// Determine branch IDs based on selection
$branchIds = [];
if ($selectedBranch !== 'all') {
    // Validate selected branch belongs to the selected business(es)
    $checkBranch = "SELECT id FROM branch WHERE id = ? AND business_id IN (" . implode(',', $businessIds) . ")";
    $stmt = $conn->prepare($checkBranch);
    $stmt->bind_param("i", $selectedBranch);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        die(json_encode(["error" => "Unauthorized access to branch."]));
    }
    $branchIds[] = $selectedBranch;
} else {
    // Get branches under the selected business(es)
    $branchQuery = "SELECT id FROM branch WHERE business_id IN (" . implode(',', $businessIds) . ")";
    $branchResult = $conn->query($branchQuery);
    while ($row = $branchResult->fetch_assoc()) {
        $branchIds[] = intval($row['id']);
    }
}

// Build the sales query with dynamic conditions
$whereClauses = ["p.business_id IN (" . implode(',', $businessIds) . ")"];
$params = [];
$types = '';

if ($selectedBranch !== 'all') {
    $whereClauses[] = "s.type = 'branch'";
    $whereClauses[] = "s.branch_id = ?";
    $params[] = $selectedBranch;
    $types .= 'i';
} else {
    $conditions = ["(s.type = 'business' AND s.branch_id = 0)"];
    if (!empty($branchIds)) {
        $placeholders = implode(',', array_fill(0, count($branchIds), '?'));
        $conditions[] = "(s.type = 'branch' AND s.branch_id IN ($placeholders))";
        $params = array_merge($params, $branchIds);
        $types .= str_repeat('i', count($branchIds));
    }
    $whereClauses[] = "(" . implode(' OR ', $conditions) . ")";
}

$salesQuery = "
    SELECT 
        p.name AS product_name,
        SUM(s.total_sales) AS total_sales
    FROM sales s
    JOIN products p ON s.product_id = p.id
    WHERE " . implode(' AND ', $whereClauses) . "
    GROUP BY s.product_id
    ORDER BY total_sales DESC
";

// Execute the query with parameters
$stmt = $conn->prepare($salesQuery);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$salesResult = $stmt->get_result();

$products = [];
while ($row = $salesResult->fetch_assoc()) {
    $products[] = [
        'name' => $row['product_name'],
        'total_sales' => (float) $row['total_sales']
    ];
}

// Split into top and low performers
$topSellers = array_slice($products, 0, 5);
$lowPerformers = array_slice(array_reverse($products), 0, 5);

echo json_encode([
    'topSelling' => $topSellers,
    'lowSelling' => $lowPerformers
]);
?>