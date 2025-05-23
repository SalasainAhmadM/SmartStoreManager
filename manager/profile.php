<?php
session_start();
require_once '../conn/auth.php';
require_once '../conn/conn.php';
validateSession('manager');

$manager_id = $_SESSION['user_id'];

// Query to fetch manager details
$query = "SELECT * FROM manager WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $manager_id);
$stmt->execute();
$result = $stmt->get_result();

$manager = $result->fetch_assoc();

$manager = array_map(function ($value) {
    return empty($value) ? ' ' : htmlspecialchars($value);
}, $manager);
// Determine the profile image
$profileImage = !empty($manager['image']) ? htmlspecialchars($manager['image']) : 'profile.png';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Profile</title>
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
                    <div class="profile-container">
                        <div class="profile-header">
                            <div class="form-group-img-profile ">
                                <img id="profile_picture_display"
                                    src="../assets/profiles/<?= !empty($manager['image']) ? $manager['image'] : 'profile.png' ?>"
                                    alt="Profile Picture" class="profile-pic"
                                    style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover;">
                                <a type="button" class="text-primary me-3 fas fa-edit"
                                    onclick="editProfilePicture()"></a>
                            </div>
                            <h1>Manager Profile</h1>
                        </div>
                        <form class="profile-form">
                            <div class="form-group">
                                <i class="fas fa-user"></i>
                                <span id="full_name_display">
                                    <b><?= $manager['first_name'] . ' ' . $manager['middle_name'] . ' ' . $manager['last_name']; ?></b>
                                </span>
                                <a type="button" class="text-primary me-3 fas fa-edit" onclick="editFullName()"></a>
                            </div>
                            <div class="form-group">
                                <i class="fas fa-envelope"></i>
                                <span id="email_display"><b><?= $manager['email']; ?></b></span>
                                <a type="button" class="text-primary me-3 fas fa-edit" onclick="editEmail()"></a>
                            </div>
                            <div class="form-group">
                                <i class="fas fa-lock"></i>
                                <span id="password_display"><b>**********</b></span>
                                <a type="button" class="text-primary me-3 fas fa-edit" onclick="editPassword()"></a>
                            </div>
                            <div class="form-group">
                                <i class="fas fa-phone-alt"></i>
                                <span id="phone_display"><b><?= $manager['contact_number']; ?></b></span>
                                <a type="button" class="text-primary me-3 fas fa-edit" onclick="editPhone()"></a>
                            </div>
                            <div class="form-group">
                                <i class="fas fa-home"></i>
                                <span
                                    id="address_display"><b><?= $manager['barangay'] . ',' . $manager['city'] . ',' . $manager['province'] . ',' . $manager['region']; ?></b></span>
                                <a type="button" class="text-primary me-3 fas fa-edit" onclick="editAddress()"></a>
                            </div>
                            <div class="form-group">
                                <i class="fas fa-venus-mars"></i>
                                <span id="gender_display"><b><?= $manager['gender']; ?></b></span>
                                <a type="button" class="text-primary me-3 fas fa-edit" onclick="editGender()"></a>
                            </div>
                            <div class="form-group">
                                <i class="fas fa-birthday-cake"></i>
                                <span id="age_display"><b><?= $manager['age']; ?></b></span>
                                <a type="button" class="text-primary me-3 fas fa-edit" onclick="editAge()"></a>
                            </div>
                            <div class="form-group">
                                <i class="fas fa-calendar-alt"></i>
                                <span id="birthday_display"><b><?= $manager['birthday']; ?></b></span>
                                <a type="button" class="text-primary me-3 fas fa-edit" onclick="editBirthday()"></a>
                            </div>
                            <div class="form-group">
                                <i class="fas fa-user-tie"></i>
                                <span id="role_display"><b>
                                        <div href="#" class="btn btn-dark text-white me-3" disabled>Role: Manager</div>
                                    </b></span>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/sidebar.js"></script>

    <script src="../js/profile_manager.js"></script>

</body>

</html>