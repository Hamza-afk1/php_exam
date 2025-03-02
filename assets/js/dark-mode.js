document.addEventListener('DOMContentLoaded', () => {
    const DarkModeManager = {
        STORAGE_KEY: 'exam_platform_dark_mode_preference',
        TRANSITION_CLASS: 'dark-mode-transition',
        
        // Initialize Dark Mode System
        init() {
            // Retry mechanism for DOM loading
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', this.setupDarkMode.bind(this));
            } else {
                this.setupDarkMode();
            }
        },

        // Comprehensive Setup
        setupDarkMode() {
            this.findToggleButton();
            this.bindEvents();
            this.applyStoredPreference();
        },

        // Find Toggle Button with Multiple Strategies
        findToggleButton() {
            const searchStrategies = [
                () => document.getElementById('dark-mode-toggle'),
                () => document.querySelector('[id="dark-mode-toggle"]'),
                () => document.querySelector('.dark-mode-toggle'),
                () => Array.from(document.getElementsByTagName('button'))
                    .find(btn => btn.textContent.toLowerCase().includes('dark mode'))
            ];

            for (let strategy of searchStrategies) {
                this.darkModeToggle = strategy();
                if (this.darkModeToggle) break;
            }

            // Fallback: Create a toggle button if not found
            if (!this.darkModeToggle) {
                this.createFallbackToggle();
            }
        },

        // Create Fallback Toggle Button
        createFallbackToggle() {
            this.darkModeToggle = document.createElement('button');
            this.darkModeToggle.id = 'dark-mode-toggle';
            this.darkModeToggle.className = 'btn btn-outline-light fixed-top';
            this.darkModeToggle.innerHTML = '<i class="fas fa-moon"></i> Dark Mode';
            this.darkModeToggle.style.zIndex = '9999';
            this.darkModeToggle.style.position = 'fixed';
            this.darkModeToggle.style.top = '10px';
            this.darkModeToggle.style.right = '10px';
            document.body.appendChild(this.darkModeToggle);
        },

        // Bind Events
        bindEvents() {
            if (this.darkModeToggle) {
                this.darkModeToggle.addEventListener('click', this.toggleDarkMode.bind(this));
            } else {
                console.error('Dark mode toggle button could not be found or created');
            }

            // System preference listener
            if (window.matchMedia) {
                const darkModeMediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
                darkModeMediaQuery.addListener(this.handleSystemPreferenceChange.bind(this));
            }
        },

        // Apply Stored Preference
        applyStoredPreference() {
            const savedPreference = localStorage.getItem(this.STORAGE_KEY);
            
            if (savedPreference === 'enabled') {
                this.enableDarkMode();
            } else if (savedPreference === 'disabled') {
                this.disableDarkMode();
            } else {
                this.checkSystemPreference();
            }
        },

        // Check System Preference
        checkSystemPreference() {
            if (window.matchMedia && 
                window.matchMedia('(prefers-color-scheme: dark)').matches) {
                this.enableDarkMode();
            }
        },

        // Handle System Preference Changes
        handleSystemPreferenceChange(e) {
            if (localStorage.getItem(this.STORAGE_KEY) === null) {
                e.matches ? this.enableDarkMode() : this.disableDarkMode();
            }
        },

        // Enable Dark Mode
        enableDarkMode() {
            console.log('Enabling dark mode');
            document.body.classList.add('dark-mode', this.TRANSITION_CLASS);
            localStorage.setItem(this.STORAGE_KEY, 'enabled');
            this.updateToggleIcon(true);
            this.applyDarkModeToElements();
        },

        // Disable Dark Mode
        disableDarkMode() {
            console.log('Disabling dark mode');
            document.body.classList.remove('dark-mode', this.TRANSITION_CLASS);
            localStorage.setItem(this.STORAGE_KEY, 'disabled');
            this.updateToggleIcon(false);
            this.removeDarkModeFromElements();
        },

        // Toggle Dark Mode
        toggleDarkMode() {
            console.log('Toggling dark mode');
            document.body.classList.contains('dark-mode') 
                ? this.disableDarkMode() 
                : this.enableDarkMode();
        },

        // Update Toggle Icon
        updateToggleIcon(isDarkMode) {
            if (this.darkModeToggle) {
                const icon = this.darkModeToggle.querySelector('i');
                if (icon) {
                    icon.classList.toggle('fa-moon', !isDarkMode);
                    icon.classList.toggle('fa-sun', isDarkMode);
                }
            }
        },

        // Apply Dark Mode to Elements
        applyDarkModeToElements() {
            const elementsToStyle = [
                '.sidebar', '.navbar', '.modal', '.card', 
                '.dropdown', '.form-control', '.table', '.btn',
                '.card-header', '.list-group', '.list-group-item',
                'body', 'main', '.content-wrapper', '.container', '.container-fluid'
            ];

            elementsToStyle.forEach(selector => {
                const elements = document.querySelectorAll(selector);
                elements.forEach(el => {
                    el.classList.add('dark-mode');
                    
                    // Additional styling for specific elements
                    if (el.classList.contains('card')) {
                        el.style.backgroundColor = 'var(--dark-bg-secondary)';
                        el.style.color = 'var(--dark-text-primary)';
                    }

                    if (el.classList.contains('table')) {
                        el.style.color = 'var(--dark-text-primary)';
                    }

                    // Set background for admin pages
                    if (el.classList.contains('admin-page') || el.tagName.toLowerCase() === 'body') {
                        el.style.backgroundColor = 'var(--dark-bg-primary)';
                        el.style.color = 'var(--dark-text-primary)';
                    }

                    // Set background for main content areas
                    if (['main', '.content-wrapper', '.container', '.container-fluid'].includes(selector)) {
                        el.style.backgroundColor = 'var(--dark-bg-primary)';
                        el.style.color = 'var(--dark-text-primary)';
                    }
                });
            });

            // Custom styling for specific elements
            const cardTitles = document.querySelectorAll('.card-title');
            cardTitles.forEach(title => {
                title.style.color = 'var(--dark-text-secondary)';
            });
        },

        // Remove Dark Mode from Elements
        removeDarkModeFromElements() {
            const elementsToStyle = [
                '.sidebar', '.navbar', '.modal', '.card', 
                '.dropdown', '.form-control', '.table', '.btn',
                '.card-header', '.list-group', '.list-group-item',
                'body', 'main', '.content-wrapper', '.container', '.container-fluid'
            ];

            elementsToStyle.forEach(selector => {
                const elements = document.querySelectorAll(selector);
                elements.forEach(el => {
                    el.classList.remove('dark-mode');
                    
                    // Reset inline styles
                    if (el.classList.contains('card')) {
                        el.style.backgroundColor = '';
                        el.style.color = '';
                    }

                    if (el.classList.contains('table')) {
                        el.style.color = '';
                    }

                    // Reset background for admin pages
                    if (el.classList.contains('admin-page') || el.tagName.toLowerCase() === 'body') {
                        el.style.backgroundColor = '';
                        el.style.color = '';
                    }

                    // Reset background for main content areas
                    if (['main', '.content-wrapper', '.container', '.container-fluid'].includes(selector)) {
                        el.style.backgroundColor = '';
                        el.style.color = '';
                    }
                });
            });

            // Reset card title colors
            const cardTitles = document.querySelectorAll('.card-title');
            cardTitles.forEach(title => {
                title.style.color = '';
            });
        }
    };

    // Initialize Dark Mode Manager
    DarkModeManager.init();
});
