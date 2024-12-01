<?php
session_start();
require_once '../../conn/auth.php';
require_once '../../conn/conn.php';
validateSession('owner');

header('Content-Type: application/json');

// Set timezone for PHP and MySQL
date_default_timezone_set('Asia/Manila');
$conn->query("SET time_zone = '+08:00'");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    // Validate input data
    if (!isset($data['product_id'], $data['quantity'], $data['total_sales'], $data['sale_date'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid input data.']);
        exit;
    }

    $product_id = filter_var($data['product_id'], FILTER_VALIDATE_INT);
    $quantity = filter_var($data['quantity'], FILTER_VALIDATE_INT);
    $total_sales = filter_var($data['total_sales'], FILTER_VALIDATE_FLOAT);
    $sale_date = filter_var($data['sale_date'], FILTER_SANITIZE_STRING);

    // Format the date properly
    $sale_date = date('Y-m-d', strtotime($sale_date));

    if ($product_id === false || $quantity === false || $total_sales === false || empty($sale_date)) {
        echo json_encode(['success' => false, 'message' => 'Invalid input values.']);
        exit;
    }

    try {
        $query = "INSERT INTO sales (quantity, total_sales, date, product_id, created_at) VALUES (?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($query);

        if (!$stmt) {
            throw new Exception("Failed to prepare statement: " . $conn->error);
        }

        $stmt->bind_param("idss", $quantity, $total_sales, $sale_date, $product_id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Sales added successfully.']);
        } else {
            throw new Exception("Failed to execute statement: " . $stmt->error);
        }

        $stmt->close();
    } catch (Exception $e) {
        error_log("Error adding sale: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>