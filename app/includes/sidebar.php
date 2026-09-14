<?php
$activePage = $activePage ?? null;

$navItems = [
    'feed'        => ['label' => 'Feed',        'href' => '/IPT_WEB_PROJECT/CampusLink/public/feed/index.php'],
    'groups'      => ['label' => 'Groups',      'href' => '/IPT_WEB_PROJECT/CampusLink/public/groups/index.php'],
    'documents'   => ['label' => 'Documents',   'href' => '/IPT_WEB_PROJECT/CampusLink/public/documents/index.php'],
    'marketplace' => ['label' => 'Marketplace', 'href' => '/IPT_WEB_PROJECT/CampusLink/public/marketplace/index.php'],
    'skills'      => ['label' => 'Skills',      'href' => '/IPT_WEB_PROJECT/CampusLink/public/skills/index.php'],
    'messages'    => ['label' => 'Messages',    'href' => '/IPT_WEB_PROJECT/CampusLink/public/messages/index.php'],
];
?>
<aside class="sidebar">
    <a href="/IPT_WEB_PROJECT/CampusLink/public/feed/index.php" class="sidebar-brand">CampusLink</a>

    <nav class="sidebar-nav">
        <?php foreach ($navItems as $key => $item): ?>
            <a href="<?= $item['href'] ?>"
               class="sidebar-link<?= $activePage === $key ? ' active' : '' ?>">
                <?= htmlspecialchars($item['label']) ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-footer">
        <a href="/IPT_WEB_PROJECT/CampusLink/public/profile/settings.php"
           class="sidebar-link<?= $activePage === 'settings' ? ' active' : '' ?>">
            Settings
        </a>
        <a href="/IPT_WEB_PROJECT/CampusLink/public/auth/logout.php" class="sidebar-logout">
            Log out
        </a>
    </div>
</aside>