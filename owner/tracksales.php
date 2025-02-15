<?php
session_start();
require_once '../conn/auth.php';
require_once '../conn/conn.php';
validateSession('owner');

$owner_id = $_SESSION['user_id'];

// Set the timezone to Philippine Time (Asia/Manila)
date_default_timezone_set('Asia/Manila');
$today = date("Y-m-d");

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

// Fetch products and today's sales for each business
$products_by_business = [];
$sales_by_business = [];

if (!empty($businesses)) {
    $query = "
    SELECT 
        p.id AS product_id, 
        p.name AS product_name, 
        p.price, 
        p.business_id,
        s.quantity, 
        s.total_sales, 
        s.date,
        CASE 
            WHEN s.type = 'branch' THEN b.location
            WHEN s.type = 'business' THEN bu.name
        END AS business_or_branch_name
    FROM products p
    LEFT JOIN sales s ON p.id = s.product_id AND s.date = ? 
    LEFT JOIN branch b ON s.branch_id = b.id
    LEFT JOIN business bu ON p.business_id = bu.id
    WHERE p.business_id IN (" . implode(",", array_keys($businesses)) . ")";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $today); // Bind the current date
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $products_by_business[$row['business_id']][] = [
            'id' => $row['product_id'],
            'name' => $row['product_name'],
            'price' => $row['price']
        ];
        if (!empty($row['quantity'])) {
            $sales_by_business[$row['business_id']][] = [
                'product_name' => $row['product_name'],
                'business_or_branch_name' => $row['business_or_branch_name'], // Add this field
                'quantity' => $row['quantity'],
                'total_sales' => $row['total_sales'],
                'date' => $row['date']
            ];
        }
    }

    $stmt->close();
}

// Fetch today's sales for all businesses
$sales_data = [];
if (!empty($businesses)) {
    $query = "
    SELECT 
    p.name AS product_name,
    p.price AS product_price,
    s.quantity,
    s.total_sales,
    b.name AS business_name,
    CASE 
        WHEN s.type = 'branch' THEN br.location
        ELSE b.name
    END AS business_or_branch_name,
    s.type,
    s.date
FROM sales s
JOIN products p ON s.product_id = p.id
JOIN business b ON p.business_id = b.id
LEFT JOIN branch br ON s.branch_id = br.id
WHERE b.owner_id = ? AND s.date = ?

";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $owner_id, $today);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $sales_data[] = $row;
    }
    $stmt->close();
}
?>

<script>
    const businesses = <?php echo json_encode($businesses); ?>;
    const productsByBusiness = <?php echo json_encode($products_by_business); ?>;
    const salesByBusiness = <?php echo json_encode($sales_by_business); ?>;
</script>

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
                    <h1><b><i class="fas fa-chart-line me-2"></i> Track Sales</b></h1>

                    <!-- Search Bar for Sales -->
                    <div class="mt-5 d-flex justify-content-between align-items-center gap-2">
                        <!-- Search Bar -->
                        <form class="d-flex flex-grow-1" role="search">
                            <input id="saleSearchBar" class="form-control w-50" type="search"
                                placeholder="Search sale.." aria-label="Search" onkeyup="searchSales()">
                        </form>

                        <!-- Buttons Container -->
                        <div class="d-flex gap-2">
                            <button class="btn btn-success" type="button" id="addSaleButton">
                                <i class="fas fa-plus me-2"></i> Add Sale
                            </button>

                            <button id="uploadDataButton" class="btn btn-success">
                                <i class="fa-solid fa-upload"></i> Upload Data
                            </button>

                        </div>
                    </div>


                    <!-- Business Selection Dropdown -->
                    <div class="mt-4">
                        <div class="form-group">
                            <label for="businessSelect"><i class="fas fa-briefcase me-2"></i> Select Business</label>
                            <select id="businessSelect" class="form-control">
                                <option value=""><strong>Select Business</strong></option>
                                <?php foreach ($businesses as $id => $name): ?>
                                    <option value="<?php echo $id; ?>"><?php echo $name; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Sales Tables (Initially hidden) -->
                    <div id="salesContainer" style="display: none;"></div>





                    <h2 class="mt-5 mb-3"><b>Sales Log</b></h2>

                    <div id="salesLogTableSection">

                        <table class="table table-striped table-hover" id="salesLogTable">

                            <div class="mt-4 mb-4 position-relative">
                                <form class="d-flex" role="search">
                                    <input id="saleSearchBar" class="form-control me-2 w-50" type="search"
                                        placeholder="Search branch.." aria-label="Search" style="visibility:hidden;">
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


                            <thead class="table-dark">
                                <tr>
                                    <th>Product</th>
                                    <th>Business/Branch</th>
                                    <th>Amount Sold</th>
                                    <th>Total Sales</th>
                                    <th>Date</th>
                                </tr>
                            </thead>

                            <tbody>

                            </tbody>



                        </table>

                        <button class="btn btn-primary mt-2 mb-5" id="salesLogTable"
                            onclick="printContent('salesLogTableSection', 'Sales Log Report')">
                            <i class="fas fa-print me-2"></i> Print Report (Sales Log)
                        </button>

                    </div>

                </div>
            </div>
        </div>
    </div>
    </div>

    <script>
        document.getElementById("uploadDataButton").addEventListener("click", function () {
            const selectedBusiness = document.getElementById("businessSelect").value;

            if (!selectedBusiness) {
                Swal.fire({
                    icon: "warning",
                    title: "No Business Selected",
                    text: "Please select a business first.",
                });
                return;
            }

            fetch("../endpoints/sales/fetch_branches.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ business_id: selectedBusiness }),
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        const branches = data.branches;
                        let branchOptions = '<option value="">Select a Branch</option>';
                        branches.forEach((branch) => {
                            branchOptions += `<option value="${branch.id}">${branch.location}</option>`;
                        });

                        const products = productsByBusiness[selectedBusiness] || [];
                        let productOptions = '<option value="">Select a Product</option>';
                        products.forEach((product) => {
                            productOptions += `<option value="${product.id}" data-price="${product.price}">${product.name} (₱${product.price})</option>`;
                        });

                        Swal.fire({
                            title: "Upload or Download Data",
                            html: `
                    <label for="branchSelect">Branch</label>
                    <select id="branchSelect" class="form-control mb-2">${branchOptions}</select>

                    <label for="productSelect">Product</label>
                    <select id="productSelect" class="form-control mb-2">${productOptions}</select>

                    <div class="mt-3 mb-3 position-relative">
                        <form action="../import_excel_display_sales.php" method="POST" enctype="multipart/form-data" class="btn btn-success p-3">
                            <i class="fa-solid fa-upload"></i>
                            <label for="file" class="mb-2">Upload Data:</label>
                            <input type="file" name="file" id="file" accept=".xlsx, .xls" class="form-control mb-2">
                            <input type="hidden" name="selectedBusiness" id="hiddenBusiness">
                            <input type="hidden" name="selectedBranch" id="hiddenBranch">
                            <input type="hidden" name="branch_id" id="hiddenBranchId">
                            <input type="hidden" name="business_id" id="hiddenBusinessId" value="${selectedBusiness}">
                            <input type="hidden" name="selectedProduct" id="hiddenProduct">
                            <input type="hidden" name="product_id" id="hiddenProductId">
                            <input type="hidden" name="productPrice" id="hiddenPrice">
                            <input type="submit" value="Upload Excel" class="form-control">
                        </form>

                        <form id="exportExcelForm" action="../export_excel_add_sales.php" method="POST" class="top-0 end-0 mt-2 me-2">
                            <input type="hidden" name="selectedBusiness" id="hiddenBusinessExport">
                            <input type="hidden" name="selectedBranch" id="hiddenBranchExport">
                            <input type="hidden" name="branch_id" id="hiddenBranchIdExport">
                            <input type="hidden" name="business_id" id="hiddenBusinessIdExport" value="${selectedBusiness}">
                            <input type="hidden" name="selectedProduct" id="hiddenProductExport">
                            <input type="hidden" name="product_id" id="hiddenProductIdExport">
                            <input type="hidden" name="productPrice" id="hiddenPriceExport">
                            <button class="btn btn-success" type="submit">
                                <i class="fa-solid fa-download"></i> Download Data Template
                            </button>
                        </form>
                    </div>
                    `,
                            showConfirmButton: false,
                            customClass: { popup: "swal2-modal-wide" }
                        });

                        // Listen for changes and set hidden input values before submitting form
                        document.getElementById("branchSelect").addEventListener("change", function () {
                            document.getElementById("hiddenBranch").value = this.options[this.selectedIndex].text;
                            document.getElementById("hiddenBranchExport").value = this.options[this.selectedIndex].text;
                            document.getElementById("hiddenBranchId").value = this.value;
                            document.getElementById("hiddenBranchIdExport").value = this.value;
                        });

                        document.getElementById("productSelect").addEventListener("change", function () {
                            document.getElementById("hiddenProduct").value = this.options[this.selectedIndex].text;
                            document.getElementById("hiddenProductExport").value = this.options[this.selectedIndex].text;
                            document.getElementById("hiddenProductId").value = this.value;
                            document.getElementById("hiddenProductIdExport").value = this.value;
                            document.getElementById("hiddenPrice").value = this.options[this.selectedIndex].getAttribute("data-price");
                            document.getElementById("hiddenPriceExport").value = this.options[this.selectedIndex].getAttribute("data-price");
                        });

                        // Set business name
                        const businessText = document.getElementById("businessSelect").options[document.getElementById("businessSelect").selectedIndex].text;
                        document.getElementById("hiddenBusiness").value = businessText;
                        document.getElementById("hiddenBusinessExport").value = businessText;
                        document.getElementById("hiddenBusinessId").value = selectedBusiness;
                        document.getElementById("hiddenBusinessIdExport").value = selectedBusiness;
                    } else {
                        Swal.fire("Error", data.message, "error");
                    }
                })
                .catch(() => {
                    Swal.fire("Error", "Failed to fetch branches.", "error");
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
    </script>
    <script src="../js/print_report.js"></script>

    <script src="../js/sidebar.js"></script>
    <script src="../js/sort_items.js"></script>

    <script src="../js/business_tracksales_filter.js"></script>
    <script src="../js/business_tracksales_add_sale.js"></script>

</body>

</html>