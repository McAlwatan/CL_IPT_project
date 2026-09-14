<header class="topbar">
    <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle menu" aria-expanded="true">
        <span></span><span></span><span></span>
    </button>

    <div class="topbar-search">
        <input type="text" placeholder="Search students, groups, documents...">
    </div>

    <a href="/IPT_WEB_PROJECT/CampusLink/public/profile/view.php" class="topbar-user">
        <span class="topbar-avatar">
            <?= htmlspecialchars(strtoupper(substr($currentUser['name'] ?? 'S', 0, 1))) ?>
        </span>
        <span class="topbar-name"><?= htmlspecialchars($currentUser['name'] ?? 'Student') ?></span>
    </a>
</header>