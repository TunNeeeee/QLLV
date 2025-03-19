<?php
// Đảm bảo biến $currentPage đã được định nghĩa
$currentPage = $currentPage ?? '';
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?php echo BASE_URL; ?>student/dashboard.php">
            <i class="fas fa-graduation-cap me-2"></i>
            <?php echo SITE_NAME; ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage == 'dashboard' ? 'active' : ''; ?>" 
                       href="<?php echo BASE_URL; ?>student/dashboard.php">
                        <i class="fas fa-tachometer-alt me-1"></i> Trang chủ
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage == 'thesis' ? 'active' : ''; ?>" 
                       href="<?php echo BASE_URL; ?>student/thesis.php">
                        <i class="fas fa-book me-1"></i> Luận văn
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage == 'appointments' ? 'active' : ''; ?>" 
                       href="<?php echo BASE_URL; ?>student/appointments.php">
                        <i class="fas fa-calendar-alt me-1"></i> Lịch gặp
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage == 'progress' ? 'active' : ''; ?>" 
                       href="<?php echo BASE_URL; ?>student/progress.php">
                        <i class="fas fa-tasks me-1"></i> Tiến độ
                    </a>
                </li>
            </ul>
            
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="notificationsDropdown" role="button"
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bell me-1"></i>
                        Thông báo
                        <?php if (isset($unreadNotificationsCount) && $unreadNotificationsCount > 0): ?>
                            <span class="badge bg-danger"><?php echo $unreadNotificationsCount; ?></span>
                        <?php endif; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationsDropdown">
                        <li><h6 class="dropdown-header">Thông báo mới</h6></li>
                        <?php if (isset($recentNotifications) && !empty($recentNotifications)): ?>
                            <?php foreach ($recentNotifications as $notification): ?>
                                <li>
                                    <a class="dropdown-item" href="<?php echo BASE_URL . $notification['LienKet']; ?>">
                                        <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($notification['NgayTao'])); ?></small>
                                        <br>
                                        <?php echo htmlspecialchars($notification['NoiDung']); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-center" href="<?php echo BASE_URL; ?>student/notifications.php">
                                    Xem tất cả
                                </a>
                            </li>
                        <?php else: ?>
                            <li><a class="dropdown-item text-muted" href="#">Không có thông báo mới</a></li>
                        <?php endif; ?>
                    </ul>
                </li>
                
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user-circle me-1"></i>
                        <?php echo isset($studentInfo['HoTen']) ? htmlspecialchars($studentInfo['HoTen']) : 'Tài khoản'; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li>
                            <a class="dropdown-item" href="<?php echo BASE_URL; ?>student/profile.php">
                                <i class="fas fa-user me-1"></i> Hồ sơ
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="<?php echo BASE_URL; ?>student/change-password.php">
                                <i class="fas fa-key me-1"></i> Đổi mật khẩu
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="<?php echo BASE_URL; ?>auth/logout.php">
                                <i class="fas fa-sign-out-alt me-1"></i> Đăng xuất
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav> 