<?php
require_once '../../conn/conn.php';

date_default_timezone_set('Asia/Manila');

$data = json_decode(file_get_contents('php://input'), true);

$id = $data['id'];
$name = $data['name'];
$type = $data['type'];
$size = $data['size'];
$price = $data['price'];
$description = $data['description'];
$status = $data['status'];
$updated_at = date('Y-m-d H:i:s');

$conn->begin_transaction();

try {
    // Update product details
    $query = "UPDATE products SET name = ?, type = ?, size = ?, price = ?, description = ?, status = ?, updated_at = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('sssssssi', $name, $type, $size, $price, $description, $status, $updated_at, $id);
    $stmt->execute();
    $stmt->close();

    // Delete all existing product availability records for this product
    $query = "DELETE FROM product_availability WHERE product_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();

    // Get the business_id of the product
    $query = "SELECT business_id FROM products WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->bind_result($business_id);
    $stmt->fetch();
    $stmt->close();

    if ($business_id) {
        // Insert new product availability for the business
        $query = "INSERT INTO product_availability (product_id, business_id, status, created_at) 
                  VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('iiss', $id, $business_id, $status, $updated_at);
        $stmt->execute();
        $stmt->close();

        // Find all branches under the same business
        $query = "SELECT id FROM branch WHERE business_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $business_id);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $branch_id = $row['id'];

            // Insert new product availability for each branch
            $query = "INSERT INTO product_availability (product_id, business_id, branch_id, status, created_at) 
                      VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('iiiss', $id, $business_id, $branch_id, $status, $updated_at);
            $stmt->execute();
        }
        $stmt->close();
    }

    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>