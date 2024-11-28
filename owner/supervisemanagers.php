<?php
session_start();
require_once '../conn/auth.php';
require_once '../conn/conn.php';

validateSession('owner');

$owner_id = $_SESSION['user_id'];

// Fetch all managers for this owner
$query = "SELECT * FROM manager WHERE owner_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$result = $stmt->get_result();
$managers = $result->fetch_all(MYSQLI_ASSOC);

// Query to fetch all businesses and their branches for the owner
$query = "
    SELECT 
        b.name AS business_name, 
        br.id AS branch_id, 
        br.location AS branch_location
    FROM business b
    LEFT JOIN branch br ON b.id = br.business_id
    WHERE b.owner_id = ?
    ORDER BY b.name, br.id
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$result = $stmt->get_result();

// Organize businesses and branches into an associative array
$businesses = [];
while ($row = $result->fetch_assoc()) {
    $business_name = $row['business_name'];
    $branch = [
        'branch_id' => $row['branch_id'],
        'branch_location' => $row['branch_location']
    ];

    if (!isset($businesses[$business_name])) {
        $businesses[$business_name] = [];
    }

    $businesses[$business_name][] = $branch;
}
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

    <div id="particles-js"></div>

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
                                <button id="add-business-btn" class="btn btn-success position-absolute top-0 end-0 me-2"
                                    type="button">
                                    <i class="fas fa-plus me-2"></i> Create Manager
                                </button>
                            </div>

                            <table class="table table-striped table-hover mt-5">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Name <button class="btn text-white"><i class="fas fa-sort"></i></button>
                                        </th>
                                        <th>Email <button class="btn text-white"><i class="fas fa-sort"></i></button>
                                        </th>
                                        <th>Phone <button class="btn text-white"><i class="fas fa-sort"></i></button>
                                        </th>
                                        <th>Address <button class="btn text-white"><i class="fas fa-sort"></i></button>
                                        </th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="manager-table-body">
                                    <?php if (empty($managers)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center">No managers found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($managers as $manager): ?>
                                            <tr>
                                                <td class="manager-name">
                                                    <?= htmlspecialchars($manager['first_name'] . ' ' . $manager['middle_name'] . ' ' . $manager['last_name']) ?>
                                                </td>
                                                <td><?= htmlspecialchars($manager['email']) ?></td>
                                                <td><?= htmlspecialchars($manager['contact_number']) ?></td>
                                                <td><?= htmlspecialchars($manager['address']) ?></td>
                                                <td>
                                                    <a href="#" class="text-primary me-3 edit-manager"
                                                        data-id="<?= $manager['id'] ?>"
                                                        data-details='<?= json_encode($manager) ?>' title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="#" class="text-danger delete-manager"
                                                        data-id="<?= $manager['id'] ?>" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
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
                                        <th>Business Name <button class="btn text-white"><i
                                                    class="fas fa-sort"></i></button></th>
                                        <th>Branch ID <button class="btn text-white"><i
                                                    class="fas fa-sort"></i></button></th>
                                        <th>Branches Locations <button class="btn text-white"><i
                                                    class="fas fa-sort"></i></button></th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($businesses as $business_name => $branches): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($business_name) ?></td>
                                            <td>
                                                <ul>
                                                    <?php foreach ($branches as $branch): ?>
                                                        <li><?= htmlspecialchars($branch['branch_id']) ?></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </td>
                                            <td>
                                                <ul>
                                                    <?php foreach ($branches as $branch): ?>
                                                        <li><?= htmlspecialchars($branch['branch_location']) ?></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </td>
                                            <td>
                                                <button class="btn btn-primary btn-sm">Assign Manager</button>
                                                <button class="btn btn-info btn-sm">List of Manager(s)</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
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
                                        <th>Name <button class="btn text-white"><i class="fas fa-sort"></i></button>
                                        </th>
                                        <th>Email <button class="btn text-white"><i class="fas fa-sort"></i></button>
                                        </th>
                                        <th>Phone <button class="btn text-white"><i class="fas fa-sort"></i></button>
                                        </th>
                                        <th>Assigned Branches <button class="btn text-white"><i
                                                    class="fas fa-sort"></i></button></th>
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
                                <!-- Sidebar with Managers -->
                                <div id="user-list" class="col-md-4 col-lg-3 p-0">
                                    <div class="list-group bg-light border">
                                    <div class="p-3 bg-primary text-white position-sticky top-0" style="z-index: 1">
                                        <h5 class="mb-0">Managers</h5>
                                    </div>
                                        <?php foreach ($managers as $manager):
                                            // Fetch unread message count
                                            $unreadQuery = "SELECT COUNT(*) as unread_count FROM messages WHERE sender_id = ? AND receiver_id = ? AND sender_type = 'manager' AND is_read = 0";
                                            $stmt = $conn->prepare($unreadQuery);
                                            $stmt->bind_param("ii", $manager['id'], $_SESSION['user_id']);
                                            $stmt->execute();
                                            $unreadResult = $stmt->get_result()->fetch_assoc();
                                            $unreadCount = $unreadResult['unread_count'] ?? 0;
                                            ?>
                                            <button class="list-group-item list-group-item-action d-flex align-items-center"
                                                data-manager-id="<?= $manager['id'] ?>"
                                                onclick="loadMessages(<?= $manager['id'] ?>)">
                                                <img src="<?= !empty($manager['image']) ? $manager['image'] : '../assets/profile.png' ?>"
                                                    alt="Avatar" style="width: 40px; height: 40px; object-fit: cover;"
                                                    class="rounded-circle me-3">
                                                <div class="flex-grow-1">
                                                    <strong><?= htmlspecialchars($manager['first_name'] . ' ' . $manager['last_name']) ?></strong>
                                                    <p class="text-muted small mb-0">
                                                        <?= htmlspecialchars($last_message['message'] ?? 'No messages yet...') ?>
                                                    </p>
                                                </div>
                                                <?php if ($unreadCount > 0): ?>
                                                    <span class="badge bg-danger ms-2"><?= $unreadCount ?></span>
                                                <?php endif; ?>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>

                                </div>

                                <!-- Chat Area -->
                                <div class="col-md-8 col-lg-9 p-0">
                                    <div class="d-flex flex-column border">
                                        <!-- Chat Header -->
                                        <div id="chat-header"
                                            class="p-3 bg-primary text-white d-flex align-items-center">
                                            <h5 class="mb-0">Select a Manager to Chat</h5>
                                        </div>

                                        <!-- Chat Messages -->
                                        <div id="chat-messages" class="flex-grow-1 p-3 bg-light overflow-auto">
                                            <p class="text-center text-muted">No messages selected...</p>
                                        </div>

                                        <!-- Chat Input -->
                                        <div class="p-3 bg-light border-top">
                                            <div class="input-group">
                                                <input type="text" id="message-input" class="form-control"
                                                    placeholder="Type a message...">
                                                <button id="send-btn" class="btn btn-primary">
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
    <script src="../js/sort_items.js"></script>


    <script>
        let selectedManagerId = null;

        function loadMessages(managerId) {
            selectedManagerId = managerId;
            const chatMessages = document.getElementById('chat-messages');
            chatMessages.innerHTML = '<p class="text-center text-muted">Loading...</p>';

            // Fetch messages and mark them as read
            fetch(`../endpoints/messages/fetch_messages.php?manager_id=${managerId}`)
                .then(response => response.json())
                .then(messages => {
                    chatMessages.innerHTML = '';
                    if (messages.length === 0) {
                        chatMessages.innerHTML = '<p class="text-center text-muted">No messages yet...</p>';
                    }
                    messages.forEach(msg => {
                        const isOwner = msg.sender_type === 'owner';
                        const messageElement = `
                <div class="d-flex ${isOwner ? 'justify-content-end' : 'justify-content-start'} mb-3">
                    <div class="${isOwner ? 'bg-primary text-white' : 'bg-white border'} p-2 rounded" style="max-width: 60%;">
                        <p class="mb-0">${msg.message}</p>
                        <small class="d-block text-${isOwner ? 'end' : 'start'} text-muted">
                            ${new Date(msg.timestamp).toLocaleString()}
                        </small>
                    </div>
                </div>`;
                        chatMessages.innerHTML += messageElement;
                    });

                    // Refresh unread counts
                    refreshUnreadCounts();
                });
        }

        function refreshUnreadCounts() {
            fetch('../endpoints/messages/fetch_unread_counts.php')
                .then(response => response.json())
                .then(unreadCounts => {
                    document.querySelectorAll('.list-group-item').forEach(item => {
                        const managerId = item.getAttribute('data-manager-id');
                        const badge = item.querySelector('.badge');
                        const unreadCount = unreadCounts[managerId] || 0;

                        if (unreadCount > 0) {
                            if (!badge) {
                                const newBadge = document.createElement('span');
                                newBadge.className = 'badge bg-danger ms-2';
                                newBadge.textContent = unreadCount;
                                item.appendChild(newBadge);
                            } else {
                                badge.textContent = unreadCount;
                            }
                        } else if (badge) {
                            badge.remove();
                        }
                    });
                });
        }

        // Function to send a new message
        document.querySelector('#send-btn').addEventListener('click', () => {
            const messageInput = document.getElementById('message-input');
            const message = messageInput.value.trim();

            if (message && selectedManagerId) {
                fetch('../endpoints/messages/send_message.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        receiver_id: selectedManagerId,
                        message
                    })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            messageInput.value = '';
                            loadMessages(selectedManagerId);
                        } else {
                            alert('Failed to send message');
                        }
                    });
            }
        });


        // Attach event listeners to manager buttons
        document.querySelectorAll('.list-group-item').forEach(button => {
            button.addEventListener('click', () => {
                const managerId = button.dataset.managerId; // Add `data-manager-id` to your buttons
                loadMessages(managerId);
            });
        });
    </script>

    <script>
        document.getElementById('add-business-btn').addEventListener('click', function () {
            const ownerId = <?= json_encode($owner_id); ?>;

            Swal.fire({
                title: 'Create Manager',
                html: `
        <input id="manager-email" class="form-control mb-2" placeholder="Email" type="email">
        <input id="manager-username" class="form-control mb-2" placeholder="Username">
        <input id="manager-firstname" class="form-control mb-2" placeholder="First Name">
        <input id="manager-middlename" class="form-control mb-2" placeholder="Middle Name">
        <input id="manager-lastname" class="form-control mb-2" placeholder="Last Name">
        <input id="manager-phone" class="form-control mb-2" placeholder="Contact Number">
        <input id="manager-address" class="form-control mb-2" placeholder="Address">
        <input id="manager-password" class="form-control mb-2" placeholder="Password" type="password">
    `,
                confirmButtonText: 'Create',
                showCancelButton: true,
                cancelButtonText: 'Cancel',
                preConfirm: () => {
                    const email = document.getElementById('manager-email').value;
                    const username = document.getElementById('manager-username').value;
                    const firstName = document.getElementById('manager-firstname').value;
                    const middleName = document.getElementById('manager-middlename').value;
                    const lastName = document.getElementById('manager-lastname').value;
                    const phone = document.getElementById('manager-phone').value;
                    const address = document.getElementById('manager-address').value;
                    const password = document.getElementById('manager-password').value;

                    if (!email || !username || !firstName || !lastName || !phone || !address || !password) {
                        Swal.showValidationMessage('All fields are required');
                    }

                    return {
                        email,
                        username,
                        firstName,
                        middleName,
                        lastName,
                        phone,
                        address,
                        password,
                        ownerId
                    };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const managerData = result.value;

                    // AJAX call to save the manager
                    fetch('../endpoints/manager/add_manager.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(managerData)
                    }).then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire('Success', 'Manager created successfully', 'success')
                                    .then(() => {
                                        // Reload the page after the user clicks OK
                                        location.reload();
                                    });
                            } else {
                                Swal.fire('Error', data.message || 'An error occurred', 'error');
                            }
                        });
                }
            });
        });

        // Edit and Delete Manager
        document.addEventListener('DOMContentLoaded', () => {
            // Edit Manager
            document.querySelectorAll('.edit-manager').forEach(button => {
                button.addEventListener('click', function () {
                    const managerId = this.dataset.id;
                    const managerDetails = JSON.parse(this.dataset.details);

                    Swal.fire({
                        title: 'Edit Manager',
                        html: `
                    <input id="manager-email" class="form-control mb-2" placeholder="Email" value="${managerDetails.email}" type="email">
                    <input id="manager-username" class="form-control mb-2" placeholder="Username" value="${managerDetails.user_name}">
                    <input id="manager-firstname" class="form-control mb-2" placeholder="First Name" value="${managerDetails.first_name}">
                    <input id="manager-middlename" class="form-control mb-2" placeholder="Middle Name" value="${managerDetails.middle_name}">
                    <input id="manager-lastname" class="form-control mb-2" placeholder="Last Name" value="${managerDetails.last_name}">
                    <input id="manager-phone" class="form-control mb-2" placeholder="Contact Number" value="${managerDetails.contact_number}">
                    <input id="manager-address" class="form-control mb-2" placeholder="Address" value="${managerDetails.address}">
                `,
                        confirmButtonText: 'Update',
                        showCancelButton: true,
                        cancelButtonText: 'Cancel',
                        preConfirm: () => {
                            const email = document.getElementById('manager-email').value;
                            const username = document.getElementById('manager-username').value;
                            const firstName = document.getElementById('manager-firstname').value;
                            const middleName = document.getElementById('manager-middlename').value;
                            const lastName = document.getElementById('manager-lastname').value;
                            const phone = document.getElementById('manager-phone').value;
                            const address = document.getElementById('manager-address').value;

                            if (!email || !username || !firstName || !lastName || !phone || !address) {
                                Swal.showValidationMessage('All fields are required');
                            }

                            return {
                                id: managerId,
                                email,
                                username,
                                firstName,
                                middleName,
                                lastName,
                                phone,
                                address
                            };
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // AJAX call to update manager
                            fetch(`../endpoints/manager/edit_manager.php`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify(result.value)
                            }).then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        Swal.fire('Success', 'Manager updated successfully', 'success')
                                            .then(() => location.reload());
                                    } else {
                                        Swal.fire('Error', data.message || 'Failed to update manager', 'error');
                                    }
                                });
                        }
                    });
                });
            });

            // Delete Manager
            document.querySelectorAll('.delete-manager').forEach(button => {
                button.addEventListener('click', function () {
                    const managerId = this.dataset.id;

                    Swal.fire({
                        title: 'Are you sure?',
                        text: 'This action cannot be undone!',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, delete it!',
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        cancelButtonText: 'Cancel'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // AJAX call to delete manager
                            fetch(`../endpoints/manager/delete_manager.php`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({
                                    id: managerId
                                })
                            }).then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        Swal.fire('Success', 'Manager deleted successfully', 'success')
                                            .then(() => location.reload());
                                    } else {
                                        Swal.fire('Error', data.message || 'Failed to delete manager', 'error');
                                    }
                                });
                        }
                    });
                });
            });
        });

        // manager search
        document.getElementById('search-business').addEventListener('input', function () {
            const filter = this.value.toLowerCase();
            const rows = document.querySelectorAll('#manager-table-body tr');

            rows.forEach(row => {
                const nameCell = row.querySelector('td:first-child');
                if (nameCell) {
                    const name = nameCell.textContent.toLowerCase();
                    row.style.display = name.includes(filter) ? '' : 'none';
                }
            });
        });

        document.addEventListener('DOMContentLoaded', () => {
            const navLinks = document.querySelectorAll('.nav-link');
            const tabContents = document.querySelectorAll('.tab-content');


            const defaultTabId = 'managerlist';

            const savedTab = localStorage.getItem('activeTab') || defaultTabId;

            const savedTabContent = document.getElementById(savedTab);
            const savedNavLink = document.querySelector(`.nav-link[data-tab="${savedTab}"]`);

            if (savedTabContent && savedNavLink) {
                navLinks.forEach(nav => nav.classList.remove('active'));
                tabContents.forEach(content => content.classList.remove('active'));

                savedNavLink.classList.add('active');
                savedTabContent.classList.add('active');
            } else {
                const defaultNavLink = document.querySelector(`.nav-link[data-tab="${defaultTabId}"]`);
                const defaultTabContent = document.getElementById(defaultTabId);

                if (defaultNavLink && defaultTabContent) {
                    defaultNavLink.classList.add('active');
                    defaultTabContent.classList.add('active');
                }
            }

            navLinks.forEach(link => {
                link.addEventListener('click', () => {
                    navLinks.forEach(nav => nav.classList.remove('active'));
                    tabContents.forEach(content => content.classList.remove('active'));

                    link.classList.add('active');
                    const targetTab = document.getElementById(link.getAttribute('data-tab'));
                    if (targetTab) targetTab.classList.add('active');

                    localStorage.setItem('activeTab', link.getAttribute('data-tab'));
                });
            });
        });
        // document.addEventListener('DOMContentLoaded', () => {
        //     const navLinks = document.querySelectorAll('.nav-link');
        //     const tabContents = document.querySelectorAll('.tab-content');

        //     navLinks.forEach(link => {
        //         link.addEventListener('click', () => {
        //             navLinks.forEach(nav => nav.classList.remove('active'));
        //             tabContents.forEach(content => content.classList.remove('active'));
        //             link.classList.add('active');
        //             const targetTab = document.getElementById(link.getAttribute('data-tab'));
        //             targetTab.classList.add('active');
        //         });
        //     });
        // });

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