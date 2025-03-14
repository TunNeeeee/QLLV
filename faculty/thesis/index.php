<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../classes/Thesis.php';
require_once '../../classes/Faculty.php';
require_once '../../classes/Auth.php';

session_start();

$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->hasRole('faculty')) {
    header('Location: ../../auth/login.php');
    exit;
}

$facultyId = $_SESSION['profile_id'];
$thesis = new Thesis();
$thesisList = $thesis->getThesisByFaculty($facultyId);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Đề tài - Hệ thống Quản lý Hướng dẫn Luận văn</title>
    <link rel="stylesheet" href="../../assets/css/bootstrap.custom.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    <?php include '../../includes/faculty-menu.php'; ?>

    <div class="container mt-4">
        <h2 class="mb-4">Danh sách Đề tài</h2>
        <a href="create.php" class="btn btn-primary mb-3">Thêm Đề tài mới</a>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tên Đề tài</th>
                    <th>Mô tả</th>
                    <th>Trạng thái</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($thesisList): ?>
                    <?php foreach ($thesisList as $thesisItem): ?>
                        <tr>
                            <td><?php echo $thesisItem['DeTaiID']; ?></td>
                            <td><?php echo $thesisItem['TenDeTai']; ?></td>
                            <td><?php echo $thesisItem['MoTa']; ?></td>
                            <td><?php echo ucfirst($thesisItem['TrangThai']); ?></td>
                            <td>
                                <a href="edit.php?id=<?php echo $thesisItem['DeTaiID']; ?>" class="btn btn-warning btn-sm">Sửa</a>
                                <a href="delete.php?id=<?php echo $thesisItem['DeTaiID']; ?>" class="btn btn-danger btn-sm">Xóa</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center">Không có đề tài nào.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php include '../../includes/footer.php'; ?>
    <script src="../../assets/js/main.js"></script>
</body>
</html>