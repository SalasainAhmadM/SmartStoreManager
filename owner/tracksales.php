<?php
session_start();
require_once '../conn/auth.php';

validateSession('owner');

$owner_id = $_SESSION['user_id'];


// Set the timezone to Philippine Time (Asia/Manila)
date_default_timezone_set('Asia/Manila');
$today = date("Y-m-d");
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

    <div id="particles-js"></div>

    <?php include '../components/owner_sidebar.php'; ?>





    <div class="container-fluid page-body">
        <div class="row">
            <div class="col-md-12 dashboard-body">
                <div class="dashboard-content">
                    <h1><b><i class="fas fa-chart-line me-2"></i> Track Sales</b></h1>

                    <div class="mt-5">
                        <div class="form-group">
                            <label for="businessSelect"><i class="fas fa-briefcase me-2"></i></label>
                            <select id="businessSelect" class="form-control">
                                <option value=""><strong>Select Business</strong></option>
                                <option value="A">Business A</option>
                                <option value="B">Business B</option>
                            </select>
                        </div>
                    </div>

                    <!-- Search Bar for Sales -->
                    <div class="mt-4 mb-4 position-relative">
                        <form class="d-flex" role="search">
                            <input id="saleSearchBar" class="form-control me-2 w-50" type="search" placeholder="Search sale.." aria-label="Search" onkeyup="searchSales()">
                        </form>
                        <!-- Add Sale Button -->
                        <button class="btn btn-success position-absolute top-0 end-0 mt-2 me-2" type="button">
                            <i class="fas fa-plus me-2"></i> Add Sale
                        </button>
                    </div>

                    <h2 class="mt-5 mb-3">
                        <b>Today’s Sales for Business A (<?php echo $today; ?>)</b>
                    </h2>

                    <div class="scrollable-table">
                        <table id="salesTable" class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Product <button class="btn text-white"><i class="fas fa-sort"></i></button></th>
                                    <th>Amount Sold <button class="btn text-white"><i class="fas fa-sort"></i></button></th>
                                    <th>Total Sales <button class="btn text-white"><i class="fas fa-sort"></i></button></th>
                                    <th>Date <button class="btn text-white"><i class="fas fa-sort"></i></button></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Product 1</td>
                                    <td>10</td>
                                    <td>₱100</td>
                                    <td><?php echo $today; ?></td>
                                </tr>
                                <tr>
                                    <td>Product 2</td>
                                    <td>15</td>
                                    <td>₱150</td>
                                    <td><?php echo $today; ?></td>
                                </tr>
                                <tr>
                                    <td>Product 3</td>
                                    <td>20</td>
                                    <td>₱200</td>
                                    <td><?php echo $today; ?></td>
                                </tr>
                                <tr>
                                    <td>Product 3</td>
                                    <td>20</td>
                                    <td>₱200</td>
                                    <td><?php echo $today; ?></td>
                                </tr>
                                <tr>
                                    <td>Product 3</td>
                                    <td>20</td>
                                    <td>₱200</td>
                                    <td><?php echo $today; ?></td>
                                </tr>
                                <tr>
                                    <td>Product 3</td>
                                    <td>20</td>
                                    <td>₱200</td>
                                    <td><?php echo $today; ?></td>
                                </tr>
                                <tr>
                                    <td>Product 3</td>
                                    <td>20</td>
                                    <td>₱200</td>
                                    <td><?php echo $today; ?></td>
                                </tr>
                                <tr>
                                    <td>Product 3</td>
                                    <td>20</td>
                                    <td>₱200</td>
                                    <td><?php echo $today; ?></td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th><strong>Total</strong></th>
                                    <th>45</th>
                                    <th>₱450</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="mt-5">
                        <button class="btn btn-info" type="button" data-bs-toggle="collapse" data-bs-target="#previousSalesTable" aria-expanded="false" aria-controls="previousSalesTable">
                            <i class="fas fa-calendar-day me-2"></i><b>View Sales Log</b> <i class="fas fa-plus me-2"></i>
                        </button>
                    </div>

                    <div class="collapse mt-5" id="previousSalesTable">
                        <h3><b>Sales Log</b></h3>

                        <div class="mt-4 mb-4 position-relative">

                            <form class="d-flex" role="search">
                                <input id="logSearchBar" class="form-control me-2 w-50" type="search" placeholder="Search sale log.." aria-label="Search" onkeyup="searchSalesLog()">
                            </form>

                            <!-- Date Filter Button for Sales Log -->
                            <div class="position-absolute top-0 end-0 mt-2 me-2">
                                <button class="btn btn-success" id="filterDateButton">
                                    <i class="fas fa-calendar-alt me-2"></i> Filter by Date
                                </button>
                                <button class="btn btn-danger" id="resetButton" onclick="resetFilter()">
                                    <i class="fas fa-times-circle me-2"></i> Reset Filter
                                </button>
                            </div>


                        </div>


                        <table id="salesLogTable" class="table table-striped table-hover mt-4">
                            <thead class="table-dark">
                                <tr>
                                    <th>Product <button class="btn text-white"><i class="fas fa-sort"></i></button></th>
                                    <th>Amount Sold <button class="btn text-white"><i class="fas fa-sort"></i></button></th>
                                    <th>Total Sales <button class="btn text-white"><i class="fas fa-sort"></i></button></th>
                                    <th>Date <button class="btn text-white"><i class="fas fa-sort"></i></button></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Product 1</td>
                                    <td>12</td>
                                    <td>₱120</td>
                                    <td>2024-12-24</td>
                                </tr>
                                <tr>
                                    <td>Product 2</td>
                                    <td>18</td>
                                    <td>₱180</td>
                                    <td>2024-11-24</td>
                                </tr>
                                <tr>
                                    <td>Product 3</td>
                                    <td>25</td>
                                    <td>₱250</td>
                                    <td>2024-10-24</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>

        </div>

    </div>



    <script src="../js/sidebar.js"></script>
    <script src="../js/sort_items.js"></script>

    <script>
        // Trigger SweetAlert with Date Picker
        document.getElementById('filterDateButton').addEventListener('click', function() {
            Swal.fire({
                title: 'Select Date to Filter Sales',
                html: '<input type="date" id="swalDateFilter" class="form-control">',
                showCancelButton: true,
                confirmButtonText: 'Filter',
                cancelButtonText: 'Cancel',
                preConfirm: () => {
                    const selectedDate = document.getElementById('swalDateFilter').value;
                    if (selectedDate) {
                        filterByDate(selectedDate);
                    }
                }
            });
        });

        // Function to filter sales log by selected date
        function filterByDate(date) {
            const rows = document.querySelectorAll('#salesLogTable tbody tr');
            let found = false; 

            rows.forEach(row => {
                const dateCell = row.cells[3]; 
                const rowDate = dateCell ? dateCell.textContent.trim() : '';

                if (rowDate === date) {
                    row.style.display = ''; 
                    found = true;
                } else {
                    row.style.display = 'none'; 
                }
            });

            // Show SweetAlert if no rows are found
            if (!found) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Sales Found',
                    text: `No sales found for the selected date: ${date}`,
                    confirmButtonText: 'OK'
                });
            }
        }

        // Function to reset the filter and show all rows
        function resetFilter() {
            const rows = document.querySelectorAll('#salesLogTable tbody tr');

            rows.forEach(row => {
                row.style.display = ''; 
            });
        }
    </script>

    <script>
        // Search function for Sales table
        function searchSales() {
            const query = document.getElementById('saleSearchBar').value.toLowerCase();
            const rows = document.querySelectorAll('#salesTable tbody tr');
            rows.forEach(row => {
                const cells = row.getElementsByTagName('td');
                let match = false;
                for (let i = 0; i < cells.length; i++) {
                    if (cells[i].textContent.toLowerCase().includes(query)) {
                        match = true;
                        break;
                    }
                }
                row.style.display = match ? '' : 'none';
            });
        }

        // Search function for Sales Log table
        function searchSalesLog() {
            const query = document.getElementById('logSearchBar').value.toLowerCase();
            const rows = document.querySelectorAll('#salesLogTable tbody tr');
            rows.forEach(row => {
                const cells = row.getElementsByTagName('td');
                let match = false;
                for (let i = 0; i < cells.length; i++) {
                    if (cells[i].textContent.toLowerCase().includes(query)) {
                        match = true;
                        break;
                    }
                }
                row.style.display = match ? '' : 'none';
            });
        }
    </script>

</body>

</html>