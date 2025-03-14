<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

session_start();

// Check if user is logged in and has admin role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit;
}

$db = new Database();
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $maGV = trim($_POST['maGV']);
    $hoTen = trim($_POST['hoTen']);
    $hocVi = trim($_POST['hocVi']);
    $chucVu = trim($_POST['chucVu']);
    $khoa = trim($_POST['khoa']);
    $chuyenNganh = trim($_POST['chuyenNganh']);
    $email = trim($_POST['email']);
    $soDienThoai = trim($_POST['soDienThoai']);

    // Validate input
    if (empty($maGV) || empty($hoTen) || empty($email)) {
        $error = 'Vui lòng điền tất cả các trường bắt buộc.';
    } else {
        // Insert new faculty member into the database
        $db->query("INSERT INTO GiangVien (MaGV, HoTen, HocVi, ChucVu, Khoa, ChuyenNganh, Email, SoDienThoai) VALUES (:maGV, :hoTen, :hocVi, :chucVu, :khoa, :chuyenNganh, :email, :soDienThoai)");
        $db->bind(':maGV', $maGV);
        $db->bind(':hoTen', $hoTen);
        $db->bind(':hocVi', $hocVi);
        $db->bind(':chucVu', $chucVu);
        $db->bind(':khoa', $khoa);
        $db->bind(':chuyenNganh', $chuyenNganh);
        $db->bind(':email', $email);
        $db->bind(':soDienThoai', $soDienThoai);

        if ($db->execute()) {
            $success = 'Giảng viên đã được thêm thành công.';
        } else {
            $error = 'Có lỗi xảy ra. Vui lòng thử lại.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm Giảng Viên - Hệ thống Quản lý Hướng dẫn Luận văn</title>
    <link rel="stylesheet" href="../../assets/css/bootstrap.custom.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    <?php include '../../includes/admin-menu.php'; ?>

    <div class="container mt-5">
        <h2 class="mb-4">Thêm Giảng Viên</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label for="maGV" class="form-label">Mã Giảng Viên</label>
                <input type="text" class="form-control" id="maGV" name="maGV" required>
            </div>
            <div class="mb-3">
                <label for="hoTen" class="form-label">Họ Tên</label>
                <input type="text" class="form-control" id="hoTen" name="hoTen" required>
            </div>
            <div class="mb-3">
                <label for="hocVi" class="form-label">Học Vị</label>
                <input type="text" class="form-control" id="hocVi" name="hocVi">
            </div>
            <div class="mb-3">
                <label for="chucVu" class="form-label">Chức Vụ</label>
                <input type="text" class="form-control" id="chucVu" name="chucVu">
            </div>
            <div class="mb-3">
                <label for="khoa" class="form-label">Khoa</label>
                <input type="text" class="form-control" id="khoa" name="khoa">
            </div>
            <div class="mb-3">
                <label for="chuyenNganh" class="form-label">Chuyên Ngành</label>
                <input type="text" class="form-control" id="chuyenNganh" name="chuyenNganh">
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="mb-3">
                <label for="soDienThoai" class="form-label">Số Điện Thoại</label>
                <input type="text" class="form-control" id="soDienThoai" name="soDienThoai">
            </div>
            <button type="submit" class="btn btn-primary">Thêm Giảng Viên</button>
            <a href="index.php" class="btn btn-secondary">Quay lại</a>
        </form>
    </div>

    <?php include '../../includes/footer.php'; ?>
    <script src="../../assets/js/main.js"></script>
</body>
</html>