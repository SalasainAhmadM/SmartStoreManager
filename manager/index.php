<?php
session_start();
require_once '../conn/auth.php';
require_once '../conn/conn.php';
validateSession('manager');

// if (isset($_SESSION['login_success']) && $_SESSION['login_success']) {
//     echo "
//         <script>
//             window.onload = function() {
//                 Swal.fire({
//                     icon: 'success',
//                     title: 'Login Successful',
//                     text: 'Welcome!',
//                     timer: 2000,
//                     showConfirmButton: false
//                 });
//             };
//         </script>
//     ";
//     unset($_SESSION['login_success']);
// }
$manager_id = $_SESSION['user_id'];

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
        WHERE s.type = 'branch' AND s.branch_id = ? AND s.user_role != 'Owner'
        ORDER BY s.date DESC
    ";
    $stmt = $conn->prepare($sales_query);
    $stmt->bind_param('i', $assigned['id']);
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
        WHERE s.type = 'business' AND s.branch_id = 0 AND s.user_role != 'Owner'
        ORDER BY s.date DESC
    ";
    $stmt = $conn->prepare($sales_query);
}

$stmt->execute();
$sales_result = $stmt->get_result();

// Prepare data for the chart
$product_sales = [];
if ($sales_result->num_rows > 0) {
    while ($row = $sales_result->fetch_assoc()) {
        $product_name = $row['product'];
        if (isset($product_sales[$product_name])) {
            $product_sales[$product_name] += $row['quantity'];
        } else {
            $product_sales[$product_name] = $row['quantity'];
        }
    }
}

// Reset the result pointer to reuse the sales data for the table
$sales_result->data_seek(0);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Dashboard</title>
    <link rel="icon" href="../assets/logo.png">
    <?php include '../components/head_cdn.php'; ?>
</head>
<style>
    .permit-modal-container .swal2-popup {
        max-height: 80vh;
        overflow: hidden;
    }

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

        .card-one h5 {
            font-size: 16px;
        }

        /* .d-flex.align-items-center {
            flex-direction: column;
            align-items: stretch !important;
        } */

        #searchInput,
        #addBranchSaleBtn,
        #addBusinessSaleBtn,
        #uploadDataButton {
            width: 100%;
            margin-bottom: 10px;
        }

        .scrollable-table {
            overflow-x: auto;
        }

        .table thead th {
            font-size: 14px;
        }

        .table tbody td {
            font-size: 14px;
            padding: 8px;
        }

        .row.mt-4 {
            flex-direction: column;
        }

        .col-md-8,
        .col-md-4 {
            width: 100%;
            max-width: 100%;
        }

        #salesChart {
            margin-top: 30px;
            max-height: 300px;
        }

        #printReportBtn {
            width: 100%;
            margin-top: 20px;
        }

        #manager_sidebar {
            order: -1;
            position: static;
            width: 100%;
            height: auto;
        }

        .dashboard-body {
            padding: 15px 0;
        }
    }

    @media (max-width: 575.98px) {
        .dashboard-content h1 {
            font-size: 20px;
        }

        .dashboard-content h4 {
            font-size: 16px;
        }

        .card-one h5 {
            font-size: 14px;
        }

        .table thead th {
            font-size: 12px;
        }

        .table tbody td {
            font-size: 12px;
            padding: 6px;
        }

        .btn {
            font-size: 14px;
            padding: 8px 12px;
        }

        #salesChart {
            max-height: 250px;
        }
    }

    @media (max-width: 767px) {
        .product-row td {
            white-space: nowrap;
        }

        .table-dark th {
            background-color: #343a40;
            position: sticky;
            left: 0;
        }

        .text-center {
            text-align: left !important;
        }

        .me-3 {
            margin-right: 1rem !important;
        }
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


                        <div id="salesPanel">
                            <h4 class="mt-4" id="salesTitle"></h4>


                            <!-- Search Bar and Buttons -->
                            <div class="mt-3 d-flex align-items-center">
                                <!-- Search Bar -->
                                <form class="d-flex me-2" role="search" style="flex: 1;">
                                    <input class="form-control" type="search" placeholder="Search product.."
                                        aria-label="Search" id="searchInput">
                                </form>

                                <!-- Add Business/Branch Button -->
                                <?php if ($assigned_type === 'Branch'): ?>
                                    <button class="btn btn-success me-2" id="addBranchSaleBtn" data-type="Branch"
                                        data-id="<?php echo htmlspecialchars($assigned['id']); ?>"
                                        data-name="<?php echo htmlspecialchars($assigned_name); ?>"
                                        data-business-id="<?php echo htmlspecialchars($assigned['business_id']); ?>"
                                        data-business-name="<?php echo htmlspecialchars($business_name); ?>">
                                        <i class="fas fa-plus me-2"></i> Add Branch Sales
                                    </button>
                                <?php elseif ($assigned_type === 'Business'): ?>
                                    <button class="btn btn-success me-2" id="addBusinessSaleBtn" data-type="Business"
                                        data-id="<?php echo htmlspecialchars($assigned['id']); ?>"
                                        data-name="<?php echo htmlspecialchars($assigned_name); ?>">
                                        <i class="fas fa-plus me-2"></i> Add Business Sales
                                    </button>
                                <?php endif; ?>

                                <!-- Upload Data Button -->
                                <button id="uploadDataButton" class="btn btn-success">
                                    <i class="fa-solid fa-upload"></i> Upload Data
                                </button>
                            </div>

                            <div class="row mt-4">
                                <!-- Sales Table -->
                                <div class="col-md-8">
                                    <div class="scrollable-table">
                                        <table class="table table-striped table-hover" id="salesTable">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th>Product</th>
                                                    <th>Price</th>
                                                    <th>Quantity Sold</th>
                                                    <th>Revenue</th>
                                                    <th>Updated At</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody id="salesTableBody">
                                                <?php if ($sales_result->num_rows > 0): ?>
                                                    <?php while ($row = $sales_result->fetch_assoc()): ?>
                                                        <tr class="product-row">
                                                            <td class="product-name">
                                                                <?php echo htmlspecialchars($row['product']); ?>
                                                            </td>
                                                            <td>$<?php echo number_format($row['price'], 2); ?></td>
                                                            <td><?php echo htmlspecialchars($row['quantity']); ?></td>
                                                            <td>$<?php echo number_format($row['revenue'], 2); ?></td>
                                                            <td><?php echo htmlspecialchars($row['date']); ?></td>
                                                            <td class="text-center">
                                                                <a href="#" class="text-primary me-3"
                                                                    onclick="editSale(<?php echo $row['id']; ?>)">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>
                                                                <a href="#" class="text-danger"
                                                                    onclick="deleteSale(<?php echo $row['id']; ?>)">
                                                                    <i class="fas fa-trash"></i>
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    <?php endwhile; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="6" class="text-center">No sales records found.</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Chart -->
                                <div class="col-md-4">
                                    <canvas id="salesChart"></canvas>
                                </div>
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
    <script>
        const productSalesData = <?php echo json_encode($product_sales); ?>;
        const productNames = Object.keys(productSalesData);
        const productQuantities = Object.values(productSalesData);

        const ctx = document.getElementById('salesChart').getContext('2d');
        const salesChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: productNames,
                datasets: [{
                    label: 'Quantity Sold',
                    data: productQuantities,
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        // Edit Sale
        function editSale(salesId) {
            console.log('Edit Sale Triggered for ID:', salesId); // Debugging

            fetch('fetch_sale_details.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({ sale_id: salesId }).toString()
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const { product, quantity, date } = data.sale;

                        Swal.fire({
                            title: 'Edit Sale',
                            html: `
                    <label for="editQuantity">Quantity Sold</label>
                    <input type="number" id="editQuantity" class="form-control mb-2" value="${quantity}" min="1">
                    <label for="editDate">Sales Date</label>
                    <input type="date" id="editDate" class="form-control mb-2" value="${date}">
                `,
                            showCancelButton: true,
                            confirmButtonText: 'Update',
                            preConfirm: () => {
                                const quantity = document.getElementById('editQuantity').value;
                                const saleDate = document.getElementById('editDate').value;

                                if (!quantity || quantity <= 0 || !saleDate) {
                                    Swal.showValidationMessage('Please enter valid data');
                                }
                                return { quantity, saleDate };
                            }
                        }).then(async (result) => {
                            if (result.isConfirmed) {
                                const { quantity, saleDate } = result.value;

                                try {
                                    const response = await fetch('update_sale.php', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/x-www-form-urlencoded'
                                        },
                                        body: new URLSearchParams({
                                            sale_id: salesId, // Correct key
                                            quantity,
                                            date: saleDate
                                        }).toString()
                                    });

                                    const resData = await response.json();
                                    if (resData.success) {
                                        Swal.fire('Success', 'Sale updated successfully!', 'success').then(() => location.reload());
                                    } else {
                                        Swal.fire('Error', resData.message || 'Failed to update sale.', 'error');
                                    }
                                } catch (error) {
                                    console.error(error);
                                    Swal.fire('Error', 'Failed to submit update request.', 'error');
                                }
                            }
                        });
                    } else {
                        Swal.fire('Error', data.message || 'Failed to fetch sale details.', 'error');
                    }
                })
                .catch(error => {
                    console.error(error);
                    Swal.fire('Error', 'Failed to fetch sale details.', 'error');
                });
        }

        function deleteSale(salesId) {
            if (!salesId) {
                Swal.fire('Error', 'Invalid Sale ID.', 'error');
                return;
            }

            console.log('Deleting Sale ID:', salesId); // Debugging to verify the ID

            Swal.fire({
                title: 'Are you sure?',
                text: "This action cannot be undone!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    try {
                        const response = await fetch('delete_sale.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: new URLSearchParams({ sale_id: salesId }).toString() // Use `sale_id`
                        });

                        const resData = await response.json();
                        if (resData.status === 'success') { // Match the `status` from PHP response
                            Swal.fire('Deleted!', 'The sale has been deleted.', 'success').then(() => location.reload());
                        } else {
                            Swal.fire('Error', resData.message || 'Failed to delete sale.', 'error');
                        }
                    } catch (error) {
                        console.error(error);
                        Swal.fire('Error', 'Failed to submit delete request.', 'error');
                    }
                }
            });
        }


        document.getElementById('searchInput').addEventListener('input', function () {
            const filter = this.value.toLowerCase();  // Get the search term
            const rows = document.querySelectorAll('#salesTableBody .product-row');

            rows.forEach(row => {
                const productCell = row.querySelector('.product-name');
                if (productCell) {
                    const productName = productCell.textContent.toLowerCase();
                    row.style.display = productName.includes(filter) ? '' : 'none';  // Show or hide the row
                }
            });
        });
        // Function to get the current date in Asia/Manila timezone
        function getManilaDate() {
            const options = {
                timeZone: 'Asia/Manila',
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
            };
            const formatter = new Intl.DateTimeFormat('en-US', options);
            const parts = formatter.formatToParts(new Date());
            const year = parts.find(part => part.type === 'year').value;
            const month = parts.find(part => part.type === 'month').value;
            const day = parts.find(part => part.type === 'day').value;
            return `${year}-${month}-${day}`;
        }

        // Event Listener for Branch Sales Button
        document.getElementById('addBranchSaleBtn')?.addEventListener('click', async (e) => {
            const button = e.currentTarget;
            const branchId = button.getAttribute('data-id');
            const businessId = button.getAttribute('data-business-id');
            const today = getManilaDate(); // Use the Manila date

            try {
                const response = await fetch(`fetch_products.php?business_id=${businessId}`);
                const products = await response.json();

                const productOptions = products
                    .map(product => `<option value="${product.id}" data-price="${product.price}">${product.name} - ₱${product.price}</option>`)
                    .join('');

                Swal.fire({
                    title: 'Add Branch Sales',
                    html: `
                <label for="productSelect">Product</label>
                <select id="productSelect" class="form-control mb-2">${productOptions}</select>
                <label for="amountSold">Amount Sold</label>
                <input type="number" id="amountSold" class="form-control mb-2" min="1" placeholder="Enter amount sold">
                <label for="totalSales">Total Sales</label>
                <input type="text" id="totalSales" class="form-control mb-2" readonly placeholder="₱0">
                <label for="saleDate">Sales Date</label>
                <input type="date" id="saleDate" class="form-control mb-2" value="${today}">
            `,
                    showCancelButton: true,
                    confirmButtonText: "Add Sales",
                    preConfirm: () => {
                        const productSelect = document.getElementById('productSelect');
                        const amountSold = document.getElementById('amountSold').value;

                        if (!productSelect.value || !amountSold || amountSold <= 0) {
                            Swal.showValidationMessage('Please select a product and enter a valid amount sold');
                        }
                        return {
                            productId: productSelect.value,
                            amountSold: parseInt(amountSold, 10),
                            totalSales: parseFloat(document.getElementById('totalSales').value.replace('₱', '')),
                            saleDate: document.getElementById('saleDate').value,
                        };
                    },
                }).then(async (result) => {
                    if (result.isConfirmed) {
                        const salesData = result.value;
                        try {
                            const response = await fetch('add_branch_sales.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                },
                                body: JSON.stringify({
                                    branch_id: branchId,
                                    product_id: salesData.productId,
                                    quantity: salesData.amountSold,
                                    total_sales: salesData.totalSales,
                                    date: salesData.saleDate,
                                    user_role: 'Manager',
                                }),
                            });

                            const result = await response.json();
                            if (result.success) {
                                Swal.fire('Success', 'Branch sales added successfully.', 'success').then(() => location.reload());
                            } else {
                                Swal.fire('Error', result.message || 'Failed to add sales.', 'error');
                            }
                        } catch (error) {
                            Swal.fire('Error', 'Failed to submit sales data.', 'error');
                            console.error(error);
                        }
                    }
                });

                document.getElementById('amountSold').addEventListener('input', (e) => {
                    const productSelect = document.getElementById('productSelect');
                    const price = parseFloat(productSelect.selectedOptions[0].getAttribute('data-price'));
                    const amount = parseInt(e.target.value, 10);
                    const totalSales = isNaN(amount) || amount <= 0 ? 0 : price * amount;
                    document.getElementById('totalSales').value = `₱${totalSales.toFixed(2)}`;
                });
            } catch (error) {
                Swal.fire('Error', 'Failed to fetch products.', 'error');
                console.error(error);
            }
        });

        // Event Listener for Business Sales Button
        document.getElementById('addBusinessSaleBtn')?.addEventListener('click', async (e) => {
            const button = e.currentTarget;
            const businessId = button.getAttribute('data-id');
            const today = getManilaDate(); // Use the Manila date

            try {
                const response = await fetch(`fetch_products.php?business_id=${businessId}`);
                const products = await response.json();

                const productOptions = products
                    .map(product => `<option value="${product.id}" data-price="${product.price}">${product.name} - ₱${product.price}</option>`)
                    .join('');

                Swal.fire({
                    title: 'Add Business Sales',
                    html: `
                <label for="productSelect">Product</label>
                <select id="productSelect" class="form-control mb-2">${productOptions}</select>
                <label for="amountSold">Amount Sold</label>
                <input type="number" id="amountSold" class="form-control mb-2" min="1" placeholder="Enter amount sold">
                <label for="totalSales">Total Sales</label>
                <input type="text" id="totalSales" class="form-control mb-2" readonly placeholder="₱0">
                <label for="saleDate">Sales Date</label>
                <input type="date" id="saleDate" class="form-control mb-2" value="${today}">
            `,
                    showCancelButton: true,
                    confirmButtonText: "Add Sales",
                    preConfirm: () => {
                        const productSelect = document.getElementById('productSelect');
                        const amountSold = document.getElementById('amountSold').value;

                        if (!productSelect.value || !amountSold || amountSold <= 0) {
                            Swal.showValidationMessage('Please select a product and enter a valid amount sold');
                        }
                        return {
                            businessId,
                            productId: productSelect.value,
                            amountSold: parseInt(amountSold, 10),
                            totalSales: parseFloat(document.getElementById('totalSales').value.replace('₱', '')),
                            saleDate: document.getElementById('saleDate').value,
                        };
                    },
                }).then(async (result) => {
                    if (result.isConfirmed) {
                        const salesData = result.value;
                        try {
                            const response = await fetch('add_business_sales.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: new URLSearchParams({
                                    business_id: salesData.businessId,
                                    product_id: salesData.productId,
                                    quantity: salesData.amountSold,
                                    total_sales: salesData.totalSales,
                                    date: salesData.saleDate,
                                    user_role: 'Manager',
                                }).toString(),
                            });

                            const result = await response.json();
                            if (result.status === 'success') {
                                Swal.fire('Success', result.message, 'success').then(() => location.reload());
                            } else {
                                Swal.fire('Error', result.message || 'Failed to add sales.', 'error');
                            }
                        } catch (error) {
                            Swal.fire('Error', 'Failed to submit sales data.', 'error');
                            console.error(error);
                        }
                    }
                });

                document.getElementById('amountSold').addEventListener('input', (e) => {
                    const productSelect = document.getElementById('productSelect');
                    const price = parseFloat(productSelect.selectedOptions[0].getAttribute('data-price'));
                    const amount = parseInt(e.target.value, 10);
                    const totalSales = isNaN(amount) || amount <= 0 ? 0 : price * amount;
                    document.getElementById('totalSales').value = `₱${totalSales.toFixed(2)}`;
                });
            } catch (error) {
                Swal.fire('Error', 'Failed to fetch products.', 'error');
                console.error(error);
            }
        });

        document.getElementById("printReportBtn").addEventListener("click", function () {
            printSalesReport();
        });

        function printSalesReport() {
            const table = document.getElementById('salesTable').cloneNode(true); // Clone the table to avoid modifying the original

            // Remove the "Action" header
            const headerRow = table.querySelector('thead tr');
            if (headerRow && headerRow.children.length > 0) {
                headerRow.deleteCell(-1); // Remove the last <th> (Action)
            }

            // Remove the "Action" column from each row in the table body
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                if (row.children.length > 0) {
                    row.deleteCell(-1); // Remove the last <td> (Action)
                }
            });

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


        document.getElementById('uploadDataButton').addEventListener('click', function () {
            Swal.fire({
                title: 'Upload or Download Data',
                html: `
        <div class="mt-3 mb-3 position-relative">
            <form action="../import_sales_manager.php" method="POST" enctype="multipart/form-data" class="btn btn-success p-3">
                <i class="fa-solid fa-upload"></i>
                <label for="file" class="mb-2">Upload Data:</label>
                <input type="file" name="file" id="file" accept=".xlsx, .xls" class="form-control mb-2">
                <input type="submit" value="Upload Excel" class="form-control">
            </form>
            <div class="d-flex justify-content-center mt-2">
                <button class="btn btn-info me-2" id="instructionsButton">
                    <i class="fa-solid fa-info-circle"></i> 
                </button>
                <form action="../export_excel_sales_manager.php" method="POST">
                    <button class="btn btn-success" type="submit">
                        <i class="fa-solid fa-download"></i> Download Data Template
                    </button>
                </form>
            </div>
            <div id="instructionsContainer" class="instructions-overlay d-none">
                <div class="instructions-content text-center">
                    <img src="../assets/instructions/sales_manager.jpg" alt="Instructions Image" class="img-fluid instructions-img" id="instructionsImage">
                </div>
            </div>
        </div>
        `,
                showConfirmButton: false,
                customClass: {
                    popup: 'swal2-modal-wide'
                }
            });

            document.getElementById('instructionsButton').addEventListener('click', function () {
                document.getElementById('instructionsContainer').classList.remove('d-none');
            });

            document.getElementById('instructionsImage').addEventListener('click', function () {
                document.getElementById('instructionsContainer').classList.add('d-none');
            });
        });

        function removeQueryParam() {
            const newUrl = window.location.pathname; // Get the base URL without parameters
            window.history.replaceState({}, document.title, newUrl); // Update the URL without refreshing
        }
        // Show success alert if "?imported=true" exists in the URL
        window.onload = function () {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('imported')) {
                Swal.fire({
                    title: 'Success!',
                    text: 'Data imported successfully!',
                    icon: 'success',
                    confirmButtonText: 'OK'
                }).then(() => {
                    removeQueryParam();
                });
            }
        };

        window.onload = function () {
            const urlParams = new URLSearchParams(window.location.search);
            const status = urlParams.get('status');

            if (status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'Login Successful',
                    text: 'Welcome!',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    // Redirect to clean URL after SweetAlert closes
                    const cleanUrl = window.location.origin + window.location.pathname;
                    window.location.href = cleanUrl;
                });
            }
        };
    </script>

</body>

</html>