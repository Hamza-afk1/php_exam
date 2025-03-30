document.addEventListener('DOMContentLoaded', function() {
    const darkModeToggle = document.getElementById('dark-mode-toggle');
    
    if (darkModeToggle) {
        // Initial state check
        const savedPreference = localStorage.getItem('exam_platform_dark_mode_preference');
        if (savedPreference === 'enabled') {
            document.body.classList.add('dark-mode');
            document.documentElement.setAttribute('data-theme', 'dark');
            updateToggleIcon(true);
        }

        // Toggle event listener
        darkModeToggle.addEventListener('click', function() {
            const isDarkMode = document.body.classList.toggle('dark-mode');
            document.documentElement.setAttribute('data-theme', isDarkMode ? 'dark' : 'light');
            updateToggleIcon(isDarkMode);
            
            // Store preference
            localStorage.setItem('exam_platform_dark_mode_preference', 
                isDarkMode ? 'enabled' : 'disabled');
        });
    }
});

function updateToggleIcon(isDarkMode) {
    const icon = document.querySelector('#dark-mode-toggle i');
    if (icon) {
        icon.classList.toggle('fa-moon', !isDarkMode);
        icon.classList.toggle('fa-sun', isDarkMode);
    }
}
