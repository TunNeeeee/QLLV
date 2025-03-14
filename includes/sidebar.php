<div class="col-md-3 col-lg-2 d-md-block sidebar collapse">
    <div class="sidebar-header">
        <a href="<?php echo BASE_URL; ?>" class="sidebar-brand"><?php echo SITE_NAME; ?></a>
    </div>
    <ul class="sidebar-menu">
        <li class="sidebar-item">
            <a href="<?php echo BASE_URL . $role; ?>/dashboard.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt sidebar-icon"></i>
                Bảng điều khiển
            </a>
        </li>
        
        <?php if ($role == 'student'): ?>
            <li class="sidebar-item">
                <a href="<?php echo BASE_URL . $role; ?>/thesis.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'thesis.php' ? 'active' : ''; ?>">
                    <i class="fas fa-book sidebar-icon"></i>
                    Luận văn
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?php echo BASE_URL . $role; ?>/appointments.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'appointments.php' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-alt sidebar-icon"></i>
                    Lịch gặp
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?php echo BASE_URL . $role; ?>/documents.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'documents.php' ? 'active' : ''; ?>">
                    <i class="fas fa-file-alt sidebar-icon"></i>
                    Tài liệu
                </a>
            </li>
        <?php elseif ($role == 'faculty'): ?>
            <li class="sidebar-item">
                <a href="<?php echo BASE_URL . $role; ?>/thesis.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'thesis.php' ? 'active' : ''; ?>">
                    <i class="fas fa-book sidebar-icon"></i>
                    Quản lý luận văn
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?php echo BASE_URL . $role; ?>/students.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'students.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user-graduate sidebar-icon"></i>
                    Sinh viên
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?php echo BASE_URL . $role; ?>/appointments.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'appointments.php' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-alt sidebar-icon"></i>
                    Lịch hẹn
                </a>
            </li>
        <?php elseif ($role == 'admin'): ?>
            <li class="sidebar-item">
                <a href="<?php echo BASE_URL . $role; ?>/users.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users sidebar-icon"></i>
                    Quản lý người dùng
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?php echo BASE_URL . $role; ?>/thesis.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'thesis.php' ? 'active' : ''; ?>">
                    <i class="fas fa-book sidebar-icon"></i>
                    Quản lý luận văn
                </a>
            </li>
            <li class="sidebar-item">
                <a href="<?php echo BASE_URL . $role; ?>/departments.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'departments.php' ? 'active' : ''; ?>">
                    <i class="fas fa-university sidebar-icon"></i>
                    Quản lý khoa
                </a>
            </li>
        <?php endif; ?>
        
        <li class="sidebar-item">
            <a href="<?php echo BASE_URL . $role; ?>/notifications.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'notifications.php' ? 'active' : ''; ?>">
                <i class="fas fa-bell sidebar-icon"></i>
                Thông báo
                <?php if (isset($notificationCount) && $notificationCount > 0): ?>
                    <span class="badge bg-danger ms-auto"><?php echo $notificationCount; ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="<?php echo BASE_URL . $role; ?>/profile.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
                <i class="fas fa-user sidebar-icon"></i>
                Hồ sơ
            </a>
        </li>
        <li class="sidebar-item">
            <a href="<?php echo BASE_URL; ?>auth/logout.php" class="sidebar-link">
                <i class="fas fa-sign-out-alt sidebar-icon"></i>
                Đăng xuất
            </a>
        </li>
    </ul>
</div>