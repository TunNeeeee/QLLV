<?php
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
$success = '';

// Lấy ID của giảng viên đang đăng nhập
$userID = $_SESSION['user_id'];

// Lấy thông tin giảng viên
try {
    $db->query("SELECT GiangVienID, HoTen FROM GiangVien WHERE UserID = :userID");
    $db->bind(':userID', $userID);
    $facultyDetails = $db->single();
    $facultyId = $facultyDetails['GiangVienID'];
} catch (PDOException $e) {
    $error = "Lỗi khi lấy thông tin giảng viên: " . $e->getMessage();
}

// Lấy ID sinh viên từ URL
$studentId = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;

// Lấy thông tin sinh viên
if ($studentId > 0) {
    try {
        $db->query("SELECT sv.*, u.Email FROM SinhVien sv 
                    JOIN Users u ON sv.UserID = u.UserID
                    WHERE sv.SinhVienID = :studentId");
        $db->bind(':studentId', $studentId);
        $student = $db->single();
        
        if (!$student) {
            header('Location: assigned-students.php?error=student_not_found');
            exit;
        }
        
        // Kiểm tra xem sinh viên có phải do giảng viên này hướng dẫn không
        $db->query("SELECT * FROM SinhVienGiangVienHuongDan 
                    WHERE SinhVienID = :studentId AND GiangVienID = :facultyId");
        $db->bind(':studentId', $studentId);
        $db->bind(':facultyId', $facultyId);
        
        if ($db->rowCount() == 0) {
            header('Location: assigned-students.php?error=not_your_student');
            exit;
        }
    } catch (PDOException $e) {
        $error = "Lỗi khi lấy thông tin sinh viên: " . $e->getMessage();
    }
}

// Lấy danh sách đề tài có sẵn của giảng viên
try {
    $db->query("SELECT * FROM DeTai WHERE GiangVienID = :facultyId AND SinhVienID IS NULL");
    $db->bind(':facultyId', $facultyId);
    $availableTheses = $db->resultSet();
} catch (PDOException $e) {
    $error = "Lỗi khi lấy danh sách đề tài: " . $e->getMessage();
}

// Xử lý khi form được submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['assign_thesis'])) {
        $thesisId = $_POST['thesis_id'];
        $description = $_POST['description'] ?? '';
        
        try {
            $db->beginTransaction();
            
            // Kiểm tra xem đề tài có tồn tại và thuộc giảng viên này không
            if (!empty($thesisId)) {
                $db->query("SELECT * FROM DeTai WHERE DeTaiID = :thesisId AND GiangVienID = :facultyId");
                $db->bind(':thesisId', $thesisId);
                $db->bind(':facultyId', $facultyId);
                
                if ($db->rowCount() == 0) {
                    throw new Exception("Đề tài không tồn tại hoặc không thuộc về bạn");
                }
                
                // Cập nhật đề tài đã được gán cho sinh viên
                $db->query("UPDATE DeTai SET SinhVienID = :studentId WHERE DeTaiID = :thesisId");
                $db->bind(':studentId', $studentId);
                $db->bind(':thesisId', $thesisId);
                $db->execute();
            } else if (isset($_POST['create_new_thesis']) && $_POST['create_new_thesis'] == 1) {
                // Tạo đề tài mới
                $thesisName = $_POST['new_thesis_name'];
                $thesisDesc = $_POST['new_thesis_description'];
                
                $db->query("INSERT INTO DeTai (TenDeTai, MoTa, GiangVienID, SinhVienID, TrangThai, NgayTao) 
                          VALUES (:name, :description, :facultyId, :studentId, 'Đang thực hiện', NOW())");
                $db->bind(':name', $thesisName);
                $db->bind(':description', $thesisDesc);
                $db->bind(':facultyId', $facultyId);
                $db->bind(':studentId', $studentId);
                $db->execute();
                
                $thesisId = $db->lastInsertId();
            }
            
            // Cập nhật phân công với đề tài mới
            $db->query("UPDATE SinhVienGiangVienHuongDan SET DeTaiID = :thesisId WHERE SinhVienID = :studentId AND GiangVienID = :facultyId");
            $db->bind(':thesisId', $thesisId);
            $db->bind(':studentId', $studentId);
            $db->bind(':facultyId', $facultyId);
            $db->execute();
            
            // Gửi thông báo cho sinh viên
            $db->query("INSERT INTO ThongBao (UserID, TieuDe, NoiDung, LoaiThongBao, LienKet) 
                      SELECT sv.UserID, 'Đã được gán đề tài', 
                      :message, 'đề tài', 'student/thesis.php'
                      FROM SinhVien sv WHERE sv.SinhVienID = :studentId");
            $db->bind(':message', "Bạn đã được gán đề tài luận văn: " . ($thesisName ?? $_POST['thesis_name']));
            $db->bind(':studentId', $studentId);
            $db->execute();
            
            $db->commit();
            $success = "Đã gán đề tài cho sinh viên thành công!";
        } catch (Exception $e) {
            $db->rollback();
            $error = "Lỗi: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gán đề tài cho sinh viên - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Các style CSS khác -->
</head>
<body>
    <!-- Thêm header/sidebar tại đây -->
    
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row mb-3">
                <div class="col-12">
                    <h1 class="page-title">Gán đề tài cho sinh viên</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="dashboard.php">Trang chủ</a></li>
                            <li class="breadcrumb-item"><a href="assigned-students.php">Sinh viên hướng dẫn</a></li>
                            <li class="breadcrumb-item active">Gán đề tài</li>
                        </ol>
                    </nav>
                </div>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
                <div class="text-center my-3">
                    <a href="assigned-students.php" class="btn btn-primary">Quay lại danh sách sinh viên</a>
                </div>
            <?php else: ?>
            
            <div class="card">
                <div class="card-header">
                    <h5>Thông tin sinh viên</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Họ tên:</strong> <?php echo $student['HoTen']; ?></p>
                            <p><strong>MSSV:</strong> <?php echo $student['MaSV']; ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Email:</strong> <?php echo $student['Email']; ?></p>
                            <p><strong>Ngành:</strong> <?php echo $student['NganhHoc']; ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <h5>Gán đề tài</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="thesis_option" class="form-label">Chọn loại đề tài</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="thesis_option" id="thesis_existing" value="existing" checked>
                                <label class="form-check-label" for="thesis_existing">
                                    Chọn từ đề tài có sẵn
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="thesis_option" id="thesis_new" value="new">
                                <label class="form-check-label" for="thesis_new">
                                    Tạo đề tài mới
                                </label>
                            </div>
                        </div>
                        
                        <div id="existing_thesis_section">
                            <div class="mb-3">
                                <label for="thesis_id" class="form-label">Đề tài</label>
                                <select class="form-select" id="thesis_id" name="thesis_id">
                                    <option value="">-- Chọn đề tài --</option>
                                    <?php foreach ($availableTheses as $thesis): ?>
                                    <option value="<?php echo $thesis['DeTaiID']; ?>">
                                        <?php echo htmlspecialchars($thesis['TenDeTai']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="thesis_name" class="form-label">Tên đề tài đã chọn</label>
                                <input type="text" class="form-control" id="thesis_name" name="thesis_name" readonly>
                            </div>
                            
                            <div class="mb-3">
                                <label for="thesis_description" class="form-label">Mô tả đề tài</label>
                                <textarea class="form-control" id="thesis_description" rows="3" readonly></textarea>
                            </div>
                        </div>
                        
                        <div id="new_thesis_section" style="display: none;">
                            <div class="mb-3">
                                <label for="new_thesis_name" class="form-label">Tên đề tài mới</label>
                                <input type="text" class="form-control" id="new_thesis_name" name="new_thesis_name">
                            </div>
                            
                            <div class="mb-3">
                                <label for="new_thesis_description" class="form-label">Mô tả đề tài</label>
                                <textarea class="form-control" id="new_thesis_description" name="new_thesis_description" rows="3"></textarea>
                            </div>
                            
                            <input type="hidden" name="create_new_thesis" value="0" id="create_new_thesis">
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Ghi chú</label>
                            <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                            <div class="form-text">Thông tin bổ sung về việc gán đề tài cho sinh viên</div>
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" name="assign_thesis" class="btn btn-primary">Gán đề tài</button>
                            <a href="assigned-students.php" class="btn btn-secondary ms-2">Hủy</a>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Thêm footer tại đây -->
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const existingThesisRadio = document.getElementById('thesis_existing');
            const newThesisRadio = document.getElementById('thesis_new');
            const existingThesisSection = document.getElementById('existing_thesis_section');
            const newThesisSection = document.getElementById('new_thesis_section');
            const createNewThesisInput = document.getElementById('create_new_thesis');
            
            existingThesisRadio.addEventListener('change', function() {
                if (this.checked) {
                    existingThesisSection.style.display = 'block';
                    newThesisSection.style.display = 'none';
                    createNewThesisInput.value = '0';
                }
            });
            
            newThesisRadio.addEventListener('change', function() {
                if (this.checked) {
                    existingThesisSection.style.display = 'none';
                    newThesisSection.style.display = 'block';
                    createNewThesisInput.value = '1';
                }
            });
            
            // Populate thesis details on selection
            const thesisSelect = document.getElementById('thesis_id');
            const thesisName = document.getElementById('thesis_name');
            const thesisDescription = document.getElementById('thesis_description');
            
            const thesisDetails = <?php echo json_encode($availableTheses); ?>;
            
            thesisSelect.addEventListener('change', function() {
                const selectedThesisId = this.value;
                
                if (selectedThesisId) {
                    const selectedThesis = thesisDetails.find(thesis => thesis.DeTaiID == selectedThesisId);
                    thesisName.value = selectedThesis.TenDeTai;
                    thesisDescription.value = selectedThesis.MoTa;
                } else {
                    thesisName.value = '';
                    thesisDescription.value = '';
                }
            });
        });
    </script>
</body>
</html>