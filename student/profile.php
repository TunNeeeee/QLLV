<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../classes/Auth.php';

// Bắt đầu phiên nếu chưa được khởi tạo
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$auth = new Auth();

// Check if user is logged in and has the role of student
if (!$auth->isLoggedIn() || !$auth->hasRole('student')) {
    header('Location: ../auth/login.php');
    exit;
}

// Get student profile information
$db = new Database();
$userId = $_SESSION['user_id'];

$db->query("SELECT sv.*, u.Username, u.Email FROM SinhVien sv JOIN Users u ON sv.UserID = u.UserID WHERE sv.UserID = :userId");
$db->bind(':userId', $userId);
$student = $db->single();

if (!$student) {
    header('Location: ../auth/login.php');
    exit;
}

// Lấy thông tin về luận văn của sinh viên (nếu có)
$studentId = $student['SinhVienID'];
$thesisCount = 0;
try {
    $db->query("SELECT COUNT(*) as total FROM DeTai dt 
                JOIN SinhVienGiangVienHuongDan svgv ON dt.DeTaiID = svgv.DeTaiID 
                WHERE svgv.SinhVienID = :studentId");
    $db->bind(':studentId', $studentId);
    $thesis = $db->single();
    if ($thesis) {
        $thesisCount = $thesis['total'];
    }
} catch (PDOException $e) {
    // Nếu có lỗi, giữ $thesisCount = 0
}

// Đếm số lượng cuộc gặp đã thực hiện
$pastMeetings = 0;
try {
    // Kiểm tra xem bảng LichGap có tồn tại không
    $db->query("SHOW TABLES LIKE 'LichGap'");
    $tableExists = $db->rowCount() > 0;
    
    if ($tableExists) {
        $db->query("SELECT COUNT(*) as total FROM LichGap 
                    WHERE SinhVienID = :studentId AND NgayGap < CURDATE()");
        $db->bind(':studentId', $studentId);
        $meetings = $db->single();
        if ($meetings) {
            $pastMeetings = $meetings['total'];
        }
    }
} catch (PDOException $e) {
    // Nếu có lỗi, giữ $pastMeetings = 0
}

// Đếm số lượng tài liệu đã upload
$documentsCount = 0;
try {
    // Kiểm tra xem bảng TaiLieu có tồn tại không
    $db->query("SHOW TABLES LIKE 'TaiLieu'");
    $tableExists = $db->rowCount() > 0;
    
    if ($tableExists) {
        $db->query("SELECT COUNT(*) as total FROM TaiLieu WHERE SinhVienID = :studentId");
        $db->bind(':studentId', $studentId);
        $documents = $db->single();
        if ($documents) {
            $documentsCount = $documents['total'];
        }
    }
} catch (PDOException $e) {
    // Nếu có lỗi, giữ $documentsCount = 0
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông tin cá nhân - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #f8f9fc;
            --accent-color: #3a0ca3;
            --text-color: #333;
            --light-text: #6c757d;
            --border-color: #e3e6f0;
            --success-color: #2ecc71;
            --info-color: #3498db;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #f5f7fb;
            color: var(--text-color);
        }
        
        .app-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            width: 250px;
            background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
            color: #fff;
            position: fixed;
            height: 100%;
            z-index: 100;
            transition: all 0.3s;
        }
        
        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-brand {
            color: white;
            text-decoration: none;
            font-weight: 700;
            font-size: 1.25rem;
            display: flex;
            align-items: center;
        }
        
        .sidebar-brand:hover {
            color: white;
            opacity: 0.9;
        }
        
        .sidebar-user {
            display: flex;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-user-img {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            font-size: 1.25rem;
            font-weight: 600;
            color: white;
        }
        
        .sidebar-user-name {
            font-weight: 600;
            margin-bottom: 0.25rem;
            display: block;
        }
        
        .sidebar-user-role {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.7);
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 1rem 0;
            margin: 0;
        }
        
        .sidebar-menu li {
            margin-bottom: 0.25rem;
        }
        
        .sidebar-menu a {
            padding: 0.85rem 1.5rem;
            display: flex;
            align-items: center;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: all 0.3s;
            border-radius: 0.25rem;
            margin: 0 0.5rem;
        }
        
        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-menu a i {
            margin-right: 0.75rem;
            font-size: 1rem;
            width: 20px;
            text-align: center;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 2rem;
            transition: all 0.3s;
        }
        
        /* Profile Card */
        .profile-card {
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.07);
            border: none;
            margin-bottom: 2rem;
            transition: transform 0.2s;
        }
        
        .profile-card:hover {
            transform: translateY(-5px);
        }
        
        .profile-header {
            display: flex;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--primary-color);
            color: white;
            font-size: 2.5rem;
            font-weight: 600;
            margin-right: 2rem;
            border: 4px solid white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .profile-info h3 {
            margin-bottom: 0.5rem;
            font-weight: 700;
        }
        
        .profile-info p {
            color: #6c757d;
            margin-bottom: 0.5rem;
        }
        
        .profile-detail-item {
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            border-bottom: 1px solid #eee;
        }
        
        .profile-detail-item:last-child {
            border-bottom: none;
        }
        
        .profile-detail-icon {
            width: 40px;
            height: 40px;
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
        }
        
        .profile-detail-content {
            flex: 1;
        }
        
        .profile-detail-label {
            font-size: 0.875rem;
            color: #6c757d;
            margin-bottom: 0.25rem;
        }
        
        .profile-detail-value {
            font-weight: 500;
        }
        
        /* Stats Card */
        .stats-card {
            border-radius: 15px;
            overflow: hidden;
            margin-bottom: 1.5rem;
            border: none;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.07);
            height: 100%;
        }
        
        .stats-header {
            background-color: var(--primary-color);
            color: white;
            padding: 1.5rem;
            text-align: center;
        }
        
        .stats-value {
            font-size: 3rem;
            font-weight: 700;
            margin: 1rem 0;
        }
        
        .stats-label {
            font-size: 1rem;
            font-weight: 500;
        }
        
        .stats-icon {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        
        /* Badge */
        .stat-badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 500;
            margin-bottom: 1rem;
            display: inline-block;
        }
        
        .badge-primary {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
        }
        
        .badge-success {
            background-color: rgba(46, 204, 113, 0.1);
            color: var(--success-color);
        }
        
        .badge-info {
            background-color: rgba(52, 152, 219, 0.1);
            color: var(--info-color);
        }
        
        /* Cards */
        .card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.07);
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            background-color: #fff;
            border-bottom: 1px solid #eee;
            padding: 1.25rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-header h5 {
            margin: 0;
            font-weight: 600;
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        /* Buttons */
        .btn {
            border-radius: 8px;
            padding: 0.6rem 1.2rem;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.2);
        }
        
        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }
        
        /* Responsive */
        @media (max-width: 991.98px) {
            .sidebar {
                width: 70px;
            }
            
            .sidebar-brand span,
            .sidebar-user-info,
            .sidebar-menu a span {
                display: none;
            }
            
            .sidebar-menu a i {
                margin-right: 0;
            }
            
            .main-content {
                margin-left: 70px;
            }
            
            .profile-header {
                flex-direction: column;
                text-align: center;
            }
            
            .profile-avatar {
                margin-right: 0;
                margin-bottom: 1rem;
            }
        }

        /* Footer Styles */
        .footer {
            padding: 1.5rem 0;
            border-top: 1px solid var(--border-color);
            margin-top: 2rem;
            color: var(--light-text);
        }

        .footer-links a {
            color: var(--light-text);
            text-decoration: none;
            transition: color 0.2s;
        }

        .footer-links a:hover {
            color: var(--primary-color);
        }

        .sidebar-user-img img, .profile-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .profile-avatar img {
            border: 4px solid white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <a href="dashboard.php" class="sidebar-brand">
                    <i class="fas fa-graduation-cap me-2"></i>
                    <span><?php echo SITE_NAME; ?></span>
                </a>
            </div>
            
            <div class="sidebar-user">
                <div class="sidebar-user-img">
                    <?php if (!empty($student['Avatar'])): ?>
                        <img src="<?php echo BASE_URL . $student['Avatar']; ?>" alt="Avatar" class="rounded-circle w-100 h-100" style="object-fit: cover;">
                    <?php else: ?>
                        <?php echo strtoupper(substr($student['HoTen'] ?? 'U', 0, 1)); ?>
                    <?php endif; ?>
                </div>
                <div class="sidebar-user-info">
                    <span class="sidebar-user-name"><?php echo htmlspecialchars($student['HoTen']); ?></span>
                    <span class="sidebar-user-role">Sinh viên</span>
                </div>
            </div>
            
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Bảng điều khiển</span></a></li>
                <li><a href="thesis.php"><i class="fas fa-book"></i> <span>Luận văn</span></a></li>
                <li><a href="appointments.php"><i class="fas fa-calendar-alt"></i> <span>Lịch gặp</span></a></li>
                <li><a href="documents.php"><i class="fas fa-file-alt"></i> <span>Tài liệu</span></a></li>
                <li><a href="profile.php" class="active"><i class="fas fa-user"></i> <span>Hồ sơ cá nhân</span></a></li>
                <li><a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> <span>Đăng xuất</span></a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <div class="container-fluid p-0">
                <h1 class="h3 mb-3">Hồ sơ cá nhân</h1>
                
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Trang chủ</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Hồ sơ cá nhân</li>
                    </ol>
                </nav>

                <div class="row">
                    <div class="col-md-8">
                        <!-- Profile Information -->
                        <div class="card profile-card">
                            <div class="card-header">
                                <h5>Thông tin sinh viên</h5>
                                <a href="edit-profile.php" class="btn btn-sm btn-primary">
                                    <i class="fas fa-edit me-1"></i> Chỉnh sửa
                                </a>
                            </div>
                            <div class="card-body p-0">
                                <div class="profile-header">
                                    <div class="profile-avatar">
                                        <?php if (!empty($student['Avatar'])): ?>
                                            <img src="<?php echo BASE_URL . $student['Avatar']; ?>" alt="Avatar" class="rounded-circle w-100 h-100" style="object-fit: cover;">
                                        <?php else: ?>
                                            <?php echo strtoupper(substr($student['HoTen'] ?? 'U', 0, 1)); ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="profile-info">
                                        <h3><?php echo htmlspecialchars($student['HoTen']); ?></h3>
                                        <p><i class="fas fa-id-card me-2"></i><?php echo htmlspecialchars($student['MaSV']); ?></p>
                                        <p><i class="fas fa-university me-2"></i><?php echo htmlspecialchars($student['Khoa']); ?></p>
                                        <p><i class="fas fa-graduation-cap me-2"></i><?php echo htmlspecialchars($student['NganhHoc']); ?></p>
                                    </div>
                                </div>

                                <div class="profile-details">
                                    <div class="profile-detail-item">
                                        <div class="profile-detail-icon">
                                            <i class="fas fa-envelope"></i>
                                        </div>
                                        <div class="profile-detail-content">
                                            <div class="profile-detail-label">Email</div>
                                            <div class="profile-detail-value"><?php echo htmlspecialchars($student['Email']); ?></div>
                                        </div>
                                    </div>
                                    
                                    <div class="profile-detail-item">
                                        <div class="profile-detail-icon">
                                            <i class="fas fa-calendar-alt"></i>
                                        </div>
                                        <div class="profile-detail-content">
                                            <div class="profile-detail-label">Niên khóa</div>
                                            <div class="profile-detail-value"><?php echo htmlspecialchars($student['NienKhoa']); ?></div>
                                        </div>
                                    </div>

                                    <?php if (!empty($student['SoDienThoai'])): ?>
                                    <div class="profile-detail-item">
                                        <div class="profile-detail-icon">
                                            <i class="fas fa-phone"></i>
                                        </div>
                                        <div class="profile-detail-content">
                                            <div class="profile-detail-label">Số điện thoại</div>
                                            <div class="profile-detail-value"><?php echo htmlspecialchars($student['SoDienThoai']); ?></div>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <?php if (!empty($student['DiaChi'])): ?>
                                    <div class="profile-detail-item">
                                        <div class="profile-detail-icon">
                                            <i class="fas fa-map-marker-alt"></i>
                                        </div>
                                        <div class="profile-detail-content">
                                            <div class="profile-detail-label">Địa chỉ</div>
                                            <div class="profile-detail-value"><?php echo htmlspecialchars($student['DiaChi']); ?></div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Account Information -->
                        <div class="card">
                            <div class="card-header">
                                <h5>Thông tin tài khoản</h5>
                                <a href="change-password.php" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-key me-1"></i> Đổi mật khẩu
                                </a>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-3">
                                        <strong>Tên đăng nhập:</strong>
                                    </div>
                                    <div class="col-md-9">
                                        <?php echo htmlspecialchars($student['Username']); ?>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-3">
                                        <strong>Email đăng nhập:</strong>
                                    </div>
                                    <div class="col-md-9">
                                        <?php echo htmlspecialchars($student['Email']); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <!-- Stats -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card stats-card bg-primary text-white">
                                    <div class="card-body text-center">
                                        <div class="stats-icon">
                                            <i class="fas fa-book"></i>
                                        </div>
                                        <div class="stats-value"><?php echo $thesisCount; ?></div>
                                        <div class="stats-label">Đề tài luận văn</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="card stats-card">
                                    <div class="card-body text-center">
                                        <div class="stat-badge badge-success">
                                            <i class="fas fa-calendar-check me-1"></i>
                                        </div>
                                        <div class="stats-value"><?php echo $pastMeetings; ?></div>
                                        <div class="stats-label text-muted">Buổi gặp</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="card stats-card">
                                    <div class="card-body text-center">
                                        <div class="stat-badge badge-info">
                                            <i class="fas fa-file-alt me-1"></i>
                                        </div>
                                        <div class="stats-value"><?php echo $documentsCount; ?></div>
                                        <div class="stats-label text-muted">Tài liệu</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Links -->
                        <div class="card">
                            <div class="card-header">
                                <h5>Liên kết nhanh</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="thesis.php" class="btn btn-outline-primary">
                                        <i class="fas fa-book me-2"></i> Quản lý luận văn
                                    </a>
                                    <a href="appointments.php" class="btn btn-outline-primary">
                                        <i class="fas fa-calendar-plus me-2"></i> Đặt lịch gặp
                                    </a>
                                    <a href="documents.php" class="btn btn-outline-primary">
                                        <i class="fas fa-file-upload me-2"></i> Tải tài liệu
                                    </a>
                                    <a href="edit-profile.php" class="btn btn-outline-primary">
                                        <i class="fas fa-user-edit me-2"></i> Cập nhật hồ sơ
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Footer được thêm vào -->
                <footer class="footer mt-5">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-0">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?> - Hệ thống quản lý luận văn</p>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <div class="footer-links">
                                    <a href="#" class="me-3">Điều khoản</a>
                                    <a href="#" class="me-3">Chính sách</a>
                                    <a href="#">Hỗ trợ</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </footer>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Script để xử lý sidebar responsive -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Kiểm tra và thiết lập trạng thái sidebar dựa trên kích thước màn hình
            const handleResize = () => {
                const sidebar = document.querySelector('.sidebar');
                const mainContent = document.querySelector('.main-content');
                
                if (window.innerWidth < 992) {
                    sidebar.style.width = '70px';
                    mainContent.style.marginLeft = '70px';
                    
                    const userInfo = document.querySelector('.sidebar-user-info');
                    const menuTexts = document.querySelectorAll('.sidebar-menu a span');
                    const brandText = document.querySelector('.sidebar-brand span');
                    
                    if (userInfo) userInfo.style.display = 'none';
                    menuTexts.forEach(span => span.style.display = 'none');
                    if (brandText) brandText.style.display = 'none';
                } else {
                    sidebar.style.width = '250px';
                    mainContent.style.marginLeft = '250px';
                    
                    const userInfo = document.querySelector('.sidebar-user-info');
                    const menuTexts = document.querySelectorAll('.sidebar-menu a span');
                    const brandText = document.querySelector('.sidebar-brand span');
                    
                    if (userInfo) userInfo.style.display = 'block';
                    menuTexts.forEach(span => span.style.display = 'inline');
                    if (brandText) brandText.style.display = 'inline';
                }
            };
            
            // Khởi tạo và đăng ký sự kiện resize
            handleResize();
            window.addEventListener('resize', handleResize);
        });
    </script>
</body>
</html>