<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../../auth/login.php');
    exit;
}

$studentId = $_SESSION['profile_id'];
$thesisId = isset($_GET['id']) ? $_GET['id'] : null;

if ($thesisId) {
    $db = new Database();
    $db->query("SELECT 
                    dt.*, 
                    gv.HoTen AS TenGiangVien, 
                    gv.Email AS EmailGiangVien 
                FROM DeTai dt 
                JOIN SinhVienGiangVienHuongDan svgv ON dt.DeTaiID = svgv.DeTaiID 
                JOIN GiangVien gv ON svgv.GiangVienID = gv.GiangVienID 
                WHERE svgv.SinhVienID = :studentId AND dt.DeTaiID = :thesisId");
    $db->bind(':studentId', $studentId);
    $db->bind(':thesisId', $thesisId);
    $thesis = $db->single();

    if (!$thesis) {
        header('Location: view.php?error=Thesis not found');
        exit;
    }
} else {
    header('Location: view.php?error=Invalid thesis ID');
    exit;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xem Đề Tài - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/bootstrap.custom.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    <?php include '../../includes/student-menu.php'; ?>

    <div class="container mt-5">
        <h2 class="mb-4"><?php echo $thesis['TenDeTai']; ?></h2>
        <p><strong>Mô tả:</strong> <?php echo $thesis['MoTa']; ?></p>
        <p><strong>Lĩnh vực:</strong> <?php echo $thesis['LinhVuc']; ?></p>
        <p><strong>Trạng thái:</strong> <?php echo ucfirst($thesis['TrangThai']); ?></p>
        <p><strong>Giảng viên hướng dẫn:</strong> <?php echo $thesis['TenGiangVien']; ?></p>
        <p><strong>Email giảng viên:</strong> <?php echo $thesis['EmailGiangVien']; ?></p>
        <a href="progress.php?id=<?php echo $thesisId; ?>" class="btn btn-primary">Xem tiến độ</a>
        <a href="register.php" class="btn btn-secondary">Đăng ký đề tài mới</a>
    </div>

    <?php include '../../includes/footer.php'; ?>
    <script src="../../assets/js/main.js"></script>
</body>
</html>