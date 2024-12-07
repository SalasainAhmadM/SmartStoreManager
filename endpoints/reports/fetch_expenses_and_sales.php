<?php
require_once '../../conn/conn.php';

if (isset($_GET['business_id'])) {
    $businessId = intval($_GET['business_id']);
    $currentMonth = date('Y-m');

    // Fetch business details
    $businessQuery = $conn->prepare("SELECT name FROM business WHERE id = ?");
    $businessQuery->bind_param("i", $businessId);
    $businessQuery->execute();
    $businessResult = $businessQuery->get_result();
    $business = $businessResult->fetch_assoc();

    if ($business) {
        // Fetch branches
        $branchQuery = $conn->prepare("SELECT id, location FROM branch WHERE business_id = ?");
        $branchQuery->bind_param("i", $businessId);
        $branchQuery->execute();
        $branchResult = $branchQuery->get_result();

        $branches = [];
        $totalBranchSales = 0;
        $totalBranchExpenses = 0;

        while ($branch = $branchResult->fetch_assoc()) {
            $branchId = $branch['id'];

            // Fetch branch sales
            $salesQuery = $conn->prepare("
                SELECT SUM(total_sales) AS monthly_sales 
                FROM sales 
                WHERE branch_id = ? AND DATE_FORMAT(date, '%Y-%m') = ?");
            $salesQuery->bind_param("is", $branchId, $currentMonth);
            $salesQuery->execute();
            $salesData = $salesQuery->get_result()->fetch_assoc();
            $branch['sales'] = $salesData['monthly_sales'] ?? 0;

            // Fetch branch expenses
            $expensesQuery = $conn->prepare("
                SELECT SUM(amount) AS total_expenses 
                FROM expenses 
                WHERE category = 'branch' AND category_id = ? AND DATE_FORMAT(created_at, '%Y-%m') = ?");
            $expensesQuery->bind_param("is", $branchId, $currentMonth);
            $expensesQuery->execute();
            $expensesData = $expensesQuery->get_result()->fetch_assoc();
            $branch['expenses'] = $expensesData['total_expenses'] ?? 0;

            $totalBranchSales += $branch['sales'];
            $totalBranchExpenses += $branch['expenses'];
            $branches[] = $branch;
        }

        // Fetch business-level sales
        $businessSalesQuery = $conn->prepare("
            SELECT SUM(total_sales) AS business_sales 
            FROM sales 
            WHERE branch_id = 0 AND product_id IN (
                SELECT id FROM products WHERE business_id = ?
            ) AND DATE_FORMAT(date, '%Y-%m') = ?");
        $businessSalesQuery->bind_param("is", $businessId, $currentMonth);
        $businessSalesQuery->execute();
        $businessSalesData = $businessSalesQuery->get_result()->fetch_assoc();
        $businessSales = $businessSalesData['business_sales'] ?? 0;

        // Fetch business-level expenses
        $businessExpensesQuery = $conn->prepare("
            SELECT SUM(amount) AS business_expenses 
            FROM expenses 
            WHERE category = 'business' AND category_id = ? AND DATE_FORMAT(created_at, '%Y-%m') = ?");
        $businessExpensesQuery->bind_param("is", $businessId, $currentMonth);
        $businessExpensesQuery->execute();
        $businessExpensesData = $businessExpensesQuery->get_result()->fetch_assoc();
        $businessExpenses = $businessExpensesData['business_expenses'] ?? 0;

        // Combine totals
        $business['total_sales'] = $businessSales;
        $business['total_expenses'] = $businessExpenses;

        // Send JSON response
        header('Content-Type: application/json');
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