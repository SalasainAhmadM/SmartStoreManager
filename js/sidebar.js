const sidebar = document.getElementById('sidebar');
const sidebarMenu = document.getElementById('sidebarMenu');
const sidebarLinks = sidebarMenu.querySelectorAll('.nav-link');
const logo = document.getElementById('sidebarLogo');
const userName = document.getElementById('userName');
const toggleButton = document.getElementById('sidebarToggle');

// Function to expand sidebar
function expandSidebar() {
    sidebar.classList.remove('collapsed');
    sidebar.style.width = '280px';
    sidebarMenu.style.display = 'block';

    sidebarLinks.forEach(link => {
        const text = link.querySelector('span');
        if (text) text.style.display = 'inline';
    });

    if (logo) logo.style.display = 'block';
    if (userName) userName.style.display = 'block';

    toggleButton.style.marginLeft = '5rem';
}

// Function to collapse sidebar
function collapseSidebar() {
    sidebar.classList.add('collapsed');
    sidebar.style.width = '80px';
    sidebarMenu.style.display = 'block';

    sidebarLinks.forEach(link => {
        const text = link.querySelector('span');
        if (text) text.style.display = 'none';
    });

    if (logo) logo.style.display = 'none';
    if (userName) userName.style.display = 'none';

    toggleButton.style.marginLeft = '0';
}

// Sidebar toggle on button click
toggleButton.addEventListener('click', function() {
    if (window.innerWidth <= 768) {
        if (sidebar.classList.contains('collapsed')) {
            expandSidebar();
        } else {
            collapseSidebar();
        }
    }
});

// Auto adjust sidebar when window resizes
window.addEventListener('resize', function() {
    if (window.innerWidth > 768) {
        expandSidebar();  // Auto open on wider screen
    } else {
        collapseSidebar();  // Collapse again on small screen
    }
});

// Initial check on page load
if (window.innerWidth > 768) {
    expandSidebar();
} else {
    collapseSidebar();
}
