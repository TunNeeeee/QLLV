<nav class="sidebar">
    <div class="sidebar-header p-3">
        <a href="dashboard.php" class="text-decoration-none text-white">
            <h4><i class="fas fa-graduation-cap me-2"></i>Quản lý luận văn</h4>
        </a>
    </div>
    
    <div class="px-3 py-2 d-flex align-items-center border-bottom border-light-subtle">
        <div class="sidebar-user-img rounded-circle bg-white text-primary d-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px; font-size: 20px; font-weight: 600;">
            <?php echo strtoupper(substr($_SESSION['name'] ?? 'U', 0, 1)); ?>
        </div>
        <div>
            <div class="sidebar-user-name"><?php echo $_SESSION['name'] ?? 'Sinh viên'; ?></div>
            <div class="sidebar-user-role">Sinh viên</div>
        </div>
    </div>
    
    <ul class="sidebar-menu">
        <li class="px-3 py-2">
            <a href="dashboard.php" class="text-decoration-none text-white d-flex align-items-center <?php echo $currentPage == 'dashboard' ? 'fw-bold' : 'text-white-50'; ?>">
                <i class="fas fa-tachometer-alt me-2"></i> Trang chủ
            </a>
        </li>
        <li class="px-3 py-2">
            <a href="thesis.php" class="text-decoration-none text-white d-flex align-items-center <?php echo $currentPage == 'thesis' ? 'fw-bold' : 'text-white-50'; ?>">
                <i class="fas fa-book me-2"></i> Luận văn của tôi
            </a>
        </li>
        <li class="px-3 py-2">
            <a href="profile.php" class="text-decoration-none text-white d-flex align-items-center <?php echo $currentPage == 'profile' ? 'fw-bold' : 'text-white-50'; ?>">
                <i class="fas fa-user me-2"></i> Hồ sơ
            </a>
        </li>
        <li class="px-3 py-2">
            <a href="meetings.php" class="text-decoration-none text-white d-flex align-items-center <?php echo $currentPage == 'meetings' ? 'fw-bold' : 'text-white-50'; ?>">
                <i class="fas fa-calendar-alt me-2"></i> Lịch gặp
            </a>
        </li>
        <li class="px-3 py-2">
            <a href="../auth/logout.php" class="text-decoration-none text-white-50 d-flex align-items-center">
                <i class="fas fa-sign-out-alt me-2"></i> Đăng xuất
            </a>
        </li>
    </ul>
</nav>