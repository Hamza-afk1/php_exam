/**
 * Dark Mode Manager
 * Handles dark mode toggle, storage and system preference detection
 */
class DarkMode {
    constructor() {
        this.init();
    }
    
    init() {
        // Get elements
        this.body = document.body;
        this.darkModeToggle = document.getElementById('dark-mode-toggle');
        this.metaThemeColor = document.querySelector('meta[name="theme-color"]');
        
        // Check for saved theme preference or use device preference
        const prefersDarkMode = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
        const savedTheme = localStorage.getItem('theme');
        
        // Apply theme
        if (savedTheme === 'dark' || (!savedTheme && prefersDarkMode)) {
            this.enableDarkMode();
        }
        
        // Add event listeners
        this.addEventListeners();
    }
    
    addEventListeners() {
        // Toggle theme when button is clicked
        if (this.darkModeToggle) {
            this.darkModeToggle.addEventListener('click', () => {
                if (this.body.classList.contains('dark-mode')) {
                    this.disableDarkMode();
                } else {
                    this.enableDarkMode();
                }
            });
        }
        
        // Listen for changes in system preference
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
            if (!localStorage.getItem('theme')) {
                if (e.matches) {
                    this.enableDarkMode(false);
                } else {
                    this.disableDarkMode(false);
                }
            }
        });
    }
    
    enableDarkMode(savePreference = true) {
        this.body.classList.add('dark-mode');
        this.updateIcon(true);
        this.updateMetaThemeColor(true);
        if (savePreference) localStorage.setItem('theme', 'dark');
    }
    
    disableDarkMode(savePreference = true) {
        this.body.classList.remove('dark-mode');
        this.updateIcon(false);
        this.updateMetaThemeColor(false);
        if (savePreference) localStorage.setItem('theme', 'light');
    }
    
    updateIcon(isDarkMode) {
        if (!this.darkModeToggle) return;
        const icon = this.darkModeToggle.querySelector('i');
        if (!icon) return;
        
        if (isDarkMode) {
            icon.classList.remove('fa-moon');
            icon.classList.add('fa-sun');
        } else {
            icon.classList.remove('fa-sun');
            icon.classList.add('fa-moon');
        }
    }
    
    updateMetaThemeColor(isDarkMode) {
        if (!this.metaThemeColor) return;
        
        if (isDarkMode) {
            this.metaThemeColor.setAttribute('content', '#2C2C2E'); // Dark mode background
        } else {
            this.metaThemeColor.setAttribute('content', '#FFFFFF'); // Light mode background
        }
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new DarkMode();
}); 