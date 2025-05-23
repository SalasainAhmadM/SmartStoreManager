<?php
require_once '../conn/conn.php';
function isActive($link)
{
    $current_page = basename($_SERVER['PHP_SELF']);
    return $current_page === $link ? 'active' : 'link-dark';
}

// Query to fetch owner details
$query = "SELECT * FROM owner WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$result = $stmt->get_result();

$ownersidebar = $result->fetch_assoc();

?>

<div class="d-flex flex-column flex-shrink-0 p-3 bg-light" id="sidebar"
    style="width: 280px; height: 100vh; transition: all 0.3s ease;">
    <div class="d-flex align-items-center mb-5">
        <div id="sidebarLogo">
            <img src="../assets/logo.png" style="height: 25px;">
        </div>
        <div id="sidebarLogo"><span class="fs-4 mr-5 ms-1"><b>Owner</b></span></div>
        <button class="btn btn-outline-dark" id="sidebarToggle"
            style="height: 40px; transition: 0.45s; margin-left: 5rem;">
            <i class="fa-solid fa-bars"></i>
        </button>
    </div>

    <hr>
    <ul class="nav nav-pills flex-column mb-auto" id="sidebarMenu">
        <li class="nav-item">
            <a href="../owner/" class="nav-link <?= isActive('index.php') ?>" aria-current="page">
                <i class="fas fa-tachometer-alt me-2"></i> <span>Dashboard Overview</span>
            </a>
        </li>
        <li>
            <a href="managebusiness.php" class="nav-link <?= isActive('managebusiness.php') ?>">
                <i class="fas fa-cogs me-2"></i> <span>Manage Business</span>
            </a>
        </li>
        <li>
            <a href="manageexpenses.php" class="nav-link <?= isActive('manageexpenses.php') ?>">
                <i class="fas fa-wallet me-2"></i> <span>Manage Expenses</span>
            </a>
        </li>
        <li>
            <a href="tracksales.php" class="nav-link <?= isActive('tracksales.php') ?>">
                <i class="fas fa-chart-line me-2"></i> <span>Track Sales</span>
            </a>
        </li>
        <li>
            <a href="viewreports.php" class="nav-link <?= isActive('viewreports.php') ?>">
                <i class="fas fa-file-alt me-2"></i> <span>View Reports</span>
            </a>
        </li>
        <li>
            <a href="supervisemanagers.php" class="nav-link <?= isActive('supervisemanagers.php') ?>">
                <i class="fas fa-users me-2"></i> <span>Supervise Managers</span>
            </a>
        </li>
        <!-- <li>
            <a href="settings.php" class="nav-link <?= isActive('settings.php') ?>">
                <i class="fas fa-cog me-2"></i> <span>Settings</span>
            </a>
        </li> -->
    </ul>
    <hr>
    <div class="dropdown">
        <a href="#" class="d-flex align-items-center link-dark text-decoration-none dropdown-toggle" id="dropdownUser2"
            data-bs-toggle="dropdown" aria-expanded="false">
            <img src="../assets/profiles/<?= !empty($ownersidebar['image']) ? $ownersidebar['image'] : 'profile.png' ?>"
                alt="" width="32" height="32" class="rounded-circle me-2">
            <strong id="sidebarLogo"><?= ($ownersidebar['first_name']) ?>
                <?= ($ownersidebar['middle_name']) ?> <?= ($ownersidebar['last_name']) ?></strong>
        </a>
        <ul class="dropdown-menu text-small shadow" aria-labelledby="dropdownUser2">
            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-circle"></i> Profile</a></li>
            <li>
                <hr class="dropdown-divider">
            </li>
            <li><a class="dropdown-item" href="#" data-logout="true"> <i class="fas fa-sign-out-alt"></i> Sign out</a>
        </ul>
    </div>
</div>

<?php include '../components/js_cdn.php' ?>
<script src="../js/particle.js"></script>
<script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.querySelector('[data-logout]').addEventListener('click', function (event) {
        event.preventDefault();

        Swal.fire({
            title: 'Are you sure?',
            text: "You will be logged out!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Confirm'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = "../components/logout.php";
            }
        });
    });
</script>