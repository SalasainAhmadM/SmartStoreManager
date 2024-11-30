<?php
session_start();
require_once '../conn/conn.php';
require_once '../conn/auth.php';

validateSession('owner');

$owner_id = $_SESSION['user_id'];
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
                    <h1><b><i class="fas fa-wallet me-2"></i> Manage Expenses</b></h1>

                    <div class="mt-5 position-relative manage-expenses">
                        <h5>
                            <div class="position-relative">

                                <label style="margin-right: 2rem;" onclick="window.location.href='manageexpenses.php'">
                                    <input type="radio" name="selection" value="business" id="businessRadio" checked>
                                    <i class="fas fa-briefcase me-2"></i> <strong>Business</strong>
                                </label>

                                <label for="branchRadio" checked>
                                    <input type="radio" name="selection" value="branch" id="branchRadio">
                                    <i class="fas fa-store me-2"></i> <strong>Branch</strong>
                                </label>
                            </div>

                        </h5>
                    </div>


                </div>

                <div id="businessPanel" class="mt-3">
                    <div class="form-group">
                        <label for="businessSelect"><i class="fas fa-store me-2"></i></label>
                        <select id="businessSelect" class="form-control">
                            <option value="">Select Business</option>
                            <option value="A">Business A</option>
                            <option value="B">Business B</option>
                        </select>
                    </div>

                    <div class="form-group" id="branchGroup" style="display:none;">
                        <label for="branchSelect">Select Branch</label>
                        <select id="branchSelect" class="form-control">
                            <option value="">Select Branch</option>
                            <!-- Branch options will be populated dynamically -->
                        </select>
                    </div>



                    <div id="expensesPanel" class="collapse mt-5 scrollable-table">


                        <div class="d-flex justify-content-between align-items-center mt-4">

                            <div class="w-50">
                                <h2>Expenses List for <span id="businessName"></span> - Branch: <span id="branchName"></span>
                                    for the month of <span id="currentMonthYear"></span></h2>
                            </div>

                            <button class="btn btn-success ms-auto m-1" id="addExpenseBtn" type="button">
                                <i class="fas fa-plus me-2"></i> Add Expenses
                            </button>

                            <div class="dropdown">
                                <button class="btn btn-secondary dropdown-toggle" type="button" id="monthDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    Select Month
                                </button>

                                <ul class="dropdown-menu" aria-labelledby="monthDropdown">
                                    <li><a class="dropdown-item" href="#">January</a></li>
                                    <li><a class="dropdown-item" href="#">February</a></li>
                                    <li><a class="dropdown-item" href="#">March</a></li>
                                    <li><a class="dropdown-item" href="#">April</a></li>
                                    <li><a class="dropdown-item" href="#">May</a></li>
                                    <li><a class="dropdown-item" href="#">June</a></li>
                                    <li><a class="dropdown-item" href="#">July</a></li>
                                    <li><a class="dropdown-item" href="#">August</a></li>
                                    <li><a class="dropdown-item" href="#">September</a></li>
                                    <li><a class="dropdown-item" href="#">October</a></li>
                                    <li><a class="dropdown-item" href="#">November</a></li>
                                    <li><a class="dropdown-item" href="#">December</a></li>
                                </ul>
                            </div>
                        </div>


                        <table class="table table-striped table-hover mt-4">
                            <thead class="table-dark position-sticky top-0">
                                <tr>
                                    <th>Type <button class="btn text-white"><i class="fas fa-sort"></i></button></th>
                                    <th>Description <button class="btn text-white"><i class="fas fa-sort"></i></button></th>
                                    <th>Amount <button class="btn text-white"><i class="fas fa-sort"></i></button></th>
                                    <th class="text-center">Action <button class="btn text-white"><i class="fas fa-sort"></i></button></th>
                                </tr>
                            </thead>
                            <tbody id="expensesList">
                                <script src="../js/branch_expenses.js"></script>
                            </tbody>
                        </table>
                    </div>


                </div>
            </div>
        </div>

        <script>
            // Get the current month and year
            const currentDate = new Date();
            const currentMonth = currentDate.toLocaleString('default', {
                month: 'long'
            });
            const currentYear = currentDate.getFullYear();

            // Display the current month and year in the title
            document.getElementById('currentMonthYear').textContent = `${currentMonth} ${currentYear}`;

            // Update the business name when a business is selected
            document.getElementById('businessSelect').addEventListener('change', function() {
                const businessName = this.options[this.selectedIndex].text;
                document.getElementById('businessName').textContent = businessName;
            });
        </script>

        <script src="../js/sidebar.js"></script>
        <script src="../js/sort_items.js"></script>

</body>

</html>