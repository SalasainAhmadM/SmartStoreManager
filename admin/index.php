<?php
session_start();
require_once '../conn/auth.php';
require_once '../conn/conn.php';
validateSession('admin');
date_default_timezone_set('Asia/Manila');
$admin_id = $_SESSION['user_id'];

// Get counts from database
$owner_count = $conn->query("SELECT COUNT(*) FROM owner")->fetch_row()[0];
$manager_count = $conn->query("SELECT COUNT(*) FROM manager")->fetch_row()[0];
$business_count = $conn->query("SELECT COUNT(*) FROM business")->fetch_row()[0];
$branch_count = $conn->query("SELECT COUNT(*) FROM branch")->fetch_row()[0];
$pending_owners = $conn->query("SELECT COUNT(*) FROM owner WHERE is_approved = 0")->fetch_row()[0];
$pending_businesses = $conn->query("SELECT COUNT(*) FROM business WHERE is_approved = 0")->fetch_row()[0];
$pending_branches = $conn->query("SELECT COUNT(*) FROM branch WHERE is_approved = 0")->fetch_row()[0];
// Get recent activities
$activities = $conn->query("SELECT * FROM activity ORDER BY created_at DESC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="icon" href="../assets/logo.png">
    <?php include '../components/head_cdn.php'; ?>
    <style>
        .stat-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            height: 100%;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }

        .stat-value {
            font-size: 2.2rem;
            font-weight: 700;
        }

        .stat-label {
            font-size: 1rem;
            color: #ffffff;
        }

        .pending-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }

            100% {
                transform: scale(1);
            }
        }

        .activity-badge {
            font-size: 0.75rem;
        }

        .activity-row:hover {
            background-color: #f8f9fa;
        }

        @media (max-width: 767.98px) {
            .container-fluid.page-body {
                padding: 0 15px;
            }

            .notif-container {
                left: 15px;
                right: 15px;
                top: 70px;
                max-width: none;
            }

            .pending-alert {
                width: 100%;
            }

            .pending-notification .alert {
                flex-direction: column;
                align-items: flex-start;
                padding: 15px;
            }

            .pending-notification i {
                margin-bottom: 10px;
            }

            .pending-notification .btn {
                width: 100%;
                margin-top: 10px;
                text-align: center;
            }

            .stat-card .col-md-6 {
                width: 100%;
                margin-bottom: 15px;
            }

            .stat-icon {
                font-size: 1.5rem !important;
            }

            .stat-value {
                font-size: 1.8rem !important;
            }

            .stat-label {
                font-size: 1rem !important;
            }

            .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            .table {
                min-width: 600px;
            }

            .activity-row td {
                white-space: nowrap;
            }

            .badge {
                font-size: 12px !important;
            }
        }

        @media (max-width: 575.98px) {
            .dashboard-content h1 {
                font-size: 24px;
            }

            .stat-card .card-body {
                padding: 20px 15px !important;
            }

            .stat-value {
                font-size: 1.5rem !important;
            }

            .table th,
            .table td {
                padding: 0.75rem;
                font-size: 14px;
            }

            .activity-badge {
                font-size: 10px !important;
            }
        }

        .notif-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-width: 400px;
        }

        .pending-alert {
            animation: pulse 2s infinite;
            margin: 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
            border: none;
        }

        .pending-notification {
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .stat-card {
            transition: transform 0.2s;
            height: 100%;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            font-size: 2rem;
        }

        .stat-value {
            font-size: 2.2rem;
            font-weight: bold;
        }

        .stat-label {
            font-size: 1.1rem;
            letter-spacing: 0.5px;
        }

        .table thead th {
            background-color: #f8f9fa;
            position: sticky;
            top: 0;
            z-index: 1;
        }
    </style>
</head>

<body class="d-flex">
    <div id="particles-js"></div>
    <?php include '../components/admin_sidebar.php'; ?>

    <!-- Notifications Container -->
    <div class="notif-container">
        <?php if ($pending_owners > 0): ?>
            <div class="alert alert-danger d-flex align-items-center pending-alert">
                <i class="fas fa-user-clock fa-lg me-3"></i>
                <div class="flex-grow-1">
                    <h5 class="mb-1">Pending Owner Approvals</h5>
                    <p class="mb-0"><?= $pending_owners ?> owner account(s) awaiting review</p>
                </div>
                <a href="accounts.php?filter=pending" class="btn btn-sm btn-outline-light ms-3">Review</a>
            </div>
        <?php endif; ?>

        <?php if ($pending_businesses > 0): ?>
            <div class="alert alert-warning d-flex align-items-center pending-alert">
                <i class="fas fa-store-alt fa-lg me-3"></i>
                <div class="flex-grow-1">
                    <h5 class="mb-1">Pending Business Registrations</h5>
                    <p class="mb-0"><?= $pending_businesses ?> business(es) need approval</p>
                </div>
                <a href="business.php?filter=pending" class="btn btn-sm btn-outline-dark ms-3">Review</a>
            </div>
        <?php endif; ?>

        <?php if ($pending_branches > 0): ?>
            <div class="alert alert-info d-flex align-items-center pending-alert">
                <i class="fas fa-code-branch fa-lg me-3"></i>
                <div class="flex-grow-1">
                    <h5 class="mb-1">Pending Branch Registrations</h5>
                    <p class="mb-0"><?= $pending_branches ?> branch(es) need approval</p>
                </div>
                <a href="business.php?filter=pending" class="btn btn-sm btn-outline-dark ms-3">Review</a>
            </div>
        <?php endif; ?>
    </div>

    <div class="container-fluid page-body">
        <div class="row">
            <div class="col-md-12 dashboard-body">
                <div class="dashboard-content">
                    <h1><b>Admin Dashboard</b></h1>

                    <!-- Statistics Cards -->
                    <div class="row mt-4 g-4">
                        <!-- Owners Card -->
                        <div class="col-md-6 col-lg-3">
                            <div class="card stat-card bg-primary text-white">
                                <div class="card-body text-center py-4">
                                    <i class="fas fa-user-tie stat-icon mb-3"></i>
                                    <div class="stat-value"><?= $owner_count ?></div>
                                    <div class="stat-label">Owner Accounts</div>
                                </div>
                                <div class="card-footer bg-primary-dark text-center py-2">
                                    <a href="accounts.php" class="text-white stretched-link">View Details</a>
                                </div>
                            </div>
                        </div>

                        <!-- Managers Card -->
                        <!-- <div class="col-md-6 col-lg-3">
                            <div class="card stat-card bg-success text-white">
                                <div class="card-body text-center py-4">
                                    <i class="fas fa-user-shield stat-icon mb-3"></i>
                                    <div class="stat-value"><?= $manager_count ?></div>
                                    <div class="stat-label">Manager Accounts</div>
                                </div>
                                <div class="card-footer bg-success-dark text-center py-2">
                                    <a href="accounts.php" class="text-white stretched-link">View Details</a>
                                </div>
                            </div>
                        </div> -->

                        <!-- Businesses Card -->
                        <div class="col-md-6 col-lg-3">
                            <div class="card stat-card bg-info text-white">
                                <div class="card-body text-center py-4">
                                    <i class="fas fa-store stat-icon mb-3"></i>
                                    <div class="stat-value"><?= $business_count ?></div>
                                    <div class="stat-label">Registered Businesses</div>
                                </div>
                                <div class="card-footer bg-info-dark text-center py-2">
                                    <a href="business.php" class="text-white stretched-link">View Details</a>
                                </div>
                            </div>
                        </div>

                        <!-- Branches Card -->
                        <div class="col-md-6 col-lg-3">
                            <div class="card stat-card bg-warning text-dark">
                                <div class="card-body text-center py-4">
                                    <i class="fas fa-code-branch stat-icon mb-3"></i>
                                    <div class="stat-value"><?= $branch_count ?></div>
                                    <div class="stat-label">Business Branches</div>
                                </div>
                                <div class="card-footer bg-warning-dark text-center py-2">
                                    <a href="business.php" class="text-dark stretched-link">View Details</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity Section -->
                    <h4 class="mt-5"><b><i class="fas fa-history me-2"></i> Recent Activity</b></h4>
                    <div class="card mt-3">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="20%">Time</th>
                                            <th>Activity</th>
                                            <th width="15%">User</th>
                                            <th width="15%">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($activity = $activities->fetch_assoc()): ?>
                                            <tr class="activity-row">
                                                <td>
                                                    <?= date('M d, Y h:i A', strtotime($activity['created_at'])) ?>
                                                </td>
                                                <td><?= htmlspecialchars($activity['message']) ?></td>
                                                <td>
                                                    <?php if ($activity['user']): ?>
                                                        <span class="badge bg-secondary"><?= $activity['user'] ?></span>
                                                    <?php else: ?>
                                                        <span class="badge bg-light text-dark">System</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    $badge_class = [
                                                        'success' => 'bg-success',
                                                        'warning' => 'bg-warning',
                                                        'error' => 'bg-danger',
                                                        'info' => 'bg-info'
                                                    ][strtolower($activity['status'])] ?? 'bg-primary';
                                                    ?>
                                                    <span class="badge <?= $badge_class ?> activity-badge">
                                                        <?= $activity['status'] ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                        <?php if ($activities->num_rows === 0): ?>
                                            <tr>
                                                <td colspan="4" class="text-center py-4">No recent activities found</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <!-- <div class="card-footer text-end">
                            <a href="activity_log.php" class="btn btn-sm btn-outline-primary">
                                View Full Activity Log <i class="fas fa-arrow-right ms-1"></i>
                            </a>
                        </div> -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/sidebar_admin.js"></script>
    <script>
        $(document).ready(function () {
            $('.pending-alert').each(function () {
                var $alert = $(this);
                setTimeout(function () {
                    $alert.fadeOut('slow');
                }, 10000);
            });

            // Click to hide individual notifications
            $('.pending-alert').click(function () {
                $(this).fadeOut('fast');
            });
        });
    </script>
</body>

</html>