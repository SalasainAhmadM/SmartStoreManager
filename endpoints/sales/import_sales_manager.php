<?php
require_once '../../conn/conn.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode($_POST['data'], true);

    if (!$data) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid data format.']);
        exit;
    }

    $manager_id = $_SESSION['user_id'];

    // Fetch business or branch details based on manager_id
    $sqlBusiness = "SELECT * FROM business WHERE manager_id = ?";
    $stmt = $conn->prepare($sqlBusiness);
    $stmt->bind_param("i", $manager_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Manager is assigned to a business
        $business = $result->fetch_assoc();
        $business_id = $business['id'];
        $branch_id = 0; // Default for business
        $type = 'business';
    } else {
        // Manager is assigned to a branch
        $sqlBranch = "SELECT * FROM branch WHERE manager_id = ?";
        $stmt = $conn->prepare($sqlBranch);
        $stmt->bind_param("i", $manager_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $branch = $result->fetch_assoc();
            $branch_id = $branch['id'];
            $type = 'branch';

            // Fetch business ID from branch
            $sqlBusinessFromBranch = "SELECT * FROM business WHERE id = ?";
            $stmt = $conn->prepare($sqlBusinessFromBranch);
            $stmt->bind_param("i", $branch['business_id']);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $business = $result->fetch_assoc();
                $business_id = $business['id'];
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Business not found for the branch.']);
                exit;
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Manager is not assigned to any business or branch.']);
            exit;
        }
    }

    // Insert sales data
    $user_role = 'Manager';
    $errors = [];

    foreach ($data['salesData'] as $sale) {
        $product = $sale['product'];
        $quantity = $sale['amount_sold'];
        $total_sales = $sale['total_sales'];
        $date = $sale['date'];

        // Extract product ID from product name (e.g., "ID:4 - Hoodie")
        preg_match('/ID:(\d+)/', $product, $productMatches);
        $product_id = $productMatches[1] ?? null;

        // Extract branch ID from branch name (e.g., "ID:13 - WMSU")
        preg_match('/ID:(\d+)/', $data['businessInfo']['branch'], $branchMatches);
        $branch_id = $branchMatches[1] ?? $branch_id;

        if ($product_id) {
            // Insert sales record
            $stmt = $conn->prepare("
                INSERT INTO `sales` (quantity, total_sales, date, product_id, branch_id, user_role, type) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param('sssiiss', $quantity, $total_sales, $date, $product_id, $branch_id, $user_role, $type);

            if (!$stmt->execute()) {
                $errors[] = "Failed to insert sales record for product: $product";
            }
        } else {
            $errors[] = "Product ID not found in product name: $product";
        }
    }

    if (empty($errors)) {
        // Redirect to index.php with a success parameter
        header("Location: ../../manager/index.php?imported=true");
        exit; // Ensure no further code is executed after the redirect
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Some records could not be imported.', 'errors' => $errors]);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>