<?php
header('Content-Type: application/json');
require_once '../../conn/conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    // Allow both single product or array of products
    $products = isset($data['products']) ? $data['products'] : [$data];

    $addedCount = 0;

    $conn->begin_transaction();

    try {
        foreach ($products as $product) {
            if (!isset($product['business_id'], $product['name'], $product['type'], $product['size'], $product['price'], $product['description'])) {
                throw new Exception("Missing fields in product entry.");
            }

            $businessId = intval($product['business_id']);
            $name = $product['name'];
            $type = $product['type'];
            $size = $product['size'];
            $price = floatval($product['price']);
            $description = $product['description'];
            $status = "Available";
            $created_at = date('Y-m-d H:i:s');

            $stmt = $conn->prepare("INSERT INTO products (business_id, name, type, size, price, description, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssss", $businessId, $name, $type, $size, $price, $description, $created_at);
            $stmt->execute();
            $productId = $stmt->insert_id;
            $stmt->close();

            if ($productId) {
                $stmt = $conn->prepare("INSERT INTO product_availability (product_id, business_id, status, created_at) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iiss", $productId, $businessId, $status, $created_at);
                $stmt->execute();
                $stmt->close();

                $stmt = $conn->prepare("SELECT id FROM branch WHERE business_id = ?");
                $stmt->bind_param("i", $businessId);
                $stmt->execute();
                $result = $stmt->get_result();

                while ($row = $result->fetch_assoc()) {
                    $branchId = $row['id'];
                    $stmtBranch = $conn->prepare("INSERT INTO product_availability (product_id, business_id, branch_id, status, created_at) VALUES (?, ?, ?, ?, ?)");
                    $stmtBranch->bind_param("iiiss", $productId, $businessId, $branchId, $status, $created_at);
                    $stmtBranch->execute();
                    $stmtBranch->close();
                }

                $addedCount++;
            }
        }

        $conn->commit();
        echo json_encode(['success' => true, 'count' => $addedCount]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}

$conn->close();
?>