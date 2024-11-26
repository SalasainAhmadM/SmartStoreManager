<?php
session_start();
require_once '../conn/auth.php';

validateSession('owner');

$owner_id = $_SESSION['user_id'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Dashboard</title>
    <link rel="icon" href="../assets/logo.png">
    <?php include '../components/head_cdn.php'; ?>
</head>

<body class="d-flex">

    <?php include '../components/owner_sidebar.php'; ?>

    <div class="container-fluid page-body">
        <div class="row">
            <div class="col-md-12 dashboard-body">
                <div class="dashboard-content">
                    <h1><b><i class="fas fa-users me-2"></i> Supervise Managers</b></h1>

                    <ul class="nav nav-pills nav-fill mt-5">
                        <li class="nav-item">
                            <a class="nav-link active" data-tab="managerlist">
                                <i class="fas fa-list me-2"></i>
                                <h5><b>Manager List</b></h5>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-tab="assignmanager">
                                <i class="fas fa-building me-2"></i>
                                <h5><b>Assign Manager</b></h5>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-tab="chat">
                                <i class="fas fa-comments me-2"></i>
                                <h5><b>Chat</b></h5>
                            </a>
                        </li>
                    </ul>

                    <div id="managerlist" class="tab-content active">

                        <h1 class="mt-5"></h1>
                        <div class="table-responsive mt-5">

                            <div class="position-relative">
                                <form class="d-flex" role="search" id="search-form">
                                    <input class="form-control me-2 w-50" id="search-business" type="search"
                                        placeholder="Search manager..." aria-label="Search">
                                    <ul id="suggestion-box" class="list-group position-absolute w-50"></ul>
                                </form>
                                <button id="add-business-btn"
                                    class="btn btn-success position-absolute top-0 end-0  me-2" type="button">
                                    <i class="fas fa-plus me-2"></i> Create Manager
                                </button>
                            </div>

                            <table class="table table-striped table-hover mt-5">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Address</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>John Doe</td>
                                        <td>johndoe@example.com</td>
                                        <td>+1234567890</td>
                                        <td>123 Main St, City, Country</td>
                                        <td>
                                            <a href="#" class="text-primary me-3" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="#" class="text-danger" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Jane Smith</td>
                                        <td>janesmith@example.com</td>
                                        <td>+0987654321</td>
                                        <td>456 Elm St, City, Country</td>
                                        <td>
                                            <a href="#" class="text-primary me-3" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="#" class="text-danger" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div id="assignmanager" class="tab-content">

                        <h1 class="mt-5"></h1>

                        <div class="table-responsive mt-5">
                            <table class="table table-striped table-hover mt-5">


                                <form class="d-flex" role="search" id="search-form">
                                    <input class="form-control me-2 w-50" id="search-business" type="search"
                                        placeholder="Search business..." aria-label="Search">
                                    <ul id="suggestion-box" class="list-group position-absolute w-50"></ul>
                                </form>



                                <thead class="table-dark">
                                    <tr>
                                        <th>Business Name</th>
                                        <th>Branch ID</th>
                                        <th>Branch Location</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td rowspan="2">Business A</td>
                                        <td>101</td>
                                        <td>Chicago</td>
                                        <td>
                                            <button class="btn btn-primary btn-sm">Assign a Manager</button>
                                            <button class="btn btn-info btn-sm">List of Manager(s)</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>102</td>
                                        <td>New World</td>
                                        <td>
                                            <button class="btn btn-primary btn-sm">Assign a Manager</button>
                                            <button class="btn btn-info btn-sm">List of Manager(s)</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Business B</td>
                                        <td>103</td>
                                        <td>Rio Hondo</td>
                                        <td>
                                            <button class="btn btn-primary btn-sm">Assign a Manager</button>
                                            <button class="btn btn-info btn-sm">List of Manager(s)</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>



                        <div class="table-responsive mt-5">
                            <table class="table table-striped table-hover mt-5">


                                <form class="d-flex" role="search" id="search-form">
                                    <input class="form-control me-2 w-50" id="search-business" type="search"
                                        placeholder="Search manager..." aria-label="Search">
                                    <ul id="suggestion-box" class="list-group position-absolute w-50"></ul>
                                </form>

                                <thead class="table-dark">
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Assigned Branches</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Nzro Mkrm</td>
                                        <td>NzroMkrm@example.com</td>
                                        <td>+0987654321</td>
                                        <td>Business A</td>
                                    </tr>
                                    <tr>
                                        <td>Sam D Roger</td>
                                        <td>SamDRoger@example.com</td>
                                        <td>+0987654321</td>
                                        <td>Business B</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div id="chat" class="tab-content">

                        <div class="container-fluid mt-4">
                            <div class="row">

                                <div class="col-md-4 col-lg-3 p-0">
                                    <div class="list-group bg-light border">
                                        <div class="p-3 bg-primary text-white">
                                            <h5 class="mb-0">Managers</h5>
                                        </div>

                                        <button
                                            class="list-group-item list-group-item-action d-flex align-items-center">
                                            <img src="../assets/profile.png" alt="Avatar"
                                                style="width: 40px; height: 40px; object-fit: cover;"
                                                class="rounded-circle me-3">
                                            <div>
                                                <strong>Manager Name</strong>
                                                <p class="text-muted small mb-0">Last message snippet...</p>
                                            </div>
                                        </button>
                                        <button
                                            class="list-group-item list-group-item-action d-flex align-items-center">
                                            <img src="../assets/profile.png"
                                                style="width: 40px; height: 40px; object-fit: cover;" alt="Avatar"
                                                class="rounded-circle me-3">
                                            <div>
                                                <strong>Another Manager</strong>
                                                <p class="text-muted small mb-0">Last message snippet...</p>
                                            </div>
                                        </button>

                                    </div>
                                </div>

                                <div class="col-md-8 col-lg-9 p-0">
                                    <div class="d-flex flex-column vh-100 border">

                                        <div class="p-3 bg-primary text-white d-flex align-items-center">
                                            <img src="../assets/profile.png"
                                                style="width: 40px; height: 40px; object-fit: cover;" alt="Avatar"
                                                class="rounded-circle me-3">
                                            <h5 class="mb-0">Manager Name</h5>
                                        </div>

                                        <div id="chat-messages" class="flex-grow-1 p-3 bg-light overflow-auto">

                                            <div class="d-flex justify-content-end mb-3">
                                                <div class="bg-primary text-white p-2 rounded" style="max-width: 60%;">
                                                    <p class="mb-0">Yow muks ikuzee?</p>
                                                    <small class="d-block text-end">2:15 PM</small>
                                                </div>
                                            </div>

                                            <div class="d-flex justify-content-start mb-3">
                                                <div class="bg-white border p-2 rounded" style="max-width: 60%;">
                                                    <p class="mb-0">nah nah ikuze ta.</p>
                                                    <small class="d-block text-start text-muted">2:16 PM</small>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="p-3 bg-light border-top">
                                            <div class="input-group">
                                                <input type="text" class="form-control" placeholder="Type a message...">
                                                <button class="btn btn-primary">
                                                    <i class="fas fa-paper-plane"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>



                    </div>

                </div>
            </div>
        </div>
    </div>

    <script src="../js/sidebar.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const navLinks = document.querySelectorAll('.nav-link');
            const tabContents = document.querySelectorAll('.tab-content');

            navLinks.forEach(link => {
                link.addEventListener('click', () => {
                    navLinks.forEach(nav => nav.classList.remove('active'));
                    tabContents.forEach(content => content.classList.remove('active'));
                    link.classList.add('active');
                    const targetTab = document.getElementById(link.getAttribute('data-tab'));
                    targetTab.classList.add('active');
                });
            });
        });
    </script>

    <script>
        document.querySelectorAll('.btn-primary').forEach(button => {
            button.addEventListener('click', function () {
                const isAssignManagerButton = this.textContent.includes('Assign a Manager');

                if (isAssignManagerButton) {
                    Swal.fire({
                        title: 'Select a Manager',
                        input: 'select',
                        inputOptions: {
                            'manager1': 'Nzro Mkrm',
                            'manager2': 'Sam D Roger',
                        },
                        inputPlaceholder: 'Select a manager',
                        showCancelButton: true,
                        confirmButtonText: 'Next',
                        cancelButtonText: 'Cancel',
                    }).then((result) => {
                        if (result.isConfirmed && result.value) {
                            // Manager selected, now ask for assigning to business or branch
                            Swal.fire({
                                title: 'Assign Manager',
                                text: 'Choose whether to assign the selected manager to a Business or Branch:',
                                icon: 'question',
                                showCancelButton: true,
                                confirmButtonText: 'Assign to Business',
                                cancelButtonText: 'Assign to Branch',
                                reverseButtons: true
                            }).then((assignResult) => {
                                if (assignResult.isConfirmed) {
                                    // Logic to assign to business
                                    Swal.fire('Assigned to Business', '', 'success');
                                } else if (assignResult.dismiss === Swal.DismissReason.cancel) {
                                    // Logic to assign to branch
                                    Swal.fire('Assigned to Branch', '', 'success');
                                } else {
                                    // Logic if the user cancels the assignment
                                    Swal.fire('Action Cancelled', '', 'info');
                                }
                            });
                        } else {
                            Swal.fire('Action Cancelled', '', 'info');
                        }
                    });
                }
            });
        });
    </script>

</body>

</html>