<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Dashboard</title>
    <link rel="icon" href="../assets/logo.png">
    <?php include '../components/head_cdn.php'; ?>
    <style>
        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .nav-link {
            cursor: pointer;
        }
    </style>
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
                    <h1><b><i class="fas fa-cogs me-2"></i> Manage Expenses</b></h1>
                    <ul class="nav nav-pills nav-fill mt-5">
                        <li class="nav-item">
                            <a class="nav-link active" data-tab="businesslist">
                                <i class="fas fa-list me-2"></i> Business List
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-tab="branchlist">
                                <i class="fas fa-building me-2"></i> Branch List
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-tab="manageproduct">
                                <i class="fas fa-box-open me-2"></i> Manage Product
                            </a>
                        </li>
                    </ul>

                    <div id="businesslist" class="tab-content active">
                        <h2 class="mt-5">Business List Section</h2>

                        <!-- Search Bar -->
                        <div class="mt-4">
                            <form class="d-flex" role="search">
                                <input class="form-control me-2" type="search" placeholder="Search businesses" aria-label="Search">
                                <button class="btn btn-outline-primary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </form>
                        </div>

                        <div class="col-md-12 mt-5">
                            <table class="table table-striped table-hover mt-4">
                                <thead class="table-dark">
                                    <tr>
                                        <th scope="col">Name</th>
                                        <th scope="col">Description</th>
                                        <th scope="col">Created At</th>
                                        <th scope="col">Updated At</th>
                                        <th scope="col">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Business A</td>
                                        <td>Example Description</td>
                                        <td>2024-11-25</td>
                                        <td>2024-11-26</td>
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
                                        <td>Business B</td>
                                        <td>Example Description</td>
                                        <td>2024-11-25</td>
                                        <td>2024-11-26</td>
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


                    <div id="branchlist" class="tab-content">
                        <h2 class="mt-5">Branch List Section</h2>
                        <p>Detailed information about each business and its branches is available here.</p>


                        <div id="businesses">
                            <div>
                                <a class="btn btn-primary business card-one" onclick="toggleDetails('businessA')">
                                    <i class="fa-solid fa-building"></i>
                                    Business A
                                </a>

                                <div id="businessA" class="business-details card-one" style="display: none; margin-top: 10px;">
                                    <p><strong>Business ID:</strong> 5</p>
                                    <p><strong>Updated At:</strong> 2024-11-25, 2:46 AM</p>

                                    <div class="mt-4 mb-4">
                                        <form class="d-flex" role="search">
                                            <input class="form-control me-2" type="search" placeholder="Search by Location" aria-label="Search">
                                            <button class="btn btn-outline-primary" type="submit">
                                                <i class="fas fa-search"></i>
                                            </button>
                                        </form>
                                    </div>

                                    <table class="table">
                                        <thead>
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
                                </a>

                                <div id="businessB" class="business-details" style="display: none; margin-top: 10px;">
                                    <p><strong>Business ID:</strong> 6</p>
                                    <p><strong>Updated At:</strong> 2024-11-25, 2:46 AM</p>

                                    <div class="mt-4 mb-4">
                                        <form class="d-flex" role="search">
                                            <input class="form-control me-2" type="search" placeholder="Search by Location" aria-label="Search">
                                            <button class="btn btn-outline-primary" type="submit">
                                                <i class="fas fa-search"></i>
                                            </button>
                                        </form>
                                    </div>

                                    <table class="table">
                                        <thead>
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
                        <h2 class="mt-5">Manage Product Section</h2>
                        <p>Detailed information on each business's products is available here.</p>

                        <div id="businesses">
                            <div>
                                <a class="btn btn-primary business card-one" onclick="toggleDetails('businessC')">
                                    <i class="fa-solid fa-building"></i>
                                    Business A
                                </a>

                                <div id="businessC" class="business-details card-one" style="display: none; margin-top: 10px;">
                                    <p><strong>Business ID:</strong> 5</p>

                                    <div class="mt-4 mb-4">
                                        <form class="d-flex" role="search">
                                            <input class="form-control me-2" type="search" placeholder="Search product.." aria-label="Search">
                                            <button class="btn btn-outline-primary" type="submit">
                                                <i class="fas fa-search"></i>
                                            </button>
                                        </form>
                                    </div>

                                    <button class="btn btn-success mb-3" onclick="addProduct()">Add Product</button>

                                    <table class="table">
                                        <thead>
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
                                                <td>1</td>
                                                <td>Product A</td>
                                                <td>Type A</td>
                                                <td>$100</td>
                                                <td>Description of Product A</td>
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
                                                <td>2</td>
                                                <td>Product B</td>
                                                <td>Type B</td>
                                                <td>$150</td>
                                                <td>Description of Product B</td>
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
                                <a class="btn btn-primary business card-one" onclick="toggleDetails('businessD')">
                                    <i class="fa-solid fa-building"></i>
                                    Business B
                                </a>

                                <div id="businessD" class="business-details card-one" style="display: none; margin-top: 10px;">
                                    <p><strong>Business ID:</strong> 6</p>

                                    <div class="mt-4 mb-4">
                                        <form class="d-flex" role="search">
                                            <input class="form-control me-2" type="search" placeholder="Search product.." aria-label="Search">
                                            <button class="btn btn-outline-primary" type="submit">
                                                <i class="fas fa-search"></i>
                                            </button>
                                        </form>
                                    </div>

                                    <button class="btn btn-success mb-3" onclick="addProduct()">Add Product</button>

                                    <table class="table">
                                        <thead>
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
                                                <td>1</td>
                                                <td>Product A</td>
                                                <td>Type A</td>
                                                <td>$100</td>
                                                <td>Description of Product A</td>
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
                                                <td>2</td>
                                                <td>Product B</td>
                                                <td>Type B</td>
                                                <td>$150</td>
                                                <td>Description of Product B</td>
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



                </div>

            </div>
        </div>
    </div>
    </div>

    <script src="../js/sidebar.js"></script>
    <script>
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
    </script>

    <script>
        function toggleDetails(id) {
            const details = document.getElementById(id);
            details.style.display = details.style.display === "none" ? "block" : "none";
        }
    </script>

</body>

</html>