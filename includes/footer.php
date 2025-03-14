<?php
// footer.php - Footer section of the HTML for all pages
?>
</main>
<footer class="footer mt-auto py-3">
    <div class="container">
        <div class="row">
            <div class="col-md-6 mb-3 mb-md-0">
                <h5><?php echo SITE_NAME; ?></h5>
                <p class="text-muted mb-0">Hệ thống quản lý hướng dẫn luận văn hiệu quả dành cho sinh viên và giảng viên.</p>
            </div>
            <div class="col-md-3 mb-3 mb-md-0">
                <h5>Liên kết</h5>
                <ul class="list-unstyled">
                    <li><a href="<?php echo BASE_URL; ?>">Trang chủ</a></li>
                    <li><a href="<?php echo BASE_URL; ?>about.php">Giới thiệu</a></li>
                    <li><a href="<?php echo BASE_URL; ?>contact.php">Liên hệ</a></li>
                    <li><a href="<?php echo BASE_URL; ?>faq.php">Hỏi đáp</a></li>
                </ul>
            </div>
            <div class="col-md-3">
                <h5>Kết nối</h5>
                <div class="social-icons">
                    <a href="#" class="me-3"><i class="fab fa-facebook-f fa-lg"></i></a>
                    <a href="#" class="me-3"><i class="fab fa-twitter fa-lg"></i></a>
                    <a href="#" class="me-3"><i class="fab fa-instagram fa-lg"></i></a>
                    <a href="#" class="me-3"><i class="fab fa-linkedin-in fa-lg"></i></a>
                </div>
            </div>
        </div>
        <hr class="my-3">
        <div class="text-center">
            <small>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</small>
        </div>
    </div>
</footer>

<!-- Bootstrap Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<?php if (isset($extraJS)): ?>
    <?php foreach ($extraJS as $js): ?>
        <script src="<?php echo BASE_URL . 'assets/js/' . $js . '.js'; ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>
</body>
</html>