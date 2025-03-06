<?php
require_once '../../conn/conn.php';

$data = json_decode(file_get_contents("php://input"), true);
$business_id = $data['business_id'];
$branch_id = $data['branch_id'];

$query = "
    SELECT 
        p.id, 
        p.name, 
        p.price, 
        p.size, 
        COALESCE(
            (SELECT pa.status 
             FROM product_availability pa 
             WHERE pa.product_id = p.id 
               AND pa.business_id = p.business_id
               AND pa.branch_id = ?
             LIMIT 1),  -- Prioritize branch-level availability
            (SELECT pa.status 
             FROM product_availability pa 
             WHERE pa.product_id = p.id 
               AND pa.business_id = p.business_id
               AND pa.branch_id IS NULL
             LIMIT 1),  -- Fallback to business-wide availability
            'Available'  -- Default to 'Available' if no record exists
        ) AS status
    FROM products p
    WHERE p.business_id = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $branch_id, $business_id);
$stmt->execute();

$result = $stmt->get_result();
$products = [];

while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

$stmt->close();

echo json_encode([
    'success' => true,
    'products' => $products
]);