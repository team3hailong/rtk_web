<script>
        // Basic Sidebar Toggle for Mobile
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        const hamburger = document.getElementById('hamburger-btn');

        function toggleSidebar() {
            sidebar.classList.toggle('open');
            overlay.classList.toggle('open');
            // Optional: Hide hamburger when sidebar is open
            if (hamburger) {
                 hamburger.style.visibility = sidebar.classList.contains('open') ? 'hidden' : 'visible';
            }
        }

         // Close sidebar if window is resized from mobile to desktop
         window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                if (sidebar && sidebar.classList.contains('open')) {
                    sidebar.classList.remove('open');
                    overlay.classList.remove('open');
                     if (hamburger) hamburger.style.visibility = 'visible';
                }
            }
        });    </script>
      <?php if (isset($_SESSION['user_id'])): ?>
    <!-- Session Activity Tracker - Chỉ tải khi người dùng đã đăng nhập -->
    <script src="<?php echo $base_url ?? ''; ?>/public/assets/js/session_tracker.js"></script>
    <?php endif; ?>
</body>
</html>