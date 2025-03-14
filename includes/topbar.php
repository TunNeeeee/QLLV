<div class="topbar">
    <button class="btn btn-light d-md-none" id="sidebarToggle">
        <i class="fas fa-bars"></i>
    </button>
    
    <div class="d-flex align-items-center">
        <?php if (isset($notifications) && count($notifications) > 0): ?>
            <div class="dropdown me-3">
                <a href="#" class="position-relative text-dark fs-5" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-bell"></i>
                    <?php if (isset($notificationCount) && $notificationCount > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?php echo $notificationCount; ?>
                        </span>
                    <?php endif; ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-end notification-dropdown" aria-labelledby="notificationDropdown">
                    <li><h6 class="dropdown-header">Thông báo</h6></li>
                    <?php foreach ($notifications as $notification): ?>
                        <li>
                            <a class="dropdown-item notification-item" href="<?php echo BASE_URL . $role; ?>/notifications.php">
                                <div class="notification-icon">
                                    <i class="fas fa-bell"></i>
                                </div>
                                <div class="notification-content">
                                    <p class="notification-title"><?php echo $notification['TieuDe']; ?></p>
                                    <span class="notification-time"><?php echo date('d/m/Y H:i', strtotime($notification['NgayTao'])); ?></span>
                                </div>
                            </a>
                        </li>
                    <?php endforeach; ?>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-center" href="<?php echo BASE_URL . $role; ?>/notifications.php">Xem tất cả</a></li>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="dropdown">
            <a href="#" class="user-dropdown" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <?php if (!empty($userProfile['Avatar'])): ?>
                    <img src="<?php echo BASE_URL . 'uploads/avatars/' . $userProfile['Avatar']; ?>" alt="Avatar" class="user-avatar">
                <?php else: ?>
                    <img src="<?php echo BASE_URL; ?>assets/img/default-avatar.png" alt="Avatar" class="user-avatar">
                <?php endif; ?>
                <span class="user-name"><?php echo $userProfile['HoTen'] ?? $_SESSION['username']; ?></span>
                <i class="fas fa-chevron-down ms-2"></i>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                <li><a class="dropdown-item"// filepath: c:\xampp\htdocs\thesis-management-system\includes\topbar.php
<div class="topbar">
    <button class="btn btn-light d-md-none" id="sidebarToggle">
        <i class="fas fa-bars"></i>
    </button>
    
    <div class="d-flex align-items-center">
        <?php if (isset($notifications) && count($notifications) > 0): ?>
            <div class="dropdown me-3">
                <a href="#" class="position-relative text-dark fs-5" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-bell"></i>
                    <?php if (isset($notificationCount) && $notificationCount > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?php echo $notificationCount; ?>
                        </span>
                    <?php endif; ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-end notification-dropdown" aria-labelledby="notificationDropdown">
                    <li><h6 class="dropdown-header">Thông báo</h6></li>
                    <?php foreach ($notifications as $notification): ?>
                        <li>
                            <a class="dropdown-item notification-item" href="<?php echo BASE_URL . $role; ?>/notifications.php">
                                <div class="notification-icon">
                                    <i class="fas fa-bell"></i>
                                </div>
                                <div class="notification-content">
                                    <p class="notification-title"><?php echo $notification['TieuDe']; ?></p>
                                    <span class="notification-time"><?php echo date('d/m/Y H:i', strtotime($notification['NgayTao'])); ?></span>
                                </div>
                            </a>
                        </li>
                    <?php endforeach; ?>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-center" href="<?php echo BASE_URL . $role; ?>/notifications.php">Xem tất cả</a></li>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="dropdown">
            <a href="#" class="user-dropdown" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <?php if (!empty($userProfile['Avatar'])): ?>
                    <img src="<?php echo BASE_URL . 'uploads/avatars/' . $userProfile['Avatar']; ?>" alt="Avatar" class="user-avatar">
                <?php else: ?>
                    <img src="<?php echo BASE_URL; ?>assets/img/default-avatar.png" alt="Avatar" class="user-avatar">
                <?php endif; ?>
                <span class="user-name"><?php echo $userProfile['HoTen'] ?? $_SESSION['username']; ?></span>
                <i class="fas fa-chevron-down ms-2"></i>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                <li><a class="dropdown-item"