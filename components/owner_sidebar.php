<?php
function isActive($link)
{
    $current_page = basename($_SERVER['PHP_SELF']);
    return $current_page === $link ? 'active' : 'link-dark';
}


function logout() {
    session_unset();
    session_destroy();
    // Redirect to the login page (or another page)
    header("Location: ../index.php");
    exit();
}

if (isset($_GET['logout'])) {
    logout();
}
?>

<div class="d-flex flex-column flex-shrink-0 p-3 bg-light collapsed" id="sidebar" style="width: 80px; height: 100vh; transition: all 0.3s ease;">
    <div class="d-flex align-items-center mb-5">
        <div id="sidebarLogo">
            <img src="../assets/logo.png" style="height: 25px;">
        </div>
        <div id="sidebarLogo"><span class="fs-4 mr-5 ms-1"><b>Owner</b></span></div>
        <button class="btn btn-outline-dark" id="sidebarToggle" style="height: 40px;">
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
        <a href="#" class="d-flex align-items-center link-dark text-decoration-none dropdown-toggle" id="dropdownUser2" data-bs-toggle="dropdown" aria-expanded="false">
            <img src="https://github.com/mdo.png" alt="" width="32" height="32" class="rounded-circle me-2">
            <strong id="sidebarLogo">mdo</strong>
        </a>
        <ul class="dropdown-menu text-small shadow" aria-labelledby="dropdownUser2">
            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-circle"></i> Profile</a></li>
            <li>
                <hr class="dropdown-divider">
            </li>
            <li><a class="dropdown-item" href="?logout=true"> <i class="fas fa-sign-out-alt"></i> Sign out</a></li>
        </ul>
    </div>
</div>


<?php include '../components/js_cdn.php' ?>
<script src="../js/particle.js"></script>