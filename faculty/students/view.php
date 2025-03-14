<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../classes/Student.php';

session_start();

// Check if user is logged in and has faculty role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'faculty') {
    header('Location: ../../auth/login.php');
    exit;
}

// Get student ID from URL
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $studentId = $_GET['id'];
} else {
    header('Location: index.php');
    exit;
}

// Create Student object
$student = new Student($db);
$studentDetails = $student->getStudentById($studentId);

// Check if student exists
if (!$studentDetails) {
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết sinh viên - Hệ thống Quản lý Hướng dẫn Luận văn</title>
    <link rel="stylesheet" href="../../assets/css/bootstrap.custom.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    <?php include '../../includes/faculty-menu.php'; ?>

    <div class="container mt-5">
        <h2>Chi tiết sinh viên</h2>
        <div class="card">
            <div class="card-header">
                <h5 class="card-title"><?php echo htmlspecialchars($studentDetails['HoTen']); ?></h5>
            </div>
            <div class="card-body">
                <p><strong>MSSV:</strong> <?php echo htmlspecialchars($studentDetails['MaSV']); ?></p>
                <p><strong>Ngày sinh:</strong> <?php echo htmlspecialchars(date('d/m/Y', strtotime($studentDetails['NgaySinh']))); ?></p>
                <p><strong>Giới tính:</strong> <?php echo htmlspecialchars($studentDetails['GioiTinh']); ?></p>
                <p><strong>Khoa:</strong> <?php echo htmlspecialchars($studentDetails['Khoa']); ?></p>
                <p><strong>Ngành học:</strong> <?php echo htmlspecialchars($studentDetails['NganhHoc']); ?></p>
                <p><strong>Niên khóa:</strong> <?php echo htmlspecialchars($studentDetails['NienKhoa']); ?></p>
                <p><strong>Số điện thoại:</strong> <?php echo htmlspecialchars($studentDetails['SoDienThoai']); ?></p>
                <p><strong>Địa chỉ:</strong> <?php echo htmlspecialchars($studentDetails['DiaChi']); ?></p>
            </div>
            <div class="card-footer">
                <a href="index.php" class="btn btn-secondary">Quay lại</a>
            </div>
        </div>
    </div>

    <?php include '../../includes/footer.php'; ?>
    <script src="../../assets/js/main.js"></script>
</body>
</html>