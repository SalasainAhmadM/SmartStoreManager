<?php
header('Content-Type: application/json');
require_once '../../conn/conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (isset($data['business_id'], $data['name'], $data['type'], $data['size'], $data['price'], $data['description'])) {
        $businessId = intval($data['business_id']);
        $name = $data['name'];
        $type = $data['type'];
        $size = $data['size'];
        $price = floatval($data['price']);
        $description = $data['description'];
        $status = "Available";
        $created_at = date('Y-m-d H:i:s');

        $conn->begin_transaction();

        try {
            // Insert product
            $query = "INSERT INTO products (business_id, name, type, size, price, description, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("issssss", $businessId, $name, $type, $size, $price, $description, $created_at);
            $stmt->execute();
            $productId = $stmt->insert_id; // Get last inserted product ID
            $stmt->close();

            if ($productId) {
                // Insert product availability for the business
                $query = "INSERT INTO product_availability (product_id, business_id, status, created_at) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("iiss", $productId, $businessId, $status, $created_at);
                $stmt->execute();
                $stmt->close();

                // Get all branches under this business
                $query = "SELECT id FROM branch WHERE business_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $businessId);
                $stmt->execute();
                $result = $stmt->get_result();

                while ($row = $result->fetch_assoc()) {
                    $branchId = $row['id'];

                    // Insert product availability for each branch
                    $query = "INSERT INTO product_availability (product_id, business_id, branch_id, status, created_at) VALUES (?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("iiiss", $productId, $businessId, $branchId, $status, $created_at);
                    $stmt->execute();
                }
                $stmt->close();
            }

            $conn->commit();
            echo json_encode(['success' => true, 'product_id' => $productId]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Failed to add product.', 'error' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid input.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}

$conn->close();
?>