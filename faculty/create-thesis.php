<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../classes/Auth.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Auth check
$auth = new Auth();
if (!$auth->isLoggedIn() || $auth->getUserRole() != 'faculty') {
    header('Location: ' . BASE_URL . 'auth/login.php?error=unauthorized_access');
    exit;
}

$db = new Database();
$userId = $_SESSION['user_id'];
$error = '';
$success = '';
$currentPage = 'thesis';
$pageTitle = 'Tạo đề tài mới';

// Lấy thông tin giảng viên
try {
    $db->query("SELECT * FROM GiangVien WHERE UserID = :userId");
    $db->bind(':userId', $userId);
    $facultyDetails = $db->single();
    $facultyId = $facultyDetails['GiangVienID'];
} catch (PDOException $e) {
    $error = "Lỗi khi lấy thông tin giảng viên: " . $e->getMessage();
}

// Xử lý form khi submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_thesis'])) {
    $tenDeTai = trim($_POST['ten_de_tai']);
    $moTa = trim($_POST['mo_ta']);
    $linhVuc = trim($_POST['linh_vuc']);
    $trangThai = isset($_POST['trang_thai']) ? $_POST['trang_thai'] : 'đề xuất';

    // Validate input
    if (empty($tenDeTai)) {
        $error = "Vui lòng nhập tên đề tài";
    } elseif (empty($moTa)) {
        $error = "Vui lòng nhập mô tả đề tài";
    } elseif (empty($linhVuc)) {
        $error = "Vui lòng nhập lĩnh vực nghiên cứu";
    } else {
        try {
            // Kiểm tra bảng DeTai đã tồn tại chưa
            $db->query("SHOW TABLES LIKE 'DeTai'");
            $tableExists = $db->rowCount() > 0;
            
            if (!$tableExists) {
                // Tạo bảng DeTai mới với GiangVienID
                $db->query("CREATE TABLE IF NOT EXISTS `DeTai` (
                    `DeTaiID` INT AUTO_INCREMENT PRIMARY KEY,
                    `TenDeTai` VARCHAR(255) NOT NULL,
                    `MoTa` TEXT,
                    `LinhVuc` VARCHAR(100),
                    `GiangVienID` INT NOT NULL,
                    `NgayTao` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    `NgayCapNhat` DATETIME ON UPDATE CURRENT_TIMESTAMP,
                    `TrangThai` ENUM('đề xuất', 'được duyệt', 'đang thực hiện', 'hoàn thành', 'hủy') DEFAULT 'đề xuất',
                    FOREIGN KEY (GiangVienID) REFERENCES GiangVien(GiangVienID),
                    INDEX `idx_giangvien` (`GiangVienID`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                $db->execute();
            } else {
                // Kiểm tra cột GiangVienID đã tồn tại chưa
                $db->query("SHOW COLUMNS FROM `DeTai` LIKE 'GiangVienID'");
                $giangVienIDExists = $db->rowCount() > 0;
                
                // Kiểm tra cột id_giangvien
                $db->query("SHOW COLUMNS FROM `DeTai` LIKE 'id_giangvien'");
                $idGiangVienExists = $db->rowCount() > 0;
                
                if (!$giangVienIDExists && $idGiangVienExists) {
                    // Nếu có id_giangvien nhưng không có GiangVienID, đổi tên cột
                    $db->query("ALTER TABLE `DeTai` CHANGE COLUMN `id_giangvien` `GiangVienID` INT NOT NULL");
                    $db->execute();
                } else if (!$giangVienIDExists && !$idGiangVienExists) {
                    // Nếu chưa có cột GiangVienID, thêm vào
                    $db->query("ALTER TABLE `DeTai` ADD COLUMN IF NOT EXISTS `GiangVienID` INT NOT NULL AFTER `LinhVuc`");
                    $db->execute();
                    
                    // Thêm khóa ngoại và index
                    try {
                        $db->query("ALTER TABLE `DeTai` ADD CONSTRAINT IF NOT EXISTS `fk_detai_giangvien` 
                                  FOREIGN KEY (`GiangVienID`) REFERENCES `GiangVien`(`GiangVienID`)");
                        $db->execute();
                        
                        // Kiểm tra xem index đã tồn tại chưa
                        $db->query("SHOW INDEX FROM `DeTai` WHERE Key_name = 'idx_giangvien'");
                        $indexExists = $db->rowCount() > 0;
                        
                        if (!$indexExists) {
                            $db->query("CREATE INDEX `idx_giangvien` ON `DeTai` (`GiangVienID`)");
                            $db->execute();
                        }
                    } catch (PDOException $e) {
                        // Bỏ qua lỗi nếu không thể thêm khóa ngoại hoặc index đã tồn tại
                        // Thường gặp lỗi 1061 (index đã tồn tại) hoặc 1826 (duplicate foreign key)
                        if (!strstr($e->getMessage(), '1061') && !strstr($e->getMessage(), '1826')) {
                            error_log('Non-critical error in alter table: ' . $e->getMessage());
                        }
                    }
                }
            }
            
            // Luôn sử dụng GiangVienID cho INSERT
            $columnForInsert = 'GiangVienID';
            
            // Thực hiện INSERT - luôn chỉ sử dụng GiangVienID
            $db->query("INSERT INTO DeTai 
                      (TenDeTai, MoTa, LinhVuc, GiangVienID, TrangThai) 
                      VALUES 
                      (:tenDeTai, :moTa, :linhVuc, :idGiangVien, :trangThai)");
            
            $db->bind(':tenDeTai', $tenDeTai);
            $db->bind(':moTa', $moTa);
            $db->bind(':linhVuc', $linhVuc);
            $db->bind(':idGiangVien', $facultyId);
            $db->bind(':trangThai', $trangThai);
            
            if ($db->execute()) {
                $success = "Đã tạo đề tài mới thành công! ID giảng viên (" . $facultyId . ") đã được lưu vào cột " . $columnForInsert;
                $newThesisId = $db->lastInsertId();
                
                // Ghi log (giữ phần code ghi log hiện tại)
                try {
                    $db->query("CREATE TABLE IF NOT EXISTS `HoatDongNguoiDung` (
                        `HoatDongID` int(11) NOT NULL AUTO_INCREMENT,
                        `UserID` int(11) NOT NULL,
                        `LoaiHanhDong` varchar(50) NOT NULL,
                        `MoTa` text NOT NULL,
                        `DoiTuongID` int(11) DEFAULT NULL,
                        `ThoiGian` timestamp NOT NULL DEFAULT current_timestamp(),
                        PRIMARY KEY (`HoatDongID`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                    $db->execute();
                    
                    $db->query("INSERT INTO HoatDongNguoiDung (UserID, LoaiHanhDong, MoTa, DoiTuongID) 
                              VALUES (:userId, :actionType, :description, :relatedId)");
                    $db->bind(':userId', $userId);
                    $db->bind(':actionType', 'create_thesis');
                    $db->bind(':description', 'Tạo đề tài mới: ' . $tenDeTai);
                    $db->bind(':relatedId', $newThesisId);
                    $db->execute();
                } catch (Exception $e) {
                    // Bỏ qua lỗi ghi log
                }
            }
        } catch (PDOException $e) {
            $error = "Lỗi khi tạo đề tài mới: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Hệ thống quản lý luận văn'; ?> - <?php echo SITE_NAME ?? 'Thesis Management'; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
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
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <a href="dashboard.php" class="sidebar-brand">
                    <i class="fas fa-graduation-cap me-2"></i>
                    <span><?php echo SITE_NAME ?? 'Hệ thống quản lý luận văn'; ?></span>
                </a>
            </div>
            
            <div class="sidebar-user">
                <div class="sidebar-user-img">
                    <?php 
                    if (isset($facultyDetails) && !empty($facultyDetails['Avatar'])): ?>
                        <img src="<?php echo BASE_URL . $facultyDetails['Avatar']; ?>" alt="Avatar">
                    <?php else: ?>
                        <?php echo isset($facultyDetails['HoTen']) ? strtoupper(substr($facultyDetails['HoTen'], 0, 1)) : 'GV'; ?>
                    <?php endif; ?>
                </div>
                <div>
                    <span class="sidebar-user-name"><?php echo isset($facultyDetails['HoTen']) ? $facultyDetails['HoTen'] : 'Giảng viên'; ?></span>
                    <span class="sidebar-user-role">Giảng viên</span>
                </div>
            </div>
            
            <ul class="sidebar-menu">
                <li>
                    <a href="dashboard.php" class="<?php echo $currentPage == 'dashboard' ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Trang chủ</span>
                    </a>
                </li>
                <li>
                    <a href="thesis-list.php" class="<?php echo $currentPage == 'thesis' ? 'active' : ''; ?>">
                        <i class="fas fa-book"></i>
                        <span>Đề tài luận văn</span>
                    </a>
                </li>
                <li>
                    <a href="students.php" class="<?php echo $currentPage == 'students' ? 'active' : ''; ?>">
                        <i class="fas fa-user-graduate"></i>
                        <span>Sinh viên</span>
                    </a>
                </li>
                <li>
                    <a href="meetings.php" class="<?php echo $currentPage == 'meetings' ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Lịch gặp</span>
                    </a>
                </li>
                <li>
                    <a href="messages.php" class="<?php echo $currentPage == 'messages' ? 'active' : ''; ?>">
                        <i class="fas fa-envelope"></i>
                        <span>Tin nhắn</span>
                    </a>
                </li>
                <li>
                    <a href="profile.php" class="<?php echo $currentPage == 'profile' ? 'active' : ''; ?>">
                        <i class="fas fa-user"></i>
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
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <div class="container-fluid p-0">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3"><?php echo $pageTitle; ?></h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="dashboard.php">Trang chủ</a></li>
                            <li class="breadcrumb-item"><a href="thesis-list.php">Đề tài</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Tạo đề tài mới</li>
                        </ol>
                    </nav>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Thông tin đề tài</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" action="">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label for="ten_de_tai" class="form-label">Tên đề tài <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="ten_de_tai" name="ten_de_tai" value="<?php echo isset($_POST['ten_de_tai']) ? htmlspecialchars($_POST['ten_de_tai']) : ''; ?>" required>
                                </div>
                                
                                <div class="col-md-12">
                                    <label for="linh_vuc" class="form-label">Lĩnh vực nghiên cứu <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="linh_vuc" name="linh_vuc" value="<?php echo isset($_POST['linh_vuc']) ? htmlspecialchars($_POST['linh_vuc']) : ''; ?>" required>
                                </div>
                                
                                <div class="col-md-12">
                                    <label for="mo_ta" class="form-label">Mô tả đề tài <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="mo_ta" name="mo_ta" rows="5" required><?php echo isset($_POST['mo_ta']) ? htmlspecialchars($_POST['mo_ta']) : ''; ?></textarea>
                                    <div class="form-text">Mô tả chi tiết về đề tài, mục tiêu nghiên cứu, ý nghĩa và phạm vi.</div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="trang_thai" class="form-label">Trạng thái</label>
                                    <select class="form-select" id="trang_thai" name="trang_thai">
                                        <option value="đề xuất" <?php echo (!isset($_POST['trang_thai']) || $_POST['trang_thai'] == 'đề xuất') ? 'selected' : ''; ?>>Đề xuất</option>
                                        <option value="được duyệt" <?php echo (isset($_POST['trang_thai']) && $_POST['trang_thai'] == 'được duyệt') ? 'selected' : ''; ?>>Được duyệt</option>
                                        <option value="đang thực hiện" <?php echo (isset($_POST['trang_thai']) && $_POST['trang_thai'] == 'đang thực hiện') ? 'selected' : ''; ?>>Đang thực hiện</option>
                                        <option value="hoàn thành" <?php echo (isset($_POST['trang_thai']) && $_POST['trang_thai'] == 'hoàn thành') ? 'selected' : ''; ?>>Hoàn thành</option>
                                        <option value="hủy" <?php echo (isset($_POST['trang_thai']) && $_POST['trang_thai'] == 'hủy') ? 'selected' : ''; ?>>Hủy</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-12 mt-4">
                                    <div class="d-flex justify-content-between">
                                        <a href="thesis-list.php" class="btn btn-secondary">Hủy bỏ</a>
                                        <button type="submit" name="create_thesis" class="btn btn-primary">
                                            <i class="fas fa-plus me-1"></i> Tạo đề tài
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <footer class="footer mt-auto py-3">
                <div class="container">
                    <div class="row">
                        <div class="col-md-6 text-md-start text-center">
                            <p class="mb-0">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME ?? 'Thesis Management System'; ?></p>
                        </div>
                        <div class="col-md-6 text-md-end text-center">
                            <p class="mb-0">Thiết kế và phát triển bởi <a href="#" class="text-decoration-none">Team Thesis</a></p>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Validate form trước khi submit
        document.querySelector('form').addEventListener('submit', function(event) {
            let tenDeTai = document.getElementById('ten_de_tai').value.trim();
            let moTa = document.getElementById('mo_ta').value.trim();
            let linhVuc = document.getElementById('linh_vuc').value.trim();
            
            if (tenDeTai === '') {
                alert('Vui lòng nhập tên đề tài');
                event.preventDefault();
                return false;
            }
            
            if (moTa === '') {
                alert('Vui lòng nhập mô tả đề tài');
                event.preventDefault();
                return false;
            }
            
            if (linhVuc === '') {
                alert('Vui lòng nhập lĩnh vực nghiên cứu');
                event.preventDefault();
                return false;
            }
        });
    });
    </script>
</body>
</html>