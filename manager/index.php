<?php
session_start();
require_once '../conn/auth.php';
require_once '../conn/conn.php';
validateSession('manager');

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
$manager_id = $_SESSION['user_id'];

// Query to fetch the assigned branch or business
$sql = "
    SELECT 'branch' AS type, b.id, b.location AS name, b.business_id, bs.name AS business_name
    FROM branch b
    LEFT JOIN business bs ON b.business_id = bs.id
    WHERE b.manager_id = ?
    UNION
    SELECT 'business' AS type, id, name, NULL AS business_id, NULL AS business_name
    FROM business
    WHERE manager_id = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $manager_id, $manager_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $assigned = $result->fetch_assoc();

    // Determine the type and details of the assignment
    if ($assigned['type'] === 'branch') {
        $assigned_type = 'Branch';
        $assigned_name = $assigned['name'];
        $business_name = $assigned['business_name'];
    } else {
        $assigned_type = 'Business';
        $assigned_name = $assigned['name'];
        $business_name = null;
    }
} else {
    // No assignment found
    $assigned_type = null;
    $assigned_name = null;
    $business_id = null;
}
$sales_query = "";

if ($assigned_type === 'Branch') {
    $sales_query = "
        SELECT 
            s.id, 
            p.name AS product, 
            p.price, 
            s.quantity, 
            (s.quantity * p.price) AS revenue, 
            s.date 
        FROM sales s
        LEFT JOIN products p ON s.product_id = p.id
        WHERE s.type = 'branch' AND s.branch_id = ? AND s.user_role != 'Owner'
        ORDER BY s.date DESC
    ";
    $stmt = $conn->prepare($sales_query);
    $stmt->bind_param('i', $assigned['id']);
} elseif ($assigned_type === 'Business') {
    $sales_query = "
        SELECT 
            s.id, 
            p.name AS product, 
            p.price, 
            s.quantity, 
            (s.quantity * p.price) AS revenue, 
            s.date 
        FROM sales s
        LEFT JOIN products p ON s.product_id = p.id
        WHERE s.type = 'business' AND s.branch_id = 0 AND s.user_role != 'Owner'
        ORDER BY s.date DESC
    ";
    $stmt = $conn->prepare($sales_query);
}

$stmt->execute();
$sales_result = $stmt->get_result();
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
                        <?php if ($assigned_type): ?>
                            <?php if ($assigned_type === 'Branch' && $business_name): ?>
                                <!-- Display the business name if assigned to a branch -->
                                <h4 class="mt-2"><?php echo htmlspecialchars($business_name); ?></h4>
                                <h5 class="mt-2">
                                    <b>
                                        Assigned to
                                        <?php echo htmlspecialchars($assigned_type) . ': ' . htmlspecialchars($assigned_name); ?>
                                    </b>
                                </h5>
                            <?php else: ?>
                                <!-- Only show the assignment in h5 if assigned to a business -->
                                <h5 class="mt-2">
                                    <b>Assigned to
                                        <?php echo htmlspecialchars($assigned_type) . ': ' . htmlspecialchars($assigned_name); ?></b>
                                </h5>
                            <?php endif; ?>
                        <?php else: ?>
                            <h5 class="mt-2"><b>No Assignment Found</b></h5>
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
                                <?php if ($assigned_type === 'Branch'): ?>
                                    <button class="btn btn-success position-absolute top-0 end-0 mt-2 me-2"
                                        id="addBranchSaleBtn" data-type="Branch"
                                        data-id="<?php echo htmlspecialchars($assigned['id']); ?>"
                                        data-name="<?php echo htmlspecialchars($assigned_name); ?>"
                                        data-business-id="<?php echo htmlspecialchars($assigned['business_id']); ?>"
                                        data-business-name="<?php echo htmlspecialchars($business_name); ?>">
                                        <i class="fas fa-plus me-2"></i> Add Branch Sales
                                    </button>
                                <?php elseif ($assigned_type === 'Business'): ?>
                                    <button class="btn btn-success position-absolute top-0 end-0 mt-2 me-2"
                                        id="addBusinessSaleBtn" data-type="Business"
                                        data-id="<?php echo htmlspecialchars($assigned['id']); ?>"
                                        data-name="<?php echo htmlspecialchars($assigned_name); ?>">
                                        <i class="fas fa-plus me-2"></i> Add Business Sales
                                    </button>
                                <?php endif; ?>

                            </div>




                            <div class="scrollable-table">
                                <div class="scrollable-table">
                                    <table class="table table-striped table-hover mt-4 mb-5">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Product</th>
                                                <th>Price</th>
                                                <th>Quantity Sold</th>
                                                <th>Revenue</th>
                                                <th>Updated At</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="salesTableBody">
                                            <?php if ($sales_result->num_rows > 0): ?>
                                                <?php while ($row = $sales_result->fetch_assoc()): ?>
                                                    <tr class="product-row">
                                                        <td class="product-name">
                                                            <?php echo htmlspecialchars($row['product']); ?>
                                                        </td>
                                                        <td>$<?php echo number_format($row['price'], 2); ?>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($row['quantity']); ?></td>
                                                        <td>$<?php echo number_format($row['revenue'], 2); ?></td>
                                                        <td><?php echo htmlspecialchars($row['date']); ?></td>
                                                        <td class="text-center">
                                                            <a href="#" class="text-primary me-3"
                                                                onclick="editSale(<?php echo $row['id']; ?>)">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <a href="#" class="text-danger"
                                                                onclick="deleteSale(<?php echo $row['id']; ?>)">
                                                                <i class="fas fa-trash"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="6" class="text-center">No sales records found.</td>
                                                </tr>
                                            <?php endif; ?>
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

    <script src="../js/sidebar_manager.js"></script>
    <script src="../js/sort_items.js"></script>
    <script>
        // Edit Sale
        function editSale(salesId) {
            console.log('Edit Sale Triggered for ID:', salesId); // Debugging

            fetch('fetch_sale_details.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({ sale_id: salesId }).toString()
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const { product, quantity, date } = data.sale;

                        Swal.fire({
                            title: 'Edit Sale',
                            html: `
                    <label for="editQuantity">Quantity Sold</label>
                    <input type="number" id="editQuantity" class="form-control mb-2" value="${quantity}" min="1">
                    <label for="editDate">Sales Date</label>
                    <input type="date" id="editDate" class="form-control mb-2" value="${date}">
                `,
                            showCancelButton: true,
                            confirmButtonText: 'Update',
                            preConfirm: () => {
                                const quantity = document.getElementById('editQuantity').value;
                                const saleDate = document.getElementById('editDate').value;

                                if (!quantity || quantity <= 0 || !saleDate) {
                                    Swal.showValidationMessage('Please enter valid data');
                                }
                                return { quantity, saleDate };
                            }
                        }).then(async (result) => {
                            if (result.isConfirmed) {
                                const { quantity, saleDate } = result.value;

                                try {
                                    const response = await fetch('update_sale.php', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/x-www-form-urlencoded'
                                        },
                                        body: new URLSearchParams({
                                            sale_id: salesId, // Correct key
                                            quantity,
                                            date: saleDate
                                        }).toString()
                                    });

                                    const resData = await response.json();
                                    if (resData.success) {
                                        Swal.fire('Success', 'Sale updated successfully!', 'success').then(() => location.reload());
                                    } else {
                                        Swal.fire('Error', resData.message || 'Failed to update sale.', 'error');
                                    }
                                } catch (error) {
                                    console.error(error);
                                    Swal.fire('Error', 'Failed to submit update request.', 'error');
                                }
                            }
                        });
                    } else {
                        Swal.fire('Error', data.message || 'Failed to fetch sale details.', 'error');
                    }
                })
                .catch(error => {
                    console.error(error);
                    Swal.fire('Error', 'Failed to fetch sale details.', 'error');
                });
        }

        function deleteSale(salesId) {
            if (!salesId) {
                Swal.fire('Error', 'Invalid Sale ID.', 'error');
                return;
            }

            console.log('Deleting Sale ID:', salesId); // Debugging to verify the ID

            Swal.fire({
                title: 'Are you sure?',
                text: "This action cannot be undone!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    try {
                        const response = await fetch('delete_sale.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: new URLSearchParams({ sale_id: salesId }).toString() // Use `sale_id`
                        });

                        const resData = await response.json();
                        if (resData.status === 'success') { // Match the `status` from PHP response
                            Swal.fire('Deleted!', 'The sale has been deleted.', 'success').then(() => location.reload());
                        } else {
                            Swal.fire('Error', resData.message || 'Failed to delete sale.', 'error');
                        }
                    } catch (error) {
                        console.error(error);
                        Swal.fire('Error', 'Failed to submit delete request.', 'error');
                    }
                }
            });
        }


        document.getElementById('searchInput').addEventListener('input', function () {
            const filter = this.value.toLowerCase();  // Get the search term
            const rows = document.querySelectorAll('#salesTableBody .product-row');

            rows.forEach(row => {
                const productCell = row.querySelector('.product-name');
                if (productCell) {
                    const productName = productCell.textContent.toLowerCase();
                    row.style.display = productName.includes(filter) ? '' : 'none';  // Show or hide the row
                }
            });
        });
        // Event Listener for Branch Sales Button
        document.getElementById('addBranchSaleBtn')?.addEventListener('click', async (e) => {
            const button = e.currentTarget;
            const branchId = button.getAttribute('data-id');
            const businessId = button.getAttribute('data-business-id');
            const today = new Date().toISOString().split('T')[0];

            try {
                const response = await fetch(`fetch_products.php?business_id=${businessId}`);
                const products = await response.json();

                const productOptions = products
                    .map(product => `<option value="${product.id}" data-price="${product.price}">${product.name} - ₱${product.price}</option>`)
                    .join('');

                Swal.fire({
                    title: 'Add Branch Sales',
                    html: `
                <label for="productSelect">Product</label>
                <select id="productSelect" class="form-control mb-2">${productOptions}</select>
                <label for="amountSold">Amount Sold</label>
                <input type="number" id="amountSold" class="form-control mb-2" min="1" placeholder="Enter amount sold">
                <label for="totalSales">Total Sales</label>
                <input type="text" id="totalSales" class="form-control mb-2" readonly placeholder="₱0">
                <label for="saleDate">Sales Date</label>
                <input type="date" id="saleDate" class="form-control mb-2" value="${today}">
            `,
                    showCancelButton: true,
                    confirmButtonText: "Add Sales",
                    preConfirm: () => {
                        const productSelect = document.getElementById('productSelect');
                        const amountSold = document.getElementById('amountSold').value;

                        if (!productSelect.value || !amountSold || amountSold <= 0) {
                            Swal.showValidationMessage('Please select a product and enter a valid amount sold');
                        }
                        return {
                            productId: productSelect.value,
                            amountSold: parseInt(amountSold, 10),
                            totalSales: parseFloat(document.getElementById('totalSales').value.replace('₱', '')),
                            saleDate: document.getElementById('saleDate').value,
                        };
                    },
                }).then(async (result) => {
                    if (result.isConfirmed) {
                        const salesData = result.value;
                        try {
                            const response = await fetch('add_branch_sales.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                },
                                body: JSON.stringify({
                                    branch_id: branchId,
                                    product_id: salesData.productId,
                                    quantity: salesData.amountSold,
                                    total_sales: salesData.totalSales,
                                    date: salesData.saleDate,
                                    user_role: 'Manager',
                                }),
                            });

                            const result = await response.json();
                            if (result.success) {
                                Swal.fire('Success', 'Branch sales added successfully.', 'success').then(() => location.reload());
                            } else {
                                Swal.fire('Error', result.message || 'Failed to add sales.', 'error');
                            }
                        } catch (error) {
                            Swal.fire('Error', 'Failed to submit sales data.', 'error');
                            console.error(error);
                        }
                    }
                });

                document.getElementById('amountSold').addEventListener('input', (e) => {
                    const productSelect = document.getElementById('productSelect');
                    const price = parseFloat(productSelect.selectedOptions[0].getAttribute('data-price'));
                    const amount = parseInt(e.target.value, 10);
                    const totalSales = isNaN(amount) || amount <= 0 ? 0 : price * amount;
                    document.getElementById('totalSales').value = `₱${totalSales.toFixed(2)}`;
                });
            } catch (error) {
                Swal.fire('Error', 'Failed to fetch products.', 'error');
                console.error(error);
            }
        });

        // Event Listener for Business Sales Button
        document.getElementById('addBusinessSaleBtn')?.addEventListener('click', async (e) => {
            const button = e.currentTarget;
            const businessId = button.getAttribute('data-id');
            const today = new Date().toISOString().split('T')[0];

            try {
                const response = await fetch(`fetch_products.php?business_id=${businessId}`);
                const products = await response.json();

                const productOptions = products
                    .map(product => `<option value="${product.id}" data-price="${product.price}">${product.name} - ₱${product.price}</option>`)
                    .join('');

                Swal.fire({
                    title: 'Add Business Sales',
                    html: `
                <label for="productSelect">Product</label>
                <select id="productSelect" class="form-control mb-2">${productOptions}</select>
                <label for="amountSold">Amount Sold</label>
                <input type="number" id="amountSold" class="form-control mb-2" min="1" placeholder="Enter amount sold">
                <label for="totalSales">Total Sales</label>
                <input type="text" id="totalSales" class="form-control mb-2" readonly placeholder="₱0">
                <label for="saleDate">Sales Date</label>
                <input type="date" id="saleDate" class="form-control mb-2" value="${today}">
            `,
                    showCancelButton: true,
                    confirmButtonText: "Add Sales",
                    preConfirm: () => {
                        const productSelect = document.getElementById('productSelect');
                        const amountSold = document.getElementById('amountSold').value;

                        if (!productSelect.value || !amountSold || amountSold <= 0) {
                            Swal.showValidationMessage('Please select a product and enter a valid amount sold');
                        }
                        return {
                            businessId,
                            productId: productSelect.value,
                            amountSold: parseInt(amountSold, 10),
                            totalSales: parseFloat(document.getElementById('totalSales').value.replace('₱', '')),
                            saleDate: document.getElementById('saleDate').value,
                        };
                    },
                }).then(async (result) => {
                    if (result.isConfirmed) {
                        const salesData = result.value;
                        try {
                            const response = await fetch('add_business_sales.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: new URLSearchParams({
                                    business_id: salesData.businessId,
                                    product_id: salesData.productId,
                                    quantity: salesData.amountSold,
                                    total_sales: salesData.totalSales,
                                    date: salesData.saleDate,
                                    user_role: 'Manager',
                                }).toString(),
                            });

                            const result = await response.json();
                            if (result.status === 'success') {
                                Swal.fire('Success', result.message, 'success').then(() => location.reload());
                            } else {
                                Swal.fire('Error', result.message || 'Failed to add sales.', 'error');
                            }
                        } catch (error) {
                            Swal.fire('Error', 'Failed to submit sales data.', 'error');
                            console.error(error);
                        }
                    }
                });

                document.getElementById('amountSold').addEventListener('input', (e) => {
                    const productSelect = document.getElementById('productSelect');
                    const price = parseFloat(productSelect.selectedOptions[0].getAttribute('data-price'));
                    const amount = parseInt(e.target.value, 10);
                    const totalSales = isNaN(amount) || amount <= 0 ? 0 : price * amount;
                    document.getElementById('totalSales').value = `₱${totalSales.toFixed(2)}`;
                });
            } catch (error) {
                Swal.fire('Error', 'Failed to fetch products.', 'error');
                console.error(error);
            }
        });



    </script>

</body>

</html>