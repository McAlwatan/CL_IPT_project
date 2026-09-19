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

$stmt = $pdo->prepare("SELECT name FROM groups WHERE id = ?");
$stmt->execute([$groupId]);
$group = $stmt->fetch();

if (!$group) {
    die("<div class='feed-empty'><p>Group chat room not found.</p></div>");
}
?>

<style>
    :root {
        --clf-ink: #1c1c1c; --clf-sub: #767676; --clf-line: #e4e4e4;
        --clf-bg-soft: #f6f6f4; --clf-accent: #b8441f;
    }

    .clg-back { color: var(--clf-sub); text-decoration: none; font-size: 13px; }
    .clg-back:hover { color: var(--clf-ink); }

    .clg-chat-header { margin: 16px 0 18px; }
    .clg-chat-header h1 { color: var(--clf-ink); font-size: 20px; font-weight: 700; margin: 0 0 4px; }
    .clg-chat-header p { color: var(--clf-sub); font-size: 13px; margin: 0; }

    .chat-container {
        background: #fff; border: 1px solid var(--clf-line); border-radius: 8px;
        display: flex; flex-direction: column; overflow: hidden;
    }
    .chat-history {
        height: 460px; overflow-y: auto; padding: 16px; display: flex; flex-direction: column; gap: 8px;
    }
    .message-row { display: flex; }
    .message-row.sent { justify-content: flex-end; }
    .message-row.received { justify-content: flex-start; }

    .message-bubble { max-width: 68%; padding: 8px 12px; border-radius: 12px; font-size: 13.5px; line-height: 1.4; }
    .message-row.received .message-bubble { background: var(--clf-bg-soft); color: var(--clf-ink); border: 1px solid var(--clf-line); }
    .message-row.sent .message-bubble { background: var(--clf-ink); color: #fff; }

    .message-sender { display: block; font-size: 11.5px; font-weight: 700; color: var(--clf-accent); margin-bottom: 2px; }
    .message-row.sent .message-sender { display: none; }
    .message-bubble p { margin: 0; white-space: pre-wrap; word-break: break-word; }
    .message-meta { display: block; font-size: 10.5px; opacity: 0.65; margin-top: 3px; text-align: right; }

    .chat-input-bar {
        display: flex; gap: 10px; padding: 12px 16px; border-top: 1px solid var(--clf-line); background: #fff;
    }
    .chat-input-bar input[type="text"] {
        flex: 1; border: 1px solid var(--clf-line); border-radius: 6px; padding: 9px 12px;
        font-size: 13.5px; font-family: inherit; outline: none; color: var(--clf-ink);
    }
    .chat-input-bar input[type="text"]:focus { border-color: var(--clf-accent); }
    .chat-input-bar button {
        background: var(--clf-ink); color: #fff; border: none; border-radius: 6px;
        padding: 9px 18px; font-size: 13px; font-weight: 600; cursor: pointer;
    }
    .chat-input-bar button:hover { background: #000; }

    .chat-syncing { color: var(--clf-sub); text-align: center; font-size: 13px; margin-top: 20px; }
    #mdgInput { color: var(--clf-bg-soft);}
</style>

<a href="/IPT_WEB_PROJECT/CampusLink/public/groups/view.php?id=<?= $groupId ?>" class="clg-back">← Back to notice board</a>

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