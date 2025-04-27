const sidebar = document.getElementById('sidebar');
const sidebarMenu = document.getElementById('sidebarMenu');
const sidebarLinks = sidebarMenu.querySelectorAll('.nav-link');
const logo = document.getElementById('sidebarLogo');
const userName = document.getElementById('userName');
const toggleButton = document.getElementById('sidebarToggle');

// Toggle button click
toggleButton.addEventListener('click', function() {
    // Only allow toggle if screen width is <= 768px
    if (window.innerWidth <= 768) {
        sidebar.classList.toggle('collapsed');
        
        if (sidebar.classList.contains('collapsed')) {
            sidebar.style.width = '80px';
            sidebarMenu.style.display = 'block';

            // Hide text but keep icons visible
            sidebarLinks.forEach(link => {
                const text = link.querySelector('span');
                if (text) text.style.display = 'none';
            });

            if (logo) logo.style.display = 'none';
            if (userName) userName.style.display = 'none';

            toggleButton.style.marginLeft = '0';
        } else {
            sidebar.style.width = '280px';
            sidebarMenu.style.display = 'block';

            sidebarLinks.forEach(link => {
                const text = link.querySelector('span');
                if (text) text.style.display = 'inline';
            });

            if (logo) logo.style.display = 'block';
            if (userName) userName.style.display = 'block';

            toggleButton.style.marginLeft = '3.25rem';
        }
    }
});

// Auto open sidebar on wider screens
function adjustSidebar() {
    if (window.innerWidth > 768) {
        sidebar.classList.remove('collapsed');
        sidebar.style.width = '280px';
        sidebarMenu.style.display = 'block';

        sidebarLinks.forEach(link => {
            const text = link.querySelector('span');
            if (text) text.style.display = 'inline';
        });

        if (logo) logo.style.display = 'block';
        if (userName) userName.style.display = 'block';

        toggleButton.style.marginLeft = '3.25rem';
    }
}

// Run adjustSidebar on page load
window.addEventListener('load', adjustSidebar);

// Run adjustSidebar when window resizes
window.addEventListener('resize', adjustSidebar);
