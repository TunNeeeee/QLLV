<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit;
}

$db = new Database();
$studentId = $_GET['id'] ?? null;

if (!$studentId) {
    header('Location: index.php');
    exit;
}

$db->query("SELECT * FROM SinhVien WHERE SinhVienID = :id");
$db->bind(':id', $studentId);
$student = $db->single();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $maSv = trim($_POST['maSv']);
    $hoTen = trim($_POST['hoTen']);
    $ngaySinh = trim($_POST['ngaySinh']);
    $gioiTinh = trim($_POST['gioiTinh']);
    $khoa = trim($_POST['khoa']);
    $nganhHoc = trim($_POST['nganhHoc']);
    $nienKhoa = trim($_POST['nienKhoa']);
    $soDienThoai = trim($_POST['soDienThoai']);
    $diaChi = trim($_POST['diaChi']);

    $db->query("UPDATE SinhVien SET MaSV = :maSv, HoTen = :hoTen, NgaySinh = :ngaySinh, GioiTinh = :gioiTinh, Khoa = :khoa, NganhHoc = :nganhHoc, NienKhoa = :nienKhoa, SoDienThoai = :soDienThoai, DiaChi = :diaChi WHERE SinhVienID = :id");
    $db->bind(':maSv', $maSv);
    $db->bind(':hoTen', $hoTen);
    $db->bind(':ngaySinh', $ngaySinh);
    $db->bind(':gioiTinh', $gioiTinh);
    $db->bind(':khoa', $khoa);
    $db->bind(':nganhHoc', $nganhHoc);
    $db->bind(':nienKhoa', $nienKhoa);
    $db->bind(':soDienThoai', $soDienThoai);
    $db->bind(':diaChi', $diaChi);
    $db->bind(':id', $studentId);

    if ($db->execute()) {
        header('Location: index.php?message=Student updated successfully');
        exit;
    } else {
        $error = "Failed to update student.";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student - Thesis Management System</title>
    <link rel="stylesheet" href="../../assets/css/bootstrap.custom.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    <?php include '../../includes/admin-menu.php'; ?>

    <div class="container mt-5">
        <h2>Edit Student</h2>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label for="maSv" class="form-label">MSSV</label>
                <input type="text" class="form-control" id="maSv" name="maSv" value="<?php echo $student['MaSV']; ?>" required>
            </div>
            <div class="mb-3">
                <label for="hoTen" class="form-label">Họ Tên</label>
                <input type="text" class="form-control" id="hoTen" name="hoTen" value="<?php echo $student['HoTen']; ?>" required>
            </div>
            <div class="mb-3">
                <label for="ngaySinh" class="form-label">Ngày Sinh</label>
                <input type="date" class="form-control" id="ngaySinh" name="ngaySinh" value="<?php echo $student['NgaySinh']; ?>" required>
            </div>
            <div class="mb-3">
                <label for="gioiTinh" class="form-label">Giới Tính</label>
                <select class="form-select" id="gioiTinh" name="gioiTinh" required>
                    <option value="Nam" <?php echo ($student['GioiTinh'] == 'Nam') ? 'selected' : ''; ?>>Nam</option>
                    <option value="Nữ" <?php echo ($student['GioiTinh'] == 'Nữ') ? 'selected' : ''; ?>>Nữ</option>
                    <option value="Khác" <?php echo ($student['GioiTinh'] == 'Khác') ? 'selected' : ''; ?>>Khác</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="khoa" class="form-label">Khoa</label>
                <input type="text" class="form-control" id="khoa" name="khoa" value="<?php echo $student['Khoa']; ?>" required>
            </div>
            <div class="mb-3">
                <label for="nganhHoc" class="form-label">Ngành Học</label>
                <input type="text" class="form-control" id="nganhHoc" name="nganhHoc" value="<?php echo $student['NganhHoc']; ?>" required>
            </div>
            <div class="mb-3">
                <label for="nienKhoa" class="form-label">Niên Khóa</label>
                <input type="text" class="form-control" id="nienKhoa" name="nienKhoa" value="<?php echo $student['NienKhoa']; ?>" required>
            </div>
            <div class="mb-3">
                <label for="soDienThoai" class="form-label">Số Điện Thoại</label>
                <input type="text" class="form-control" id="soDienThoai" name="soDienThoai" value="<?php echo $student['SoDienThoai']; ?>" required>
            </div>
            <div class="mb-3">
                <label for="diaChi" class="form-label">Địa Chỉ</label>
                <textarea class="form-control" id="diaChi" name="diaChi" required><?php echo $student['DiaChi']; ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Cập Nhật</button>
            <a href="index.php" class="btn btn-secondary">Hủy</a>
        </form>
    </div>

    <?php include '../../includes/footer.php'; ?>
    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>