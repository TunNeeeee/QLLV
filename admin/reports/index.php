<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

session_start();

// Check if the user is logged in and has admin role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit;
}

// Database connection
$db = new Database();
$conn = $db->connect();

// Fetch reports data (example: thesis progress)
$query = "SELECT 
            sv.HoTen AS StudentName, 
            gv.HoTen AS FacultyName, 
            dt.TenDeTai AS ThesisTitle, 
            svgv.TrangThai AS Status 
          FROM SinhVienGiangVienHuongDan svgv
          JOIN SinhVien sv ON svgv.SinhVienID = sv.SinhVienID
          JOIN GiangVien gv ON svgv.GiangVienID = gv.GiangVienID
          JOIN DeTai dt ON svgv.DeTaiID = dt.DeTaiID";

$stmt = $conn->prepare($query);
$stmt->execute();
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo cáo - Hệ thống Quản lý Hướng dẫn Luận văn</title>
    <link rel="stylesheet" href="../../assets/css/bootstrap.custom.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    <?php include '../../includes/admin-menu.php'; ?>

    <div class="container mt-4">
        <h2>Báo cáo tiến độ luận văn</h2>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Tên sinh viên</th>
                    <th>Tên giảng viên</th>
                    <th>Đề tài luận văn</th>
                    <th>Trạng thái</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($reports) > 0): ?>
                    <?php foreach ($reports as $report): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($report['StudentName']); ?></td>
                            <td><?php echo htmlspecialchars($report['FacultyName']); ?></td>
                            <td><?php echo htmlspecialchars($report['ThesisTitle']); ?></td>
                            <td><?php echo htmlspecialchars($report['Status']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center">Không có báo cáo nào</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php include '../../includes/footer.php'; ?>
    <script src="../../assets/js/main.js"></script>
</body>
</html>