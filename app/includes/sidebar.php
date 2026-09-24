<?php
$activePage = $activePage ?? null;

$navItems = [
    'feed'        => ['label' => 'Feed',        'href' => '/CL_DEV/CampusLink/public/feed/index.php', 'icon' => '<i class="fa-solid fa-house"></i>'],
    'groups'      => ['label' => 'Groups',      'href' => '/CL_DEV/CampusLink/public/groups/index.php', 'icon' => '<i class="fa-solid fa-people-group"></i>'],
    'documents'   => ['label' => 'Documents',   'href' => '/CL_DEV/CampusLink/public/documents/index.php', 'icon' => '<i class="fa-solid fa-file"></i>'],
    'marketplace' => ['label' => 'Marketplace', 'href' => '/CL_DEV/CampusLink/public/marketplace/index.php', 'icon' => '<i class="fa-solid fa-cart-shopping"></i>'],
    'skills'      => ['label' => 'Skills',      'href' => '/CL_DEV/CampusLink/public/skills/index.php', 'icon' => '<i class="fa-solid fa-user-check"></i>'],
    'messages'    => ['label' => 'Messages',    'href' => '/CL_DEV/CampusLink/public/messages/index.php', 'icon' => '<i class="fa-regular fa-message"></i>'],
];
?>
<aside class="sidebar">
    <img class="clLogo" src="/CL_DEV/CampusLink/public/images/favicon.png" alt="logo"><a href="/CL_DEV/CampusLink/public/feed/index.php" class="sidebar-brand">Campus<span>Link</span></a>

    <nav class="sidebar-nav">
        <?php foreach ($navItems as $key => $item): ?>
            <a href="<?= $item['href'] ?>"
               class="sidebar-link<?= $activePage === $key ? ' active' : '' ?>"
               title="<?= htmlspecialchars($item['label']) ?>">
                <?= $item['icon'] ?>
                <span class="label"><?= htmlspecialchars($item['label']) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-footer">
        <a href="/CL_DEV/CampusLink/public/settings.php"
           class="sidebar-link<?= $activePage === 'settings' ? ' active' : '' ?>"
           title="Settings">
            <i class="fa-solid fa-gear"></i>
            <span class="label">Settings</span>
        </a>
        <a href="/CL_DEV/CampusLink/public/auth/logout.php" class="sidebar-link sidebar-logout" title="Log out">
            <i class="fa-solid fa-arrow-right-from-bracket"></i>
            <span class="label">Log out</span>
        </a>
    </div>
</aside>