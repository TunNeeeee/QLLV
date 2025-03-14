<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../classes/Faculty.php';

session_start();

// Check if user is logged in and has admin role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit;
}

$db = new Database();
$faculty = new Faculty($db);

// Check if faculty ID is provided
if (isset($_GET['id'])) {
    $facultyId = $_GET['id'];
    $facultyMember = $faculty->getFacultyById($facultyId);

    // Check if faculty member exists
    if (!$facultyMember) {
        header('Location: index.php?error=Faculty member not found');
        exit;
    }
} else {
    header('Location: index.php?error=Invalid request');
    exit;
}

// Handle form submission for editing faculty member
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = [
        'id' => $facultyId,
        'maGV' => trim($_POST['maGV']),
        'hoTen' => trim($_POST['hoTen']),
        'hocVi' => trim($_POST['hocVi']),
        'chucVu' => trim($_POST['chucVu']),
        'khoa' => trim($_POST['khoa']),
        'chuyenNganh' => trim($_POST['chuyenNganh']),
        'email' => trim($_POST['email']),
        'soDienThoai' => trim($_POST['soDienThoai']),
    ];

    if ($faculty->updateFaculty($data)) {
        header('Location: index.php?success=Faculty member updated successfully');
        exit;
    } else {
        $error = 'Failed to update faculty member. Please try again.';
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Faculty - Thesis Management System</title>
    <link rel="stylesheet" href="../../assets/css/bootstrap.custom.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    <?php include '../../includes/admin-menu.php'; ?>

    <div class="container mt-5">
        <h2>Edit Faculty Member</h2>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="post" action="">
            <div class="mb-3">
                <label for="maGV" class="form-label">Faculty ID</label>
                <input type="text" class="form-control" id="maGV" name="maGV" value="<?php echo htmlspecialchars($facultyMember['MaGV']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="hoTen" class="form-label">Full Name</label>
                <input type="text" class="form-control" id="hoTen" name="hoTen" value="<?php echo htmlspecialchars($facultyMember['HoTen']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="hocVi" class="form-label">Academic Degree</label>
                <input type="text" class="form-control" id="hocVi" name="hocVi" value="<?php echo htmlspecialchars($facultyMember['HocVi']); ?>">
            </div>
            <div class="mb-3">
                <label for="chucVu" class="form-label">Position</label>
                <input type="text" class="form-control" id="chucVu" name="chucVu" value="<?php echo htmlspecialchars($facultyMember['ChucVu']); ?>">
            </div>
            <div class="mb-3">
                <label for="khoa" class="form-label">Department</label>
                <input type="text" class="form-control" id="khoa" name="khoa" value="<?php echo htmlspecialchars($facultyMember['Khoa']); ?>">
            </div>
            <div class="mb-3">
                <label for="chuyenNganh" class="form-label">Specialization</label>
                <input type="text" class="form-control" id="chuyenNganh" name="chuyenNganh" value="<?php echo htmlspecialchars($facultyMember['ChuyenNganh']); ?>">
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($facultyMember['Email']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="soDienThoai" class="form-label">Phone Number</label>
                <input type="text" class="form-control" id="soDienThoai" name="soDienThoai" value="<?php echo htmlspecialchars($facultyMember['SoDienThoai']); ?>">
            </div>
            <button type="submit" class="btn btn-primary">Update Faculty Member</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>

    <?php include '../../includes/footer.php'; ?>
    <script src="../../assets/js/main.js"></script>
</body>
</html>