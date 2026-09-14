document.addEventListener('DOMContentLoaded', function () {
    var toggle = document.getElementById('sidebarToggle');
    var shell = document.querySelector('.app-shell');
    if (!toggle || !shell) return;

    var backdrop = null;

    function isMobile() {
        return window.matchMedia('(max-width: 900px)').matches;
    }

    function openMobile() {
        shell.classList.add('sidebar-open');
        toggle.setAttribute('aria-expanded', 'true');
        backdrop = document.createElement('div');
        backdrop.className = 'sidebar-backdrop';
        backdrop.addEventListener('click', closeMobile);
        document.body.appendChild(backdrop);
    }

    function closeMobile() {
        shell.classList.remove('sidebar-open');
        toggle.setAttribute('aria-expanded', 'false');
        if (backdrop) {
            backdrop.remove();
            backdrop = null;
        }
    }

    toggle.addEventListener('click', function () {
        if (isMobile()) {
            if (shell.classList.contains('sidebar-open')) {
                closeMobile();
            } else {
                openMobile();
            }
        } else {
            var collapsed = shell.classList.toggle('sidebar-collapsed');
            toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        }
    });
    window.addEventListener('resize', function () {
        if (!isMobile()) closeMobile();
    });
});