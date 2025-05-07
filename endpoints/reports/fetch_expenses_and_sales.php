<?php
require_once '../../conn/conn.php';

if (isset($_GET['business_id'])) {
    $businessId = intval($_GET['business_id']);

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

            $salesQuery = $conn->prepare("
                SELECT SUM(total_sales) AS monthly_sales 
                FROM sales 
                WHERE branch_id = ?");
            $salesQuery->bind_param("i", $branchId);
            $salesQuery->execute();
            $salesData = $salesQuery->get_result()->fetch_assoc();
            $branch['sales'] = $salesData['monthly_sales'] ?? 0;

            $expensesQuery = $conn->prepare("
                SELECT SUM(amount) AS total_expenses 
                FROM expenses 
                WHERE category = 'branch' AND category_id = ?");
            $expensesQuery->bind_param("i", $branchId);
            $expensesQuery->execute();
            $expensesData = $expensesQuery->get_result()->fetch_assoc();
            $branch['expenses'] = $expensesData['total_expenses'] ?? 0;

            $totalBranchSales += $branch['sales'];
            $totalBranchExpenses += $branch['expenses'];
            $branches[] = $branch;
        }

        // ✅ Fetch ALL business-level sales (no date filter)
        $businessSalesQuery = $conn->prepare("
            SELECT SUM(total_sales) AS business_sales 
            FROM sales 
            WHERE branch_id = 0 AND product_id IN (
                SELECT id FROM products WHERE business_id = ?)");
        $businessSalesQuery->bind_param("i", $businessId);
        $businessSalesQuery->execute();
        $businessSalesData = $businessSalesQuery->get_result()->fetch_assoc();
        $businessSales = $businessSalesData['business_sales'] ?? 0;

        // ✅ Fetch ALL business-level expenses (no date filter)
        $businessExpensesQuery = $conn->prepare("
            SELECT SUM(amount) AS business_expenses 
            FROM expenses 
            WHERE category = 'business' AND category_id = ?");
        $businessExpensesQuery->bind_param("i", $businessId);
        $businessExpensesQuery->execute();
        $businessExpensesData = $businessExpensesQuery->get_result()->fetch_assoc();
        $businessExpenses = $businessExpensesData['business_expenses'] ?? 0;

        $business['total_sales'] = $businessSales;
        $business['total_expenses'] = $businessExpenses;

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