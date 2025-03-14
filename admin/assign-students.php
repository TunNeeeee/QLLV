<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
$success = '';
$error = '';
$logFile = __DIR__ . '/debug.log'; // Thêm dòng này để định nghĩa logFile

// Xử lý phân công sinh viên cho giảng viên
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_student'])) {
    $studentId = $_POST['student_id'];
    $facultyId = $_POST['faculty_id'];
    $startDate = $_POST['start_date'];
    $endDate = $_POST['end_date'];
    $note = $_POST['note'];
    
    // Debug values
    error_log("Phân công SV: studentId=$studentId, facultyId=$facultyId, startDate=$startDate");

    // Thêm ngay sau đoạn khai báo biến
    file_put_contents($logFile, "\n\n--- NEW REQUEST: " . date('Y-m-d H:i:s') . " ---\n", FILE_APPEND);

    try {
        // Bắt đầu transaction
        $db->beginTransaction();
        
        // Thêm debug trước khi bắt đầu transaction
        file_put_contents($logFile, "Starting transaction\n", FILE_APPEND);
        
        // Kiểm tra xem sinh viên đã được phân công chưa
        $db->query("SELECT * FROM SinhVienGiangVienHuongDan WHERE SinhVienID = :studentId");
        $db->bind(':studentId', $studentId);
        $existingAssignment = $db->single();
        
        if ($existingAssignment) {
            $error = "Sinh viên này đã được phân công cho giảng viên hướng dẫn!";
            $db->rollback(); // Rollback transaction nếu có lỗi
        } else {
            // Tạo bảng SinhVienGiangVienHuongDan nếu chưa có
            $db->query("CREATE TABLE IF NOT EXISTS `SinhVienGiangVienHuongDan` (
                `ID` int(11) NOT NULL AUTO_INCREMENT,
                `SinhVienID` int(11) NOT NULL,
                `GiangVienID` int(11) NOT NULL,
                `DeTaiID` int(11) DEFAULT NULL,
                `NgayBatDau` date NOT NULL,
                `NgayKetThucDuKien` date DEFAULT NULL,
                `TienDo` int(3) DEFAULT 0,
                `GhiChu` text DEFAULT NULL,
                `NgayTao` timestamp NOT NULL DEFAULT current_timestamp(),
                PRIMARY KEY (`ID`),
                UNIQUE KEY `SinhVien_GiangVien_Unique` (`SinhVienID`,`GiangVienID`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            
            // Thêm debug trước khi tạo bảng
            file_put_contents($logFile, "Checking/Creating table\n", FILE_APPEND);
            
            // Thực hiện phân công
            try {
                // Debug info
                $debugInfo = "SinhVienID: $studentId, GiangVienID: $facultyId, NgayBatDau: $startDate\n";
                file_put_contents($logFile, $debugInfo, FILE_APPEND);
                
                // Trước khi thực hiện INSERT, log chi tiết query
                $insert_query = "INSERT INTO SinhVienGiangVienHuongDan (SinhVienID, GiangVienID, NgayBatDau, NgayKetThucDuKien, GhiChu) VALUES (:studentId, :facultyId, :startDate, :endDate, :note)";
                file_put_contents($logFile, "Query to execute: $insert_query\n", FILE_APPEND);
                file_put_contents($logFile, "Params: studentId=$studentId, facultyId=$facultyId, startDate=$startDate, endDate=" . ($endDate ? $endDate : "NULL") . "\n", FILE_APPEND);
                
                $db->query("INSERT INTO SinhVienGiangVienHuongDan
                          (SinhVienID, GiangVienID, NgayBatDau, NgayKetThucDuKien, GhiChu) 
                          VALUES 
                          (:studentId, :facultyId, :startDate, :endDate, :note)");
                $db->bind(':studentId', $studentId);
                $db->bind(':facultyId', $facultyId);
                $db->bind(':startDate', $startDate);
                $db->bind(':endDate', $endDate ? $endDate : null);
                $db->bind(':note', $note);
                
                $result = $db->execute();
                
                // Log result
                file_put_contents($logFile, "Insert result: " . ($result ? "SUCCESS" : "FAILED") . "\n", FILE_APPEND);
                
            } catch (PDOException $e) {
                file_put_contents($logFile, "Error: " . $e->getMessage() . "\n", FILE_APPEND);
                throw $e; // Rethrow to be caught by the outer try-catch
            }
            
            if ($result) {
                $db->commit();
                $success = "Phân công sinh viên thành công!";
                // Gửi thông báo cho sinh viên và giảng viên
                sendAssignmentNotification($db, $studentId, $facultyId);
            } else {
                $db->rollback();
                $error = "Không thể phân công sinh viên. Vui lòng thử lại!";
                error_log("Phân công SV thất bại không có ngoại lệ");
            }
        }
    } catch (PDOException $e) {
        if ($db->inTransaction()) { // Thêm phương thức inTransaction() vào class Database
            $db->rollback();
        }
        $error = "Lỗi: " . $e->getMessage();
        error_log("Phân công SV thất bại: " . $e->getMessage());
    }
}

// Lấy danh sách sinh viên chưa được phân công
$unassignedStudents = [];
try {
    $db->query("SELECT sv.*, u.Email FROM SinhVien sv 
                JOIN Users u ON sv.UserID = u.UserID 
                WHERE sv.SinhVienID NOT IN (
                    SELECT SinhVienID FROM SinhVienGiangVienHuongDan
                )
                ORDER BY sv.HoTen");
    $unassignedStudents = $db->resultSet();
} catch (PDOException $e) {
    $error = "Lỗi khi lấy danh sách sinh viên: " . $e->getMessage();
}

// Lấy danh sách giảng viên
$faculty = [];
try {
    $db->query("SELECT gv.*, u.Email, 
                (SELECT COUNT(*) FROM SinhVienGiangVienHuongDan WHERE GiangVienID = gv.GiangVienID) as SoSinhVien 
                FROM GiangVien gv 
                JOIN Users u ON gv.UserID = u.UserID 
                ORDER BY gv.HoTen");
    $faculty = $db->resultSet();
} catch (PDOException $e) {
    $error = "Lỗi khi lấy danh sách giảng viên: " . $e->getMessage();
}

// Lấy danh sách phân công hiện có
$assignments = [];
try {
    $db->query("SELECT svgv.*, sv.HoTen as TenSinhVien, sv.MaSV, gv.HoTen as TenGiangVien, 
                dt.TenDeTai, dt.TrangThai as TrangThaiDeTai
                FROM SinhVienGiangVienHuongDan svgv
                JOIN SinhVien sv ON svgv.SinhVienID = sv.SinhVienID
                JOIN GiangVien gv ON svgv.GiangVienID = gv.GiangVienID
                LEFT JOIN DeTai dt ON svgv.DeTaiID = dt.DeTaiID
                ORDER BY svgv.NgayBatDau DESC"); // Thay thế NgayTao bằng NgayBatDau
    $assignments = $db->resultSet();
} catch (PDOException $e) {
    $error = "Lỗi khi lấy danh sách phân công: " . $e->getMessage();
}

// Sửa lại hàm sendAssignmentNotification
function sendAssignmentNotification($db, $studentId, $facultyId) {
    try {
        // Tạo bảng thông báo nếu chưa có
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
        
        // Kiểm tra xem cột LoaiThongBao có tồn tại không
        $db->query("SHOW COLUMNS FROM ThongBao LIKE 'LoaiThongBao'");
        $loaiThongBaoExists = $db->rowCount() > 0;
        
        // Thêm cột nếu không tồn tại
        if (!$loaiThongBaoExists) {
            $db->query("ALTER TABLE ThongBao ADD COLUMN LoaiThongBao varchar(50) DEFAULT NULL");
            $db->execute();
        }
        
        // Kiểm tra xem cột LienKet có tồn tại không
        $db->query("SHOW COLUMNS FROM ThongBao LIKE 'LienKet'");
        $lienKetExists = $db->rowCount() > 0;
        
        // Thêm cột nếu không tồn tại
        if (!$lienKetExists) {
            $db->query("ALTER TABLE ThongBao ADD COLUMN LienKet varchar(255) DEFAULT NULL");
            $db->execute();
        }
        
        // Lấy thông tin sinh viên
        $db->query("SELECT sv.HoTen, u.UserID FROM SinhVien sv JOIN Users u ON sv.UserID = u.UserID WHERE sv.SinhVienID = :studentId");
        $db->bind(':studentId', $studentId);
        $student = $db->single();
        
        // Lấy thông tin giảng viên
        $db->query("SELECT gv.HoTen, u.UserID FROM GiangVien gv JOIN Users u ON gv.UserID = u.UserID WHERE gv.GiangVienID = :facultyId");
        $db->bind(':facultyId', $facultyId);
        $faculty = $db->single();
        
        // Gửi thông báo cho sinh viên
        if ($loaiThongBaoExists && $lienKetExists) {
            // Sử dụng câu lệnh insert với cột LoaiThongBao và LienKet
            $db->query("INSERT INTO ThongBao (UserID, TieuDe, NoiDung, LoaiThongBao, LienKet) 
                      VALUES (:userId, :title, :content, 'phân công', 'student/thesis-info.php')");
        } else {
            // Sử dụng câu lệnh insert không có cột LoaiThongBao và LienKet
            $db->query("INSERT INTO ThongBao (UserID, TieuDe, NoiDung) 
                      VALUES (:userId, :title, :content)");
        }
        
        $db->bind(':userId', $student['UserID']);
        $db->bind(':title', 'Đã được phân công giảng viên hướng dẫn');
        $db->bind(':content', 'Bạn đã được phân công ' . $faculty['HoTen'] . ' làm giảng viên hướng dẫn. Vui lòng liên hệ để thảo luận về đề tài luận văn.');
        $db->execute();
        
        // Gửi thông báo cho giảng viên
        if ($loaiThongBaoExists && $lienKetExists) {
            // Sử dụng câu lệnh insert với cột LoaiThongBao và LienKet
            $db->query("INSERT INTO ThongBao (UserID, TieuDe, NoiDung, LoaiThongBao, LienKet) 
                      VALUES (:userId, :title, :content, 'phân công', 'faculty/assigned-students.php')");
        } else {
            // Sử dụng câu lệnh insert không có cột LoaiThongBao và LienKet
            $db->query("INSERT INTO ThongBao (UserID, TieuDe, NoiDung) 
                      VALUES (:userId, :title, :content)");
        }
        
        $db->bind(':userId', $faculty['UserID']);
        $db->bind(':title', 'Sinh viên mới được phân công');
        $db->bind(':content', 'Bạn đã được phân công hướng dẫn sinh viên ' . $student['HoTen'] . '. Vui lòng liên hệ để thảo luận về đề tài luận văn.');
        $db->execute();
        
        return true;
    } catch (PDOException $e) {
        error_log("Lỗi khi gửi thông báo phân công: " . $e->getMessage());
        return false;
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phân công sinh viên - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
    <!-- CSS Styling tương tự như trong các file khác -->
</head>
<body>
    <div class="app-container">
        <!-- Sidebar giống như trong file dashboard.php -->

        <!-- Main Content -->
        <div class="main-content">
            <!-- Page Header -->
            <div class="page-header mb-4">
                <h1 class="h3">Phân công sinh viên</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Trang chủ</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Phân công sinh viên</li>
                    </ol>
                </nav>
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
            
            <div class="row">
                <!-- Form phân công sinh viên -->
                <div class="col-lg-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-user-plus me-2"></i>Phân công sinh viên cho giảng viên</h5>
                        </div>
                        <div class="card-body">
                            <?php if (count($unassignedStudents) > 0 && count($faculty) > 0): ?>
                            <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" id="assignForm">
                                <div class="mb-3">
                                    <label for="student_id" class="form-label">Chọn sinh viên</label>
                                    <select class="form-select select2" id="student_id" name="student_id" required>
                                        <option value="">-- Chọn sinh viên --</option>
                                        <?php foreach ($unassignedStudents as $student): ?>
                                        <option value="<?php echo $student['SinhVienID']; ?>">
                                            <?php echo htmlspecialchars($student['MaSV'] . ' - ' . $student['HoTen'] . ' (' . $student['Khoa'] . ')'); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text">Chỉ hiển thị sinh viên chưa được phân công giảng viên</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="faculty_id" class="form-label">Chọn giảng viên hướng dẫn</label>
                                    <select class="form-select select2" id="faculty_id" name="faculty_id" required>
                                        <option value="">-- Chọn giảng viên --</option>
                                        <?php foreach ($faculty as $lecturer): ?>
                                        <option value="<?php echo $lecturer['GiangVienID']; ?>">
                                            <?php echo htmlspecialchars($lecturer['HocVi'] . ' ' . $lecturer['HoTen'] . ' (' . $lecturer['SoSinhVien'] . ' SV)'); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="start_date" class="form-label">Ngày bắt đầu <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo date('Y-m-d'); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="end_date" class="form-label">Ngày kết thúc dự kiến</label>
                                            <input type="date" class="form-control" id="end_date" name="end_date">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="note" class="form-label">Ghi chú</label>
                                    <textarea class="form-control" id="note" name="note" rows="2" placeholder="Nhập ghi chú (nếu có)"></textarea>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" name="assign_student" class="btn btn-primary">
                                        <i class="fas fa-user-check me-2"></i>Phân công
                                    </button>
                                </div>
                            </form>
                            <?php else: ?>
                            <div class="alert alert-info mb-0">
                                <?php if (count($unassignedStudents) == 0): ?>
                                <i class="fas fa-info-circle me-2"></i> Tất cả sinh viên đã được phân công giảng viên hướng dẫn.
                                <?php elseif (count($faculty) == 0): ?>
                                <i class="fas fa-info-circle me-2"></i> Không có giảng viên nào trong hệ thống.
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Form phân công hàng loạt -->
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-users me-2"></i>Phân công hàng loạt</h5>
                        </div>
                        <div class="card-body">
                            <?php if (count($unassignedStudents) > 0 && count($faculty) > 0): ?>
                            <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" id="bulkAssignForm">
                                <div class="mb-3">
                                    <label for="student_ids" class="form-label">Chọn nhiều sinh viên</label>
                                    <select class="form-select select2" id="student_ids" name="student_ids[]" multiple required>
                                        <?php foreach ($unassignedStudents as $student): ?>
                                        <option value="<?php echo $student['SinhVienID']; ?>">
                                            <?php echo htmlspecialchars($student['MaSV'] . ' - ' . $student['HoTen'] . ' (' . $student['Khoa'] . ')'); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="bulk_faculty_id" class="form-label">Chọn giảng viên hướng dẫn</label>
                                    <select class="form-select select2" id="bulk_faculty_id" name="faculty_id" required>
                                        <option value="">-- Chọn giảng viên --</option>
                                        <?php foreach ($faculty as $lecturer): ?>
                                        <option value="<?php echo $lecturer['GiangVienID']; ?>">
                                            <?php echo htmlspecialchars($lecturer['HocVi'] . ' ' . $lecturer['HoTen'] . ' (' . $lecturer['SoSinhVien'] . ' SV)'); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <input type="hidden" name="bulk_assign" value="1">
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-users-cog me-2"></i>Phân công hàng loạt
                                    </button>
                                </div>
                            </form>
                            <?php else: ?>
                            <div class="alert alert-info mb-0">
                                <i class="fas fa-info-circle me-2"></i> Không đủ điều kiện để phân công hàng loạt.
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Danh sách sinh viên đã phân công -->
                <div class="col-lg-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-list me-2"></i>Danh sách phân công</h5>
                        </div>
                        <div class="card-body">
                            <?php if (count($assignments) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover" id="assignedTable">
                                    <thead>
                                        <tr>
                                            <th>MSSV</th>
                                            <th>Họ tên SV</th>
                                            <th>Giảng viên HD</th>
                                            <th>Đề tài</th>
                                            <th>Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($assignments as $assignment): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($assignment['MaSV']); ?></td>
                                            <td>
                                                <a href="student-details.php?id=<?php echo $assignment['SinhVienID']; ?>" class="fw-bold text-decoration-none">
                                                    <?php echo htmlspecialchars($assignment['TenSinhVien']); ?>
                                                </a>
                                            </td>
                                            <td>
                                                <a href="faculty-details.php?id=<?php echo $assignment['GiangVienID']; ?>" class="text-decoration-none">
                                                    <?php echo htmlspecialchars($assignment['TenGiangVien']); ?>
                                                </a>
                                            </td>
                                            <td>
                                                <?php if ($assignment['DeTaiID']): ?>
                                                <a href="thesis-details.php?id=<?php echo $assignment['DeTaiID']; ?>" class="text-decoration-none">
                                                    <?php echo htmlspecialchars($assignment['TenDeTai']); ?>
                                                </a>
                                                <?php else: ?>
                                                <span class="text-muted">Chưa có đề tài</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="edit-assignment.php?id=<?php echo $assignment['ID']; ?>" class="btn btn-primary" title="Chỉnh sửa">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-danger delete-assignment" data-id="<?php echo $assignment['ID']; ?>" title="Xóa">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <div class="alert alert-info mb-0">
                                <i class="fas fa-info-circle me-2"></i> Chưa có sinh viên nào được phân công giảng viên hướng dẫn.
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal xác nhận xóa -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Xác nhận xóa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Bạn có chắc chắn muốn xóa phân công này không? Hành động này không thể hoàn tác.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <a href="#" class="btn btn-danger" id="confirmDelete">Xóa</a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            // Khởi tạo DataTables
            $('#assignedTable').DataTable({
                language: {
                    url: "//cdn.datatables.net/plug-ins/1.11.5/i18n/vi.json"
                },
                responsive: true,
                order: [[2, 'asc'], [1, 'asc']]
            });
            
            // Khởi tạo Select2
            $('.select2').select2({
                theme: 'bootstrap-5'
            });
            
            // Xử lý xóa phân công
            $('.delete-assignment').click(function() {
                var assignmentId = $(this).data('id');
                $('#confirmDelete').attr('href', 'delete-assignment.php?id=' + assignmentId);
                $('#deleteModal').modal('show');
            });
        });
    </script>
</body>
</html>