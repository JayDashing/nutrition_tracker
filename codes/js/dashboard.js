        // Toggle Sidebar for Mobile View
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            if (sidebar.style.width === '250px') {
                sidebar.style.width = '0';
            } else {
                sidebar.style.width = '250px';
            }
        }

        // Dark Mode Toggle
        const darkModeToggle = document.querySelector('.toggle-switch input');
        const darkModeText = document.querySelector('.mode-toggle span');

        darkModeToggle.addEventListener('change', () => {
            document.body.classList.toggle('dark-mode');
            
            // Change the text based on the toggle state
            if (darkModeToggle.checked) {
                darkModeText.textContent = 'Dark Mode';
            } else {
                darkModeText.textContent = 'Light Mode';
            }
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (e) => {
            const sidebar = document.getElementById('sidebar');
            const menuIcon = document.querySelector('.menu-icon');
            
            if (window.innerWidth <= 576) {
                if (!sidebar.contains(e.target) && !menuIcon.contains(e.target) && sidebar.style.width === '250px') {
                    sidebar.style.width = '0';
                }
            }
        });