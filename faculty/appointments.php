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

// Kiểm tra và tạo bảng LichGap nếu chưa tồn tại (không hiển thị thông báo)
try {
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

    // Kiểm tra cấu trúc thực tế của bảng LichGap (xử lí ngầm)
    $db->query("DESCRIBE LichGap");
    $columns = $db->resultSet();
    
    $hasGiangVienID = false;
    $hasIdGiangVien = false;
    
    foreach ($columns as $column) {
        if ($column['Field'] == 'GiangVienID') $hasGiangVienID = true;
        if ($column['Field'] == 'id_giangvien') $hasIdGiangVien = true;
    }
    
    if (!$hasGiangVienID && $hasIdGiangVien) {
        $db->query("ALTER TABLE LichGap CHANGE COLUMN `id_giangvien` `GiangVienID` INT NOT NULL");
        $db->execute();
    } else if (!$hasGiangVienID && !$hasIdGiangVien) {
        $db->query("ALTER TABLE LichGap ADD COLUMN `GiangVienID` INT NOT NULL AFTER `SinhVienID`");
        $db->execute();
    }
} catch (PDOException $e) {
    $error = "Lỗi khi kiểm tra cấu trúc bảng: " . $e->getMessage();
}

// Xử lý xóa lịch gặp
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $appointmentId = $_GET['delete'];
    try {
        $db->query("DELETE FROM LichGap WHERE LichGapID = :id AND GiangVienID = :facultyId");
        $db->bind(':id', $appointmentId);
        $db->bind(':facultyId', $facultyId);
        if ($db->execute()) {
            $success = "Đã xóa lịch gặp thành công!";
        }
    } catch (PDOException $e) {
        $error = "Lỗi khi xóa lịch gặp: " . $e->getMessage();
    }
}

// Xử lý cập nhật trạng thái
if (isset($_GET['action']) && isset($_GET['id']) && is_numeric($_GET['id'])) {
    $appointmentId = $_GET['id'];
    $action = $_GET['action'];
    
    if ($action == 'complete' || $action == 'cancel') {
        $newStatus = ($action == 'complete') ? 'đã hoàn thành' : 'đã hủy';
        
        try {
            $db->query("UPDATE LichGap SET TrangThai = :status WHERE LichGapID = :id AND GiangVienID = :facultyId");
            $db->bind(':status', $newStatus);
            $db->bind(':id', $appointmentId);
            $db->bind(':facultyId', $facultyId);
            
            if ($db->execute()) {
                $success = "Đã cập nhật trạng thái lịch gặp thành công!";
            }
        } catch (PDOException $e) {
            $error = "Lỗi khi cập nhật trạng thái: " . $e->getMessage();
        }
    }
}

// Bộ lọc
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'upcoming';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Lấy danh sách lịch gặp
try {
    $sql = "SELECT lg.*, sv.HoTen as TenSinhVien, sv.MaSV
           FROM LichGap lg
           JOIN SinhVien sv ON lg.SinhVienID = sv.SinhVienID
           WHERE lg.GiangVienID = :facultyId";
           
    // Áp dụng bộ lọc
    if ($filter == 'upcoming') {
        $sql .= " AND lg.NgayGap >= CURRENT_DATE() AND lg.TrangThai = 'đã lên lịch'";
    } elseif ($filter == 'completed') {
        $sql .= " AND lg.TrangThai = 'đã hoàn thành'";
    } elseif ($filter == 'canceled') {
        $sql .= " AND lg.TrangThai = 'đã hủy'";
    } elseif ($filter == 'past') {
        $sql .= " AND (lg.NgayGap < CURRENT_DATE() AND lg.TrangThai = 'đã lên lịch')";
    }
    
    // Áp dụng tìm kiếm
    if (!empty($search)) {
        $sql .= " AND (sv.HoTen LIKE :search OR sv.MaSV LIKE :search OR lg.TieuDe LIKE :search OR lg.DiaDiem LIKE :search)";
    }
    
    $sql .= " ORDER BY lg.NgayGap DESC";
    
    $db->query($sql);
    $db->bind(':facultyId', $facultyId);
    
    if (!empty($search)) {
        $db->bind(':search', '%' . $search . '%');
    }
    
    $appointments = $db->resultSet();
} catch (PDOException $e) {
    $error = "Lỗi khi lấy danh sách lịch gặp: " . $e->getMessage();
    $appointments = [];
}

$pageTitle = 'Quản lý lịch gặp';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý lịch gặp - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <!-- Đặt nội dung trực tiếp mà không qua app-container -->
    <div class="container-fluid py-4">
        <!-- Page Heading -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">Quản lý lịch gặp</h1>
            <a href="schedule-meeting.php" class="btn btn-primary shadow-sm">
                <i class="fas fa-plus fa-sm text-white-50 me-1"></i> Thêm lịch gặp mới
            </a>
        </div>

        <!-- Alerts -->
        <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Content Row -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Danh sách lịch gặp sinh viên</h6>
                <!-- Search Form -->
                <div class="col-md-4">
                    <form action="" method="get" class="d-flex">
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="Tìm kiếm..." name="search" value="<?php echo htmlspecialchars($search); ?>">
                            <button class="btn btn-outline-secondary" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card-body">
                <!-- Filters -->
                <div class="mb-3">
                    <div class="btn-group">
                        <a href="?filter=upcoming<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="btn btn-<?php echo $filter == 'upcoming' ? 'primary' : 'outline-primary'; ?>">Sắp tới</a>
                        <a href="?filter=past<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="btn btn-<?php echo $filter == 'past' ? 'primary' : 'outline-primary'; ?>">Đã qua hạn</a>
                        <a href="?filter=completed<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="btn btn-<?php echo $filter == 'completed' ? 'primary' : 'outline-primary'; ?>">Đã hoàn thành</a>
                        <a href="?filter=canceled<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="btn btn-<?php echo $filter == 'canceled' ? 'primary' : 'outline-primary'; ?>">Đã hủy</a>
                        <a href="?filter=all<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="btn btn-<?php echo $filter == 'all' ? 'primary' : 'outline-primary'; ?>">Tất cả</a>
                    </div>
                </div>
                
                <!-- Appointments Table -->
                <?php if (count($appointments) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Tiêu đề</th>
                                    <th>Sinh viên</th>
                                    <th>Thời gian</th>
                                    <th>Địa điểm</th>
                                    <th>Trạng thái</th>
                                    <th width="15%">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($appointments as $appointment): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($appointment['TieuDe']); ?></strong>
                                            <?php if (!empty($appointment['NoiDung'])): ?>
                                                <div class="small text-muted"><?php echo htmlspecialchars(substr($appointment['NoiDung'], 0, 50) . (strlen($appointment['NoiDung']) > 50 ? '...' : '')); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div><?php echo htmlspecialchars($appointment['TenSinhVien']); ?></div>
                                            <div class="small text-muted">MSSV: <?php echo htmlspecialchars($appointment['MaSV']); ?></div>
                                        </td>
                                        <td>
                                            <?php
                                            $ngayGap = new DateTime($appointment['NgayGap']);
                                            $formattedDate = $ngayGap->format('d/m/Y');
                                            $formattedTime = $ngayGap->format('H:i');
                                            ?>
                                            <div><?php echo $formattedDate; ?></div>
                                            <div class="small"><?php echo $formattedTime; ?></div>
                                        </td>
                                        <td><?php echo htmlspecialchars($appointment['DiaDiem']); ?></td>
                                        <td>
                                            <?php
                                            $statusClass = 'secondary';
                                            switch ($appointment['TrangThai']) {
                                                case 'đã lên lịch': $statusClass = 'primary'; break;
                                                case 'đã xác nhận': $statusClass = 'info'; break;
                                                case 'đã hoàn thành': $statusClass = 'success'; break;
                                                case 'đã hủy': $statusClass = 'danger'; break;
                                            }
                                            ?>
                                            <span class="badge bg-<?php echo $statusClass; ?>">
                                                <?php echo htmlspecialchars(ucfirst($appointment['TrangThai'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="view-appointment.php?id=<?php echo $appointment['LichGapID']; ?>" class="btn btn-sm btn-info" title="Xem chi tiết">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="edit-appointment.php?id=<?php echo $appointment['LichGapID']; ?>" class="btn btn-sm btn-primary" title="Chỉnh sửa">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <?php if ($appointment['TrangThai'] == 'đã lên lịch'): ?>
                                                    <a href="?action=complete&id=<?php echo $appointment['LichGapID']; ?>&filter=<?php echo $filter; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                                                       class="btn btn-sm btn-success" 
                                                       title="Đánh dấu đã hoàn thành"
                                                       onclick="return confirm('Bạn có chắc chắn muốn đánh dấu cuộc gặp này là hoàn thành?')">
                                                        <i class="fas fa-check"></i>
                                                    </a>
                                                    <a href="?action=cancel&id=<?php echo $appointment['LichGapID']; ?>&filter=<?php echo $filter; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                                                       class="btn btn-sm btn-warning" 
                                                       title="Hủy cuộc gặp"
                                                       onclick="return confirm('Bạn có chắc chắn muốn hủy cuộc gặp này?')">
                                                        <i class="fas fa-ban"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <a href="?delete=<?php echo $appointment['LichGapID']; ?>&filter=<?php echo $filter; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                                                   class="btn btn-sm btn-danger"
                                                   title="Xóa"
                                                   onclick="return confirm('Bạn có chắc chắn muốn xóa cuộc gặp này?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <!-- Empty State -->
                    <div class="text-center my-5 py-5">
                        <i class="fas fa-calendar-alt fa-4x text-muted mb-3"></i>
                        <h4 class="mt-3">Chưa có lịch gặp nào</h4>
                        <p class="text-muted">Sử dụng nút "Thêm lịch gặp mới" để tạo lịch gặp với sinh viên của bạn.</p>
                        <a href="schedule-meeting.php" class="btn btn-primary mt-3">
                            <i class="fas fa-plus me-1"></i> Tạo lịch gặp mới
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal for appointment details -->
    <div class="modal fade" id="appointmentDetailModal" tabindex="-1" aria-labelledby="appointmentDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="appointmentDetailModalLabel">Chi tiết cuộc gặp</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="appointmentDetailContent">
                    <!-- Nội dung chi tiết sẽ được load bằng Ajax -->
                    <div class="d-flex justify-content-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Đang tải...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Hàm load chi tiết cuộc gặp bằng Ajax
    function loadAppointmentDetails(id) {
        document.getElementById('appointmentDetailContent').innerHTML = '<div class="d-flex justify-content-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Đang tải...</span></div></div>';
        
        fetch('get-appointment-details.php?id=' + id)
            .then(response => response.text())
            .then(data => {
                document.getElementById('appointmentDetailContent').innerHTML = data;
            })
            .catch(error => {
                document.getElementById('appointmentDetailContent').innerHTML = '<div class="alert alert-danger">Có lỗi xảy ra khi tải dữ liệu. Vui lòng thử lại.</div>';
            });
    }

    document.addEventListener('DOMContentLoaded', function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        document.querySelectorAll('.view-details-btn').forEach(button => {
            button.addEventListener('click', function() {
                var id = this.getAttribute('data-id');
                loadAppointmentDetails(id);
            });
        });
    });
    </script>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/scripts.js"></script>
</body>
</html>