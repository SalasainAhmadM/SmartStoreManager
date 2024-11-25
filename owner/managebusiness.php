<?php
session_start();
require_once '../conn/conn.php';
require_once '../conn/auth.php';

validateSession('owner');

$owner_id = $_SESSION['user_id'];

// Fetch business data for the logged-in owner
$query = "SELECT * FROM business WHERE owner_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$result = $stmt->get_result();

$businesses = [];
while ($row = $result->fetch_assoc()) {
    $businesses[] = $row;
}

$stmt->close();
$conn->close();
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

    <?php include '../components/owner_sidebar.php'; ?>

    <div class="container-fluid page-body">
        <div class="row">
            <div class="col-md-12 dashboard-body">
                <div class="dashboard-content">
                    <h1><b><i class="fas fa-cogs me-2"></i> Manage Business</b></h1>
                    <ul class="nav nav-pills nav-fill mt-5">
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
                        <h1 class="mt-5"><b>Business List Section</b></h1>

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

                        <div class="col-md-12 mt-5">
                            <table class="table table-striped table-hover mt-4">
                                <thead class="table-dark">
                                    <tr>
                                        <th scope="col">Name</th>
                                        <th scope="col">Description</th>
                                        <th scope="col">Asset Size</th>
                                        <th scope="col">Employee Count</th>
                                        <th scope="col">Created At</th>
                                        <th scope="col">Updated At</th>
                                        <th scope="col">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="business-table-body">
                                    <?php foreach ($businesses as $business): ?>
                                        <tr data-id="<?php echo $business['id']; ?>">
                                            <td class="business-name"><?php echo htmlspecialchars($business['name']); ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($business['description']); ?></td>
                                            <td><?php echo htmlspecialchars($business['asset']); ?></td>
                                            <td><?php echo htmlspecialchars($business['employee_count']); ?></td>
                                            <td><?php echo htmlspecialchars($business['created_at']); ?></td>
                                            <td><?php echo htmlspecialchars($business['updated_at']); ?></td>
                                            <td>
                                                <a href="#" class="text-primary me-3 edit-btn" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="#" class="text-danger delete-btn" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div id="branchlist" class="tab-content">
                        <h1 class="mt-5"><b>Branch List Section</b></h1>
                        <p class="mb-5">Detailed information about each business and its branches is available here.</p>


                        <div id="businesses">
                            <div>
                                <a class="btn btn-primary business card-one" onclick="toggleDetails('businessA')">
                                    <i class="fa-solid fa-building"></i>
                                    Business A
                                    <i class="position-absolute end-0 mt-2 me-2 fas fa-plus me-2"></i>
                                </a>

                                <div id="businessA" class="business-details card-one"
                                    style="display: none; margin-top: 10px;">
                                    <p><strong>Business ID:</strong> 5</p>
                                    <p><strong>Updated At:</strong> 2024-11-25, 2:46 AM</p>


                                    <!-- Search Bar -->
                                    <div class="mt-4 mb-4 position-relative">
                                        <form class="d-flex" role="search">
                                            <input class="form-control me-2 w-50" type="search"
                                                placeholder="Search branch.." aria-label="Search">
                                        </form>
                                        <!-- Add Branch Button -->
                                        <button class="btn btn-success position-absolute top-0 end-0 mt-2 me-2"
                                            type="button">
                                            <i class="fas fa-plus me-2"></i> Add Branch
                                        </button>
                                    </div>

                                    <table class="table">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Location</th>
                                                <th>Created At</th>
                                                <th>Updated At</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>Location 1</td>
                                                <td>2024-01-10</td>
                                                <td>2024-11-10</td>
                                                <td>
                                                    <a href="#" class="text-primary me-3" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="#" class="text-danger" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Location 2</td>
                                                <td>2024-02-15</td>
                                                <td>2024-11-15</td>
                                                <td>
                                                    <a href="#" class="text-primary me-3" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="#" class="text-danger" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div id="businesses">
                            <div>
                                <a class="btn btn-primary business card-one" onclick="toggleDetails('businessB')">
                                    <i class="fa-solid fa-building"></i>
                                    Business B
                                    <i class="position-absolute end-0 mt-2 me-2 fas fa-plus me-2"></i>
                                </a>

                                <div id="businessB" class="business-details card-one"
                                    style="display: none; margin-top: 10px;">
                                    <p><strong>Business ID:</strong> 6</p>
                                    <p><strong>Updated At:</strong> 2024-11-25, 2:46 AM</p>

                                    <!-- Search Bar -->
                                    <div class="mt-4 mb-4 position-relative">
                                        <form class="d-flex" role="search">
                                            <input class="form-control me-2 w-50" type="search"
                                                placeholder="Search branch.." aria-label="Search">
                                        </form>
                                        <!-- Add Branch Button -->
                                        <button class="btn btn-success position-absolute top-0 end-0 mt-2 me-2"
                                            type="button">
                                            <i class="fas fa-plus me-2"></i> Add Branch
                                        </button>
                                    </div>


                                    <table class="table">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Location</th>
                                                <th>Created At</th>
                                                <th>Updated At</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>Location 1</td>
                                                <td>2024-01-10</td>
                                                <td>2024-11-10</td>
                                                <td>
                                                    <a href="#" class="text-primary me-3" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="#" class="text-danger" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Location 2</td>
                                                <td>2024-02-15</td>
                                                <td>2024-11-15</td>
                                                <td>
                                                    <a href="#" class="text-primary me-3" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="#" class="text-danger" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>


                    </div>




                    <div id="manageproduct" class="tab-content">
                        <h1 class="mt-5"><b>Manage Product Section</b></h1>
                        <p class="mb-5">Detailed information on each business's products is available here.</p>

                        <div id="businesses">
                            <?php foreach ($businesses as $business): ?>
                                <div>
                                    <a class="btn btn-primary business card-one"
                                        onclick="toggleDetails('business-<?php echo $business['id']; ?>')">
                                        <i class="fa-solid fa-building"></i>
                                        <?php echo htmlspecialchars($business['name']); ?>
                                        <i class="position-absolute end-0 mt-2 me-2 fas fa-plus me-2"></i>
                                    </a>

                                    <div id="business-<?php echo $business['id']; ?>" class="business-details card-one"
                                        style="display: none; margin-top: 10px;">
                                        <p><strong>Business ID:</strong> <?php echo htmlspecialchars($business['id']); ?>
                                        </p>

                                        <div class="mt-4 mb-4 position-relative">
                                            <form class="d-flex" role="search">
                                                <input class="form-control me-2 w-50" type="search"
                                                    placeholder="Search product.." aria-label="Search">
                                            </form>
                                            <button class="btn btn-success position-absolute top-0 end-0 mt-2 me-2"
                                                type="button" onclick="addProduct(<?php echo $business['id']; ?>)">
                                                <i class="fas fa-plus me-2"></i> Add Product
                                            </button>
                                        </div>

                                        <table class="table">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th>Product ID</th>
                                                    <th>Name</th>
                                                    <th>Type</th>
                                                    <th>Price</th>
                                                    <th>Description</th>
                                                    <th>Created At</th>
                                                    <th>Updated At</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td colspan="8" class="text-center">No products available for this
                                                        business yet.</td>
                                                </tr>
                                            </tbody>
                                        </table>
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
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        const ownerId = <?php echo json_encode($owner_id); ?>;

        //  <input type="text" id="business-branch" class="form-control mb-2" placeholder="Branch Location">
        // Add Business
        $('#add-business-btn').click(function () {
            Swal.fire({
                title: 'Add New Business',
                html: `
            <div>
                <input type="text" id="business-name" class="form-control mb-2" placeholder="Business Name">
                <textarea type="text" id="business-description" class="form-control mb-2" placeholder="Business Description"></textarea>
                <input type="number" id="business-asset" class="form-control mb-2" placeholder="Asset Size">
                <input type="number" id="employee-count" class="form-control mb-2" placeholder="Number of Employees">
            </div>
        `,
                confirmButtonText: 'Add Business',
                showCancelButton: true,
                preConfirm: () => {
                    const data = {
                        name: $('#business-name').val(),
                        description: $('#business-description').val(),
                        asset: parseInt($('#business-asset').val(), 10),
                        employeeCount: parseInt($('#employee-count').val(), 10),
                        owner_id: ownerId,
                    };

                    if (Object.values(data).some(value => !value)) {
                        Swal.showValidationMessage('All fields are required');
                        return false;
                    }

                    if (data.asset > 15000000) {
                        Swal.showValidationMessage('Asset size must not exceed 15,000,000');
                        return false;
                    }

                    if (data.employeeCount > 99) {
                        Swal.showValidationMessage('Employee count must not exceed 99');
                        return false;
                    }

                    return $.ajax({
                        url: '../endpoints/add_business.php',
                        type: 'POST',
                        data: data,
                    }).fail(() => {
                        Swal.showValidationMessage('Failed to add business. Please try again.');
                    });
                },
            }).then(result => {
                if (result.isConfirmed) {
                    Swal.fire('Success!', 'Business added successfully.', 'success')
                        .then(() => location.reload());
                }
            });
        });

        $(document).ready(function () {
            // Edit Button
            $('.edit-btn').click(function (e) {
                e.preventDefault();
                const row = $(this).closest('tr');
                const businessId = row.data('id');
                const name = row.find('td:eq(0)').text();
                const description = row.find('td:eq(1)').text();
                const asset = row.find('td:eq(2)').text();
                const employees = row.find('td:eq(3)').text();

                Swal.fire({
                    title: 'Edit Business',
                    html: `
                <input type="text" id="edit-name" class="form-control mb-2" placeholder="Name" value="${name}">
                <textarea type="text" id="edit-description" class="form-control mb-2" placeholder="Description">${description}</textarea>
                <input type="number" id="edit-asset" class="form-control mb-2" placeholder="Asset" value="${asset}">
                <input type="number" id="edit-employees" class="form-control mb-2" placeholder="Employees" value="${employees}">
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
                        };

                        if (Object.values(updatedData).some(value => !value)) {
                            Swal.showValidationMessage('All fields are required');
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

                        return $.ajax({
                            url: '../endpoints/edit_business.php',
                            type: 'POST',
                            data: updatedData,
                        }).fail(() => {
                            Swal.showValidationMessage('Failed to save changes. Please try again.');
                        });
                    },
                }).then(result => {
                    if (result.isConfirmed) {
                        Swal.fire('Updated!', 'Business details updated successfully.', 'success')
                            .then(() => location.reload());
                    }
                });
            });

            // Delete Button
            $('.delete-btn').click(function (e) {
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
                            url: '../endpoints/delete_business.php',
                            type: 'POST',
                            data: { id: businessId },
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

        // add product
        function addProduct(businessId) {
            Swal.fire({
                title: 'Add Product',
                html: `
            <input id="product-name" class="form-control mb-2" placeholder="Product Name">
            <input id="product-type" class="form-control mb-2" placeholder="Product Type">
            <input id="product-price" type="number" class="form-control mb-2" placeholder="Product Price">
            <textarea id="product-description" class="form-control mb-2" placeholder="Product Description"></textarea>
        `,
                confirmButtonText: 'Add Product',
                focusConfirm: false,
                showCancelButton: true,
                preConfirm: () => {
                    const name = document.getElementById('product-name').value;
                    const type = document.getElementById('product-type').value;
                    const price = document.getElementById('product-price').value;
                    const description = document.getElementById('product-description').value;

                    if (!name || !type || !price || !description) {
                        Swal.showValidationMessage('Please fill out all fields');
                    }
                    return { name, type, price, description };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const { name, type, price, description } = result.value;

                    // Send data to add_product.php
                    fetch('../endpoints/add_product.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ business_id: businessId, name, type, price, description })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire('Success', 'Product added successfully!', 'success');
                                // Optionally refresh or update product table
                            } else {
                                Swal.fire('Error', data.message, 'error');
                            }
                        })
                        .catch(error => Swal.fire('Error', 'An error occurred.', 'error'));
                }
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            const navLinks = document.querySelectorAll('.nav-link');
            const tabContents = document.querySelectorAll('.tab-content');

            navLinks.forEach(link => {
                link.addEventListener('click', () => {
                    navLinks.forEach(nav => nav.classList.remove('active'));
                    tabContents.forEach(content => content.classList.remove('active'));
                    link.classList.add('active');
                    const targetTab = document.getElementById(link.getAttribute('data-tab'));
                    targetTab.classList.add('active');
                });
            });
        });

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


        function toggleDetails(id) {
            const details = document.getElementById(id);
            details.style.display = details.style.display === "none" ? "block" : "none";
        }
    </script>

</body>

</html>