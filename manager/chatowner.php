<?php
session_start();
require_once '../conn/auth.php';
require_once '../conn/conn.php';
validateSession('manager');

$manager_id = $_SESSION['user_id'];

// Fetch owner details for this manager
$query = "SELECT o.* FROM owner o 
          INNER JOIN manager m ON m.owner_id = o.id 
          WHERE m.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $manager_id);
$stmt->execute();
$result = $stmt->get_result();
$owner = $result->fetch_assoc();

if (!$owner) {
    echo "No owner found for this manager.";
    exit;
}


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Owner</title>
    <link rel="icon" href="../assets/logo.png">
    <?php include '../components/head_cdn.php'; ?>
</head>


<body class="d-flex">

    <div id="particles-js"></div>

    <?php include '../components/manager_sidebar.php'; ?>

    <div class="container-fluid page-body">
        <div class="row">
            <div class="col-md-12 dashboard-body">
                <div class="dashboard-content">
                    <h1><b><i class="fas fa-comments me-2"></i> Chat Owner</b></h1>

                    <div class="container-fluid mt-4">
                        <div class="row">
                            <!-- Sidebar with Owner Details -->
                            <div class="col-md-4 col-lg-3 p-0">
                                <div class="list-group bg-light border">
                                    <div class="p-3 bg-primary text-white">
                                        <h5 class="mb-0">Owner Details</h5>
                                    </div>
                                    <div class="list-group-item list-group-item-action d-flex align-items-center">
                                        <img src="../assets/profiles/<?= !empty($owner['image']) ? $owner['image'] : '../assets/profile.png' ?>"
                                            alt="Owner Avatar" style="width: 60px; height: 60px; object-fit: cover;"
                                            class="rounded-circle me-3">
                                        <div>
                                            <strong><?= htmlspecialchars($owner['first_name'] . ' ' . $owner['middle_name'] . ' ' . $owner['last_name']) ?></strong>
                                            <p class="text-muted small mb-0"><?= htmlspecialchars($owner['email']) ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Chat Area -->
                            <div class="col-md-8 col-lg-9 p-0">
                                <div class="d-flex flex-column vh-100 border">
                                    <!-- Chat Header -->
                                    <div id="chat-header" class="p-3 bg-primary text-white d-flex align-items-center">
                                        <h5 class="mb-0">Chat with <?= htmlspecialchars($owner['first_name']) ?></h5>
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

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const messageInput = document.getElementById('message-input');
            const sendButton = document.getElementById('send-btn');
            const chatMessages = document.getElementById('chat-messages');

            // Fetch Messages Function
            async function fetchMessages() {
                const response = await fetch('../endpoints/messages/fetch_messages_manager.php');
                const messages = await response.json();

                chatMessages.innerHTML = '';
                if (messages.error) {
                    chatMessages.innerHTML = `<p class="text-center text-danger">${messages.error}</p>`;
                    return;
                }

                messages.forEach(msg => {
                    const msgClass = msg.sender_type === 'manager' ? 'text-end' : 'text-start';
                    const bgColor = msg.sender_type === 'owner'
                        ? 'background-color: #d1ecf1; color: #0c5460;' // Light blue inline style
                        : 'background-color: #f8f9fa; color: #212529;'; // Default light gray

                    chatMessages.innerHTML += `
            <div class="${msgClass} mb-3">
                <p style="${bgColor}" class="border p-2 rounded">${msg.message}</p>
                <small class="text-muted">${msg.timestamp}</small>
            </div>
        `;
                });

                chatMessages.scrollTop = chatMessages.scrollHeight;
            }




            // Send Message Function
            async function sendMessage() {
                const message = messageInput.value.trim();
                if (!message) return;

                const response = await fetch('../endpoints/messages/send_message_manager.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `message=${encodeURIComponent(message)}`
                });
                const result = await response.json();

                if (result.success) {
                    messageInput.value = '';
                    fetchMessages();
                } else {
                    alert(result.error || 'Failed to send message.');
                }
            }

            sendButton.addEventListener('click', sendMessage);
            messageInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') sendMessage();
            });

            fetchMessages();
            setInterval(fetchMessages, 5000);
        });

    </script>
    <script src="../js/sidebar_manager.js"></script>

</body>

</html>