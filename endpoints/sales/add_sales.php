<?php
require_once '../../conn/auth.php';
require_once '../../conn/conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $product_id = filter_var($data['product_id'], FILTER_VALIDATE_INT);
    $quantity = filter_var($data['quantity'], FILTER_VALIDATE_INT);
    $total_sales = filter_var($data['total_sales'], FILTER_VALIDATE_FLOAT);
    $sale_date = filter_var($data['sale_date'], FILTER_SANITIZE_STRING);

    // If branch_id is not provided or invalid, default to 0
    $branch_id = isset($data['branch_id']) && filter_var($data['branch_id'], FILTER_VALIDATE_INT) !== false
        ? $data['branch_id']
        : 0;

    if (!$product_id || !$quantity || !$total_sales || !$sale_date) {
        echo json_encode(['success' => false, 'message' => 'Invalid input data.']);
        exit;
    }

    try {
        $query = "INSERT INTO sales (product_id, quantity, total_sales, date, branch_id, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iidsi", $product_id, $quantity, $total_sales, $sale_date, $branch_id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Sales added successfully.']);
        } else {
            throw new Exception($stmt->error);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to add sale.', 'error' => $e->getMessage()]);
    }
}
?>