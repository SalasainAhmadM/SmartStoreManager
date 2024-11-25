<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Dashboard</title>
    <link rel="icon" href="../assets/logo.png">
    <?php include '../components/head_cdn.php'; ?>
</head>

<?php
session_start();
if (isset($_SESSION['login_success']) && $_SESSION['login_success']) {
    echo "
        <script>
            window.onload = function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Login Successful',
                    text: 'Welcome!',
                    timer: 2000,
                    showConfirmButton: false
                });
            };
        </script>
    ";
    unset($_SESSION['login_success']);
}
?>

<body class="d-flex">

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

                                <label for="branchRadio" onclick="window.location.href='manageexpenses _branch.php'" checked>
                                    <input type="radio" name="selection" value="branch" id="branchRadio">
                                    <i class="fas fa-store me-2"></i> <strong>Branch</strong>
                                </label>
                            </div>
                            
                            <?php include '../components/add_expenses.php'; ?>

                        </h5>
                    </div>


                </div>

                <div id="businessPanel" class="mt-3">
                    <div class="form-group">
                        <label for="businessSelect">Select Business</label>
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

                    <div id="expensesPanel" class="collapse mt-3">
                        <h4>Expenses List for Branch <span id="branchName"></span></h4>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Description</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody id="expensesList">
                                <!-- Expenses will be dynamically populated here -->
                                <script src="../js/branch_expenses.js"></script>
                            </tbody>
                        </table>
                    </div>
                </div>


            </div>
        </div>
    </div>



    <script src="../js/sidebar.js"></script>

</body>

</html>