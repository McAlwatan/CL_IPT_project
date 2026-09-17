<?php
$activePage = 'messages';
$pageTitle = 'Direct Messages';
require_once __DIR__ . '/../../app/includes/dashboard_head.php';

$userId = $_SESSION['user_id'];

// Get the person we are currently chatting with from the URL (if selected)
$activeChatPartnerId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;

// 1. Fetch all people the logged-in student has an active message history with
$stmt = $pdo->prepare("
    SELECT DISTINCT u.id, u.name 
    FROM users u
    JOIN messages m ON (m.sender_id = u.id OR m.chat_room_id = u.id) -- Using chat_room_id as a loose placeholder or recipient_id match
    WHERE u.id != ? AND (m.sender_id = ? OR m.chat_room_id = ?)
");
// For a simple peer-to-peer shortcut without creating 1000 room entries, we reuse chat_room_id to hold the RECIPIENT_ID for direct messages!
$stmt->execute([$userId, $userId, $userId]);
$activeChats = $stmt->fetchAll();

// If we came from a profile page click but have no chat history yet, ensure they appear in our listing sidebar!
if ($activeChatPartnerId && !in_array($activeChatPartnerId, array_column($activeChats, 'id'))) {
    $partnerStmt = $pdo->prepare("SELECT id, name FROM users WHERE id = ?");
    $partnerStmt->execute([$activeChatPartnerId]);
    $partnerData = $partnerStmt->fetch();
    if ($partnerData) {
        $activeChats[] = $partnerData;
    }
}
?>

<div class="messages-layout" style="display: flex; gap: 24px; min-height: calc(100vh - 160px); margin-top: 10px;">
    
    <!-- ⬅️ LEFT PANEL: Active Conversations Listing -->
    <div class="inbox-sidebar" style="width: 280px; background-color: #2a2a2a; border: 1px solid #3d3d3d; border-radius: 16px; padding: 20px; display: flex; flex-direction: column; gap: 12px;">
        <h3 style="color: #ffffff; font-size: 18px; font-weight: 600; border-bottom: 1px solid #3d3d3d; padding-bottom: 12px; margin-bottom: 8px;">Inbox</h3>
        <div class="threads-list" style="display: flex; flex-direction: column; gap: 8px; overflow-y: auto; flex: 1;">
            <?php if (empty($activeChats)): ?>
                <p style="color: #757575; font-size: 14px; text-align: center; margin-top: 20px;">No message history found.</p>
            <?php else: ?>
                <?php foreach ($activeChats as $chat): ?>
                    <?php $isActiveThread = ($chat['id'] === $activeChatPartnerId); ?>
                    <a href="index.php?user_id=<?= $chat['id'] ?>" style="
                        display: flex; align-items: center; gap: 12px; padding: 12px; text-decoration: none; border-radius: 8px;
                        background-color: <?= $isActiveThread ? '#121212' : 'transparent' ?>;
                        border: 1px solid <?= $isActiveThread ? '#ffffff' : 'transparent' ?>;
                        transition: background 0.2s ease;">
                        <span style="width: 32px; height: 32px; background: #ffffff; color: #121212; display: flex; align-items: center; justify-content: center; border-radius: 50%; font-weight: 600; font-size: 13px;">
                            <?= htmlspecialchars(strtoupper(substr($chat['name'], 0, 1))) ?>
                        </span>
                        <span style="color: #ffffff; font-size: 15px; font-weight: 500;"><?= htmlspecialchars($chat['name']) ?></span>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- ➡️ RIGHT PANEL: WhatsApp Active Dialogue Screen -->
    <div class="chat-workspace" style="flex: 1; display: flex; flex-direction: column; justify-content: space-between;">
        <?php if (!$activeChatPartnerId): ?>
            <!-- Default Placeholder if no conversation is clicked yet -->
            <div class="feed-empty" style="flex: 1; display: flex; align-items: center; justify-content: center; margin: 0;">
                <p>Select a student from your inbox directory panel to begin messaging.</p>
            </div>
        <?php else: ?>
            
            <div class="chat-container" style="margin-top: 0; flex: 1; display: flex; flex-direction: column;">
                <!-- Direct Private Chat Room Timeline Stream -->
                <div class="chat-history" id="dmWindow" style="flex: 1; padding: 24px; overflow-y: auto;">
                    <p style="color: #757575; text-align: center;">Syncing encrypted stream...</p>
                </div>

                <!-- Messaging Post Input Element Bar -->
                <div class="chat-input-bar">
                    <input type="text" id="dmInput" placeholder="Type a private message..." autocomplete="off">
                    <button id="dmSendBtn" class="feed-submit-btn">Send</button>
                </div>
            </div>

            <!-- Polling Scripts Handling Communication Blocks -->
            <script>
            const partnerId = <?= (int)$activeChatPartnerId ?>;
            const dmWindow = document.getElementById('dmWindow');
            const dmInput = document.getElementById('dmInput');
            const dmSendBtn = document.getElementById('dmSendBtn');

            function fetchDMs() {
                fetch(`fetch-messages.php?partner_id=${partnerId}`)
                    .then(res => res.json())
                    .then(messages => {
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
                        dmWindow.innerHTML = html;
                        
                        if (shouldScroll || dmWindow.innerHTML.includes('Syncing encrypted stream...')) {
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
            dmInput.addEventListener('keypress', (e) => { if(e.key === 'Enter') sendDM(); });

            fetchDMs();
            setInterval(fetchDMs, 2000); // Polls server backend data records streams every 2 seconds
            </script>
        <?php endif; ?>
    </div>

</div>

<?php require_once __DIR__ . '/../../app/includes/dashboard_foot.php'; ?>
