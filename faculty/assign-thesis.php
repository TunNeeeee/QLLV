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
if (!$auth->isLoggedIn() || $auth->getUserRole() != 'faculty') {
    header('Location: ' . BASE_URL . 'auth/login.php?error=unauthorized_access');
    exit;
}

$db = new Database();
$userId = $_SESSION['user_id'];
$success = '';
$error = '';
$currentPage = 'assign'; // Đánh dấu trang hiện tại cho menu sidebar
$gvColumn = 'GiangVienID'; // Sử dụng GiangVienID làm mặc định

// Lấy thông tin giảng viên
try {
    $db->query("SELECT * FROM GiangVien WHERE UserID = :userId");
    $db->bind(':userId', $userId);
    $facultyDetails = $db->single();

    if (!$facultyDetails) {
        header('Location: ' . BASE_URL . 'auth/logout.php');
        exit;
    }

    $facultyId = $facultyDetails['GiangVienID']; // Sử dụng GiangVienID từ bảng GiangVien
} catch (PDOException $e) {
    $error = "Lỗi khi lấy thông tin giảng viên: " . $e->getMessage();
}

// Kiểm tra tồn tại và cấu trúc bảng
try {
    // Kiểm tra xem bảng SinhVienGiangVienHuongDan có tồn tại không
    $db->query("SHOW TABLES LIKE 'SinhVienGiangVienHuongDan'");
    $tableExists = $db->rowCount() > 0;
    
    if (!$tableExists) {
        // Nếu bảng chưa tồn tại, tạo mới với cột GiangVienID
        $db->query("CREATE TABLE IF NOT EXISTS `SinhVienGiangVienHuongDan` (
            `ID` INT AUTO_INCREMENT PRIMARY KEY,
            `SinhVienID` INT NOT NULL,
            `GiangVienID` INT NOT NULL,
            `DeTaiID` INT DEFAULT NULL,
            `GhiChu` TEXT,
            `NgayTao` DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`SinhVienID`) REFERENCES `SinhVien`(`SinhVienID`),
            FOREIGN KEY (`GiangVienID`) REFERENCES `GiangVien`(`GiangVienID`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $db->execute();
        $success = "Đã tạo bảng SinhVienGiangVienHuongDan mới.";
    } else {
        // Kiểm tra GiangVienID tồn tại
        $db->query("SHOW COLUMNS FROM `SinhVienGiangVienHuongDan` LIKE 'GiangVienID'");
        $giangVienIDExists = $db->rowCount() > 0;
        
        // Kiểm tra id_giangvien để đổi tên nếu cần
        $db->query("SHOW COLUMNS FROM `SinhVienGiangVienHuongDan` LIKE 'id_giangvien'");
        $idGiangVienExists = $db->rowCount() > 0;
        
        if (!$giangVienIDExists && $idGiangVienExists) {
            // Đổi tên cột từ id_giangvien thành GiangVienID
            $db->query("ALTER TABLE `SinhVienGiangVienHuongDan` CHANGE COLUMN `id_giangvien` `GiangVienID` INT NOT NULL");
            $db->execute();
            $success = "Đã đổi tên cột id_giangvien thành GiangVienID.";
        } else if (!$giangVienIDExists && !$idGiangVienExists) {
            // Nếu không có cột nào, thêm GiangVienID
            $db->query("ALTER TABLE `SinhVienGiangVienHuongDan` ADD COLUMN `GiangVienID` INT NOT NULL AFTER `SinhVienID`");
            $db->execute();
            $success = "Đã thêm cột GiangVienID vào bảng SinhVienGiangVienHuongDan.";
        }
    }
    
    // Luôn sử dụng GiangVienID sau khi xử lý
    $gvColumn = 'GiangVienID';
    
} catch (Exception $e) {
    $error = "Lỗi khi kiểm tra cấu trúc bảng: " . $e->getMessage();
}

// Thêm sau phần kiểm tra cấu trúc bảng
if (isset($idGiangVienExists) && isset($giangVienIDExists) && $idGiangVienExists && !$giangVienIDExists) {
    try {
        // Đổi tên cột id_giangvien thành GiangVienID
        $db->query("ALTER TABLE `SinhVienGiangVienHuongDan` 
                  CHANGE COLUMN `id_giangvien` `GiangVienID` INT NOT NULL");
        $db->execute();
        
        // Cập nhật tên cột sau khi đổi
        $gvColumn = 'GiangVienID';
        
        $success .= " Đã đổi tên cột id_giangvien thành GiangVienID trong bảng SinhVienGiangVienHuongDan.";
    } catch (PDOException $e) {
        $error = "Lỗi khi cập nhật cấu trúc bảng: " . $e->getMessage();
    }
}

// Lấy sinh viên được chọn
$selectedStudent = null;
$studentId = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;

if ($studentId > 0) {
    try {
        // Đảm bảo $gvColumn có giá trị
        if (!isset($gvColumn) || empty($gvColumn)) {
            $gvColumn = 'GiangVienID';  // Giá trị mặc định
        }
        
        // Hiển thị cột đang sử dụng (để debug)
        //echo "Đang sử dụng cột: " . $gvColumn;
        
        // Kiểm tra xem sinh viên có thuộc về giảng viên này không
        $sql = "SELECT sv.*, svgv.ID as AssignmentID 
                FROM SinhVien sv
                JOIN SinhVienGiangVienHuongDan svgv ON sv.SinhVienID = svgv.SinhVienID
                WHERE svgv.{$gvColumn} = :facultyId AND sv.SinhVienID = :studentId";
        
        $db->query($sql);
        $db->bind(':facultyId', $facultyId);
        $db->bind(':studentId', $studentId);
        $selectedStudent = $db->single();

        if (!$selectedStudent) {
            $error = "Sinh viên không tồn tại hoặc không thuộc quyền hướng dẫn của bạn!";
        }
    } catch (PDOException $e) {
        $error = "Lỗi khi lấy thông tin sinh viên: " . $e->getMessage();
    }
}

// Lấy danh sách đề tài có sẵn của giảng viên
$theses = [];
try {
    $db->query("SELECT * FROM DeTai 
                WHERE GiangVienID = :facultyId 
                AND TrangThai IN ('đề xuất', 'được duyệt') 
                ORDER BY NgayTao DESC");
    $db->bind(':facultyId', $facultyId);
    $theses = $db->resultSet();
} catch (PDOException $e) {
    $error = "Lỗi khi lấy danh sách đề tài: " . $e->getMessage();
}

// Lấy danh sách tất cả đề tài do giảng viên này tạo
try {
    $db->query("SELECT * FROM DeTai 
                WHERE GiangVienID = :giangVienId 
                AND TrangThai NOT IN ('hoàn thành', 'hủy')
                ORDER BY NgayTao DESC");
    $db->bind(':giangVienId', $facultyId);
    $thesisList = $db->resultSet();
} catch (PDOException $e) {
    $error = "Lỗi khi lấy danh sách đề tài: " . $e->getMessage();
}

// Xử lý gán đề tài cho sinh viên
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_thesis'])) {
    $thesisId = $_POST['thesis_id'];
    $assignmentId = $_POST['assignment_id'];
    $notes = isset($_POST['notes']) ? $_POST['notes'] : '';

    if (empty($thesisId)) {
        $error = "Vui lòng chọn đề tài!";
    } else {
        try {
            // Kiểm tra đề tài có tồn tại không
            $db->query("SELECT * FROM DeTai WHERE DeTaiID = :thesisId AND GiangVienID = :facultyId");
            $db->bind(':thesisId', $thesisId);
            $db->bind(':facultyId', $facultyId);
            $thesis = $db->single();

            if (!$thesis) {
                $error = "Đề tài không tồn tại hoặc không thuộc về bạn!";
            } else {
                // Kiểm tra xem cột DeTaiID có tồn tại trong bảng SinhVienGiangVienHuongDan không
                try {
                    // Sử dụng INFORMATION_SCHEMA để kiểm tra chính xác hơn
                    $db->query("SELECT COLUMN_NAME 
                                FROM INFORMATION_SCHEMA.COLUMNS 
                                WHERE TABLE_NAME = 'SinhVienGiangVienHuongDan' 
                                AND COLUMN_NAME = 'DeTaiID'");
                    $detaiColumnExists = $db->rowCount() > 0;
                    
                    if (!$detaiColumnExists) {
                        // Sử dụng IF NOT EXISTS để tránh lỗi nếu cột đã tồn tại
                        $db->query("ALTER TABLE SinhVienGiangVienHuongDan ADD COLUMN IF NOT EXISTS DeTaiID INT DEFAULT NULL");
                        $db->execute();
                    }
                    
                    // Tương tự cho cột GhiChu
                    $db->query("SELECT COLUMN_NAME 
                                FROM INFORMATION_SCHEMA.COLUMNS 
                                WHERE TABLE_NAME = 'SinhVienGiangVienHuongDan' 
                                AND COLUMN_NAME = 'GhiChu'");
                    $ghiChuColumnExists = $db->rowCount() > 0;
                    
                    if (!$ghiChuColumnExists) {
                        $db->query("ALTER TABLE SinhVienGiangVienHuongDan ADD COLUMN IF NOT EXISTS GhiChu TEXT");
                        $db->execute();
                    }
                    
                } catch (PDOException $e) {
                    $error = "Lỗi khi kiểm tra cấu trúc bảng: " . $e->getMessage();
                    // Tiếp tục xử lý mặc dù có lỗi
                }
                
                // Cập nhật bảng SinhVienGiangVienHuongDan
                $db->query("UPDATE SinhVienGiangVienHuongDan 
                            SET DeTaiID = :thesisId, GhiChu = :notes 
                            WHERE ID = :assignmentId AND $gvColumn = :facultyId");
                $db->bind(':thesisId', $thesisId);
                $db->bind(':notes', $notes);
                $db->bind(':assignmentId', $assignmentId);
                $db->bind(':facultyId', $facultyId);
                
                if ($db->execute()) {
                    // Cập nhật trạng thái đề tài
                    $db->query("UPDATE DeTai SET TrangThai = 'đang thực hiện' WHERE DeTaiID = :thesisId");
                    $db->bind(':thesisId', $thesisId);
                    $db->execute();
    
                    // Gửi thông báo cho sinh viên
                    $db->query("SELECT sv.HoTen, sv.SinhVienID, u.UserID 
                                FROM SinhVien sv 
                                JOIN Users u ON sv.UserID = u.UserID 
                                JOIN SinhVienGiangVienHuongDan svgv ON sv.SinhVienID = svgv.SinhVienID 
                                WHERE svgv.ID = :assignmentId");
                    $db->bind(':assignmentId', $assignmentId);
                    $student = $db->single();
    
                    if ($student) {
                        try {
                            // Tạo bảng ThongBao nếu chưa có
                            $db->query("CREATE TABLE IF NOT EXISTS `ThongBao` (
                                `ThongBaoID` int(11) NOT NULL AUTO_INCREMENT,
                                `UserID` int(11) NOT NULL,
                                `TieuDe` varchar(255) NOT NULL,
                                `NoiDung` text NOT NULL,
                                `DaDoc` tinyint(1) DEFAULT 0,
                                `NgayTao` timestamp NOT NULL DEFAULT current_timestamp(),
                                `LoaiThongBao` varchar(50) DEFAULT NULL,
                                `LienKet` varchar(255) DEFAULT NULL,
                                PRIMARY KEY (`ThongBaoID`),
                                KEY `UserID` (`UserID`)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                            $db->execute();
                            
                            // Thêm thông báo vào bảng ThongBao
                            $db->query("INSERT INTO ThongBao 
                                       (UserID, TieuDe, NoiDung, LoaiThongBao, LienKet) 
                                       VALUES 
                                       (:userId, :title, :content, 'đề tài', 'student/thesis.php')");
                            $db->bind(':userId', $student['UserID']);
                            $db->bind(':title', 'Được gán đề tài mới');
                            $db->bind(':content', 'Bạn đã được gán đề tài: ' . $thesis['TenDeTai'] . '. Vui lòng xem chi tiết và liên hệ với giảng viên hướng dẫn để tiếp tục.');
                            $db->execute();
                            
                        } catch (PDOException $e) {
                            // Bỏ qua lỗi khi gửi thông báo, vẫn tiếp tục quá trình gán đề tài
                        }
                    }
    
                    $success = "Đã gán đề tài thành công cho sinh viên!";
                    
                    // Cập nhật lại thông tin sinh viên
                    $db->query("SELECT sv.*, svgv.ID as AssignmentID, dt.TenDeTai, dt.DeTaiID
                                FROM SinhVien sv
                                JOIN SinhVienGiangVienHuongDan svgv ON sv.SinhVienID = svgv.SinhVienID
                                LEFT JOIN DeTai dt ON svgv.DeTaiID = dt.DeTaiID
                                WHERE svgv.$gvColumn = :facultyId AND sv.SinhVienID = :studentId");
                    $db->bind(':facultyId', $facultyId);
                    $db->bind(':studentId', $studentId);
                    $selectedStudent = $db->single();
                } else {
                    $error = "Có lỗi xảy ra khi gán đề tài. Vui lòng thử lại.";
                }
            }
        } catch (PDOException $e) {
            $error = "Lỗi khi gán đề tài: " . $e->getMessage();
        }
    }
}

// Tiêu đề và thông tin trang
$pageTitle = "Gán đề tài luận văn";
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo SITE_NAME; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    
    <!-- jQuery (cần đặt trước Bootstrap) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
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
        
        /* Card */
        .card {
            border-radius: 10px;
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            background-color: #fff;
            border-bottom: 1px solid #f5f5f5;
            padding: 1.25rem 1.5rem;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
        }
        
        .card-title {
            margin-bottom: 0;
            font-weight: 600;
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        /* Media queries */
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
        }
        
        /* Avatar sizes */
        .avatar-lg {
            width: 64px;
            height: 64px;
        }
        
        .avatar-sm {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        /* Card hover effects */
        .hover-shadow:hover {
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            transform: translateY(-2px);
            transition: all 0.3s ease;
        }
        
        /* Progress bars */
        .progress {
            height: 10px;
            border-radius: 10px;
            background-color: #e9ecef;
        }
        
        .progress-bar {
            border-radius: 10px;
        }
        
        /* Badge styles */
        .badge {
            padding: 0.5em 0.8em;
            font-weight: 500;
        }
        
        /* Animation for loading */
        @keyframes pulse {
            0% { opacity: 0.5; }
            50% { opacity: 1; }
            100% { opacity: 0.5; }
        }
        
        .pulse {
            animation: pulse 1.5s infinite ease-in-out;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <!-- HUTECH Logo -->
            <div class="sidebar-header">
                <a href="dashboard.php" class="sidebar-brand">
                    <i class="fas fa-graduation-cap me-2"></i>
                    <span>HUTECH - LUẬN VĂN</span>
                </a>
            </div>
            
            <!-- User Info -->
            <div class="sidebar-user">
                <div class="sidebar-user-img">
                    <?php 
                    // Hiển thị avatar hoặc chữ cái đầu của tên
                    if (isset($facultyDetails) && !empty($facultyDetails)) {
                        if (isset($facultyDetails['Avatar']) && !empty($facultyDetails['Avatar'])) {
                            echo '<img src="' . $facultyDetails['Avatar'] . '" alt="Avatar">';
                        } else {
                            echo substr($facultyDetails['HoTen'] ?? 'U', 0, 1);
                        }
                    } else {
                        echo 'U';
                    }
                    ?>
                </div>
                <div class="sidebar-user-info">
                    <span class="sidebar-user-name"><?php echo $facultyDetails['HoTen'] ?? 'Giảng viên'; ?></span>
                    <span class="sidebar-user-role">Giảng viên</span>
                </div>
            </div>
            
            <!-- Menu Items -->
            <ul class="sidebar-menu">
                <li>
                    <a href="dashboard.php" class="<?php echo $currentPage == 'dashboard' ? 'active' : ''; ?>">
                        <i class="fas fa-home"></i>
                        <span>Trang chủ</span>
                    </a>
                </li>
                <li>
                    <a href="theses.php" class="<?php echo $currentPage == 'theses' ? 'active' : ''; ?>">
                        <i class="fas fa-clipboard-list"></i>
                        <span>Đề tài luận văn</span>
                    </a>
                </li>
                <li>
                    <a href="assigned-students.php" class="<?php echo $currentPage == 'students' ? 'active' : ''; ?>">
                        <i class="fas fa-user-graduate"></i>
                        <span>Sinh viên</span>
                    </a>
                </li>
                <li>
                    <a href="assign-thesis.php" class="<?php echo $currentPage == 'assign' ? 'active' : ''; ?>">
                        <i class="fas fa-tasks"></i>
                        <span>Gán đề tài</span>
                    </a>
                </li>
                <li>
                    <a href="appointments.php" class="<?php echo $currentPage == 'appointments' ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Lịch gặp</span>
                    </a>
                </li>
                <li>
                    <a href="messages.php" class="<?php echo $currentPage == 'messages' ? 'active' : ''; ?>">
                        <i class="fas fa-comments"></i>
                        <span>Tin nhắn</span>
                    </a>
                </li>
                <li>
                    <a href="profile.php" class="<?php echo $currentPage == 'profile' ? 'active' : ''; ?>">
                        <i class="fas fa-user-circle"></i>
                        <span>Hồ sơ</span>
                    </a>
                </li>
                <li>
                    <a href="../auth/logout.php">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Đăng xuất</span>
                    </a>
                </li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
<div class="container-fluid p-0">
                <!-- Page Title and Breadcrumb -->
                <div class="row mb-4">
        <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h1 class="h3 mb-0 text-gray-800"><?php echo $pageTitle; ?></h1>
                                <p class="mb-0 text-muted">Chọn đề tài phù hợp để gán cho sinh viên</p>
                            </div>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb mb-0 bg-transparent">
                                    <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Trang chủ</a></li>
                                    <li class="breadcrumb-item"><a href="assigned-students.php" class="text-decoration-none">Sinh viên hướng dẫn</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Gán đề tài</li>
                    </ol>
                            </nav>
            </div>
        </div>
    </div>

                <!-- Alert Messages -->
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-exclamation-circle flex-shrink-0 me-2"></i>
                            <div><?php echo $error; ?></div>
                        </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-check-circle flex-shrink-0 me-2"></i>
                            <div><?php echo $success; ?></div>
                        </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <?php if ($selectedStudent): ?>
            <!-- Thông tin sinh viên -->
            <div class="col-lg-4">
                            <div class="card hover-shadow">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-user-graduate me-2 text-primary"></i>Thông tin sinh viên
                                    </h5>
                                    <a href="student-details.php?id=<?php echo $selectedStudent['SinhVienID']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i> Xem chi tiết
                                    </a>
                    </div>
                    <div class="card-body">
                                    <div class="text-center mb-4">
                                        <div class="avatar-lg mx-auto mb-3 rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center">
                                            <span class="text-primary display-6">
                                    <?php echo strtoupper(substr($selectedStudent['HoTen'], 0, 1)); ?>
                                </span>
                            </div>
                                        <h4 class="mb-1"><?php echo htmlspecialchars($selectedStudent['HoTen']); ?></h4>
                                        <p class="text-muted mb-0">
                                            <span class="badge bg-info">
                                                <i class="fas fa-id-card me-1"></i>
                                                <?php echo htmlspecialchars($selectedStudent['MaSV']); ?>
                                            </span>
                                        </p>
                        </div>
                        
                                    <div class="border-top pt-3">
                        <div class="table-responsive">
                            <table class="table table-borderless mb-0">
                                <tbody>
                                    <tr>
                                                        <td class="text-muted" width="30%">
                                                            <i class="fas fa-graduation-cap me-1"></i> Ngành học:
                                                        </td>
                                                        <td class="fw-medium"><?php echo htmlspecialchars($selectedStudent['NganhHoc'] ?? 'Chưa cập nhật'); ?></td>
                                    </tr>
                                    <tr>
                                                        <td class="text-muted">
                                                            <i class="fas fa-university me-1"></i> Khoa:
                                                        </td>
                                                        <td class="fw-medium"><?php echo htmlspecialchars($selectedStudent['Khoa'] ?? 'Chưa cập nhật'); ?></td>
                                    </tr>
                                    <tr>
                                                        <td class="text-muted">
                                                            <i class="fas fa-envelope me-1"></i> Email:
                                                        </td>
                                                        <td class="fw-medium"><?php echo htmlspecialchars($selectedStudent['Email'] ?? 'Chưa cập nhật'); ?></td>
                                    </tr>
                                    <tr>
                                                        <td class="text-muted">
                                                            <i class="fas fa-phone me-1"></i> SĐT:
                                                        </td>
                                                        <td class="fw-medium"><?php echo htmlspecialchars($selectedStudent['SoDienThoai'] ?? 'Chưa cập nhật'); ?></td>
                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="card-footer bg-light">
                                    <h6 class="mb-2">Trạng thái đề tài</h6>
                                            <?php if (!empty($selectedStudent['TenDeTai'])): ?>
                                        <div class="alert alert-success mb-0 d-flex align-items-center">
                                            <i class="fas fa-check-circle me-2"></i>
                                            <div>
                                                <strong>Đã có đề tài:</strong><br>
                                                <a href="thesis-details.php?id=<?php echo $selectedStudent['DeTaiID']; ?>" class="text-decoration-none">
                                                    <?php echo htmlspecialchars($selectedStudent['TenDeTai']); ?>
                                                </a>
                                            </div>
                                        </div>
                                            <?php else: ?>
                                        <div class="alert alert-warning mb-0 d-flex align-items-center">
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            <div>
                                                <strong>Chưa có đề tài</strong><br>
                                                Hãy gán đề tài cho sinh viên
                                            </div>
                                        </div>
                                            <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Form gán đề tài -->
                        <div class="col-lg-8">
                            <div class="card hover-shadow">
                                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-clipboard-list me-2 text-primary"></i>Gán đề tài
                                    </h5>
                                    <a href="create-thesis.php" class="btn btn-sm btn-primary">
                                        <i class="fas fa-plus me-1"></i> Tạo đề tài mới
                                    </a>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($selectedStudent['TenDeTai'])): ?>
                                        <div class="alert alert-info border-0 d-flex">
                                            <i class="fas fa-info-circle me-2 fs-4"></i>
                                            <div>
                                                <strong>Lưu ý:</strong> Sinh viên này đã được gán đề tài. 
                                                Việc gán đề tài mới sẽ thay thế đề tài hiện tại.
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (count($theses) > 0): ?>
                                        <form method="post" action="" id="assignThesisForm">
                                            <input type="hidden" name="assignment_id" value="<?php echo $selectedStudent['AssignmentID']; ?>">
                                            
                                            <div class="mb-4">
                                                <label for="thesis_id" class="form-label fw-bold">
                                                    <i class="fas fa-book me-1"></i> Chọn đề tài
                                                </label>
                                                <select class="form-select form-select-lg select2" id="thesis_id" name="thesis_id" required>
                                                    <option value="">-- Chọn đề tài --</option>
                                                    <?php foreach ($theses as $thesis): ?>
                                                    <option value="<?php echo $thesis['DeTaiID']; ?>" data-thesis='<?php echo json_encode($thesis); ?>'>
                                                        <?php echo htmlspecialchars($thesis['TenDeTai']); ?>
                                                        (<?php echo htmlspecialchars($thesis['TrangThai']); ?>)
                                                    </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <div class="form-text">Chọn đề tài phù hợp với sinh viên từ danh sách</div>
                                            </div>

                                            <div id="thesis_details" class="mb-4 p-3 border rounded bg-light d-none">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <h5 class="thesis-title fw-bold mb-0"></h5>
                                                    <span class="thesis-status badge"></span>
                                                </div>
                                                <div class="thesis-description mb-3 text-muted"></div>
                                                <div class="d-flex justify-content-between text-muted small">
                                                    <div>
                                                        <i class="fas fa-tag me-1"></i> <span class="thesis-field"></span>
                                                    </div>
                                                    <div>
                                                        <i class="fas fa-calendar-alt me-1"></i> <span class="thesis-date"></span>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="mb-4">
                                                <label for="notes" class="form-label fw-bold">
                                                    <i class="fas fa-sticky-note me-1"></i> Ghi chú
                                                </label>
                                                <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Nhập ghi chú cho sinh viên về đề tài này (nếu có)"></textarea>
                                                <div class="form-text">Ghi chú có thể chứa yêu cầu, chỉ dẫn, hoặc thông tin bổ sung về đề tài</div>
                                            </div>
                                            
                                            <div class="d-flex justify-content-between align-items-center">
                                                <a href="assigned-students.php" class="btn btn-outline-secondary">
                                                    <i class="fas fa-arrow-left me-1"></i> Quay lại
                                                </a>
                                                <button type="submit" name="assign_thesis" class="btn btn-success">
                                                    <i class="fas fa-check-circle me-1"></i> Gán đề tài
                                                </button>
                                            </div>
                                        </form>
                                    <?php else: ?>
                                        <div class="text-center py-5">
                                            <div class="mb-3">
                                                <i class="fas fa-clipboard text-muted" style="font-size: 4rem;"></i>
                                            </div>
                                            <h4 class="mb-2">Không có đề tài nào khả dụng</h4>
                                            <p class="text-muted mb-4">Bạn chưa có đề tài nào hoặc tất cả đề tài đã được gán cho sinh viên.</p>
                                            <a href="create-thesis.php" class="btn btn-primary">
                                                <i class="fas fa-plus me-1"></i> Tạo đề tài mới
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if(count($theses) > 0): ?>
                            <div class="card mt-4">
                                <div class="card-header bg-white">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-clipboard-list me-2 text-primary"></i>Danh sách đề tài có sẵn
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover" id="thesisTable">
                                            <thead>
                                                <tr>
                                                    <th>Tên đề tài</th>
                                                    <th>Lĩnh vực</th>
                                                    <th>Trạng thái</th>
                                                    <th>Ngày tạo</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach($theses as $thesis): ?>
                                                <tr class="thesis-row" data-id="<?php echo $thesis['DeTaiID']; ?>">
                                                    <td>
                                                        <a href="#" class="thesis-select-link text-decoration-none fw-medium">
                                                            <?php echo htmlspecialchars($thesis['TenDeTai']); ?>
                                                        </a>
                                        </td>
                                                    <td><?php echo htmlspecialchars($thesis['LinhVuc'] ?? 'Chưa cập nhật'); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php 
                                                        switch($thesis['TrangThai']) {
                                                            case 'đề xuất': echo 'info'; break;
                                                            case 'được duyệt': echo 'warning'; break;
                                                            case 'đang thực hiện': echo 'primary'; break;
                                                            case 'hoàn thành': echo 'success'; break;
                                                            case 'hủy': echo 'danger'; break;
                                                            default: echo 'secondary';
                                                        }
                                                        ?>">
                                                            <?php echo htmlspecialchars($thesis['TrangThai']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo date('d/m/Y', strtotime($thesis['NgayTao'])); ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="col-12">
                <div class="card text-center py-5">
                    <div class="card-body">
                        <div class="mb-4">
                            <i class="fas fa-user-graduate text-muted mb-3" style="font-size: 5rem;"></i>
                        </div>
                        <h3 class="mb-3">Không tìm thấy sinh viên</h3>
                        <p class="text-muted mb-4">Sinh viên không tồn tại hoặc không thuộc quyền hướng dẫn của bạn.</p>
                        <a href="assigned-students.php" class="btn btn-primary">
                            <i class="fas fa-arrow-left me-1"></i> Quay lại danh sách sinh viên
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal tạo đề tài -->
<div class="modal fade" id="createThesisModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-clipboard-list me-2 text-primary"></i>Tạo đề tài mới cho sinh viên
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="" id="createThesisForm">
                    <input type="hidden" id="modal_student_id" name="student_id">
                    <div class="mb-3">
                        <label class="form-label fw-medium">Sinh viên</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">
                                <i class="fas fa-user-graduate"></i>
                            </span>
                            <input type="text" class="form-control" id="modal_student_name" readonly>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="thesis_title" class="form-label fw-medium">Tên đề tài</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">
                                <i class="fas fa-book"></i>
                            </span>
                            <input type="text" class="form-control" id="thesis_title" name="thesis_title" 
                                   placeholder="Nhập tên đề tài" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="thesis_description" class="form-label fw-medium">Mô tả đề tài</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">
                                <i class="fas fa-align-left"></i>
                            </span>
                            <textarea class="form-control" id="thesis_description" name="thesis_description" 
                                      rows="4" placeholder="Nhập mô tả chi tiết về đề tài" required></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Hủy
                </button>
                <button type="submit" class="btn btn-primary" form="createThesisForm" name="create_thesis">
                    <i class="fas fa-save me-1"></i> Tạo đề tài
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Định nghĩa ngôn ngữ tiếng Việt cho DataTables trực tiếp để tránh lỗi CORS
    const vietnameseLanguage = {
        "emptyTable": "Không có dữ liệu",
        "info": "Hiển thị _START_ đến _END_ của _TOTAL_ mục",
        "infoEmpty": "Hiển thị 0 đến 0 của 0 mục",
        "infoFiltered": "(được lọc từ _MAX_ mục)",
        "infoPostFix": "",
        "thousands": ".",
        "lengthMenu": "Hiển thị _MENU_ mục",
        "loadingRecords": "Đang tải...",
        "processing": "Đang xử lý...",
        "search": "Tìm kiếm:",
        "zeroRecords": "Không tìm thấy kết quả",
        "paginate": {
            "first": "Đầu",
            "last": "Cuối",
            "next": "Sau",
            "previous": "Trước"
        },
        "aria": {
            "sortAscending": ": kích hoạt để sắp xếp cột tăng dần",
            "sortDescending": ": kích hoạt để sắp xếp cột giảm dần"
        }
    };

    // Initialize Select2
    if (jQuery().select2) {
        $('.select2').select2({
            theme: 'bootstrap-5',
            placeholder: "-- Chọn đề tài --",
            width: '100%'
        });
    }
    
    // Initialize DataTable
    if (jQuery().DataTable) {
        $('#thesisTable').DataTable({
            language: vietnameseLanguage,
            pageLength: 5,
            lengthMenu: [[5, 10, 25, -1], [5, 10, 25, "Tất cả"]]
        });
    }
    
    // Lấy danh sách đề tài để hiển thị chi tiết khi chọn
    const thesesData = <?php echo json_encode($theses); ?>;
    const thesisSelect = document.getElementById('thesis_id');
    const thesisDetails = document.getElementById('thesis_details');
    
    // Hàm hiển thị chi tiết đề tài
    function displayThesisDetails(thesisId) {
        if (thesisId) {
                // Tìm đề tài được chọn trong mảng dữ liệu
            const selectedThesis = thesesData.find(thesis => thesis.DeTaiID == thesisId);
                
                if (selectedThesis) {
                    // Hiển thị chi tiết đề tài
                    document.querySelector('.thesis-title').textContent = selectedThesis.TenDeTai;
                    document.querySelector('.thesis-description').textContent = selectedThesis.MoTa || 'Không có mô tả';
                document.querySelector('.thesis-field').textContent = selectedThesis.LinhVuc || 'Chưa xác định';
                document.querySelector('.thesis-date').textContent = new Date(selectedThesis.NgayTao).toLocaleDateString('vi-VN');
                    
                    const statusBadge = document.querySelector('.thesis-status');
                    statusBadge.textContent = selectedThesis.TrangThai || 'đề xuất';
                    statusBadge.className = 'thesis-status badge ';
                    
                    // Phân loại màu theo trạng thái
                    switch (selectedThesis.TrangThai) {
                        case 'đề xuất':
                            statusBadge.classList.add('bg-info');
                            break;
                        case 'được duyệt':
                            statusBadge.classList.add('bg-warning');
                            break;
                        case 'đang thực hiện':
                            statusBadge.classList.add('bg-primary');
                            break;
                        case 'hoàn thành':
                            statusBadge.classList.add('bg-success');
                            break;
                        case 'hủy':
                            statusBadge.classList.add('bg-danger');
                            break;
                        default:
                            statusBadge.classList.add('bg-secondary');
                    }
                    
                    thesisDetails.classList.remove('d-none');
                }
            } else {
                // Ẩn chi tiết nếu không chọn đề tài
                thesisDetails.classList.add('d-none');
            }
    }
    
    if (thesisSelect) {
        thesisSelect.addEventListener('change', function() {
            displayThesisDetails(this.value);
        });
    }
    
    // Handle clicking on thesis table rows
    document.querySelectorAll('.thesis-select-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const thesisId = this.closest('.thesis-row').dataset.id;
            if (thesisSelect) {
                thesisSelect.value = thesisId;
                if (jQuery().select2) {
                    $(thesisSelect).trigger('change');
                } else {
                    displayThesisDetails(thesisId);
                }
            }
            // Scroll to the form
            document.getElementById('assignThesisForm').scrollIntoView({behavior: 'smooth'});
        });
    });
    
    // Form validation
    const assignThesisForm = document.getElementById('assignThesisForm');
    if (assignThesisForm) {
        assignThesisForm.addEventListener('submit', function(e) {
            const thesisId = document.getElementById('thesis_id').value;
            if (!thesisId) {
                e.preventDefault();
                alert('Vui lòng chọn đề tài');
                return false;
            }
        });
    }
});
</script>

<?php 
// Bao gồm footer - footer.php sẽ đóng thẻ .main-content và .app-container
include '../includes/faculty/footer.php'; 
?>


