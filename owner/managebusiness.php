<?php
session_start();
require_once '../conn/conn.php';
require_once '../conn/auth.php';

validateSession('owner');

$owner_id = $_SESSION['user_id'];

// Fetch only approved business data for the logged-in owner
$query = "SELECT * FROM business WHERE owner_id = ? AND is_approved = 1";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$result = $stmt->get_result();

$businesses = [];
while ($row = $result->fetch_assoc()) {
    $businesses[] = $row;
}
$stmt->close();


// Fetch branches for each business
$branches_by_business = [];
$branch_query = "SELECT * FROM branch WHERE business_id = ? AND is_approved = 1";
$branch_stmt = $conn->prepare($branch_query);

foreach ($businesses as $business) {
    $branch_stmt->bind_param("i", $business['id']);
    $branch_stmt->execute();
    $branch_result = $branch_stmt->get_result();

    while ($branch_row = $branch_result->fetch_assoc()) {
        $branches_by_business[$business['id']][] = $branch_row;
    }
}

$branch_stmt->close();

// Fetch products for each business
$products_by_business = [];
$product_query = "SELECT * FROM products WHERE business_id = ?";
$product_stmt = $conn->prepare($product_query);

foreach ($businesses as $business) {
    $product_stmt->bind_param("i", $business['id']);
    $product_stmt->execute();
    $product_result = $product_stmt->get_result();

    while ($product_row = $product_result->fetch_assoc()) {
        $products_by_business[$business['id']][] = $product_row;
    }
}

$product_stmt->close();

// Fetch unique product types
$type_query = "SELECT DISTINCT type FROM products";
$type_stmt = $conn->prepare($type_query);
$type_stmt->execute();
$type_result = $type_stmt->get_result();

$product_types = [];
while ($row = $type_result->fetch_assoc()) {
    $product_types[] = $row['type'];
}
$type_stmt->close();

// Define size options for different categories
$size_options = [
    'Clothing' => ['XS', 'S', 'M', 'L', 'XL', 'XXL'],
    'Shoes' => ['5', '6', '7', '8', '9', '10', '11', '12'],
    'Food' => ['Small (250g)', 'Medium (500g)', 'Large (1kg)'],
    // Add more categories and sizes as needed
];
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
        .permit-modal-container .swal2-popup {
            max-height: 80vh;
            overflow: hidden;
        }

        @media (max-width: 767.98px) {
            .container-fluid {
                padding: 0 15px;
            }

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

            .nav-pills {
                flex-wrap: wrap;
            }

            .nav-pills .nav-item {
                flex: 1 1 auto;
                margin-bottom: 10px;
            }

            .nav-pills .nav-link {
                text-align: center;
                padding: 10px;
                font-size: 14px;
            }

            .nav-pills .nav-link h5 {
                margin: 0;
                font-size: 16px;
            }

            .tab-content {
                margin-top: 20px;
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

            .form-control {
                width: 100%;
                padding: 0.375rem 0.75rem;
                font-size: 1rem;
                line-height: 1.5;
                border: 1px solid #ced4da;
                border-radius: 0.25rem;
            }

            .position-relative {
                position: relative;
            }

            .position-absolute {
                position: absolute;
            }

            .top-0 {
                top: 0;
            }

            .end-0 {
                right: 0;
            }

            .mt-2 {
                margin-top: 0.5rem;
            }

            .me-2 {
                margin-right: 0.5rem;
            }

            .mb-5 {
                margin-bottom: 3rem;
            }

            .text-center {
                text-align: center;
            }

            .text-primary {
                color: #007bff !important;
            }

            .text-danger {
                color: #dc3545 !important;
            }

            .text-success {
                color: #198754 !important;
            }

            .dashboard-content h1 {
                font-size: 20px;
            }

            .nav-pills .nav-link h5 {
                font-size: 14px;
            }

            .nav-pills .nav-link {
                font-size: 12px;
                padding: 8px;
            }

            .table th,
            .table td {
                padding: 0.5rem;
            }

            .btn {
                padding: 0.25rem 0.5rem;
                font-size: 0.875rem;
            }

            .form-control {
                font-size: 14px;
            }

            .position-absolute {
                position: static;
                margin-top: 10px;
            }

            .w-50 {
                width: 100% !important;
            }

            .d-flex {
                align-items: flex-start;
            }

            .ms-auto {
                margin-left: 0 !important;
                margin-top: 10px;
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

            .page-body {
                display: flex;
                flex-direction: column;
            }

            .dashboard-body {
                order: 2;
            }

            .sidebar {
                order: 1;
                width: 100%;
                position: static;
            }
        }

        @media (max-width: 575.98px) {
            .dashboard-content h1 {
                font-size: 18px;
            }

            .nav-pills .nav-link h5 {
                font-size: 12px;
            }

            .nav-pills .nav-link {
                font-size: 10px;
                padding: 6px;
            }

            .table th,
            .table td {
                padding: 0.375rem;
            }

            .btn {
                padding: 0.2rem 0.4rem;
                font-size: 0.75rem;
            }

            .form-control {
                font-size: 12px;
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
        }
    </style>
    <div class="container-fluid page-body">
        <div class="row">
            <div class="col-md-12 dashboard-body">
                <div class="dashboard-content">
                    <h1><b><i class="fas fa-cogs me-2"></i> Manage Business </b></h1>

                    <div class="mt-4">
                        <button id="uploadDataButton" class="btn btn-success">
                            <i class="fa-solid fa-upload"></i> Upload Multiple Data
                        </button>

                        <button id="deleteMultipleButton" class="btn btn-danger ms-2">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>

                    <ul class="nav nav-pills nav-fill mt-4">
                        <li class="nav-item">
                            <a class="nav-link active" data-tab="businesslist">
                                <i class="fas fa-list me-2"></i>
                                <h5><b>Business List</b></h5>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-tab="branchlist">
                                <i class="fas fa-building me-2"></i>
                                <h5><b>Branch List</b></h5>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-tab="manageproduct">
                                <i class="fas fa-box-open me-2"></i>
                                <h5><b>Manage Product</b></h5>
                            </a>
                        </li>
                    </ul>

                    <div id="businesslist" class="tab-content active">
                        <h1 class="mt-5"></h1>

                        <!-- Search Bar -->
                        <div class="mt-4 mb-4 position-relative">
                            <form class="d-flex" role="search" id="search-form">
                                <input class="form-control me-2 w-50" id="search-business" type="search"
                                    placeholder="Search business..." aria-label="Search">
                                <ul id="suggestion-box" class="list-group position-absolute w-50"></ul>
                            </form>
                            <!-- Add Business Button -->
                            <button id="add-business-btn"
                                class="btn btn-success position-absolute top-0 end-0 mt-2 me-2" type="button">
                                <i class="fas fa-plus me-2"></i> Add Business
                            </button>
                        </div>


                        <h4 class="mb-3">Business List <i class="fas fa-info-circle"
                                onclick="showInfo('Business List', 'This table displays all businesses owned by you, including their details such as asset size and employee count.');"></i>
                        </h4>
                        <div class="mb-3">
                            <label for="monthFilter"><b>Filter by Month:</b></label>
                            <select id="monthFilter" class="form-control" onchange="filterProductsByMonthAndYear()">
                                <option value="0">All Time</option>
                                <option value="1">January</option>
                                <option value="2">February</option>
                                <option value="3">March</option>
                                <option value="4">April</option>
                                <option value="5">May</option>
                                <option value="6">June</option>
                                <option value="7">July</option>
                                <option value="8">August</option>
                                <option value="9">September</option>
                                <option value="10">October</option>
                                <option value="11">November</option>
                                <option value="12">December</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="yearFilter"><b>Filter by Year:</b></label>
                            <select id="yearFilter" class="form-control" onchange="filterProductsByMonthAndYear()">
                                <option value="0">All Years</option>
                                <!-- Years will be dynamically populated here -->
                            </select>
                        </div>
                        <div class="col-md-12 mb-5 scrollable-table">
                            <table class="table table-striped table-hover mt-4" id="businessTable">
                                <thead class="table-dark position-sticky top-0">
                                    <tr>
                                        <th scope="col">Name <button class="btn text-white"><i
                                                    class="fas fa-sort"></i></button></th>
                                        <th scope="col">Description <button class="btn text-white"><i
                                                    class="fas fa-sort"></i></button></th>
                                        <th scope="col">Asset Size <button class="btn text-white"><i
                                                    class="fas fa-sort"></i></button></th>
                                        <th scope="col">Employee Count <button class="btn text-white"><i
                                                    class="fas fa-sort"></i></button></th>
                                        <th scope="col">Location <button class="btn text-white"><i
                                                    class="fas fa-sort"></i></button></th>
                                        <th scope="col">Permit <button class="btn text-white"><i
                                                    class="fas fa-sort"></i></button></th>
                                        <th scope="col">Created At <button class="btn text-white"><i
                                                    class="fas fa-sort"></i></button></th>
                                        <th scope="col">Updated At <button class="btn text-white"><i
                                                    class="fas fa-sort"></i></button></th>
                                        <th class="text-center" scope="col">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="business-table-body">
                                    <?php if (!empty($businesses)): ?>
                                        <?php foreach ($businesses as $business): ?>
                                            <tr data-id="<?php echo $business['id']; ?>" data-type="business">
                                                <td class="business-name"><?php echo htmlspecialchars($business['name']); ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($business['description']); ?></td>
                                                <td><?php echo htmlspecialchars($business['asset']); ?></td>
                                                <td><?php echo htmlspecialchars($business['employee_count']); ?></td>
                                                <td><?php echo htmlspecialchars($business['location']); ?></td>
                                                <td>
                                                    <?php if (!empty($business['business_permit'])): ?>
                                                        <a href="#" class="view-permit"
                                                            data-permit="<?php echo htmlspecialchars($business['business_permit']); ?>"
                                                            data-type="<?php echo pathinfo($business['business_permit'], PATHINFO_EXTENSION) === 'pdf' ? 'pdf' : 'image'; ?>">
                                                            View Permit
                                                        </a>
                                                    <?php else: ?>
                                                        No Permit
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($business['created_at']); ?></td>
                                                <td><?php echo htmlspecialchars($business['updated_at']); ?></td>
                                                <td class="text-center">
                                                    <a href="#" class="edit-button text-primary me-3" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="#" class="delete-button text-danger me-3" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                    <a href="#" class="print-button text-primary me-3" title="Print"
                                                        data-id="<?php echo $business['id']; ?>" data-type="business">
                                                        <i class="fas fa-print"></i>
                                                    </a>
                                                    <a href="#" class="text-success" title="Edit Product Availability"
                                                        onclick="editProductAvailabilityBusiness(<?php echo $business['id']; ?>)">
                                                        <i class="fas fa-box"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="9" style="text-align: center;">No Business Found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>

                            <button class="btn btn-primary mt-2 mb-5"
                                onclick="printContent('businesslist', 'Business List Report')">
                                <i class="fas fa-print me-2"></i> Generate Report (Business List)
                            </button>

                        </div>
                    </div>

                    <div id="branchlist" class="tab-content">
                        <h1 class="mt-5"></h1>
                        <p class="mb-5">Detailed information about each business and its branches is available here.</p>


                        <div id="businesses">
                            <?php foreach ($businesses as $business): ?>
                                <div>
                                    <a class="btn btn-primary business card-one"
                                        onclick="toggleDetails('business<?php echo $business['id']; ?>')">
                                        <i class="fa-solid fa-building"></i>
                                        <?php echo htmlspecialchars($business['name']); ?>
                                        <i class="end-0 mt-2 me-2 fas fa-plus me-2"></i>
                                    </a>

                                    <div id="business<?php echo $business['id']; ?>" class="business-details card-one"
                                        style="display: none; margin-top: 10px;">
                                        <i class="fas fa-info-circle"
                                            onclick="showInfo('Branch List', 'A record of all business branches, including their locations, contact details, and management information. It helps keep track of multiple branches and their operations.');"></i>
                                        <p><strong>Business ID:</strong> <?php echo $business['id']; ?></p>
                                        <p><strong>Updated At:</strong> <?php echo $business['updated_at']; ?></p>


                                        <!-- Search Bar -->
                                        <div class="mt-4 mb-4 position-relative">
                                            <form class="d-flex" role="search">
                                                <input id="search-branch" class="form-control me-2 w-50" type="search"
                                                    placeholder="Search branch.." aria-label="Search">
                                            </form>

                                            <!-- Add Branch Button -->
                                            <button class="btn btn-success position-absolute top-0 end-0 mt-2 me-2"
                                                type="button" onclick="addBranch(<?php echo $business['id']; ?>)">
                                                <i class="fas fa-plus me-2"></i> Add Branch
                                            </button>

                                        </div>

                                        <div class="scrollable-table">
                                            <table class="table" id="branchTable">
                                                <thead class="table-dark position-sticky top-0">
                                                    <tr>
                                                        <th>Location <button class="btn text-white"><i
                                                                    class="fas fa-sort"></i></button></th>
                                                        <th>Permit <button class="btn text-white"><i
                                                                    class="fas fa-sort"></i></button></th>
                                                        <th>Created At <button class="btn text-white"><i
                                                                    class="fas fa-sort"></i></button></th>
                                                        <th>Updated At <button class="btn text-white"><i
                                                                    class="fas fa-sort"></i></button></th>
                                                        <th class="text-center">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (isset($branches_by_business[$business['id']])): ?>
                                                        <?php foreach ($branches_by_business[$business['id']] as $branch): ?>
                                                            <tr data-id="<?php echo $branch['id']; ?>" data-type="branch">
                                                                <td><?php echo htmlspecialchars($branch['location']); ?></td>
                                                                <td>
                                                                    <?php if (!empty($branch['business_permit'])): ?>
                                                                        <a href="#" class="view-permit-branch"
                                                                            data-permit="<?php echo htmlspecialchars($branch['business_permit']); ?>"
                                                                            data-type="<?php echo pathinfo($branch['business_permit'], PATHINFO_EXTENSION) === 'pdf' ? 'pdf' : 'image'; ?>">
                                                                            View Permit
                                                                        </a>
                                                                    <?php else: ?>
                                                                        No Permit
                                                                    <?php endif; ?>
                                                                </td>
                                                                <td><?php echo $branch['created_at']; ?></td>
                                                                <td><?php echo $branch['updated_at']; ?></td>
                                                                <td class="text-center">
                                                                    <a href="#" class="text-primary me-3" title="Edit"
                                                                        onclick="editBranch(<?php echo $branch['id']; ?>)">
                                                                        <i class="fas fa-edit"></i>
                                                                    </a>
                                                                    <a href="#" class="text-danger me-3" title="Delete"
                                                                        onclick="deleteBranch(<?php echo $branch['id']; ?>)">
                                                                        <i class="fas fa-trash"></i>
                                                                    </a>
                                                                    <a href="#" class="print-button text-primary me-3" title="Print"
                                                                        data-id="<?php echo $branch['id']; ?>" data-type="branch">
                                                                        <i class="fas fa-print"></i>
                                                                    </a>
                                                                    <a href="#" class="text-success"
                                                                        title="Edit Product Availability"
                                                                        onclick="editProductAvailability(<?php echo $branch['id']; ?>, <?php echo $business['id']; ?>)">
                                                                        <i class="fas fa-box"></i>
                                                                    </a>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <tr>
                                                            <td class="text-center" colspan="5">No branches available</td>
                                                        </tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>

                                            <button class="btn btn-primary mt-2 mb-5"
                                                onclick="printContent('business<?php echo $business['id']; ?>', 'Branch List for <?php echo $business['name']; ?>')">
                                                <i class="fas fa-print me-2"></i> Generate Report (Branch List)
                                            </button>

                                        </div>

                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>




                    </div>




                    <div id="manageproduct" class="tab-content">
                        <h1 class="mt-5"></h1>
                        <p class="mb-5">Detailed information on each business's products is available here.</p>

                        <div id="businesses">
                            <?php foreach ($businesses as $business): ?>
                                <div>
                                    <a class="btn btn-primary business card-one"
                                        onclick="toggleDetails('business-<?php echo $business['id']; ?>')">
                                        <i class="fa-solid fa-building"></i>
                                        <?php echo htmlspecialchars($business['name']); ?>
                                        <i class="end-0 mt-2 me-2 fas fa-plus me-2"></i>
                                    </a>

                                    <div id="business-<?php echo $business['id']; ?>" class="business-details card-one"
                                        style="display: none; margin-top: 10px;">
                                        <i class="fas fa-info-circle"
                                            onclick="showInfo('Manage Product', 'The process of organizing and overseeing products, including adding new items, updating details, setting prices, and managing inventory to ensure smooth business operations.');"></i>
                                        <p><strong>Business ID:</strong> <?php echo htmlspecialchars($business['id']); ?>
                                        </p>

                                        <div class="mt-4 mb-4 position-relative">
                                            <form class="d-flex" role="search">
                                                <input class="form-control me-2 w-50" type="search" id="search-product"
                                                    placeholder="Search product.." aria-label="Search">
                                            </form>
                                            <button class="btn btn-success position-absolute top-0 end-0 mt-2 me-2"
                                                type="button" onclick="addProduct(<?php echo $business['id']; ?>)">
                                                <i class="fas fa-plus me-2"></i> Add Product
                                            </button>
                                        </div>

                                        <div class="scrollable-table">
                                            <table class="table" id="product-table">
                                                <thead class="table-dark position-sticky top-0">
                                                    <tr>
                                                        <th>Product ID <button class="btn text-white"><i
                                                                    class="fas fa-sort"></i></button></th>
                                                        <th>Name <button class="btn text-white"><i
                                                                    class="fas fa-sort"></i></button></th>
                                                        <th>Type <button class="btn text-white"><i
                                                                    class="fas fa-sort"></i></button></th>
                                                        <th>Size/Weight <button class="btn text-white"><i
                                                                    class="fas fa-sort"></i></button></th>
                                                        <th>Price <button class="btn text-white"><i
                                                                    class="fas fa-sort"></i></button></th>
                                                        <th>Description <button class="btn text-white"><i
                                                                    class="fas fa-sort"></i></button></th>
                                                        <!-- <th>Status <button class="btn text-white"><i
                                                                    class="fas fa-sort"></i></button></th> -->
                                                        <th>Created At <button class="btn text-white"><i
                                                                    class="fas fa-sort"></i></button></th>
                                                        <th>Updated At <button class="btn text-white"><i
                                                                    class="fas fa-sort"></i></button></th>
                                                        <th class="text-center">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (!empty($products_by_business[$business['id']])): ?>
                                                        <?php foreach ($products_by_business[$business['id']] as $product): ?>
                                                            <tr>
                                                                <td><?php echo htmlspecialchars($product['id']); ?></td>
                                                                <td class="product-name">
                                                                    <?php echo htmlspecialchars($product['name']); ?>
                                                                </td>
                                                                <td><?php echo htmlspecialchars($product['type']); ?></td>
                                                                <td><?php echo htmlspecialchars($product['size']); ?></td>
                                                                <td><?php echo htmlspecialchars($product['price']); ?></td>
                                                                <td><?php echo htmlspecialchars($product['description']); ?></td>
                                                                <!-- <td><?php echo htmlspecialchars($product['status']); ?></td> -->
                                                                <td><?php echo htmlspecialchars($product['created_at']); ?></td>
                                                                <td><?php echo htmlspecialchars($product['updated_at']); ?></td>
                                                                <td class="text-center">
                                                                    <a href="#" class="text-primary me-3"
                                                                        onclick="editProduct(<?php echo $product['id']; ?>)">
                                                                        <i class="fas fa-edit"></i>
                                                                    </a>
                                                                    <a href="#" class="text-danger"
                                                                        onclick="deleteProduct(<?php echo $product['id']; ?>)">
                                                                        <i class="fas fa-trash"></i>
                                                                    </a>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <tr>
                                                            <td colspan="9" class="text-center">No products available for this
                                                                business yet.</td>
                                                        </tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>

                                            <button class="btn btn-primary mt-2 mb-5"
                                                onclick="printContent('business-<?php echo $business['id']; ?>', 'Product List for <?php echo $business['name']; ?>')">
                                                <i class="fas fa-print me-2"></i> Generate Report (Product List)
                                            </button>

                                        </div>

                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>




                    </div>



                </div>

            </div>
        </div>
    </div>
    </div>

    <script src="../js/sidebar.js"></script>
    <script src="../js/sort_items.js"></script>
    <script src="../js/show_info.js"></script>
    <script src="../js/print_report.js"></script>
    <script src="../js/filter_month_year_manage_business.js"></script>


    <script>
        // business permits
        document.addEventListener('DOMContentLoaded', function () {
            // Handle click on permit links
            document.querySelectorAll('.view-permit').forEach(link => {
                link.addEventListener('click', function (e) {
                    e.preventDefault();
                    const permitFile = this.getAttribute('data-permit');
                    const permitType = this.getAttribute('data-type');
                    const permitPath = '../assets/permits/' + permitFile;

                    if (permitType === 'pdf') {
                        // Show PDF download modal
                        Swal.fire({
                            title: 'Business Permit (PDF)',
                            html: `
                        <div class="text-center">
                            <p>This business permit is a PDF file.</p>
                            <a href="${permitPath}" class="btn btn-primary" download>
                                <i class="fas fa-download"></i> Download PDF
                            </a>
                        </div>
                    `,
                            showConfirmButton: false,
                            showCloseButton: true,
                            width: '500px'
                        });
                    } else {
                        // Show image modal
                        Swal.fire({
                            title: 'Business Permit',
                            html: `
                        <div style="max-height: 70vh; overflow: auto;">
                            <img src="${permitPath}" 
                                 style="max-width: 100%; max-height: 60vh; display: block; margin: 0 auto;" 
                                 alt="Business Permit">
                        </div>
                    `,
                            showConfirmButton: false,
                            showCloseButton: true,
                            width: '700px',
                            customClass: {
                                container: 'permit-modal-container'
                            },
                            didOpen: () => {
                                // Make image zoomable
                                const img = Swal.getHtmlContainer().querySelector('img');
                                img.style.cursor = 'zoom-in';
                                img.addEventListener('click', () => {
                                    if (img.style.cursor === 'zoom-in') {
                                        img.style.maxWidth = 'none';
                                        img.style.maxHeight = 'none';
                                        img.style.width = 'auto';
                                        img.style.height = 'auto';
                                        img.style.cursor = 'zoom-out';
                                    } else {
                                        img.style.maxWidth = '100%';
                                        img.style.maxHeight = '60vh';
                                        img.style.cursor = 'zoom-in';
                                    }
                                });
                            }
                        });
                    }
                });
            });
        });
        // branch permits
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.view-permit-branch').forEach(link => {
                link.addEventListener('click', function (e) {
                    e.preventDefault();
                    const permitFile = this.getAttribute('data-permit');
                    const permitType = this.getAttribute('data-type');
                    const permitPath = '../assets/branch_permits/' + permitFile;

                    if (permitType === 'pdf') {
                        // Show PDF download modal
                        Swal.fire({
                            title: 'Branch Permit (PDF)',
                            html: `
                        <div class="text-center">
                            <p>This branch permit is a PDF file.</p>
                            <a href="${permitPath}" class="btn btn-primary" download>
                                <i class="fas fa-download"></i> Download PDF
                            </a>
                        </div>
                    `,
                            showConfirmButton: false,
                            showCloseButton: true,
                            width: '500px'
                        });
                    } else {
                        // Show image modal
                        Swal.fire({
                            title: 'Branch Permit',
                            html: `
                        <div style="max-height: 70vh; overflow: auto;">
                            <img src="${permitPath}" 
                                 style="max-width: 100%; max-height: 60vh; display: block; margin: 0 auto;" 
                                 alt="Branch Permit">
                        </div>
                    `,
                            showConfirmButton: false,
                            showCloseButton: true,
                            width: '700px',
                            customClass: {
                                container: 'permit-modal-container'
                            },
                            didOpen: () => {
                                // Make image zoomable
                                const img = Swal.getHtmlContainer().querySelector('img');
                                img.style.cursor = 'zoom-in';
                                img.addEventListener('click', () => {
                                    if (img.style.cursor === 'zoom-in') {
                                        img.style.maxWidth = 'none';
                                        img.style.maxHeight = 'none';
                                        img.style.width = 'auto';
                                        img.style.height = 'auto';
                                        img.style.cursor = 'zoom-out';
                                    } else {
                                        img.style.maxWidth = '100%';
                                        img.style.maxHeight = '60vh';
                                        img.style.cursor = 'zoom-in';
                                    }
                                });
                            }
                        });
                    }
                });
            });
        });


        document.getElementById('uploadDataButton').addEventListener('click', function () {
            Swal.fire({
                title: 'Upload or Download Data',
                html: `
        <div class="mt-3 mb-3 position-relative">
            <form action="../import_excel_display_business.php" method="POST" enctype="multipart/form-data" class="btn btn-success p-3">
                <i class="fa-solid fa-upload"></i>
                <label for="file" class="mb-2">Upload Data:</label>
                <input type="file" name="file" id="file" accept=".xlsx, .xls" class="form-control mb-2">
                <input type="submit" value="Upload Excel" class="form-control">
            </form>
            <div class="d-flex justify-content-center mt-2">
                <button class="btn btn-info me-2" id="instructionsButton">
                    <i class="fa-solid fa-info-circle"></i> 
                </button>
                <form action="../export_excel_add_business.php" method="POST">
                    <button class="btn btn-success" type="submit">
                        <i class="fa-solid fa-download"></i> Download Data Template
                    </button>
                </form>
            </div>
            <div id="instructionsContainer" class="instructions-overlay d-none">
                <div class="instructions-content text-center">
                    <img src="../assets/instructions/business.jpg" alt="Instructions Image" class="img-fluid instructions-img" id="instructionsImage">
                </div>
            </div>
        </div>
        `,
                showConfirmButton: false,
                customClass: {
                    popup: 'swal2-modal-wide'
                }
            });

            document.getElementById('instructionsButton').addEventListener('click', function () {
                document.getElementById('instructionsContainer').classList.remove('d-none');
            });

            document.getElementById('instructionsImage').addEventListener('click', function () {
                document.getElementById('instructionsContainer').classList.add('d-none');
            });
        });




        const ownerId = <?php echo json_encode($owner_id); ?>;

        //  <input type="text" id="business-branch" class="form-control mb-2" placeholder="Branch Location">
        // Add Business
        $('#add-business-btn').click(function () {
            Swal.fire({
                title: 'Add New Business',
                html: `
            <div>
                <input type="text" id="business-name" class="form-control mb-2" placeholder="Business Name" required>
                <textarea type="text" id="business-description" class="form-control mb-2" placeholder="Business Description"></textarea>
                <input type="number" id="business-asset" class="form-control mb-2" placeholder="Asset Size" required>
                <input type="number" id="employee-count" class="form-control mb-2" placeholder="Number of Employees" required>
                <input type="text" id="business-location" class="form-control mb-2" placeholder="Location" required>
                <div class="mt-3">
                    <label for="business-permit" class="form-label">Business Permit (Image/PDF)</label>
                    <input type="file" id="business-permit" class="form-control" accept="image/*,.pdf" required>
                    <small class="text-muted">Upload a clear image or PDF of your business permit (max 5MB)</small>
                </div>
            </div>
        `,
                confirmButtonText: 'Add Business',
                showCancelButton: true,
                preConfirm: () => {
                    const data = {
                        name: $('#business-name').val().trim(),
                        description: $('#business-description').val().trim(),
                        asset: parseInt($('#business-asset').val(), 10),
                        employeeCount: parseInt($('#employee-count').val(), 10),
                        location: $('#business-location').val().trim(),
                        owner_id: ownerId,
                    };

                    const permitFile = document.getElementById('business-permit').files[0];

                    // Validate required fields
                    if (Object.values(data).some(value => !value) || !permitFile) {
                        Swal.showValidationMessage('All fields are required');
                        return false;
                    }

                    // Validate asset size
                    if (data.asset > 15000000) {
                        Swal.showValidationMessage('Asset size must not exceed 15,000,000');
                        return false;
                    }

                    // Validate employee count
                    if (data.employeeCount > 99) {
                        Swal.showValidationMessage('Employee count must not exceed 99');
                        return false;
                    }

                    // Validate file size (max 5MB)
                    if (permitFile.size > 5 * 1024 * 1024) {
                        Swal.showValidationMessage('File size must be less than 5MB');
                        return false;
                    }

                    // Create FormData for file upload
                    const formData = new FormData();
                    formData.append('name', data.name);
                    formData.append('description', data.description);
                    formData.append('asset', data.asset);
                    formData.append('employeeCount', data.employeeCount);
                    formData.append('location', data.location);
                    formData.append('owner_id', data.owner_id);
                    formData.append('permit', permitFile);

                    return $.ajax({
                        url: '../endpoints/business/add_business.php',
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                    }).fail(() => {
                        Swal.showValidationMessage('Failed to add business. Please try again.');
                    });
                },
            }).then(result => {
                if (result.isConfirmed) {
                    Swal.fire('Success!', 'Business added successfully. Pending for approval', 'success')
                        .then(() => location.reload());
                }
            });
        });

        $(document).ready(function () {
            // Edit Button
            $('.edit-button').click(function (e) {
                e.preventDefault();
                const row = $(this).closest('tr');
                const businessId = row.data('id');
                const name = row.find('td:eq(0)').text();
                const description = row.find('td:eq(1)').text();
                const asset = row.find('td:eq(2)').text();
                const employees = row.find('td:eq(3)').text();
                const location = row.find('td:eq(4)').text();

                Swal.fire({
                    title: 'Edit Business',
                    html: `
                <input type="text" id="edit-name" class="form-control mb-2" placeholder="Name" value="${name}">
                <textarea type="text" id="edit-description" class="form-control mb-2" placeholder="Description">${description}</textarea>
                <input type="number" id="edit-asset" class="form-control mb-2" placeholder="Asset" value="${asset}">
                <input type="number" id="edit-employees" class="form-control mb-2" placeholder="Employees" value="${employees}">
                <input type="text" id="edit-location" class="form-control mb-2" placeholder="Location" value="${location}">
                <div class="mt-3">
                    <label for="edit-business-permit" class="form-label">Change Business Permit (Image/PDF)</label>
                    <input type="file" id="edit-business-permit" class="form-control" accept="image/*,.pdf">
                    <small class="text-muted">Upload a clear image or PDF of your business permit (max 5MB)</small>
                </div>
            `,
                    confirmButtonText: 'Save Changes',
                    showCancelButton: true,
                    preConfirm: () => {
                        const updatedData = {
                            id: businessId,
                            name: $('#edit-name').val(),
                            description: $('#edit-description').val(),
                            asset: parseInt($('#edit-asset').val(), 10),
                            employeeCount: parseInt($('#edit-employees').val(), 10),
                            location: $('#edit-location').val(),
                        };

                        if (Object.values(updatedData).some(value => !value && value !== 0)) {
                            Swal.showValidationMessage('All fields are required except permit');
                            return false;
                        }

                        if (updatedData.asset > 15000000) {
                            Swal.showValidationMessage('Asset size must not exceed 15,000,000');
                            return false;
                        }

                        if (updatedData.employeeCount > 99) {
                            Swal.showValidationMessage('Employee count must not exceed 99');
                            return false;
                        }

                        // Handle file upload
                        const permitFile = document.getElementById('edit-business-permit').files[0];
                        const formData = new FormData();

                        // Append all data to formData
                        for (const key in updatedData) {
                            formData.append(key, updatedData[key]);
                        }

                        if (permitFile) {
                            // Validate file size (5MB max)
                            if (permitFile.size > 5 * 1024 * 1024) {
                                Swal.showValidationMessage('File size must not exceed 5MB');
                                return false;
                            }
                            formData.append('permit', permitFile);
                        }

                        return $.ajax({
                            url: '../endpoints/business/edit_business.php',
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                        }).fail((error) => {
                            Swal.showValidationMessage('Failed to save changes: ' + error.responseText);
                        });
                    },
                }).then(result => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Updated!',
                            text: 'Business details updated successfully.',
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then((result) => {
                            if (result.isConfirmed || result.isDismissed) {
                                location.reload();
                            }
                        });
                    }
                });
            });

            // Delete Button
            $('.delete-button').click(function (e) {
                e.preventDefault();
                const row = $(this).closest('tr');
                const businessId = row.data('id');

                Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!',
                }).then(result => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '../endpoints/business/delete_business.php',
                            type: 'POST',
                            data: {
                                id: businessId
                            },
                            success: () => {
                                Swal.fire('Deleted!', 'Your business has been deleted.', 'success')
                                    .then(() => location.reload());
                            },
                            error: () => {
                                Swal.fire('Error!', 'Failed to delete business. Please try again.', 'error');
                            },
                        });
                    }
                });
            });
        });

        function addBranch(businessId) {
            Swal.fire({
                title: 'Add Branch',
                html: `
            <div>
                <input id="branch-location" class="form-control mb-2" placeholder="Branch Location" required>
                <div class="mt-3">
                    <label for="branch-permit" class="form-label">Branch Business Permit (Image/PDF)</label>
                    <input type="file" id="branch-permit" class="form-control" accept="image/*,.pdf" required>
                    <small class="text-muted">Upload a clear image or PDF of your branch business permit (max 5MB)</small>
                </div>
            </div>
        `,
                confirmButtonText: 'Add Branch',
                focusConfirm: false,
                showCancelButton: true,
                preConfirm: () => {
                    const location = document.getElementById('branch-location').value.trim();
                    const permitFile = document.getElementById('branch-permit').files[0];

                    if (!location || !permitFile) {
                        Swal.showValidationMessage('Please enter branch location and upload permit');
                        return false;
                    }

                    if (permitFile.size > 5 * 1024 * 1024) {
                        Swal.showValidationMessage('File size must be less than 5MB');
                        return false;
                    }

                    return {
                        location,
                        permitFile
                    };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const { location, permitFile } = result.value;
                    const formData = new FormData();

                    formData.append('business_id', businessId);
                    formData.append('location', location);
                    formData.append('permit', permitFile);

                    fetch('../endpoints/branch/add_branch.php', {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire('Success', data.message, 'success').then(() => {
                                    window.location.reload();
                                });
                            } else {
                                Swal.fire('Error', data.message, 'error');
                            }
                        })
                        .catch(error => Swal.fire('Error', 'An error occurred.', 'error'));
                }
            });
        }

        function editBranch(branchId) {
            fetch(`../endpoints/branch/fetch_branch.php?id=${branchId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data) {
                        Swal.fire({
                            title: 'Edit Branch',
                            html: `
                        <input id="branch-location" class="form-control mb-2" 
                               placeholder="Branch Location" 
                               value="${data.data.location}">
                        <div class="mt-3">
                            <label for="edit-business-branch-permit" class="form-label">Change Business Permit (Image/PDF)</label>
                            <input type="file" id="edit-business-branch-permit" class="form-control" accept="image/*,.pdf">
                            <small class="text-muted">Upload a clear image or PDF of your business permit (max 5MB)</small>
                        </div>
                    `,
                            confirmButtonText: 'Save Changes',
                            focusConfirm: false,
                            showCancelButton: true,
                            preConfirm: () => {
                                const location = document.getElementById('branch-location').value;
                                const permitFile = document.getElementById('edit-business-branch-permit').files[0];

                                if (!location) {
                                    Swal.showValidationMessage('Please enter a branch location');
                                    return false;
                                }

                                if (permitFile && permitFile.size > 5 * 1024 * 1024) {
                                    Swal.showValidationMessage('File size must not exceed 5MB');
                                    return false;
                                }

                                const formData = new FormData();
                                formData.append('id', branchId);
                                formData.append('location', location);
                                if (permitFile) {
                                    formData.append('permit', permitFile);
                                }

                                return formData;
                            }
                        }).then((result) => {
                            if (result.isConfirmed) {
                                const formData = result.value;

                                fetch('../endpoints/branch/edit_branch.php', {
                                    method: 'POST',
                                    body: formData
                                })
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data.success) {
                                            Swal.fire({
                                                title: 'Success',
                                                text: 'Branch updated successfully!',
                                                icon: 'success'
                                            }).then(() => {
                                                window.location.reload();
                                            });
                                        } else {
                                            Swal.fire('Error', data.message || 'Failed to update branch!', 'error');
                                        }
                                    })
                                    .catch(error => {
                                        Swal.fire('Error', 'Network error while updating branch', 'error');
                                    });
                            }
                        });
                    } else {
                        Swal.fire('Error', data.message || 'Branch data not found', 'error');
                    }
                })
                .catch(error => {
                    Swal.fire('Error', 'Failed to fetch branch details', 'error');
                });
        }

        function deleteBranch(branchId) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it!',
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                cancelButtonText: 'Cancel'
            }).then(result => {
                if (result.isConfirmed) {
                    fetch('../endpoints/branch/delete_branch.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            id: branchId
                        })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire('Deleted!', 'The branch has been deleted.', 'success').then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire('Error', 'Failed to delete branch!', 'error');
                            }
                        });
                }
            });
        }


        // Add Product
        function addProduct(businessId) {
            const productTypesOptions = <?php echo json_encode($product_types); ?>; // Pass PHP array to JavaScript
            const sizeOptions = <?php echo json_encode($size_options); ?>; // Pass size options to JavaScript

            const typeOptions = productTypesOptions.map(type => `<option value="${type}">${type}</option>`).join('');
            const sizeDropdown = Object.keys(sizeOptions).map(category => {
                const sizes = sizeOptions[category].map(size => `<option value="${size}">${size}</option>`).join('');
                return `<optgroup label="${category}">${sizes}</optgroup>`;
            }).join('');

            Swal.fire({
                title: 'Add Product',
                html: `
                                <input id="product-name" class="form-control mb-2" placeholder="Product Name">
                                    <select id="product-type" class="form-control mb-2">
                                        <option value="">Select Type</option>
                                        ${typeOptions}
                                    </select>
                                    <input id="product-type-custom" class="form-control mb-2" placeholder="Or specify a new type (optional)">
                                        <select id="product-size" class="form-control mb-2">
                                            <option value="">Select Size</option>
                                            ${sizeDropdown}
                                        </select>
                                        <input id="product-size-custom" class="form-control mb-2" placeholder="Or specify a new size (optional)">
                                            <input id="product-price" type="number" class="form-control mb-2" placeholder="Product Price">
                                                <textarea id="product-description" class="form-control mb-2" placeholder="Product Description"></textarea>
                                                `,
                showCancelButton: true,
                confirmButtonText: 'Add Product',
                preConfirm: () => {
                    const name = document.getElementById('product-name').value;
                    const type = document.getElementById('product-type').value || document.getElementById('product-type-custom').value; // Use custom type if selected
                    const size = document.getElementById('product-size').value || document.getElementById('product-size-custom').value; // Use custom size if selected
                    const price = document.getElementById('product-price').value;
                    const description = document.getElementById('product-description').value;

                    if (!name || !type || !size || !price || !description) {
                        Swal.showValidationMessage('Please fill out all fields');
                    }

                    return {
                        business_id: businessId,
                        name,
                        type,
                        size,
                        price,
                        description
                    };
                }
            }).then(result => {
                if (result.isConfirmed) {
                    fetch('../endpoints/product/add_product.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(result.value)
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire('Success', 'Product added successfully!', 'success').then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire('Error', data.message, 'error');
                            }
                        })
                        .catch(error => Swal.fire('Error', 'An error occurred.', 'error'));
                }
            });
        }

        // Edit Product
        function editProduct(productId) {
            fetch(`../endpoints/product/fetch_product.php?id=${productId}`)
                .then(response => response.json())
                .then(data => {
                    Swal.fire({
                        title: 'Edit Product',
                        html: `
                    <input id="product-name" class="form-control mb-2" placeholder="Product Name" value="${data.name}">
                    <input id="product-type" class="form-control mb-2" placeholder="Product Type" value="${data.type}">
                    <input id="product-size" class="form-control mb-2" placeholder="Product Size" value="${data.size}">
                    <input id="product-price" type="number" class="form-control mb-2" placeholder="Product Price" value="${data.price}">
                    <textarea id="product-description" class="form-control mb-2" placeholder="Product Description">${data.description}</textarea>
                `,
                        showCancelButton: true,
                        confirmButtonText: 'Save Changes',
                        preConfirm: () => {
                            const name = document.getElementById('product-name').value;
                            const type = document.getElementById('product-type').value;
                            const size = document.getElementById('product-size').value;
                            const price = document.getElementById('product-price').value;
                            const description = document.getElementById('product-description').value;

                            if (!name || !type || !size || !price || !description) {
                                Swal.showValidationMessage('Please fill out all fields');
                            }

                            return {
                                name,
                                type,
                                size,
                                price,
                                description
                            };
                        }
                    }).then(result => {
                        if (result.isConfirmed) {
                            fetch('../endpoints/product/edit_product.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({
                                    id: productId,
                                    ...result.value
                                })
                            })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        Swal.fire('Success', 'Product updated successfully!', 'success').then(() => {
                                            location.reload();
                                        });
                                    } else {
                                        Swal.fire('Error', 'Failed to update product!', 'error');
                                    }
                                });
                        }
                    });
                });
        }
        // function editProduct(productId) {
        //     fetch(`../endpoints/product/fetch_product.php?id=${productId}`)
        //         .then(response => response.json())
        //         .then(data => {
        //             Swal.fire({
        //                 title: 'Edit Product',
        //                 html: `
        //             <input id="product-name" class="form-control mb-2" placeholder="Product Name" value="${data.name}">
        //             <input id="product-type" class="form-control mb-2" placeholder="Product Type" value="${data.type}">
        //             <input id="product-size" class="form-control mb-2" placeholder="Product Size" value="${data.size}">
        //             <input id="product-price" type="number" class="form-control mb-2" placeholder="Product Price" value="${data.price}">
        //             <textarea id="product-description" class="form-control mb-2" placeholder="Product Description">${data.description}</textarea>
        //             <select id="product-status" class="form-control mb-2">
        //                 <option value="Available" ${data.status === 'Available' ? 'selected' : ''}>Available</option>
        //                 <option value="Unavailable" ${data.status === 'Unavailable' ? 'selected' : ''}>Unavailable</option>
        //             </select>
        //         `,
        //                 showCancelButton: true,
        //                 confirmButtonText: 'Save Changes',
        //                 preConfirm: () => {
        //                     const name = document.getElementById('product-name').value;
        //                     const type = document.getElementById('product-type').value;
        //                     const size = document.getElementById('product-size').value;
        //                     const price = document.getElementById('product-price').value;
        //                     const description = document.getElementById('product-description').value;
        //                     const status = document.getElementById('product-status').value;

        //                     if (!name || !type || !size || !price || !description || !status) {
        //                         Swal.showValidationMessage('Please fill out all fields');
        //                     }

        //                     return {
        //                         name,
        //                         type,
        //                         size,
        //                         price,
        //                         description,
        //                         status
        //                     };
        //                 }
        //             }).then(result => {
        //                 if (result.isConfirmed) {
        //                     fetch('../endpoints/product/edit_product.php', {
        //                         method: 'POST',
        //                         headers: {
        //                             'Content-Type': 'application/json'
        //                         },
        //                         body: JSON.stringify({
        //                             id: productId,
        //                             ...result.value
        //                         })
        //                     })
        //                         .then(response => response.json())
        //                         .then(data => {
        //                             if (data.success) {
        //                                 Swal.fire('Success', 'Product updated successfully!', 'success').then(() => {
        //                                     location.reload();
        //                                 });
        //                             } else {
        //                                 Swal.fire('Error', 'Failed to update product!', 'error');
        //                             }
        //                         });
        //                 }
        //             });
        //         });
        // }

        // Delete Product
        function deleteProduct(productId) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it!',
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                cancelButtonText: 'Cancel'
            }).then(result => {
                if (result.isConfirmed) {
                    fetch('../endpoints/product/delete_product.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            id: productId
                        })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire('Deleted!', 'The product has been deleted.', 'success').then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire('Error', 'Failed to delete product!', 'error');
                            }
                        });
                }
            });
        }

        // Product Availability
        function editProductAvailability(branchId, businessId) {
            $.ajax({
                url: '../endpoints/product/fetch_product_availability.php',
                type: 'POST',
                data: { branch_id: branchId, business_id: businessId },
                dataType: 'json',
                success: function (data) {
                    if (data.success) {
                        let productOptions = '';

                        if (data.products.length === 0) {
                            productOptions = `<div class="text-center text-muted">No products available for this branch.</div>`;
                        } else {
                            data.products.forEach(product => {
                                let formattedPrice = parseFloat(product.price).toLocaleString('en-US');
                                let availability = product.status === 'Available' ? 'selected' : '';
                                let unavailable = product.status === 'Unavailable' ? 'selected' : '';

                                productOptions += `
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="product-info">
                                    <label>${product.name} - ${product.size} (₱${formattedPrice})</label>
                                </div>
                                <div>
                                    <select class="form-control product-status" data-product-id="${product.id}">
                                        <option value="Available" ${availability}>Available</option>
                                        <option value="Unavailable" ${unavailable}>Unavailable</option>
                                    </select>
                                </div>
                            </div>
                        `;
                            });
                        }

                        Swal.fire({
                            title: 'Edit Product Availability',
                            html: `<div>${productOptions}</div>`,
                            showCancelButton: true,
                            confirmButtonText: 'Update',
                            preConfirm: () => {
                                let updates = [];
                                $('.product-status').each(function () {
                                    let productId = $(this).data('product-id');
                                    let status = $(this).val();
                                    updates.push({ product_id: productId, status: status });
                                });
                                return updates;
                            }
                        }).then((result) => {
                            if (result.isConfirmed && data.products.length > 0) {
                                $.ajax({
                                    url: '../endpoints/product/update_product_availability.php',
                                    type: 'POST',
                                    data: {
                                        branch_id: branchId,
                                        business_id: businessId,
                                        updates: JSON.stringify(result.value)
                                    },
                                    dataType: 'json',
                                    success: function (updateData) {
                                        if (updateData.success) {
                                            Swal.fire({
                                                title: 'Updated!',
                                                text: 'Product availability updated.',
                                                icon: 'success'
                                            }).then(() => {
                                                location.reload();
                                            });
                                        } else {
                                            Swal.fire('Error!', 'Failed to update availability.', 'error');
                                        }
                                    }
                                });
                            }
                        });
                    } else {
                        Swal.fire('Error!', 'Failed to fetch products.', 'error');
                    }
                }
            });
        }

        function editProductAvailabilityBusiness(businessId) {
            $.ajax({
                url: '../endpoints/product/fetch_product_availability_business.php',
                type: 'POST',
                data: { business_id: businessId },
                dataType: 'json',
                success: function (data) {
                    if (data.success) {
                        let productOptions = '';

                        if (data.products.length === 0) {
                            productOptions = `<div class="text-center text-muted">No products available for this business.</div>`;
                        } else {
                            data.products.forEach(product => {
                                let formattedPrice = parseFloat(product.price).toLocaleString('en-US');
                                let availability = product.status === 'Available' ? 'selected' : '';
                                let unavailable = product.status === 'Unavailable' ? 'selected' : '';

                                productOptions += `
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="product-info">
                                <label>${product.name} - ${product.size} (₱${formattedPrice})</label>
                            </div>
                            <div>
                                <select class="form-control product-status" data-product-id="${product.id}">
                                    <option value="Available" ${availability}>Available</option>
                                    <option value="Unavailable" ${unavailable}>Unavailable</option>
                                </select>
                            </div>
                        </div>`;
                            });
                        }

                        Swal.fire({
                            title: 'Edit Product Availability',
                            html: `<div>${productOptions}</div>`,
                            showCancelButton: true,
                            confirmButtonText: 'Update',
                            preConfirm: () => {
                                let updates = [];
                                $('.product-status').each(function () {
                                    let productId = $(this).data('product-id');
                                    let status = $(this).val();
                                    updates.push({ product_id: productId, status: status });
                                });
                                return updates;
                            }
                        }).then((result) => {
                            if (result.isConfirmed && data.products.length > 0) {
                                $.ajax({
                                    url: '../endpoints/product/update_product_availability_business.php',
                                    type: 'POST',
                                    data: {
                                        business_id: businessId,
                                        updates: JSON.stringify(result.value)
                                    },
                                    dataType: 'json',
                                    success: function (updateData) {
                                        if (updateData.success) {
                                            Swal.fire({
                                                title: 'Updated!',
                                                text: 'Product availability updated.',
                                                icon: 'success'
                                            }).then(() => {
                                                location.reload();
                                            });
                                        } else {
                                            Swal.fire('Error!', 'Failed to update availability.', 'error');
                                        }
                                    }
                                });
                            }
                        });
                    } else {
                        Swal.fire('Error!', 'Failed to fetch products.', 'error');
                    }
                }
            });
        }


        document.addEventListener('DOMContentLoaded', () => {
            const navLinks = document.querySelectorAll('.nav-link');
            const tabContents = document.querySelectorAll('.tab-content');


            const defaultTabId = 'businesslist';

            const savedTab = localStorage.getItem('activeTab') || defaultTabId;

            const savedTabContent = document.getElementById(savedTab);
            const savedNavLink = document.querySelector(`.nav-link[data-tab="${savedTab}"]`);

            if (savedTabContent && savedNavLink) {
                navLinks.forEach(nav => nav.classList.remove('active'));
                tabContents.forEach(content => content.classList.remove('active'));

                savedNavLink.classList.add('active');
                savedTabContent.classList.add('active');
            } else {
                const defaultNavLink = document.querySelector(`.nav-link[data-tab="${defaultTabId}"]`);
                const defaultTabContent = document.getElementById(defaultTabId);

                if (defaultNavLink && defaultTabContent) {
                    defaultNavLink.classList.add('active');
                    defaultTabContent.classList.add('active');
                }
            }

            navLinks.forEach(link => {
                link.addEventListener('click', () => {
                    navLinks.forEach(nav => nav.classList.remove('active'));
                    tabContents.forEach(content => content.classList.remove('active'));

                    link.classList.add('active');
                    const targetTab = document.getElementById(link.getAttribute('data-tab'));
                    if (targetTab) targetTab.classList.add('active');

                    localStorage.setItem('activeTab', link.getAttribute('data-tab'));
                });
            });
        });



        // document.addEventListener('DOMContentLoaded', () => {
        //     const navLinks = document.querySelectorAll('.nav-link');
        //     const tabContents = document.querySelectorAll('.tab-content');

        //     navLinks.forEach(link => {
        //         link.addEventListener('click', () => {
        //             navLinks.forEach(nav => nav.classList.remove('active'));
        //             tabContents.forEach(content => content.classList.remove('active'));
        //             link.classList.add('active');
        //             const targetTab = document.getElementById(link.getAttribute('data-tab'));
        //             targetTab.classList.add('active');
        //         });
        //     });
        // });

        // business filter
        document.getElementById('search-business').addEventListener('input', function () {
            const filter = this.value.toLowerCase();
            const rows = document.querySelectorAll('#business-table-body tr');

            rows.forEach(row => {
                const nameCell = row.querySelector('.business-name');
                if (nameCell) {
                    const name = nameCell.textContent.toLowerCase();
                    row.style.display = name.includes(filter) ? '' : 'none';
                }
            });
        });
        // Branch filter
        document.getElementById('search-branch').addEventListener('input', function () {
            const filter = this.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr'); // Target all table rows in the tbody

            rows.forEach(row => {
                const locationCell = row.querySelector('td:first-child'); // Target the first <td> (Location column)
                if (locationCell) {
                    const location = locationCell.textContent.toLowerCase();
                    row.style.display = location.includes(filter) ? '' : 'none';
                }
            });
        });

        // product filter
        document.getElementById('search-product').addEventListener('input', function () {
            const filter = this.value.toLowerCase();
            const rows = document.querySelectorAll('#product-table tbody tr');

            rows.forEach(row => {
                const nameCell = row.querySelector('.product-name');
                if (nameCell) {
                    const name = nameCell.textContent.toLowerCase();
                    row.style.display = name.includes(filter) ? '' : 'none';
                }
            });
        });


        function toggleDetails(id) {
            const details = document.getElementById(id);
            details.style.display = details.style.display === "none" ? "block" : "none";
        }

        function removeQueryParam() {
            const newUrl = window.location.pathname; // Get the base URL without parameters
            window.history.replaceState({}, document.title, newUrl); // Update the URL without refreshing
        }

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

        document.getElementById("deleteMultipleButton").addEventListener("click", function () {
            Swal.fire({
                title: "Delete Multiple Data's?",
                text: "Select the type of data you want to delete:",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Confirm",
                cancelButtonText: "Cancel",
                input: "radio",
                inputOptions: {
                    business: "Business",
                    branch: "Branch",
                    products: "Products"
                },
                inputValidator: (value) => {
                    if (!value) {
                        return "Please select an option!";
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    fetchData(result.value);
                }
            });
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

            fetch("../endpoints/business/fetch_data.php", {
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
                        displayData(type, data);
                    } else {
                        Swal.fire("No Data Found", "There is no data available for the selected type.", "info");
                    }
                })
                .catch(error => {
                    Swal.fire("Error", "Failed to fetch data. Please try again.", "error");
                });
        }
        function displayData(type, data) {
            let table = `<table border='1' width='100%' style="border-collapse: collapse;">
                <tr style="background-color: #f8f9fa; font-weight: bold;">`;

            // Add a checkbox column header
            table += `<th style="width: 10%;"><input type="checkbox" id="selectAll"> Select All</th>`;

            if (type === "business") {
                table += `<th style="width: 5%;">ID</th>
                  <th style="width: 15%;">Name</th>
                  <th style="width: auto;">Description</th>
                  <th style="width: 15%;">Asset</th>
                  <th style="width: 10%;">Employee Count</th>`;
                data.forEach(row => {
                    table += `<tr>
                        <td><input type="checkbox" name="selectedItems" value="${row.id}"></td>
                        <td>${row.id}</td>
                        <td>${row.name}</td>
                        <td>${row.description}</td>
                        <td>${row.asset}</td>
                        <td>${row.employee_count}</td>
                      </tr>`;
                });
            } else if (type === "branch") {
                table += `<th style="width: 5%;">ID</th>
                  <th style="width: 25%;">Location</th>
                  <th style="width: 10%;">Business ID</th>`;
                data.forEach(row => {
                    table += `<tr>
                        <td><input type="checkbox" name="selectedItems" value="${row.id}"></td>
                        <td>${row.id}</td>
                        <td>${row.location}</td>
                        <td>${row.business_id}</td>
                      </tr>`;
                });
            } else if (type === "products") {
                table += `<th style="width: 5%;">ID</th>
                  <th style="width: 15%;">Name</th>
                  <th style="width: auto;">Description</th>
                  <th style="width: 10%;">Price</th>
                  <th style="width: 10%;">Size</th>
                  <th style="width: 15%;">Type</th>`;
                data.forEach(row => {
                    table += `<tr>
                        <td><input type="checkbox" name="selectedItems" value="${row.id}"></td>
                        <td>${row.id}</td>
                        <td>${row.name}</td>
                        <td>${row.description}</td>
                        <td>${row.price}</td>
                        <td>${row.size}</td>
                        <td>${row.type}</td>
                      </tr>`;
                });
            }

            table += "</tr></table>";

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
                        filterData(type, year, month);
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
            fetch("../endpoints/business/delete_data.php", {
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
                        Swal.fire({
                            title: "Success",
                            text: "Selected data has been deleted.",
                            icon: "success",
                            confirmButtonText: "OK"
                        }).then(() => {
                            // Reload the page after the user clicks "OK"
                            window.location.reload();
                        });
                    } else {
                        Swal.fire("Error", "Failed to delete data. Please try again.", "error");
                    }
                })
                .catch(error => {
                    Swal.fire("Error", "Failed to delete data. Please try again.", "error");
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

        function filterData(type, year, month) {
            fetchData(type, year, month);
        }


    </script>


</body>

</html>