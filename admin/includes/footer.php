            </main>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="../assets/js/dark-mode.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const darkModeToggle = document.getElementById('dark-mode-toggle');
        
        if (darkModeToggle) {
            // Initial state check
            const savedPreference = localStorage.getItem('exam_platform_dark_mode_preference');
            if (savedPreference === 'enabled') {
                document.body.classList.add('dark-mode');
                updateToggleIcon(true);
            }

            // Toggle event listener
            darkModeToggle.addEventListener('click', function() {
                const isDarkMode = document.body.classList.toggle('dark-mode');
                updateToggleIcon(isDarkMode);
                
                // Store preference
                localStorage.setItem('exam_platform_dark_mode_preference', 
                    isDarkMode ? 'enabled' : 'disabled');

                // Apply dark mode to specific elements
                const elementsToStyle = [
                    '.sidebar', '.navbar', '.modal', '.card', 
                    '.dropdown', '.form-control', '.table', '.btn'
                ];
                elementsToStyle.forEach(selector => {
                    const elements = document.querySelectorAll(selector);
                    elements.forEach(el => el.classList.toggle('dark-mode', isDarkMode));
                });
            });

            function updateToggleIcon(isDarkMode) {
                const icon = darkModeToggle.querySelector('i');
                if (icon) {
                    icon.classList.toggle('fa-moon', !isDarkMode);
                    icon.classList.toggle('fa-sun', isDarkMode);
                }
            }
        }
    });
    </script>
</body>
</html>
