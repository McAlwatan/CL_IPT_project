<?php
$activePage = 'groups';
$pageTitle = 'Group Chat';
require_once __DIR__ . '/../../app/includes/dashboard_head.php';

$groupId = $_GET['group_id'] ?? null;
$userId = $_SESSION['user_id'];

if (!$groupId) {
    header("Location: /CL_DEV/CampusLink/public/groups/index.php");
    exit;
}

$stmt = $pdo->prepare("SELECT name FROM groups WHERE id = ?");
$stmt->execute([$groupId]);
$group = $stmt->fetch();

if (!$group) {
    die("<div class='feed-empty'><p>Group chat room not found.</p></div>");
}
?>

<a href="/CL_DEV/CampusLink/public/groups/view.php?id=<?= $groupId ?>" class="clg-back">← Back to notice board</a>

<div class="clg-chat-header">
    <h1><i class="fa-regular fa-comment"></i> <?= htmlspecialchars($group['name']) ?></h1>
    <p>Live messaging channel for this group.</p>
</div>

<div class="chat-container">
    <div class="chat-history" id="chatWindow">
        <p class="chat-syncing">Loading messages...</p>
    </div>
    <div class="chat-input-bar">
        <input type="text" id="msgInput" placeholder="Type a message..." autocomplete="off">
        <button id="sendBtn">Send</button>
    </div>
</div>

<script>
const groupId = <?= (int)$groupId ?>;
const chatWindow = document.getElementById('chatWindow');
const msgInput = document.getElementById('msgInput');
const sendBtn = document.getElementById('sendBtn');

function fetchMessages() {
    fetch(`fetch-messages.php?group_id=${groupId}`)
        .then(res => res.json())
        .then(messages => {
            if (!messages.length) {
                chatWindow.innerHTML = '<p class="chat-syncing">No messages yet — say hello.</p>';
                return;
            }

            let html = '';
            messages.forEach(msg => {
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
            const wasEmpty = chatWindow.querySelector('.chat-syncing') !== null;
            chatWindow.innerHTML = html;

            if (shouldScroll || wasEmpty) {
                chatWindow.scrollTop = chatWindow.scrollHeight;
            }
        });
}

function sendMessage() {
    const text = msgInput.value.trim();
    if (!text) return;

    fetch('send-message.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `group_id=${groupId}&message=${encodeURIComponent(text)}`
    }).then(() => {
        msgInput.value = '';
        fetchMessages();
    });
}

sendBtn.addEventListener('click', sendMessage);
msgInput.addEventListener('keypress', (e) => { if (e.key === 'Enter') sendMessage(); });

fetchMessages();
setInterval(fetchMessages, 2000);
</script>

<?php require_once __DIR__ . '/../../app/includes/dashboard_foot.php'; ?>