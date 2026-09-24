<?php
// includes/footer.php
?>
<footer class="bg-dark text-light mt-5 py-4">
    <div class="container text-center">
        <p class="mb-1"><i class="fas fa-mountain-sun me-2"></i>Tour Group Management System</p>
        <small class="text-muted">&copy; <?php echo date('Y'); ?> Tour Group. All rights reserved.</small>
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
