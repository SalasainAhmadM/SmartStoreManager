<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Dashboard</title>
    <link rel="icon" href="../assets/logo.png">
    <?php include '../components/head_cdn.php'; ?>
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
                <h1><b>Dashboard Overview</b></h1>

                <div class="container-fluid">
                    <div class="row">
                        <h5 class="mt-5">Select Business:</h5>
                        <div class="col-md-5">
                        <div class="scroll-container" style="height: 450px; overflow-y: auto;">
                            <div class="col-md-12 card">
                                <p class="card-body">
                                    nt ut labore et dolore magna aliqua. Ut enim ad minim veniam, 
                                    quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea 
                                    commodo consequat.
                                </p>
                            </div>
                            <div class="col-md-12 card">
                                <p class="card-body">
                                    nt ut labore et dolore magna aliqua. Ut enim ad minim veniam, 
                                    quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea 
                                    commodo consequat.
                                </p>
                            </div>
                            <div class="col-md-12 card">
                                <p class="card-body">
                                    nt ut labore et dolore magna aliqua. Ut enim ad minim veniam, 
                                    quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea 
                                    commodo consequat.
                                </p>
                            </div>
                        </div>
                        </div>

                        <div class="col-md-7">
                        yes
                        </div>
                    </div>
                </div>
                
            </div>
            </div>
        </div>
    </div>

    <script src="../js/sidebar.js"></script>

</body>

</html>