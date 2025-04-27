<?php
session_start();
require_once '../../conn/auth.php';
require_once '../../conn/conn.php';

validateSession('owner');

$owner_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
$selectedBusiness = $_GET['business'] ?? 'all';
$selectedBranch = $_GET['branch'] ?? 'all';

// Validate business/branch access
$businessIds = [];
if ($selectedBusiness !== 'all') {
    $stmt = $conn->prepare("SELECT id FROM business WHERE id = ? AND owner_id = ?");
    $stmt->bind_param("ii", $selectedBusiness, $owner_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0)
        die(json_encode([]));
    $businessIds[] = $selectedBusiness;
} else {
    $stmt = $conn->prepare("SELECT id FROM business WHERE owner_id = ?");
    $stmt->bind_param("i", $owner_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc())
        $businessIds[] = $row['id'];
}

if (empty($businessIds))
    die(json_encode([]));

// Build query conditions
$conditions = ["b.owner_id = ?"];
$params = [$owner_id];
$types = "i";

if ($selectedBusiness !== 'all') {
    $conditions[] = "p.business_id = ?";
    $params[] = $selectedBusiness;
    $types .= "i";
}

if ($selectedBranch !== 'all') {
    $conditions[] = "s.branch_id = ?";
    $params[] = $selectedBranch;
    $types .= "i";
} else {
    $conditions[] = "(s.branch_id = 0 OR s.branch_id IS NOT NULL)";
}

$sql = "SELECT
    CASE 
        WHEN s.branch_id = 0 THEN CONCAT(b.name, ' - Main Branch')
        ELSE CONCAT(b.name, ' - ', br.location)
    END AS location,
    p.name AS product_name,
    COUNT(*) AS purchase_count,
    SUM(CAST(s.total_sales AS DECIMAL(10,2))) AS total_revenue
FROM sales s
JOIN products p ON s.product_id = p.id
JOIN business b ON p.business_id = b.id
LEFT JOIN branch br ON s.branch_id = br.id
WHERE " . implode(' AND ', $conditions) . "
GROUP BY location, p.name
ORDER BY total_revenue DESC
LIMIT 10";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = [
        'location' => $row['location'],
        'product_name' => $row['product_name'],
        'purchase_count' => (int) $row['purchase_count'],
        'total_revenue' => (float) $row['total_revenue']
    ];
}

header('Content-Type: application/json');
echo json_encode($data);
?>