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
        b.id AS business_id,  
        b.name AS business_name, 
        b.manager_id AS business_manager_id,
        br.id AS branch_id, 
        br.location AS branch_location,
        br.manager_id AS branch_manager_id
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
    $business_id = $row['business_id'];
    $business_name = $row['business_name'];
    $business_manager_id = $row['business_manager_id']; // Business-level manager

    $branch = [
        'branch_id' => $row['branch_id'],
        'branch_location' => $row['branch_location'],
        'branch_manager_id' => $row['branch_manager_id'] // Branch-level manager
    ];

    if (!isset($businesses[$business_id])) {
        $businesses[$business_id] = [
            'business_name' => $business_name,
            'business_manager_id' => $business_manager_id,
            'branches' => [],
            'all_branches_have_managers' => true // Assume all branches have managers
        ];
    }

    if ($branch['branch_id']) {
        $businesses[$business_id]['branches'][] = $branch;
        // If a branch has no manager, update flag
        if (!$branch['branch_manager_id']) {
            $businesses[$business_id]['all_branches_have_managers'] = false;
        }
    }
}
// Fetch managers with their assigned businesses or branches
$query = "
    SELECT 
        m.id AS manager_id,
        m.first_name,
        m.middle_name,
        m.last_name,
        m.email,
        m.user_name,
        m.contact_number,
        b.name AS business_name,
        br.location AS branch_location
    FROM manager m
    LEFT JOIN business b ON m.id = b.manager_id
    LEFT JOIN branch br ON m.id = br.manager_id
    WHERE m.owner_id = ?
    ORDER BY m.first_name, m.last_name, b.name, br.location
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$result = $stmt->get_result();
$assignmanagers = [];

// Organize results
while ($row = $result->fetch_assoc()) {
    $assignmanagers[] = $row;
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
<style>
    .permit-modal-container .swal2-popup {
        max-height: 80vh;
        overflow: hidden;
    }

    @media (max-width: 767.98px) {
        .container-fluid {
            padding: 0 15px;
        }

        .container-fluid {
            padding: 0 15px;
        }

        .dashboard-body {
            padding: 15px;
        }

        .dashboard-content h1 {
            font-size: 24px;
            margin-bottom: 20px;
        }

        .nav-pills {
            flex-wrap: wrap;
        }

        .nav-pills .nav-item {
            flex: 1 1 auto;
            margin-bottom: 10px;
        }

        .nav-pills .nav-link {
            text-align: center;
            padding: 10px;
            font-size: 14px;
        }

        .nav-pills .nav-link h5 {
            margin: 0;
            font-size: 16px;
        }

        .tab-content {
            margin-top: 20px;
        }

        .scrollable-table {
            width: 100%;
            overflow-x: auto;
        }

        .table {
            width: 100%;
            margin-bottom: 1rem;
            color: #212529;
        }

        .table th,
        .table td {
            padding: 0.75rem;
            vertical-align: top;
            border-top: 1px solid #dee2e6;
        }

        .table thead th {
            vertical-align: bottom;
            border-bottom: 2px solid #dee2e6;
        }

        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(0, 0, 0, 0.05);
        }

        .table-hover tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.075);
        }

        .btn {
            display: inline-block;
            font-weight: 400;
            color: #212529;
            text-align: center;
            vertical-align: middle;
            cursor: pointer;
            background-color: transparent;
            /* border: 1px solid transparent; */
            padding: 0.375rem 0.75rem;
            font-size: 1rem;
            line-height: 1.5;
            border-radius: 0.25rem;
            transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }

        .btn-success {
            color: #fff;
            background-color: #198754;
            border-color: #198754;
        }

        .btn-danger {
            color: #fff;
            background-color: #dc3545;
            border-color: #dc3545;
        }

        .btn-primary {
            color: #fff;
            background-color: #007bff;
            border-color: #007bff;
        }

        .btn-secondary {
            color: #fff;
            background-color: #6c757d;
            border-color: #6c757d;
        }

        .form-control {
            width: 100%;
            padding: 0.375rem 0.75rem;
            font-size: 1rem;
            line-height: 1.5;
            border: 1px solid #ced4da;
            border-radius: 0.25rem;
        }

        .position-relative {
            position: relative;
        }

        .position-absolute {
            position: absolute;
        }

        .top-0 {
            top: 0;
        }

        .end-0 {
            right: 0;
        }

        .mt-2 {
            margin-top: 0.5rem;
        }

        .me-2 {
            margin-right: 0.5rem;
        }

        .mb-5 {
            margin-bottom: 3rem;
        }

        .text-center {
            text-align: center;
        }

        .text-primary {
            color: #007bff !important;
        }

        .text-danger {
            color: #dc3545 !important;
        }

        .text-success {
            color: #198754 !important;
        }

        .dashboard-content h1 {
            font-size: 20px;
        }

        .nav-pills .nav-link h5 {
            font-size: 14px;
        }

        .nav-pills .nav-link {
            font-size: 12px;
            padding: 8px;
        }

        .table th,
        .table td {
            padding: 0.5rem;
        }

        .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }

        .form-control {
            font-size: 14px;
        }

        .position-absolute {
            position: static;
            margin-top: 10px;
        }

        .w-50 {
            width: 100% !important;
        }

        .d-flex {
            /* flex-direction: column; */
            align-items: flex-start;
        }

        .ms-auto {
            margin-left: 0 !important;
            margin-top: 10px;
        }

        .scrollable-table {
            overflow-x: auto;
        }

        .table thead th {
            font-size: 14px;
        }

        .table tbody td {
            font-size: 14px;
        }

        .btn-success,
        .btn-danger,
        .btn-primary,
        .btn-secondary {
            font-size: 14px;
        }

        .page-body {
            display: flex;
            flex-direction: column;
        }

        .dashboard-body {
            order: 2;
        }

        .sidebar {
            order: 1;
            width: 100%;
            position: static;
        }
    }

    @media (max-width: 575.98px) {
        .dashboard-content h1 {
            font-size: 18px;
        }

        .nav-pills .nav-link h5 {
            font-size: 12px;
        }

        .nav-pills .nav-link {
            font-size: 10px;
            padding: 6px;
        }

        .table th,
        .table td {
            padding: 0.375rem;
        }

        .btn {
            padding: 0.2rem 0.4rem;
            font-size: 0.75rem;
        }

        .form-control {
            font-size: 12px;
        }

        .table thead th {
            font-size: 12px;
        }

        .table tbody td {
            font-size: 12px;
        }

        .btn-success,
        .btn-danger,
        .btn-primary,
        .btn-secondary {
            font-size: 12px;
        }
    }
</style>

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
                                    <input class="form-control me-2 w-50" id="search-manager2" type="search"
                                        placeholder="Search manager..." aria-label="Search">
                                    <ul id="suggestion-box" class="list-group position-absolute w-50"></ul>
                                </form>
                                <button id="add-business-btn" class="btn btn-success position-absolute top-0 end-0 me-2"
                                    type="button">
                                    <i class="fas fa-plus me-2"></i> Create Manager
                                </button>
                            </div>

                            <div class="scrollable-table" id="managerListTableSection">
                                <h4 class="mt-3">Manager List <i class="fas fa-info-circle"
                                        onclick="showInfo('Manager List', 
                                    'The Manager List is a record of all managers in a business, showing their names, roles, and responsibilities. It helps keep track of who’s in charge and makes communication easier.');">
                                    </i></h4>
                                <table class="table table-striped table-hover mt-3" id="managerListTable">
                                    <thead class="table-dark position-sticky top-0">
                                        <tr>
                                            <th>Name <button class="btn text-white"><i class="fas fa-sort"></i></button>
                                            </th>
                                            <th>Email <button class="btn text-white"><i
                                                        class="fas fa-sort"></i></button>
                                            </th>
                                            <th>Username <button class="btn text-white"><i
                                                        class="fas fa-sort"></i></button>
                                            </th>
                                            <th>Phone <button class="btn text-white"><i
                                                        class="fas fa-sort"></i></button>
                                            </th>
                                            <th>Address <button class="btn text-white"><i
                                                        class="fas fa-sort"></i></button>
                                            </th>
                                            <th class="text-center">Actions</th>
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
                                                    <td><?= htmlspecialchars($manager['user_name']) ?></td>
                                                    <td><?= htmlspecialchars($manager['contact_number']) ?></td>
                                                    <td><?= htmlspecialchars($manager['barangay'] . "," . $manager['city'] . "," . $manager['province'] . "," . $manager['region']) ?>
                                                    </td>
                                                    <td class="text-center">
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

                                <button class="btn btn-primary mt-2 mb-5" id="managerListTable"
                                    onclick="printContent('managerListTableSection', 'Manager List Report')">
                                    <i class="fas fa-print me-2"></i> Generate Report (Manager List)
                                </button>

                            </div>



                        </div>
                    </div>

                    <div id="assignmanager" class="tab-content">
                        <h1 class="mt-5"></h1>

                        <div class="table-responsive mt-5 scrollable-table" id="assignManagerTableSection">

                            <table class="table table-striped table-hover mt-3" id="assignManagerTable">
                                <form class="d-flex" role="search" id="search-form">
                                    <input class="form-control me-2 w-50" id="search-business" type="search"
                                        placeholder="Search business or branch..." aria-label="Search">
                                    <ul id="suggestion-box" class="list-group position-absolute w-50"></ul>
                                </form>
                                <h4 class="mt-3">Assign Manager <i class="fas fa-info-circle"
                                        onclick="showInfo('Assign Manager', 
                                    'Assign Manager means selecting a person to take on a managerial role, giving them responsibilities to oversee operations, teams, or specific tasks within the business.');">
                                    </i></h4>
                                <thead class="table-dark position-sticky top-0">
                                    <tr>
                                        <th>Business Name <button class="btn text-white"><i
                                                    class="fas fa-sort"></i></button></th>
                                        <th>Branch ID <button class="btn text-white"><i
                                                    class="fas fa-sort"></i></button></th>
                                        <th>Branches Locations <button class="btn text-white"><i
                                                    class="fas fa-sort"></i></button></th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="business-table-body">
                                    <?php foreach ($businesses as $business_id => $business):
                                        // Condition to show/hide Assign Manager button
                                        $show_assign_button = !$business['business_manager_id'] || !$business['all_branches_have_managers'];
                                        ?>
                                        <tr>
                                            <td><?= htmlspecialchars($business['business_name']) ?></td>
                                            <td>
                                                <ul>
                                                    <?php foreach ($business['branches'] as $branch): ?>
                                                        <li><?= htmlspecialchars($branch['branch_id']) ?></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </td>
                                            <td>
                                                <ul>
                                                    <?php foreach ($business['branches'] as $branch): ?>
                                                        <li><?= htmlspecialchars($branch['branch_location']) ?></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($show_assign_button): ?>
                                                    <button class="btn btn-primary btn-sm assign-manager"
                                                        data-business-id="<?= htmlspecialchars($business_id) ?>"
                                                        data-branches='<?= htmlspecialchars(json_encode($business['branches'])) ?>'>
                                                        Assign Manager
                                                    </button>
                                                <?php endif; ?>
                                                <button class="btn btn-info btn-sm list-managers"
                                                    data-business-id="<?= htmlspecialchars($business_id) ?>"
                                                    data-branches='<?= htmlspecialchars(json_encode($business['branches'])) ?>'>
                                                    Assigned Manager(s)
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>

                            </table>

                            <button class="btn btn-primary mt-2 mb-5" id="assignManagerTable"
                                onclick="printContent('assignManagerTableSection', 'Business Branches Report')">
                                <i class="fas fa-print me-2"></i> Generate Report (Business Branches)
                            </button>

                        </div>

                        <div class="table-responsive mt-5 scrollable-table" id="managerListTableSection">
                            <table class="table table-striped table-hover mt-5" id="managerListTable">
                                <form class="d-flex" role="search" id="search-form" onsubmit="return false;">
                                    <input class="form-control me-2 w-50" id="search-manager" type="search"
                                        placeholder="Search manager..." aria-label="Search">
                                </form>
                                <thead class="table-dark position-sticky top-0">
                                    <tr>
                                        <th>Name <button class="btn text-white"><i class="fas fa-sort"></i></button>
                                        </th>
                                        <th>Email <button class="btn text-white"><i class="fas fa-sort"></i></button>
                                        </th>
                                        <th>Username <button class="btn text-white"><i class="fas fa-sort"></i></button>
                                        </th>
                                        <th>Phone <button class="btn text-white"><i class="fas fa-sort"></i></button>
                                        </th>
                                        <th>Assigned Business/Branches <button class="btn text-white"><i
                                                    class="fas fa-sort"></i></button></th>
                                    </tr>
                                </thead>
                                <tbody id="manager-table-body">
                                    <?php if (!empty($assignmanagers)): ?>
                                        <?php foreach ($assignmanagers as $manager): ?>
                                            <tr>
                                                <td>
                                                    <?= htmlspecialchars($manager['first_name'] . ' ' . $manager['middle_name'] . ' ' . $manager['last_name']) ?>
                                                </td>
                                                <td><?= htmlspecialchars($manager['email']) ?></td>
                                                <td><?= htmlspecialchars($manager['user_name']) ?></td>
                                                <td><?= htmlspecialchars($manager['contact_number']) ?></td>
                                                <td>
                                                    <?php if ($manager['business_name']): ?>
                                                        <span>Business: <?= htmlspecialchars($manager['business_name']) ?></span>
                                                    <?php elseif ($manager['branch_location']): ?>
                                                        <span>Branch: <?= htmlspecialchars($manager['branch_location']) ?></span>
                                                    <?php else: ?>
                                                        <span>Unassigned</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center">No managers found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>


                            <button class="btn btn-primary mt-2 mb-5" id="managerListTable"
                                onclick="printContent('managerListTableSection', 'Manager Assigned Branches/Business Report')">
                                <i class="fas fa-print me-2"></i> Print Report (Manager Assigned Branches/Business)
                            </button>

                        </div>

                    </div>

                    <div id="chat" class="tab-content">

                        <div class="container-fluid mt-4">
                            <div class="row">
                                <!-- Sidebar with Managers -->
                                <div id="user-list" class="col-md-4 col-lg-3 p-0">
                                    <div class="list-group bg-light border">
                                        <!-- Search Header -->
                                        <div class="p-3 bg-primary text-white position-sticky top-0 shadow"
                                            style="z-index: 1">
                                            <h5 class="mb-0">Managers</h5>
                                            <form class="d-flex" role="search" id="search-form">
                                                <input class="form-control me-2 mt-3" id="search-manager" type="search"
                                                    placeholder="Search business..." aria-label="Search">
                                                <ul id="suggestion-box" class="list-group position-absolute w-50"></ul>
                                            </form>
                                        </div>
                                        <!-- Manager List -->
                                        <div id="manager-list">
                                            <?php foreach ($managers as $manager):
                                                // Fetch unread message count
                                                $unreadQuery = "SELECT COUNT(*) as unread_count FROM messages WHERE sender_id = ? AND receiver_id = ? AND sender_type = 'manager' AND is_read = 0";
                                                $stmt = $conn->prepare($unreadQuery);
                                                $stmt->bind_param("ii", $manager['id'], $_SESSION['user_id']);
                                                $stmt->execute();
                                                $unreadResult = $stmt->get_result()->fetch_assoc();
                                                $unreadCount = $unreadResult['unread_count'] ?? 0;

                                                // Fetch last message only from the manager
                                                $lastMessageQuery = "SELECT message, timestamp FROM messages 
                                     WHERE sender_id = ? AND receiver_id = ? AND sender_type = 'manager' 
                                     ORDER BY timestamp DESC LIMIT 1";
                                                $stmt = $conn->prepare($lastMessageQuery);
                                                $stmt->bind_param("ii", $manager['id'], $_SESSION['user_id']);
                                                $stmt->execute();
                                                $lastMessageResult = $stmt->get_result()->fetch_assoc();
                                                $lastMessage = $lastMessageResult['message'] ?? 'No messages yet...';
                                                ?>
                                                <button
                                                    class="list-group-item list-group-item-action d-flex-alt align-items-center manager-item"
                                                    data-manager-id="<?= $manager['id'] ?>" onclick="loadMessages(
                                            <?= $manager['id'] ?>)" style="z-index: 0">
                                                    <img src="../assets/profiles/<?= !empty($manager['image']) ? $manager['image'] : 'profile.png' ?>"
                                                        alt="Avatar" style="width: 40px; height: 40px; object-fit: cover;"
                                                        class="rounded-circle me-3">
                                                    <div class=" flex-grow-1">
                                                        <strong class="manager-name">
                                                            <?= htmlspecialchars($manager['first_name'] . ' ' . $manager['middle_name'] . ' ' . $manager['last_name']) ?>
                                                        </strong>
                                                        <p class="text-muted small mb-0">
                                                            <?= htmlspecialchars($lastMessage) ?>
                                                        </p>
                                                    </div>
                                                    <?php if ($unreadCount > 0): ?>
                                                        <span class="badge bg-danger ms-2"><?= $unreadCount ?></span>
                                                    <?php endif; ?>
                                                </button>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>


                                <!-- Chat Area -->
                                <div class="col-md-8 col-lg-9 p-0">
                                    <div class="d-flex flex-column border">
                                        <!-- Chat Header -->
                                        <div id="chat-header"
                                            class="p-3 bg-primary text-white d-flex align-items-center shadow">
                                            <h5 class="mb-0">Select a Manager to Chat</h5>
                                        </div>

                                        <!-- Chat Messages -->
                                        <div id="chat-messages" class="flex-grow-1 p-3 overflow-auto">
                                            <p class="text-center text-muted">No messages selected...</p>
                                        </div>


                                        <!-- Chat Input -->
                                        <div class="p-3 bg-light border-top border-dark">
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

    <script src="../js/print_report.js"></script>
    <script src="../js/sidebar.js"></script>
    <script src="../js/sort_items.js"></script>
    <script src="../js/show_info.js"></script>

    <script>
        let selectedManagerId = null;

        function loadMessages(managerId) {
            selectedManagerId = managerId;
            const chatMessages = document.getElementById('chat-messages');
            chatMessages.innerHTML = '<p class="text-center text-muted">Loading...</p>';

            // Highlight the selected manager's button
            document.querySelectorAll('.list-group-item').forEach(item => {
                if (item.getAttribute('data-manager-id') == managerId) {
                    item.classList.add('bg-primary', 'text-white');
                } else {
                    item.classList.remove('bg-primary', 'text-white');
                }
            });

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
                                                            <div class="${isOwner ? 'bg-primary text-white' : 'bg-white border'} px-4 py-2 rounded" style="max-width: 60%; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);">
                                                                <p class="mb-0">${msg.message}</p>
                                                                <small class="d-block text-${isOwner ? 'end' : 'start'} text-muted">
                                                                    ${new Date(msg.timestamp).toLocaleString()}
                                                                </small>
                                                            </div>
                                                        </div>`;
                        chatMessages.innerHTML += messageElement;
                    });

                    // Scroll to the bottom after the messages are loaded
                    chatMessages.scrollTop = chatMessages.scrollHeight;
                    // Refresh unread counts
                    refreshUnreadCounts();
                });
        }

        // Polling for new messages every 5 seconds
        setInterval(() => {
            if (selectedManagerId) {
                loadMessages(selectedManagerId);  // Refresh messages for the selected manager
            }
        }, 5000); // Refresh every 5 seconds

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

        // Search manager
        document.addEventListener('DOMContentLoaded', () => {
            const searchInput = document.getElementById('search-manager');
            const managerButtons = document.querySelectorAll('#user-list .list-group-item');

            searchInput.addEventListener('input', (event) => {
                const query = event.target.value.toLowerCase();

                managerButtons.forEach(button => {
                    const managerName = button.querySelector('strong').textContent.toLowerCase();
                    if (managerName.includes(query)) {
                        button.style.display = ''; // Show matching button
                    } else {
                        button.style.display = 'none'; // Hide non-matching button
                    }
                });
            });
        });

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

        document.getElementById('add-business-btn').addEventListener('click', function () {
            const ownerId = <?= json_encode($owner_id); ?>;

            Swal.fire({
                title: 'Create Manager',
                html: `
        <div class="mb-2">
            <label for="manager-email" class="form-label text-center w-100">Email <span style="color:red">*</span></label>
            <input id="manager-email" class="form-control" placeholder="Email" type="email">
        </div>

        <div class="mb-2">
            <label for="manager-username" class="form-label text-center w-100">Username <span style="color:red">*</span></label>
            <input id="manager-username" class="form-control" placeholder="Username">
        </div>

        <div class="mb-2">
            <label for="manager-firstname" class="form-label text-center w-100">First Name <span style="color:red">*</span></label>
            <input id="manager-firstname" class="form-control" placeholder="First Name">
        </div>

        <div class="mb-2">
            <label for="manager-middlename" class="form-label text-center w-100">Middle Name</label>
            <input id="manager-middlename" class="form-control" placeholder="Middle Name">
        </div>

        <div class="mb-2">
            <label for="manager-lastname" class="form-label text-center w-100">Last Name <span style="color:red">*</span></label>
            <input id="manager-lastname" class="form-control" placeholder="Last Name">
        </div>

        <div class="mb-2">
            <label for="manager-phone" class="form-label text-center w-100">Contact Number <span style="color:red">*</span></label>
            <input id="manager-phone" class="form-control" placeholder="Contact Number">
        </div>

        <div class="mb-2">
    <label>Region <span style="color:red">*</span></label>
    <select id="manager-region" class="form-control">
        <option value="">Select Region</option>
    </select>
</div>

<div class="mb-2">
    <label>Province <span style="color:red">*</span></label>
    <select id="manager-province" class="form-control">
        <option value="">Select Province</option>
    </select>
</div>

<div class="mb-2">
    <label>City / Municipality <span style="color:red">*</span></label>
    <select id="manager-city" class="form-control">
        <option value="">Select City/Municipality</option>
    </select>
</div>

<div class="mb-2">
    <label>Barangay <span style="color:red">*</span></label>
    <select id="manager-barangay" class="form-control">
        <option value="">Select Barangay</option>
    </select>
</div>

        <div class="mb-2">
            <label for="manager-password" class="form-label text-center w-100">Password <span style="color:red">*</span></label>
            <input id="manager-password" class="form-control" placeholder="Password" type="password">
        </div>
    `,
                confirmButtonText: 'Create',
                showCancelButton: true,
                cancelButtonText: 'Cancel'
                ,
                didOpen: () => {
                    // Fetch and populate region dropdown
                    fetch('../json/refregion.json')
                        .then(res => res.json())
                        .then(data => {
                            const regionSelect = document.getElementById('manager-region');
                            data.RECORDS.forEach(region => {
                                const opt = document.createElement('option');
                                opt.value = region.regCode;
                                opt.textContent = region.regDesc;
                                regionSelect.appendChild(opt);
                            });
                        });

                    // Handle region → province
                    document.getElementById('manager-region').addEventListener('change', function () {
                        const regCode = this.value;
                        const provinceSelect = document.getElementById('manager-province');
                        provinceSelect.innerHTML = '<option value="">Select Province</option>';
                        document.getElementById('manager-city').innerHTML = '<option value="">Select City/Municipality</option>';
                        document.getElementById('manager-barangay').innerHTML = '<option value="">Select Barangay</option>';

                        fetch('../json/refprovince.json')
                            .then(res => res.json())
                            .then(data => {
                                data.RECORDS.filter(p => p.regCode === regCode).forEach(province => {
                                    const opt = document.createElement('option');
                                    opt.value = province.provCode;
                                    opt.textContent = province.provDesc;
                                    provinceSelect.appendChild(opt);
                                });
                            });
                    });

                    // Handle province → city
                    document.getElementById('manager-province').addEventListener('change', function () {
                        const provCode = this.value;
                        const citySelect = document.getElementById('manager-city');
                        citySelect.innerHTML = '<option value="">Select City/Municipality</option>';
                        document.getElementById('manager-barangay').innerHTML = '<option value="">Select Barangay</option>';

                        fetch('../json/refcitymun.json')
                            .then(res => res.json())
                            .then(data => {
                                data.RECORDS.filter(c => c.provCode === provCode).forEach(city => {
                                    const opt = document.createElement('option');
                                    opt.value = city.citymunCode;
                                    opt.textContent = city.citymunDesc;
                                    citySelect.appendChild(opt);
                                });
                            });
                    });

                    // Handle city → barangay
                    document.getElementById('manager-city').addEventListener('change', function () {
                        const cityCode = this.value;
                        const brgySelect = document.getElementById('manager-barangay');
                        brgySelect.innerHTML = '<option value="">Select Barangay</option>';

                        fetch('../json/refbrgy.json')
                            .then(res => res.json())
                            .then(data => {
                                data.RECORDS.filter(b => b.citymunCode === cityCode).forEach(brgy => {
                                    const opt = document.createElement('option');
                                    opt.value = brgy.brgyDesc;
                                    opt.textContent = brgy.brgyDesc;
                                    brgySelect.appendChild(opt);
                                });
                            });
                    });
                },
                preConfirm: () => {
                    const email = document.getElementById('manager-email').value;
                    const username = document.getElementById('manager-username').value;
                    const firstName = document.getElementById('manager-firstname').value;
                    const middleName = document.getElementById('manager-middlename').value;
                    const lastName = document.getElementById('manager-lastname').value;
                    const phone = document.getElementById('manager-phone').value;
                    const region = document.getElementById('manager-region').selectedOptions[0].textContent;
                    const province = document.getElementById('manager-province').selectedOptions[0].textContent;
                    const city = document.getElementById('manager-city').selectedOptions[0].textContent;
                    const barangay = document.getElementById('manager-barangay').selectedOptions[0].textContent;
                    const password = document.getElementById('manager-password').value;

                    if (!email || !username || !firstName || !lastName || !phone || !password) {
                        Swal.showValidationMessage('All fields are required');
                    }

                    return {
                        email,
                        username,
                        firstName,
                        middleName,
                        lastName,
                        phone,
                        barangay,
                        city,
                        province,
                        region,
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
                    <select id="manager-region" class="form-control mb-2">
    <option value="">Select Region</option>
</select>
<select id="manager-province" class="form-control mb-2">
    <option value="">Select Province</option>
</select>
<select id="manager-city" class="form-control mb-2">
    <option value="">Select City/Municipality</option>
</select>
<select id="manager-barangay" class="form-control mb-2">
    <option value="">Select Barangay</option>
</select>

                `,
                        confirmButtonText: 'Update',
                        showCancelButton: true,
                        cancelButtonText: 'Cancel',
                        didOpen: () => {
                            const regionSelect = document.getElementById('manager-region');
                            const provinceSelect = document.getElementById('manager-province');
                            const citySelect = document.getElementById('manager-city');
                            const barangaySelect = document.getElementById('manager-barangay');

                            const selectedRegion = managerDetails.region;
                            const selectedProvince = managerDetails.province;
                            const selectedCity = managerDetails.city;
                            const selectedBarangay = managerDetails.barangay;

                            fetch('../json/refregion.json')
                                .then(res => res.json())
                                .then(data => {
                                    data.RECORDS.forEach(region => {
                                        const opt = document.createElement('option');
                                        opt.value = region.regCode;
                                        opt.textContent = region.regDesc;
                                        if (region.regDesc === selectedRegion) opt.selected = true;
                                        regionSelect.appendChild(opt);
                                    });

                                    // Trigger change to populate province
                                    regionSelect.dispatchEvent(new Event('change'));
                                });

                            regionSelect.addEventListener('change', () => {
                                const regCode = regionSelect.value;
                                provinceSelect.innerHTML = '<option value="">Select Province</option>';
                                citySelect.innerHTML = '<option value="">Select City/Municipality</option>';
                                barangaySelect.innerHTML = '<option value="">Select Barangay</option>';

                                fetch('../json/refprovince.json')
                                    .then(res => res.json())
                                    .then(data => {
                                        data.RECORDS.filter(p => p.regCode === regCode).forEach(province => {
                                            const opt = document.createElement('option');
                                            opt.value = province.provCode;
                                            opt.textContent = province.provDesc;
                                            if (province.provDesc === selectedProvince) opt.selected = true;
                                            provinceSelect.appendChild(opt);
                                        });

                                        provinceSelect.dispatchEvent(new Event('change'));
                                    });
                            });

                            provinceSelect.addEventListener('change', () => {
                                const provCode = provinceSelect.value;
                                citySelect.innerHTML = '<option value="">Select City/Municipality</option>';
                                barangaySelect.innerHTML = '<option value="">Select Barangay</option>';

                                fetch('../json/refcitymun.json')
                                    .then(res => res.json())
                                    .then(data => {
                                        data.RECORDS.filter(c => c.provCode === provCode).forEach(city => {
                                            const opt = document.createElement('option');
                                            opt.value = city.citymunCode;
                                            opt.textContent = city.citymunDesc;
                                            if (city.citymunDesc === selectedCity) opt.selected = true;
                                            citySelect.appendChild(opt);
                                        });

                                        citySelect.dispatchEvent(new Event('change'));
                                    });
                            });

                            citySelect.addEventListener('change', () => {
                                const cityCode = citySelect.value;
                                barangaySelect.innerHTML = '<option value="">Select Barangay</option>';

                                fetch('../json/refbrgy.json')
                                    .then(res => res.json())
                                    .then(data => {
                                        data.RECORDS.filter(b => b.citymunCode === cityCode).forEach(brgy => {
                                            const opt = document.createElement('option');
                                            opt.value = brgy.brgyDesc;
                                            opt.textContent = brgy.brgyDesc;
                                            if (brgy.brgyDesc === selectedBarangay) opt.selected = true;
                                            barangaySelect.appendChild(opt);
                                        });
                                    });
                            });
                        }
                        ,
                        preConfirm: () => {
                            const email = document.getElementById('manager-email').value;
                            const username = document.getElementById('manager-username').value;
                            const firstName = document.getElementById('manager-firstname').value;
                            const middleName = document.getElementById('manager-middlename').value;
                            const lastName = document.getElementById('manager-lastname').value;
                            const phone = document.getElementById('manager-phone').value;
                            const region = document.getElementById('manager-region').selectedOptions[0].textContent;
                            const province = document.getElementById('manager-province').selectedOptions[0].textContent;
                            const city = document.getElementById('manager-city').selectedOptions[0].textContent;
                            const barangay = document.getElementById('manager-barangay').selectedOptions[0].textContent;


                            if (!email || !username || !firstName || !lastName || !phone) {
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
                                city,
                                province,
                                barangay,
                                region
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
        document.getElementById('search-manager2').addEventListener('input', function () {
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
        // Manager search functionality
        document.getElementById('search-manager').addEventListener('input', function () {
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
        document.getElementById('search-business').addEventListener('input', function () {
            const filter = this.value.toLowerCase();
            const rows = document.querySelectorAll('#business-table-body tr');

            rows.forEach(row => {
                const nameCell = row.querySelector('td:first-child');
                const locationCells = row.querySelectorAll('td:nth-child(3) li');

                let matchFound = false;

                if (nameCell && nameCell.textContent.toLowerCase().includes(filter)) {
                    matchFound = true;
                }

                locationCells.forEach(locationCell => {
                    if (locationCell.textContent.toLowerCase().includes(filter)) {
                        matchFound = true;
                    }
                });

                row.style.display = matchFound ? '' : 'none';
            });
        });
        // Add an event listener to the search input field
        document.getElementById('search-business').addEventListener('input', function () {
            const filter = this.value.toLowerCase();
            const rows = document.querySelectorAll('#manager-table-body tr');

            rows.forEach(row => {
                const nameCell = row.querySelector('td:first-child');
                const locationCells = row.querySelectorAll('td:nth-child(3) ul li'); // Adjust to your branch location structure

                let matchesName = false;
                let matchesLocation = false;

                // Check if the business name matches the filter
                if (nameCell) {
                    const name = nameCell.textContent.toLowerCase();
                    matchesName = name.includes(filter);
                }

                // Check if any branch location matches the filter
                locationCells.forEach(locationCell => {
                    if (locationCell) {
                        const location = locationCell.textContent.toLowerCase();
                        if (location.includes(filter)) {
                            matchesLocation = true;
                        }
                    }
                });

                // Display the row if it matches either the name or any location
                row.style.display = matchesName || matchesLocation ? '' : 'none';
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

        // Assign funtions 
        document.addEventListener('DOMContentLoaded', async () => {
            let managers = [];

            // Fetch managers and populate the list
            try {
                const response = await fetch('../endpoints/assign/fetch_managers.php');
                const data = await response.json();
                if (data.success) {
                    managers = data.managers;
                } else {
                    console.error('Failed to fetch managers:', data.message);
                }
            } catch (error) {
                console.error('Error fetching managers:', error);
            }

            const buttons = document.querySelectorAll('.assign-manager');

            buttons.forEach(button => {
                button.addEventListener('click', async () => {
                    const businessId = button.getAttribute('data-business-id');
                    const branchesData = button.getAttribute('data-branches');
                    const branches = branchesData ? JSON.parse(branchesData) : [];

                    const managerOptions = managers.map(manager =>
                        `<option value="${manager.id}">${manager.username}</option>`
                    ).join('');

                    if (branches.length > 0) {
                        Swal.fire({
                            title: 'Assign Manager',
                            text: 'Do you want to assign the manager to the Main Branch (Business) or a specific Branch?',
                            showDenyButton: true,
                            showCancelButton: true,
                            confirmButtonText: 'Main Branch (Business)',
                            denyButtonText: 'Branch'
                        }).then(result => {
                            if (result.isConfirmed) {
                                assignToBusiness(businessId, managerOptions);
                            } else if (result.isDenied) {
                                assignToBranch(branches, managerOptions);
                            }
                        });
                    } else {
                        assignToBusiness(businessId, managerOptions);
                    }
                });
            });

            function assignToBusiness(businessId, managerOptions) {
                Swal.fire({
                    title: 'Assign to Business?',
                    html: `
                <p>Business ID: ${businessId}</p>
                <label for="manager-select">Choose a manager:</label>
                <select id="manager-select" class="swal2-input">${managerOptions}</select>
            `,
                    showCancelButton: true,
                    confirmButtonText: 'Assign',
                    preConfirm: () => {
                        const selectedManager = document.getElementById('manager-select')?.value;
                        if (!selectedManager) {
                            Swal.showValidationMessage('Please select a manager.');
                            return false;
                        }
                        return { business: businessId, manager: selectedManager };
                    }
                }).then(result => {
                    if (result.isConfirmed) {
                        const { business, manager } = result.value;
                        assignManagerToBusiness(business, manager);
                    }
                });
            }

            function assignToBranch(branches, managerOptions) {
                const branchOptions = branches.map(branch =>
                    `<option value="${branch.branch_id}">${branch.branch_location} (ID: ${branch.branch_id})</option>`
                ).join('');

                Swal.fire({
                    title: 'Assign to a Branch',
                    html: `
                <label for="branch-select">Choose a branch:</label>
                <select id="branch-select" class="swal2-input">${branchOptions}</select>
                <label for="manager-select">Choose a manager:</label>
                <select id="manager-select" class="swal2-input">${managerOptions}</select>
            `,
                    showCancelButton: true,
                    confirmButtonText: 'Assign',
                    preConfirm: () => {
                        const selectedBranch = document.getElementById('branch-select')?.value;
                        const selectedManager = document.getElementById('manager-select')?.value;

                        if (!selectedBranch || !selectedManager) {
                            Swal.showValidationMessage('Please select both a branch and a manager.');
                            return false;
                        }
                        return { branch: selectedBranch, manager: selectedManager };
                    }
                }).then(result => {
                    if (result.isConfirmed) {
                        const { branch, manager } = result.value;
                        assignManagerToBranch(branch, manager);
                    }
                });
            }

            async function assignManagerToBranch(branchId, managerId) {
                try {
                    const response = await fetch('../endpoints/assign/assign_branch_manager.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ branch_id: branchId, manager_id: managerId })
                    });
                    const data = await response.json();

                    if (data.success) {
                        Swal.fire('Success', data.message, 'success');
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                } catch (error) {
                    Swal.fire('Error', 'Failed to assign manager. Please try again.', 'error');
                    console.error('Error:', error);
                }
            }

            async function assignManagerToBusiness(businessId, managerId) {
                try {
                    const response = await fetch('../endpoints/assign/assign_business_manager.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ business_id: businessId, manager_id: managerId })
                    });
                    const data = await response.json();

                    if (data.success) {
                        Swal.fire('Success', data.message, 'success');
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                } catch (error) {
                    Swal.fire('Error', 'Failed to assign manager. Please try again.', 'error');
                    console.error('Error:', error);
                }
            }
        });




        document.addEventListener('DOMContentLoaded', () => {
            const listManagerButtons = document.querySelectorAll('.list-managers');

            listManagerButtons.forEach(button => {
                button.addEventListener('click', async () => {
                    const businessId = button.getAttribute('data-business-id');
                    const branches = JSON.parse(button.getAttribute('data-branches') || '[]');

                    let managersHtml = '';

                    try {
                        // Fetch Business Manager
                        const businessResponse = await fetch(`../endpoints/assign/fetch_assign_managers.php?business_id=${businessId}`);
                        const businessData = await businessResponse.json();

                        if (businessData.success) {
                            managersHtml += `<p><strong>Business Manager:</strong> ${businessData.managers.length > 0
                                ? businessData.managers.map(manager => `
                                        Username: ${manager.user_name} (ID: ${manager.id})
                                        <button class="btn btn-danger btn-sm unassign-manager" 
                                                data-manager-id="${manager.id}" 
                                                data-type="business" 
                                                data-business-id="${businessId}">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    `).join('<br>')
                                : 'None assigned'
                                }</p>`;
                        }

                        // Fetch Branch Managers
                        if (branches.length > 0) {
                            for (const branch of branches) {
                                const branchResponse = await fetch(`../endpoints/assign/fetch_assign_managers.php?branch_id=${branch.branch_id}`);
                                const branchData = await branchResponse.json();

                                if (branchData.success) {
                                    managersHtml += `<p><strong>Branch ${branch.branch_id} (${branch.branch_location}):</strong> ${branchData.managers.length > 0
                                        ? branchData.managers.map(manager => `
                                                Username: ${manager.user_name} (ID: ${manager.id})
                                                <button class="btn btn-danger btn-sm unassign-manager" 
                                                        data-manager-id="${manager.id}" 
                                                        data-type="branch" 
                                                        data-branch-id="${branch.branch_id}">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            `).join('<br>')
                                        : 'None assigned'
                                        }</p>`;
                                }
                            }
                        }

                        // Display SweetAlert with Managers
                        Swal.fire({
                            title: 'List of Managers',
                            html: managersHtml || '<p>No managers found.</p>',
                            confirmButtonText: 'Close'
                        });

                        // Add event listeners to unassign buttons
                        document.querySelectorAll('.unassign-manager').forEach(unassignButton => {
                            unassignButton.addEventListener('click', (event) => {
                                const managerId = unassignButton.getAttribute('data-manager-id');
                                const type = unassignButton.getAttribute('data-type');
                                const id = type === 'business'
                                    ? unassignButton.getAttribute('data-business-id')
                                    : unassignButton.getAttribute('data-branch-id');

                                // SweetAlert confirmation
                                Swal.fire({
                                    title: 'Are you sure?',
                                    text: 'Do you really want to unassign this manager?',
                                    icon: 'warning',
                                    showCancelButton: true,
                                    confirmButtonText: 'Yes, unassign',
                                    cancelButtonText: 'Cancel'
                                }).then(async (result) => {
                                    if (result.isConfirmed) {
                                        try {
                                            const response = await fetch(`../endpoints/assign/unassign_manager.php`, {
                                                method: 'POST',
                                                headers: { 'Content-Type': 'application/json' },
                                                body: JSON.stringify({ manager_id: managerId, type, id })
                                            });
                                            const data = await response.json();

                                            if (data.success) {
                                                Swal.fire({
                                                    title: 'Success',
                                                    text: data.message || 'Manager unassigned successfully!',
                                                    icon: 'success',
                                                    confirmButtonText: 'Close'
                                                }).then(() => location.reload()); // Refresh page to update data
                                            } else {
                                                Swal.fire({
                                                    title: 'Error',
                                                    text: data.message || 'Failed to unassign manager.',
                                                    icon: 'error',
                                                    confirmButtonText: 'Close'
                                                });
                                            }
                                        } catch (error) {
                                            console.error('Error unassigning manager:', error);
                                            Swal.fire({
                                                title: 'Error',
                                                text: 'Failed to unassign manager. Please try again.',
                                                icon: 'error',
                                                confirmButtonText: 'Close'
                                            });
                                        }
                                    }
                                });
                            });
                        });
                    } catch (error) {
                        console.error('Error fetching managers:', error);
                        Swal.fire({
                            title: 'Error',
                            text: 'Failed to fetch managers. Please try again.',
                            icon: 'error',
                            confirmButtonText: 'Close'
                        });
                    }
                });
            });
        });


    </script>

</body>

</html>