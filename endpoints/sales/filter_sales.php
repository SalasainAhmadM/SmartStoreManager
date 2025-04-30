<?php
session_start();
require_once '../../conn/auth.php';
require_once '../../conn/conn.php';
validateSession('owner');

header('Content-Type: application/json');

$owner_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

$business = $data['business'] ?? 'all';
$period = $data['period'] ?? 'all';

// Determine business/branch filter
$businessFilter = '';
$params = [$owner_id];
$types = 'i';

if ($business !== 'all') {
    list($type, $id) = explode('_', $business);
    if ($type === 'business') {
        $businessFilter = " AND b.id = ?";
        $params[] = $id;
        $types .= 'i';
    } else if ($type === 'branch') {
        $businessFilter = " AND br.id = ?";
        $params[] = $id;
        $types .= 'i';
    }
}

// Determine date range
$dateFilter = '';
$today = new DateTime('now', new DateTimeZone('Asia/Manila'));
switch ($period) {
    case 'day':
        $dateToUse = isset($data['date']) ? $data['date'] : $today->format('Y-m-d');
        $dateFilter = " AND s.date = ?";
        $params[] = $dateToUse;
        $types .= 's';
        break;
    case 'week':
        $start = (clone $today)->modify('Monday this week')->format('Y-m-d');
        $end = (clone $today)->modify('Sunday this week')->format('Y-m-d');
        $dateFilter = " AND s.date BETWEEN ? AND ?";
        array_push($params, $start, $end);
        $types .= 'ss';
        break;
    case 'month':
        $start = (clone $today)->modify('first day of this month')->format('Y-m-d');
        $end = (clone $today)->modify('last day of this month')->format('Y-m-d');
        $dateFilter = " AND s.date BETWEEN ? AND ?";
        array_push($params, $start, $end);
        $types .= 'ss';
        break;
    case 'all':
        // No date filter
        break;
}


$query = "
    SELECT 
        p.name AS product_name,
        s.quantity,
        s.total_sales,
        CASE 
            WHEN s.type = 'branch' THEN br.location
            ELSE b.name
        END AS business_or_branch_name,
        s.date
    FROM sales s
    JOIN products p ON s.product_id = p.id
    JOIN business b ON p.business_id = b.id
    LEFT JOIN branch br ON s.branch_id = br.id
    WHERE b.owner_id = ? 
    $businessFilter 
    $dateFilter
    ORDER BY s.date DESC
";

$stmt = $conn->prepare($query);
if ($types)
    $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$sales = [];
while ($row = $result->fetch_assoc()) {
    $sales[] = $row;
}

echo json_encode(['success' => true, 'sales' => $sales]);
exit;
?>