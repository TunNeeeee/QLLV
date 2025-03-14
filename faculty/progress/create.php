<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../classes/Progress.php';
require_once '../../classes/Auth.php';

session_start();

$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->hasRole('faculty')) {
    header('Location: ../../auth/login.php');
    exit;
}

$db = new Database();
$progress = new Progress($db);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $studentId = $_POST['student_id'];
    $thesisId = $_POST['thesis_id'];
    $progressStatus = $_POST['progress_status'];
    $comments = $_POST['comments'];

    if ($progress->createProgress($studentId, $thesisId, $progressStatus, $comments)) {
        header('Location: index.php?success=Progress report created successfully.');
        exit;
    } else {
        $error = 'Failed to create progress report. Please try again.';
    }
}

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tạo báo cáo tiến độ - Hệ thống Quản lý Hướng dẫn Luận văn</title>
    <link rel="stylesheet" href="../../assets/css/bootstrap.custom.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    <?php include '../../includes/faculty-menu.php'; ?>

    <div class="container mt-5">
        <h2>Tạo báo cáo tiến độ</h2>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="mb-3">
                <label for="student_id" class="form-label">Mã sinh viên</label>
                <input type="text" class="form-control" id="student_id" name="student_id" required>
            </div>
            <div class="mb-3">
                <label for="thesis_id" class="form-label">Mã đề tài</label>
                <input type="text" class="form-control" id="thesis_id" name="thesis_id" required>
            </div>
            <div class="mb-3">
                <label for="progress_status" class="form-label">Trạng thái tiến độ</label>
                <select class="form-select" id="progress_status" name="progress_status" required>
                    <option value="In Progress">Đang thực hiện</option>
                    <option value="Completed">Hoàn thành</option>
                    <option value="On Hold">Tạm dừng</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="comments" class="form-label">Ghi chú</label>
                <textarea class="form-control" id="comments" name="comments" rows="3"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Tạo báo cáo</button>
        </form>
    </div>

    <?php include '../../includes/footer.php'; ?>
    <script src="../../assets/js/main.js"></script>
</body>
</html>