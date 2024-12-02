<?php
session_start();
require_once '../conn/auth.php';
require_once '../conn/conn.php';

validateSession('owner');

$owner_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
if (isset($_GET['id']) && intval($_GET['id']) > 0) {
    $owner_id = intval($_GET['id']);
}

// Query to fetch owner details
$query = "SELECT id FROM owner WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die('Owner not found.');
}

$query = "SELECT * FROM owner WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$result = $stmt->get_result();

$owner = $result->fetch_assoc();
// Check if the owner is new
$query = "SELECT is_new_owner FROM owner WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $isNewOwner = $row['is_new_owner'] == 1;
}

if (isset($_GET['status']) && $_GET['status'] === 'success') {
    echo "
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Login Successful',
                    text: 'Welcome to the dashboard!',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    " . ($isNewOwner ? "triggerAddBusinessModal();" : "") . "
                });
            });
        </script>
    ";
    unset($_SESSION['login_success']);
}
// SQL query to get the business and branch data
$sql = "SELECT b.name AS business_name, br.location AS branch_location, br.business_id
FROM business b
JOIN branch br ON b.id = br.business_id";
$result = $conn->query($sql);

// Business chart data
$businessData = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $businessData[$row['business_name']][] = $row['branch_location'];
    }
} else {
    echo "No data found";
}

// Generate derived data
$processedData = [];
foreach ($businessData as $businessName => $branches) {
    $branchCount = count($branches);
    $processedData[$businessName] = [
        'branches' => $branchCount,
        'sales' => rand(50000, 150000), // Mock sales data
        'expenses' => rand(20000, 100000), // Mock expenses data
    ];
}
?>
<script>
    const ownerId = <?= json_encode(isset($_GET['id']) ? $_GET['id'] : $_SESSION['user_id']); ?>;

    const businessData = <?php echo json_encode($processedData); ?>;

    function triggerAddBusinessModal(ownerId) {
        Swal.fire({
            title: 'Add New Business',
            html: `
        <div>
            <input type="text" id="business-name" class="form-control mb-2" placeholder="Business Name">
            <input type="text" id="business-description" class="form-control mb-2" placeholder="Business Description">
            <input type="number" id="business-asset" class="form-control mb-2" placeholder="Asset Size">
            <input type="number" id="employee-count" class="form-control mb-2" placeholder="Number of Employees">
        </div>
        `,
            confirmButtonText: 'Add Business',
            showCancelButton: true,
            cancelButtonText: 'Skip'
        }).then((result) => {
            if (result.isConfirmed) {
                const businessName = document.getElementById('business-name').value.trim();
                const businessDescription = document.getElementById('business-description').value.trim();
                const businessAsset = document.getElementById('business-asset').value.trim();
                const employeeCount = document.getElementById('employee-count').value.trim();

                if (!businessName || !businessAsset || !employeeCount) {
                    Swal.fire('Error', 'Please fill in all required fields.', 'error');
                    return;
                }

                const formData = new FormData();
                formData.append('name', businessName);
                formData.append('description', businessDescription);
                formData.append('asset', businessAsset);
                formData.append('employeeCount', employeeCount);
                formData.append('owner_id', ownerId || <?= json_encode($_SESSION['user_id']); ?>);

                fetch('../endpoints/business/add_business_prompt.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Success', data.message, 'success').then(() => {
                                const url = new URL(window.location.href);
                                url.search = '';
                                history.replaceState(null, '', url);
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', data.message, 'error');
                        }
                    })
                    .catch(err => {
                        Swal.fire('Error', 'An unexpected error occurred.', 'error');
                        console.error(err);
                    });
            } else if (result.dismiss === Swal.DismissReason.cancel) {
                // Trigger update to set is_new_owner = 0
                fetch('../endpoints/business/skip_business_prompt.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ owner_id: ownerId || <?= json_encode($_SESSION['user_id']); ?> })
                })
            }
        });
    }


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
                    <h1><b><i class="fas fa-tachometer-alt me-2"></i> Dashboard Overview</b></h1>

                    <div class="container-fluid">
                        <div class="row">

                            <div class="col-md-5">
                                <h5 class="mt-5"><b>Select Business:</b></h5>

                                <div class="scroll-container">
                                    <?php
                                    foreach ($businessData as $businessName => $branches) {
                                        echo '<button class="col-md-12 card" data-business-name="' . $businessName . '" onclick="showBusinessData(\'' . $businessName . '\')">';
                                        echo '<h5>' . $businessName . '</h5>';
                                        echo '<table class="table table-striped table-hover mt-4">';
                                        echo '<thead class="table-dark"><tr><th>Branch</th><th>Sales (₱)</th><th>Expenses (₱)</th></tr></thead>';
                                        echo '<tbody>';

                                        // Loop through each branch of the business
                                        foreach ($branches as $branchLocation) {
                                            echo '<tr>';
                                            echo '<td>' . $branchLocation . '</td>';
                                            echo '<td>8000</td>';
                                            echo '<td>4000</td>';
                                            echo '</tr>';
                                        }

                                        echo '</tbody>';
                                        echo '</table>';
                                        echo '</button>';
                                    }
                                    ?>
                                </div>


                            </div>

                            <div class="col-md-7">

                                <h5 class="mt-5"><b>Sales Overview:</b></h5>
                                <canvas id="financialChart"></canvas>

                            </div>

                            <div class="col-md-12 mt-5">
                                <h1><b><i class="fa-solid fa-lightbulb"></i> Insights</b></h1>
                                <div class="col-md-12 dashboard-content">
                                    <div>
                                        <h5>Predicted Growth:</h5>
                                        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi tincidunt
                                            tellus quis ligula semper, vitae bibendum felis lacinia. Donec eleifend
                                            tellus ac massa malesuada, a pellentesque dolor scelerisque. Sed feugiat
                                            felis vel odio condimentum aliquet. Nulla sit amet urna sed est elementum
                                            dapibus non ac mauris. Aenean nec est diam. Maecenas a nisi ut nibh luctus
                                            porttitor. Vestibulum pretium auctor condimentum.</p>
                                    </div>
                                    <div>
                                        <h5>Actionable Advice:</h5>
                                        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi tincidunt
                                            tellus quis ligula semper, vitae bibendum felis lacinia. Donec eleifend
                                            tellus ac massa malesuada, a pellentesque dolor scelerisque. Sed feugiat
                                            felis vel odio condimentum aliquet. Nulla sit amet urna sed est elementum
                                            dapibus non ac mauris. Aenean nec est diam. Maecenas a nisi ut nibh luctus
                                            porttitor. Vestibulum pretium auctor condimentum.</p>
                                    </div>
                                </div>
                            </div>

                            <div id="popularProductsSection">
                                <div class="col-md-12 mt-5">
                                    <h1 class="section-title">
                                        <b><i class="fas fa-boxes icon"></i> Popular Products</b>
                                    </h1>
                                    <div class="col-md-12 dashboard-content">
                                        <table class="table table-hover" id="product-table">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th>Product <button class="btn text-white"><i
                                                                class="fas fa-sort"></i></button></th>
                                                    <th>Business <button class="btn text-white"><i
                                                                class="fas fa-sort"></i></button></th>
                                                    <th>Type <button class="btn text-white"><i
                                                                class="fas fa-sort"></i></button></th>
                                                    <th>Price <button class="btn text-white"><i
                                                                class="fas fa-sort"></i></button></th>
                                                    <th>Description <button class="btn text-white"><i
                                                                class="fas fa-sort"></i></button></th>
                                                    <th>Total Sales <button class="btn text-white"><i
                                                                class="fas fa-sort"></i></button></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>Laptop</td>
                                                    <td>Business A</td>
                                                    <td>Services</td>
                                                    <td>₱5000</td>
                                                    <td>Awesome Service</td>
                                                    <td>₱1,000,000</td>
                                                </tr>
                                                <tr>
                                                    <td>Custom T-Shirts</td>
                                                    <td>Business B</td>
                                                    <td>Products</td>
                                                    <td>₱4000</td>
                                                    <td>Awesome T-Shirts</td>
                                                    <td>₱500,000</td>
                                                </tr>
                                                <tr>
                                                    <td>Coffee Beans</td>
                                                    <td>Business C</td>
                                                    <td>Products</td>
                                                    <td>₱2,000</td>
                                                    <td>Awesome Coffee Beans</td>
                                                    <td>₱10,000</td>
                                                </tr>
                                                <tr>
                                                    <td>Coffee Beans</td>
                                                    <td>Business C</td>
                                                    <td>Products</td>
                                                    <td>₱777,000</td>
                                                    <td>Awesome Coffee Beans</td>
                                                    <td>₱222,210,000</td>
                                                </tr>
                                            </tbody>
                                        </table>


                                        <button class="btn btn-primary mt-2 mb-5" id="printPopularProducts"
                                            onclick="printTable('product-table', 'Popular Products')">
                                            <i class="fas fa-print me-2"></i> Print Report (Popular Products)
                                        </button>

                                    </div>
                                </div>
                            </div>


                            <div id="recentActivitiesSection">
                                <div class="col-md-12 mt-5">
                                    <h1 class="section-title"><b><i class="fas fa-history icon"></i> Recent
                                            Activities</b>
                                    </h1>
                                    <div class="col-md-12 dashboard-content">
                                        <table class="table" id="recent-activities-table">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th>Activity <button class="btn text-white"><i
                                                                class="fas fa-sort"></i></button></th>
                                                    <th>Date <button class="btn text-white"><i
                                                                class="fas fa-sort"></i></button></th>
                                                    <th>Status <button class="btn text-white"><i
                                                                class="fas fa-sort"></i></button></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>New User Registered</td>
                                                    <td>2024-11-20</td>
                                                    <td><i class="fas fa-check-circle icon"></i> Completed</td>
                                                </tr>
                                                <tr>
                                                    <td>Report Generated</td>
                                                    <td>2024-11-21</td>
                                                    <td><i class="fas fa-spinner icon"></i> In Progress</td>
                                                </tr>
                                                <tr>
                                                    <td>Product Ordered</td>
                                                    <td>2024-11-22</td>
                                                    <td><i class="fas fa-times-circle icon"></i> Failed</td>
                                                </tr>
                                            </tbody>
                                        </table>

                                        <button class="btn btn-primary mt-2 mb-5" id="printRecentActivities"
                                            onclick="printTable('recent-activities-table', 'Recent Activities')">
                                            <i class="fas fa-print me-2"></i> Print Report (Recent Activities)
                                        </button>

                                    </div>
                                </div>
                            </div>


                        </div>
                    </div>

                </div>
            </div>

        </div>


    </div>
    <!-- <script>
        window.onload = function () {
            Swal.fire({
                icon: 'success',
                title: 'Login Successful',
                text: 'Welcome!',
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                <?php unset($_SESSION['login_success']); ?>
                <?php if ($isNewOwner): ?>
                    triggerAddBusinessModal();
                <?php endif; ?>
            });
        };
    </script> -->


    <script>
        function printTable(tableId, title) {
            const table = document.getElementById(tableId);

            // Create a new window for printing
            const printWindow = window.open('', '_blank', 'width=800,height=600');
            printWindow.document.open();
            printWindow.document.write(`
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Print Report</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    margin: 20px;
                }
                h1 {
                    text-align: center;
                    margin-bottom: 20px;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                }
                table, th, td {
                    border: 1px solid black;
                }
                th, td {
                    padding: 10px;
                    text-align: left;
                }
                thead {
                    background-color: #333;
                    color: #fff;
                }
                tfoot {
                    background-color: #f1f1f1;
                    font-weight: bold;
                }
                button, .btn, .fas.fa-sort {
                    display: none; /* Hide sort icons and buttons in print */
                }
            </style>
        </head>
        <body>
            <h1>${title}</h1>
            ${table.outerHTML}               
        </body>
        </html>
    `);
            printWindow.print();
            printWindow.document.close();
        }

        // Attach event listeners to print buttons
        document.getElementById('printPopularProducts').addEventListener('click', () => {
            printTable('product-table', 'Popular Products Report');
        });

        document.getElementById('printRecentActivities').addEventListener('click', () => {
            printTable('recent-activities-table', 'Recent Activities Report');
        });
    </script>



    <script src="../js/chart.js"></script>
    <script src="../js/sidebar.js"></script>
    <script src="../js/sort_items.js"></script>

</body>

</html>