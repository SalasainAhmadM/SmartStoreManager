<?php
include "../../conn/conn.php";

$data = json_decode(file_get_contents("php://input"), true);
$business_id = $data['business_id'];
$branch_id = isset($data['branch_id']) ? $data['branch_id'] : null;

$query = "
    SELECT p.id, p.name, p.size, p.price,
        COALESCE(
            (SELECT pa.status 
             FROM product_availability pa 
             WHERE pa.product_id = p.id 
               AND pa.business_id = p.business_id
               AND pa.branch_id = ?
             LIMIT 1), 
            (SELECT pa.status 
             FROM product_availability pa 
             WHERE pa.product_id = p.id 
               AND pa.business_id = p.business_id
               AND pa.branch_id IS NULL
             LIMIT 1),  
            'Available'  
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
    if ($row['status'] !== 'Unavailable') {
        $products[] = $row;
    }
}

echo json_encode(["success" => true, "products" => $products]);
$stmt->close();
$conn->close();
?>