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

// Xử lý gửi yêu cầu phân công sinh viên
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['student_id']) && isset($_POST['thesis_id'])) {
    $studentId = $_POST['student_id'];
    $thesisId = $_POST['thesis_id'];
    $note = isset($_POST['note']) ? $_POST['note'] : '';
    
    try {
        // Kiểm tra bảng YeuCauPhanCong có tồn tại chưa
        $db->query("SHOW TABLES LIKE 'YeuCauPhanCong'");
        $tableExists = $db->rowCount() > 0;
        
        if (!$tableExists) {
            // Tạo bảng YeuCauPhanCong nếu chưa tồn tại
            $db->query("CREATE TABLE IF NOT EXISTS YeuCauPhanCong (
                YeuCauID int(11) NOT NULL AUTO_INCREMENT,
                GiangVienID int(11) NOT NULL,
                SinhVienID int(11) NOT NULL,
                DeTaiID int(11) NOT NULL,
                GhiChu text DEFAULT NULL,
                TrangThai enum('Chờ duyệt','Đã duyệt','Từ chối') DEFAULT 'Chờ duyệt',
                NgayYeuCau datetime DEFAULT current_timestamp(),
                NgayDuyet datetime DEFAULT NULL,
                NguoiDuyetID int(11) DEFAULT NULL,
                PRIMARY KEY (YeuCauID),
                KEY GiangVienID (GiangVienID),
                KEY SinhVienID (SinhVienID),
                KEY DeTaiID (DeTaiID)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        
        // Kiểm tra sinh viên đã được phân công chưa
        $db->query("SELECT * FROM SinhVienGiangVienHuongDan WHERE SinhVienID = :studentId");
        $db->bind(':studentId', $studentId);
        if ($db->rowCount() > 0) {
            $error = "Sinh viên đã được phân công cho giảng viên khác";
        } else {
            // Kiểm tra đã gửi yêu cầu cho sinh viên này chưa
            $db->query("SELECT * FROM YeuCauPhanCong WHERE SinhVienID = :studentId AND GiangVienID = :facultyId AND TrangThai = 'Chờ duyệt'");
            $db->bind(':studentId', $studentId);
            $db->bind(':facultyId', $facultyId);
            if ($db->rowCount() > 0) {
                $error = "Bạn đã gửi yêu cầu phân công cho sinh viên này rồi";
            } else {
                // Tạo yêu cầu phân công mới
                $db->query("INSERT INTO YeuCauPhanCong (GiangVienID, SinhVienID, DeTaiID, GhiChu) 
                           VALUES (:facultyId, :studentId, :thesisId, :note)");
                $db->bind(':facultyId', $facultyId);
                $db->bind(':studentId', $studentId);
                $db->bind(':thesisId', $thesisId);
                $db->bind(':note', $note);
                
                if ($db->execute()) {
                    $success = "Đã gửi yêu cầu phân công sinh viên thành công. Vui lòng chờ quản trị viên phê duyệt.";
                } else {
                    $error = "Có lỗi xảy ra khi gửi yêu cầu phân công";
                }
            }
        }
    } catch (PDOException $e) {
        $error = "Lỗi hệ thống: " . $e->getMessage();
    }
}

// Lấy danh sách sinh viên đã được phân công
$assignedStudents = [];
try {
    // Kiểm tra bảng SinhVienGiangVienHuongDan có tồn tại chưa
    $db->query("SHOW TABLES LIKE 'SinhVienGiangVienHuongDan'");
    $svgvTableExists = $db->rowCount() > 0;
    
    if ($svgvTableExists) {
        $db->query("SELECT sv.*, u.Email, 
                   svgv.NgayBatDau, svgv.NgayKetThucDuKien, 
                   dt.TenDeTai, dt.TrangThai
                   FROM SinhVien sv
                   JOIN SinhVienGiangVienHuongDan svgv ON sv.SinhVienID = svgv.SinhVienID
                   JOIN DeTai dt ON svgv.DeTaiID = dt.DeTaiID
                   JOIN Users u ON sv.UserID = u.UserID
                   WHERE svgv.GiangVienID = :facultyId
                   ORDER BY svgv.NgayBatDau DESC");
        $db->bind(':facultyId', $facultyId);
        $assignedStudents = $db->resultSet();
    }
} catch (PDOException $e) {
    $error = "Lỗi khi lấy danh sách sinh viên: " . $e->getMessage();
}

// Lấy danh sách sinh viên chưa được phân công
$unassignedStudents = [];
try {
    $db->query("SELECT sv.*, u.Email 
               FROM SinhVien sv
               JOIN Users u ON sv.UserID = u.UserID
               WHERE sv.SinhVienID NOT IN (
                   SELECT DISTINCT SinhVienID FROM SinhVienGiangVienHuongDan
               )
               ORDER BY sv.HoTen");
    $unassignedStudents = $db->resultSet();
} catch (PDOException $e) {
    $error = "Lỗi khi lấy danh sách sinh viên chưa phân công: " . $e->getMessage();
}

// Lấy danh sách đề tài của giảng viên
$theses = [];
try {
    // Kiểm tra cấu trúc bảng DeTai và điều chỉnh truy vấn
    $db->query("DESCRIBE DeTai");
    $columns = $db->resultSet();
    $hasGiangVienID = false;

    foreach ($columns as $col) {
        if ($col['Field'] === 'GiangVienID') {
            $hasGiangVienID = true;
            break;
        }
    }

    if ($hasGiangVienID) {
        // Nếu bảng DeTai có cột GiangVienID
        $db->query("SELECT * FROM DeTai WHERE GiangVienID = :facultyId");
        $db->bind(':facultyId', $facultyId);
    } else {
        // Trường hợp bảng DeTai không có cột GiangVienID - dùng bảng liên kết
        $db->query("SELECT dt.* FROM DeTai dt 
                   JOIN SinhVienGiangVienHuongDan svgv ON dt.DeTaiID = svgv.DeTaiID
                   WHERE svgv.GiangVienID = :facultyId
                   GROUP BY dt.DeTaiID");
        $db->bind(':facultyId', $facultyId);
    }
    $theses = $db->resultSet();
} catch (PDOException $e) {
    $error = "Lỗi khi lấy danh sách đề tài: " . $e->getMessage();
}

// Lấy danh sách yêu cầu phân công đã gửi
$requests = [];
try {
    $db->query("SHOW TABLES LIKE 'YeuCauPhanCong'");
    $tableExists = $db->rowCount() > 0;
    
    if ($tableExists) {
        $db->query("SELECT yc.*, sv.HoTen as TenSinhVien, sv.MaSV, dt.TenDeTai 
                   FROM YeuCauPhanCong yc
                   JOIN SinhVien sv ON yc.SinhVienID = sv.SinhVienID
                   JOIN DeTai dt ON yc.DeTaiID = dt.DeTaiID
                   WHERE yc.GiangVienID = :facultyId
                   ORDER BY yc.NgayYeuCau DESC");
        $db->bind(':facultyId', $facultyId);
        $requests = $db->resultSet();
    }
} catch (PDOException $e) {
    $error = "Lỗi khi lấy danh sách yêu cầu phân công: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý sinh viên - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #f8f9fc;
            --accent-color: #3a0ca3;
            --text-color: #333;
            --light-text: #6c757d;
            --border-color: #e3e6f0;
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
        
        .sidebar {
            width: 250px;
            background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
            color: #fff;
            position: fixed;
            height: 100%;
            z-index: 100;
            transition: all 0.3s;
        }
        
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 2rem;
            transition: all 0.3s;
        }
        
        .card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.07);
            margin-bottom: 1.5rem;
            transition: transform 0.2s;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding: 1.2rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-header h5 {
            font-weight: 600;
            margin: 0;
        }
        
        @media (max-width: 991.98px) {
            .sidebar {
                width: 70px;
            }
            
            .main-content {
                margin-left: 70px;
            }
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar giống như trong dashboard.php -->

        <!-- Main Content -->
        <div class="main-content">
            <div class="page-header mb-4">
                <h1 class="h3">Quản lý sinh viên</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Trang chủ</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Quản lý sinh viên</li>
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
                <!-- Sinh viên đang hướng dẫn -->
                <div class="col-lg-12 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-user-graduate me-2"></i>Sinh viên đang hướng dẫn</h5>
                        </div>
                        <div class="card-body">
                            <?php if (count($assignedStudents) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover" id="assignedStudentsTable">
                                    <thead>
                                        <tr>
                                            <th>MSSV</th>
                                            <th>Họ tên</th>
                                            <th>Email</th>
                                            <th>Đề tài</th>
                                            <th>Ngày bắt đầu</th>
                                            <th>Ngày kết thúc dự kiến</th>
                                            <th>Trạng thái</th>
                                            <th>Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($assignedStudents as $student): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($student['MaSV']); ?></td>
                                            <td>
                                                <a href="student-details.php?id=<?php echo $student['SinhVienID']; ?>" class="fw-bold text-decoration-none">
                                                    <?php echo htmlspecialchars($student['HoTen']); ?>
                                                </a>
                                            </td>
                                            <td><?php echo htmlspecialchars($student['Email']); ?></td>
                                            <td><?php echo htmlspecialchars($student['TenDeTai']); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($student['NgayBatDau'])); ?></td>
                                            <td><?php echo $student['NgayKetThucDuKien'] ? date('d/m/Y', strtotime($student['NgayKetThucDuKien'])) : 'Chưa xác định'; ?></td>
                                            <td>
                                                <?php 
                                                switch ($student['TrangThai']) {
                                                    case 'Đang thực hiện':
                                                        echo '<span class="badge bg-primary">Đang thực hiện</span>';
                                                        break;
                                                    case 'Hoàn thành':
                                                        echo '<span class="badge bg-success">Hoàn thành</span>';
                                                        break;
                                                    case 'Chưa bắt đầu':
                                                        echo '<span class="badge bg-warning text-dark">Chưa bắt đầu</span>';
                                                        break;
                                                    default:
                                                        echo '<span class="badge bg-secondary">Không xác định</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="student-details.php?id=<?php echo $student['SinhVienID']; ?>" class="btn btn-info" title="Xem chi tiết">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="update-progress.php?id=<?php echo $student['SinhVienID']; ?>" class="btn btn-primary" title="Cập nhật tiến độ">
                                                        <i class="fas fa-tasks"></i>
                                                    </a>
                                                    <a href="schedule-meeting.php?id=<?php echo $student['SinhVienID']; ?>" class="btn btn-warning" title="Lịch gặp">
                                                        <i class="fas fa-calendar-alt"></i>
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
                                <i class="fas fa-info-circle me-2"></i> Bạn chưa có sinh viên nào được phân công. Hãy gửi yêu cầu phân công sinh viên bên dưới.
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Form yêu cầu thêm sinh viên -->
                <div class="col-lg-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-user-plus me-2"></i>Yêu cầu phân công sinh viên</h5>
                        </div>
                        <div class="card-body">
                            <?php if (count($unassignedStudents) > 0 && count($theses) > 0): ?>
                            <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" id="assignForm">
                                <div class="mb-3">
                                    <label for="student_id" class="form-label">Chọn sinh viên</label>
                                    <select class="form-select" id="student_id" name="student_id" required>
                                        <option value="">-- Chọn sinh viên --</option>
                                        <?php foreach ($unassignedStudents as $student): ?>
                                        <option value="<?php echo $student['SinhVienID']; ?>">
                                            <?php echo htmlspecialchars($student['MaSV'] . ' - ' . $student['HoTen']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text">Chỉ hiển thị sinh viên chưa được phân công giảng viên</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="thesis_id" class="form-label">Chọn đề tài</label>
                                    <select class="form-select" id="thesis_id" name="thesis_id" required>
                                        <option value="">-- Chọn đề tài --</option>
                                        <?php foreach ($theses as $thesis): ?>
                                        <option value="<?php echo $thesis['DeTaiID']; ?>">
                                            <?php echo htmlspecialchars($thesis['TenDeTai']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="note" class="form-label">Ghi chú</label>
                                    <textarea class="form-control" id="note" name="note" rows="3" placeholder="Nhập ghi chú (nếu có)"></textarea>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-paper-plane me-2"></i>Gửi yêu cầu phân công
                                    </button>
                                </div>
                            </form>
                            <?php elseif (count($theses) == 0): ?>
                            <div class="alert alert-warning mb-0">
                                <i class="fas fa-exclamation-triangle me-2"></i> Bạn chưa có đề tài nào. Vui lòng thêm đề tài trước khi phân công sinh viên.
                                <div class="mt-3">
                                    <a href="manage-thesis.php" class="btn btn-sm btn-primary">Quản lý đề tài</a>
                                </div>
                            </div>
                            <?php else: ?>
                            <div class="alert alert-info mb-0">
                                <i class="fas fa-info-circle me-2"></i> Không có sinh viên nào chưa được phân công giảng viên.
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Danh sách yêu cầu phân công đã gửi -->
                <div class="col-lg-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-history me-2"></i>Yêu cầu phân công đã gửi</h5>
                        </div>
                        <div class="card-body">
                            <?php if (count($requests) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-sm" id="requestsTable">
                                    <thead>
                                        <tr>
                                            <th>Sinh viên</th>
                                            <th>MSSV</th>
                                            <th>Đề tài</th>
                                            <th>Ngày yêu cầu</th>
                                            <th>Trạng thái</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($requests as $request): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($request['TenSinhVien']); ?></td>
                                            <td><?php echo htmlspecialchars($request['MaSV']); ?></td>
                                            <td><?php echo htmlspecialchars($request['TenDeTai']); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($request['NgayYeuCau'])); ?></td>
                                            <td>
                                                <?php
                                                switch ($request['TrangThai']) {
                                                    case 'Đã duyệt':
                                                        echo '<span class="badge bg-success">Đã duyệt</span>';
                                                        break;
                                                    case 'Từ chối':
                                                        echo '<span class="badge bg-danger">Từ chối</span>';
                                                        break;
                                                    case 'Chờ duyệt':
                                                    default:
                                                        echo '<span class="badge bg-warning text-dark">Chờ duyệt</span>';
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <div class="alert alert-info mb-0">
                                <i class="fas fa-info-circle me-2"></i> Bạn chưa gửi yêu cầu phân công nào.
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            // Khởi tạo DataTables
            $('#assignedStudentsTable').DataTable({
                language: {
                    url: "//cdn.datatables.net/plug-ins/1.11.5/i18n/vi.json"
                },
                responsive: true,
                order: [[1, 'asc']]
            });
            
            $('#requestsTable').DataTable({
                language: {
                    url: "//cdn.datatables.net/plug-ins/1.11.5/i18n/vi.json"
                },
                responsive: true,
                order: [[3, 'desc']]
            });
        });
    </script>
</body>
</html>