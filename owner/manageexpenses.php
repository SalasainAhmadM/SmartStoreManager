<?php
session_start();
require_once '../conn/conn.php';
require_once '../conn/auth.php';

validateSession('owner');

$owner_id = $_SESSION['user_id'];

// Fetch businesses owned by the logged-in user
$query = "SELECT id, name FROM business WHERE owner_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$business_result = $stmt->get_result();

$businesses = [];
while ($row = $business_result->fetch_assoc()) {
    $businesses[$row['id']] = $row['name'];
}
$stmt->close();

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
    <style>
        @media (max-width: 767.98px) {
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

            .manage-expenses h5 {
                font-size: 16px;
            }

            .manage-expenses label {
                display: inline-block;
                margin-right: 10px;
                margin-bottom: 10px;
            }

            .manage-expenses label input {
                margin-right: 5px;
            }

            #expensesPanel {
                overflow-x: auto;
            }

            .scrollable-table {
                width: 100%;
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

            .btn {
                display: inline-block;
                font-weight: 400;
                color: #212529;
                text-align: center;
                vertical-align: middle;
                cursor: pointer;
                background-color: transparent;
                /* border: 1px solid transparent; */
                padding: 0.375rem 0.75rem;
                font-size: 1rem;
                line-height: 1.5;
                border-radius: 0.25rem;
                transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
            }

            .btn-success {
                color: #fff;
                background-color: #198754;
                border-color: #198754;
            }

            .btn-danger {
                color: #fff;
                background-color: #dc3545;
                border-color: #dc3545;
            }

            .btn-primary {
                color: #fff;
                background-color: #007bff;
                border-color: #007bff;
            }

            .btn-secondary {
                color: #fff;
                background-color: #6c757d;
                border-color: #6c757d;
            }

            .dropdown-menu {
                max-height: 200px;
                overflow-y: auto;
            }

            .dashboard-content h1 {
                font-size: 20px;
            }

            .manage-expenses h5 {
                font-size: 14px;
            }

            .manage-expenses label {
                display: block;
                margin-right: 0;
                margin-bottom: 8px;
            }

            .manage-expenses label input {
                margin-right: 3px;
            }

            .table th,
            .table td {
                padding: 0.5rem;
            }

            .btn {
                padding: 0.25rem 0.5rem;
                font-size: 0.875rem;
            }

            #expensesPanel {
                padding: 0 10px;
            }

            .scrollable-table {
                overflow-x: auto;
            }

            .table thead th {
                font-size: 14px;
            }

            .table tbody td {
                font-size: 14px;
            }

            .btn-success,
            .btn-danger,
            .btn-primary,
            .btn-secondary {
                font-size: 14px;
            }

            .dropdown-menu {
                max-height: 150px;
            }
        }

        @media (max-width: 575.98px) {
            .dashboard-content h1 {
                font-size: 18px;
            }

            .manage-expenses h5 {
                font-size: 12px;
            }

            .manage-expenses label {
                display: block;
                margin-right: 0;
                margin-bottom: 5px;
            }

            .manage-expenses label input {
                margin-right: 2px;
            }

            .table th,
            .table td {
                padding: 0.375rem;
            }

            .btn {
                padding: 0.2rem 0.4rem;
                font-size: 0.75rem;
            }

            #expensesPanel {
                padding: 0 5px;
            }

            .table thead th {
                font-size: 12px;
            }

            .table tbody td {
                font-size: 12px;
            }

            .btn-success,
            .btn-danger,
            .btn-primary,
            .btn-secondary {
                font-size: 12px;
            }

            .dropdown-menu {
                max-height: 100px;
            }

            .form-control {
                font-size: 14px;
            }

            .form-group label {
                font-size: 14px;
            }

            .dropdown-toggle {
                font-size: 14px;
            }

            .dropdown-item {
                font-size: 14px;
            }
        }
    </style>
    <div class="container-fluid page-body">

        <div class="row">
            <div class="col-md-12 dashboard-body">
                <div class="dashboard-content">
                    <h1><b><i class="fas fa-wallet me-2"></i> Manage Expenses</b></h1>
                    <div class="mt-4">
                        <button id="uploadWholeDataButton" class="btn btn-success">
                            <i class="fa-solid fa-upload"></i> Upload Multiple Data
                        </button>

                        <button id="deleteMultipleButton" class="btn btn-danger ms-2">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>


                    <div class="mt-2 position-relative manage-expenses">
                        <h5>
                            <div class="position-relative">

                                <label style="margin-right: 2rem;">
                                    <input type="radio" name="selection" value="business" id="businessRadio" checked>
                                    <i class="fas fa-briefcase me-2"></i> <strong>Business</strong>
                                </label>

                                <label style="margin-right: 2rem;" for="branchRadio"
                                    onclick="window.location.href='manageexpenses_branch.php'">
                                    <input type="radio" name="selection" value="branch" id="branchRadio">
                                    <i class="fas fa-store me-2"></i> <strong>Branch</strong>
                                </label>

                                <label for="typesRadio" onclick="window.location.href='manageexpense_types.php'">
                                    <input type="radio" name="selection" value="branch" id="typesRadio">
                                    <i class="fa-solid fa-money-check-dollar"></i><strong> Expense Types</strong>
                                </label>
                            </div>



                        </h5>
                    </div>

                </div>


                <div id="businessPanel" class="mt-3">
                    <div class="form-group">
                        <label for="businessSelect"><i class="fas fa-briefcase me-2"></i></label>
                        <select id="businessSelect" class="form-control">
                            <option value=""><strong>Select Business</strong></option>
                            <?php foreach ($businesses as $id => $name): ?>
                                <option value="<?php echo $id; ?>"><?php echo $name; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <script>
                    const businesses = <?php echo json_encode($businesses); ?>;
                    const ownerId = <?php echo json_encode($_SESSION['user_id']); ?>;
                </script>


                <div id="expensesPanel" class="collapse scrollable-table" style="padding:0 2rem;">

                    <div class="d-flex justify-content-between align-items-center mt-4">

                        <div class="w-50">
                            <h2>Expenses List for <span id="businessName"></span> for the month of
                                <span id="currentMonthYear"></span>
                                <i class="fas fa-info-circle"
                                    onclick="showInfo('Business Expenses', 
                                    'Business expenses are the costs a company incurs to keep things running, like rent, salaries, supplies, and marketing. They are essential for day-to-day operations and can often be deducted from taxes.');">
                                </i>
                            </h2>
                        </div>

                        <button class="btn btn-success ms-auto m-1" id="addExpenseBtn" type="button">
                            <i class="fas fa-plus me-2"></i> Add Expenses
                        </button>

                        <div class="dropdown">
                            <button class="btn btn-secondary dropdown-toggle" type="button" id="monthDropdown"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                Select Month
                            </button>

                            <ul class="dropdown-menu" aria-labelledby="monthDropdown" id="monthDropdownMenu">
                                <li><a class="dropdown-item" data-value="1" href="#">January</a></li>
                                <li><a class="dropdown-item" data-value="2" href="#">February</a></li>
                                <li><a class="dropdown-item" data-value="3" href="#">March</a></li>
                                <li><a class="dropdown-item" data-value="4" href="#">April</a></li>
                                <li><a class="dropdown-item" data-value="5" href="#">May</a></li>
                                <li><a class="dropdown-item" data-value="6" href="#">June</a></li>
                                <li><a class="dropdown-item" data-value="7" href="#">July</a></li>
                                <li><a class="dropdown-item" data-value="8" href="#">August</a></li>
                                <li><a class="dropdown-item" data-value="9" href="#">September</a></li>
                                <li><a class="dropdown-item" data-value="10" href="#">October</a></li>
                                <li><a class="dropdown-item" data-value="11" href="#">November</a></li>
                                <li><a class="dropdown-item" data-value="12" href="#">December</a></li>
                            </ul>
                        </div>
                    </div>

                    <table class="table table-striped table-hover mt-4" id="expensesListTable">
                        <thead class="table-dark position-sticky top-0">
                            <tr>
                                <th>Type <button class="btn text-white"><i class="fas fa-sort"></i></button></th>
                                <th>Description <button class="btn text-white"><i class="fas fa-sort"></i></button></th>
                                <th>Amount <button class="btn text-white"><i class="fas fa-sort"></i></button></th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>

                        <tbody id="expensesList">
                            <script src="../js/business_expenses.js"></script>
                        </tbody>

                    </table>


                    <button class="btn btn-primary mt-2 mb-5" id="expensesListTable"
                        onclick="printContent('expensesPanel', 'Expenses List Report for <?php echo $name; ?>')">
                        <i class="fas fa-print me-2"></i> Generate Report (Expenses List)
                    </button>

                </div>
            </div>
        </div>
    </div>
    </div>

    <script>
        document.getElementById('uploadWholeDataButton').addEventListener('click', function () {
            Swal.fire({
                title: 'Upload or Download Data',
                html: `
              <div class="mt-3 mb-3 position-relative">
    <form action="../import_expenses.php" method="POST" enctype="multipart/form-data"
        class="btn btn-success p-3">
        <i class="fa-solid fa-upload"></i>
        <label for="file" class="mb-2">Upload Data:</label>
        <input type="file" name="file" id="file" accept=".xlsx, .xls" class="form-control mb-2">
        <input type="submit" value="Upload Excel" class="form-control">
    </form>
    <div class="d-flex justify-content-center mt-2">
        <button class="btn btn-info me-2" id="instructionsButton">
            <i class="fa-solid fa-info-circle"></i>
        </button>
        <form action="../export_expense_excel.php" method="POST">
            <button class="btn btn-success" type="submit">
                <i class="fa-solid fa-download"></i> Download Data Template
            </button>
        </form>
    </div>
    <div id="instructionsContainer" class="instructions-overlay d-none">
        <div class="instructions-content text-center">
            <img src="../assets/instructions/expenses.jpg" alt="Instructions Image" class="img-fluid instructions-img"
                id="instructionsImage">
        </div>
    </div>

</div>
                `,
                showConfirmButton: false, // Remove default confirmation button
                customClass: {
                    popup: 'swal2-modal-wide' // Optional for larger modals
                }
            });

            document.getElementById('instructionsButton').addEventListener('click', function () {
                document.getElementById('instructionsContainer').classList.remove('d-none');
            });

            document.getElementById('instructionsImage').addEventListener('click', function () {
                document.getElementById('instructionsContainer').classList.add('d-none');
            });
        });

        // Get the current month and year
        const currentDate = new Date();
        const currentMonth = currentDate.toLocaleString('default', {
            month: 'long'
        });
        const currentYear = currentDate.getFullYear();

        // Display the current month and year in the title
        document.getElementById('currentMonthYear').textContent = `${currentMonth} ${currentYear}`;

        // Update the business name when a business is selected
        document.getElementById('businessSelect').addEventListener('change', function () {
            const businessName = this.options[this.selectedIndex].text;
            document.getElementById('businessName').textContent = businessName;
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

        document.getElementById('deleteMultipleButton').addEventListener('click', function () {
            fetchData('expenses');
        });

        function fetchData(type, year = null, month = null) {
            Swal.fire({
                title: "Fetching Data...",
                text: "Please wait...",
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            let body = `type=${type}`;
            if (year) body += `&year=${year}`;
            if (month) body += `&month=${month}`;

            fetch("../endpoints/expenses/fetch_data.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: body
            })
                .then(response => response.json())
                .then(data => {
                    Swal.close();
                    if (data.length > 0) {
                        displayTable(type, data);
                    } else {
                        Swal.fire("No Data Found", "There is no data available for the selected type.", "info");
                    }
                })
                .catch(error => {
                    Swal.fire("Error", "Failed to fetch data. Please try again.", "error");
                });
        }

        function displayTable(type, data) {
            let table = `<table border='1' width='100%' style="border-collapse: collapse;">
        <tr style="background-color: #f8f9fa; font-weight: bold;">
            <th style="width: 10%;"><input type="checkbox" id="selectAll"> Select All</th>
            <th style="width: 5%;">ID</th>
            <th style="width: 15%;">Expense Type</th>
            <th style="width: 10%;">Amount</th>
            <th style="width: auto;">Description</th>
            <th style="width: 15%;">Created At</th>
            <th style="width: 15%;">Category</th>
            <th style="width: 15%;">Business/Branch</th>
        </tr>`;

            data.forEach(row => {
                let formattedAmount = new Intl.NumberFormat('en-PH', {
                    style: 'currency',
                    currency: 'PHP'
                }).format(row.amount);

                table += `<tr>
            <td><input type="checkbox" name="selectedItems" value="${row.id}"></td>
            <td>${row.id}</td>
            <td>${row.expense_type}</td>
            <td>${formattedAmount}</td>
            <td>${row.description}</td>
            <td>${row.created_at}</td>
            <td>${row.category}</td>
            <td>${row.category === 'business' ? row.business_name : row.branch_location}</td>
        </tr>`;
            });

            table += "</table>";

            // Add filter options
            let filterOptions = `<div style="display: flex; justify-content: flex-end; margin-bottom: 10px;">
        <select id="filterYear" style="margin-right: 10px;">
            <option value="">Select Year</option>
            ${getYearOptions()}
        </select>
        <select id="filterMonth" style="margin-right: 10px;">
            <option value="">Select Month</option>
            ${getMonthOptions()}
        </select>
        <button id="applyFilter">Apply Filter</button>
    </div>`;

            Swal.fire({
                title: `${type.charAt(0).toUpperCase() + type.slice(1)} Data`,
                html: filterOptions + table,
                width: '80%',
                showCancelButton: true,
                confirmButtonText: "Delete Selected",
                cancelButtonText: "Cancel",
                didOpen: () => {
                    // Add event listener for "Select All" checkbox
                    document.getElementById('selectAll').addEventListener('change', function () {
                        let checkboxes = document.querySelectorAll('input[name="selectedItems"]');
                        checkboxes.forEach(checkbox => {
                            checkbox.checked = this.checked;
                        });
                        this.nextSibling.textContent = this.checked ? "Unselect All" : "Select All";
                    });

                    // Add event listener for filter button
                    document.getElementById('applyFilter').addEventListener('click', function () {
                        let year = document.getElementById('filterYear').value;
                        let month = document.getElementById('filterMonth').value;
                        fetchData(type, year, month);
                    });
                },
                preConfirm: () => {
                    const selectedItems = [];
                    document.querySelectorAll('input[name="selectedItems"]:checked').forEach(checkbox => {
                        selectedItems.push(checkbox.value);
                    });
                    if (selectedItems.length === 0) {
                        Swal.showValidationMessage("Please select at least one item to delete.");
                        return false;
                    }
                    return selectedItems;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: "Are you sure?",
                        text: "This action cannot be undone!",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonText: "Yes, delete it!",
                        cancelButtonText: "Cancel"
                    }).then((confirmResult) => {
                        if (confirmResult.isConfirmed) {
                            deleteData(type, result.value);
                        }
                    });
                }
            });
        }

        function deleteData(type, selectedItems) {
            Swal.fire({
                title: "Deleting Data...",
                text: "Please wait...",
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch("../endpoints/expenses/delete_data.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: `type=${type}&ids=${selectedItems.join(',')}`
            })
                .then(response => response.json())
                .then(data => {
                    Swal.close();
                    if (data.success) {
                        Swal.fire("Success", "Selected items have been deleted.", "success");
                    } else {
                        Swal.fire("Error", "Failed to delete items. Please try again.", "error");
                    }
                })
                .catch(error => {
                    Swal.fire("Error", "Failed to delete items. Please try again.", "error");
                });
        }

        function getYearOptions() {
            let currentYear = new Date().getFullYear();
            let options = '';
            for (let i = currentYear; i >= currentYear - 10; i--) {
                options += `<option value="${i}">${i}</option>`;
            }
            return options;
        }

        function getMonthOptions() {
            const months = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
            let options = '';
            months.forEach((month, index) => {
                options += `<option value="${index + 1}">${month}</option>`;
            });
            return options;
        }
    </script>

    <script src="../js/print_report.js"></script>

    <script src="../js/sidebar.js"></script>
    <script src="../js/sort_items.js"></script>
    <script src="../js/show_info.js"></script>

</body>

</html>