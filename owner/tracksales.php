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
    p.size, 
    p.business_id,
    s.quantity, 
    s.total_sales, 
    s.date,
    CASE 
        WHEN s.type = 'branch' THEN b.location
        WHEN s.type = 'business' THEN bu.name
        ELSE 'Unknown'
    END AS business_or_branch_name,
    COALESCE(
        (SELECT pa.status 
         FROM product_availability pa 
         WHERE pa.product_id = p.id 
           AND pa.business_id = p.business_id
           AND pa.branch_id = ?
         LIMIT 1),  -- Prioritize branch-level availability
        (SELECT pa.status 
         FROM product_availability pa 
         WHERE pa.product_id = p.id 
           AND pa.business_id = p.business_id
           AND pa.branch_id IS NULL
         LIMIT 1),  -- Fallback to business-wide availability
        'Available'  -- Default to 'Available' if no record exists
    ) AS status
FROM products p
LEFT JOIN sales s ON p.id = s.product_id AND s.date = ? 
LEFT JOIN branch b ON s.branch_id = b.id
LEFT JOIN business bu ON p.business_id = bu.id
WHERE p.business_id IN (" . implode(",", array_keys($businesses)) . ")
HAVING status != 'Unavailable'";  // Exclude unavailable products dynamically

    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $selectedBranch, $today);
    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $products_by_business[$row['business_id']][] = [
            'id' => $row['product_id'],
            'name' => $row['product_name'],
            'size' => $row['size'],
            'price' => $row['price'],
            'status' => $row['status'] // Now correctly considers branch or business-wide availability
        ];
        if (!empty($row['quantity'])) {
            $sales_by_business[$row['business_id']][] = [
                'product_name' => $row['product_name'],
                'business_or_branch_name' => $row['business_or_branch_name'],
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
    <!-- <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script> -->

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
                    <div class="mt-4">
                        <button id="uploadWholeDataButton" class="btn btn-success">
                            <i class="fa-solid fa-upload"></i> Upload Multiple Data
                        </button>

                        <button style='height: 38px' id="deleteMultipleButton" class="btn btn-danger ms-2">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                    <!-- Search Bar for Sales -->
                    <div class="mt-2 d-flex justify-content-between align-items-center gap-2">
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





                    <h2 class="mt-5 mb-3"><b>Sales Report</b> <i class="fas fa-info-circle"
                            onclick="showInfo('Sales Report', 
                        'A sales report is a record of all transactions, tracking what was sold, when, and for how much. It helps businesses monitor revenue, analyze trends, and keep finances organized.');">
                        </i>
                    </h2>

                    <div id="salesLogTableSection">

                        <table class="table table-striped table-hover" id="salesLogTable">

                            <div class="mt-4 mb-4 position-relative">
                                <form class="d-flex" role="search">
                                    <input id="saleSearchBar" class="form-control me-2 w-50" type="search"
                                        placeholder="Search branch.." aria-label="Search" style="visibility:hidden;">
                                </form>
                                <!-- Date Filter Button for Sales Report -->
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
                            onclick="printContent('salesLogTableSection', 'Sales Report')">
                            <i class="fas fa-print me-2"></i> Generate Report (Sales Report)
                        </button>

                    </div>

                </div>
            </div>
        </div>
    </div>
    </div>

    <script>

        document.getElementById("uploadWholeDataButton").addEventListener("click", function () {
            // Fetch businesses owned by the logged-in user
            fetch('../endpoints/sales/get_businesses.php')
                .then(response => response.json())
                .then(data => {
                    // Generate options for the business dropdown
                    let businessOptions = '<option value="" disabled selected>Select Business</option>';
                    data.forEach(business => {
                        businessOptions += `<option value="${business.id}">${business.name}</option>`;
                    });

                    // Display the SweetAlert modal with the populated business dropdown
                    Swal.fire({
                        title: 'Upload or Download Data',
                        html: `
                    <label for="businessSalesSelect">Business</label>
                    <select id="businessSalesSelect" class="form-control mb-2">${businessOptions}</select>
                    <div class="mt-3 mb-3 position-relative">
                        <form id="uploadForm" action="../import_excel_display_whole_sales.php" method="POST" enctype="multipart/form-data" class="btn btn-success p-3">
                            <i class="fa-solid fa-upload"></i>
                            <label for="file" class="mb-2">Upload Data:</label>
                            <input type="file" name="file" id="file" accept=".xlsx, .xls" class="form-control mb-2">
                            <input type="submit" value="Upload Excel" class="form-control">
                        </form>
                       

                        <div class="d-flex justify-content-center mt-2">
        <button style='height: 38px' class="btn btn-info me-2" id="instructionsButton">
            <i class="fa-solid fa-info-circle"></i>
        </button>
         <form id="downloadForm" action="../export_excel_add_whole_sales.php" method="POST">
                            <input type="hidden" name="business_id" id="hiddenBusinessId" value="">
                            <button class="btn btn-success" type="submit" id="downloadButton" disabled>
                                <i class="fa-solid fa-download"></i> Download Data Template
                            </button>
                        </form>
    </div>
    <div id="instructionsContainer" class="instructions-overlay d-none">
        <div class="instructions-content text-center">
            <img src="../assets/instructions/salesmultiple.jpg" alt="Instructions Image" class="img-fluid instructions-img"
                id="instructionsImage">
        </div>
    </div>
                    </div>
                `,
                        showConfirmButton: false, // Remove default confirmation button
                        customClass: {
                            popup: 'swal2-modal-wide' // Optional for larger modals
                        },
                        didOpen: () => {
                            // Add an event listener to the business dropdown to update the hidden input
                            const businessSalesSelect = document.getElementById('businessSalesSelect');
                            const hiddenBusinessId = document.getElementById('hiddenBusinessId');
                            const downloadButton = document.getElementById('downloadButton');

                            businessSalesSelect.addEventListener('change', () => {
                                hiddenBusinessId.value = businessSalesSelect.value;
                                downloadButton.disabled = false; // Enable download button once a business is selected
                            });

                            // Initially disable the download button
                            downloadButton.disabled = true;
                        }
                    });
                    document.getElementById('instructionsButton').addEventListener('click', function () {
                        document.getElementById('instructionsContainer').classList.remove('d-none');
                    });

                    document.getElementById('instructionsImage').addEventListener('click', function () {
                        document.getElementById('instructionsContainer').classList.add('d-none');
                    });
                })
                .catch(error => {
                    console.error('Error fetching businesses:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to fetch businesses. Please try again.',
                    });
                });
        });



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

                        const products = (productsByBusiness[selectedBusiness] || []).filter(product => product.status !== 'Unavailable');
                        let productOptions = '<option value="">Select a Product</option>';
                        const uniqueProducts = new Set();

                        products.forEach((product) => {
                            const productKey = `${product.id}-${product.name}-${product.size}-${product.price}`;
                            if (!uniqueProducts.has(productKey) && product.status !== 'Unavailable') {
                                uniqueProducts.add(productKey);
                                productOptions += `<option value="${product.id}" data-price="${product.price}">${product.name} - ${product.size} (₱${product.price})</option>`;
                            }
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

                          

                              <div style='height: 38px' class="d-flex justify-content-center mt-2">
        <button class="btn btn-info me-2" id="instructionsButton">
            <i class="fa-solid fa-info-circle"></i>
        </button>
         <form id="exportExcelForm" action="../export_excel_add_sales.php" method="POST" >
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
    <div id="instructionsContainer" class="instructions-overlay d-none">
        <div class="instructions-content text-center">
            <img src="../assets/instructions/sales.jpg" alt="Instructions Image" class="img-fluid instructions-img"
                id="instructionsImage">
        </div>
    </div>
                        </div>
                    `,
                            showConfirmButton: false,
                            customClass: { popup: "swal2-modal-wide" }
                        });
                        document.getElementById('instructionsButton').addEventListener('click', function () {
                            document.getElementById('instructionsContainer').classList.remove('d-none');
                        });

                        document.getElementById('instructionsImage').addEventListener('click', function () {
                            document.getElementById('instructionsContainer').classList.add('d-none');
                        });
                        // Listen for changes and set hidden input values before submitting form
                        document.getElementById("branchSelect").addEventListener("change", function () {
                            const selectedBranch = this.value;
                            document.getElementById("hiddenBranch").value = this.options[this.selectedIndex].text;
                            document.getElementById("hiddenBranchExport").value = this.options[this.selectedIndex].text;
                            document.getElementById("hiddenBranchId").value = this.value;
                            document.getElementById("hiddenBranchIdExport").value = this.value;

                            // Fetch product availability status for the selected branch
                            fetch("../endpoints/sales/fetch_product_availability.php", {
                                method: "POST",
                                headers: { "Content-Type": "application/json" },
                                body: JSON.stringify({ business_id: selectedBusiness, branch_id: selectedBranch }),
                            })
                                .then((response) => response.json())
                                .then((data) => {
                                    if (data.success) {
                                        const products = data.products;
                                        let productOptions = '<option value="">Select a Product</option>';
                                        products.forEach((product) => {
                                            if (product.status !== 'Unavailable') {
                                                productOptions += `<option value="${product.id}" data-price="${product.price}">${product.name} - ${product.size} (₱${product.price})</option>`;
                                            }
                                        });
                                        document.getElementById("productSelect").innerHTML = productOptions;
                                    } else {
                                        Swal.fire("Error", data.message, "error");
                                    }
                                })
                                .catch(() => {
                                    Swal.fire("Error", "Failed to fetch product availability.", "error");
                                });
                        });

                        document.getElementById("productSelect").addEventListener("change", function () {
                            document.getElementById("hiddenProduct").value = this.options[this.selectedIndex].text;
                            document.getElementById("hiddenProductExport").value = this.options[this.selectedIndex].text;
                            // document.getElementById("hiddenProductId").value = this.value;
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


        document.getElementById('deleteMultipleButton').addEventListener('click', function () {
            fetchData('sales');
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

            fetch("../endpoints/sales/fetch_data.php", {
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
                        Swal.fire("No Data Found", "There is no data available for the selected filters.", "info");
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
            <th style="width: 10%;">Quantity</th>
            <th style="width: 10%;">Total Sales</th>
            <th style="width: 15%;">Date</th>
            <th style="width: 15%;">Product</th>
            <th style="width: 15%;">Type</th>
            <th style="width: 20%;">Business/Branch</th>
        </tr>`;

            data.forEach(row => {
                let formattedTotalSales = new Intl.NumberFormat('en-PH', {
                    style: 'currency',
                    currency: 'PHP'
                }).format(row.total_sales);

                table += `<tr>
            <td><input type="checkbox" name="selectedItems" value="${row.id}"></td>
            <td>${row.id}</td>
            <td>${row.quantity}</td>
            <td>${formattedTotalSales}</td>
            <td>${row.date}</td>
            <td>${row.product_name}</td>
            <td>${row.type}</td>
            <td>${row.type === 'business' ? row.business_name : row.branch_location}</td>
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

            fetch("../endpoints/sales/delete_data.php", {
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

    <script src="../js/business_tracksales_filter.js"></script>
    <script src="../js/business_tracksales_add_sale.js"></script>
    <script src="../js/show_info.js"></script>
</body>

</html>