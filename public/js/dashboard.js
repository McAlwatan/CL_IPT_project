document.addEventListener('DOMContentLoaded', () => {
    const appShell = document.querySelector('.app-shell');
    const menuToggleBtn = document.getElementById('menuToggle');

    if (menuToggleBtn) {
        menuToggleBtn.addEventListener('click', () => {
            if (window.innerWidth > 768) {
                appShell.classList.toggle('sidebar-collapsed');
            } else {
                appShell.classList.toggle('mobile-sidebar-open');
            }
        });
    }
});
