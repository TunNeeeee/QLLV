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
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit;
}

$db = new Database();
$userId = $_SESSION['user_id'];
$success = '';
$error = '';

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

// Lấy danh sách sinh viên được phân công cho giảng viên
$assignedStudents = [];
try {
    // Kiểm tra bảng SinhVienGiangVienHuongDan tồn tại
    $db->query("SHOW TABLES LIKE 'SinhVienGiangVienHuongDan'");
    $tableExists = $db->rowCount() > 0;
    
    if ($tableExists) {
        $db->query("SELECT sv.SinhVienID, sv.HoTen, sv.MaSV, sv.Khoa, sv.NganhHoc, u.Email 
                   FROM SinhVien sv
                   JOIN SinhVienGiangVienHuongDan svgv ON sv.SinhVienID = svgv.SinhVienID
                   JOIN Users u ON sv.UserID = u.UserID
                   WHERE svgv.GiangVienID = :facultyId
                   AND svgv.DeTaiID IS NULL");
        $db->bind(':facultyId', $facultyId);
        $assignedStudents = $db->resultSet();
    }
} catch (PDOException $e) {
    $error = "Lỗi khi lấy danh sách sinh viên: " . $e->getMessage();
}

// Xử lý đề xuất đề tài cho sinh viên
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_thesis'])) {
    $studentId = $_POST['student_id'];
    $thesisTitle = $_POST['thesis_title'];
    $thesisDescription = $_POST['thesis_description'];
    $requirements = $_POST['requirements'];
    $expectedResults = $_POST['expected_results'];
    
    if (empty($studentId) || empty($thesisTitle) || empty($thesisDescription)) {
        $error = "Vui lòng điền đầy đủ thông tin bắt buộc";
    } else {
        try {
            // Kiểm tra bảng DeTai tồn tại
            $db->query("SHOW TABLES LIKE 'DeTai'");
            $tableExists = $db->rowCount() > 0;
            
            if (!$tableExists) {
                // Tạo bảng DeTai nếu chưa tồn tại
                $db->query("CREATE TABLE IF NOT EXISTS `DeTai` (
                    `DeTaiID` int(11) NOT NULL AUTO_INCREMENT,
                    `TenDeTai` varchar(255) NOT NULL,
                    `MoTa` text,
                    `YeuCau` text,
                    `KetQuaDuKien` text,
                    `TrangThai` enum('Chờ duyệt','Đã duyệt','Từ chối','Đang thực hiện','Hoàn thành') DEFAULT 'Chờ duyệt',
                    `NgayTao` timestamp NOT NULL DEFAULT current_timestamp(),
                    `NgayDuyet` datetime DEFAULT NULL,
                    PRIMARY KEY (`DeTaiID`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
            }
            
            // Thêm đề tài mới
            $db->query("INSERT INTO DeTai (TenDeTai, MoTa, YeuCau, KetQuaDuKien, TrangThai) 
                       VALUES (:title, :description, :requirements, :expectedResults, 'Chờ duyệt')");
            $db->bind(':title', $thesisTitle);
            $db->bind(':description', $thesisDescription);
            $db->bind(':requirements', $requirements);
            $db->bind(':expectedResults', $expectedResults);
            $db->execute();
            
            $thesisId = $db->lastInsertId();
            
            // Cập nhật bảng SinhVienGiangVienHuongDan với đề tài mới
            $db->query("UPDATE SinhVienGiangVienHuongDan 
                       SET DeTaiID = :thesisId, NgayBatDau = CURDATE()
                       WHERE SinhVienID = :studentId AND GiangVienID = :facultyId");
            $db->bind(':thesisId', $thesisId);
            $db->bind(':studentId', $studentId);
            $db->bind(':facultyId', $facultyId);
            $db->execute();
            
            // Thêm thông báo cho sinh viên
            $db->query("INSERT INTO ThongBao (UserID, TieuDe, NoiDung, TrangThai) 
                       SELECT sv.UserID, 'Đề tài luận văn mới', CONCAT('Giảng viên đã đề xuất đề tài \"', :title, '\" cho bạn'), 'Chưa đọc'
                       FROM SinhVien sv WHERE sv.SinhVienID = :studentId");
            $db->bind(':title', $thesisTitle);
            $db->bind(':studentId', $studentId);
            $db->execute();
            
            $success = "Đề xuất đề tài thành công!";
        } catch (PDOException $e) {
            $error = "Lỗi khi đề xuất đề tài: " . $e->getMessage();
        }
    }
}

// Lấy các đề tài đã đề xuất
$proposedTheses = [];
try {
    // Kiểm tra bảng DeTai tồn tại
    $db->query("SHOW TABLES LIKE 'DeTai'");
    $tableExists = $db->rowCount() > 0;
    
    if ($tableExists) {
        $db->query("SELECT dt.*, sv.HoTen, sv.MaSV 
                   FROM DeTai dt
                   JOIN SinhVienGiangVienHuongDan svgv ON dt.DeTaiID = svgv.DeTaiID
                   JOIN SinhVien sv ON svgv.SinhVienID = sv.SinhVienID
                   WHERE svgv.GiangVienID = :facultyId
                   ORDER BY dt.NgayTao DESC");
        $db->bind(':facultyId', $facultyId);
        $proposedTheses = $db->resultSet();
    }
} catch (PDOException $e) {
    $error .= "Lỗi khi lấy danh sách đề tài: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đề xuất đề tài luận văn - <?php echo SITE_NAME; ?></title>
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
            --card-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fb;
            color: var(--text-color);
        }
        
        .app-container {
            display: flex;
            min-height: 100vh;
        }
        
        .main-content {
            flex: 1;
            padding: 2rem;
            margin-left: 250px;
        }
        
        .page-header {
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .page-title {
            font-weight: 600;
            font-size: 1.75rem;
            margin-bottom: 0.25rem;
        }
        
        .page-subtitle {
            color: var(--light-text);
            font-size: 1rem;
        }
        
        .card {
            border: none;
            border-radius: 0.75rem;
            box-shadow: var(--card-shadow);
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid var(--border-color);
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
        
        .form-label {
            font-weight: 500;
        }
        
        .status-badge {
            padding: 0.5em 0.75em;
            border-radius: 50rem;
            font-weight: 500;
            font-size: 0.75em;
        }
        
        .table td, .table th {
            vertical-align: middle;
        }
        
        @media (max-width: 991.98px) {
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar sẽ được include từ file sidebar.php -->
        <?php include_once '../includes/faculty-sidebar.php'; ?>
        
        <div class="main-content">
            <div class="page-header">
                <div>
                    <h1 class="page-title">Đề xuất đề tài luận văn</h1>
                    <p class="page-subtitle">Đề xuất đề tài cho sinh viên đã được phân công</p>
                </div>
            </div>
            
            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-lg-5 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-lightbulb me-2"></i>Đề xuất đề tài mới</h5>
                        </div>
                        <div class="card-body">
                            <?php if (count($assignedStudents) > 0): ?>
                            <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                                <div class="mb-3">
                                    <label for="student_id" class="form-label">Sinh viên</label>
                                    <select class="form-select" id="student_id" name="student_id" required>
                                        <option value="">-- Chọn sinh viên --</option>
                                        <?php foreach ($assignedStudents as $student): ?>
                                        <option value="<?php echo $student['SinhVienID']; ?>">
                                            <?php echo htmlspecialchars($student['MaSV'] . ' - ' . $student['HoTen']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text">Chọn sinh viên đã được phân công cho bạn</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="thesis_title" class="form-label">Tên đề tài</label>
                                    <input type="text" class="form-control" id="thesis_title" name="thesis_title" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="thesis_description" class="form-label">Mô tả đề tài</label>
                                    <textarea class="form-control" id="thesis_description" name="thesis_description" rows="4" required></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="requirements" class="form-label">Yêu cầu</label>
                                    <textarea class="form-control" id="requirements" name="requirements" rows="3"></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="expected_results" class="form-label">Kết quả dự kiến</label>
                                    <textarea class="form-control" id="expected_results" name="expected_results" rows="3"></textarea>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" name="submit_thesis" class="btn btn-primary">
                                        <i class="fas fa-paper-plane me-2"></i>Gửi đề xuất
                                    </button>
                                </div>
                            </form>
                            <?php else: ?>
                            <div class="alert alert-info mb-0">
                                <i class="fas fa-info-circle me-2"></i> Bạn chưa được phân công sinh viên nào hoặc tất cả sinh viên đã có đề tài.
                                <div class="mt-3">
                                    <a href="manage-students.php" class="btn btn-sm btn-primary">Quản lý sinh viên</a>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-7">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-list-alt me-2"></i>Đề tài đã đề xuất</h5>
                        </div>
                        <div class="card-body">
                            <?php if (count($proposedTheses) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Tên đề tài</th>
                                            <th>Sinh viên</th>
                                            <th>Ngày đề xuất</th>
                                            <th>Trạng thái</th>
                                            <th>Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($proposedTheses as $thesis): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($thesis['TenDeTai']); ?></td>
                                            <td><?php echo htmlspecialchars($thesis['HoTen'] . ' (' . $thesis['MaSV'] . ')'); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($thesis['NgayTao'])); ?></td>
                                            <td>
                                                <?php
                                                switch ($thesis['TrangThai']) {
                                                    case 'Chờ duyệt':
                                                        echo '<span class="badge bg-warning text-dark">Chờ duyệt</span>';
                                                        break;
                                                    case 'Đã duyệt':
                                                        echo '<span class="badge bg-success">Đã duyệt</span>';
                                                        break;
                                                    case 'Từ chối':
                                                        echo '<span class="badge bg-danger">Từ chối</span>';
                                                        break;
                                                    case 'Đang thực hiện':
                                                        echo '<span class="badge bg-primary">Đang thực hiện</span>';
                                                        break;
                                                    case 'Hoàn thành':
                                                        echo '<span class="badge bg-info">Hoàn thành</span>';
                                                        break;
                                                    default:
                                                        echo '<span class="badge bg-secondary">Không xác định</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <a href="thesis-details.php?id=<?php echo $thesis['DeTaiID']; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <?php if ($thesis['TrangThai'] == 'Chờ duyệt'): ?>
                                                <a href="edit-thesis.php?id=<?php echo $thesis['DeTaiID']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <div class="alert alert-info mb-0">
                                <i class="fas fa-info-circle me-2"></i> Bạn chưa đề xuất đề tài nào cho sinh viên.
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>