<?php
session_start();
require_once '../conn/auth.php';
require_once '../conn/conn.php';
validateSession('manager');
date_default_timezone_set('Asia/Manila');
$manager_id = $_SESSION['user_id'];
$selected_date = $_GET['date'] ?? date('Y-m-d'); // Default to today's date if no date is provided

// Query to fetch the assigned branch or business
$sql = "
    SELECT 'branch' AS type, b.id, b.location AS name, b.business_id, bs.name AS business_name
    FROM branch b
    LEFT JOIN business bs ON b.business_id = bs.id
    WHERE b.manager_id = ?
    UNION
    SELECT 'business' AS type, id, name, NULL AS business_id, NULL AS business_name
    FROM business
    WHERE manager_id = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $manager_id, $manager_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $assigned = $result->fetch_assoc();

    // Determine the type and details of the assignment
    if ($assigned['type'] === 'branch') {
        $assigned_type = 'Branch';
        $assigned_name = $assigned['name'];
        $business_name = $assigned['business_name'];
    } else {
        $assigned_type = 'Business';
        $assigned_name = $assigned['name'];
        $business_name = null;
    }
} else {
    // No assignment found
    $assigned_type = null;
    $assigned_name = null;
    $business_id = null;
}

$sales_query = "";

if ($assigned_type === 'Branch') {
    $sales_query = "
        SELECT 
            s.id, 
            p.name AS product, 
            p.price, 
            s.quantity, 
            (s.quantity * p.price) AS revenue, 
            s.date 
        FROM sales s
        LEFT JOIN products p ON s.product_id = p.id
        WHERE s.type = 'branch' AND s.branch_id = ? AND s.user_role != 'Owner' AND DATE(s.date) = ?
        ORDER BY s.date DESC
    ";
    $stmt = $conn->prepare($sales_query);
    $stmt->bind_param('is', $assigned['id'], $selected_date);
} elseif ($assigned_type === 'Business') {
    $sales_query = "
        SELECT 
            s.id, 
            p.name AS product, 
            p.price, 
            s.quantity, 
            (s.quantity * p.price) AS revenue, 
            s.date 
        FROM sales s
        LEFT JOIN products p ON s.product_id = p.id
        WHERE s.type = 'business' AND s.branch_id = 0 AND s.user_role != 'Owner' AND DATE(s.date) = ?
        ORDER BY s.date DESC
    ";
    $stmt = $conn->prepare($sales_query);
    $stmt->bind_param('s', $selected_date);
}

$stmt->execute();
$sales_result = $stmt->get_result();

$sales_data = [];
while ($row = $sales_result->fetch_assoc()) {
    $sales_data[] = $row;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Reports</title>
    <link rel="icon" href="../assets/logo.png">
    <?php include '../components/head_cdn.php'; ?>
</head>
<style>
    @media (max-width: 767.98px) {
        .container-fluid.page-body {
            padding: 0 15px;
        }

        .dashboard-content h1 {
            font-size: 24px;
        }

        .dashboard-content h4 {
            font-size: 18px;
        }

        .card-one h4,
        .card-one h5 {
            font-size: 16px;
        }

        .position-relative {
            flex-direction: column;
        }

        .position-absolute {
            position: static !important;
            margin-top: 10px;
            display: flex;
            gap: 10px;
        }

        #productSearchInput {
            width: 100% !important;
            margin-bottom: 10px;
        }

        .btn-success,
        .btn-danger {
            width: 100%;
            font-size: 14px;
            padding: 8px 12px;
        }

        .scrollable-table {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .table {
            min-width: 600px;
        }

        .table th,
        .table td {
            padding: 0.75rem;
            font-size: 14px;
        }

        #printReportBtn {
            width: 100%;
            margin-top: 20px;
        }
    }

    @media (max-width: 575.98px) {
        .dashboard-content h1 {
            font-size: 20px;
        }

        .dashboard-content h4 {
            font-size: 16px;
        }

        .card-one h4,
        .card-one h5 {
            font-size: 14px;
        }

        .btn-success,
        .btn-danger {
            font-size: 12px;
            padding: 6px 10px;
        }

        .table th,
        .table td {
            padding: 0.5rem;
            font-size: 12px;
        }

        .position-absolute {
            flex-direction: column;
            gap: 8px;
        }
    }

    .product-row td {
        white-space: nowrap;
    }

    .table-dark th {
        background-color: #343a40;
        position: sticky;
        left: 0;
    }

    .table tbody td {
        vertical-align: middle;
    }

    #salesReportPanel {
        position: relative;
    }
</style>

<body class="d-flex">

    <div id="particles-js"></div>

    <?php include '../components/manager_sidebar.php'; ?>

    <div class="container-fluid page-body">
        <div class="row">
            <div class="col-md-12 dashboard-body">
                <div class="dashboard-content">
                    <h1><b>Manager Dashboard</b></h1>
                    <h4 class="mt-5"><b><i class="fas fa-tachometer-alt me-2"></i> Manage Sales</b></h4>
                    <div class="card-one">
                        <?php if ($assigned_type): ?>
                            <?php if ($assigned_type === 'Branch' && $business_name): ?>
                                <!-- Display the business name if assigned to a branch -->
                                <h4 class="mt-2"><?php echo htmlspecialchars($business_name); ?></h4>
                                <h5 class="mt-2">
                                    <b>
                                        Assigned to
                                        <?php echo htmlspecialchars($assigned_type) . ': ' . htmlspecialchars($assigned_name); ?>
                                    </b>
                                </h5>
                            <?php else: ?>
                                <!-- Only show the assignment in h5 if assigned to a business -->
                                <h5 class="mt-2">
                                    <b>Assigned to
                                        <?php echo htmlspecialchars($assigned_type) . ': ' . htmlspecialchars($assigned_name); ?></b>
                                </h5>
                            <?php endif; ?>
                        <?php else: ?>
                            <h5 class="mt-2"><b>No Assignment Found</b></h5>
                        <?php endif; ?>

                        <div id="salesReportPanel">
                            <h4 class="mt-4" id="reportTitle"></h4>

                            <div class="mt-4 position-relative">
                                <form class="d-flex" role="search">
                                    <input class="form-control me-2 w-50" type="search" placeholder="Search product.."
                                        aria-label="Search" id="productSearchInput">
                                </form>

                                <div class="position-absolute top-0 end-0 mt-2 me-2">
                                    <button class="btn btn-success" type="button">
                                        <i class="fas fa-plus me-2"></i> Filter Date
                                    </button>
                                    <button class="btn btn-danger" id="resetButton">
                                        <i class="fas fa-times-circle me-2"></i> Reset Filter
                                    </button>
                                </div>


                            </div>

                            <div class="scrollable-table">
                                <table class="table mt-3" id="salesReportTable">
                                    <thead class="table-dark position-sticky top-0">
                                        <tr>
                                            <th>Product<button class="btn text-white"><i
                                                        class="fas fa-sort"></i></button>
                                            </th>
                                            <th>Amount Sold <button class="btn text-white"><i
                                                        class="fas fa-sort"></i></button></th>
                                            <th>Total Sales (PHP) <button class="btn text-white"><i
                                                        class="fas fa-sort"></i></button></th>
                                            <th>Date <button class="btn text-white"><i class="fas fa-sort"></i></button>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody id="salesReportBody">
                                        <?php if ($sales_result->num_rows > 0): ?>
                                            <?php while ($row = $sales_result->fetch_assoc()): ?>
                                                <tr class="product-row">
                                                    <td class="product-name">
                                                        <?php echo htmlspecialchars($row['product']); ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($row['quantity']); ?></td>
                                                    <td>$<?php echo number_format($row['revenue'], 2); ?></td>
                                                    <td><?php echo htmlspecialchars($row['date']); ?></td>

                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center">No sales records found.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="2"><strong>Total Sales</strong></td>
                                            <td id="totalSalesCell">
                                                <!-- Total Sales will be displayed here -->
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <button class="btn btn-primary mt-2 mb-5" id="printReportBtn" onclick="printSalesReport()">
                                <i class="fas fa-print me-2"></i> Print Sales Report
                            </button>

                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/sidebar_manager.js"></script>
    <script src="../js/sort_items.js"></script>

    <script src="../js/manager_view_reports.js"></script>
    <script src="../js/manager_view_reports_filter.js"></script>

    <!-- Searchbar -->
    <script>
        document.getElementById('productSearchInput').addEventListener('input', function () {
            const searchValue = this.value.toLowerCase();
            const tableRows = document.querySelectorAll('#salesReportBody tr');

            tableRows.forEach(row => {
                const productColumn = row.children[1];
                if (productColumn) {
                    const productText = productColumn.textContent.toLowerCase();
                    if (productText.includes(searchValue)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                }
            });
        });

        document.addEventListener("DOMContentLoaded", function () {
            const today = new Date().toISOString().split('T')[0]; // Get today's date in YYYY-MM-DD format
            filterTableByDate(today); // Fetch and display today's sales
        });

        function filterTableByDate(date) {
            fetch(`fetch_sales.php?date=${date}`)
                .then(response => response.json())
                .then(data => {
                    const salesReportBody = document.getElementById("salesReportBody");
                    salesReportBody.innerHTML = ""; // Clear existing rows

                    let totalSales = 0;

                    data.forEach(row => {
                        const newRow = document.createElement("tr");
                        newRow.innerHTML = `
          <td class="product-name">${row.product}</td>
          <td>${row.quantity}</td>
          <td>$${row.revenue.toFixed(2)}</td>
          <td>${row.date}</td>
        `;
                        salesReportBody.appendChild(newRow);

                        totalSales += row.revenue;
                    });

                    document.getElementById("totalSalesCell").textContent = `$${totalSales.toFixed(2)}`;
                })
                .catch(error => console.error('Error:', error));
        }

        document.getElementById("resetButton").addEventListener("click", function () {
            const today = getManilaDate();
            filterTableByDate(today);
        });

        function printSalesReport() {
            const table = document.getElementById('salesReportTable').cloneNode(true); // Clone the table to avoid modifying the original

            // Remove sort buttons from the header
            const headerButtons = table.querySelectorAll('thead .btn');
            headerButtons.forEach(button => button.remove());

            // Update the total sales in the cloned table
            const totalSalesCell = table.querySelector('#totalSalesCell');
            if (totalSalesCell) {
                totalSalesCell.textContent = document.getElementById('totalSalesCell').textContent;
            }

            // Get current date and time for the report heading
            const currentDate = new Date().toLocaleDateString();
            const currentTime = new Date().toLocaleTimeString();
            const businessName = "<?php echo htmlspecialchars($assigned_name); ?>"; // Replace with PHP to dynamically insert the business name

            // Create a new window for printing
            const printWindow = window.open('', '_blank', 'width=800,height=600');
            printWindow.document.open();
            printWindow.document.write(`
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Sales Report</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                margin: 20px;
                color: #333;
            }
            h1 {
                text-align: center;
                margin-bottom: 10px;
            }
            .report-heading {
                text-align: center;
                font-size: 16px;
                margin-bottom: 20px;
            }
            .report-details {
                margin-bottom: 15px;
                text-align: center;
                font-size: 14px;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
            }
            table, th, td {
                border: 1px solid black;
            }
            th, td {
                padding: 10px;
                text-align: left;
            }
            thead {
                background-color: #333;
                color: #fff;
            }
            tfoot {
                background-color: #f1f1f1;
                font-weight: bold;
            }
            button, .btn, .fas.fa-sort {
                display: none; /* Hide sort icons and buttons in print */
            }
        </style>
    </head>
    <body>
        <h1>Sales Report</h1>
        <div class="report-heading">
            <p><strong>Business:</strong> ${businessName}</p>
            <p><strong>Report Date:</strong> ${currentDate} | <strong>Time:</strong> ${currentTime}</p>
            <p><strong>Report Details:</strong> Sales data for the business</p>
        </div>
        ${table.outerHTML}
    </body>
    </html>
    `);
            printWindow.document.close();
            printWindow.print();
        }

    </script>

</body>

</html>