<?php
session_start();
require_once '../conn/conn.php';
require_once '../conn/auth.php';

validateSession('owner');

$owner_id = $_SESSION['user_id'];

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
                }).then(() => {
                    " . ($isNewOwner ? "triggerAddBusinessModal();" : "") . "
                });
            };
        </script>
    ";
    unset($_SESSION['login_success']);
}


// SQL query to get the business and branch data
$sql = "SELECT b.name AS business_name, br.location AS branch_location, br.business_id
        FROM business b
        JOIN branch br ON b.id = br.business_id";
$result = $conn->query($sql);

$businessData = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $businessData[$row['business_name']][] = $row['branch_location'];
    }
} else {
    echo "No data found";
}

?>
<script>
    const ownerId = <?php echo json_encode($owner_id); ?>;

    function triggerAddBusinessModal() {
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
                formData.append('owner_id', ownerId);

                fetch('../endpoints/add_business_prompt.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Success', data.message, 'success').then(() => {
                                location.reload(); // Reload page to reflect the added business
                            });
                        } else {
                            Swal.fire('Error', data.message, 'error');
                        }
                    })
                    .catch(err => {
                        Swal.fire('Error', 'An unexpected error occurred.', 'error');
                        console.error(err);
                    });
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

                                <div class="scroll-container" style="height: 450px; overflow-y: auto;">
                                    <?php
                                    foreach ($businessData as $businessName => $branches) {
                                        echo '<button class="col-md-12 card">';
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

                            <div class="col-md-12 mt-5">
                                <h1 class="section-title"><b><i class="fas fa-boxes icon"></i> Popular
                                        Products/Services</b></h1>
                                <div class="col-md-12 dashboard-content">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>Product/Service</th>
                                                <th>Category</th>
                                                <th>Popularity</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><i class="fas fa-laptop icon"></i> Laptop Repair</td>
                                                <td>Services</td>
                                                <td><i class="fas fa-fire icon"></i> High</td>
                                            </tr>
                                            <tr>
                                                <td><i class="fas fa-tshirt icon"></i> Custom T-Shirts</td>
                                                <td>Products</td>
                                                <td><i class="fas fa-chart-line icon"></i> Moderate</td>
                                            </tr>
                                            <tr>
                                                <td><i class="fas fa-coffee icon"></i> Coffee Beans</td>
                                                <td>Products</td>
                                                <td><i class="fas fa-arrow-up icon"></i> Trending</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="col-md-12 mt-5">
                                <h1 class="section-title"><b><i class="fas fa-history icon"></i> Recent Activities</b>
                                </h1>
                                <div class="col-md-12 dashboard-content">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>Activity</th>
                                                <th>Date</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><i class="fas fa-user-plus icon"></i> New User Registered</td>
                                                <td>2024-11-20</td>
                                                <td><i class="fas fa-check-circle icon"></i> Completed</td>
                                            </tr>
                                            <tr>
                                                <td><i class="fas fa-file-alt icon"></i> Report Generated</td>
                                                <td>2024-11-21</td>
                                                <td><i class="fas fa-spinner icon"></i> In Progress</td>
                                            </tr>
                                            <tr>
                                                <td><i class="fas fa-shopping-cart icon"></i> Product Ordered</td>
                                                <td>2024-11-22</td>
                                                <td><i class="fas fa-times-circle icon"></i> Failed</td>
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

    <script src="../js/chart.js"></script>
    <script src="../js/sidebar.js"></script>
    
</body>

</html>