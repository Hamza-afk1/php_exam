                <!-- Page content ends here -->
            </main>
        </div>
    </div>

    <!-- jQuery and Bootstrap Bundle (includes Popper) -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        $(document).ready(function() {
            // Mobile sidebar toggle
            $('#sidebarToggler').on('click', function() {
                $('#sidebarMenu').toggleClass('show');
                $('#sidebarBackdrop').toggleClass('show');
            });
            
            // Close sidebar when clicking outside on mobile
            $('#sidebarBackdrop').on('click', function() {
                $('#sidebarMenu').removeClass('show');
                $('#sidebarBackdrop').removeClass('show');
            });
            
            // Add active class to current page link
            const currentPath = window.location.pathname;
            $('.sidebar .nav-link').each(function() {
                const linkPath = $(this).attr('href');
                if (currentPath.indexOf(linkPath.split('/').pop()) > -1) {
                    $(this).addClass('active');
                }
            });
            
            // Dark mode toggle
            const darkModeToggle = document.getElementById('dark-mode-toggle');
            const applyDarkMode = () => {
                document.body.classList.toggle('dark-mode');
                const isDarkMode = document.body.classList.contains('dark-mode');
                localStorage.setItem('darkMode', isDarkMode ? 'enabled' : 'disabled');
                
                // Update button icon
                if (isDarkMode) {
                    $('#dark-mode-toggle i').removeClass('fa-moon').addClass('fa-sun');
                } else {
                    $('#dark-mode-toggle i').removeClass('fa-sun').addClass('fa-moon');
                }
            };
            
            // Check for saved dark mode preference
            if (localStorage.getItem('darkMode') === 'enabled') {
                document.body.classList.add('dark-mode');
                $('#dark-mode-toggle i').removeClass('fa-moon').addClass('fa-sun');
            }
            
            // Dark mode button click handler
            darkModeToggle.addEventListener('click', applyDarkMode);
            
            // Initialize tooltips
            $('[data-toggle="tooltip"]').tooltip();
            
            // Animate cards on scroll
            const animateCards = () => {
                $('.card').each(function(index) {
                    const cardPosition = $(this).offset().top;
                    const scrollPosition = $(window).scrollTop();
                    const windowHeight = $(window).height();
                    
                    if (cardPosition < scrollPosition + windowHeight - 100) {
                        $(this).css({
                            'opacity': 1,
                            'transform': 'translateY(0)'
                        });
                    }
                });
            };
            
            // Set initial state
            $('.card').css({
                'opacity': 0,
                'transform': 'translateY(20px)',
                'transition': 'opacity 0.5s ease, transform 0.5s ease'
            });
            
            // Run on page load and scroll
            animateCards();
            $(window).scroll(animateCards);
        });
    </script>
</body>
</html>
