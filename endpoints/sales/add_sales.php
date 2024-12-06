<?php
require_once '../../conn/auth.php';
require_once '../../conn/conn.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $product_id = filter_var($data['product_id'], FILTER_VALIDATE_INT);
    $quantity = filter_var($data['quantity'], FILTER_VALIDATE_INT);
    $total_sales = filter_var($data['total_sales'], FILTER_VALIDATE_FLOAT);
    $sale_date = filter_var($data['sale_date'], FILTER_SANITIZE_STRING);

    $branch_id = isset($data['branch_id']) && filter_var($data['branch_id'], FILTER_VALIDATE_INT) !== false
        ? $data['branch_id']
        : 0;

    if (!$product_id || !$quantity || !$total_sales || !$sale_date) {
        echo json_encode(['success' => false, 'message' => 'Invalid input data.']);
        exit;
    }

    // Validate user_id from session (if available)
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
    } else {
        echo json_encode(['success' => false, 'message' => 'User not authenticated']);
        exit;
    }

    try {
        // Fetch product name
        $productQuery = "SELECT name FROM products WHERE id = ?";
        $productStmt = $conn->prepare($productQuery);
        $productStmt->bind_param("i", $product_id);
        $productStmt->execute();
        $productResult = $productStmt->get_result();

        if ($productResult->num_rows > 0) {
            $productRow = $productResult->fetch_assoc();
            $productName = $productRow['name'];
        } else {
            echo json_encode(['success' => false, 'message' => 'Product not found']);
            exit;
        }

        // Determine if it's a branch or a business and fetch details
        $type = $branch_id > 0 ? 'branch' : 'business'; // Determine the type value
        if ($type === 'branch') {
            $branchQuery = "SELECT b.location, biz.name AS business_name 
                            FROM branch b 
                            JOIN business biz ON b.business_id = biz.id 
                            WHERE b.id = ?";
            $branchStmt = $conn->prepare($branchQuery);
            $branchStmt->bind_param("i", $branch_id);
            $branchStmt->execute();
            $branchResult = $branchStmt->get_result();

            if ($branchResult->num_rows > 0) {
                $branchRow = $branchResult->fetch_assoc();
                $branchName = $branchRow['location'];
                $businessName = $branchRow['business_name'];
                $activityMessage = "Sale Added at Branch: $branchName (Business: $businessName) - Product: $productName, Quantity: $quantity, Total Sales: $total_sales";
            } else {
                echo json_encode(['success' => false, 'message' => 'Branch not found']);
                exit;
            }
        } else {
            $businessQuery = "SELECT name FROM business WHERE id = ?";
            $businessStmt = $conn->prepare($businessQuery);
            $businessStmt->bind_param("i", $data['business_id']);
            $businessStmt->execute();
            $businessResult = $businessStmt->get_result();

            if ($businessResult->num_rows > 0) {
                $businessRow = $businessResult->fetch_assoc();
                $businessName = $businessRow['name'];
                $activityMessage = "Sale Added at Business: $businessName - Product: $productName, Quantity: $quantity, Total Sales: $total_sales";
            } else {
                echo json_encode(['success' => false, 'message' => 'Business not found']);
                exit;
            }
        }

        // Insert sale data into sales table
        $query = "INSERT INTO sales (product_id, quantity, total_sales, date, branch_id, created_at, type) 
                  VALUES (?, ?, ?, ?, ?, NOW(), ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iidsis", $product_id, $quantity, $total_sales, $sale_date, $branch_id, $type);

        if ($stmt->execute()) {
            // Log the activity
            $activityQuery = "INSERT INTO activity (message, created_at, status, user, user_id) 
                              VALUES (?, NOW(), 'Completed', 'owner', ?)";
            $activityStmt = $conn->prepare($activityQuery);
            $activityStmt->bind_param("si", $activityMessage, $user_id);
            $activityStmt->execute();

            echo json_encode(['success' => true, 'message' => 'Sales added successfully.']);
        } else {
            throw new Exception($stmt->error);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to add sale.', 'error' => $e->getMessage()]);
    }
}
?>