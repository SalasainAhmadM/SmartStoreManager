<?php
session_start();
require_once '../conn/auth.php';
require_once '../conn/conn.php';
validateSession('admin');
date_default_timezone_set('Asia/Manila');
$admin_id = $_SESSION['user_id'];

$businessQuery = "SELECT * FROM business ORDER BY is_approved ASC, created_at DESC";
$businessResult = $conn->query($businessQuery);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Business</title>
    <link rel="icon" href="../assets/logo.png">
    <?php include '../components/head_cdn.php'; ?>
</head>
<style>
    @media (max-width: 767.98px) {
        .container-fluid.page-body {
            padding: 0 15px;
        }

        .nav-pills {
            flex-direction: column;
        }

        .nav-item {
            margin-bottom: 10px;
        }

        .nav-link {
            padding: 12px !important;
        }

        .nav-link h5 {
            font-size: 16px !important;
            margin: 0;
        }

        #businessSearchInput {
            width: 100% !important;
            margin-bottom: 15px;
        }

        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .table {
            min-width: 600px;
        }

        .table td,
        .table th {
            padding: 0.75rem;
            font-size: 14px;
        }

        .clickable-row {
            cursor: pointer;
            position: relative;
        }

        .btn-warning {
            white-space: nowrap;
            font-size: 13px;
            padding: 5px 10px;
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

        .badge {
            font-size: 12px !important;
        }

        .nav-link i {
            font-size: 14px;
        }
    }

    .table-dark th {
        background-color: #343a40;
        position: sticky;
        left: 0;
    }

    .clickable-row:hover {
        background-color: #f8f9fa;
    }

    .card-one {
        border-radius: 8px;
        overflow: hidden;
    }

    #branchContent .table {
        margin-top: 15px;
    }

    .btn-primary {
        transition: all 0.3s ease;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
    }
</style>

<body class="d-flex">

    <div id="particles-js"></div>

    <?php include '../components/admin_sidebar.php'; ?>

    <div class="container-fluid page-body">
        <div class="row">
            <div class="col-md-12 dashboard-body">
                <div class="dashboard-content">
                    <h1><b>Businesses</b></h1>
                    <h4 class="mt-5"><b><i class="fas fa-tachometer-alt me-2"></i> Manage</b></h4>

                    <ul class="nav nav-pills nav-fill mt-4">
                        <li class="nav-item">
                            <a class="nav-link active" href="business.php" onclick="clearBusinessSession()">
                                <i class="fas fa-list me-2"></i>
                                <h5><b>Business</b></h5>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-tab="branchlist">
                                <i class="fas fa-building me-2"></i>
                                <h5><b>Branches</b></h5>
                            </a>
                        </li>
                    </ul>

                    <div class="mt-4 position-relative">
                        <form class="d-flex" role="search">
                            <input class="form-control me-2 w-50" type="search" placeholder="Search business/branch..."
                                aria-label="Search" id="businessSearchInput">
                        </form>
                    </div>
                    <!-- Business List Tab -->
                    <div class="tab-pane active" id="businesslist">
                        <div class="card-one mt-4">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Business Name</th>
                                            <th>Description</th>
                                            <th>Status</th>
                                            <th>Location</th>
                                            <th>Created At</th>
                                        </tr>
                                    </thead>
                                    <tbody id="businessBody">
                                        <?php while ($business = $businessResult->fetch_assoc()): ?>
                                            <tr class="clickable-row" onclick="loadBranches(<?= $business['id'] ?>)">
                                                <td><?= htmlspecialchars($business['name']) ?></td>
                                                <td><?= htmlspecialchars($business['description']) ?></td>
                                                <td>
                                                    <?php if ($business['is_approved']): ?>
                                                        <span class="badge bg-success">Approved</span>
                                                    <?php else: ?>
                                                        <button class="btn btn-warning btn-sm"
                                                            onclick="event.stopPropagation(); approveBusiness(<?= $business['id'] ?>)">Pending
                                                            Approval</button>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= htmlspecialchars($business['location']) ?></td>
                                                <td><?= date('M d, Y h:i A', strtotime($business['created_at'])) ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                                <button class="btn btn-primary mt-2 mb-5"
                                    onclick="printContent('businesslist', 'Business List Report')">
                                    <i class="fas fa-print me-2"></i> Generate Report (Business List)
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Branch List Tab -->
                    <div class="tab-pane" id="branchlist">
                        <div class="card-one mt-2">
                            <div id="branchContent">
                                <!-- Business header and branches will be loaded here via AJAX -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>

        // Search functionality
        document.getElementById('businessSearchInput').addEventListener('input', function () {
            const searchTerm = this.value.toLowerCase();
            document.querySelectorAll('#businessBody tr').forEach(row => {
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
        document.getElementById('branchSearchInput').addEventListener('input', function () {
            const searchTerm = this.value.toLowerCase();
            document.querySelectorAll('#branchBody tr').forEach(row => {
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
        function formatDate(dateString) {
            const date = new Date(dateString);
            const options = {
                month: 'short',
                day: 'numeric',
                year: 'numeric',
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            };
            return date.toLocaleDateString('en-US', options);
        }

        function loadBranches(businessId) {
            sessionStorage.setItem('selectedBusinessId', businessId);
            sessionStorage.setItem('activeTab', 'branchlist');
            fetch(`../endpoints/branch/get_branches.php?business_id=${businessId}`)
                .then(response => response.json())
                .then(data => {
                    const branchContent = document.getElementById('branchContent');
                    let html = `
                     <div class="business-header mb-4">
                             <div class="d-flex justify-content-between align-items-center mb-3">
                        <a href="business.php" class="btn btn-secondary" onclick="clearBusinessSession()">
                            <i class="fas fa-arrow-left me-2"></i>Back to Businesses
                        </a>
                    </div>
                 
                    </div>
                            <h3 class="mb-0">${data.business.name}</h3>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Description:</strong> ${data.business.description}</p>
                                <p class="mb-1"><strong>Location:</strong> ${data.business.location}</p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Status:</strong> ${data.business.is_approved ? '<span class="badge bg-success">Approved</span>' : '<span class="badge bg-warning">Pending Approval</span>'}</p>
                                <p class="mb-1"><strong>Registered:</strong> ${formatDate(data.business.created_at)}</p>
                            </div>
                        </div>
                        <hr>
                        <h5 class="mb-3"><b>Branches</b></h5>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Location</th>
                                        <th>Status</th>
                                        <th>Created At</th>
                                    </tr>
                                </thead>
                                <tbody  id="businessBody">
                                    ${data.branches.length ?
                            data.branches.map(branch => `
                                            <tr>
                                                <td>${branch.location}</td>
                                                <td>${branch.is_approved ? '<span class="badge bg-success">Approved</span>' : `<button class="btn btn-warning btn-sm" onclick="approveBranch(${branch.id}, ${data.business.id})">Pending Approval</button>`}</td>
                                                <td>${formatDate(branch.created_at)}</td>
                                            </tr>
                                        `).join('') :
                            `<tr>
                                            <td colspan="3" class="text-center py-4">
                                                <div class="alert alert-info m-0">No branches found for this business.</div>
                                            </td>
                                        </tr>`
                        }
                                </tbody>
                            </table>
                            <button class="btn btn-primary mt-2 mb-5"
                                    onclick="printContent('branchlist', 'Branch List Report')">
                                    <i class="fas fa-print me-2"></i> Generate Report (Branch List)
                                </button>
                        </div>
                    </div>
                `;
                    branchContent.innerHTML = html;

                    // Switch to branch tab and hide business list
                    document.querySelectorAll('.nav-link').forEach(link => link.classList.remove('active'));
                    document.querySelector('[data-tab="branchlist"]').classList.add('active');
                    document.querySelectorAll('.tab-pane').forEach(pane => {
                        pane.classList.remove('active', 'show');
                        pane.style.display = 'none'; // Ensure tab panes are hidden
                    });
                    document.getElementById('branchlist').classList.add('active', 'show');
                    document.getElementById('branchlist').style.display = 'block';
                    document.getElementById('businesslist').style.display = 'none';
                })
                .catch(error => console.error('Error:', error));
        }

        function showBusinessList() {
            sessionStorage.removeItem('selectedBusinessId');
            sessionStorage.removeItem('activeTab');

            document.querySelectorAll('.nav-link').forEach(link => link.classList.remove('active'));
            document.querySelector('[data-tab="businesslist"]').classList.add('active');
            document.querySelectorAll('.tab-pane').forEach(pane => {
                pane.classList.remove('active', 'show');
                pane.style.display = 'none'; // Reset display property
            });
            document.getElementById('businesslist').classList.add('active', 'show');
            document.getElementById('businesslist').style.display = 'block';
            document.getElementById('branchContent').innerHTML = '';
        }

        function approveBusiness(businessId) {
            Swal.fire({
                title: 'Approve Business?',
                text: 'This will approve the business!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, approve it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`../endpoints/business/approve.php?id=${businessId}`, {
                        method: 'POST'
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire('Approved!', 'Business has been approved.', 'success')
                                    .then(() => {
                                        location.reload();
                                    });
                            } else {
                                Swal.fire('Error', data.message || 'Failed to approve business', 'error');
                            }
                        })
                        .catch(error => {
                            Swal.fire('Error', 'An error occurred while approving.', 'error');
                        });
                }
            });
        }


        function approveBranch(branchId, businessId) {
            Swal.fire({
                title: 'Approve Branch?',
                text: 'Are you sure you want to approve this branch?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, approve it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`../endpoints/branch/approve.php?id=${branchId}`, {
                        method: 'POST'
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire('Approved!', 'Branch has been approved.', 'success')
                                    .then(() => {
                                        location.reload();
                                    });
                            } else {
                                Swal.fire('Error', data.message || 'Failed to approve branch', 'error');
                            }
                        })
                        .catch(error => {
                            Swal.fire('Error', 'An error occurred while approving.', 'error');
                        });
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            const selectedBusinessId = sessionStorage.getItem('selectedBusinessId');
            const activeTab = sessionStorage.getItem('activeTab');

            if (selectedBusinessId && activeTab === 'branchlist') {
                loadBranches(selectedBusinessId);
            }
        });

        function clearBusinessSession() {
            // Clear the stored session data
            sessionStorage.removeItem('selectedBusinessId');
            sessionStorage.removeItem('activeTab');
        }

        document.addEventListener('DOMContentLoaded', function () {
            const selectedBusinessId = sessionStorage.getItem('selectedBusinessId');
            const activeTab = sessionStorage.getItem('activeTab');

            if (selectedBusinessId && activeTab === 'branchlist') {
                loadBranches(selectedBusinessId);
            } else {
                document.getElementById('businesslist').style.display = 'block';
                document.getElementById('branchlist').style.display = 'none';
            }
        });

        function printContent(tabId, title) {
            var table = document.getElementById(tabId).getElementsByTagName('table')[0];
            var headers = table.getElementsByTagName('th');
            var shouldDeleteLastColumn = false;

            // Check if we need to remove the last column (Action column)
            for (var i = 0; i < headers.length; i++) {
                if (headers[i].textContent.includes('Action')) {
                    shouldDeleteLastColumn = true;
                    break;
                }
            }

            // Remove last column if needed
            if (shouldDeleteLastColumn) {
                var rows = table.rows;
                for (var i = 0; i < rows.length; i++) {
                    rows[i].deleteCell(rows[i].cells.length - 1);
                }
            }

            // Create print window
            var printWindow = window.open('', '', 'height=600,width=800');
            printWindow.document.write('<html><head><title>' + title + '</title>');
            printWindow.document.write('<style>');
            printWindow.document.write(`
    body { 
      font-family: Arial, sans-serif; 
      margin: 20px; 
      -webkit-print-color-adjust: exact !important;
      print-color-adjust: exact !important;
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
    .badge {
      padding: 4px 8px;
      border-radius: 4px;
      font-weight: normal;
    }
    .bg-success {
      background-color: #28a745 !important;
      color: white !important;
    }
    button.btn {
      background: none !important;
      border: none !important;
      padding: 0 !important;
      margin: 0 !important;
      color: #000 !important;
      box-shadow: none !important;
    }
    @media print {
      body { 
        width: 100%; 
        padding: 0; 
      }
      th, td { 
        font-size: 12px; 
      }
    }
  `);
            printWindow.document.write('</style></head><body>');
            printWindow.document.write('<h1>' + title + '</h1>');
            printWindow.document.write(table.outerHTML);
            printWindow.document.write('</body></html>');

            // Print and refresh
            printWindow.document.close();
            printWindow.print();
            location.reload();
        }

        function printContent(tabId, title) {
            // Clone the original table to avoid modifying the DOM
            var originalTable = document.getElementById(tabId).getElementsByTagName('table')[0];
            var table = originalTable.cloneNode(true);

            // Process Action column if present
            var headers = table.getElementsByTagName('th');
            var shouldDeleteLastColumn = false;
            for (var i = 0; i < headers.length; i++) {
                if (headers[i].textContent.includes('Action')) {
                    shouldDeleteLastColumn = true;
                    break;
                }
            }

            // Remove last column if needed
            if (shouldDeleteLastColumn) {
                var rows = table.rows;
                for (var i = 0; i < rows.length; i++) {
                    if (rows[i].cells.length > 0) {
                        rows[i].deleteCell(rows[i].cells.length - 1);
                    }
                }
            }

            // Convert buttons to text in all cells
            var cells = table.getElementsByTagName('td');
            for (var i = 0; i < cells.length; i++) {
                var button = cells[i].querySelector('button');
                if (button) {
                    cells[i].textContent = button.textContent; // Preserve button text
                }
            }

            // Create print window
            var printWindow = window.open('', '', 'height=600,width=800');
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
    .badge {
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
    @media print {
      body { 
        width: 100%; 
        padding: 0; 
      }
      th, td { 
        font-size: 12px; 
      }
    }
  `);
            printWindow.document.write('</style></head><body>');
            printWindow.document.write('<h1>' + title + '</h1>');
            printWindow.document.write(table.outerHTML);
            printWindow.document.write('</body></html>');

            // Print and refresh
            printWindow.document.close();
            printWindow.print();
            location.reload();
        }
    </script>

    <script src="../js/sidebar_admin.js"></script>

</body>

</html>