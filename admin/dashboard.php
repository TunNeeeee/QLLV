<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../classes/Auth.php';

// Bắt đầu phiên nếu chưa được khởi tạo
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$auth = new Auth();

// Kiểm tra đăng nhập và vai trò
if (!$auth->isLoggedIn() || $auth->getUserRole() != 'admin') {
    header('Location: ' . BASE_URL . 'auth/login.php?error=unauthorized_access');
    exit;
}

$db = new Database();
$userId = $_SESSION['user_id'];

// Kiểm tra xem bảng Admin có tồn tại không
$db->query("SHOW TABLES LIKE 'Admin'");
$adminTableExists = $db->rowCount() > 0;

// Nếu bảng Admin không tồn tại, tạo nó
if (!$adminTableExists) {
    try {
        $db->query("CREATE TABLE IF NOT EXISTS `Admin` (
            `AdminID` int(11) NOT NULL AUTO_INCREMENT,
            `UserID` int(11) NOT NULL,
            `HoTen` varchar(100) DEFAULT NULL,
            `ChucVu` varchar(50) DEFAULT NULL,
            `Avatar` varchar(255) DEFAULT NULL,
            `NgayTao` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`AdminID`),
            UNIQUE KEY `UserID` (`UserID`),
            CONSTRAINT `FK_Admin_Users` FOREIGN KEY (`UserID`) REFERENCES `Users` (`UserID`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
        
        // Thêm admin hiện tại vào bảng
        $db->query("INSERT INTO Admin (UserID, HoTen, ChucVu) 
                   SELECT UserID, Username as HoTen, 'Quản trị viên' as ChucVu 
                   FROM Users WHERE UserID = :userId AND NOT EXISTS 
                   (SELECT * FROM Admin WHERE UserID = :userId2)");
        $db->bind(':userId', $userId);
        $db->bind(':userId2', $userId);
        $db->execute();
        
        $adminTableCreated = true;
    } catch (PDOException $e) {
        error_log("Không thể tạo bảng Admin: " . $e->getMessage());
        $adminTableCreated = false;
    }
}

// Lấy thông tin admin
$adminInfo = null;
if ($adminTableExists || isset($adminTableCreated)) {
    $db->query("SELECT a.*, u.Email, u.Username FROM Admin a 
                JOIN Users u ON a.UserID = u.UserID 
                WHERE a.UserID = :userId");
    $db->bind(':userId', $userId);
    $adminInfo = $db->single();
}

// Đếm số lượng sinh viên
$studentCount = 0;
try {
    $db->query("SELECT COUNT(*) as total FROM SinhVien");
    $result = $db->single();
    if ($result) {
        $studentCount = $result['total'];
    }
} catch (PDOException $e) {
    error_log("Lỗi đếm sinh viên: " . $e->getMessage());
}

// Đếm số lượng giảng viên
$facultyCount = 0;
try {
    $db->query("SELECT COUNT(*) as total FROM GiangVien");
    $result = $db->single();
    if ($result) {
        $facultyCount = $result['total'];
    }
} catch (PDOException $e) {
    error_log("Lỗi đếm giảng viên: " . $e->getMessage());
}

// Đếm số lượng đề tài
$thesisCount = 0;
try {
    $db->query("SELECT COUNT(*) as total FROM DeTai");
    $result = $db->single();
    if ($result) {
        $thesisCount = $result['total'];
    }
} catch (PDOException $e) {
    error_log("Lỗi đếm đề tài: " . $e->getMessage());
}

// Đếm số lượng người dùng
$userCount = 0;
try {
    $db->query("SELECT COUNT(*) as total FROM Users");
    $result = $db->single();
    if ($result) {
        $userCount = $result['total'];
    }
} catch (PDOException $e) {
    error_log("Lỗi đếm người dùng: " . $e->getMessage());
}

// Lấy các đề tài mới nhất
$latestTheses = [];
try {
    $db->query("SELECT dt.*, gv.HoTen as TenGiangVien, COUNT(svgv.SinhVienID) as SoLuongSinhVien
                FROM DeTai dt
                LEFT JOIN SinhVienGiangVienHuongDan svgv ON dt.DeTaiID = svgv.DeTaiID
                LEFT JOIN GiangVien gv ON svgv.GiangVienID = gv.GiangVienID
                GROUP BY dt.DeTaiID
                ORDER BY dt.NgayTao DESC
                LIMIT 5");
    $latestTheses = $db->resultSet();
} catch (PDOException $e) {
    error_log("Lỗi lấy đề tài mới nhất: " . $e->getMessage());
}

// Lấy các sinh viên mới đăng ký
$latestStudents = [];
try {
    $db->query("SELECT sv.*, u.Email, u.NgayTao
                FROM SinhVien sv
                JOIN Users u ON sv.UserID = u.UserID
                ORDER BY u.NgayTao DESC
                LIMIT 5");
    $latestStudents = $db->resultSet();
} catch (PDOException $e) {
    error_log("Lỗi lấy sinh viên mới: " . $e->getMessage());
}

// Lấy danh sách người dùng mới
$latestUsers = [];
try {
    $db->query("SELECT * FROM Users ORDER BY NgayTao DESC LIMIT 5");
    $latestUsers = $db->resultSet();
} catch (PDOException $e) {
    error_log("Lỗi lấy người dùng mới: " . $e->getMessage());
}

// Kiểm tra trạng thái của hệ thống
$systemStatus = [
    'database' => true,
    'upload_dir' => is_writable('../uploads'),
    'php_version' => version_compare(PHP_VERSION, '7.4.0', '>='),
];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
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
            --dark-color: #343a40;
            --light-color: #f8f9fa;
            --text-color: #333;
            --light-text: #6c757d;
            --border-color: #e3e6f0;
            --card-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #f5f7fb;
            color: var(--text-color);
        }
        
        /* App Container */
        .app-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            width: 250px;
            background-color: #343a40;
            color: #fff;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
        }
        
        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-brand {
            color: #fff;
            font-weight: 700;
            font-size: 1.2rem;
            text-decoration: none;
            display: flex;
            align-items: center;
        }
        
        .sidebar-brand:hover {
            color: #fff;
            opacity: 0.9;
        }
        
        .sidebar-brand-icon {
            margin-right: 0.75rem;
            font-size: 1.1rem;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 1.5rem 0;
            margin: 0;
        }
        
        .sidebar-menu-category {
            padding: 0.75rem 1.5rem 0.5rem;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(255, 255, 255, 0.5);
        }
        
        .sidebar-menu-item {
            margin-bottom: 0.25rem;
        }
        
        .sidebar-menu-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: all 0.2s;
            border-left: 3px solid transparent;
        }
        
        .sidebar-menu-link:hover,
        .sidebar-menu-link.active {
            background-color: rgba(255, 255, 255, 0.05);
            color: #fff;
            border-left-color: var(--primary-color);
        }
        
        .sidebar-menu-icon {
            width: 20px;
            margin-right: 0.75rem;
            text-align: center;
        }
        
        .sidebar-divider {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin: 1rem 0;
        }
        
        .sidebar-user {
            padding: 1.5rem;
            display: flex;
            align-items: center;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: auto;
        }
        
        .sidebar-user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-right: 1rem;
            flex-shrink: 0;
        }
        
        .sidebar-user-details {
            overflow: hidden;
        }
        
        .sidebar-user-name {
            font-weight: 600;
            margin-bottom: 0.25rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .sidebar-user-role {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.7);
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 2rem;
            transition: all 0.3s;
        }
        
        /* Topbar */
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .page-title {
            font-weight: 700;
            color: var(--text-color);
            margin: 0;
        }
        
        .breadcrumb {
            margin-bottom: 0;
            background-color: transparent;
            padding: 0;
        }
        
        .topbar-actions {
            display: flex;
            align-items: center;
        }
        
        .topbar-btn {
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 0.75rem;
            color: var(--light-text);
            background-color: #fff;
            border: none;
            transition: all 0.2s;
        }
        
        .topbar-btn:hover {
            background-color: var(--light-color);
            color: var(--text-color);
        }
        
        /* Dashboard Stats */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            display: flex;
            align-items: center;
            padding: 1.5rem;
            border-radius: 12px;
            background-color: white;
            box-shadow: var(--card-shadow);
            transition: transform 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1.5rem;
            font-size: 1.75rem;
        }
        
        .stat-icon.primary {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
        }
        
        .stat-icon.success {
            background-color: rgba(46, 204, 113, 0.1);
            color: var(--success-color);
        }
        
        .stat-icon.warning {
            background-color: rgba(243, 156, 18, 0.1);
            color: var(--warning-color);
        }
        
        .stat-icon.danger {
            background-color: rgba(231, 76, 60, 0.1);
            color: var(--danger-color);
        }
        
        .stat-content {
            flex: 1;
        }
        
        .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 0.25rem;
            line-height: 1;
        }
        
        .stat-label {
            color: var(--light-text);
            font-size: 0.875rem;
            margin: 0;
        }
        
        /* Cards */
        .card {
            border-radius: 12px;
            border: none;
            box-shadow: var(--card-shadow);
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            background-color: #fff;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding: 1.25rem 1.5rem;
            border-radius: 12px 12px 0 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .card-header h5 {
            margin: 0;
            font-weight: 700;
            font-size: 1.1rem;
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        .card-footer {
            background-color: rgba(0,0,0,0.02);
            border-top: 1px solid rgba(0,0,0,0.05);
            padding: 1rem 1.5rem;
            border-radius: 0 0 12px 12px;
        }
        
        /* Table */
        .table {
            margin-bottom: 0;
        }
        
        .table th {
            font-weight: 600;
            border-top: none;
            white-space: nowrap;
        }
        
        .table td {
            vertical-align: middle;
        }
        
        /* Status Badges */
        .badge {
            font-weight: 600;
            padding: 0.35rem 0.65rem;
            font-size: 0.75rem;
            border-radius: 50px;
        }
        
        .badge-primary {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
        }
        
        .badge-success {
            background-color: rgba(46, 204, 113, 0.1);
            color: var (--success-color);
        }
        
        .badge-warning {
            background-color: rgba(243, 156, 18, 0.1);
            color: var(--warning-color);
        }
        
        .badge-danger {
            background-color: rgba(231, 76, 60, 0.1);
            color: var(--danger-color);
        }
        
        /* Buttons */
        .btn {
            border-radius: 8px;
            font-weight: 500;
            padding: 0.5rem 1rem;
            transition: all 0.2s;
        }
        
        .btn-sm {
            font-size: 0.75rem;
            padding: 0.25rem 0.75rem;
            border-radius: 6px;
        }
        
        /* System Status */
        .system-status-item {
            display: flex;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #eee;
        }
        
        .system-status-item:last-child {
            border-bottom: none;
        }
        
        .system-status-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
        }
        
        .system-status-icon.success {
            background-color: rgba(46, 204, 113, 0.1);
            color: var(--success-color);
        }
        
        .system-status-icon.danger {
            background-color: rgba(231, 76, 60, 0.1);
            color: var(--danger-color);
        }
        
        .system-status-content {
            flex: 1;
        }
        
        .system-status-label {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .system-status-description {
            color: var(--light-text);
            font-size: 0.875rem;
            margin: 0;
        }
        
        /* Toast/Alert */
        .toast-container {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            z-index: 9999;
        }
        
        /* Responsive Styles */
        @media (max-width: 991.98px) {
            .sidebar {
                width: 70px;
                overflow: hidden;
            }
            
            .sidebar-brand-text, 
            .sidebar-user-details,
            .sidebar-menu-text,
            .sidebar-menu-category {
                display: none;
            }
            
            .sidebar-user {
                justify-content: center;
            }
            
            .sidebar-user-avatar {
                margin-right: 0;
            }
            
            .sidebar-menu-link {
                padding: 0.75rem;
                justify-content: center;
            }
            
            .sidebar-menu-icon {
                margin-right: 0;
                font-size: 1.25rem;
            }
            
            .main-content {
                margin-left: 70px;
            }
        }
        
        @media (max-width: 767.98px) {
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(100%, 1fr));
            }
        }
        
        /* Footer */
        .footer {
            margin-top: 3rem;
            padding-top: 1.5rem;
            color: var(--light-text);
            border-top: 1px solid var(--border-color);
            font-size: 0.875rem;
        }
        
        /* Hover effects */
        .card, .stat-card, .btn {
            transition: all 0.3s ease;
        }
        
        .card:hover, .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <a href="dashboard.php" class="sidebar-brand">
                    <i class="fas fa-shield-alt sidebar-brand-icon"></i>
                    <span class="sidebar-brand-text"><?php echo SITE_NAME; ?> Admin</span>
                </a>
            </div>
            
            <ul class="sidebar-menu">
                <li class="sidebar-menu-category">Dashboard</li>
                <li class="sidebar-menu-item">
                    <a href="dashboard.php" class="sidebar-menu-link active">
                        <i class="fas fa-tachometer-alt sidebar-menu-icon"></i>
                        <span class="sidebar-menu-text">Trang chính</span>
                    </a>
                </li>
                
                <li class="sidebar-menu-category">Quản lý người dùng</li>
                <li class="sidebar-menu-item">
                    <a href="students.php" class="sidebar-menu-link">
                        <i class="fas fa-user-graduate sidebar-menu-icon"></i>
                        <span class="sidebar-menu-text">Sinh viên</span>
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="faculty.php" class="sidebar-menu-link">
                        <i class="fas fa-chalkboard-teacher sidebar-menu-icon"></i>
                        <span class="sidebar-menu-text">Giảng viên</span>
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="users.php" class="sidebar-menu-link">
                        <i class="fas fa-users sidebar-menu-icon"></i>
                        <span class="sidebar-menu-text">Tài khoản</span>
                    </a>
                </li>
                
                <li class="sidebar-menu-category">Quản lý đề tài</li>
                <li class="sidebar-menu-item">
                    <a href="theses.php" class="sidebar-menu-link">
                        <i class="fas fa-book sidebar-menu-icon"></i>
                        <span class="sidebar-menu-text">Đề tài luận văn</span>
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="assignments.php" class="sidebar-menu-link">
                        <i class="fas fa-tasks sidebar-menu-icon"></i>
                        <span class="sidebar-menu-text">Phân công hướng dẫn</span>
                    </a>
                </li>
                
                <li class="sidebar-divider"></li>
                
                <li class="sidebar-menu-category">Hệ thống</li>
                <li class="sidebar-menu-item">
                    <a href="settings.php" class="sidebar-menu-link">
                        <i class="fas fa-cog sidebar-menu-icon"></i>
                        <span class="sidebar-menu-text">Cài đặt</span>
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="logs.php" class="sidebar-menu-link">
                        <i class="fas fa-history sidebar-menu-icon"></i>
                        <span class="sidebar-menu-text">Nhật ký hoạt động</span>
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="../auth/logout.php" class="sidebar-menu-link text-danger">
                        <i class="fas fa-sign-out-alt sidebar-menu-icon"></i>
                        <span class="sidebar-menu-text">Đăng xuất</span>
                    </a>
                </li>
            </ul>
            
            <div class="sidebar-user">
                <div class="sidebar-user-avatar">
                    <?php echo strtoupper(substr($adminInfo['HoTen'] ?? $_SESSION['username'], 0, 1)); ?>
                </div>
                <div class="sidebar-user-details">
                    <div class="sidebar-user-name"><?php echo htmlspecialchars($adminInfo['HoTen'] ?? $_SESSION['username']); ?></div>
                    <div class="sidebar-user-role"><?php echo htmlspecialchars($adminInfo['ChucVu'] ?? 'Quản trị viên'); ?></div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Topbar -->
            <div class="topbar">
                <div>
                    <h1 class="page-title">Dashboard</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="#">Admin</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
                        </ol>
                    </nav>
                </div>
                
                <div class="topbar-actions">
                    <button type="button" class="topbar-btn" id="refreshDashboard" title="Làm mới">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    <div class="dropdown d-inline-block">
                        <button type="button" class="topbar-btn" id="notificationsDropdown" data-bs-toggle="dropdown" aria-expanded="false" title="Thông báo">
                            <i class="fas fa-bell"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationsDropdown">
                            <li><h6 class="dropdown-header">Thông báo</h6></li>
                            <li><a class="dropdown-item" href="#">Không có thông báo mới</a></li>
                        </ul>
                    </div>
                    <div class="dropdown d-inline-block">
                        <button type="button" class="topbar-btn" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false" title="Tài khoản">
                            <i class="fas fa-user"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-circle me-2"></i> Hồ sơ</a></li>
                            <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i> Cài đặt</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Đăng xuất</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Alert khi tạo bảng Admin thành công -->
            <?php if (isset($adminTableCreated) && $adminTableCreated): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i> Tạo bảng Admin và thêm thông tin quản trị viên thành công!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <!-- Stats Overview -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon primary">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-value"><?php echo number_format($studentCount); ?></h3>
                        <p class="stat-label">Sinh viên</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon success">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-value"><?php echo number_format($facultyCount); ?></h3>
                        <p class="stat-label">Giảng viên</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon warning">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-value"><?php echo number_format($thesisCount); ?></h3>
                        <p class="stat-label">Đề tài luận văn</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon danger">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-value"><?php echo number_format($userCount); ?></h3>
                        <p class="stat-label">Người dùng</p>
                    </div>
                </div>
            </div>
            
            <!-- Main Dashboard Content -->
            <div class="row">
                <!-- Latest Theses -->
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5>Đề tài mới nhất</h5>
                            <a href="theses.php" class="btn btn-sm btn-primary">Xem tất cả</a>
                        </div>
                        <div class="card-body">
                            <?php if (count($latestTheses) > 0): ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Tên đề tài</th>
                                            <th>Giảng viên</th>
                                            <th>Số SV</th>
                                            <th>Trạng thái</th>
                                            <th>Ngày tạo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($latestTheses as $thesis): ?>
                                        <tr>
                                            <td>
                                                <a href="thesis-details.php?id=<?php echo $thesis['DeTaiID']; ?>" class="fw-bold text-decoration-none">
                                                    <?php echo htmlspecialchars($thesis['TenDeTai']); ?>
                                                </a>
                                            </td>
                                            <td><?php echo htmlspecialchars($thesis['TenGiangVien'] ?? 'Chưa phân công'); ?></td>
                                            <td><?php echo $thesis['SoLuongSinhVien']; ?></td>
                                            <td>
                                                <?php
                                                $statusClass = '';
                                                switch($thesis['TrangThai']) {
                                                    case 'Đang thực hiện':
                                                        $statusClass = 'badge-primary';
                                                        break;
                                                    case 'Hoàn thành':
                                                        $statusClass = 'badge-success';
                                                        break;
                                                    case 'Chưa bắt đầu':
                                                        $statusClass = 'badge-warning';
                                                        break;
                                                    default:
                                                        $statusClass = 'badge-secondary';
                                                }
                                                ?>
                                                <span class="badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($thesis['TrangThai']); ?></span>
                                            </td>
                                            <td><?php echo date('d/m/Y', strtotime($thesis['NgayTao'])); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <div class="alert alert-info mb-0">
                                <i class="fas fa-info-circle me-2"></i> Chưa có đề tài nào trong hệ thống.
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Latest Students -->
                    <div class="card">
                        <div class="card-header">
                            <h5>Sinh viên mới đăng ký</h5>
                            <a href="students.php" class="btn btn-sm btn-primary">Xem tất cả</a>
                        </div>
                        <div class="card-body">
                            <?php if (count($latestStudents) > 0): ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Họ tên</th>
                                            <th>MSSV</th>
                                            <th>Khoa</th>
                                            <th>Email</th>
                                            <th>Ngày đăng ký</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($latestStudents as $student): ?>
                                        <tr>
                                            <td>
                                                <a href="student-details.php?id=<?php echo $student['SinhVienID']; ?>" class="fw-bold text-decoration-none">
                                                    <?php echo htmlspecialchars($student['HoTen']); ?>
                                                </a>
                                            </td>
                                            <td><?php echo htmlspecialchars($student['MaSV']); ?></td>
                                            <td><?php echo htmlspecialchars($student['Khoa']); ?></td>
                                            <td><?php echo htmlspecialchars($student['Email']); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($student['NgayTao'])); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <div class="alert alert-info mb-0">
                                <i class="fas fa-info-circle me-2"></i> Chưa có sinh viên nào đăng ký.
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Side Content -->
                <div class="col-lg-4">
                    <!-- System Status -->
                    <div class="card">
                        <div class="card-header">
                            <h5>Trạng thái hệ thống</h5>
                        </div>
                        <div class="card-body">
                            <div class="system-status-item">
                                <div class="system-status-icon <?php echo $systemStatus['database'] ? 'success' : 'danger'; ?>">
                                    <i class="fas fa-database"></i>
                                </div>
                                <div class="system-status-content">
                                    <div class="system-status-label">Cơ sở dữ liệu</div>
                                    <p class="system-status-description">
                                        <?php echo $systemStatus['database'] ? 'Kết nối thành công' : 'Lỗi kết nối'; ?>
                                    </p>
                                </div>
                            </div>
                            
                            <div class="system-status-item">
                                <div class="system-status-icon <?php echo $systemStatus['upload_dir'] ? 'success' : 'danger'; ?>">
                                    <i class="fas fa-folder"></i>
                                </div>
                                <div class="system-status-content">
                                    <div class="system-status-label">Thư mục uploads</div>
                                    <p class="system-status-description">
                                        <?php echo $systemStatus['upload_dir'] ? 'Có thể ghi' : 'Không thể ghi'; ?>
                                    </p>
                                </div>
                            </div>
                            
                            <div class="system-status-item">
                                <div class="system-status-icon <?php echo $systemStatus['php_version'] ? 'success' : 'danger'; ?>">
                                    <i class="fas fa-code"></i>
                                </div>
                                <div class="system-status-content">
                                    <div class="system-status-label">Phiên bản PHP</div>
                                    <p class="system-status-description">
                                        <?php echo PHP_VERSION; ?> 
                                        (<?php echo $systemStatus['php_version'] ? 'Tương thích' : 'Cần nâng cấp'; ?>)
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Latest Users -->
                    <div class="card">
                        <div class="card-header">
                            <h5>Người dùng mới</h5>
                            <a href="users.php" class="btn btn-sm btn-primary">Xem tất cả</a>
                        </div>
                        <div class="card-body">
                            <?php if (count($latestUsers) > 0): ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($latestUsers as $user): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="d-inline-block text-center me-2" style="width:30px; height:30px; background-color: rgba(<?php echo rand(0, 255); ?>, <?php echo rand(0, 255); ?>, <?php echo rand(0, 255); ?>, 0.1); border-radius: 50%; line-height: 30px; font-weight: 600;">
                                            <?php echo strtoupper(substr($user['Username'], 0, 1)); ?>
                                        </span>
                                        <span class="fw-bold"><?php echo htmlspecialchars($user['Username']); ?></span>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($user['Email']); ?></small>
                                    </div>
                                    <span class="badge rounded-pill bg-<?php 
                                        switch ($user['Role']) {
                                            case 'admin': echo 'danger'; break;
                                            case 'faculty': echo 'success'; break;
                                            case 'student': echo 'primary'; break;
                                            default: echo 'secondary';
                                        }
                                    ?>"><?php echo htmlspecialchars(ucfirst($user['Role'])); ?></span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                            <?php else: ?>
                            <div class="alert alert-info mb-0">
                                <i class="fas fa-info-circle me-2"></i> Chưa có người dùng nào đăng ký.
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="card">
                        <div class="card-header">
                            <h5>Thao tác nhanh</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="add-student.php" class="btn btn-outline-primary">
                                    <i class="fas fa-user-plus me-2"></i> Thêm sinh viên
                                </a>
                                <a href="add-faculty.php" class="btn btn-outline-success">
                                    <i class="fas fa-user-tie me-2"></i> Thêm giảng viên
                                </a>
                                <a href="add-thesis.php" class="btn btn-outline-warning">
                                    <i class="fas fa-folder-plus me-2"></i> Thêm đề tài
                                </a>
                                <a href="settings.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-cog me-2"></i> Cài đặt hệ thống
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quản lý phân công -->
                    <div class="card">
                        <div class="card-header">
                            <h5>Quản lý phân công</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="assign-students.php" class="btn btn-outline-primary">
                                    <i class="fas fa-user-plus me-2"></i> Phân công sinh viên - GVHD
                                </a>
                                <a href="assignment-requests.php" class="btn btn-outline-success">
                                    <i class="fas fa-clipboard-check me-2"></i> Duyệt yêu cầu phân công
                                </a>
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
                            <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?> - Hệ thống quản lý luận văn</p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <p>Phiên bản: 1.0.0</p>
                        </div>
                    </div>
                </div>
            </footer>
        </div>

        <!-- Thêm script -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Nút làm mới dashboard
            document.getElementById('refreshDashboard').addEventListener('click', function() {
                location.reload();
            });
            
            // Auto hide alerts after 5 seconds
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(function(alert) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);
        });
        </script>
    </div>
</body>
</html>