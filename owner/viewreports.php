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
            SUM(sales.total_sales) AS total_sales
        FROM 
            sales
        LEFT JOIN 
            branch ON sales.branch_id = branch.id
        LEFT JOIN 
            products ON sales.product_id = products.id
        WHERE 
            (branch.business_id IS NOT NULL OR products.business_id IS NOT NULL)
        GROUP BY 
            COALESCE(branch.business_id, products.business_id);
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
<style>
    .container-fluid {
        padding: 0 15px;
    }

    .dashboard-body {
        padding: 15px;
    }

    .dashboard-content h1 {
        font-size: 24px;
        margin-bottom: 20px;
    }

    .table-container {
        overflow-x: auto;
    }

    .table {
        width: 100%;
        margin-bottom: 1rem;
        color: #212529;
    }

    .table th,
    .table td {
        padding: 0.75rem;
        vertical-align: top;
        border-top: 1px solid #dee2e6;
    }

    .table thead th {
        vertical-align: bottom;
        border-bottom: 2px solid #dee2e6;
    }

    .table-striped tbody tr:nth-of-type(odd) {
        background-color: rgba(0, 0, 0, 0.05);
    }

    .table-hover tbody tr:hover {
        background-color: rgba(0, 0, 0, 0.075);
    }

    .view-branches {
        padding: 0.375rem 0.75rem;
        font-size: 1rem;
        background-color: #007bff;
        color: white;
        border: none;
        border-radius: 0.25rem;
    }

    /* Mobile Responsive Styles */
    @media (max-width: 767.98px) {
        .dashboard-content h1 {
            font-size: 20px;
        }

        .table th,
        .table td {
            padding: 0.5rem;
            font-size: 14px;
        }

        .table thead th {
            font-size: 14px;
        }

        .view-branches {
            padding: 0.25rem 0.5rem;
            font-size: 14px;
        }

        .dashboard-content h5 {
            font-size: 16px;
        }
    }

    @media (max-width: 575.98px) {
        .dashboard-content h1 {
            font-size: 18px;
        }

        .table th,
        .table td {
            padding: 0.375rem;
            font-size: 12px;
        }

        .table thead th {
            font-size: 12px;
        }

        .view-branches {
            padding: 0.2rem 0.4rem;
            font-size: 12px;
        }

        .dashboard-content h5 {
            font-size: 14px;
        }

        .container-fluid {
            padding: 0 10px;
        }

        .dashboard-body {
            padding: 10px;
        }
    }

    /* Scrollable Table Container */
    .scrollable-table-two {
        overflow-x: auto;
        max-width: 100%;
        margin: 0 auto;
    }
</style>

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

                    <h5 class="mt-5"><b>Business Overview:</b> <i class="fas fa-info-circle"
                            onclick="showInfo('Business Overview', 'This table provides an overview of each business, including total sales and expenses.');"></i>
                    </h5>

                    <div class="table-container scrollable-table-two">
                        <table class="table table-striped table-hover mt-4">
                            <thead class="table-dark position-sticky top-0">
                                <tr>
                                    <th>Business Name <button class="btn text-white"><i
                                                class="fas fa-sort"></i></button></th>
                                    <th>Total Sales (₱) <button class="btn text-white"><i
                                                class="fas fa-sort"></i></button></th>
                                    <th>Total Expenses (₱) <button class="btn text-white"><i
                                                class="fas fa-sort"></i></button></th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($businesses) {
                                    foreach ($businesses as $business) {
                                        // Initialize total sales and expenses
                                        $total_sales = 0;
                                        $total_expenses = $business['total_expenses'];

                                        // Find the matching sales data for this business_id
                                        foreach ($salesData as $sales) {
                                            if ($sales['business_id'] == $business['business_id']) {
                                                $total_sales += $sales['total_sales']; // Add the sales data
                                            }
                                        }

                                        // Display the data in the table
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($business['business_name']) . "</td>";
                                        echo "<td>₱" . number_format($total_sales, 2) . "</td>";
                                        echo "<td>₱" . number_format($total_expenses, 2) . "</td>";
                                        echo "<td><button class='swal2-print-btn view-branches' 
            data-business-id='" . $business['business_id'] . "' 
            onclick=\"fetchAndShowBranchDetails(" . $business['business_id'] . ")\">
            View Branches
        </button></td>";
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
    <script>
        async function fetchAndShowBranchDetails(businessId) {
            try {
                const response = await fetch(`../endpoints/reports/fetch_expenses_and_sales.php?business_id=${businessId}`);
                const data = await response.json();

                if (data.error) {
                    Swal.fire('Error', data.error, 'error');
                    return;
                }

                const { business, branches } = data;
                let branchCheckboxes = '';

                // Generate checkboxes for branches (if available)
                if (branches.length > 0) {
                    branches.forEach(branch => {
                        branchCheckboxes += `
                    <div>
                        <input type="checkbox" class="branch-checkbox" value="${branch.id}" checked>
                        <label>${branch.location}</label>
                    </div>`;
                    });
                } else {
                    branchCheckboxes = `<p style="color: gray;">No branches available.</p>`;
                }

                // Construct the SweetAlert modal
                Swal.fire({
                    title: 'Select Business & Branches',
                    html: `
                <div>
                    <input type="checkbox" id="business-checkbox" value="${business.id}" checked>
                    <label><strong>${business.name}</strong></label>
                </div>
                <hr>
                <h5>Branches</h5>
                ${branchCheckboxes}
            `,
                    showCancelButton: true,
                    confirmButtonText: 'Proceed',
                    cancelButtonText: 'Cancel',
                    preConfirm: () => {
                        // Get selected checkboxes
                        const selectedBusiness = document.getElementById('business-checkbox').checked ? business.id : null;
                        const selectedBranches = [...document.querySelectorAll('.branch-checkbox:checked')].map(cb => cb.value);

                        // Allow proceeding if either the business or at least one branch is selected
                        if (!selectedBusiness === 0) {
                            Swal.showValidationMessage('Please select at least one option.');
                            return false;
                        }

                        return { selectedBusiness, selectedBranches };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        showBusinessReport(result.value.selectedBusiness, result.value.selectedBranches, business, branches);
                    }
                });

            } catch (error) {
                console.error('Error fetching branch details:', error);
                Swal.fire('Error', 'Failed to fetch branch details.', 'error');
            }
        }

        // Function to show the business report based on selection
        function showBusinessReport(selectedBusiness, selectedBranches, business, branches) {
            let filteredBranches = branches.filter(branch => selectedBranches.includes(branch.id.toString()));
            let combinedSales = parseFloat(business.total_sales) || 0;
            let combinedExpenses = parseFloat(business.total_expenses) || 0;

            let branchDetails = filteredBranches.map(branch => `
        <tr>
            <td>${branch.location}</td>
            <td>₱${parseFloat(branch.sales).toLocaleString()}</td>
            <td>₱${parseFloat(branch.expenses).toLocaleString()}</td>
        </tr>`).join('');

            if (!branchDetails) {
                branchDetails = `<tr><td colspan="3" style="text-align: center;">No Branch Selected.</td></tr>`;
            }

            // If branches exist, add their sales & expenses
            if (filteredBranches.length > 0) {
                filteredBranches.forEach(branch => {
                    combinedSales += parseFloat(branch.sales);
                    combinedExpenses += parseFloat(branch.expenses);
                });
            }

            const content = `
        <div>
            <h3>${business.name}</h3>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Business Sales</th>
                        <th>Business Expenses</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>₱${parseFloat(business.total_sales).toLocaleString()}</td>
                        <td>₱${parseFloat(business.total_expenses).toLocaleString()}</td>
                    </tr>
                </tbody>
            </table>
            <hr>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Branch</th>
                        <th>Sales (₱)</th>
                        <th>Expenses (₱)</th>
                    </tr>
                </thead>
                <tbody>
                    ${branchDetails}
                </tbody>
            </table>
            <hr>
            <p><b>Total Sales:</b> ₱${combinedSales.toLocaleString()}</p>
            <p><b>Total Expenses:</b> ₱${combinedExpenses.toLocaleString()}</p>
            <button class="swal2-print-btn" onclick='printBranchReport("${business.name}", ${JSON.stringify(filteredBranches)}, ${JSON.stringify(business)})'>
                <i class="fas fa-print me-2"></i> Generate Report
            </button>
        </div>`;

            Swal.fire({
                title: 'Business Details',
                html: content,
                width: '50%',
                showConfirmButton: false
            });
        }


    </script>

    <script src="../js/owner_view_reports.js"></script>
    <script src="../js/sidebar.js"></script>
    <script src="../js/sort_items.js"></script>
    <script src="../js/show_info.js"></script>
</body>

</html>