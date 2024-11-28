<?php
session_start();
require_once '../conn/conn.php';
require_once '../conn/auth.php';

validateSession('owner');

$owner_id = $_SESSION['user_id'];

// Query to fetch owner details
$query = "SELECT * FROM owner WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$result = $stmt->get_result();

$owner = $result->fetch_assoc();

$owner = array_map(function ($value) {
    return empty($value) ? 'N/A' : htmlspecialchars($value);
}, $owner);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Profile</title>
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
                    <div class="profile-container">
                        <div class="profile-header">
                            <div class="form-group-img-profile ">
                                <img id="profile_picture_display" src="../assets/profiles/<?= $owner['image']; ?>"
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
                                    <b><?= $owner['first_name'] . ' ' . $owner['middle_name'] . ' ' . $owner['last_name']; ?></b>
                                </span>
                                <a type="button" class="text-primary me-3 fas fa-edit" onclick="editFullName()"></a>
                            </div>
                            <div class="form-group">
                                <i class="fas fa-envelope"></i>
                                <span id="email_display"><b><?= $owner['email']; ?></b></span>
                                <a type="button" class="text-primary me-3 fas fa-edit" onclick="editEmail()"></a>
                            </div>
                            <div class="form-group">
                                <i class="fas fa-lock"></i>
                                <span id="password_display"><b>**********</b></span>
                                <a type="button" class="text-primary me-3 fas fa-edit" onclick="editPassword()"></a>
                            </div>
                            <div class="form-group">
                                <i class="fas fa-phone-alt"></i>
                                <span id="phone_display"><b><?= $owner['contact_number']; ?></b></span>
                                <a type="button" class="text-primary me-3 fas fa-edit" onclick="editPhone()"></a>
                            </div>
                            <div class="form-group">
                                <i class="fas fa-home"></i>
                                <span id="address_display"><b><?= $owner['address']; ?></b></span>
                                <a type="button" class="text-primary me-3 fas fa-edit" onclick="editAddress()"></a>
                            </div>
                            <div class="form-group">
                                <i class="fas fa-venus-mars"></i>
                                <span id="gender_display"><b><?= $owner['gender']; ?></b></span>
                                <a type="button" class="text-primary me-3 fas fa-edit" onclick="editGender()"></a>
                            </div>
                            <div class="form-group">
                                <i class="fas fa-birthday-cake"></i>
                                <span id="age_display"><b><?= $owner['age']; ?></b></span>
                                <a type="button" class="text-primary me-3 fas fa-edit" onclick="editAge()"></a>
                            </div>
                            <div class="form-group">
                                <i class="fas fa-calendar-alt"></i>
                                <span id="birthday_display"><b><?= $owner['birthday']; ?></b></span>
                                <a type="button" class="text-primary me-3 fas fa-edit" onclick="editBirthday()"></a>
                            </div>
                            <div class="form-group">
                                <i class="fas fa-user-tie"></i>
                                <span id="role_display"><b>
                                        <div href="#" class="btn btn-dark text-white me-3" disabled>Role: Owner</div>
                                    </b></span>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="../js/sidebar.js"></script>
    <script src="../js/profile_owner.js"></script>

</body>

</html>