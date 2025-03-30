<?php
// Include the configuration file
require_once __DIR__ . '/config/config.php';

// Initialize session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FFFFFF">
    <meta name="color-scheme" content="light dark">
    <title>Page Not Found - <?php echo SITE_NAME; ?></title>
    
    <!-- Preload Critical Resources -->
    <link rel="preload" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" as="style">
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" as="style">
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" as="style">
    
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts - Inter (Apple-like font) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        :root {
            --bg-primary: #f5f5f7;
            --bg-secondary: #ffffff;
            --text-primary: #1d1d1f;
            --text-secondary: #86868b;
            --primary-color: #007aff;
            --primary-color-hover: #0062cc;
            --border-color: rgba(0, 0, 0, 0.1);
            --box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            --btn-shadow: 0 2px 5px rgba(0, 98, 204, 0.3);
        }
        
        body.dark-mode {
            --bg-primary: #1d1d1f;
            --bg-secondary: #2c2c2e;
            --text-primary: #f5f5f7;
            --text-secondary: #98989d;
            --border-color: #3a3a3c;
            --box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: var(--bg-primary);
            color: var(--text-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
            margin: 0;
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        
        .error-container {
            max-width: 500px;
            text-align: center;
            padding: 40px 20px;
        }
        
        .error-icon {
            font-size: 80px;
            margin-bottom: 20px;
            color: var(--primary-color);
            opacity: 0.8;
        }
        
        .error-title {
            font-size: 2.5rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--text-primary);
        }
        
        .error-message {
            font-size: 1.1rem;
            color: var(--text-secondary);
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-color-hover);
            border-color: var(--primary-color-hover);
            transform: translateY(-1px);
            box-shadow: var(--btn-shadow);
        }
        
        .dark-mode-toggle {
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: var(--box-shadow);
            color: var(--text-primary);
        }
        
        .dark-mode-toggle:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        /* Loading Animation */
        .loading {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: var(--bg-primary);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            transition: opacity 0.5s ease, visibility 0.5s ease;
        }
        
        .loading.hidden {
            opacity: 0;
            visibility: hidden;
        }
        
        .spinner {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 3px solid rgba(0, 122, 255, 0.1);
            border-top-color: var(--primary-color);
            animation: spin 1s infinite linear;
        }
        
        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
    </style>
</head>
<body>
    <!-- Loading Animation -->
    <div class="loading">
        <div class="spinner"></div>
    </div>
    
    <!-- Dark Mode Toggle -->
    <button id="dark-mode-toggle" class="dark-mode-toggle" aria-label="Toggle dark mode">
        <i class="fas fa-moon"></i>
    </button>
    
    <div class="error-container">
        <div class="error-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h1 class="error-title">404</h1>
        <p class="error-message">
            We couldn't find the page you're looking for. It might have been moved or doesn't exist.
        </p>
        <div class="d-flex justify-content-center">
            <a href="<?php echo BASE_URL; ?>" class="btn btn-primary mr-2">
                <i class="fas fa-home"></i> Go Home
            </a>
            <a href="javascript:history.back()" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Go Back
            </a>
        </div>
    </div>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <!-- Dark Mode JavaScript -->
    <script>
        // Dark Mode Manager
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
                    this.metaThemeColor.setAttribute('content', '#1d1d1f');
                } else {
                    this.metaThemeColor.setAttribute('content', '#FFFFFF');
                }
            }
        }

        // Initialize when DOM is loaded
        document.addEventListener('DOMContentLoaded', () => {
            new DarkMode();
            
            // Loading animation
            const loading = document.querySelector('.loading');
            if (loading) {
                loading.classList.add('hidden');
                setTimeout(() => {
                    loading.style.display = 'none';
                }, 500);
            }
        });
    </script>
</body>
</html> 