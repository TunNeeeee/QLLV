<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit;
}

$db = new Database();
$db->query("SELECT * FROM SinhVien");
$students = $db->resultSet();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Sinh viên - Hệ thống Quản lý Hướng dẫn Luận văn</title>
    <link rel="stylesheet" href="../../assets/css/bootstrap.custom.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    <?php include '../../includes/admin-menu.php'; ?>

    <div class="container mt-4">
        <h2 class="mb-4">Danh sách Sinh viên</h2>
        <a href="create.php" class="btn btn-primary mb-3">Thêm Sinh viên</a>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>MSSV</th>
                    <th>Họ tên</th>
                    <th>Ngày sinh</th>
                    <th>Khoa</th>
                    <th>Ngành học</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($students) > 0): ?>
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td><?php echo $student['MaSV']; ?></td>
                            <td><?php echo $student['HoTen']; ?></td>
                            <td><?php echo date('d/m/Y', strtotime($student['NgaySinh'])); ?></td>
                            <td><?php echo $student['Khoa']; ?></td>
                            <td><?php echo $student['NganhHoc']; ?></td>
                            <td>
                                <a href="edit.php?id=<?php echo $student['SinhVienID']; ?>" class="btn btn-warning btn-sm">Sửa</a>
                                <a href="delete.php?id=<?php echo $student['SinhVienID']; ?>" class="btn btn-danger btn-sm">Xóa</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center">Không có sinh viên nào.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php include '../../includes/footer.php'; ?>
    <script src="../../assets/js/main.js"></script>
</body>
</html>