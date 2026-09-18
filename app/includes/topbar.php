<?php
// Shared chrome styling + behavior for the topbar and sidebar (search dropdown,
// user menu, sidebar collapse/off-canvas). This <style> block and the closing
// <script> at the bottom of this file should only be included ONCE per page —
// since topbar.php is included on every page, put it here rather than
// duplicating it into sidebar.php too. If your layout actually includes
// sidebar.php before topbar.php, move this whole <style> block there instead
// so it loads before first paint.
?>
<style>
    :root {
        --clf-ink: #1c1c1c;
        --clf-sub: #767676;
        --clf-line: #e4e4e4;
        --clf-bg-soft: #f6f6f4;
        --clf-accent: #b8441f;
        --clf-sidebar-w: 210px;
        --clf-sidebar-rail: 60px;
    }

    .clf-avatar {
        border-radius: 50%; display: flex; align-items: center; justify-content: center;
        font-weight: 600; color: #fff; flex-shrink: 0;
    }
    .avatar-rust { background: #a8481f; } .avatar-ink-blue { background: #2c3e5c; }
    .avatar-moss { background: #4a5e3a; } .avatar-plum { background: #5c3a54; }

    /* ---------- Topbar ---------- */
    .topbar {
        display: flex; align-items: center; gap: 16px;
        height: 58px; padding: 0 20px; background: #fff; border-bottom: 1px solid var(--clf-line);
        position: sticky; top: 0; z-index: 200;
    }

    .sidebar-toggle {
        display: flex; flex-direction: column; justify-content: center; gap: 4px;
        width: 34px; height: 34px; background: none; border: 1px solid var(--clf-line);
        border-radius: 6px; cursor: pointer; flex-shrink: 0; padding: 0;
    }
    .sidebar-toggle:hover { border-color: var(--clf-ink); }
    .sidebar-toggle span { display: block; width: 16px; height: 2px; background: var(--clf-ink); margin: 0 auto; }

    .topbar-search { flex: 1; max-width: 420px; position: relative; }
    .topbar-search input {
        width: 100%; padding: 8px 12px; background: var(--clf-bg-soft); border: 1px solid var(--clf-line);
        border-radius: 6px; color: var(--clf-ink); font-size: 13.5px; font-family: inherit; outline: none;
    }
    .topbar-search input:focus { border-color: var(--clf-accent); }

    .search-results {
        display: none; position: absolute; top: 42px; left: 0; right: 0; background: #fff;
        border: 1px solid var(--clf-line); border-radius: 8px; max-height: 340px; overflow-y: auto; z-index: 300;
    }
    .search-results.open { display: block; }
    .search-group-label {
        font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;
        color: var(--clf-sub); padding: 10px 12px 4px;
    }
    .search-result-item {
        display: flex; align-items: center; gap: 9px; padding: 8px 12px; text-decoration: none; color: var(--clf-ink); font-size: 13.5px;
    }
    .search-result-item:hover { background: var(--clf-bg-soft); }
    .search-result-item .clf-avatar { width: 24px; height: 24px; font-size: 10.5px; }
    .search-result-item .badge-icon {
        width: 24px; height: 24px; border-radius: 6px; background: var(--clf-bg-soft); color: var(--clf-sub);
        display: flex; align-items: center; justify-content: center; font-size: 12px; flex-shrink: 0;
    }
    .search-result-meta { color: var(--clf-sub); font-size: 11.5px; }
    .search-empty { color: var(--clf-sub); font-size: 12.5px; padding: 14px; text-align: center; }

    .topbar-user {
        display: flex; align-items: center; gap: 8px; padding: 5px 9px 5px 5px; border-radius: 20px;
        cursor: pointer; margin-left: auto; border: 1px solid transparent; background: none; position: relative;
    }
    .topbar-user:hover { border-color: var(--clf-line); }
    .topbar-avatar { width: 30px; height: 30px; font-size: 12.5px; }
    .topbar-name { font-size: 13.5px; font-weight: 600; color: var(--clf-ink); white-space: nowrap; }
    .topbar-user .chevron { color: var(--clf-sub); font-size: 10px; margin-left: 2px; }

    .topbar-user-menu {
        display: none; position: absolute; top: 48px; right: 0; background: #fff; border: 1px solid var(--clf-line);
        border-radius: 8px; min-width: 170px; padding: 6px; z-index: 300;
    }
    .topbar-user-menu.open { display: block; }
    .topbar-user-menu a {
        display: flex; align-items: center; gap: 8px; padding: 8px 10px; border-radius: 6px;
        text-decoration: none; color: var(--clf-ink); font-size: 13.5px;
    }
    .topbar-user-menu a:hover { background: var(--clf-bg-soft); }
    .topbar-user-menu a.danger { color: var(--clf-accent); }
    .topbar-user-menu hr { border: none; border-top: 1px solid var(--clf-line); margin: 5px 2px; }

    @media (max-width: 640px) {
        .topbar-name { display: none; }
        .topbar-search { max-width: none; }
    }

    /* ---------- Sidebar ---------- */
    .sidebar {
        width: var(--clf-sidebar-w); flex-shrink: 0; background: #fff; border-right: 1px solid var(--clf-line);
        display: flex; flex-direction: column; padding: 18px 12px; transition: width 0.18s ease;
    }
    html:not(.sidebar-open) .sidebar { width: var(--clf-sidebar-rail); }

    .sidebar-brand {
        font-size: 17px; font-weight: 800; color: var(--clf-ink); text-decoration: none;
        padding: 0 8px; margin-bottom: 20px; white-space: nowrap; overflow: hidden;
    }
    .sidebar-brand span { color: var(--clf-accent); }
    html:not(.sidebar-open) .sidebar-brand { font-size: 0; }
    html:not(.sidebar-open) .sidebar-brand::before { content: "CL"; font-size: 15px; }

    .sidebar-nav { display: flex; flex-direction: column; gap: 2px; flex: 1; }
    .sidebar-link {
        display: flex; align-items: center; gap: 11px; padding: 9px 10px; border-radius: 6px;
        text-decoration: none; color: var(--clf-sub); font-size: 13.5px; font-weight: 500; white-space: nowrap; overflow: hidden;
    }
    .sidebar-link:hover { background: var(--clf-bg-soft); color: var(--clf-ink); }
    .sidebar-link.active { background: var(--clf-bg-soft); color: var(--clf-ink); font-weight: 700; }
    .sidebar-link i { width: 16px; text-align: center; flex-shrink: 0; color: inherit; }
    html:not(.sidebar-open) .sidebar-link .label { display: none; }
    html:not(.sidebar-open) .sidebar-link { justify-content: center; padding: 9px 0; }

    .sidebar-footer { display: flex; flex-direction: column; gap: 2px; border-top: 1px solid var(--clf-line); padding-top: 10px; margin-top: 10px; }
    .sidebar-logout { color: var(--clf-accent); }

    .sidebar-overlay {
        display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.35); z-index: 90;
    }

    @media (max-width: 900px) {
        .sidebar {
            position: fixed; top: 0; left: 0; bottom: 0; width: 230px; z-index: 100;
            transform: translateX(-100%); transition: transform 0.2s ease;
        }
        html.sidebar-open .sidebar { transform: translateX(0); width: 230px; }
        html.sidebar-open .sidebar-brand { font-size: 17px; }
        html.sidebar-open .sidebar-brand::before { content: none; }
        html.sidebar-open .sidebar-link .label { display: inline; }
        html.sidebar-open .sidebar-link { justify-content: flex-start; padding: 9px 10px; }
        html.sidebar-open .sidebar-overlay { display: block; }
    }
</style>

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
            <a href="/IPT_WEB_PROJECT/CampusLink/public/profile.php">👤 Profile</a>
            <a href="/IPT_WEB_PROJECT/CampusLink/public/settings.php">⚙️ Settings</a>
            <hr>
            <a href="/IPT_WEB_PROJECT/CampusLink/public/auth/logout.php" class="danger">↪ Log out</a>
        </div>
    </div>
</header>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<script>
(function () {
    var html = document.documentElement;
    var isDesktop = window.innerWidth > 900;
    var collapsedPref = localStorage.getItem('clSidebarCollapsed') === '1';
    if (isDesktop && !collapsedPref) html.classList.add('sidebar-open');
})();

document.addEventListener('DOMContentLoaded', () => {
    var html = document.documentElement;
    var toggleBtn = document.getElementById('sidebarToggle');
    var overlay = document.getElementById('sidebarOverlay');

    function setToggleState() {
        var isOpen = html.classList.contains('sidebar-open');
        toggleBtn.setAttribute('aria-expanded', String(isOpen));
    }
    setToggleState();

    function toggleSidebar() {
        html.classList.toggle('sidebar-open');
        var isOpen = html.classList.contains('sidebar-open');
        setToggleState();
        if (window.innerWidth > 900) {
            localStorage.setItem('clSidebarCollapsed', isOpen ? '0' : '1');
        }
    }

    toggleBtn.addEventListener('click', toggleSidebar);
    overlay.addEventListener('click', () => {
        if (window.innerWidth <= 900) {
            html.classList.remove('sidebar-open');
            setToggleState();
        }
    });

    // --- User menu dropdown ---
    var userTrigger = document.getElementById('userMenuTrigger');
    var userMenu = document.getElementById('userMenu');
    userTrigger.addEventListener('click', (e) => {
        e.stopPropagation();
        userMenu.classList.toggle('open');
    });
    document.addEventListener('click', (e) => {
        if (!userTrigger.contains(e.target)) userMenu.classList.remove('open');
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') userMenu.classList.remove('open');
    });

    // --- Global search (students, groups, documents) ---
    var searchInput = document.getElementById('globalSearchInput');
    var searchResults = document.getElementById('globalSearchResults');
    var searchTimer = null;

    function iconFor(type) {
        return type === 'group' ? '👥' : type === 'document' ? '📄' : null;
    }

    function renderSection(label, items, type) {
        if (!items.length) return '';
        var rows = items.map(item => {
            if (type === 'student') {
                var initial = item.name.charAt(0).toUpperCase();
                return `<a class="search-result-item" href="/IPT_WEB_PROJECT/CampusLink/public/profile.php?id=${item.id}">
                    <span class="clf-avatar avatar-ink-blue">${initial}</span>
                    <span>${item.name}</span>
                </a>`;
            }
            if (type === 'group') {
                return `<a class="search-result-item" href="/IPT_WEB_PROJECT/CampusLink/public/groups/view.php?id=${item.id}">
                    <span class="badge-icon">👥</span>
                    <span>${item.name}</span>
                </a>`;
            }
            return `<a class="search-result-item" href="/IPT_WEB_PROJECT/CampusLink/public/documents/download.php?id=${item.id}">
                <span class="badge-icon">📄</span>
                <span>${item.title} <span class="search-result-meta">· ${item.course_code}</span></span>
            </a>`;
        }).join('');
        return `<div class="search-group-label">${label}</div>${rows}`;
    }

    searchInput.addEventListener('input', () => {
        var query = searchInput.value.trim();
        clearTimeout(searchTimer);

        if (query.length < 2) {
            searchResults.classList.remove('open');
            searchResults.innerHTML = '';
            return;
        }

        searchTimer = setTimeout(() => {
            fetch(`/IPT_WEB_PROJECT/CampusLink/public/search.php?q=${encodeURIComponent(query)}`)
                .then(res => res.json())
                .then(data => {
                    var html = '';
                    html += renderSection('Students', data.students || [], 'student');
                    html += renderSection('Groups', data.groups || [], 'group');
                    html += renderSection('Documents', data.documents || [], 'document');

                    searchResults.innerHTML = html || '<div class="search-empty">No matches found.</div>';
                    searchResults.classList.add('open');
                });
        }, 220);
    });

    document.addEventListener('click', (e) => {
        if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
            searchResults.classList.remove('open');
        }
    });
});
</script>