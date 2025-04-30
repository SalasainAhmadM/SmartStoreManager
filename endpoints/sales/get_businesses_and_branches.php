<?php
session_start();
require_once '../../conn/auth.php';
require_once '../../conn/conn.php';
validateSession('owner');

$owner_id = $_SESSION['user_id'];

$query = "
    SELECT 
        b.id AS business_id,
        b.name AS business_name,
        br.id AS branch_id,
        br.location AS branch_location
    FROM business b
    LEFT JOIN branch br ON b.id = br.business_id
    WHERE b.owner_id = ?
    ORDER BY b.name, br.location
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$result = $stmt->get_result();

$businesses = [];

// Group by business first
while ($row = $result->fetch_assoc()) {
    $business_id = $row['business_id'];
    if (!isset($businesses[$business_id])) {
        $businesses[$business_id] = [
            'business_id' => $row['business_id'],
            'business_name' => $row['business_name'],
            'branches' => []
        ];
    }
    if (!empty($row['branch_id'])) {
        $businesses[$business_id]['branches'][] = [
            'branch_id' => $row['branch_id'],
            'branch_location' => $row['branch_location']
        ];
    }
}

// Reformat into simple array
$output = array_values($businesses);

echo json_encode($output);
exit;
?>