<?php
// Bắt đầu output buffering ngay từ đầu
ob_start();

// Tắt hiển thị lỗi và thông báo
error_reporting(0);
ini_set('display_errors', 0);

// Đảm bảo không có output nào trước khi include các file
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../classes/Auth.php';
require_once '../classes/Faculty.php';

// Bắt đầu phiên nếu chưa được khởi tạo
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$auth = new Auth();

// Kiểm tra đăng nhập và vai trò
if (!$auth->isLoggedIn() || $auth->getUserRole() != 'faculty') {
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit;
}

$db = new Database();
$faculty = new Faculty($db);

// Lấy thông tin profile ID của giảng viên
if (!isset($_SESSION['profile_id'])) {
    $db->query("SELECT GiangVienID FROM GiangVien WHERE UserID = :userId");
    $db->bind(':userId', $_SESSION['user_id']);
    $result = $db->single();
    if ($result) {
        $_SESSION['profile_id'] = $result['GiangVienID'];
    } else {
        session_destroy();
        header('Location: ' . BASE_URL . 'auth/login.php?error=no_profile');
        exit;
    }
}

$facultyId = $_SESSION['profile_id'];

// Lấy thông tin giảng viên
$facultyDetails = $faculty->getFacultyDetails($facultyId);

// Lấy danh sách sinh viên được hướng dẫn
$assignedStudents = $faculty->getAssignedStudents($facultyId);

// Biến mặc định cho tiến độ sinh viên chưa có dữ liệu
$progressDefault = 0;

// Đếm số lượng sinh viên
$studentsCount = $faculty->countAssignedStudents($facultyId);

// Lấy danh sách đề tài
$theses = $faculty->getTheses($facultyId);
$thesesCount = count($theses);

// Đếm số lượng lịch gặp sắp tới
$upcomingMeetingsCount = 0;
try {
    // Kiểm tra xem bảng LichGap có tồn tại không
    $db->query("SHOW TABLES LIKE 'LichGap'");
    $tableExists = $db->rowCount() > 0;
    
    if (!$tableExists) {
        // Chỉ tạo bảng mới nếu chưa tồn tại
        $db->query("CREATE TABLE `LichGap` (
            `LichGapID` INT AUTO_INCREMENT PRIMARY KEY,
            `SinhVienID` INT NOT NULL,
            `GiangVienID` INT NOT NULL,
            `TieuDe` VARCHAR(255) NOT NULL,
            `NgayGap` DATETIME NOT NULL,
            `DiaDiem` VARCHAR(255) NOT NULL,
            `NoiDung` TEXT,
            `TrangThai` ENUM('đã lên lịch', 'đã xác nhận', 'đã hủy', 'đã hoàn thành') DEFAULT 'đã lên lịch',
            `GhiChu` TEXT,
            `NgayTao` DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`SinhVienID`) REFERENCES `SinhVien`(`SinhVienID`),
            FOREIGN KEY (`GiangVienID`) REFERENCES `GiangVien`(`GiangVienID`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
    
    // Truy vấn tất cả lịch gặp của giảng viên trong tương lai
    $db->query("SELECT lg.*, sv.HoTen as TenSinhVien, sv.MaSV  
               FROM LichGap lg
               LEFT JOIN SinhVien sv ON lg.SinhVienID = sv.SinhVienID
               WHERE lg.GiangVienID = :facultyId
               AND lg.NgayGap >= CURDATE()
               ORDER BY lg.NgayGap ASC");
    $db->bind(':facultyId', $facultyId);
    $allMeetings = $db->resultSet();
    
    // Gom nhóm các lịch gặp có cùng ngày và giờ
    $groupedMeetings = [];
    foreach ($allMeetings as $meeting) {
        $dateTime = $meeting['NgayGap'];
        if (!isset($groupedMeetings[$dateTime])) {
            $groupedMeetings[$dateTime] = [
                'NgayGap' => $meeting['NgayGap'],
                'TieuDe' => $meeting['TieuDe'],
                'DiaDiem' => $meeting['DiaDiem'],
                'students' => []
            ];
        }
        if (!empty($meeting['TenSinhVien'])) {
            $groupedMeetings[$dateTime]['students'][] = [
                'TenSinhVien' => $meeting['TenSinhVien'],
                'MaSV' => $meeting['MaSV']
            ];
        }
    }
    
    // Chuyển về dạng mảng tuần tự và sắp xếp theo thời gian
    $upcomingMeetings = array_values($groupedMeetings);
    usort($upcomingMeetings, function($a, $b) {
        return strtotime($a['NgayGap']) - strtotime($b['NgayGap']);
    });
    
    // Cập nhật số lượng lịch gặp sắp tới
    $upcomingMeetingsCount = count($upcomingMeetings);
} catch (PDOException $e) {
    $upcomingMeetings = [];
    $upcomingMeetingsCount = 0;
}

// Lấy thông báo mới nhất
try {
    $db->query("SHOW TABLES LIKE 'ThongBao'");
    $tableExists = $db->rowCount() > 0;
    
    if ($tableExists) {
        $db->query("SELECT COUNT(*) as total FROM ThongBao WHERE UserID = :userId AND DaDoc = 0");
        $db->bind(':userId', $_SESSION['user_id']);
        $result = $db->single();
        $unreadNotificationsCount = $result ? $result['total'] : 0;
        
        // Lấy 5 thông báo mới nhất
        $db->query("SELECT * FROM ThongBao 
                   WHERE UserID = :userId 
                   ORDER BY NgayTao DESC 
                   LIMIT 5");
        $db->bind(':userId', $_SESSION['user_id']);
        $recentNotifications = $db->resultSet();
    } else {
        $unreadNotificationsCount = 0;
        $recentNotifications = [];
    }
} catch (PDOException $e) {
    $unreadNotificationsCount = 0;
    $recentNotifications = [];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bảng điều khiển - Giảng viên - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
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
            min-height: 100vh;
        }
        
        /* App Container */
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
            box-shadow: 4px 0 10px rgba(0,0,0,0.1);
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
        
        .page-header {
            margin-bottom: 2rem;
        }
        
        .page-title {
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        /* Cards */
        .card {
            border: none;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding: 1rem 1.25rem;
        }
        
        .card-header h5 {
            font-weight: 600;
            margin: 0;
            color: #333;
        }
        
        /* Dashboard stats */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .stat-card {
            display: flex;
            align-items: center;
            padding: 1.25rem;
            border-radius: 8px;
            background-color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            font-size: 1.5rem;
        }
        
        .stat-content {
            flex-grow: 1;
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }
        
        .stat-label {
            color: var(--light-text);
            font-size: 0.875rem;
        }
        
        /* Tables */
        .table {
            margin-bottom: 0;
        }
        
        .table th {
            font-weight: 600;
            border-top: none;
            white-space: nowrap;
        }
        
        .table thead th {
            background-color: #f8f9fa;
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #e9ecef;
        }
        
        .table-light th {
            color: #6c757d;
            font-weight: 500;
        }
        
        .table td {
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid #e9ecef;
        }
        
        .table tbody tr:last-child td {
            border-bottom: none;
        }
        
        .badge {
            padding: 0.35em 0.65em;
            font-weight: 500;
        }
        
        /* Buttons */
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        
        .card-footer {
            background-color: #f8f9fa;
            padding: 0.75rem 1.25rem;
            border-top: 1px solid #e9ecef;
        }
        
        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 2rem;
        }
        
        .empty-state i {
            font-size: 3rem;
            color: #d1d3e2;
            margin-bottom: 1rem;
        }
        
        .empty-state h6 {
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        
        /* Footer */
        .footer {
            padding: 1rem 0;
            border-top: 1px solid #e9ecef;
            margin-top: 2rem;
            color: #6c757d;
            font-size: 0.875rem;
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
                    <?php echo strtoupper(substr($facultyDetails['HoTen'] ?? 'F', 0, 1)); ?>
                </div>
                <div class="sidebar-user-info">
                    <span class="sidebar-user-name"><?php echo htmlspecialchars($facultyDetails['HoTen']); ?></span>
                    <span class="sidebar-user-role"><?php echo htmlspecialchars($facultyDetails['HocVi']); ?></span>
                </div>
            </div>
            
            <ul class="sidebar-menu">
                <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> <span>Tổng quan</span></a></li>
                <li class="sidebar-item">
    <a href="assigned-students.php" class="sidebar-link <?php echo $currentPage == 'assigned-students' ? 'active' : ''; ?>">
        <i class="fas fa-user-graduate sidebar-icon"></i>
        <span>Sinh viên hướng dẫn</span>
    </a>
</li>
                
                <li><a href="manage-students.php"><i class="fas fa-users-cog"></i> <span>Quản lý sinh viên</span></a></li>
                <li><a href="thesis.php"><i class="fas fa-book"></i> <span>Đề tài</span></a></li>
                <li><a href="appointments.php"><i class="fas fa-calendar-alt"></i> <span>Lịch gặp</span></a></li>
                <li>
                    <a href="notifications.php">
                        <i class="fas fa-bell"></i> 
                        <span>Thông báo</span>
                        <?php if ($unreadNotificationsCount > 0): ?>
                            <span class="badge bg-danger rounded-pill ms-auto"><?php echo $unreadNotificationsCount; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li><a href="profile.php"><i class="fas fa-user-circle"></i> <span>Hồ sơ</span></a></li>
                <li><a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> <span>Đăng xuất</span></a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <div class="page-header">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h1 class="page-title">Xin chào, <?php echo htmlspecialchars($facultyDetails['HocVi'] . ' ' . $facultyDetails['HoTen']); ?></h1>
                        <p class="text-muted mb-0">Chào mừng bạn đến với hệ thống quản lý luận văn</p>
                    </div>
                    <a href="create-thesis.php" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Thêm đề tài mới
                    </a>
                </div>
            </div>
            
            <!-- Stats Section -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background-color: rgba(67, 97, 238, 0.1); color: #4361ee;">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $studentsCount; ?></div>
                        <div class="stat-label">Sinh viên hướng dẫn</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background-color: rgba(46, 204, 113, 0.1); color: #2ecc71;">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $thesesCount; ?></div>
                        <div class="stat-label">Đề tài luận văn</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background-color: rgba(243, 156, 18, 0.1); color: #f39c12;">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $upcomingMeetingsCount; ?></div>
                        <div class="stat-label">Lịch gặp sắp tới</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background-color: rgba(52, 152, 219, 0.1); color: #3498db;">
                        <i class="fas fa-bell"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $unreadNotificationsCount; ?></div>
                        <div class="stat-label">Thông báo mới</div>
                    </div>
                </div>
            </div>
            
            <!-- Main Content Section -->
            <div class="row">
                <!-- Student List -->
                <div class="col-lg-8">
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-user-graduate me-2 text-primary"></i>Sinh viên hướng dẫn
                            </h5>
                            <a href="assigned-students.php" class="btn btn-sm btn-primary">Xem tất cả</a>
                        </div>
                        
                        <div class="card-body p-0">
                            <?php if (!empty($assignedStudents)): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Sinh viên</th>
                                                <th>Đề tài</th>
                                                <th>Trạng thái</th>
                                                <th>Thao tác</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $displayStudents = array_slice($assignedStudents, 0, 5);
                                            foreach ($displayStudents as $student): 
                                            ?>
                                            <tr>
                                                <td>
                                                    <div>
                                                        <div class="fw-bold"><?php echo htmlspecialchars($student['HoTen']); ?></div>
                                                        <div class="small text-muted"><?php echo htmlspecialchars($student['MaSV']); ?></div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php if (!empty($student['TenDeTai'])): ?>
                                                        <?php echo htmlspecialchars(mb_strimwidth($student['TenDeTai'], 0, 30, '...')); ?>
                                                    <?php else: ?>
                                                        <span class="text-warning fw-bold">Chưa có đề tài</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (!empty($student['TrangThai'])): ?>
                                                        <?php
                                                        $badgeClass = 'bg-primary';
                                                        if ($student['TrangThai'] == 'Hoàn thành') {
                                                            $badgeClass = 'bg-success';
                                                        } elseif ($student['TrangThai'] == 'Chờ phê duyệt') {
                                                            $badgeClass = 'bg-warning text-dark';
                                                        }
                                                        ?>
                                                        <span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($student['TrangThai']); ?></span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Chưa bắt đầu</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="student-details.php?id=<?php echo $student['SinhVienID']; ?>" class="btn btn-outline-primary" title="Xem chi tiết">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <?php if (empty($student['TenDeTai'])): ?>
                                                        <a href="assign-thesis.php?student_id=<?php echo $student['SinhVienID']; ?>" class="btn btn-outline-success" title="Gán đề tài">
                                                            <i class="fas fa-plus"></i>
                                                        </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <?php if (count($assignedStudents) > 5): ?>
                                <div class="card-footer text-center py-2">
                                    <a href="assigned-students.php" class="text-decoration-none">
                                        <span>Xem tất cả <?php echo count($assignedStudents); ?> sinh viên</span>
                                        <i class="fas fa-chevron-right ms-1"></i>
                                    </a>
                                </div>
                                <?php endif; ?>
                                
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-users"></i>
                                    <h6>Không có sinh viên hướng dẫn</h6>
                                    <p class="text-muted">Bạn chưa được phân công hướng dẫn sinh viên nào.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Đề tài luận văn -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-book me-2 text-success"></i>Đề tài luận văn
                            </h5>
                            <a href="thesis.php" class="btn btn-sm btn-success">Quản lý đề tài</a>
                        </div>
                        <div class="card-body p-0">
                            <?php if (count($theses) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Tên đề tài</th>
                                            <th>Sinh viên thực hiện</th>
                                            <th>Trạng thái</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (array_slice($theses, 0, 3) as $thesis): ?>
                                        <tr>
                                            <td>
                                                <a href="thesis-detail.php?id=<?php echo $thesis['DeTaiID']; ?>" class="text-decoration-none fw-bold">
                                                    <?php echo htmlspecialchars(mb_strimwidth($thesis['TenDeTai'], 0, 40, '...')); ?>
                                                </a>
                                            </td>
                                            <td><?php echo $thesis['SoLuongSinhVien']; ?></td>
                                            <td>
                                                <?php
                                                $badgeClass = 'bg-primary';
                                                if ($thesis['TrangThai'] == 'Hoàn thành') {
                                                    $badgeClass = 'bg-success';
                                                } elseif ($thesis['TrangThai'] == 'Chờ phê duyệt') {
                                                    $badgeClass = 'bg-warning text-dark';
                                                }
                                                ?>
                                                <span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($thesis['TrangThai']); ?></span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-book"></i>
                                <h6>Chưa có đề tài nào</h6>
                                <p class="text-muted">Hãy tạo đề tài mới để bắt đầu hướng dẫn sinh viên.</p>
                                <a href="create-thesis.php" class="btn btn-sm btn-primary mt-2">
                                    <i class="fas fa-plus me-1"></i> Thêm đề tài mới
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Side Content -->
                <div class="col-lg-4">
                    <!-- Lịch gặp sắp tới -->
                    <div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-calendar-alt me-2 text-warning"></i>Lịch gặp sắp tới
        </h5>
        <a href="appointments.php" class="btn btn-sm btn-warning text-dark">Tất cả</a>
    </div>
    <!-- Phần hiển thị lịch gặp trong card -->
<div class="card-body">
    <?php if (!empty($upcomingMeetings)): ?>
        <ul class="list-group list-group-flush">
            <?php foreach ($upcomingMeetings as $meeting): ?>
            <li class="list-group-item px-0">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="fw-bold">
                            <?php echo !empty($meeting['TieuDe']) ? htmlspecialchars($meeting['TieuDe']) : 'Lịch gặp'; ?>
                        </div>
                        <?php if (!empty($meeting['students'])): ?>
                            <div class="small text-muted">
                                <i class="fas fa-users me-1"></i>
                                <?php 
                                $studentNames = array_map(function($student) {
                                    return htmlspecialchars($student['TenSinhVien']);
                                }, $meeting['students']);
                                echo implode(', ', $studentNames);
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="text-end">
                        <div class="small fw-bold">
                            <?php echo date('d/m/Y', strtotime($meeting['NgayGap'])); ?>
                        </div>
                        <div class="small text-muted">
                            <?php echo date('H:i', strtotime($meeting['NgayGap'])); ?> | 
                            <?php echo htmlspecialchars($meeting['DiaDiem']); ?>
                        </div>
                    </div>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <div class="text-center py-3">
            <i class="fas fa-calendar-day text-muted mb-2" style="font-size: 2rem;"></i>
            <p class="mb-0">Không có lịch gặp sắp tới</p>
            <a href="schedule-meeting.php" class="btn btn-sm btn-primary mt-2">
                <i class="fas fa-plus me-1"></i> Tạo lịch gặp mới
            </a>
        </div>
    <?php endif; ?>
</div>

</div>

                    
                    <!-- Thông báo gần đây -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-bell me-2 text-info"></i>Thông báo gần đây
                            </h5>
                            <a href="notifications.php" class="btn btn-sm btn-info text-white">Tất cả</a>
                        </div>
                        <div class="card-body">
                            <?php if (count($recentNotifications) > 0): ?>
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($recentNotifications as $notification): ?>
                                    <li class="list-group-item px-0">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <div class="fw-bold"><?php echo htmlspecialchars($notification['TieuDe']); ?></div>
                                            <small class="text-muted">
                                                <?php echo date('d/m/Y', strtotime($notification['NgayTao'])); ?>
                                            </small>
                                        </div>
                                        <div class="small text-muted">
                                            <?php echo htmlspecialchars(mb_strimwidth($notification['NoiDung'], 0, 100, '...')); ?>
                                        </div>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <div class="text-center py-3">
                                    <i class="fas fa-bell-slash text-muted mb-2" style="font-size: 2rem;"></i>
                                    <p class="mb-0">Không có thông báo mới</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <footer class="footer">
                <div class="d-flex justify-content-between align-items-center">
                    <div>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</div>
                </div>
            </footer>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Hiệu ứng hover cho các hàng trong bảng
        $(document).ready(function() {
            $(".table-hover tr").hover(
                function() {
                    $(this).css("background-color", "rgba(0,0,0,0.03");
                },
                function() {
                    $(this).css("background-color", "");
                }
            );
        });
    </script>
</body>
</html>
<?php
$output = ob_get_clean();

// Xóa các thông báo debug và <br> tags
$output = preg_replace('/^(?:<br>)*/', '', $output);
$output = preg_replace('/Đã cập nhật .*?trong (?:bảng|dữ liệu).*?\.<br>/', '', $output);

echo $output;
?>