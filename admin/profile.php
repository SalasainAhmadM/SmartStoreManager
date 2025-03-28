<?php
session_start();
require_once '../conn/conn.php';
require_once '../conn/auth.php';

validateSession('admin');

$admin_id = $_SESSION['user_id'];

// Query to fetch admin details
$query = "SELECT * FROM admin WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();

$admin = $result->fetch_assoc();

$admin = array_map(function ($value) {
    return empty($value) ? ' ' : htmlspecialchars($value);
}, $admin);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile</title>
    <link rel="icon" href="../assets/logo.png">
    <?php include '../components/head_cdn.php'; ?>
</head>

<body class="d-flex">

    <div id="particles-js"></div>

    <?php include '../components/admin_sidebar.php'; ?>

    <div class="container-fluid page-body">
        <div class="row">
            <div class="col-md-12 dashboard-body">
                <div class="dashboard-content">
                    <div class="profile-container">
                        <div class="profile-header">
                            <div class="form-group-img-profile ">
                                <img id="profile_picture_display"
                                    src="../assets/profiles/<?= !empty($admin['image']) ? $admin['image'] : 'profile.png' ?>"
                                    alt="Profile Picture" class="profile-pic"
                                    style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover;">
                                <a type="button" class="text-primary me-3 fas fa-edit"
                                    onclick="editProfilePicture()"></a>
                            </div>
                            <h1>Owner Profile</h1>
                        </div>
                        <form class="profile-form">
                            <div class="form-group">
                                <i class="fas fa-user"></i>
                                <span id="full_name_display">
                                    <b><?= $admin['first_name'] . ' ' . $admin['middle_name'] . ' ' . $admin['last_name']; ?></b>
                                </span>
                                <a type="button" class="text-primary me-3 fas fa-edit" onclick="editFullName()"></a>
                            </div>
                            <div class="form-group">
                                <i class="fas fa-envelope"></i>
                                <span id="email_display"><b><?= $admin['email']; ?></b></span>
                                <a type="button" class="text-primary me-3 fas fa-edit" onclick="editEmail()"></a>
                            </div>
                            <div class="form-group">
                                <i class="fas fa-lock"></i>
                                <span id="password_display"><b>**********</b></span>
                                <a type="button" class="text-primary me-3 fas fa-edit" onclick="editPassword()"></a>
                            </div>


                            <div class="form-group">
                                <i class="fas fa-user-tie"></i>
                                <span id="role_display"><b>
                                        <div href="#" class="btn btn-dark text-white me-3" disabled>Role: Admin</div>
                                    </b></span>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="../js/sidebar.js"></script>
    <script src="../js/profile_admin.js"></script>

</body>

</html>