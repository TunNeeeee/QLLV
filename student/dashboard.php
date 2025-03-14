<?php
// Đảm bảo session đã được khởi tạo
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../classes/Auth.php';

$auth = new Auth();

// Check if the user is logged in and has the student role
if (!$auth->isLoggedIn()) {
    header('Location: ' . BASE_URL . 'auth/login.php?error=not_logged_in');
    exit;
}

if ($auth->getUserRole() != 'student') {
    header('Location: ' . BASE_URL . 'auth/login.php?error=wrong_role&role=' . $auth->getUserRole());
    exit;
}

// Kiểm tra xem profile_id có tồn tại không
if (!isset($_SESSION['profile_id'])) {
    // Tìm lại profile_id nếu chưa được thiết lập
    $db = new Database();
    $db->query("SELECT SinhVienID FROM SinhVien WHERE UserID = :userId");
    $db->bind(':userId', $_SESSION['user_id']);
    $student = $db->single();
    
    if ($student) {
        $_SESSION['profile_id'] = $student['SinhVienID'];
    } else {
        // Không tìm thấy profile, chuyển hướng về trang login
        session_destroy();
        header('Location: ' . BASE_URL . 'auth/login.php?error=no_profile');
        exit;
    }
}

$studentId = $_SESSION['profile_id'];
$db = new Database();

// Get student information
$db->query("SELECT sv.*, u.Username, u.Email FROM SinhVien sv 
            JOIN Users u ON sv.UserID = u.UserID 
            WHERE sv.SinhVienID = :id");
$db->bind(':id', $studentId);
$student = $db->single();

// Thay thế đoạn lấy thông tin luận văn với câu truy vấn mới
// Get thesis and advisor information
$db->query("SELECT 
    svgv.*, 
    gv.HoTen AS TenGiangVien,
    gv.HocVi,
    gv.Email AS EmailGiangVien,
    dt.DeTaiID, 
    dt.TenDeTai,
    dt.MoTa,
    dt.TrangThai AS TrangThaiDeTai
FROM SinhVienGiangVienHuongDan svgv
LEFT JOIN DeTai dt ON svgv.DeTaiID = dt.DeTaiID
LEFT JOIN GiangVien gv ON svgv.GiangVienID = gv.GiangVienID
WHERE svgv.SinhVienID = :studentId");
$db->bind(':studentId', $studentId);
$advisorData = $db->resultSet();

// Kiểm tra nếu có giảng viên hướng dẫn
$hasAdvisor = !empty($advisorData);
$hasThesis = !empty($advisorData) && !empty($advisorData[0]['DeTaiID']);

// Get recent notifications
$db->query("SELECT * FROM ThongBao WHERE UserID = :userId AND TrangThai = 'chưa đọc' ORDER BY NgayTao DESC LIMIT 5");
$db->bind(':userId', $_SESSION['user_id']);
$notifications = $db->resultSet();

// Count unread notifications
$db->query("SELECT COUNT(*) as count FROM ThongBao WHERE UserID = :userId AND TrangThai = 'chưa đọc'");
$db->bind(':userId', $_SESSION['user_id']);
$notificationCount = $db->single()['count'];

// Calculate days left to deadline for first thesis (if exists)
$daysLeft = 0;
$progressPercentage = 0;

if ($hasThesis && !empty($advisorData[0]['NgayKetThucDuKien'])) {
    $currentDate = new DateTime();
    $endDate = new DateTime($advisorData[0]['NgayKetThucDuKien']);
    $startDate = new DateTime($advisorData[0]['NgayBatDau']);
    
    $totalDays = $startDate->diff($endDate)->days;
    $daysLeft = $currentDate->diff($endDate)->days;
    
    if ($currentDate > $endDate) {
        $daysLeft = -$daysLeft; // Overdue
    }
    
    $daysElapsed = $totalDays - $daysLeft;
    $progressPercentage = min(100, max(0, round(($daysElapsed / $totalDays) * 100)));
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bảng điều khiển - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #f8f9fc;
            --accent-color: #3a0ca3;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --info-color: #3498db;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --text-color: #333;
            --light-text: #6c757d;
            --border-color: #e3e6f0;
            --card-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #f5f7fb;
            color: var(--text-color);
            line-height: 1.5;
        }
        
        /* App Container */
        .app-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            width: 250px;
            background: linear-gradient(to bottom, #4361ee, #3a0ca3);
            color: #fff;
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            z-index: 100;
            box-shadow: 4px 0 10px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
        }
        
        .sidebar.collapsed {
            width: 70px;
        }
        
        .sidebar-header {
            padding: 1.2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-brand {
            color: white;
            font-weight: 700;
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            text-decoration: none;
        }
        
        .sidebar-brand-text {
            margin-left: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .sidebar.collapsed .sidebar-brand-text {
            display: none;
        }
        
        .sidebar-toggle {
            background: transparent;
            color: white;
            border: none;
            font-size: 1.25rem;
            cursor: pointer;
            padding: 0.25rem;
        }
        
        .sidebar-user {
            padding: 1.5rem 1.25rem;
            display: flex;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-user-img {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            margin-right: 1rem;
            overflow: hidden;
            flex-shrink: 0;
            border: 2px solid rgba(255, 255, 255, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(255, 255, 255, 0.2);
        }
        
        .sidebar-user-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .avatar-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1.2rem;
            color: white;
        }
        
        .sidebar-user-info {
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .sidebar.collapsed .sidebar-user-info {
            display: none;
        }
        
        .sidebar-user-name {
            font-weight: 600;
            color: white;
            display: block;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-bottom: 0.25rem;
        }
        
        .sidebar-user-role {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.75rem;
            display: block;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
            margin-top: 1rem;
        }
        
        .sidebar-item {
            position: relative;
            margin-bottom: 0.25rem;
        }
        
        .sidebar-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.25rem;
            color: rgba(255, 255, 255, 0.8);
            transition: all 0.3s ease;
            border-radius: 0.25rem;
            margin: 0 0.5rem;
            position: relative;
            text-decoration: none;
        }
        
        .sidebar-link:hover, 
        .sidebar-link.active {
            background-color: rgba(255, 255, 255, 0.15);
            color: white;
        }
        
        .sidebar-icon {
            font-size: 1.1rem;
            width: 22px;
            text-align: center;
            margin-right: 1rem;
            transition: all 0.3s ease;
        }
        
        .sidebar.collapsed .sidebar-icon {
            margin-right: 0;
        }
        
        .sidebar-link span {
            transition: all 0.3s ease;
        }
        
        .sidebar.collapsed .sidebar-link span {
            display: none;
        }
        
        .sidebar-footer {
            margin-top: auto;
            padding: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 250px;
            transition: all 0.3s ease;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .main-content.expanded {
            margin-left: 70px;
        }
        
        /* Topbar */
        .topbar {
            background-color: #fff;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            position: sticky;
            top: 0;
            z-index: 99;
        }
        
        .topbar-title h4 {
            margin: 0;
            font-weight: 600;
            color: var(--text-color);
        }
        
        .topbar-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .btn-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            border: 1px solid var(--border-color);
            background-color: #fff;
            color: var(--text-color);
            transition: all 0.2s ease;
        }
        
        .btn-icon:hover {
            background-color: var(--secondary-color);
            color: var(--primary-color);
        }
        
        .user-dropdown {
            display: flex;
            align-items: center;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 8px;
            transition: all 0.2s ease;
        }
        
        .user-dropdown:hover {
            background-color: var(--secondary-color);
        }
        
        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            overflow: hidden;
            margin-right: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--secondary-color);
        }
        
        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .user-name {
            font-weight: 500;
            margin-right: 0.5rem;
        }
        
        /* Content Wrapper */
        .content-wrapper {
            flex: 1;
            padding: 1.5rem;
        }
        
        .dashboard-header {
            margin-bottom: 2rem;
        }
        
        .page-title {
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--text-color);
            font-size: 1.75rem;
        }
        
        /* Card Styles */
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            margin-bottom: 1.5rem;
            transition: transform 0.2s, box-shadow 0.2s;
            overflow: hidden;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 1.25rem;
            font-weight: 600;
        }
        
        /* Stats Cards */
        .stats-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: var(--card-shadow);
            padding: 1.25rem;
            height: 100%;
        }
        
        .stats-card-body {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }
        
        .stats-card-icon {
            width: 56px;
            height: 56px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #fff;
        }
        
        .bg-primary { background-color: var(--primary-color); }
        .bg-success { background-color: var(--success-color); }
        .bg-warning { background-color: var(--warning-color); }
        .bg-info { background-color: var(--info-color); }
        
        .stats-card-info {
            text-align: right;
        }
        
        .stats-card-label {
            color: var(--light-text);
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 0.25rem;
        }
        
        .stats-card-value {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0;
        }
        
        /* Profile Card */
        .profile-card {
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
        }
        
        .profile-card-header {
            background: linear-gradient(to right, var(--primary-color), var(--accent-color));
            padding: 2rem 1.5rem;
            text-align: center;
            color: white;
        }
        
        .profile-card-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid rgba(255, 255, 255, 0.3);
            margin: 0 auto 1rem;
            display: block;
        }
        
        .profile-info-icon {
            width: 36px;
            height: 36px;
            background-color: var(--secondary-color);
            color: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
        }
        
        /* Notification List */
        .notification-item {
            display: flex;
            padding: 1rem 0;
        }
        
        .notification-avatar {
            width: 40px;
            height: 40px;
            background-color: rgba(67, 97, 238, 0.15);
            color: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            flex-shrink: 0;
        }
        
        .notification-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .notification-text {
            color: var(--light-text);
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }
        
        /* Empty States */
        .empty-state {
            padding: 2rem;
            text-align: center;
        }
        
        .empty-state-icon {
            font-size: 3rem;
            color: var(--light-text);
            margin-bottom: 1rem;
        }
        
        /* Footer */
        .footer {
            background-color: white;
            padding: 1.25rem 1.5rem;
            color: var(--light-text);
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .footer-links {
            display: flex;
            gap: 1.5rem;
        }
        
        .footer-links a {
            color: var(--light-text);
            text-decoration: none;
        }
        
        .footer-links a:hover {
            color: var(--primary-color);
        }
        
        /* Avatar classes */
        .avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: white;
        }
        
        .avatar-sm {
            width: 30px;
            height: 30px;
            font-size: 0.75rem;
        }
        
        .bg-soft-primary {
            background-color: rgba(67, 97, 238, 0.2);
            color: var(--primary-color);
        }
        
        /* Progress Bar */
        .progress {
            height: 8px;
            border-radius: 4px;
            background-color: #e9ecef;
            overflow: hidden;
            margin-bottom: 0.5rem;
        }
        
        .progress-bar {
            background-color: var(--primary-color);
        }
        
        /* Responsive adjustments */
        @media (max-width: 992px) {
            .sidebar {
                width: 70px;
            }
            
            .sidebar-brand-text,
            .sidebar-user-info,
            .sidebar-link span {
                display: none;
            }
            
            .sidebar-icon {
                margin-right: 0;
            }
            
            .main-content {
                margin-left: 70px;
            }
        }
        
        @media (max-width: 768px) {
            .content-wrapper {
                padding: 1rem;
            }
            
            .stats-card-value {
                font-size: 1.5rem;
            }
            
            .sidebar {
                width: 0;
                transform: translateX(-100%);
            }
            
            .sidebar.mobile-show {
                width: 250px;
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="dashboard.php" class="sidebar-brand">
                    <i class="fas fa-graduation-cap me-2"></i>
                    <span class="sidebar-brand-text"><?php echo SITE_NAME; ?></span>
                </a>
                <button id="sidebarClose" class="sidebar-toggle d-lg-none">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="sidebar-user">
                <div class="sidebar-user-img">
                    <?php if (!empty($student['Avatar'])): ?>
                        <img src="<?php echo BASE_URL . 'uploads/avatars/' . $student['Avatar']; ?>" alt="Avatar">
                    <?php else: ?>
                        <div class="avatar-placeholder">
                            <?php echo strtoupper(substr($student['HoTen'] ?? 'U', 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="sidebar-user-info">
                    <span class="sidebar-user-name"><?php echo htmlspecialchars($student['HoTen'] ?? 'Người dùng'); ?></span>
                    <span class="sidebar-user-role">Sinh viên</span>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <ul class="sidebar-menu">
                    <li class="sidebar-item">
                        <a href="dashboard.php" class="sidebar-link active">
                            <i class="fas fa-tachometer-alt sidebar-icon"></i>
                            <span>Bảng điều khiển</span>
                        </a>
                    </li>
                    <li class="sidebar-item">
                        <a href="thesis.php" class="sidebar-link">
                            <i class="fas fa-book sidebar-icon"></i>
                            <span>Luận văn</span>
                        </a>
                    </li>
                    <li class="sidebar-item">
                        <a href="appointments.php" class="sidebar-link">
                            <i class="fas fa-calendar-alt sidebar-icon"></i>
                            <span>Lịch gặp</span>
                        </a>
                    </li>
                    <li class="sidebar-item">
                        <a href="documents.php" class="sidebar-link">
                            <i class="fas fa-file-alt sidebar-icon"></i>
                            <span>Tài liệu</span>
                        </a>
                    </li>
                    <li class="sidebar-item">
                        <a href="notifications.php" class="sidebar-link">
                            <i class="fas fa-bell sidebar-icon"></i>
                            <span>Thông báo</span>
                            <?php if ($notificationCount > 0): ?>
                                <span class="badge bg-danger float-end"><?php echo $notificationCount; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="sidebar-item">
                        <a href="profile.php" class="sidebar-link">
                            <i class="fas fa-user sidebar-icon"></i>
                            <span>Hồ sơ</span>
                        </a>
                    </li>
                </ul>
            </nav>
            
            <div class="sidebar-footer">
                <a href="../auth/logout.php" class="sidebar-link">
                    <i class="fas fa-sign-out-alt sidebar-icon"></i>
                    <span>Đăng xuất</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="main-content" id="main-content">
            <!-- Top Navigation Bar -->
            <header class="topbar">
                <button class="btn btn-icon" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                
                <div class="topbar-title d-none d-md-block">
                    <h4>Bảng điều khiển sinh viên</h4>
                </div>
                
                <div class="topbar-menu">
                    <div class="dropdown">
                        <button class="btn btn-icon position-relative" type="button" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-bell"></i>
                            <?php if ($notificationCount > 0): ?>
                                <span class="badge bg-danger badge-pill position-absolute top-0 end-0"><?php echo $notificationCount; ?></span>
                            <?php endif; ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationDropdown">
                            <li><h6 class="dropdown-header">Thông báo</h6></li>
                            <?php if (count($notifications) > 0): ?>
                                <?php foreach ($notifications as $index => $notification): ?>
                                    <?php if ($index < 3): ?>
                                        <li>
                                            <a class="dropdown-item" href="notifications.php?id=<?php echo $notification['ThongBaoID']; ?>">
                                                <div class="d-flex align-items-center">
                                                    <div class="flex-shrink-0">
                                                        <div class="avatar avatar-sm bg-soft-primary">
                                                            <i class="fas fa-bell"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <p class="mb-0 fw-bold"><?php echo $notification['TieuDe']; ?></p>
                                                        <p class="small text-muted mb-0"><?php echo date('d/m/Y H:i', strtotime($notification['NgayTao'])); ?></p>
                                                    </div>
                                                </div>
                                            </a>
                                        </li>
                                        <?php if ($index < count($notifications) - 1): ?>
                                            <li><hr class="dropdown-divider"></li>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li>
                                    <span class="dropdown-item">Không có thông báo mới</span>
                                </li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-center" href="notifications.php">Xem tất cả</a></li>
                        </ul>
                    </div>
                    
                    <div class="dropdown">
                        <div class="user-dropdown" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="user-avatar">
                                <?php if (!empty($student['Avatar'])): ?>
                                    <img src="<?php echo BASE_URL . 'uploads/avatars/' . $student['Avatar']; ?>" alt="Avatar">
                                <?php else: ?>
                                    <div class="avatar-placeholder">
                                        <?php echo strtoupper(substr($student['HoTen'] ?? 'U', 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <span class="user-name d-none d-md-inline-block"><?php echo $student['HoTen']; ?></span>
                            <i class="fas fa-chevron-down ms-1"></i>
                        </div>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Hồ sơ</a></li>
                            <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i>Cài đặt</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Đăng xuất</a></li>
                        </ul>
                    </div>
                </div>
            </header>
            
            <!-- Page Content -->
            <div class="content-wrapper">
                <!-- Dashboard header -->
                <div class="dashboard-header">
                    <div class="container-fluid">
                        <div class="row align-items-center">
                            <div class="col">
                                <h1 class="page-title">Bảng điều khiển</h1>
                                <nav aria-label="breadcrumb">
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item"><a href="#"><i class="fas fa-home"></i></a></li>
                                        <li class="breadcrumb-item active" aria-current="page">Bảng điều khiển</li>
                                    </ol>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Stats cards -->
                <div class="container-fluid">
                    <div class="row g-4">
                        <div class="col-sm-6 col-xl-3">
                            <div class="stats-card">
                                <div class="stats-card-body">
                                    <div class="stats-card-icon bg-primary">
                                        <i class="fas fa-book"></i>
                                    </div>
                                    <div class="stats-card-info">
                                        <span class="stats-card-label">Đề tài</span>
                                        <h3 class="stats-card-value"><?php echo count($advisorData); ?></h3>
                                    </div>
                                </div>
                                <div class="stats-card-footer">
                                    <div class="stats-trend">
                                        <i class="fas fa-check-circle text-success"></i>
                                        <span>Đang thực hiện</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-sm-6 col-xl-3">
                            <div class="stats-card">
                                <div class="stats-card-body">
                                    <div class="stats-card-icon bg-success">
                                        <i class="fas fa-calendar-check"></i>
                                    </div>
                                    <div class="stats-card-info">
                                        <span class="stats-card-label">Tiến độ</span>
                                        <h3 class="stats-card-value"><?php echo $progressPercentage; ?>%</h3>
                                    </div>
                                </div>
                                <div class="stats-card-footer">
                                    <div class="progress">
                                        <div class="progress-bar" role="progressbar" style="width: <?php echo $progressPercentage; ?>%" aria-valuenow="<?php echo $progressPercentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-sm-6 col-xl-3">
                            <div class="stats-card">
                                <div class="stats-card-body">
                                    <div class="stats-card-icon bg-warning">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <div class="stats-card-info">
                                        <span class="stats-card-label">Ngày còn lại</span>
                                        <h3 class="stats-card-value"><?php echo $daysLeft > 0 ? $daysLeft : 'Quá hạn'; ?></h3>
                                    </div>
                                </div>
                                <div class="stats-card-footer">
                                    <div class="stats-trend">
                                        <?php if ($daysLeft > 10): ?>
                                            <i class="fas fa-arrow-up text-success"></i>
                                            <span>Còn nhiều thời gian</span>
                                        <?php elseif ($daysLeft > 0): ?>
                                            <i class="fas fa-exclamation-circle text-warning"></i>
                                            <span>Sắp đến hạn</span>
                                        <?php else: ?>
                                            <i class="fas fa-exclamation-triangle text-danger"></i>
                                            <span>Đã quá hạn</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-sm-6 col-xl-3">
                            <div class="stats-card">
                                <div class="stats-card-body">
                                    <div class="stats-card-icon bg-info">
                                        <i class="fas fa-bell"></i>
                                    </div>
                                    <div class="stats-card-info">
                                        <span class="stats-card-label">Thông báo</span>
                                        <h3 class="stats-card-value"><?php echo $notificationCount; ?></h3>
                                    </div>
                                </div>
                                <div class="stats-card-footer">
                                    <a href="notifications.php" class="stats-card-link">
                                        <span>Xem thông báo</span>
                                        <i class="fas fa-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Content row -->
                    <div class="row g-4 mt-2">
                        <!-- Profile card -->
                        <div class="col-md-12 col-lg-4">
                            <div class="card profile-card">
                                <div class="profile-card-header">
                                    <?php if (!empty($student['Avatar'])): ?>
                                        <img src="<?php echo BASE_URL . 'uploads/avatars/' . $student['Avatar']; ?>" alt="Avatar" class="profile-card-avatar">
                                    <?php else: ?>
                                        <img src="<?php echo BASE_URL; ?>assets/img/default-avatar.jpg" alt="Avatar" class="profile-card-avatar">
                                    <?php endif; ?>
                                    <h4><?php echo $student['HoTen']; ?></h4>
                                    <p class="mb-0">Sinh viên</p>
                                </div>
                                <div class="profile-card-body">
                                    <ul class="profile-card-info">
                                        <li>
                                            <i class="fas fa-id-card profile-info-icon"></i>
                                            <span class="profile-info-label">MSSV</span>
                                            <span class="profile-info-value"><?php echo $student['MaSV']; ?></span>
                                        </li>
                                        <li>
                                            <i class="fas fa-envelope profile-info-icon"></i>
                                            <span class="profile-info-label">Email</span>
                                            <span class="profile-info-value"><?php echo $student['Email']?></span>
                                        </li>
                                        <li>
                                            <i class="fas fa-university profile-info-icon"></i>
                                            <span class="profile-info-label">Khoa</span>
                                            <span class="profile-info-value"><?php echo $student['Khoa']; ?></span>
                                        </li>
                                        <li>
                                            <i class="fas fa-graduation-cap profile-info-icon"></i>
                                            <span class="profile-info-label">Ngành học</span>
                                            <span class="profile-info-value"><?php echo $student['NganhHoc']; ?></span>
                                        </li>
                                        <li>
                                            <i class="fas fa-calendar-alt profile-info-icon"></i>
                                            <span class="profile-info-label">Niên khóa</span>
                                            <span class="profile-info-value"><?php echo $student['NienKhoa']; ?></span>
                                        </li>
                                        <?php if (!empty($student['SoDienThoai'])): ?>
                                        <li>
                                            <i class="fas fa-phone profile-info-icon"></i>
                                            <span class="profile-info-label">Điện thoại</span>
                                            <span class="profile-info-value"><?php echo $student['SoDienThoai']; ?></span>
                                        </li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                                <div class="profile-card-footer">
                                    <a href="profile.php" class="btn btn-primary btn-block">Xem hồ sơ đầy đủ</a>
                                </div>
                            </div>
                        </div>

                        <!-- Main content area -->
                        <div class="col-md-12 col-lg-8">
                            <!-- Thesis information -->
                                                         <!-- Advisor information -->
                            <div class="card mb-4">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fas fa-user-tie me-2 text-info"></i>
                                        <span>Giảng viên hướng dẫn</span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <?php if ($hasAdvisor): ?>
                                        <div class="d-flex align-items-center">
                                            <div class="flex-shrink-0">
                                                <div class="avatar bg-primary" style="width: 60px; height: 60px;">
                                                    <?php echo substr($advisorData[0]['TenGiangVien'], 0, 1); ?>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h5 class="mb-1"><?php echo (!empty($advisorData[0]['HocVi']) ? $advisorData[0]['HocVi'] . ' ' : '') . $advisorData[0]['TenGiangVien']; ?></h5>
                                                <?php if (!empty($advisorData[0]['EmailGiangVien'])): ?>
                                                    <p class="mb-1"><i class="fas fa-envelope me-1 text-muted"></i> <?php echo $advisorData[0]['EmailGiangVien']; ?></p>
                                                <?php endif; ?>
                                                <div class="d-flex mt-2">
                                                    <span class="badge bg-info me-2">
                                                        <i class="fas fa-calendar-alt me-1"></i> 
                                                        Bắt đầu: <?php echo date('d/m/Y', strtotime($advisorData[0]['NgayBatDau'])); ?>
                                                    </span>
                                                    <?php if (!empty($advisorData[0]['NgayKetThucDuKien'])): ?>
                                                        <span class="badge bg-warning">
                                                            <i class="fas fa-clock me-1"></i>
                                                            Đến: <?php echo date('d/m/Y', strtotime($advisorData[0]['NgayKetThucDuKien'])); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <?php if (!$hasThesis): ?>
                                            <div class="alert alert-info mt-3">
                                                <i class="fas fa-info-circle me-2"></i>
                                                Bạn đã được phân công giảng viên hướng dẫn nhưng chưa có đề tài. Vui lòng liên hệ với giảng viên để thảo luận về đề tài luận văn của bạn.
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="text-center py-4">
                                            <div class="empty-state">
                                                <div class="empty-state-icon">
                                                    <i class="fas fa-user-tie"></i>
                                                </div>
                                                <h5>Chưa có giảng viên hướng dẫn</h5>
                                                <p class="text-muted">Bạn chưa được phân công giảng viên hướng dẫn. Vui lòng liên hệ với khoa để biết thêm thông tin.</p>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Thesis information -->
                            <div class="card mb-4">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fas fa-book me-2 text-primary"></i>
                                        <span>Thông tin luận văn</span>
                                    </div>
                                    <?php if ($hasThesis): ?>
                                        <a href="thesis.php" class="btn btn-sm btn-primary">Xem chi tiết</a>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body">
                                    <?php if ($hasThesis): ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Tên đề tài</th>
                                                        <th>Giảng viên</th>
                                                        <th>Thời gian</th>
                                                        
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($advisorData as $t): ?>
                                                        <tr>
                                                            <td>
                                                                <div class="thesis-title"><?php echo $t['TenDeTai']; ?></div>
                                                                <div class="thesis-desc text-muted small"><?php echo substr($t['MoTa'], 0, 50) . (strlen($t['MoTa']) > 50 ? '...' : ''); ?></div>
                                                            </td>
                                                            <td>
                                                                <div class="d-flex align-items-center">
                                                                    <div class="avatar avatar-sm me-2 bg-soft-primary">
                                                                        <?php echo substr((!empty($t['TenGiangVien']) ? $t['TenGiangVien'] : 'GV'), 0, 1); ?>
                                                                    </div>
                                                                    <div>
                                                                        <?php echo (!empty($t['HocVi']) ? $t['HocVi'] . ' ' : '') . $t['TenGiangVien']; ?>
                                                                    </div>
                                                                </td>
                                                            <td>
                                                                <div class="small">
                                                                    <i class="fas fa-calendar-day me-1 text-muted"></i> <?php echo date('d/m/Y', strtotime($t['NgayBatDau'])); ?>
                                                                </div>
                                                                <div class="small">
                                                                    <i class="fas fa-calendar-check me-1 text-muted"></i> <?php echo date('d/m/Y', strtotime($t['NgayKetThucDuKien'])); ?>
                                                                </div>
                                                            </td>
                                                            
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>

                                        <!-- Progress -->
                                        <div class="thesis-progress mt-4">
                                            <div class="d-flex justify-content-between mb-2">
                                                <h6>Tiến độ thực hiện</h6>
                                                <span class="text-muted"><?php echo $progressPercentage; ?>%</span>
                                            </div>
                                            <div class="progress mb-3">
                                                <div class="progress-bar" role="progressbar" style="width: <?php echo $progressPercentage; ?>%" aria-valuenow="<?php echo $progressPercentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                            <div class="d-flex justify-content-between small text-muted">
                                                <div>
                                                    <i class="fas fa-calendar-day me-1"></i> Bắt đầu: <?php echo date('d/m/Y', strtotime($advisorData[0]['NgayBatDau'])); ?>
                                                </div>
                                                <div>
                                                    <i class="fas fa-calendar-check me-1"></i> Hạn nộp: <?php echo date('d/m/Y', strtotime($advisorData[0]['NgayKetThucDuKien'])); ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center py-4">
                                            <div class="empty-state">
                                                <div class="empty-state-icon">
                                                    <i class="fas fa-book"></i>
                                                </div>
                                                <h5>Bạn chưa có đề tài luận văn</h5>
                                                <p class="text-muted">Đăng ký đề tài để bắt đầu quá trình thực hiện luận văn</p>
                                                
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>



                            <!-- Notifications -->
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fas fa-bell me-2 text-warning"></i>
                                        <span>Thông báo gần đây</span>
                                    </div>
                                    <a href="notifications.php" class="btn btn-sm btn-primary">Xem tất cả</a>
                                </div>
                                <div class="card-body">
                                    <?php if (count($notifications) > 0): ?>
                                        <div class="notification-list">
                                            <?php foreach ($notifications as $index => $notification): ?>
                                                <div class="notification-item">
                                                    <div class="notification-avatar">
                                                        <i class="fas fa-bell"></i>
                                                    </div>
                                                    <div class="notification-content">
                                                        <h6 class="notification-title"><?php echo $notification['TieuDe']; ?></h6>
                                                        <p class="notification-text">
                                                            <?php echo substr($notification['NoiDung'], 0, 100) . (strlen($notification['NoiDung']) > 100 ? '...' : ''); ?>
                                                        </p>
                                                        <div class="notification-meta">
                                                            <span class="notification-time">
                                                                <i class="fas fa-clock me-1"></i>
                                                                <?php echo date('d/m/Y H:i', strtotime($notification['NgayTao'])); ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php if ($index < count($notifications) - 1): ?>
                                                    <hr>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center py-4">
                                            <div class="empty-state">
                                                <div class="empty-state-icon">
                                                    <i class="fas fa-bell"></i>
                                                </div>
                                                <h5>Không có thông báo mới</h5>
                                                <p class="text-muted">Bạn sẽ nhận được thông báo khi có thông tin mới</p>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <footer class="footer">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="text-muted">
                                &copy; <?php echo date('Y'); ?> - <?php echo SITE_NAME; ?> | Hệ thống quản lý hướng dẫn luận văn
                            </p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <div class="footer-links">
                                <a href="../pages/privacy.php">Chính sách bảo mật</a>
                                <a href="../pages/terms.php">Điều khoản sử dụng</a>
                                <a href="../pages/contact.php">Liên hệ</a>
                            </div>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Sidebar toggle
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.querySelector('.main-content');
            
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    document.body.classList.toggle('sidebar-collapsed');
                    sidebar.classList.toggle('collapsed');
                    mainContent.classList.toggle('expanded');
                });
            }
            
            // Tooltips
            const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            tooltips.forEach(tooltip => {
                new bootstrap.Tooltip(tooltip);
            });
        });
    </script>
</body>
</html>