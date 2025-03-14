<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?><?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <?php if (isset($extraCSS)): ?>
        <?php foreach ($extraCSS as $css): ?>
            <link rel="stylesheet" href="<?php echo BASE_URL . 'assets/css/' . $css . '.css'; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <header class="main-header">
        <nav class="navbar navbar-expand-lg navbar-dark">
            <div class="container">
                <a class="navbar-brand" href="<?php echo BASE_URL; ?>">
                    <i class="fas fa-graduation-cap me-2"></i>
                    <?php echo SITE_NAME; ?>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarMain">
                    <ul class="navbar-nav ms-auto">
                        <?php if(isset($_SESSION['user_id'])): ?>
                            <?php if($_SESSION['role'] === 'student'): ?>
                                <li class="nav-item">
                                    <a class="nav-link<?php echo ($activePage == 'dashboard') ? ' active' : ''; ?>" href="<?php echo BASE_URL; ?>student/dashboard.php">
                                        <i class="fas fa-tachometer-alt me-1"></i> Bảng điều khiển
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link<?php echo ($activePage == 'thesis') ? ' active' : ''; ?>" href="<?php echo BASE_URL; ?>student/thesis/index.php">
                                        <i class="fas fa-file-alt me-1"></i> Đề tài luận văn
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link<?php echo ($activePage == 'advisor') ? ' active' : ''; ?>" href="<?php echo BASE_URL; ?>student/advisor/view.php">
                                        <i class="fas fa-chalkboard-teacher me-1"></i> GVHD
                                    </a>
                                </li>
                            <?php elseif($_SESSION['role'] === 'faculty'): ?>
                                <li class="nav-item">
                                    <a class="nav-link<?php echo ($activePage == 'dashboard') ? ' active' : ''; ?>" href="<?php echo BASE_URL; ?>faculty/dashboard.php">
                                        <i class="fas fa-tachometer-alt me-1"></i> Bảng điều khiển
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link<?php echo ($activePage == 'students') ? ' active' : ''; ?>" href="<?php echo BASE_URL; ?>faculty/students/index.php">
                                        <i class="fas fa-user-graduate me-1"></i> Sinh viên
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link<?php echo ($activePage == 'theses') ? ' active' : ''; ?>" href="<?php echo BASE_URL; ?>faculty/theses/index.php">
                                        <i class="fas fa-file-alt me-1"></i> Đề tài
                                    </a>
                                </li>
                            <?php elseif($_SESSION['role'] === 'admin'): ?>
                                <li class="nav-item">
                                    <a class="nav-link<?php echo ($activePage == 'dashboard') ? ' active' : ''; ?>" href="<?php echo BASE_URL; ?>admin/dashboard.php">
                                        <i class="fas fa-tachometer-alt me-1"></i> Bảng điều khiển
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link<?php echo ($activePage == 'users') ? ' active' : ''; ?>" href="<?php echo BASE_URL; ?>admin/users/index.php">
                                        <i class="fas fa-users me-1"></i> Người dùng
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link<?php echo ($activePage == 'reports') ? ' active' : ''; ?>" href="<?php echo BASE_URL; ?>admin/reports/index.php">
                                        <i class="fas fa-chart-bar me-1"></i> Báo cáo
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-user-circle me-1"></i> <?php echo $_SESSION['username']; ?>
                                    <?php if(isset($notificationCount) && $notificationCount > 0): ?>
                                        <span class="badge bg-danger rounded-pill ms-1"><?php echo $notificationCount; ?></span>
                                    <?php endif; ?>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>profile.php"><i class="fas fa-user me-2"></i> Hồ sơ</a></li>
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>notifications.php">
                                        <i class="fas fa-bell me-2"></i> Thông báo
                                        <?php if(isset($notificationCount) && $notificationCount > 0): ?>
                                            <span class="badge bg-danger rounded-pill ms-1"><?php echo $notificationCount; ?></span>
                                        <?php endif; ?>
                                    </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Đăng xuất</a></li>
                                </ul>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo BASE_URL; ?>auth/login.php">
                                    <i class="fas fa-sign-in-alt me-1"></i> Đăng nhập
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo BASE_URL; ?>auth/register.php">
                                    <i class="fas fa-user-plus me-1"></i> Đăng ký
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    <main class="py-4">
    <div class="container mt-4">