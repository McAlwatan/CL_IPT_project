<?php
$activePage = 'groups';
$pageTitle = 'Group Chat';
require_once __DIR__ . '/../../app/includes/dashboard_head.php';

$groupId = $_GET['group_id'] ?? null;
$userId = $_SESSION['user_id'];

if (!$groupId) {
    header("Location: /IPT_WEB_PROJECT/CampusLink/public/groups/index.php");
    exit;
}

// Confirm the group exists and load the title meta definitions
$stmt = $pdo->prepare("SELECT name FROM groups WHERE id = ?");
$stmt->execute([$groupId]);
$group = $stmt->fetch();

if (!$group) {
    die("<div class='feed-empty'><p>Group chat room not found.</p></div>");
}
?>

<div style="margin-bottom: 20px;">
    <a href="/IPT_WEB_PROJECT/CampusLink/public/groups/view.php?id=<?= $groupId ?>" style="color: #a0a0a0; text-decoration: none; font-size: 14px;">← Back to Notice Board</a>
</div>

<div class="page-header">
    <h1>💬 <?= htmlspecialchars($group['name']) ?> Room Chat</h1>
    <p>Live messaging workspace channel.</p>
</div>

<!-- Dynamic Layout Framework Shell -->
<div class="chat-container">
    <!-- Live dynamic historical payload items drop inside here via the polling script -->
    <div class="chat-history" id="chatWindow">
        <p style="color: #757575; text-align: center;">Syncing dialogue stream...</p>
    </div>

    <!-- Asynchronous Post Processing Submission Forms Elements -->
    <div class="chat-input-bar">
        <input type="text" id="msgInput" placeholder="Type a message..." autocomplete="off">
        <button id="sendBtn" class="feed-submit-btn">Send</button>
    </div>
</div>

<!-- JavaScript Integration Pipeline for Handling Automated Script Refresh Syncs -->
<script>
const groupId = <?= (int)$groupId ?>;
const chatWindow = document.getElementById('chatWindow');
const msgInput = document.getElementById('msgInput');
const sendBtn = document.getElementById('sendBtn');

// Function to pull down data records directly out of our log endpoint router
function fetchMessages() {
    fetch(`fetch-messages.php?group_id=${groupId}`)
        .then(res => res.json())
        .then(messages => {
            let html = '';
            messages.forEach(msg => {
                // Evaluates alignment rule properties natively inside client script
                const alignmentClass = msg.is_me ? 'sent' : 'received';
                const senderNameLabel = msg.is_me ? '' : `<span class="message-sender">${msg.sender_name}</span>`;
                
                html += `
                    <div class="message-row ${alignmentClass}">
                        <div class="message-bubble">
                            ${senderNameLabel}
                            <p>${msg.text}</p>
                            <span class="message-meta">${msg.time}</span>
                        </div>
                    </div>
                `;
            });
            
            const shouldScroll = chatWindow.scrollTop + chatWindow.clientHeight >= chatWindow.scrollHeight - 50;
            chatWindow.innerHTML = html;
            
            if (shouldScroll || chatWindow.innerHTML.includes('Syncing dialogue stream...')) {
                chatWindow.scrollTop = chatWindow.scrollHeight;
            }
        });
}

// Function to send message asynchronously via the backend API controller
function sendMessage() {
    const text = msgInput.value.trim();
    if (!text) return;

    fetch('send-message.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `group_id=${groupId}&message=${encodeURIComponent(text)}`
    }).then(() => {
        msgInput.value = '';
        fetchMessages(); // Instant refresh layout update triggers manually on callback complete
    });
}

// Listeners for triggers
sendBtn.addEventListener('click', sendMessage);
msgInput.addEventListener('keypress', (e) => { if(e.key === 'Enter') sendMessage(); });

// Start the continuous short-polling tracking loops (Runs every 2000 milliseconds)
fetchMessages();
setInterval(fetchMessages, 2000);
</script>

<?php require_once __DIR__ . '/../../app/includes/dashboard_foot.php'; ?>
