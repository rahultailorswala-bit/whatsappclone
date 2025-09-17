<?php
session_start();
require_once 'db.php';
 
// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
 
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
 
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        echo json_encode(['success' => true]);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
        exit;
    }
}
 
// Handle signup
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'signup') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
 
    // Check if username exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Username already taken']);
        exit;
    }
 
    // Create new user
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    if ($stmt->execute([$username, $hashedPassword])) {
        $_SESSION['user_id'] = $pdo->lastInsertId();
        $_SESSION['username'] = $username;
        echo json_encode(['success' => true]);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Registration failed']);
        exit;
    }
}
 
// Check if user is logged in
$loggedIn = isset($_SESSION['user_id']);
if (!$loggedIn && basename($_SERVER['PHP_SELF']) !== 'index.php') {
    header("Location: index.php");
    exit;
}
 
// Get users for chat list
if ($loggedIn) {
    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE id != ? ORDER BY username");
    $stmt->execute([$_SESSION['user_id']]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
 
// Handle sending messages
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_message') {
    $receiver_id = $_POST['receiver_id'];
    $message = trim($_POST['message']);
 
    if (!empty($message)) {
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $receiver_id, $message]);
        echo json_encode(['success' => true]);
        exit;
    }
    echo json_encode(['success' => false]);
    exit;
}
 
// Get messages for a chat
if (isset($_GET['action']) && $_GET['action'] === 'get_messages' && isset($_GET['contact_id'])) {
    $contact_id = $_GET['contact_id'];
    $stmt = $pdo->prepare("
        SELECT m.*, u.username as sender_name 
        FROM messages m 
        JOIN users u ON m.sender_id = u.id 
        WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) 
        ORDER BY created_at
    ");
    $stmt->execute([$_SESSION['user_id'], $contact_id, $contact_id, $_SESSION['user_id']]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
 
    // Mark messages as read
    $stmt = $pdo->prepare("UPDATE messages SET status = 'read' WHERE sender_id = ? AND receiver_id = ? AND status = 'sent'");
    $stmt->execute([$contact_id, $_SESSION['user_id']]);
 
    echo json_encode($messages);
    exit;
}
 
// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}
?>
 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhatsApp Clone</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Helvetica, Arial, sans-serif;
        }
 
        body {
            background-color: #3b4a54;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
 
        .whatsapp-container {
            width: 90%;
            height: 95vh;
            background-color: #111b21;
            display: flex;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
 
        /* Login/Signup styles */
        .auth-container {
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #00b09b, #96c93d);
        }
 
        .auth-form {
            background: white;
            padding: 30px 40px;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
            width: 320px;
        }
 
        .auth-form h2 {
            margin-bottom: 20px;
            color: #075E54;
            text-align: center;
        }
 
        .auth-form input {
            width: 100%;
            padding: 12px 15px;
            margin: 10px 0 20px 0;
            border: 1.5px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
 
        .auth-form input:focus {
            border-color: #25D366;
            outline: none;
        }
 
        .auth-form button {
            width: 100%;
            background-color: #25D366;
            border: none;
            padding: 14px;
            color: white;
            font-weight: 600;
            font-size: 16px;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
 
        .auth-form button:hover {
            background-color: #128C7E;
        }
 
        .auth-switch {
            margin-top: 15px;
            text-align: center;
            font-size: 14px;
        }
 
        .auth-switch a {
            color: #128C7E;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
        }
 
        .error {
            color: #e74c3c;
            font-size: 14px;
            margin-bottom: 10px;
            text-align: center;
        }
 
        /* Left sidebar styles */
        .sidebar {
            width: 30%;
            background-color: #111b21;
            border-right: 1px solid #2a3942;
            display: flex;
            flex-direction: column;
        }
 
        .sidebar-header {
            background-color: #202c33;
            padding: 10px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
 
        .user-profile {
            display: flex;
            align-items: center;
            color: #e9edef;
        }
 
        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            overflow: hidden;
            margin-right: 10px;
            background-color: #6a7175;
            display: flex;
            justify-content: center;
            align-items: center;
            color: white;
            font-weight: 500;
        }
 
        .logout-btn {
            background: transparent;
            border: none;
            color: #aebac1;
            cursor: pointer;
            font-size: 14px;
        }
 
        .search-container {
            padding: 8px 14px;
            background-color: #111b21;
        }
 
        .search-box {
            background-color: #202c33;
            border-radius: 18px;
            padding: 8px 20px;
            display: flex;
            align-items: center;
        }
 
        .search-box input {
            background: transparent;
            border: none;
            outline: none;
            color: #d1d7db;
            padding-left: 10px;
            width: 100%;
        }
 
        .chat-list {
            flex: 1;
            overflow-y: auto;
        }
 
        .chat-item {
            display: flex;
            padding: 10px 16px;
            cursor: pointer;
            border-bottom: 1px solid #222d34;
            transition: background-color 0.2s;
        }
 
        .chat-item:hover {
            background-color: #202c33;
        }
 
        .chat-item.active {
            background-color: #2a3942;
        }
 
        .chat-info {
            margin-left: 15px;
            flex: 1;
        }
 
        .chat-top {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
 
        .chat-name {
            color: #e9edef;
            font-weight: 500;
        }
 
        .chat-time {
            color: #8696a0;
            font-size: 0.8rem;
        }
 
        .chat-bottom {
            display: flex;
            justify-content: space-between;
        }
 
        .chat-message {
            color: #8696a0;
            font-size: 0.9rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 180px;
        }
 
        .chat-status {
            color: #8696a0;
            font-size: 0.8rem;
        }
 
        /* Main chat area styles */
        .chat-area {
            flex: 1;
            background-image: url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png');
            background-size: cover;
            display: flex;
            flex-direction: column;
        }
 
        .chat-header {
            background-color: #202c33;
            padding: 10px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-left: 1px solid #2a3942;
        }
 
        .current-chat-info {
            display: flex;
            align-items: center;
        }
 
        .current-chat-name {
            color: #e9edef;
            margin-left: 15px;
            font-weight: 500;
        }
 
        .messages-container {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }
 
        .message {
            max-width: 65%;
            padding: 8px 12px;
            margin-bottom: 8px;
            border-radius: 8px;
            position: relative;
            font-size: 15px;
            line-height: 1.4;
        }
 
        .message.received {
            background-color: #2a3942;
            color: #e9edef;
            align-self: flex-start;
            border-top-left-radius: 0;
        }
 
        .message.sent {
            background-color: #005c4b;
            color: #e9edef;
            align-self: flex-end;
            border-top-right-radius: 0;
        }
 
        .message-time {
            font-size: 0.7rem;
            color: #8696a0;
            text-align: right;
            margin-top: 3px;
        }
 
        .message-status {
            font-size: 0.7rem;
            color: #8696a0;
            position: absolute;
            bottom: 3px;
            right: 10px;
        }
 
        .message-input-container {
            background-color: #202c33;
            padding: 10px 16px;
            display: flex;
            align-items: center;
            border-left: 1px solid #2a3942;
        }
 
        .message-input {
            flex: 1;
            background-color: #2a3942;
            border: none;
            border-radius: 20px;
            padding: 12px 20px;
            color: #d1d7db;
            outline: none;
        }
 
        .send-button {
            background-color: #25D366;
            border: none;
            color: white;
            padding: 10px 15px;
            margin-left: 10px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 18px;
        }
 
        /* Mobile responsiveness */
        @media (max-width: 900px) {
            .whatsapp-container {
                width: 100%;
                height: 100vh;
                border-radius: 0;
            }
 
            .sidebar {
                width: 40%;
            }
        }
 
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                display: flex;
            }
 
            .chat-area {
                display: none;
            }
 
            .chat-area.active {
                display: flex;
                width: 100%;
            }
 
            .sidebar.hidden {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="whatsapp-container">
        <?php if (!$loggedIn): ?>
            <!-- Login/Signup Form -->
            <div class="auth-container">
                <div class="auth-form" id="authForm">
                    <h2 id="authTitle">Login to WhatsApp</h2>
                    <div id="error" class="error"></div>
                    <input type="text" id="username" placeholder="Username" autocomplete="off">
                    <input type="password" id="password" placeholder="Password" autocomplete="off">
                    <button id="authButton" onclick="login()">Login</button>
                    <div class="auth-switch">
                        <span id="authSwitchText">Don't have an account? </span>
                        <a id="authSwitchLink" onclick="toggleAuth()">Sign up</a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Chat Interface -->
            <!-- Left Sidebar -->
            <div class="sidebar" id="sidebar">
                <div class="sidebar-header">
                    <div class="user-profile">
                        <div class="avatar">
                            <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                        </div>
                        <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    </div>
                    <button class="logout-btn" onclick="location.href='?logout=true'">Logout</button>
                </div>
 
                <div class="search-container">
                    <div class="search-box">
                        <span>🔍</span>
                        <input type="text" placeholder="Search or start new chat">
                    </div>
                </div>
 
                <div class="chat-list">
                    <?php foreach ($users as $user): ?>
                    <div class="chat-item" data-user-id="<?php echo $user['id']; ?>">
                        <div class="avatar">
                            <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                        </div>
                        <div class="chat-info">
                            <div class="chat-top">
                                <div class="chat-name"><?php echo htmlspecialchars($user['username']); ?></div>
                                <div class="chat-time">14:22</div>
                            </div>
                            <div class="chat-bottom">
                                <div class="chat-message">Hey there! I'm using WhatsApp.</div>
                                <div class="chat-status">✓✓</div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
 
            <!-- Main Chat Area -->
            <div class="chat-area" id="chatArea">
                <div class="chat-header">
                    <div class="current-chat-info">
                        <div class="avatar" id="currentChatAvatar">
                            W
                        </div>
                        <div class="current-chat-name" id="currentChatName">Select a chat</div>
                    </div>
                </div>
 
                <div class="messages-container" id="messagesContainer">
                    <div class="message info">
                        Select a contact to start chatting
                    </div>
                </div>
 
                <div class="message-input-container">
                    <input type="text" class="message-input" placeholder="Type a message" id="messageInput" disabled>
                    <button class="send-button" id="sendButton" disabled>➤</button>
                </div>
            </div>
        <?php endif; ?>
    </div>
 
    <script>
        <?php if (!$loggedIn): ?>
        let isLoginMode = true;
 
        function toggleAuth() {
            isLoginMode = !isLoginMode;
            const title = document.getElementById('authTitle');
            const button = document.getElementById('authButton');
            const switchText = document.getElementById('authSwitchText');
            const switchLink = document.getElementById('authSwitchLink');
 
            if (isLoginMode) {
                title.textContent = 'Login to WhatsApp';
                button.textContent = 'Login';
                button.setAttribute('onclick', 'login()');
                switchText.textContent = 'Don\'t have an account? ';
                switchLink.textContent = 'Sign up';
            } else {
                title.textContent = 'Create Account';
                button.textContent = 'Sign Up';
                button.setAttribute('onclick', 'signup()');
                switchText.textContent = 'Already have an account? ';
                switchLink.textContent = 'Login';
            }
 
            document.getElementById('error').textContent = '';
        }
 
        function login() {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();
            const errorDiv = document.getElementById('error');
            errorDiv.textContent = '';
 
            if (!username || !password) {
                errorDiv.textContent = 'Please enter username and password.';
                return;
            }
 
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'index.php', true);
            xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (this.status === 200) {
                    const res = JSON.parse(this.responseText);
                    if (res.success) {
                        window.location.reload();
                    } else {
                        errorDiv.textContent = res.message;
                    }
                } else {
                    errorDiv.textContent = 'Server error. Try again later.';
                }
            };
            xhr.send('action=login&username=' + encodeURIComponent(username) + '&password=' + encodeURIComponent(password));
        }
 
        function signup() {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();
            const errorDiv = document.getElementById('error');
            errorDiv.textContent = '';
 
            if (!username || !password) {
                errorDiv.textContent = 'Please enter username and password.';
                return;
            }
            if (username.length < 3) {
                errorDiv.textContent = 'Username must be at least 3 characters.';
                return;
            }
            if (password.length < 5) {
                errorDiv.textContent = 'Password must be at least 5 characters.';
                return;
            }
 
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'index.php', true);
            xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (this.status === 200) {
                    const res = JSON.parse(this.responseText);
                    if (res.success) {
                        window.location.reload();
                    } else {
                        errorDiv.textContent = res.message;
                    }
                } else {
                    errorDiv.textContent = 'Server error. Try again later.';
                }
            };
            xhr.send('action=signup&username=' + encodeURIComponent(username) + '&password=' + encodeURIComponent(password));
        }
        <?php else: ?>
        let currentContactId = null;
        let messageInterval = null;
 
        document.addEventListener('DOMContentLoaded', function() {
            const chatItems = document.querySelectorAll('.chat-item');
            const messageInput = document.getElementById('messageInput');
            const sendButton = document.getElementById('sendButton');
            const messagesContainer = document.getElementById('messagesContainer');
 
            // Chat item click event
            chatItems.forEach(item => {
                item.addEventListener('click', function() {
                    const userId = this.getAttribute('data-user-id');
                    const userName = this.querySelector('.chat-name').textContent;
                    const userInitial = userName.charAt(0).toUpperCase();
 
                    // Update UI
                    document.getElementById('currentChatName').textContent = userName;
                    document.getElementById('currentChatAvatar').textContent = userInitial;
 
                    // Remove active class from all items
                    chatItems.forEach(chat => chat.classList.remove('active'));
 
                    // Add active class to clicked item
                    this.classList.add('active');
 
                    // Enable message input
                    messageInput.disabled = false;
                    sendButton.disabled = false;
 
                    // Set current contact
                    currentContactId = userId;
 
                    // Load messages
                    loadMessages();
 
                    // Start polling for new messages
                    if (messageInterval) clearInterval(messageInterval);
                    messageInterval = setInterval(loadMessages, 2000);
                });
            });
 
            // Send message function
            function sendMessage() {
                const messageText = messageInput.value.trim();
                if (messageText === '' || !currentContactId) return;
 
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'index.php', true);
                xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                xhr.onload = function() {
                    if (this.status === 200) {
                        const res = JSON.parse(this.responseText);
                        if (res.success) {
                            messageInput.value = '';
                            loadMessages();
                        }
                    }
                };
                xhr.send('action=send_message&receiver_id=' + currentContactId + '&message=' + encodeURIComponent(messageText));
            }
 
            // Load messages function
            function loadMessages() {
                if (!currentContactId) return;
 
                const xhr = new XMLHttpRequest();
                xhr.open('GET', 'index.php?action=get_messages&contact_id=' + currentContactId, true);
                xhr.onload = function() {
                    if (this.status === 200) {
                        const messages = JSON.parse(this.responseText);
                        displayMessages(messages);
                    }
                };
                xhr.send();
            }
 
            // Display messages function
            function displayMessages(messages) {
                messagesContainer.innerHTML = '';
 
                if (messages.length === 0) {
                    messagesContainer.innerHTML = '<div class="message info">No messages yet. Start a conversation!</div>';
                    return;
                }
 
                messages.forEach(message => {
                    const isSent = message.sender_id == <?php echo $_SESSION['user_id']; ?>;
                    const messageElement = document.createElement('div');
                    messageElement.classList.add('message');
                    messageElement.classList.add(isSent ? 'sent' : 'received');
 
                    const time = new Date(message.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
 
                    messageElement.innerHTML = `
                        ${message.message}
                        <div class="message-time">${time}</div>
                        ${isSent ? `<div class="message-status">${message.status === 'read' ? '✓✓' : '✓'}</div>` : ''}
                    `;
 
                    messagesContainer.appendChild(messageElement);
                });
 
                // Scroll to bottom
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }
 
            // Event listeners for sending messages
            sendButton.addEventListener('click', sendMessage);
 
            messageInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    sendMessage();
                }
            });
 
            // Mobile responsiveness
            if (window.innerWidth <= 768) {
                // Add back button functionality for mobile
                const chatHeader = document.querySelector('.chat-header');
                const backButton = document.createElement('button');
                backButton.innerHTML = '←';
                backButton.classList.add('logout-btn');
                backButton.addEventListener('click', function() {
                    document.getElementById('sidebar').classList.remove('hidden');
                    document.getElementById('chatArea').classList.remove('active');
                });
 
                chatHeader.insertBefore(backButton, chatHeader.firstChild);
 
                // Show sidebar only initially on mobile
                document.getElementById('sidebar').classList.remove('hidden');
                document.getElementById('chatArea').classList.remove('active');
 
                // Chat item click for mobile
                chatItems.forEach(item => {
                    item.addEventListener('click', function() {
                        document.getElementById('sidebar').classList.add('hidden');
                        document.getElementById('chatArea').classList.add('active');
                    });
                });
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>
