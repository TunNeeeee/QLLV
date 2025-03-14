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
if (!$auth->isLoggedIn() || $auth->getUserRole() != 'admin') {
    header('Location: ' . BASE_URL . 'auth/login.php?error=unauthorized_access');
    exit;
}

$db = new Database();
$success = '';
$error = '';

// Xử lý duyệt yêu cầu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_request'])) {
    $requestId = $_POST['request_id'];
    $status = $_POST['status']; // 'approve' hoặc 'reject'
    
    try {
        $db->query("SELECT * FROM YeuCauPhanCong WHERE YeuCauID = :requestId");
        $db->bind(':requestId', $requestId);
        $request = $db->single();
        
        if (!$request) {
            $error = "Không tìm thấy yêu cầu phân công!";
        } else {
            // Cập nhật trạng thái yêu cầu
            $db->query("UPDATE YeuCauPhanCong 
                       SET TrangThai = :status, NgayDuyet = NOW(), NguoiDuyetID = :adminId 
                       WHERE YeuCauID = :requestId");
            $db->bind(':status', ($status == 'approve') ? 'Đã duyệt' : 'Từ chối');
            $db->bind(':adminId', $_SESSION['user_id']);
            $db->bind(':requestId', $requestId);
            $db->execute();
            
            // Nếu duyệt, tạo bản ghi trong bảng SinhVienGiangVienHuongDan
            if ($status == 'approve') {
                // Kiểm tra xem sinh viên đã được phân công chưa
                $db->query("SELECT * FROM SinhVienGiangVienHuongDan WHERE SinhVienID = :studentId");
                $db->bind(':studentId', $request['SinhVienID']);
                $existingAssignment = $db->single();
                
                if (!$existingAssignment) {
                    // Tạo phân công mới
                    $db->query("INSERT INTO SinhVienGiangVienHuongDan 
                              (SinhVienID, GiangVienID, DeTaiID, NgayBatDau, TienDo, GhiChu) 
                              VALUES (:studentId, :facultyId, :thesisId, NOW(), 0, :note)");
                    $db->bind(':studentId', $request['SinhVienID']);
                    $db->bind(':facultyId', $request['GiangVienID']);
                    $db->bind(':thesisId', $request['DeTaiID']);
                    $db->bind(':note', $request['GhiChu']);
                    $db->execute();
                    
                    // Gửi thông báo
                    sendAssignmentNotification($db, $request['SinhVienID'], $request['GiangVienID']);
                }
            }
            
            $success = ($status == 'approve') ? "Đã duyệt yêu cầu phân công thành công!" : "Đã từ chối yêu cầu phân công!";
        }
    } catch (PDOException $e) {
        $error = "Lỗi khi xử lý yêu cầu phân công: " . $e->getMessage();
    }
}

// Lấy danh sách yêu cầu phân công
$requests = [];
try {
    // Kiểm tra bảng YeuCauPhanCong tồn tại
    $db->query("SHOW TABLES LIKE 'YeuCauPhanCong'");
    $tableExists = $db->rowCount() > 0;
    
    if ($tableExists) {
        $db->query("SELECT yc.*, 
                   sv.HoTen as TenSinhVien, sv.MaSV, 
                   gv.HoTen as TenGiangVien, gv.HocVi,
                   dt.TenDeTai,
                   admin.HoTen as TenAdmin
                   FROM YeuCauPhanCong yc
                   JOIN SinhVien sv ON yc.SinhVienID = sv.SinhVienID
                   JOIN GiangVien gv ON yc.GiangVienID = gv.GiangVienID
                   JOIN DeTai dt ON yc.DeTaiID = dt.DeTaiID
                   LEFT JOIN Admin admin ON yc.NguoiDuyetID = admin.AdminID
                   ORDER BY yc.NgayYeuCau DESC");
        $requests = $db->resultSet();
    } else {
        // Tạo bảng nếu chưa tồn tại
        $db->query("CREATE TABLE IF NOT EXISTS `YeuCauPhanCong` (
            `YeuCauID` int(11) NOT NULL AUTO_INCREMENT,
            `GiangVienID` int(11) NOT NULL,
            `SinhVienID` int(11) NOT NULL,
            `DeTaiID` int(11) NOT NULL,
            `GhiChu` text DEFAULT NULL,
            `TrangThai` enum('Chờ duyệt','Đã duyệt','Từ chối') DEFAULT 'Chờ duyệt',
            `NgayYeuCau` datetime DEFAULT current_timestamp(),
            `NgayDuyet` datetime DEFAULT NULL,
            `NguoiDuyetID` int(11) DEFAULT NULL,
            PRIMARY KEY (`YeuCauID`),
            KEY `GiangVienID` (`GiangVienID`),
            KEY `SinhVienID` (`SinhVienID`),
            KEY `DeTaiID` (`DeTaiID`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
} catch (PDOException $e) {
    $error = "Lỗi khi lấy danh sách yêu cầu phân công: " . $e->getMessage();
}

// Hàm gửi thông báo
function sendAssignmentNotification($db, $studentId, $facultyId) {
    // Code tương tự như trong assign-students.php
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Duyệt yêu cầu phân công - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="app-container">
        <!-- Sidebar (giống như trong dashboard.php) -->
        
        <div class="main-content">
            <!-- Page Header -->
            <div class="page-header mb-4">
                <h1 class="h3">Duyệt yêu cầu phân công</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Trang chủ</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Duyệt yêu cầu phân công</li>
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
            
            <!-- Danh sách yêu cầu phân công -->
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-list-alt me-2"></i>Danh sách yêu cầu phân công</h5>
                </div>
                <div class="card-body">
                    <?php if (count($requests) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover" id="requestsTable">
                            <thead>
                                <tr>
                                    <th>MSSV</th>
                                    <th>Sinh viên</th>
                                    <th>Giảng viên</th>
                                    <th>Đề tài</th>
                                    <th>Ngày yêu cầu</th>
                                    <th>Trạng thái</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($requests as $request): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($request['MaSV']); ?></td>
                                    <td>
                                        <a href="student-details.php?id=<?php echo $request['SinhVienID']; ?>" class="fw-bold text-decoration-none">
                                            <?php echo htmlspecialchars($request['TenSinhVien']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <a href="faculty-details.php?id=<?php echo $request['GiangVienID']; ?>" class="text-decoration-none">
                                            <?php echo htmlspecialchars($request['HocVi'] . ' ' . $request['TenGiangVien']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <a href="thesis-details.php?id=<?php echo $request['DeTaiID']; ?>" class="text-decoration-none">
                                            <?php echo htmlspecialchars($request['TenDeTai']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($request['NgayYeuCau'])); ?></td>
                                    <td>
                                        <?php 
                                        switch ($request['TrangThai']) {
                                            case 'Đã duyệt':
                                                echo '<span class="badge bg-success">Đã duyệt</span>';
                                                break;
                                            case 'Từ chối':
                                                echo '<span class="badge bg-danger">Từ chối</span>';
                                                break;
                                            default:
                                                echo '<span class="badge bg-warning text-dark">Chờ duyệt</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($request['TrangThai'] == 'Chờ duyệt'): ?>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-success approve-btn" 
                                                    data-id="<?php echo $request['YeuCauID']; ?>" 
                                                    data-action="approve" 
                                                    title="Duyệt">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button type="button" class="btn btn-danger reject-btn" 
                                                    data-id="<?php echo $request['YeuCauID']; ?>" 
                                                    data-action="reject" 
                                                    title="Từ chối">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                        <?php else: ?>
                                        <span class="text-muted">
                                            <?php echo $request['TenAdmin'] ? 'Bởi: ' . htmlspecialchars($request['TenAdmin']) : ''; ?>
                                            <?php echo $request['NgayDuyet'] ? '<br>Ngày: ' . date('d/m/Y H:i', strtotime($request['NgayDuyet'])) : ''; ?>
                                        </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle me-2"></i> Không có yêu cầu phân công nào trong hệ thống.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal xác nhận -->
    <div class="modal fade" id="actionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Xác nhận thao tác</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="modalBody">
                    Bạn có chắc chắn muốn thực hiện thao tác này?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <form method="post" action="">
                        <input type="hidden" name="request_id" id="request_id" value="">
                        <input type="hidden" name="status" id="status" value="">
                        <button type="submit" name="approve_request" class="btn" id="confirmBtn">Xác nhận</button>
                    </form>
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
            $('#requestsTable').DataTable({
                language: {
                    url: "//cdn.datatables.net/plug-ins/1.11.5/i18n/vi.json"
                },
                responsive: true,
                order: [[4, 'desc']] // Sắp xếp theo ngày yêu cầu
            });
            
            // Xử lý nút duyệt
            $('.approve-btn').click(function() {
                const requestId = $(this).data('id');
                $('#request_id').val(requestId);
                $('#status').val('approve');
                $('#modalTitle').text('Xác nhận duyệt yêu cầu');
                $('#modalBody').text('Bạn có chắc chắn muốn duyệt yêu cầu phân công này?');
                $('#confirmBtn').removeClass('btn-danger').addClass('btn-success').text('Duyệt');
                $('#actionModal').modal('show');
            });
            
            // Xử lý nút từ chối
            $('.reject-btn').click(function() {
                const requestId = $(this).data('id');
                $('#request_id').val(requestId);
                $('#status').val('reject');
                $('#modalTitle').text('Xác nhận từ chối yêu cầu');
                $('#modalBody').text('Bạn có chắc chắn muốn từ chối yêu cầu phân công này?');
                $('#confirmBtn').removeClass('btn-success').addClass('btn-danger').text('Từ chối');
                $('#actionModal').modal('show');
            });
        });
    </script>
</body>
</html>