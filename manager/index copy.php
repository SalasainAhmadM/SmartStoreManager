<?php
session_start();
require_once '../conn/auth.php';
require_once '../conn/conn.php';
validateSession('manager');

$manager_id = $_SESSION['user_id'];

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
// Fetch the business or branch assigned to the manager
$sql = "
    SELECT 
        'branch' AS type, b.location AS name 
    FROM branch b 
    WHERE b.manager_id = ? 
    UNION 
    SELECT 
        'business' AS type, bs.name AS name 
    FROM business bs 
    WHERE bs.manager_id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $manager_id, $manager_id);
$stmt->execute();
$result = $stmt->get_result();

$assignment = null;
if ($row = $result->fetch_assoc()) {
    $assignment = $row; // Contains 'type' and 'name'
}

$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Dashboard</title>
    <link rel="icon" href="../assets/logo.png">
    <?php include '../components/head_cdn.php'; ?>
</head>

<body class="d-flex">

    <div id="particles-js"></div>

    <?php include '../components/manager_sidebar.php'; ?>

    <div class="container-fluid page-body">
        <div class="row">
            <div class="col-md-12 dashboard-body">
                <div class="dashboard-content">
                    <h1><b>Manager Dashboard</b></h1>
                    <h4 class="mt-5"><b><i class="fas fa-tachometer-alt me-2"></i> Manage Sales</b></h4>
                    <div class="card-one">
                        <h5 class="mt-5"><b>Business/Branch</b></h5>
                        <?php if ($assignment): ?>
                            <p>You are assigned to the <?= htmlspecialchars($assignment['type']); ?>:
                                <strong><?= htmlspecialchars($assignment['name']); ?></strong>
                            </p>
                        <?php else: ?>
                            <p>You are not currently assigned to any business or branch.</p>
                        <?php endif; ?>

                        <div id="salesPanel">
                            <h4 class="mt-4" id="salesTitle"></h4>


                            <!-- Search Bar -->
                            <div class="mt-3 position-relative">
                                <form class="d-flex" role="search">
                                    <input class="form-control me-2 w-50" type="search" placeholder="Search product.."
                                        aria-label="Search" id="searchInput">
                                </form>
                                <!-- Add Business Button -->
                                <button class="btn btn-success position-absolute top-0 end-0 mt-2 me-2" id="addSaleBtn">
                                    <i class="fas fa-plus me-2"></i> Add Sale
                                </button>

                            </div>




                            <div class="scrollable-table">
                                <table class="table table-striped table-hover mt-4 mb-5">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>
                                                Product
                                                <button class="btn text-white"><i class="fas fa-sort"></i></button>
                                            </th>
                                            <th>
                                                Price
                                                <button class="btn text-white"><i class="fas fa-sort"></i></button>
                                            </th>
                                            <th>
                                                Quantity Sold
                                                <button class="btn text-white"><i class="fas fa-sort"></i></button>
                                            </th>
                                            <th>
                                                Revenue
                                                <button class="btn text-white"><i class="fas fa-sort"></i></button>
                                            </th>
                                            <th>
                                                Updated At
                                                <button class="btn text-white"><i class="fas fa-sort"></i></button>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody id="salesTableBody">
                                        <!-- Example static rows -->
                                        <tr>
                                            <td>Product A</td>
                                            <td>$50.00</td>
                                            <td>10</td>
                                            <td>$500.00</td>
                                            <td>2024-12-01</td>
                                        </tr>
                                        <tr>
                                            <td>Product B</td>
                                            <td>$30.00</td>
                                            <td>5</td>
                                            <td>$150.00</td>
                                            <td>2024-12-03</td>
                                        </tr>
                                        <tr>
                                            <td>Product C</td>
                                            <td>$20.00</td>
                                            <td>8</td>
                                            <td>$160.00</td>
                                            <td>2024-12-05</td>
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

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const addSaleBtn = document.getElementById("addSaleBtn");
            const salesTableBody = document.getElementById("salesTableBody");

            addSaleBtn.addEventListener("click", async function () {
                // Fetch branch or business assignment from PHP (defined earlier)
                const assignment = <?= json_encode($assignment); ?>; // Use PHP to pass assignment details
                if (!assignment) {
                    Swal.fire("Error", "You are not assigned to any branch or business.", "error");
                    return;
                }

                const assignmentType = assignment.type; // "branch" or "business"
                const assignmentId = assignment.id; // ID of branch/business

                // Fetch products for the assigned branch/business via AJAX
                const fetchProducts = async () => {
                    const response = await fetch("get_products.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                        },
                        body: JSON.stringify({
                            assignment_type: assignmentType,
                            assignment_id: assignmentId,
                        }),
                    });
                    return response.json();
                };

                const productData = await fetchProducts();
                if (productData.status !== "success" || productData.data.length === 0) {
                    Swal.fire("Error", "No products available for this branch or business.", "error");
                    return;
                }

                // Populate product options
                const productOptions = productData.data
                    .map((product) => `<option value="${product.id}" data-price="${product.price}">${product.name} - ₱${product.price}</option>`)
                    .join("");

                const today = new Date().toISOString().split("T")[0]; // Current date

                Swal.fire({
                    title: "Add Sales",
                    html: `
            <label for="productSelect">Product</label>
            <select id="productSelect" class="form-control mb-2">${productOptions}</select>
            <label for="amountSold">Amount Sold</label>
            <input type="number" id="amountSold" class="form-control mb-2" min="1" placeholder="Enter amount sold">
            <label for="totalSales">Total Sales</label>
            <input type="text" id="totalSales" class="form-control mb-2" readonly placeholder="₱0">
            <label for="saleDate">Sales Date</label>
            <input type="date" id="saleDate" class="form-control mb-2" value="${today}" readonly>
        `,
                    showCancelButton: true,
                    confirmButtonText: "Add Sale",
                    preConfirm: () => {
                        const productSelect = document.getElementById("productSelect");
                        const amountSold = document.getElementById("amountSold").value;

                        if (!productSelect.value || !amountSold) {
                            Swal.showValidationMessage("All fields are required!");
                            return false;
                        }

                        const productPrice = productSelect.options[productSelect.selectedIndex].dataset.price;
                        const totalSales = amountSold * productPrice;

                        return {
                            productId: productSelect.value,
                            productName: productSelect.options[productSelect.selectedIndex].text,
                            productPrice,
                            amountSold,
                            totalSales,
                            saleDate: today,
                        };
                    },
                }).then((result) => {
                    if (result.isConfirmed) {
                        const { productId, productName, productPrice, amountSold, totalSales, saleDate } = result.value;

                        // Dynamically add sale to the table
                        const newRow = `
                <tr>
                    <td>${productName}</td>
                    <td>₱${productPrice}</td>
                    <td>${amountSold}</td>
                    <td>₱${totalSales}</td>
                    <td>${saleDate}</td>
                </tr>
            `;
                        salesTableBody.insertAdjacentHTML("beforeend", newRow);

                        Swal.fire("Success", "Sale added successfully!", "success");
                    }
                });
            });
        });

    </script>


    <script src="../js/sidebar_manager.js"></script>
    <script src="../js/sort_items.js"></script>

    <script>
        const searchInput = document.getElementById('searchInput');
        const salesTableBody = document.getElementById('salesTableBody');

        searchInput.addEventListener('input', function () {
            const searchValue = searchInput.value.toLowerCase();

            // Get all rows in the sales table
            const rows = salesTableBody.getElementsByTagName('tr');

            // Loop through rows and toggle their visibility based on the search value
            for (let row of rows) {
                const cells = row.getElementsByTagName('td');
                let rowMatches = false;

                // Check each cell in the row
                for (let cell of cells) {
                    if (cell.textContent.toLowerCase().includes(searchValue)) {
                        rowMatches = true;
                        break;
                    }
                }

                row.style.display = rowMatches ? '' : 'none';
            }
        });
    </script>

</body>

</html>