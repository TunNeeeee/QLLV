<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../classes/Auth.php';
require_once '../../classes/Progress.php';

session_start();

$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->hasRole('student')) {
    header('Location: ../../auth/login.php');
    exit;
}

$db = new Database();
$progress = new Progress();

// Fetch the student's thesis progress
$studentId = $_SESSION['profile_id'];
$progressData = $progress->getProgressByStudentId($studentId);

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tiến độ luận văn - Hệ thống Quản lý Hướng dẫn Luận văn</title>
    <link rel="stylesheet" href="../../assets/css/bootstrap.custom.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    <?php include '../../includes/student-menu.php'; ?>

    <div class="container mt-4">
        <h2 class="mb-4">Tiến độ luận văn</h2>
        <?php if ($progressData): ?>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Ngày</th>
                        <th>Nội dung</th>
                        <th>Ghi chú</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($progressData as $progressItem): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($progressItem['NgayGioHuongDan'])); ?></td>
                            <td><?php echo htmlspecialchars($progressItem['NoiDung']); ?></td>
                            <td><?php echo htmlspecialchars($progressItem['GhiChu']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert alert-warning">Chưa có thông tin tiến độ nào.</div>
        <?php endif; ?>
    </div>

    <?php include '../../includes/footer.php'; ?>
    <script src="../../assets/js/main.js"></script>
</body>
</html>