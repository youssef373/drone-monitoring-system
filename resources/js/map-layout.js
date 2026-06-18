/**
 * map-layout.js
 * Sidebar toggle logic shared by all map pages (layout/map.blade.php).
 */

let sidebarOpen = true;

function toggleSidebar() {
    const sidebar     = document.getElementById('sidebar');
    const mainContent = document.getElementById('main-content');

    if (sidebarOpen) {
        sidebar.style.transform      = 'translateX(-320px)';
        mainContent.style.marginLeft = '0';
    } else {
        sidebar.style.transform      = 'translateX(0)';
        mainContent.style.marginLeft = '320px';
    }

    sidebarOpen = !sidebarOpen;
}

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('sidebar-toggle')?.addEventListener('click', toggleSidebar);

    // Collapse automatically on small screens
    window.addEventListener('resize', () => {
        if (window.innerWidth < 1024 && sidebarOpen) {
            toggleSidebar();
        }
    });

    if (window.innerWidth < 1024) {
        toggleSidebar();
    }
});
