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

    <div class="container-fluid page-body">

        <div class="row">
            <div class="col-md-12 dashboard-body">
                <div class="dashboard-content">
                    <h1><b><i class="fas fa-wallet me-2"></i> Manage Expenses</b></h1>
                    <button id="uploadWholeDataButton" class="btn btn-success mt-2">
                        <i class="fa-solid fa-upload"></i> Upload Multiple Data
                    </button>
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
                    <form action="../import_expense_excel_branch.php" method="POST" enctype="multipart/form-data" class="btn btn-success p-3">
                        <i class="fa-solid fa-upload"></i>
                        <label for="file" class="mb-2">Upload Data:</label>
                        <input type="file" name="file" id="file" accept=".xlsx, .xls" class="form-control mb-2">
                        <input type="submit" value="Upload Excel" class="form-control">
                    </form>
                    <form action="../export_expense_excel.php" method="POST" class="top-0 end-0 mt-2 me-2">
                        <button class="btn btn-success" type="submit">
                            <i class="fa-solid fa-download"></i> Download Data Template
                        </button>
                    </form>
                </div>
                `,
                showConfirmButton: false, // Remove default confirmation button
                customClass: {
                    popup: 'swal2-modal-wide' // Optional for larger modals
                }
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
    </script>

    <script src="../js/print_report.js"></script>

    <script src="../js/sidebar.js"></script>
    <script src="../js/sort_items.js"></script>
    <script src="../js/show_info.js"></script>

</body>

</html>