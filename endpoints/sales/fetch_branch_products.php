<?php
header('Content-Type: application/json');
include '../../conn/conn.php';

$data = json_decode(file_get_contents("php://input"), true);
$businessId = $data['business_id'];
$branchId = $data['branch_id'];

$response = [];

if ($businessId && $branchId) {
    $query = "
        SELECT p.id, p.name, p.description, p.price, p.type 
        FROM products p
        LEFT JOIN product_availability pa ON p.id = pa.product_id 
        WHERE p.business_id = ? 
        AND (pa.branch_id IS NULL OR pa.branch_id = ?)
        AND (pa.status IS NULL OR pa.status = 'Available')
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $businessId, $branchId);
    $stmt->execute();
    $result = $stmt->get_result();

    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }

    $response['success'] = true;
    $response['products'] = $products;
} else {
    $response['success'] = false;
    $response['message'] = 'Invalid input data';
}

echo json_encode($response);
$stmt->close();
$conn->close();
?>