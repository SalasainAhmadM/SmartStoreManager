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
                                    <input type="radio" name="selection" value="business" id="businessRadio">
                                    <i class="fas fa-briefcase me-2"></i> <strong>Business</strong>
                                </label>

                                <label for="branchRadio" onclick="window.location.href='manageexpenses_branch.php'"
                                    style="margin-right: 2rem;">
                                    <input type="radio" name="selection" value="branch" id="branchRadio">
                                    <i class="fas fa-store me-2"></i> <strong>Branch</strong>
                                </label>

                                <label for="typesRadio">
                                    <input type="radio" name="selection" value="branch" id="typesRadio" checked>
                                    <i class="fa-solid fa-money-check-dollar"></i><strong> Expense Types</strong>
                                </label>
                            </div>



                        </h5>
                    </div>

                </div>


                <div id="expensesPanel" class="scrollable-table" style="padding:0 2rem;">

                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <button class="btn btn-success ms-auto m-1" id="addExpenseTypeBtn" type="button">
                            <i class="fas fa-plus me-2"></i> Add Type
                        </button>
                    </div>

                    <table class="table table-striped table-hover mt-4" id="expensesListTable">
                        <thead class="table-dark position-sticky top-0">
                            <tr>
                                <th>Type <button class="btn text-white"><i class="fas fa-sort"></i></button></th>
                                <th>Description <button class="btn text-white"><i class="fas fa-sort"></i></button></th>
                                <th>Created At <button class="btn text-white"><i class="fas fa-sort"></i></button></th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>

                        <tbody id="expensesList">

                        </tbody>

                    </table>


                    <!-- <button class="btn btn-primary mt-2 mb-5" id="expensesListTable"
                        onclick="printContent('expensesPanel', 'Expenses List Report for <?php echo $name; ?>')">
                        <i class="fas fa-print me-2"></i> Print Report (Expenses List)
                    </button> -->

                </div>
            </div>
        </div>
    </div>
    </div>

    <script>
        document.getElementById('addExpenseTypeBtn').addEventListener('click', function () {
            // Use PHP to inject the `owner_id` dynamically
            const ownerId = <?php echo json_encode($owner_id); ?>;

            Swal.fire({
                title: 'Add Custom Expense Type',
                html: `
                <div>
                    <input type="text" id="expenseTypeName" class="form-control mb-2" placeholder="Expense Type Name">
                </div>
            `,
                confirmButtonText: 'Add Expense Type',
                showCancelButton: true,
                cancelButtonText: 'Close',
                preConfirm: () => {
                    const typeName = document.getElementById('expenseTypeName').value.trim();

                    if (!typeName) {
                        Swal.showValidationMessage('Please enter an expense type name');
                        return false;
                    }

                    return { typeName };
                }
            }).then(result => {
                if (result.isConfirmed) {
                    // POST the new expense type to the server
                    fetch('../endpoints/expenses/add_expense_type.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            type_name: result.value.typeName,
                            is_custom: 1,
                            owner_id: ownerId
                        })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire('Success', 'Custom expense type added successfully.', 'success').then(() => {
                                    location.reload(); // Reload to update the list
                                });
                            } else {
                                Swal.fire('Error', data.message, 'error');
                            }
                        })
                        .catch(err => {
                            Swal.fire('Error', 'An unexpected error occurred.', 'error');
                            console.error('Error:', err);
                        });
                }
            });
        });

        async function fetchAndDisplayExpenseTypes() {
            try {
                const response = await fetch('../endpoints/expenses/fetch_expense_types.php');
                const data = await response.json();

                if (data.success) {
                    const expenseList = document.getElementById('expensesList');
                    expenseList.innerHTML = ''; // Clear existing rows

                    data.expenseTypes.forEach(type => {
                        const row = document.createElement('tr');

                        // Define action column content
                        let actionContent = '';
                        if (type.is_custom && type.owner_id === <?= $_SESSION['user_id']; ?>) {
                            actionContent = `
                        <a href="#" class="text-primary me-3" onclick="editExpenseType(${type.id}, '${type.type_name}')">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="#" class="text-danger" onclick="deleteExpenseType(${type.id})">
                            <i class="fas fa-trash"></i>
                        </a>
                    `;
                        } else if (!type.is_custom) {
                            actionContent = 'Default';
                        }

                        // Define created_at content
                        const createdAtContent = type.is_custom ? type.created_at : 'Default';

                        // Populate table row
                        row.innerHTML = `
                    <td>${type.type_name}</td>
                    <td>${type.is_custom ? 'Custom Expense Type' : 'System Generated'}</td>
                    <td>${createdAtContent}</td>
                    <td class="text-center">${actionContent}</td>
                `;
                        expenseList.appendChild(row);
                    });
                } else {
                    console.error('Failed to fetch expense types:', data.message);
                }
            } catch (error) {
                console.error('Error fetching expense types:', error);
            }
        }


        // Edit Expense Type
        function editExpenseType(id, typeName) {
            Swal.fire({
                title: 'Edit Expense Type',
                input: 'text',
                inputValue: typeName,
                inputLabel: 'Expense Type Name',
                confirmButtonText: 'Save Changes',
                showCancelButton: true,
            }).then(result => {
                if (result.isConfirmed) {
                    fetch(`../endpoints/expenses/edit_expense_type.php`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id, type_name: result.value })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire('Updated!', 'Expense type updated successfully.', 'success').then(() => {
                                    fetchAndDisplayExpenseTypes(); // Refresh table
                                });
                            } else {
                                Swal.fire('Error', data.message, 'error');
                            }
                        });
                }
            });
        }

        // Delete Expense Type
        function deleteExpenseType(id) {
            Swal.fire({
                title: 'Delete Expense Type',
                text: 'Are you sure you want to delete this expense type?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Confirm',
            }).then(result => {
                if (result.isConfirmed) {
                    fetch(`../endpoints/expenses/delete_expense_type.php`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire('Deleted!', 'Expense type deleted successfully.', 'success').then(() => {
                                    fetchAndDisplayExpenseTypes(); // Refresh table
                                });
                            } else {
                                Swal.fire('Error', data.message, 'error');
                            }
                        });
                }
            });
        }

        // Fetch and display the expense types on page load
        fetchAndDisplayExpenseTypes();
    </script>

    <script src="../js/print_report.js"></script>

    <script src="../js/sidebar.js"></script>
    <script src="../js/sort_items.js"></script>

</body>

</html>