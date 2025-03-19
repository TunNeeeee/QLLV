<?php
// Check if the user is logged in and has the role of faculty
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Get current page for active highlighting
$currentPage = basename($_SERVER['PHP_SELF']);

// Get faculty information
$facultyID = $_SESSION['user_id'] ?? 0;
$facultyName = $_SESSION['name'] ?? 'Giảng viên';
$facultyAvatar = $_SESSION['avatar'] ?? '';

// Define base URL
if (!defined('BASE_URL')) {
    define('BASE_URL', '/thesis-management-system');
}
?>

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
    <div class="container-fluid px-4">
        <a class="navbar-brand d-flex align-items-center" href="<?php echo BASE_URL; ?>/faculty/dashboard.php">
            <img src="<?php echo BASE_URL; ?>/assets/images/logo.png" alt="Logo" height="40" class="me-2">
            <span class="d-none d-lg-inline fw-bold text-primary">Quản lý Luận văn</span>
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#facultyNavbar" 
                aria-controls="facultyNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="facultyNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage == 'dashboard.php') ? 'active fw-bold' : ''; ?>" 
                       href="<?php echo BASE_URL; ?>/faculty/dashboard.php">
                        <i class="fas fa-home me-1"></i> Trang chủ
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage == 'thesis-list.php') ? 'active fw-bold' : ''; ?>" 
                       href="<?php echo BASE_URL; ?>/faculty/thesis-list.php">
                        <i class="fas fa-clipboard-list me-1"></i> Đề tài
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (in_array($currentPage, ['assigned-students.php', 'assign-thesis.php'])) ? 'active fw-bold' : ''; ?>" 
                       href="<?php echo BASE_URL; ?>/faculty/assigned-students.php">
                        <i class="fas fa-user-graduate me-1"></i> Sinh viên
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage == 'appointments.php') ? 'active fw-bold' : ''; ?>" 
                       href="<?php echo BASE_URL; ?>/faculty/appointments.php">
                        <i class="fas fa-calendar-alt me-1"></i> Lịch gặp
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage == 'messages.php') ? 'active fw-bold' : ''; ?>" 
                       href="<?php echo BASE_URL; ?>/faculty/messages.php">
                        <i class="fas fa-comments me-1"></i> Tin nhắn
                        <span class="badge rounded-pill bg-danger ms-1">2</span>
                    </a>
                </li>
            </ul>
            
            <div class="d-flex align-items-center">
                <div class="dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown" 
                       role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="avatar-sm me-2 rounded-circle bg-primary d-flex align-items-center justify-content-center">
                            <?php if (!empty($facultyAvatar)): ?>
                                <img src="<?php echo $facultyAvatar; ?>" alt="Avatar" class="rounded-circle" width="32" height="32">
                            <?php else: ?>
                                <span class="text-white"><?php echo substr($facultyName, 0, 1); ?></span>
                            <?php endif; ?>
                        </div>
                        <span class="d-none d-md-inline"><?php echo $facultyName; ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" aria-labelledby="navbarDropdown">
                        <li>
                            <a class="dropdown-item" href="<?php echo BASE_URL; ?>/faculty/profile.php">
                                <i class="fas fa-user me-2 text-primary"></i> Hồ sơ
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="<?php echo BASE_URL; ?>/faculty/settings.php">
                                <i class="fas fa-cog me-2 text-primary"></i> Cài đặt
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="<?php echo BASE_URL; ?>/logout.php">
                                <i class="fas fa-sign-out-alt me-2 text-danger"></i> Đăng xuất
                            </a>
                        </li>
                    </ul>
                </div>
                
                <div class="ms-3 position-relative">
                    <a href="<?php echo BASE_URL; ?>/faculty/notifications.php" class="nav-link">
                        <i class="fas fa-bell fs-5"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            3
                            <span class="visually-hidden">unread notifications</span>
                        </span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</nav>

<style>
.avatar-sm {
    width: 32px;
    height: 32px;
}

.navbar .nav-link {
    color: #495057;
    padding: 0.5rem 1rem;
    border-radius: 0.25rem;
    transition: all 0.3s ease;
}

.navbar .nav-link:hover {
    color: var(--bs-primary);
    background-color: rgba(var(--bs-primary-rgb), 0.1);
}

.navbar .nav-link.active {
    color: var(--bs-primary);
    background-color: rgba(var(--bs-primary-rgb), 0.1);
}

@media (max-width: 992px) {
    .navbar .navbar-nav {
        padding-top: 1rem;
    }
    
    .navbar .nav-link {
        padding: 0.7rem 1rem;
    }
    
    .navbar .dropdown-menu {
        border: none;
        box-shadow: none;
        padding-left: 1rem;
    }
}
</style>