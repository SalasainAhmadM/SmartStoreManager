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

    <?php include '../components/owner_sidebar.php'; ?>

    <div class="container-fluid page-body">

        <div class="row">
            <div class="col-md-12 dashboard-body">
                <div class="dashboard-content">
                    <h1><b><i class="fas fa-wallet me-2"></i> Manage Expenses</b></h1>

                    <!-- Radio Buttons for Business or Branch -->
                    <div class="mt-5 position-relative manage-expenses">
                        <h5>
                            <div class="position-relative">
                                <label style="margin-right: 2rem;">
                                    <input type="radio" name="selection" value="business" id="businessRadio">
                                    <i class="fas fa-briefcase me-2"></i> <strong>Business</strong>
                                </label>
                                <label>
                                    <input type="radio" name="selection" value="branch" id="branchRadio">
                                    <i class="fas fa-store me-2"></i> <strong>Branch</strong>
                                </label>
                            </div>
                            <!-- Add Expenses Button -->
                            <button class="btn btn-success position-absolute top-0 end-0 mt-2 me-2" type="button">
                                <i class="fas fa-plus me-2"></i> Add Expenses
                            </button>
                        </h5>
                    </div>


                </div>


                <div id="businessPanel" class="collapse mt-3">
                    <div class="form-group">
                        <label for="businessSelect">Select Business</label>
                        <select id="businessSelect" class="form-control">
                            <option value="">Select Business</option>
                            <option value="A">Business A</option>
                            <option value="B">Business B</option>
                        </select>
                    </div>


                    <div id="expensesPanel" class="collapse mt-3">
                        <h4>Expenses List for Business <span id="businessName"></span></h4>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Description</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody id="expensesList">
                                <!-- Expenses will be dynamically populated here -->
                            </tbody>
                        </table>
                    </div>



                </div>


                <script>
                    document.getElementById('businessRadio').addEventListener('click', function () {
                        document.getElementById('businessPanel').classList.add('show');
                    });
                    document.getElementById('branchRadio').addEventListener('click', function () {
                        document.getElementById('businessPanel').classList.remove('show');
                    });

                    document.getElementById('businessSelect').addEventListener('change', function () {
                        var businessName = this.value === 'A' ? 'A' : this.value === 'B' ? 'B' : '';
                        document.getElementById('businessName').textContent = businessName;

                        var expenses = businessName === 'A' ? [{
                            description: 'Rent',
                            amount: '$5000'
                        },
                        {
                            description: 'Utilities',
                            amount: '$300'
                        }
                        ] : businessName === 'B' ? [{
                            description: 'Marketing',
                            amount: '$2000'
                        },
                        {
                            description: 'Salaries',
                            amount: '$12000'
                        }
                        ] : [];

                        var expensesList = document.getElementById('expensesList');
                        expensesList.innerHTML = '';
                        expenses.forEach(function (expense) {
                            var row = document.createElement('tr');
                            row.innerHTML = `<td>${expense.description}</td><td>${expense.amount}</td>`;
                            expensesList.appendChild(row);
                        });

                        if (businessName) {
                            document.getElementById('expensesPanel').classList.add('show');
                        } else {
                            document.getElementById('expensesPanel').classList.remove('show');
                        }
                    });
                </script>
            </div>
        </div>
    </div>


    </div>

    <script src="../js/sidebar.js"></script>

</body>

</html>