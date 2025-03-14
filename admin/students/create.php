<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit;
}

$db = new Database();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $maSV = trim($_POST['maSV']);
    $hoTen = trim($_POST['hoTen']);
    $ngaySinh = trim($_POST['ngaySinh']);
    $gioiTinh = trim($_POST['gioiTinh']);
    $khoa = trim($_POST['khoa']);
    $nganhHoc = trim($_POST['nganhHoc']);
    $nienKhoa = trim($_POST['nienKhoa']);
    $soDienThoai = trim($_POST['soDienThoai']);
    $diaChi = trim($_POST['diaChi']);
    
    if (empty($maSV) || empty($hoTen) || empty($ngaySinh) || empty($gioiTinh) || empty($khoa) || empty($nganhHoc) || empty($nienKhoa)) {
        $error = 'Vui lòng điền tất cả các trường bắt buộc.';
    } else {
        $db->query("INSERT INTO SinhVien (MaSV, HoTen, NgaySinh, GioiTinh, Khoa, NganhHoc, NienKhoa, SoDienThoai, DiaChi) VALUES (:maSV, :hoTen, :ngaySinh, :gioiTinh, :khoa, :nganhHoc, :nienKhoa, :soDienThoai, :diaChi)");
        $db->bind(':maSV', $maSV);
        $db->bind(':hoTen', $hoTen);
        $db->bind(':ngaySinh', $ngaySinh);
        $db->bind(':gioiTinh', $gioiTinh);
        $db->bind(':khoa', $khoa);
        $db->bind(':nganhHoc', $nganhHoc);
        $db->bind(':nienKhoa', $nienKhoa);
        $db->bind(':soDienThoai', $soDienThoai);
        $db->bind(':diaChi', $diaChi);
        
        if ($db->execute()) {
            $success = 'Thêm sinh viên thành công.';
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
    <title>Thêm Sinh Viên - Hệ thống Quản lý Hướng dẫn Luận văn</title>
    <link rel="stylesheet" href="../../assets/css/bootstrap.custom.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    <?php include '../../includes/admin-menu.php'; ?>

    <div class="container mt-5">
        <h2>Thêm Sinh Viên</h2>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="mb-3">
                <label for="maSV" class="form-label">Mã Sinh Viên</label>
                <input type="text" class="form-control" id="maSV" name="maSV" required>
            </div>
            <div class="mb-3">
                <label for="hoTen" class="form-label">Họ Tên</label>
                <input type="text" class="form-control" id="hoTen" name="hoTen" required>
            </div>
            <div class="mb-3">
                <label for="ngaySinh" class="form-label">Ngày Sinh</label>
                <input type="date" class="form-control" id="ngaySinh" name="ngaySinh" required>
            </div>
            <div class="mb-3">
                <label for="gioiTinh" class="form-label">Giới Tính</label>
                <select class="form-select" id="gioiTinh" name="gioiTinh" required>
                    <option value="">Chọn giới tính</option>
                    <option value="Nam">Nam</option>
                    <option value="Nữ">Nữ</option>
                    <option value="Khác">Khác</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="khoa" class="form-label">Khoa</label>
                <input type="text" class="form-control" id="khoa" name="khoa" required>
            </div>
            <div class="mb-3">
                <label for="nganhHoc" class="form-label">Ngành Học</label>
                <input type="text" class="form-control" id="nganhHoc" name="nganhHoc" required>
            </div>
            <div class="mb-3">
                <label for="nienKhoa" class="form-label">Niên Khóa</label>
                <input type="text" class="form-control" id="nienKhoa" name="nienKhoa" required>
            </div>
            <div class="mb-3">
                <label for="soDienThoai" class="form-label">Số Điện Thoại</label>
                <input type="text" class="form-control" id="soDienThoai" name="soDienThoai">
            </div>
            <div class="mb-3">
                <label for="diaChi" class="form-label">Địa Chỉ</label>
                <textarea class="form-control" id="diaChi" name="diaChi"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Thêm Sinh Viên</button>
            <a href="index.php" class="btn btn-secondary">Quay lại</a>
        </form>
    </div>

    <?php include '../../includes/footer.php'; ?>
    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>