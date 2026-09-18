<?php
$activePage = 'messages';
$pageTitle = 'Direct Messages';
require_once __DIR__ . '/../../app/includes/dashboard_head.php';

$userId = $_SESSION['user_id'];

$activeChatPartnerId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;

$stmt = $pdo->prepare("
    SELECT DISTINCT u.id, u.name 
    FROM users u
    JOIN chat_members cm_partner ON u.id = cm_partner.user_id
    JOIN chat_members cm_me ON cm_partner.chat_room_id = cm_me.chat_room_id
    JOIN chat_rooms cr ON cm_partner.chat_room_id = cr.id
    WHERE u.id != ? AND cm_me.user_id = ? AND cr.type = 'direct'
");
$stmt->execute([$userId, $userId]);
$activeChats = $stmt->fetchAll();

if ($activeChatPartnerId && !in_array($activeChatPartnerId, array_column($activeChats, 'id'))) {
    $partnerStmt = $pdo->prepare("SELECT id, name FROM users WHERE id = ?");
    $partnerStmt->execute([$activeChatPartnerId]);
    $partnerData = $partnerStmt->fetch();
    if ($partnerData) {
        $activeChats[] = $partnerData;
    }
}

$activePartnerName = null;
foreach ($activeChats as $chat) {
    if ((int)$chat['id'] === $activeChatPartnerId) {
        $activePartnerName = $chat['name'];
    }
}

function clInitial($name) {
    $name = trim((string)$name);
    return htmlspecialchars($name === '' ? 'S' : mb_strtoupper(mb_substr($name, 0, 1)));
}
function clAvatarClass($seed) {
    $palette = ['rust', 'ink-blue', 'moss', 'plum'];
    return 'avatar-' . $palette[crc32((string)$seed) % count($palette)];
}
?>

<style>
    :root {
        --clf-ink: #1c1c1c;
        --clf-sub: #767676;
        --clf-line: #e4e4e4;
        --clf-bg-soft: #f6f6f4;
        --clf-accent: #b8441f;
    }

    .clf-avatar {
        width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center;
        justify-content: center; font-weight: 600; font-size: 12.5px; color: #fff; flex-shrink: 0;
    }
    .avatar-rust { background: #a8481f; } .avatar-ink-blue { background: #2c3e5c; }
    .avatar-moss { background: #4a5e3a; } .avatar-plum { background: #5c3a54; }

    .messages-layout {
        display: flex; gap: 16px; min-height: calc(100vh - 160px); margin-top: 10px;
    }

    .inbox-sidebar {
        width: 270px; flex-shrink: 0; background: #fff; border: 1px solid var(--clf-line); border-radius: 8px;
        padding: 16px; display: flex; flex-direction: column; gap: 10px;
    }
    .inbox-sidebar h3 {
        color: var(--clf-ink); font-size: 15px; font-weight: 700; margin: 0 0 8px;
        border-bottom: 1px solid var(--clf-line); padding-bottom: 10px;
    }

    .search-buddy-container { position: relative; margin-bottom: 4px; }
    #buddySearchInput {
        width: 100%; padding: 9px 12px; background: var(--clf-bg-soft); border: 1px solid var(--clf-line);
        border-radius: 6px; color: var(--clf-ink); font-size: 13.5px; outline: none; font-family: inherit;
    }
    #buddySearchInput:focus { border-color: var(--clf-accent); }
    #buddySearchResults {
        display: none; position: absolute; top: 40px; left: 0; right: 0; background: #fff;
        border: 1px solid var(--clf-line); border-radius: 6px; max-height: 220px; overflow-y: auto; z-index: 500;
    }
    .buddy-result {
        display: flex; align-items: center; gap: 9px; padding: 9px 12px; text-decoration: none;
        color: var(--clf-ink); font-size: 13.5px;
    }
    .buddy-result:hover { background: var(--clf-bg-soft); }
    .buddy-empty { color: var(--clf-sub); font-size: 12.5px; padding: 10px; text-align: center; }

    .threads-list { display: flex; flex-direction: column; gap: 4px; overflow-y: auto; flex: 1; }
    .thread-link {
        display: flex; align-items: center; gap: 10px; padding: 9px 10px; text-decoration: none;
        border-radius: 6px; border: 1px solid transparent;
    }
    .thread-link:hover { background: var(--clf-bg-soft); }
    .thread-link.active { background: var(--clf-bg-soft); border-color: var(--clf-line); }
    .thread-link span.name { color: var(--clf-ink); font-size: 13.5px; font-weight: 500; }
    .threads-empty { color: var(--clf-sub); font-size: 13px; text-align: center; margin-top: 16px; }

    .chat-workspace { flex: 1; display: flex; flex-direction: column; justify-content: space-between; min-width: 0; }

    .feed-empty {
        background: #fff; border: 1px dashed var(--clf-line); border-radius: 8px; padding: 28px;
        text-align: center; color: var(--clf-sub); font-size: 13.5px;
        flex: 1; display: flex; align-items: center; justify-content: center;
    }

    .chat-container { flex: 1; display: flex; flex-direction: column; background: #fff; border: 1px solid var(--clf-line); border-radius: 8px; overflow: hidden; }
    .chat-history { flex: 1; padding: 18px; overflow-y: auto; display: flex; flex-direction: column; gap: 8px; }
    .chat-syncing { color: var(--clf-sub); text-align: center; font-size: 13px; }

    .message-row { display: flex; }
    .message-row.sent { justify-content: flex-end; }
    .message-row.received { justify-content: flex-start; }
    .message-bubble { max-width: 68%; padding: 8px 12px; border-radius: 12px; font-size: 13.5px; line-height: 1.4; }
    .message-row.received .message-bubble { background: var(--clf-bg-soft); color: var(--clf-ink); border: 1px solid var(--clf-line); }
    .message-row.sent .message-bubble { background: var(--clf-ink); color: #fff; }
    .message-bubble p { margin: 0; white-space: pre-wrap; word-break: break-word; }
    .message-meta { display: block; font-size: 10.5px; opacity: 0.65; margin-top: 3px; text-align: right; }

    .chat-input-bar { display: flex; gap: 10px; padding: 12px 16px; border-top: 1px solid var(--clf-line); background: #fff; }
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

    .mobile-back-link {
        display: none; align-items: center; gap: 6px; color: var(--clf-sub); text-decoration: none;
        font-size: 13px; font-weight: 600; margin-bottom: 10px;
    }
    .mobile-back-link:hover { color: var(--clf-ink); }

    .chat-partner-header { display: none; align-items: center; gap: 10px; margin-bottom: 10px; }
    .chat-partner-header span.name { font-size: 14.5px; font-weight: 600; color: var(--clf-ink); }
    #dmInput { color: var(--clf-bg-soft);}

    /* --- Mobile: retract the inbox list, show it again via the back link --- */
    @media (max-width: 800px) {
        .messages-layout { flex-direction: column; }

        .inbox-sidebar { width: 100%; }
        .chat-workspace { display: none; }
        .mobile-back-link { display: flex; }
        .chat-partner-header { display: flex; }

        .messages-layout.has-active-chat .inbox-sidebar { display: none; }
        .messages-layout.has-active-chat .chat-workspace { display: flex; }
    }
</style>

<div class="messages-layout<?= $activeChatPartnerId ? ' has-active-chat' : '' ?>">

    <div class="inbox-sidebar">
        <h3>Inbox</h3>
        <div class="search-buddy-container">
            <input type="text" id="buddySearchInput" placeholder="Search classmate to text..." autocomplete="off">
            <div id="buddySearchResults"></div>
        </div>

        <div class="threads-list">
            <?php if (empty($activeChats)): ?>
                <p class="threads-empty">No message history found.</p>
            <?php else: ?>
                <?php foreach ($activeChats as $chat): ?>
                    <?php $isActiveThread = ($chat['id'] === $activeChatPartnerId); ?>
                    <a href="index.php?user_id=<?= $chat['id'] ?>" class="thread-link<?= $isActiveThread ? ' active' : '' ?>">
                        <span class="clf-avatar <?= clAvatarClass($chat['name']) ?>"><?= clInitial($chat['name']) ?></span>
                        <span class="name"><?= htmlspecialchars($chat['name']) ?></span>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="chat-workspace">
        <?php if (!$activeChatPartnerId): ?>
            <div class="feed-empty">
                <p>Select a student from your inbox to begin messaging.</p>
            </div>
        <?php else: ?>

            <a href="index.php" class="mobile-back-link">← Inbox</a>
            <div class="chat-partner-header">
                <span class="clf-avatar <?= clAvatarClass($activePartnerName ?? 'S') ?>"><?= clInitial($activePartnerName ?? 'S') ?></span>
                <span class="name"><?= htmlspecialchars($activePartnerName ?? 'Conversation') ?></span>
            </div>

            <div class="chat-container">
                <div class="chat-history" id="dmWindow">
                    <p class="chat-syncing">Loading messages...</p>
                </div>
                <div class="chat-input-bar">
                    <input type="text" id="dmInput" placeholder="Type a private message..." autocomplete="off">
                    <button id="dmSendBtn">Send</button>
                </div>
            </div>

            <script>
            const partnerId = <?= (int)$activeChatPartnerId ?>;
            const dmWindow = document.getElementById('dmWindow');
            const dmInput = document.getElementById('dmInput');
            const dmSendBtn = document.getElementById('dmSendBtn');

            function fetchDMs() {
                fetch(`fetch-messages.php?partner_id=${partnerId}`)
                    .then(res => res.json())
                    .then(messages => {
                        if (!messages.length) {
                            dmWindow.innerHTML = '<p class="chat-syncing">No messages yet — say hello.</p>';
                            return;
                        }

                        let html = '';
                        messages.forEach(msg => {
                            const alignmentClass = msg.is_me ? 'sent' : 'received';
                            html += `
                                <div class="message-row ${alignmentClass}">
                                    <div class="message-bubble">
                                        <p>${msg.text}</p>
                                        <span class="message-meta">${msg.time}</span>
                                    </div>
                                </div>
                            `;
                        });

                        const shouldScroll = dmWindow.scrollTop + dmWindow.clientHeight >= dmWindow.scrollHeight - 50;
                        const wasEmpty = dmWindow.querySelector('.chat-syncing') !== null;
                        dmWindow.innerHTML = html;

                        if (shouldScroll || wasEmpty) {
                            dmWindow.scrollTop = dmWindow.scrollHeight;
                        }
                    });
            }

            function sendDM() {
                const text = dmInput.value.trim();
                if (!text) return;

                fetch('send.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `recipient_id=${partnerId}&message=${encodeURIComponent(text)}`
                }).then(() => {
                    dmInput.value = '';
                    fetchDMs();
                });
            }

            dmSendBtn.addEventListener('click', sendDM);
            dmInput.addEventListener('keypress', (e) => { if (e.key === 'Enter') sendDM(); });

            fetchDMs();
            setInterval(fetchDMs, 2000);
            </script>
        <?php endif; ?>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('buddySearchInput');
    const resultsDropdown = document.getElementById('buddySearchResults');

    if (searchInput && resultsDropdown) {
        searchInput.addEventListener('input', () => {
            const query = searchInput.value.trim();

            if (query.length < 2) {
                resultsDropdown.style.display = 'none';
                resultsDropdown.innerHTML = '';
                return;
            }

            fetch(`search-users.php?q=${encodeURIComponent(query)}`)
                .then(res => res.json())
                .then(users => {
                    if (users.length === 0) {
                        resultsDropdown.innerHTML = '<p class="buddy-empty">No classmates found.</p>';
                        resultsDropdown.style.display = 'block';
                        return;
                    }

                    let html = '';
                    users.forEach(user => {
                        const initial = user.name.charAt(0).toUpperCase();
                        html += `
                            <a href="index.php?user_id=${user.id}" class="buddy-result">
                                <span class="clf-avatar avatar-ink-blue" style="width:24px;height:24px;font-size:10.5px;">${initial}</span>
                                <strong>${user.name}</strong>
                            </a>
                        `;
                    });

                    resultsDropdown.innerHTML = html;
                    resultsDropdown.style.display = 'block';
                });
        });

        document.addEventListener('click', (e) => {
            if (!searchInput.contains(e.target) && !resultsDropdown.contains(e.target)) {
                resultsDropdown.style.display = 'none';
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../../app/includes/dashboard_foot.php'; ?>