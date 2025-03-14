<?php
session_start();
require_once '../../config/config.php';
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'faculty') {
    header('Location: ../../auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $thesisId = $_POST['thesis_id'];
    $reason = $_POST['reason'];

    if (!empty($thesisId) && !empty($reason)) {
        $db = new Database();
        $db->query("UPDATE DeTai SET TrangThai = 'hủy', LyDo = :reason WHERE DeTaiID = :thesisId");
        $db->bind(':reason', $reason);
        $db->bind(':thesisId', $thesisId);

        if ($db->execute()) {
            $_SESSION['message'] = 'Thesis rejected successfully.';
        } else {
            $_SESSION['message'] = 'Failed to reject the thesis. Please try again.';
        }
    } else {
        $_SESSION['message'] = 'Thesis ID and reason are required.';
    }

    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reject Thesis - Thesis Management System</title>
    <link rel="stylesheet" href="../../assets/css/bootstrap.custom.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    <?php include '../../includes/faculty-menu.php'; ?>

    <div class="container mt-5">
        <h2>Reject Thesis</h2>
        <form method="POST" action="reject.php">
            <div class="mb-3">
                <label for="thesis_id" class="form-label">Thesis ID</label>
                <input type="text" class="form-control" id="thesis_id" name="thesis_id" required>
            </div>
            <div class="mb-3">
                <label for="reason" class="form-label">Reason for Rejection</label>
                <textarea class="form-control" id="reason" name="reason" rows="4" required></textarea>
            </div>
            <button type="submit" class="btn btn-danger">Reject Thesis</button>
        </form>
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-info mt-3">
                <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
            </div>
        <?php endif; ?>
    </div>

    <?php include '../../includes/footer.php'; ?>
    <script src="../../assets/js/main.js"></script>
</body>
</html>