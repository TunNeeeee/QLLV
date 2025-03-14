<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../classes/Student.php';
require_once '../../classes/Faculty.php';

session_start();

// Check if user is logged in and is a faculty member
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'faculty') {
    header('Location: ../../auth/login.php');
    exit;
}

// Get faculty ID from session
$facultyId = $_SESSION['profile_id'];

// Create a new instance of the Student class
$student = new Student();

// Fetch students assigned to the faculty member
$students = $student->getStudentsByFaculty($facultyId);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách sinh viên - Hệ thống Quản lý Hướng dẫn Luận văn</title>
    <link rel="stylesheet" href="../../assets/css/bootstrap.custom.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    <?php include '../../includes/faculty-menu.php'; ?>

    <div class="container mt-4">
        <h2 class="mb-4">Danh sách sinh viên</h2>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>MSSV</th>
                    <th>Họ tên</th>
                    <th>Khoa</th>
                    <th>Ngành học</th>
                    <th>Chi tiết</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($students) > 0): ?>
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['MaSV']); ?></td>
                            <td><?php echo htmlspecialchars($student['HoTen']); ?></td>
                            <td><?php echo htmlspecialchars($student['Khoa']); ?></td>
                            <td><?php echo htmlspecialchars($student['NganhHoc']); ?></td>
                            <td>
                                <a href="view.php?id=<?php echo $student['SinhVienID']; ?>" class="btn btn-info btn-sm">Xem</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center">Không có sinh viên nào được phân công.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php include '../../includes/footer.php'; ?>
    <script src="../../assets/js/main.js"></script>
</body>
</html>