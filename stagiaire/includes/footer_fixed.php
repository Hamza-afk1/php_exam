        </div><!-- End of main content -->
    </div><!-- End of page container -->

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        $(document).ready(function() {
            // Toggle sidebar on mobile
            $('#sidebarToggle').click(function(e) {
                e.preventDefault();
                $('#sidebar').toggleClass('show');
                $('body').toggleClass('sidebar-visible');
            });
            
            // Toggle user dropdown menu
            $('#userDropdown').click(function(e) {
                e.preventDefault();
                $('#userMenu').toggle();
            });
            
            // Close dropdown when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('#userDropdown').length) {
                    $('#userMenu').hide();
                }
                
                // Close sidebar when clicking outside on mobile
                if ($(window).width() <= 768) {
                    if (!$(e.target).closest('#sidebar').length && 
                        !$(e.target).closest('#sidebarToggle').length && 
                        $('#sidebar').hasClass('show')) {
                        $('#sidebar').removeClass('show');
                        $('body').removeClass('sidebar-visible');
                    }
                }
            });
            
            // Theme toggle functionality
            $('#themeToggle').click(function() {
                $('body').toggleClass('dark-mode');
                
                // Change icon based on theme
                if ($('body').hasClass('dark-mode')) {
                    $(this).find('i').removeClass('fa-moon').addClass('fa-sun');
                    localStorage.setItem('theme', 'dark');
                } else {
                    $(this).find('i').removeClass('fa-sun').addClass('fa-moon');
                    localStorage.setItem('theme', 'light');
                }
            });
            
            // Check saved theme preference
            if (localStorage.getItem('theme') === 'dark') {
                $('body').addClass('dark-mode');
                $('#themeToggle').find('i').removeClass('fa-moon').addClass('fa-sun');
            }
            
            // Adjust layout on window resize
            $(window).resize(function() {
                if ($(window).width() > 768) {
                    $('#sidebar').removeClass('show');
                    $('body').removeClass('sidebar-visible');
                }
            });
        });
    </script>
</body>
</html> 