document.addEventListener('DOMContentLoaded', () => {
    const appShell = document.querySelector('.app-shell');
    const menuToggleBtn = document.getElementById('menuToggle');

    if (menuToggleBtn) {
        menuToggleBtn.addEventListener('click', () => {
            // Checks viewport width dynamically
            if (window.innerWidth > 768) {
                // Desktop: Retract/collapse sidebar width down
                appShell.classList.toggle('sidebar-collapsed');
            } else {
                // Mobile: Slid open the drawer overlay from the left
                appShell.classList.toggle('mobile-sidebar-open');
            }
        });
    }
});
