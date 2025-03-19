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
$currentPage = 'appointments'; // Đánh dấu trang hiện tại cho menu sidebar

// Lấy thông tin giảng viên
try {
    $db->query("SELECT * FROM GiangVien WHERE UserID = :userId");
    $db->bind(':userId', $userId);
    $facultyDetails = $db->single();

    if (!$facultyDetails) {
        header('Location: ' . BASE_URL . 'auth/logout.php');
        exit;
    }

    $facultyId = $facultyDetails['GiangVienID']; // ID giảng viên
} catch (PDOException $e) {
    $error = "Lỗi khi lấy thông tin giảng viên: " . $e->getMessage();
}

// Thay thế đoạn truy vấn lấy sinh viên
try {
    // Kiểm tra cấu trúc bảng SinhVien để tìm đúng tên cột email (nếu có)
    $db->query("DESCRIBE SinhVien");
    $columns = $db->resultSet();
    
    $hasEmailColumn = false;
    $emailColumnName = '';
    
    // Tìm cột email (có thể là Email, email, EmailSV, email_sv, etc.)
    foreach ($columns as $column) {
        if (stripos($column['Field'], 'email') !== false) {
            $hasEmailColumn = true;
            $emailColumnName = $column['Field'];
            break;
        }
    }
    
    // Xây dựng câu truy vấn dựa trên cấu trúc bảng
    $emailSelect = $hasEmailColumn ? ", sv.{$emailColumnName} as Email" : "";
    $db->query("SELECT sv.SinhVienID, sv.MaSV, sv.HoTen{$emailSelect}
               FROM SinhVien sv
               JOIN SinhVienGiangVienHuongDan svgv ON sv.SinhVienID = svgv.SinhVienID
               WHERE svgv.GiangVienID = :facultyId
               ORDER BY sv.HoTen ASC");
    $db->bind(':facultyId', $facultyId);
    $students = $db->resultSet();
} catch (PDOException $e) {
    $error = "Lỗi khi lấy danh sách sinh viên: " . $e->getMessage();
    $students = [];
}

// Xử lý form tạo lịch gặp mới
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['schedule_meeting'])) {
    // Lấy dữ liệu từ form
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $selectedStudents = isset($_POST['student_ids']) ? $_POST['student_ids'] : [];
    $meetingDate = isset($_POST['meeting_date']) ? $_POST['meeting_date'] : '';
    $meetingTime = isset($_POST['meeting_time']) ? $_POST['meeting_time'] : '';
    $location = isset($_POST['location']) ? trim($_POST['location']) : '';
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';

    // Validate dữ liệu
    if (empty($title)) {
        $error = "Vui lòng nhập tiêu đề cuộc gặp";
    } elseif (empty($selectedStudents)) {
        $error = "Vui lòng chọn ít nhất một sinh viên";
    } elseif (empty($meetingDate)) {
        $error = "Vui lòng chọn ngày gặp";
    } elseif (empty($meetingTime)) {
        $error = "Vui lòng chọn giờ gặp";
    } elseif (empty($location)) {
        $error = "Vui lòng nhập địa điểm gặp";
    } else {
        // Tạo datetime cho cuộc gặp
        $meetingDateTime = $meetingDate . ' ' . $meetingTime . ':00';
        
        try {
            // 1. Kiểm tra và tạo bảng LichGap nếu chưa tồn tại
            $db->query("SHOW TABLES LIKE 'LichGap'");
            $tableExists = $db->rowCount() > 0;
            
            if (!$tableExists) {
                // Tạo bảng mới với đầy đủ cấu trúc
                $db->query("CREATE TABLE IF NOT EXISTS `LichGap` (
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
                $db->execute();
            }

            // 2. Kiểm tra và tạo bảng ThongBao nếu chưa tồn tại
            $db->query("SHOW TABLES LIKE 'ThongBao'");
            $tableExists = $db->rowCount() > 0;
            
            if (!$tableExists) {
                $db->query("CREATE TABLE IF NOT EXISTS `ThongBao` (
                    `ThongBaoID` INT AUTO_INCREMENT PRIMARY KEY,
                    `UserID` INT NOT NULL,
                    `TieuDe` VARCHAR(255) NOT NULL,
                    `NoiDung` TEXT NOT NULL,
                    `DaDoc` TINYINT(1) DEFAULT 0,
                    `NgayTao` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `LoaiThongBao` VARCHAR(50) DEFAULT NULL,
                    `LienKet` VARCHAR(255) DEFAULT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                $db->execute();
            }

            // Thêm lịch gặp cho từng sinh viên đã chọn
            $successCount = 0;
            foreach ($selectedStudents as $studentId) {
                // Thêm lịch gặp
                $db->query("INSERT INTO LichGap (SinhVienID, GiangVienID, TieuDe, NgayGap, DiaDiem, TrangThai) 
                          VALUES (:studentId, :facultyId, :title, :meetingDateTime, :location, 'đã lên lịch')");
                $db->bind(':studentId', $studentId);
                $db->bind(':facultyId', $facultyId);
                $db->bind(':title', $title);
                $db->bind(':meetingDateTime', $meetingDateTime);
                $db->bind(':location', $location);
                
                if ($db->execute()) {
                    $successCount++;
                    
                    // Lấy thông tin UserID của sinh viên để gửi thông báo
                    $db->query("SELECT UserID FROM SinhVien WHERE SinhVienID = :studentId");
                    $db->bind(':studentId', $studentId);
                    $studentUser = $db->single();
                    
                    if ($studentUser) {
                        // Tạo nội dung thông báo
                        $notificationTitle = "Lịch gặp mới";
                        $notificationContent = "Giảng viên đã tạo một lịch gặp mới: {$title} vào ngày {$meetingDate} lúc {$meetingTime} tại {$location}";
                        
                        // Thêm thông báo cho sinh viên
                        $db->query("INSERT INTO ThongBao (UserID, TieuDe, NoiDung, LoaiThongBao, LienKet) 
                                  VALUES (:userID, :title, :content, 'lịch gặp', 'student/appointments.php')");
                        $db->bind(':userID', $studentUser['UserID']);
                        $db->bind(':title', $notificationTitle);
                        $db->bind(':content', $notificationContent);
                        $db->execute();
                    }
                }
            }
            
            if ($successCount > 0) {
                $success = "Đã tạo lịch gặp thành công!";
                // Reset form
                $title = $location = $content = '';
                $meetingDate = $meetingTime = '';
                $selectedStudents = [];
            } else {
                $error = "Không thể tạo lịch gặp. Vui lòng thử lại.";
            }
        } catch (PDOException $e) {
            $error = "Có lỗi xảy ra. Vui lòng thử lại sau.";
        }
    }
}

$pageTitle = 'Tạo lịch gặp mới';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tạo lịch gặp mới - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">Tạo lịch gặp mới</h1>
            <a href="appointments.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Quay lại danh sách
            </a>
        </div>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Thông tin lịch gặp</h6>
            </div>
            <div class="card-body">
                <form method="post" action="">
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="title" class="form-label">Tiêu đề cuộc gặp <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" required 
                                   value="<?php echo isset($title) ? htmlspecialchars($title) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="meeting_date" class="form-label">Ngày gặp <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="meeting_date" name="meeting_date" required
                                   min="<?php echo date('Y-m-d'); ?>"
                                   value="<?php echo isset($meetingDate) ? htmlspecialchars($meetingDate) : ''; ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="meeting_time" class="form-label">Giờ gặp <span class="text-danger">*</span></label>
                            <input type="time" class="form-control" id="meeting_time" name="meeting_time" required
                                   value="<?php echo isset($meetingTime) ? htmlspecialchars($meetingTime) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="location" class="form-label">Địa điểm <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="location" name="location" required
                                   value="<?php echo isset($location) ? htmlspecialchars($location) : ''; ?>">
                        </div>
                    </div>
                    
                    
                    
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <label class="form-label">Chọn sinh viên <span class="text-danger">*</span></label>
                            <div class="mb-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="selectAll">Chọn tất cả</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="deselectAll">Bỏ chọn tất cả</button>
                            </div>
                            <div class="card">
                                <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                                    <?php if (count($students) > 0): ?>
                                        <div class="row">
                                            <?php foreach ($students as $student): ?>
                                                <div class="col-md-6 mb-2">
                                                    <div class="form-check">
                                                        <input class="form-check-input student-checkbox" type="checkbox" 
                                                               name="student_ids[]" 
                                                               value="<?php echo $student['SinhVienID']; ?>" 
                                                               id="student_<?php echo $student['SinhVienID']; ?>"
                                                               <?php echo (isset($selectedStudents) && in_array($student['SinhVienID'], $selectedStudents)) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="student_<?php echo $student['SinhVienID']; ?>">
                                                            <?php echo htmlspecialchars($student['HoTen']) . ' (' . htmlspecialchars($student['MaSV']) . ')'; ?>
                                                            <?php if (isset($student['Email'])): ?>
                                                                <div class="small text-muted"><?php echo htmlspecialchars($student['Email']); ?></div>
                                                            <?php endif; ?>
                                                        </label>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-info mb-0">
                                            Bạn chưa có sinh viên nào được phân công hướng dẫn.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <button type="submit" name="schedule_meeting" class="btn btn-primary">
                                <i class="fas fa-calendar-plus me-1"></i> Tạo lịch gặp
                            </button>
                            <a href="appointments.php" class="btn btn-secondary ms-2">Hủy</a>
                            <div class="form-text mt-2">
                                <i class="fas fa-bell text-info small me-1"></i> Sinh viên được chọn sẽ tự động nhận thông báo về lịch gặp mới.
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Xử lý chọn tất cả & bỏ chọn tất cả
        document.getElementById('selectAll').addEventListener('click', function() {
            document.querySelectorAll('.student-checkbox').forEach(function(checkbox) {
                checkbox.checked = true;
            });
        });
        
        document.getElementById('deselectAll').addEventListener('click', function() {
            document.querySelectorAll('.student-checkbox').forEach(function(checkbox) {
                checkbox.checked = false;
            });
        });
    });
    </script>
</body>
</html>