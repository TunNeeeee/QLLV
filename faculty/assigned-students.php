<?php
// Các phần include và kiểm tra quyền truy cập
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../classes/Auth.php';

// Thêm các hàm không định nghĩa của PHP
if (!function_exists('session_status')) {
    function session_status() {
        return session_id() ? PHP_SESSION_ACTIVE : PHP_SESSION_NONE;
    }
}

if (!defined('PHP_SESSION_NONE')) {
    define('PHP_SESSION_NONE', 1);
}

if (!defined('PHP_SESSION_ACTIVE')) {
    define('PHP_SESSION_ACTIVE', 2);
}

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!defined('BASE_URL')) {
    define('BASE_URL', '/');
}

if (!defined('SITE_NAME')) {
    define('SITE_NAME', 'Hệ thống quản lý luận văn');
}

$auth = new Auth();
if (!$auth->isLoggedIn() || $auth->getUserRole() != 'faculty') {
    header('Location: ' . BASE_URL . 'auth/login.php?error=unauthorized_access');
    exit;
}

$db = new Database();
$error = '';
$success = ''; 

// Lấy ID của giảng viên đang đăng nhập
$userID = $_SESSION['user_id'];

// Lấy thông tin giảng viên
try {
    $db->query("SELECT gv.GiangVienID, gv.HoTen, gv.HocVi, u.Email 
                FROM GiangVien gv 
                JOIN Users u ON gv.UserID = u.UserID 
                WHERE gv.UserID = :userID");
    $db->bind(':userID', $userID);
    $facultyDetails = $db->single();
    
    if (!$facultyDetails) {
        throw new Exception("Không tìm thấy thông tin giảng viên.");
    }
    
    $facultyId = $facultyDetails['GiangVienID'];
} catch (PDOException $e) {
    $error = "Lỗi khi lấy thông tin giảng viên: " . $e->getMessage();
    $facultyId = null;
}

// Lấy danh sách sinh viên được phân công
$assignedStudents = [];

if ($facultyId) {
    try {
        $query = "SELECT sv.SinhVienID, sv.MaSV, sv.HoTen, u.Email, 
                  svgv.ID as PhanCongID, svgv.NgayBatDau, svgv.TienDo,
                  dt.DeTaiID, dt.TenDeTai, dt.TrangThai
                  FROM SinhVienGiangVienHuongDan svgv
                  JOIN SinhVien sv ON svgv.SinhVienID = sv.SinhVienID
                  JOIN Users u ON sv.UserID = u.UserID
                  LEFT JOIN DeTai dt ON svgv.DeTaiID = dt.DeTaiID
                  WHERE svgv.GiangVienID = :facultyId
                  ORDER BY svgv.NgayBatDau DESC";
        
        $db->query($query);
        $db->bind(':facultyId', $facultyId);
        
        $assignedStudents = $db->resultSet();
    } catch (PDOException $e) {
        $error = "Lỗi khi lấy danh sách sinh viên: " . $e->getMessage();
        if (function_exists('error_log')) {
            error_log("SQL Error: " . $e->getMessage() . " - Query: " . $query);
        }
    }
}

// Đếm số lượng sinh viên theo trạng thái
$completedCount = 0;
$inProgressCount = 0;
$notStartedCount = 0;

if (!empty($assignedStudents)) {
    foreach ($assignedStudents as $student) {
        if (isset($student['TrangThai'])) {
            if ($student['TrangThai'] == 'Hoàn thành') {
                $completedCount++;
            } elseif ($student['TrangThai'] == 'Đang thực hiện') {
                $inProgressCount++;
            } else {
                $notStartedCount++;
            }
        } else {
            $notStartedCount++;
        }
    }
}

// Xử lý việc tạo đề tài mới cho sinh viên
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_thesis'])) {
    $studentId = $_POST['student_id'];
    $thesisTitle = $_POST['thesis_title'];
    $thesisDesc = $_POST['thesis_description'];
    
    try {
        // Tạo mới đề tài
        $db->query("INSERT INTO DeTai (TenDeTai, MoTa, NgayTao, TrangThai) 
                   VALUES (:title, :description, NOW(), 'Chưa bắt đầu')");
        $db->bind(':title', $thesisTitle);
        $db->bind(':description', $thesisDesc);
        $db->execute();
        
        $thesisId = $db->lastInsertId();
        
        // Cập nhật vào bảng phân công
        $db->query("UPDATE SinhVienGiangVienHuongDan 
                   SET DeTaiID = :thesisId 
                   WHERE SinhVienID = :studentId AND GiangVienID = :facultyId");
        $db->bind(':thesisId', $thesisId);
        $db->bind(':studentId', $studentId);
        $db->bind(':facultyId', $facultyId);
        $db->execute();
        
        $success = "Đã tạo đề tài mới cho sinh viên thành công!";
        
        // Refresh lại danh sách sinh viên
        header("Location: assigned-students.php?success=thesis_created");
        exit;
    } catch (PDOException $e) {
        $error = "Lỗi khi tạo đề tài: " . $e->getMessage();
    }
}

// Thêm các hàm không định nghĩa nếu cần
if (!function_exists('htmlspecialchars')) {
    function htmlspecialchars($string, $flags = ENT_QUOTES, $encoding = 'UTF-8', $double_encode = true) {
        return $string;
    }
}

if (!function_exists('date')) {
    function date($format, $timestamp = null) {
        if ($timestamp === null) {
            $timestamp = time();
        }
        return strftime($format, $timestamp);
    }
}

if (!function_exists('strtotime')) {
    function strtotime($datetime) {
        return time();
    }
}

if (!function_exists('count')) {
    function count($array) {
        return is_array($array) ? sizeof($array) : 0;
    }
}

// Thêm biến pageTitle để hiển thị tiêu đề trang
$pageTitle = "Sinh viên hướng dẫn";
$currentPage = 'students'; // Đánh dấu trang hiện tại cho menu sidebar

// Bao gồm header - header.php đã mở thẻ .main-content
include '../includes/faculty/header.php';
?>

<div class="container-fluid p-0">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">Sinh viên hướng dẫn</h1>
                    <p class="mb-0 text-muted">Quản lý danh sách sinh viên được phân công hướng dẫn</p>
                </div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 bg-transparent">
                        <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Trang chủ</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Sinh viên hướng dẫn</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    
    <!-- Alert Messages -->
    <?php if (!empty($success)): ?>
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <div class="d-flex align-items-center">
                <i class="fas fa-check-circle flex-shrink-0 me-2"></i>
                <div><?php echo $success; ?></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <div class="d-flex align-items-center">
                <i class="fas fa-exclamation-circle flex-shrink-0 me-2"></i>
                <div><?php echo $error; ?></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <!-- Info Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card border-start border-5 border-primary">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h5 class="text-muted fw-normal mt-0 mb-1">Tổng sinh viên</h5>
                            <h2 class="mb-0"><?php echo count($assignedStudents); ?></h2>
                        </div>
                        <div class="avatar bg-primary bg-opacity-10 rounded-circle p-3">
                            <i class="fas fa-users text-primary fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card border-start border-5 border-success">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h5 class="text-muted fw-normal mt-0 mb-1">Đã hoàn thành</h5>
                            <h2 class="mb-0"><?php echo $completedCount; ?></h2>
                        </div>
                        <div class="avatar bg-success bg-opacity-10 rounded-circle p-3">
                            <i class="fas fa-check-circle text-success fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card border-start border-5 border-warning">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h5 class="text-muted fw-normal mt-0 mb-1">Đang thực hiện</h5>
                            <h2 class="mb-0"><?php echo $inProgressCount; ?></h2>
                        </div>
                        <div class="avatar bg-warning bg-opacity-10 rounded-circle p-3">
                            <i class="fas fa-spinner text-warning fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card border-start border-5 border-info">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h5 class="text-muted fw-normal mt-0 mb-1">Chưa bắt đầu</h5>
                            <h2 class="mb-0"><?php echo $notStartedCount; ?></h2>
                        </div>
                        <div class="avatar bg-info bg-opacity-10 rounded-circle p-3">
                            <i class="fas fa-hourglass-start text-info fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Faculty Info and Student List -->
    <div class="row">
        <div class="col-xl-3 col-lg-4 mb-4">
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center">
                    <i class="fas fa-user-tie me-2 text-primary"></i>
                    <span>Thông tin giảng viên</span>
                </div>
                <div class="card-body">
                    <?php if (isset($facultyDetails)): ?>
                        <div class="text-center mb-4">
                            <div class="avatar-lg mx-auto mb-3 rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center">
                                <span class="text-primary display-6">
                                    <?php echo strtoupper(substr($facultyDetails['HoTen'], 0, 1)); ?>
                                </span>
                            </div>
                            <h4 class="mb-1"><?php echo htmlspecialchars($facultyDetails['HoTen']); ?></h4>
                            <p class="text-muted mb-0"><?php echo htmlspecialchars($facultyDetails['HocVi']); ?></p>
                        </div>
                        
                        <div class="border-top pt-3">
                            <div class="table-responsive">
                                <table class="table table-borderless mb-0">
                                    <tbody>
                                        <tr>
                                            <td class="text-muted">
                                                <i class="fas fa-envelope me-1"></i> Email:
                                            </td>
                                            <td class="fw-medium"><?php echo htmlspecialchars($facultyDetails['Email']); ?></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">
                                                <i class="fas fa-users me-1"></i> Sinh viên hướng dẫn:
                                            </td>
                                            <td class="fw-medium"><?php echo count($assignedStudents); ?> sinh viên</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-danger mb-0">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            Không tìm thấy thông tin giảng viên
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header d-flex align-items-center">
                    <i class="fas fa-chart-pie me-2 text-primary"></i>
                    <span>Tỷ lệ tiến độ</span>
                </div>
                <div class="card-body">
                    <?php if (count($assignedStudents) > 0): ?>
                        <div class="progress-wrapper mb-4">
                            <span class="progress-label">Đã hoàn thành</span>
                            <div class="progress mt-2">
                                <div class="progress-bar bg-success" role="progressbar" 
                                     style="width: <?php echo (count($assignedStudents) > 0) ? ($completedCount / count($assignedStudents) * 100) : 0; ?>%" 
                                     aria-valuenow="<?php echo $completedCount; ?>" 
                                     aria-valuemin="0" aria-valuemax="<?php echo count($assignedStudents); ?>">
                                    <?php echo (count($assignedStudents) > 0) ? round($completedCount / count($assignedStudents) * 100) : 0; ?>%
                                </div>
                            </div>
                        </div>
                        <div class="progress-wrapper mb-4">
                            <span class="progress-label">Đang thực hiện</span>
                            <div class="progress mt-2">
                                <div class="progress-bar bg-warning" role="progressbar" 
                                     style="width: <?php echo (count($assignedStudents) > 0) ? ($inProgressCount / count($assignedStudents) * 100) : 0; ?>%" 
                                     aria-valuenow="<?php echo $inProgressCount; ?>" 
                                     aria-valuemin="0" aria-valuemax="<?php echo count($assignedStudents); ?>">
                                    <?php echo (count($assignedStudents) > 0) ? round($inProgressCount / count($assignedStudents) * 100) : 0; ?>%
                                </div>
                            </div>
                        </div>
                        <div class="progress-wrapper">
                            <span class="progress-label">Chưa bắt đầu</span>
                            <div class="progress mt-2">
                                <div class="progress-bar bg-info" role="progressbar" 
                                     style="width: <?php echo (count($assignedStudents) > 0) ? ($notStartedCount / count($assignedStudents) * 100) : 0; ?>%" 
                                     aria-valuenow="<?php echo $notStartedCount; ?>" 
                                     aria-valuemin="0" aria-valuemax="<?php echo count($assignedStudents); ?>">
                                    <?php echo (count($assignedStudents) > 0) ? round($notStartedCount / count($assignedStudents) * 100) : 0; ?>%
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            Chưa có dữ liệu thống kê
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-xl-9 col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-user-graduate me-2 text-primary"></i>
                        <span>Danh sách sinh viên hướng dẫn</span>
                    </div>
                    <a href="assign-student.php" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus me-1"></i> Thêm sinh viên
                    </a>
                </div>
                <div class="card-body">
                    <?php if (!empty($assignedStudents)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover" id="assignedStudentsTable">
                                <thead>
                                    <tr>
                                        <th>Sinh viên</th>
                                        <th>Đề tài</th>
                                        <th>Ngày bắt đầu</th>
                                        <th>Tiến độ</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($assignedStudents as $student): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-sm me-2 rounded-circle bg-primary d-flex align-items-center justify-content-center">
                                                    <span class="text-white">
                                                        <?php echo strtoupper(substr($student['HoTen'], 0, 1)); ?>
                                                    </span>
                                                </div>
                                                <div>
                                                    <a href="student-details.php?id=<?php echo $student['SinhVienID']; ?>" class="text-decoration-none fw-medium">
                                                        <?php echo htmlspecialchars($student['HoTen']); ?>
                                                    </a>
                                                    <div class="small text-muted">
                                                        <span><?php echo htmlspecialchars($student['MaSV']); ?></span>
                                                        <span class="mx-1">•</span>
                                                        <span><?php echo htmlspecialchars($student['Email']); ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if (!empty($student['DeTaiID']) && !empty($student['TenDeTai'])): ?>
                                                <a href="thesis-details.php?id=<?php echo $student['DeTaiID']; ?>" class="text-decoration-none">
                                                    <?php echo htmlspecialchars($student['TenDeTai']); ?>
                                                </a>
                                                <div class="small text-muted">
                                                    <span class="badge bg-<?php 
                                                        switch($student['TrangThai']) {
                                                            case 'đề xuất': echo 'info'; break;
                                                            case 'được duyệt': echo 'warning'; break;
                                                            case 'đang thực hiện': echo 'primary'; break;
                                                            case 'hoàn thành': echo 'success'; break;
                                                            case 'hủy': echo 'danger'; break;
                                                            default: echo 'secondary';
                                                        }
                                                    ?>">
                                                        <?php echo htmlspecialchars($student['TrangThai'] ?? 'Chưa xác định'); ?>
                                                    </span>
                                                </div>
                                            <?php else: ?>
                                                <div class="d-flex align-items-center">
                                                    <span class="text-muted me-2">Chưa có đề tài</span>
                                                    <a href="assign-thesis.php?id=<?php echo $student['SinhVienID']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-plus-circle"></i> Gán đề tài
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($student['NgayBatDau'])); ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="progress flex-grow-1" style="height: 8px;">
                                                    <div class="progress-bar bg-<?php 
                                                        $progress = isset($student['TienDo']) ? $student['TienDo'] : 0;
                                                        if ($progress >= 75) echo 'success';
                                                        elseif ($progress >= 50) echo 'primary';
                                                        elseif ($progress >= 25) echo 'warning';
                                                        else echo 'danger';
                                                    ?>" role="progressbar" 
                                                        style="width: <?php echo isset($student['TienDo']) ? $student['TienDo'] : 0; ?>%" 
                                                        aria-valuenow="<?php echo isset($student['TienDo']) ? $student['TienDo'] : 0; ?>" 
                                                        aria-valuemin="0" aria-valuemax="100">
                                                    </div>
                                                </div>
                                                <span class="ms-2 fw-medium">
                                                    <?php echo isset($student['TienDo']) ? $student['TienDo'] : 0; ?>%
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="student-details.php?id=<?php echo $student['SinhVienID']; ?>" class="btn btn-sm btn-outline-primary" title="Xem chi tiết">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="student-discussion.php?id=<?php echo $student['SinhVienID']; ?>" class="btn btn-sm btn-outline-info" title="Trao đổi">
                                                    <i class="fas fa-comments"></i>
                                                </a>
                                                <a href="update-progress.php?id=<?php echo $student['PhanCongID']; ?>" class="btn btn-sm btn-outline-success" title="Cập nhật tiến độ">
                                                    <i class="fas fa-tasks"></i>
                                                </a>
                                                <?php if (!empty($student['DeTaiID'])): ?>
                                                <a href="edit-thesis.php?id=<?php echo $student['DeTaiID']; ?>" class="btn btn-sm btn-outline-warning" title="Chỉnh sửa đề tài">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <div class="mb-3">
                                <i class="fas fa-user-graduate text-muted" style="font-size: 4rem;"></i>
                            </div>
                            <h4 class="mb-3">Chưa có sinh viên được phân công</h4>
                            <p class="text-muted mb-4">Bạn chưa được phân công hướng dẫn sinh viên nào.</p>
                            <a href="assign-student.php" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i> Thêm sinh viên
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
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

<script>
    $(document).ready(function() {
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

        $('#assignedStudentsTable').DataTable({
            language: vietnameseLanguage,
            pageLength: 10,
            lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "Tất cả"]],
            columnDefs: [
                { orderable: false, targets: 4 }
            ]
        });

        // Xử lý khi mở modal tạo đề tài
        $('#createThesisModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var studentId = button.data('student-id');
            var studentName = button.data('student-name');
            
            $('#modal_student_id').val(studentId);
            $('#modal_student_name').val(studentName);
        });
        
        // Validate form trước khi submit
        $('#createThesisForm').on('submit', function(e) {
            if (!$('#thesis_title').val().trim()) {
                e.preventDefault();
                alert('Vui lòng nhập tên đề tài');
                $('#thesis_title').focus();
                return false;
            }
            
            if (!$('#thesis_description').val().trim()) {
                e.preventDefault();
                alert('Vui lòng nhập mô tả đề tài');
                $('#thesis_description').focus();
                return false;
            }
        });
    });
</script>

<?php 
// Bao gồm footer - footer.php sẽ đóng thẻ .main-content và .app-container
include '../includes/faculty/footer.php'; 
?>