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
                return `<a class="search-result-item" href="/CL_DEV/CampusLink/public/profile.php?id=${item.id}">
                    <span class="clf-avatar avatar-ink-blue">${initial}</span>
                    <span>${item.name}</span>
                </a>`;
            }
            if (type === 'group') {
                return `<a class="search-result-item" href="/CL_DEV/CampusLink/public/groups/view.php?id=${item.id}">
                    <span class="badge-icon">👥</span>
                    <span>${item.name}</span>
                </a>`;
            }
            return `<a class="search-result-item" href="/CL_DEV/CampusLink/public/documents/download.php?id=${item.id}">
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
            fetch(`/CL_DEV/CampusLink/public/search.php?q=${encodeURIComponent(query)}`)
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