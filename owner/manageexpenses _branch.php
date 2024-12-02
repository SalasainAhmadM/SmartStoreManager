<?php
session_start();
require_once '../conn/conn.php';
require_once '../conn/auth.php';

validateSession('owner');

$owner_id = $_SESSION['user_id'];

// Fetch businesses owned by the logged-in user
$query = "SELECT DISTINCT b.id, b.name 
          FROM business b
          JOIN branch br ON b.id = br.business_id
          WHERE b.owner_id = ?";
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
                            <option value=""><strong>Select Business</strong></option>
                            <?php foreach ($businesses as $id => $name): ?>
                                <option value="<?php echo $id; ?>"><?php echo $name; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" id="branchGroup" style="display:none;">
                        <label for="branchSelect">Select Branch</label>
                        <select id="branchSelect" class="form-control">
                            <option value="">Select Branch</option>
                            <!-- Branch options will be populated dynamically -->
                        </select>
                    </div>

                    <script>
                        const businesses = <?php echo json_encode($businesses); ?>;
                        const ownerId = <?php echo json_encode($_SESSION['user_id']); ?>;
                    </script>


                    <div id="expensesPanel" class="collapse mt-5 scrollable-table">


                        <div class="d-flex justify-content-between align-items-center mt-4">

                            <div class="w-50">
                                <h2>Expenses List for <span id="businessName"></span> - Branch: <span
                                        id="branchName"></span>
                                    for the month of <span id="currentMonthYear"></span></h2>
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


                        <table class="table table-striped table-hover mt-4" id="expensesListTableBranch">
                            <thead class="table-dark position-sticky top-0">
                                <tr>
                                    <th>Type <button class="btn text-white"><i class="fas fa-sort"></i></button></th>
                                    <th>Description <button class="btn text-white"><i class="fas fa-sort"></i></button>
                                    </th>
                                    <th>Amount <button class="btn text-white"><i class="fas fa-sort"></i></button></th>
                                    <th class="text-center">Action <button class="btn text-white"><i
                                                class="fas fa-sort"></i></button></th>
                                </tr>
                            </thead>
                            <tbody id="expensesList">
                                <script src="../js/branch_expenses.js"></script>
                            </tbody>
                        </table>


                        <button class="btn btn-primary mt-2 mb-5" id="expensesListTableBranch"
                            onclick="printContent('expensesPanel', `Expenses List Report for ${document.getElementById('businessSelect').options[document.getElementById('businessSelect').selectedIndex].text} <br> Branch: ${document.getElementById('branchSelect').options[document.getElementById('branchSelect').selectedIndex].text || 'All Branches'} for the month of ${currentMonth} ${currentYear}`)">
                            <i class="fas fa-print me-2"></i> Print Report (Expenses List)
                        </button>


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
            document.getElementById('businessSelect').addEventListener('change', function () {
                const businessName = this.options[this.selectedIndex].text;
                const businessId = this.value;
                document.getElementById('businessName').textContent = businessName;

                // Update the branch name in the print preview title
                const branchName = document.getElementById('branchSelect').options[document.getElementById('branchSelect').selectedIndex].text || 'All Branches';
                document.getElementById('branchName').textContent = branchName;

                // Update the print report button to include the correct title in the onclick function
                const printButton = document.getElementById('expensesListTable');
                printButton.setAttribute('onclick', `printContent('expensesPanel', 'Expenses List for ${businessName} - Branch: ${branchName} for the month of ${currentMonth} ${currentYear}')`);
            });
        </script>


        <script>
        function getPrintReportTitle() {
        const businessSelect = document.getElementById('businessSelect');
        const branchSelect = document.getElementById('branchSelect');
        const currentMonth = new Date().toLocaleString('default', { month: 'long' }); // e.g., 'December'
        const currentYear = new Date().getFullYear(); // e.g., 2024

        const businessName = businessSelect.options[businessSelect.selectedIndex]?.text || 'All Businesses';
        const branchName = branchSelect.options[branchSelect.selectedIndex]?.text || 'All Branches';

        return `Expenses List Report for ${businessName} <br> Branch: ${branchName} for the month of ${currentMonth} ${currentYear}`;
        }
        </script>


        <script src="../js/print_report.js"></script>
        <script src="../js/sidebar.js"></script>
        <script src="../js/sort_items.js"></script>

</body>

</html>