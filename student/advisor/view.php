<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../classes/Auth.php';
require_once '../../classes/Student.php';

// Bắt đầu phiên
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$auth = new Auth();
$student = new Student();

// Kiểm tra đăng nhập và vai trò
if (!$auth->isLoggedIn() || !$auth->hasRole('student')) {
    header('Location: ../../auth/login.php');
    exit;
}

// Lấy ID sinh viên từ session
$studentId = $_SESSION['profile_id'];

// Lấy thông tin giảng viên hướng dẫn
$advisor = $student->getAdvisor($studentId);

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông tin Giảng viên Hướng dẫn - Hệ thống Quản lý Hướng dẫn Luận văn</title>
    <link rel="stylesheet" href="../../assets/css/bootstrap.custom.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    <?php include '../../includes/student-menu.php'; ?>

    <div class="container mt-5">
        <h2 class="mb-4">Thông tin Giảng viên Hướng dẫn</h2>
        <?php if ($advisor): ?>
            <div class="card">
                <div class="card-header">
                    <h5><?php echo $advisor['HoTen']; ?></h5>
                </div>
                <div class="card-body">
                    <p><strong>Mã Giảng viên:</strong> <?php echo $advisor['MaGV']; ?></p>
                    <p><strong>Học vị:</strong> <?php echo $advisor['HocVi']; ?></p>
                    <p><strong>Chức vụ:</strong> <?php echo $advisor['ChucVu']; ?></p>
                    <p><strong>Khoa:</strong> <?php echo $advisor['Khoa']; ?></p>
                    <p><strong>Email:</strong> <?php echo $advisor['Email']; ?></p>
                    <p><strong>Số điện thoại:</strong> <?php echo $advisor['SoDienThoai']; ?></p>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-warning" role="alert">
                Bạn chưa có giảng viên hướng dẫn.
            </div>
        <?php endif; ?>
    </div>

    <?php include '../../includes/footer.php'; ?>
    <script src="../../assets/js/main.js"></script>
</body>
</html>