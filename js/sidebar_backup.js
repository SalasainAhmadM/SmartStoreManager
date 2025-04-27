document.getElementById('sidebarToggle').addEventListener('click', function () {
    const sidebar = document.getElementById('sidebar');
    const sidebarMenu = document.getElementById('sidebarMenu');
    const sidebarLinks = sidebarMenu.querySelectorAll('.nav-link');
    const logos = document.querySelectorAll('#sidebarLogo');
    const userName = document.getElementById('userName');
    const toggleButton = document.getElementById('sidebarToggle');

    // Only allow collapsing/expanding if screen width is small (e.g., < 768px)
    if (window.innerWidth <= 768) {
        sidebar.classList.toggle('collapsed');

        if (sidebar.classList.contains('collapsed')) {
            sidebar.style.width = '80px';
            sidebarMenu.style.display = 'block';

            // Hide text but keep icons visible in nav links
            sidebarLinks.forEach(link => {
                const text = link.querySelector('span');
                if (text) text.style.display = 'none';
            });

            // Hide all logos and user name
            logos.forEach(logo => logo.style.display = 'none');
            if (userName) userName.style.display = 'none';

            toggleButton.style.marginLeft = '0';
        } else {
            sidebar.style.width = '280px';
            sidebarMenu.style.display = 'block';

            // Show text again
            sidebarLinks.forEach(link => {
                const text = link.querySelector('span');
                if (text) text.style.display = 'inline';
            });

            logos.forEach(logo => logo.style.display = 'block');
            if (userName) userName.style.display = 'block';

            toggleButton.style.marginLeft = '5rem';
        }
    }
});

// On page load, make sure sidebar is open by default on large screens
window.addEventListener('load', function () {
    const sidebar = document.getElementById('sidebar');
    const sidebarMenu = document.getElementById('sidebarMenu');
    const sidebarLinks = sidebarMenu.querySelectorAll('.nav-link');
    const logos = document.querySelectorAll('#sidebarLogo');
    const userName = document.getElementById('userName');

    if (window.innerWidth > 768) {
        sidebar.classList.remove('collapsed');
        sidebar.style.width = '280px';
        sidebarMenu.style.display = 'block';

        sidebarLinks.forEach(link => {
            const text = link.querySelector('span');
            if (text) text.style.display = 'inline';
        });

        logos.forEach(logo => logo.style.display = 'block');
        if (userName) userName.style.display = 'block';
    }
});
