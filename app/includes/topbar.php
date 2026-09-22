<header class="topbar">
    <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle menu" aria-expanded="true">
        <span></span><span></span><span></span>
    </button>

    <div class="topbar-search">
        <input type="text" id="globalSearchInput" placeholder="Search students, groups, documents..." autocomplete="off">
        <div class="search-results" id="globalSearchResults"></div>
    </div>

    <div class="topbar-user" id="userMenuTrigger">
        <span class="topbar-avatar clf-avatar <?= 'avatar-' . ['rust','ink-blue','moss','plum'][crc32($currentUser['name'] ?? 'S') % 4] ?>">
            <?= htmlspecialchars(strtoupper(substr($currentUser['name'] ?? 'S', 0, 1))) ?>
        </span>
        <span class="topbar-name"><?= htmlspecialchars($currentUser['name'] ?? 'Student') ?></span>
        <span class="chevron">▾</span>

        <div class="topbar-user-menu" id="userMenu">
            <a href="/IPT_WEB_PROJECT/CampusLink/public/profile.php"><i class="fa-solid fa-user"></i> Profile</a>
            <a href="/IPT_WEB_PROJECT/CampusLink/public/settings.php"><i class="fa-solid fa-gear"></i> Settings</a>
            <hr>
            <a href="/IPT_WEB_PROJECT/CampusLink/public/auth/logout.php" class="danger"><i class="fa-solid fa-arrow-right-from-bracket"></i> Log out</a>
        </div>
    </div>
</header>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<script src="../../public/js/topbar.js"></script>