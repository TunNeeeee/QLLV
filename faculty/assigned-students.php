<?php
// Các phần include và kiểm tra quyền truy cập
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../classes/Auth.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$auth = new Auth();
if (!$auth->isLoggedIn() || $auth->getUserRole() != 'faculty') {
    header('Location: ' . BASE_URL . 'auth/login.php?error=unauthorized_access');
    exit;
}

$db = new Database();
$error = '';
$success = ''; // Thêm dòng này

// Lấy ID của giảng viên đang đăng nhập
$userID = $_SESSION['user_id'];

// Sửa truy vấn lấy thông tin giảng viên để bao gồm email
try {
    $db->query("SELECT gv.GiangVienID, gv.HoTen, gv.HocVi, u.Email 
                FROM GiangVien gv 
                JOIN Users u ON gv.UserID = u.UserID 
                WHERE gv.UserID = :userID");
    $db->bind(':userID', $userID);
    $facultyDetails = $db->single();
    $facultyId = $facultyDetails['GiangVienID'];
} catch (PDOException $e) {
    $error = "Lỗi khi lấy thông tin giảng viên: " . $e->getMessage();
}

// Lấy danh sách sinh viên được phân công
$assignedStudents = [];
try {
    // Thay thế câu truy vấn hiện tại bằng câu truy vấn mới sử dụng LEFT JOIN
    $db->query("SELECT sv.*, u.Email, 
                svgv.ID as PhanCongID, svgv.NgayBatDau, svgv.NgayKetThucDuKien, 
                svgv.DeTaiID, svgv.GhiChu,
                dt.TenDeTai, dt.TrangThai
                FROM SinhVienGiangVienHuongDan svgv
                JOIN SinhVien sv ON svgv.SinhVienID = sv.SinhVienID
                JOIN Users u ON sv.UserID = u.UserID
                LEFT JOIN DeTai dt ON svgv.DeTaiID = dt.DeTaiID
                WHERE svgv.GiangVienID = :facultyId
                ORDER BY svgv.NgayBatDau DESC");
    $db->bind(':facultyId', $facultyId);
    
    $assignedStudents = $db->resultSet();
    // Debug - ghi log số lượng sinh viên được tìm thấy
    error_log("Faculty ID: $facultyId, Found " . count($assignedStudents) . " students");
} catch (PDOException $e) {
    $error = "Lỗi khi lấy danh sách sinh viên hướng dẫn: " . $e->getMessage();
    error_log($error);
}

// Thay thế đoạn code đếm số lượng sinh viên
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
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách sinh viên - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
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
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">Danh sách sinh viên được phân công</h1>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-table me-1"></i>
                Thông tin giảng viên
            </div>
            <div class="card-body">
                <p><strong>Tên:</strong> <?php echo $facultyDetails['HoTen']; ?></p>
                <p><strong>Email:</strong> <?php echo $facultyDetails['Email']; ?></p>
            </div>
        </div>
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-table me-1"></i>
                Thống kê sinh viên
            </div>
            <div class="card-body">
                <p><strong>Số lượng sinh viên đã hoàn thành:</strong> <?php echo $completedCount; ?></p>
                <p><strong>Số lượng sinh viên đang thực hiện:</strong> <?php echo $inProgressCount; ?></p>
                <p><strong>Số lượng sinh viên chưa bắt đầu:</strong> <?php echo $notStartedCount; ?></p>
            </div>
        </div>
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-table me-1"></i>
                Danh sách sinh viên
            </div>
            <div class="card-body">
                <table id="datatablesSimple" class="table table-striped">
                    <thead>
                        <tr>
                            <th>Tên sinh viên</th>
                            <th>Email</th>
                            <th>Đề tài</th>
                            <th>Trạng thái đề tài</th>
                            <th>Ngày bắt đầu</th>
                            <th>Ngày kết thúc dự kiến</th>
                            <th>Tiến độ</th>
                            <th>Ghi chú</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assignedStudents as $student): ?>
                            <tr>
                                <td><?php echo $student['HoTen']; ?></td>
                                <td><?php echo $student['Email']; ?></td>
                                <td><?php echo $student['TenDeTai']; ?></td>
                                <td><?php echo $student['TrangThai']; ?></td>
                                <td><?php echo $student['NgayBatDau']; ?></td>
                                <td><?php echo $student['NgayKetThucDuKien']; ?></td>
                                <td>
                                    <div class="progress">
                                        <div class="progress-bar bg-success" role="progressbar" 
                                             style="width: <?php echo isset($student['TienDo']) ? $student['TienDo'] : 0; ?>%" 
                                             aria-valuenow="<?php echo isset($student['TienDo']) ? $student['TienDo'] : 0; ?>" 
                                             aria-valuemin="0" aria-valuemax="100">
                                             <?php echo isset($student['TienDo']) ? $student['TienDo'] : 0; ?>%
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo $student['GhiChu']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-plus me-1"></i>
                Tạo đề tài mới cho sinh viên
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="student_id" class="form-label">Chọn sinh viên</label>
                        <select class="form-select" id="student_id" name="student_id" required>
                            <option value="">Chọn sinh viên</option>
                            <?php foreach ($assignedStudents as $student): ?>
                                <option value="<?php echo $student['SinhVienID']; ?>"><?php echo $student['HoTen']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="thesis_title" class="form-label">Tên đề tài</label>
                        <input type="text" class="form-control" id="thesis_title" name="thesis_title" required>
                    </div>
                    <div class="mb-3">
                        <label for="thesis_description" class="form-label">Mô tả đề tài</label>
                        <textarea class="form-control" id="thesis_description" name="thesis_description" rows="3" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary" name="create_thesis">Tạo đề tài</button>
                </form>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-user-graduate me-2"></i>Danh sách sinh viên hướng dẫn</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($assignedStudents)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped" id="assignedStudentsTable">
                            <thead>
                                <tr>
                                    <th>MSSV</th>
                                    <th>Họ tên</th>
                                    <th>Email</th>
                                    <th>Đề tài</th>
                                    <th>Ngày bắt đầu</th>
                                    <th>Tiến độ</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($assignedStudents as $student): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['MaSV']); ?></td>
                                    <td>
                                        <a href="student-details.php?id=<?php echo $student['SinhVienID']; ?>" class="text-decoration-none">
                                            <?php echo htmlspecialchars($student['HoTen']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($student['Email']); ?></td>
                                    <td>
                                        <?php if (!empty($student['DeTaiID']) && !empty($student['TenDeTai'])): ?>
                                            <a href="thesis-details.php?id=<?php echo $student['DeTaiID']; ?>" class="text-decoration-none">
                                                <?php echo htmlspecialchars($student['TenDeTai']); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">Chưa có đề tài</span>
                                            <a href="assign-thesis.php?student_id=<?php echo $student['SinhVienID']; ?>" class="btn btn-sm btn-outline-primary ms-2">
                                                <i class="fas fa-plus-circle"></i> Gán đề tài
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($student['NgayBatDau'])); ?></td>
                                    <td>
                                        <div class="progress">
                                            <div class="progress-bar bg-success" role="progressbar" 
                                                 style="width: <?php echo isset($student['TienDo']) ? $student['TienDo'] : 0; ?>%" 
                                                 aria-valuenow="<?php echo isset($student['TienDo']) ? $student['TienDo'] : 0; ?>" 
                                                 aria-valuemin="0" aria-valuemax="100">
                                                 <?php echo isset($student['TienDo']) ? $student['TienDo'] : 0; ?>%
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="student-discussion.php?id=<?php echo $student['SinhVienID']; ?>" class="btn btn-primary" title="Trao đổi">
                                                <i class="fas fa-comments"></i>
                                            </a>
                                            <a href="update-progress.php?id=<?php echo $student['PhanCongID']; ?>" class="btn btn-success" title="Cập nhật tiến độ">
                                                <i class="fas fa-tasks"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle me-2"></i> Bạn chưa được phân công hướng dẫn sinh viên nào.
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <!-- Debug info: -->
        <!-- Faculty ID: <?php echo $facultyId; ?> -->
        <!-- Query: SELECT sv.*, u.Email, svgv.NgayBatDau, svgv.NgayKetThucDuKien, dt.TenDeTai FROM SinhVien sv JOIN SinhVienGiangVienHuongDan svgv ON sv.SinhVienID = svgv.SinhVienID LEFT JOIN DeTai dt ON svgv.DeTaiID = dt.DeTaiID JOIN Users u ON sv.UserID = u.UserID WHERE svgv.GiangVienID = <?php echo $facultyId; ?> -->
        <!-- Found: <?php echo count($assignedStudents); ?> students -->

        <?php
        // Nếu không tìm thấy sinh viên, thử kiểm tra dữ liệu trong bảng phân công
        if (count($assignedStudents) == 0) {
            try {
                $db->query("SELECT * FROM SinhVienGiangVienHuongDan WHERE GiangVienID = :facultyId");
                $db->bind(':facultyId', $facultyId);
                $assignments = $db->resultSet();
                
                echo "<!-- Raw assignments: " . count($assignments) . " -->";
                foreach ($assignments as $a) {
                    echo "<!-- Assignment ID: " . $a['ID'] . ", SinhVienID: " . $a['SinhVienID'] . ", GiangVienID: " . $a['GiangVienID'] . " -->";
                }
            } catch (PDOException $e) {
                echo "<!-- Debug error: " . $e->getMessage() . " -->";
            }
        }
        ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#datatablesSimple').DataTable();
        });
    </script>
    <script>
        console.log('Faculty ID: <?php echo $facultyId; ?>');
        console.log('Assigned students count: <?php echo count($assignedStudents); ?>');

        // Debug SQL
        <?php if (empty($assignedStudents)): ?>
        console.log('No students found for this faculty');
        <?php endif; ?>
    </script>
</body>
</html>