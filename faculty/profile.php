<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../classes/Auth.php';
require_once '../classes/Faculty.php';

session_start();

$db = new Database();
$auth = new Auth();
$faculty = new Faculty($db);

if (!$auth->isLoggedIn() || !$auth->hasRole('faculty')) {
    header('Location: ../auth/login.php');
    exit;
}

$facultyId = $_SESSION['profile_id'];
$facultyData = $faculty->getFaculty($facultyId);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hồ sơ Giảng viên - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/bootstrap.custom.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/profile.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/faculty-navbar.php'; ?>

    <div class="container mt-4">
        <h2 class="mb-4">Thông tin Giảng viên</h2>
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0"><?php echo $facultyData['HoTen']; ?></h5>
            </div>
            <div class="card-body">
                <p><strong>Mã Giảng viên:</strong> <?php echo $facultyData['MaGV']; ?></p>
                <p><strong>Học vị:</strong> <?php echo $facultyData['HocVi']; ?></p>
                <p><strong>Chức vụ:</strong> <?php echo $facultyData['ChucVu']; ?></p>
                <p><strong>Khoa:</strong> <?php echo $facultyData['Khoa']; ?></p>
                <p><strong>Chuyên ngành:</strong> <?php echo $facultyData['ChuyenNganh']; ?></p>
                <p><strong>Email:</strong> <?php echo $facultyData['Email']; ?></p>
                <p><strong>Số điện thoại:</strong> <?php echo $facultyData['SoDienThoai']; ?></p>
            </div>
            <div class="card-footer">
                <a href="edit.php?id=<?php echo $facultyId; ?>" class="btn btn-warning">Chỉnh sửa thông tin</a>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    <script src="../assets/js/main.js"></script>
</body>
</html>