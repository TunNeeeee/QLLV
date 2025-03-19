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

// Get upcoming appointments
$db->query("SELECT lg.*, gv.HoTen AS TenGiangVien, gv.HocVi 
           FROM LichGap lg
           JOIN GiangVien gv ON lg.GiangVienID = gv.GiangVienID
           WHERE lg.SinhVienID = :studentId AND lg.NgayGap >= CURDATE()
           ORDER BY lg.NgayGap ASC");
$db->bind(':studentId', $studentId);
$upcomingAppointments = $db->resultSet();

// Get past appointments
$db->query("SELECT lg.*, gv.HoTen AS TenGiangVien, gv.HocVi 
           FROM LichGap lg
           JOIN GiangVien gv ON lg.GiangVienID = gv.GiangVienID
           WHERE lg.SinhVienID = :studentId AND lg.NgayGap < CURDATE()
           ORDER BY lg.NgayGap DESC");
$db->bind(':studentId', $studentId);
$pastAppointments = $db->resultSet();

// Get recent notifications
$db->query("SELECT * FROM ThongBao WHERE UserID = :userId AND TrangThai = 'chưa đọc' ORDER BY NgayTao DESC LIMIT 5");
$db->bind(':userId', $_SESSION['user_id']);
$notifications = $db->resultSet();

// Count unread notifications
$db->query("SELECT COUNT(*) as count FROM ThongBao WHERE UserID = :userId AND TrangThai = 'chưa đọc'");
$db->bind(':userId', $_SESSION['user_id']);
$notificationCount = $db->single()['count'];

// Set current page for navbar
$currentPage = 'appointments';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch gặp - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Copy all CSS from dashboard.php */
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

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            margin-bottom: 1.5rem;
            transition: transform 0.2s, box-shadow 0.2s;
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

        .appointment-item {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            transition: background-color 0.2s;
        }

        .appointment-item:last-child {
            border-bottom: none;
        }

        .appointment-item:hover {
            background-color: var(--light-color);
        }

        .appointment-title {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .appointment-meta {
            font-size: 0.875rem;
            color: var(--light-text);
        }

        .appointment-status {
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-pending {
            background-color: rgba(243, 156, 18, 0.1);
            color: var(--warning-color);
        }

        .status-completed {
            background-color: rgba(46, 204, 113, 0.1);
            color: var(--success-color);
        }

        .status-cancelled {
            background-color: rgba(231, 76, 60, 0.1);
            color: var(--danger-color);
        }

        .empty-state {
            padding: 3rem;
            text-align: center;
        }

        .empty-state-icon {
            font-size: 3rem;
            color: var(--light-text);
            margin-bottom: 1rem;
        }

        .page-title {
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--text-color);
            font-size: 1.75rem;
        }

        .breadcrumb {
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include '../includes/student_navbar.php'; ?>

        <div class="container py-4">
            <!-- Page header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="page-title">Lịch gặp</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="dashboard.php"><i class="fas fa-home"></i></a></li>
                            <li class="breadcrumb-item active" aria-current="page">Lịch gặp</li>
                        </ol>
                    </nav>
                </div>
            </div>

            <!-- Upcoming appointments -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-calendar-alt me-2 text-primary"></i>
                        <span>Lịch gặp sắp tới</span>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (!empty($upcomingAppointments)): ?>
                        <?php foreach ($upcomingAppointments as $appointment): ?>
                            <div class="appointment-item">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <h5 class="appointment-title">
                                            <?php echo htmlspecialchars($appointment['TieuDe']); ?>
                                        </h5>
                                        <div class="appointment-meta">
                                            <p class="mb-1">
                                                <i class="fas fa-user-tie me-2"></i>
                                                <?php echo $appointment['HocVi'] . ' ' . $appointment['TenGiangVien']; ?>
                                            </p>
                                            <p class="mb-1">
                                                <i class="fas fa-map-marker-alt me-2"></i>
                                                <?php echo htmlspecialchars($appointment['DiaDiem']); ?>
                                            </p>
                                            <?php if (!empty($appointment['NoiDung'])): ?>
                                                <p class="mb-1">
                                                    <i class="fas fa-info-circle me-2"></i>
                                                    <?php echo htmlspecialchars($appointment['NoiDung']); ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="appointment-meta">
                                            <p class="mb-1">
                                                <i class="fas fa-calendar me-2"></i>
                                                <?php echo date('d/m/Y', strtotime($appointment['NgayGap'])); ?>
                                            </p>
                                            <p class="mb-1">
                                                <i class="fas fa-clock me-2"></i>
                                                <?php 
                                                    $datetime = new DateTime($appointment['NgayGap']);
                                                    echo $datetime->format('H:i'); 
                                                ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="col-md-2 text-end">
                                        <span class="appointment-status status-pending">
                                            <?php echo htmlspecialchars($appointment['TrangThai']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <h5>Không có lịch gặp sắp tới</h5>
                            <p class="text-muted">Bạn chưa có lịch hẹn gặp nào trong thời gian tới</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Past appointments -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-history me-2 text-secondary"></i>
                        <span>Lịch sử gặp</span>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (!empty($pastAppointments)): ?>
                        <?php foreach ($pastAppointments as $appointment): ?>
                            <div class="appointment-item">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <h5 class="appointment-title">
                                            <?php echo htmlspecialchars($appointment['TieuDe']); ?>
                                        </h5>
                                        <div class="appointment-meta">
                                            <p class="mb-1">
                                                <i class="fas fa-user-tie me-2"></i>
                                                <?php echo $appointment['HocVi'] . ' ' . $appointment['TenGiangVien']; ?>
                                            </p>
                                            <p class="mb-1">
                                                <i class="fas fa-map-marker-alt me-2"></i>
                                                <?php echo htmlspecialchars($appointment['DiaDiem']); ?>
                                            </p>
                                            <?php if (!empty($appointment['NoiDung'])): ?>
                                                <p class="mb-1">
                                                    <i class="fas fa-info-circle me-2"></i>
                                                    <?php echo htmlspecialchars($appointment['NoiDung']); ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="appointment-meta">
                                            <p class="mb-1">
                                                <i class="fas fa-calendar me-2"></i>
                                                <?php echo date('d/m/Y', strtotime($appointment['NgayGap'])); ?>
                                            </p>
                                            <p class="mb-1">
                                                <i class="fas fa-clock me-2"></i>
                                                <?php 
                                                    $datetime = new DateTime($appointment['NgayGap']);
                                                    echo $datetime->format('H:i'); 
                                                ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="col-md-2 text-end">
                                        <span class="appointment-status status-completed">
                                            <?php echo htmlspecialchars($appointment['TrangThai']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <i class="fas fa-history"></i>
                            </div>
                            <h5>Chưa có lịch sử gặp</h5>
                            <p class="text-muted">Bạn chưa có buổi gặp nào được ghi nhận</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="footer mt-auto py-3 bg-white border-top">
            <div class="container">
                <div class="row">
                    <div class="col-md-6">
                        <p class="text-muted mb-0">
                            &copy; <?php echo date('Y'); ?> - <?php echo SITE_NAME; ?> | Hệ thống quản lý hướng dẫn luận văn
                        </p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <ul class="list-inline mb-0">
                            <li class="list-inline-item">
                                <a href="../pages/privacy.php" class="text-muted">Chính sách bảo mật</a>
                            </li>
                            <li class="list-inline-item">
                                <a href="../pages/terms.php" class="text-muted">Điều khoản sử dụng</a>
                            </li>
                            <li class="list-inline-item">
                                <a href="../pages/contact.php" class="text-muted">Liên hệ</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 