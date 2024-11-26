<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Reports</title>
    <link rel="icon" href="../assets/logo.png">
    <?php include '../components/head_cdn.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0-alpha1/css/bootstrap.min.css">
    <script src="https://kit.fontawesome.com/a076d05399.js"></script> <!-- FontAwesome CDN -->
</head>

<body class="d-flex">

    <?php include '../components/manager_sidebar.php'; ?>

    <div class="container-fluid page-body">
        <div class="row">
            <div class="col-md-12 dashboard-body">
                <div class="dashboard-content">
                    <h1><b>Manager Dashboard</b></h1>
                    <h4 class="mt-5"><b><i class="fas fa-tachometer-alt me-2"></i> View Reports</b></h4>
                    <div class="card-one">

                        <h5 class="mt-5"><b>Select Business:</b></h5>
                        <div class="mt-4 mb-4 position-relative">
                            <select class="form-select w-50" id="businessSelect">
                                <option value="">Select Business</option>
                                <option value="A">Business A</option>
                                <option value="B">Business B</option>
                            </select>
                        </div>

                        <!-- Sales Report Panel -->
                        <div id="salesReportPanel" class="collapse">
                            <h4 class="mt-4" id="reportTitle"></h4>
                            <button class="btn btn-primary mt-2 mb-5" id="printReportBtn">
                                <i class="fas fa-print me-2"></i> Print Sales Report
                            </button>

                            <!-- Search Bar -->
                            <div class="mt-4">
                                <form class="d-flex" role="search">
                                    <input class="form-control me-2 w-50" type="search" placeholder="Search product.." aria-label="Search">
                                </form>
                            </div>


                            <table class="table mt-3">
                            <table class="table table-striped table-hover mt-4">
                                <thead class="table-dark">
                                        <th>Date</th>
                                        <th>Product Sold</th>
                                        <th>Total Sales (PHP)</th>
                                    </tr>
                                </thead>
                                <tbody id="salesReportBody">
                                    <!-- Sales Data will be dynamically populated here -->
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="2"><strong>Total Sales</strong></td>
                                        <td id="totalSalesCell"><!-- Total Sales will be displayed here --></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0-alpha1/js/bootstrap.bundle.min.js"></script>
    <script src="../js/sidebar_manager.js"></script>
    <script>
        document.getElementById('businessSelect').addEventListener('change', function() {
            var selectedBusiness = this.value;
            var salesReportPanel = document.getElementById('salesReportPanel');
            var reportTitle = document.getElementById('reportTitle');
            var salesReportBody = document.getElementById('salesReportBody');
            var totalSalesCell = document.getElementById('totalSalesCell');

            salesReportBody.innerHTML = '';
            totalSalesCell.textContent = '';

            if (selectedBusiness === 'A') {
                reportTitle.textContent = 'Sales Report for Business A';
                // Example Sales Data for Business A
                const salesData = [
                    { date: '2024-11-01', product: 'Product 1', quantity: 10, price: 150, sales: 10 * 150 },
                    { date: '2024-11-01', product: 'Product 2', quantity: 5, price: 100, sales: 5 * 100 },
                    { date: '2024-11-02', product: 'Product 1', quantity: 8, price: 150, sales: 8 * 150 },
                    { date: '2024-11-02', product: 'Product 2', quantity: 3, price: 100, sales: 3 * 100 }
                ];

                let totalSales = 0;
                salesData.forEach(sale => {
                    salesReportBody.innerHTML += `
                        <tr>
                            <td>${sale.date}</td>
                            <td>${sale.product} (Quantity: ${sale.quantity}, Price: ₱${sale.price.toLocaleString()})</td>
                            <td>₱${sale.sales.toLocaleString()}</td>
                        </tr>
                    `;
                    totalSales += sale.sales;
                });

                totalSalesCell.textContent = `₱${totalSales.toLocaleString()}`;
            } else if (selectedBusiness === 'B') {
                reportTitle.textContent = 'Sales Report for Business B';
                // Example Sales Data for Business B
                const salesData = [
                    { date: '2024-11-01', product: 'Product 3', quantity: 15, price: 200, sales: 15 * 200 },
                    { date: '2024-11-01', product: 'Product 4', quantity: 7, price: 120, sales: 7 * 120 },
                    { date: '2024-11-02', product: 'Product 3', quantity: 12, price: 200, sales: 12 * 200 },
                    { date: '2024-11-02', product: 'Product 4', quantity: 5, price: 120, sales: 5 * 120 }
                ];

                let totalSales = 0;
                salesData.forEach(sale => {
                    salesReportBody.innerHTML += `
                        <tr>
                            <td>${sale.date}</td>
                            <td>${sale.product} (Quantity: ${sale.quantity}, Price: ₱${sale.price.toLocaleString()})</td>
                            <td>₱${sale.sales.toLocaleString()}</td>
                        </tr>
                    `;
                    totalSales += sale.sales;
                });

                totalSalesCell.textContent = `₱${totalSales.toLocaleString()}`;
            }

            if (selectedBusiness) {
                salesReportPanel.classList.add('show');
            } else {
                salesReportPanel.classList.remove('show');
            }
        });

        // Print report functionality
        document.getElementById('printReportBtn').addEventListener('click', function() {
            const printContent = document.getElementById('salesReportPanel').innerHTML;
            const originalContent = document.body.innerHTML;

            document.body.innerHTML = printContent;
            window.print();
            document.body.innerHTML = originalContent;
        });
    </script>
</body>

</html>
