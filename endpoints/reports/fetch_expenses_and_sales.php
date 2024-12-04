<?php
require_once '../../conn/conn.php'; // Ensure this establishes a MySQLi connection as $conn

if (isset($_GET['business_id'])) {
    $businessId = intval($_GET['business_id']);

    // Fetch business details
    $businessQuery = $conn->prepare("SELECT * FROM business WHERE id = ?");
    $businessQuery->bind_param("i", $businessId);
    $businessQuery->execute();
    $businessResult = $businessQuery->get_result();
    $business = $businessResult->fetch_assoc();

    if ($business) {
        // Fetch branches
        $branchQuery = $conn->prepare("SELECT * FROM branch WHERE business_id = ?");
        $branchQuery->bind_param("i", $businessId);
        $branchQuery->execute();
        $branchResult = $branchQuery->get_result();

        $branches = [];
        $currentMonth = date('Y-m'); // Get current month in 'YYYY-MM' format

        while ($branch = $branchResult->fetch_assoc()) {
            $branchId = $branch['id'];

            // Calculate total sales for the branch in the current month
            $salesQuery = $conn->prepare("
                SELECT SUM(total_sales) AS monthly_sales 
                FROM sales 
                WHERE branch_id = ? AND DATE_FORMAT(date, '%Y-%m') = ?
            ");
            $salesQuery->bind_param("is", $branchId, $currentMonth);
            $salesQuery->execute();
            $salesResult = $salesQuery->get_result();
            $salesData = $salesResult->fetch_assoc();
            $branch['sales'] = $salesData['monthly_sales'] ?? 0;

            $expensesQuery = $conn->prepare("
                SELECT SUM(amount) AS total_expenses 
                FROM expenses 
                WHERE category_id = ? AND category = 'branch'
            ");
            $expensesQuery->bind_param("i", $branchId);
            $expensesQuery->execute();
            $expensesResult = $expensesQuery->get_result();
            $expensesData = $expensesResult->fetch_assoc();
            $branch['expenses'] = $expensesData['total_expenses'] ?? 0;

            $branches[] = $branch;
        }

        // Calculate total sales for the business (without branch ID) in the current month
        $businessSalesQuery = $conn->prepare("
            SELECT SUM(total_sales) AS business_sales 
            FROM sales 
            WHERE branch_id IS NULL AND product_id IN (
                SELECT id FROM products WHERE business_id = ?
            ) AND DATE_FORMAT(date, '%Y-%m') = ?
        ");
        $businessSalesQuery->bind_param("is", $businessId, $currentMonth);
        $businessSalesQuery->execute();
        $businessSalesResult = $businessSalesQuery->get_result();
        $businessSalesData = $businessSalesResult->fetch_assoc();
        $business['total_sales'] = $businessSalesData['business_sales'] ?? 0;

        // Calculate total expenses for the business in the current month
        $businessExpensesQuery = $conn->prepare("
            SELECT SUM(amount) AS business_expenses 
            FROM expenses 
            WHERE category_id = ? AND category = 'business' AND DATE_FORMAT(created_at, '%Y-%m') = ?
        ");
        $businessExpensesQuery->bind_param("is", $businessId, $currentMonth);
        $businessExpensesQuery->execute();
        $businessExpensesResult = $businessExpensesQuery->get_result();
        $businessExpensesData = $businessExpensesResult->fetch_assoc();
        $business['total_expenses'] = $businessExpensesData['business_expenses'] ?? 0;

        echo json_encode([
            'business' => $business,
            'branches' => $branches
        ]);
    } else {
        echo json_encode(['error' => 'Business not found']);
    }
} else {
    echo json_encode(['error' => 'Business ID is required']);
}
?>