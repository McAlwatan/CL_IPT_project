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