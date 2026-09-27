<?php
// includes/footer.php
?>
<footer class="site-footer bg-dark text-light mt-auto">
    <div class="container text-center py-2">
        <p class="mb-0 text-white small"><i class="fas fa-mountain-sun me-1"></i>Tour Group Management System</p>
        <small class="text-white">&copy; <?php echo date('Y'); ?> Tour Group. All rights reserved.</small>
    </div>
</footer>
<script src="<?php echo site_url('assets/js/bootstrap.bundle.min.js'); ?>"></script>
<script src="<?php echo site_url('assets/js/all.min.js'); ?>"></script>
<script src="<?php echo site_url('assets/js/main.js'); ?>"></script>
<?php if (is_logged_in()): ?>
<script src="<?php echo site_url('assets/js/session-timeout.js'); ?>"></script>
<?php endif; ?>
</body>
</html>