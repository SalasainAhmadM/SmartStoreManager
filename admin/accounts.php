<?php
session_start();
require_once '../conn/auth.php';
require_once '../conn/conn.php';
validateSession('admin');
date_default_timezone_set('Asia/Manila');
$admin_id = $_SESSION['user_id'];

// Fetch owner accounts with sorting
$query = "SELECT * FROM owner ORDER BY is_approved ASC, is_verified ASC, created_at DESC";
$result = $conn->query($query);
$owners = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Owner Accounts</title>
    <link rel="icon" href="../assets/logo.png">
    <?php include '../components/head_cdn.php'; ?>
    <style>
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.9em;
        }

        .thumbnail-img,
        .thumbnail-img-id {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 5px;
            cursor: pointer;
        }

        @media (max-width: 767.98px) {
            .container-fluid.page-body {
                padding: 0 15px;
            }

            #ownerSearchInput {
                width: 100% !important;
                margin-bottom: 15px;
            }

            .scrollable-table {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            #ownersTable {
                min-width: 800px;
            }

            .table td,
            .table th {
                padding: 0.75rem;
                font-size: 14px;
                vertical-align: middle;
            }

            .thumbnail-img,
            .thumbnail-img-id {
                max-width: 40px;
                height: auto;
            }

            .status-badge {
                font-size: 12px;
                padding: 4px 8px;
                white-space: nowrap;
            }

            .approve-btn {
                padding: 6px 10px !important;
                font-size: 13px;
                white-space: nowrap;
            }

            .btn-primary {
                width: 100%;
                margin-top: 15px;
            }
        }

        @media (max-width: 575.98px) {
            .dashboard-content h1 {
                font-size: 24px;
            }

            .dashboard-content h4 {
                font-size: 18px;
            }

            .table td,
            .table th {
                padding: 0.5rem;
                font-size: 13px;
            }

            td:nth-child(2) {
                min-width: 120px;
            }

            td:nth-child(3) {
                min-width: 150px;
            }
        }
    </style>
</head>

<body class="d-flex">

    <div id="particles-js"></div>

    <?php include '../components/admin_sidebar.php'; ?>

    <div class="container-fluid page-body">
        <div class="row">
            <div class="col-md-12 dashboard-body">
                <div class="dashboard-content">
                    <h1><b>Owner Accounts</b></h1>
                    <h4 class="mt-5"><b><i class="fas fa-users-cog me-2"></i> Manage Owner Accounts</b></h4>
                    <div class="card-one">

                        <div id="ownerAccountsPanel">
                            <div class="mt-4 position-relative">
                                <form class="d-flex" role="search">
                                    <input class="form-control me-2 w-50" type="search" placeholder="Search owners..."
                                        aria-label="Search" id="ownerSearchInput">
                                </form>
                            </div>

                            <div class="scrollable-table mt-3" id="ownerlist">
                                <table class="table" id="ownersTable">
                                    <thead class="table-dark position-sticky top-0">
                                        <tr>
                                            <th>Profile</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Contact</th>
                                            <th>Status</th>
                                            <th>Registered</th>
                                            <th>Valid ID</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="ownersBody">
                                        <?php foreach ($owners as $owner): ?>
                                            <?php
                                            $status = '';
                                            $statusClass = '';
                                            if ($owner['is_approved'] == 0) {
                                                $status = 'Pending Approval';
                                                $statusClass = 'bg-warning text-dark';
                                            } else if ($owner['is_verified'] == 0) {
                                                $status = 'Unverified';
                                                $statusClass = 'bg-info text-white';
                                            } else {
                                                $status = 'Active';
                                                $statusClass = 'bg-success text-white';
                                            }
                                            ?>
                                            <tr>
                                                <td><?php if ($owner['image']): ?>
                                                        <img src="../assets/profiles/<?= $owner['image'] ?>"
                                                            class="thumbnail-img"
                                                            data-full="../assets/profiles/<?= $owner['image'] ?>"
                                                            title="Click to view">
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?= htmlspecialchars($owner['first_name'] . ' ' . $owner['middle_name'] . ' ' . $owner['last_name']) ?>
                                                </td>
                                                <td><?= htmlspecialchars($owner['email']) ?></td>
                                                <td><?= htmlspecialchars($owner['contact_number']) ?></td>
                                                <td>
                                                    <span class="status-badge <?= $statusClass ?>">
                                                        <?= $status ?>
                                                    </span>
                                                </td>
                                                <td><?= date('M d, Y h:i A', strtotime($owner['created_at'])) ?></td>
                                                <td>
                                                    <?php if ($owner['valid_id']): ?>
                                                        <img src="../assets/valid_ids/<?= $owner['valid_id'] ?>"
                                                            class="thumbnail-img-id"
                                                            data-full="../assets/valid_ids/<?= $owner['valid_id'] ?>"
                                                            title="Click to view">
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($owner['is_approved'] == 0): ?>
                                                        <button class="btn btn-sm btn-success approve-btn"
                                                            data-owner-id="<?= $owner['id'] ?>">
                                                            <i class="fas fa-check"></i> Approve
                                                        </button>
                                                    <?php else: ?>
                                                        <button class="btn btn-sm btn-secondary" disabled>
                                                            Approved
                                                        </button>
                                                    <?php endif; ?>
                                                </td>

                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>

                                <button class="btn btn-primary mt-2 mb-5"
                                    onclick="printContent('ownerlist', 'Owners List')">
                                    <i class="fas fa-print me-2"></i> Generate Report (Owner List)
                                </button>

                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imageModalLabel">Image Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalImage" src="" class="img-fluid">
                </div>
            </div>
        </div>
    </div>
    <!-- Image Modal -->
    <div class="modal fade" id="imageModalID" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imageModalLabel">Valid ID Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalImageID" src="" class="img-fluid">
                </div>
            </div>
        </div>
    </div>

    <script src="../js/sidebar_admin.js"></script>
    <script>
        // Image preview
        document.querySelectorAll('.thumbnail-img').forEach(img => {
            img.addEventListener('click', () => {
                document.getElementById('modalImage').src = img.dataset.full;
                new bootstrap.Modal(document.getElementById('imageModal')).show();
            });
        });
        // Image preview
        document.querySelectorAll('.thumbnail-img-id').forEach(img => {
            img.addEventListener('click', () => {
                document.getElementById('modalImageID').src = img.dataset.full;
                new bootstrap.Modal(document.getElementById('imageModalID')).show();
            });
        });

        // Search functionality
        document.getElementById('ownerSearchInput').addEventListener('input', function () {
            const searchTerm = this.value.toLowerCase();
            document.querySelectorAll('#ownersBody tr').forEach(row => {
                const name = row.cells[0].textContent.toLowerCase();
                const email = row.cells[1].textContent.toLowerCase();
                const contact = row.cells[2].textContent.toLowerCase();
                if (name.includes(searchTerm) || email.includes(searchTerm) || contact.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });


        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.approve-btn').forEach(button => {
                button.addEventListener('click', function () {
                    const ownerId = this.getAttribute('data-owner-id');

                    Swal.fire({
                        title: 'Confirm Approval',
                        text: 'Are you sure you want to approve this owner?',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, approve it!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            fetch('../endpoints/approve_owner.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                },
                                body: JSON.stringify({ owner_id: ownerId })
                            })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        let message = 'Owner has been approved.';
                                        if (data.email_status && !data.email_status.sent) {
                                            message += ' But failed to send approval email.';
                                        }
                                        Swal.fire({
                                            title: 'Approved!',
                                            text: message,
                                            icon: 'success',
                                            confirmButtonText: 'OK'
                                        }).then(() => {
                                            location.reload();
                                        });
                                    }
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                    Swal.fire({
                                        title: 'Error!',
                                        text: 'An error occurred while approving the owner.',
                                        icon: 'error',
                                        confirmButtonText: 'OK'
                                    });
                                });
                        }
                    });
                });
            });
        });

        function printContent(elementId, title) {
            const content = document.getElementById(elementId);
            if (!content) {
                alert("Content not found.");
                return;
            }

            const printWindow = window.open('', '', 'height=600,width=800');
            printWindow.document.write('<html><head><title>' + title + '</title>');
            printWindow.document.write('<style>');
            printWindow.document.write(`
            body { 
                font-family: Arial, sans-serif; 
                margin: 20px; 
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            table { 
                width: 100%; 
                border-collapse: collapse; 
                margin: 20px 0; 
            }
            th, td { 
                border: 1px solid #ddd; 
                padding: 8px; 
                text-align: left; 
            }
            th {
                background-color: #333 !important;
                color: #fff !important; 
            }
            .status-badge {
                padding: 4px 8px;
                border-radius: 4px;
                font-weight: normal;
                display: inline-block;
            }
            .bg-success {
                background-color: #28a745 !important;
                color: white !important;
            }
            .bg-warning {
                background-color: #ffc107 !important;
                color: black !important;
            }
            .bg-info {
                background-color: #17a2b8 !important;
                color: white !important;
            }
            img {
                height: 40px;
                width: auto;
            }
            @media print {
                body { width: 100%; padding: 0; }
                th, td { font-size: 12px; }
            }
        `);
            printWindow.document.write('</style></head><body>');
            printWindow.document.write('<h1>' + title + '</h1>');
            printWindow.document.write(content.innerHTML);
            printWindow.document.write('</body></html>');

            printWindow.document.close();
            printWindow.focus();
            printWindow.print();
            printWindow.close();
        }
    </script>

</body>

</html>