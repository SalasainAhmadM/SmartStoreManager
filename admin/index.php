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
    </style>
</head>

<body class="d-flex">
    <div id="particles-js"></div>
    <?php include '../components/admin_sidebar.php'; ?>

    <!-- Pending Owners Notification -->
    <?php if ($pending_owners > 0): ?>
        <div class="pending-notification">
            <div class="alert alert-danger d-flex align-items-center shadow-lg">
                <i class="fas fa-exclamation-circle fa-2x me-3"></i>
                <div>
                    <h5 class="mb-1">Pending Owner Approvals</h5>
                    <p class="mb-0">You have <strong><?= $pending_owners ?></strong> owner accounts waiting for approval</p>
                </div>
                <a href="accounts.php?filter=pending" class="btn btn-sm btn-outline-light ms-3">Review Now</a>
            </div>
        </div>
    <?php endif; ?>

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
                        <div class="col-md-6 col-lg-3">
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
                        </div>

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

    <script>
        // Auto-hide the pending notification after 10 seconds
        $(document).ready(function () {
            setTimeout(function () {
                $('.pending-notification').fadeOut('slow');
            }, 10000);

            // Click to hide
            $('.pending-notification').click(function () {
                $(this).fadeOut('fast');
            });
        });
    </script>
</body>

</html>