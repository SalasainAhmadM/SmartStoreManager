document.getElementById('sidebarToggle').addEventListener('click', function() {
    const sidebar = document.getElementById('sidebar');
    const sidebarMenu = document.getElementById('sidebarMenu');
    const sidebarLinks = sidebarMenu.querySelectorAll('.nav-link');
    const logo = document.getElementById('sidebarLogo');
    const userName = document.getElementById('userName');
    const toggleButton = document.getElementById('sidebarToggle');

    sidebar.classList.toggle('collapsed');
    
    if (sidebar.classList.contains('collapsed')) {
        sidebar.style.width = '80px';
        sidebarMenu.style.display = 'block';

        // Hide text but keep icons visible in the nav links
        sidebarLinks.forEach(link => {
            const text = link.querySelector('span');
            if (text) text.style.display = 'none';  // Hide the text
        });

        // Hide logo and user name
        if (logo) logo.style.display = 'none';
        if (userName) userName.style.display = 'none';

        // Adjust margin-left of the toggle button when sidebar is collapsed
        toggleButton.style.marginLeft = '0';  // Set margin-left to 0
    } else {
        sidebar.style.width = '280px';
        sidebarMenu.style.display = 'block';

        // Show text again
        sidebarLinks.forEach(link => {
            const text = link.querySelector('span');
            if (text) text.style.display = 'inline';  // Show the text
        });

        // Show logo and user name
        if (logo) logo.style.display = 'block';
        if (userName) userName.style.display = 'block';

        // Reset margin-left of the toggle button when sidebar is expanded
        toggleButton.style.marginLeft = '5rem';  // Reset to original margin
    }
});
