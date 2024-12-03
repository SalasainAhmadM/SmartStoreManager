<?php
session_start();
require_once '../conn/conn.php';
require_once '../conn/auth.php';

validateSession('owner');

$owner_id = $_SESSION['user_id'];

function fetchBusinessOverview($owner_id)
{
    global $conn;

    $query = "
        SELECT b.id AS business_id, b.name AS business_name,
            SUM(DISTINCT CASE 
                WHEN e.category = 'business' AND e.category_id = b.id THEN e.amount
                ELSE 0
            END) +
            SUM(DISTINCT CASE 
                WHEN e.category = 'branch' AND e.category_id = br.id THEN e.amount
                ELSE 0
            END) AS total_expenses
        FROM business b
        LEFT JOIN branch br ON b.id = br.business_id
        LEFT JOIN products p ON p.business_id = b.id
        LEFT JOIN expenses e ON (e.category = 'business' AND e.category_id = b.id)
                            OR (e.category = 'branch' AND e.category_id = br.id)
        WHERE b.owner_id = ?
        GROUP BY b.id, b.name
    ";

    // Prepare and execute the query
    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("i", $owner_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $businesses = [];
        while ($row = $result->fetch_assoc()) {
            $businesses[] = $row;
        }

        $stmt->close();
        return $businesses;
    } else {
        return false;
    }
}




function fetchSalesData($owner_id)
{
    global $conn;

    $query = "
        SELECT 
            COALESCE(branch.business_id, products.business_id) AS business_id,
            sales.product_id,
            SUM(sales.total_sales) AS total_sales
        FROM 
            sales
        LEFT JOIN 
            branch ON sales.branch_id = branch.id AND sales.branch_id != 0
        LEFT JOIN 
            products ON sales.product_id = products.id
        WHERE 
            (sales.branch_id != 0 AND branch.business_id IS NOT NULL)
            OR (sales.branch_id = 0 AND products.business_id IS NOT NULL)
        GROUP BY 
            COALESCE(branch.business_id, products.business_id), sales.product_id;
            ";

    // Execute the query
    if ($stmt = $conn->prepare($query)) {
        $stmt->execute();
        $result = $stmt->get_result();

        $salesData = [];
        while ($row = $result->fetch_assoc()) {
            $salesData[] = $row;
        }

        $stmt->close();
        return $salesData;
    } else {
        return false;
    }
}

$businesses = fetchBusinessOverview($owner_id);
$salesData = fetchSalesData($owner_id);

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Dashboard</title>
    <link rel="icon" href="../assets/logo.png">
    <?php include '../components/head_cdn.php'; ?>
</head>

<body class="d-flex">
    <div id="particles-js"></div>
    <?php include '../components/owner_sidebar.php'; ?>

    <div class="container-fluid page-body">
        <div class="row">
            <div class="col-md-12 dashboard-body">
                <div class="dashboard-content">
                    <h1 class="mb-4">
                        <b><i class="fas fa-chart-bar me-2"></i> View Reports</b>
                    </h1>

                    <h5 class="mt-5"><b>Business Overview:</b></h5>

                    <div class="table-container scrollable-table-two">
                        <table class="table table-striped table-hover mt-4">
                            <thead class="table-dark position-sticky top-0">
                                <tr>
                                    <th>Business Name <button class="btn text-white"><i class="fas fa-sort"></i></button></th>
                                    <th>Total Sales (₱) <button class="btn text-white"><i class="fas fa-sort"></i></button></th>
                                    <th>Total Expenses (₱) <button class="btn text-white"><i class="fas fa-sort"></i></button></th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($businesses) {
                                    foreach ($businesses as $business) {
                                        // Find the matching sales data for this business_id
                                        $total_sales = 0; // Default to 0 if no matching sales data
                                        foreach ($salesData as $sales) {
                                            if ($sales['business_id'] == $business['business_id']) {
                                                $total_sales = $sales['total_sales'];
                                                $total_expenses = $business['total_expenses']; 
                                                break;
                                            }
                                        }

                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($business['business_name']) . "</td>";
                                        echo "<td>₱" . number_format($total_sales, 2) . "</td>";
                                        echo "<td>₱" . number_format($business['total_expenses'], 2) . "</td>";
                                        echo "<td><button class='swal2-print-btn view-branches' onclick=\"showBranchDetails('" . htmlspecialchars($business['business_name']) . "', [
                                            {branch: 'N/A', sales: 0, expenses: 0}
                                        ])\">View Branches</button></td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='4'>No data available</td></tr>";
                                    }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>                             

    <script src="../js/owner_view_reports.js"></script>
    <script src="../js/sidebar.js"></script>
    <script src="../js/sort_items.js"></script>
</body>

</html>