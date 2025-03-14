<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit;
}

$db = new Database();
$thesisId = $_GET['id'] ?? null;

if (!$thesisId) {
    header('Location: index.php');
    exit;
}

$db->query("SELECT * FROM DeTai WHERE DeTaiID = :id");
$db->bind(':id', $thesisId);
$thesis = $db->single();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $field = trim($_POST['field']);
    $status = trim($_POST['status']);

    $db->query("UPDATE DeTai SET TenDeTai = :title, MoTa = :description, LinhVuc = :field, TrangThai = :status WHERE DeTaiID = :id");
    $db->bind(':title', $title);
    $db->bind(':description', $description);
    $db->bind(':field', $field);
    $db->bind(':status', $status);
    $db->bind(':id', $thesisId);

    if ($db->execute()) {
        header('Location: index.php?message=Thesis updated successfully');
        exit;
    } else {
        $error = "Failed to update thesis.";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Thesis - Thesis Management System</title>
    <link rel="stylesheet" href="../../assets/css/bootstrap.custom.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    <?php include '../../includes/admin-menu.php'; ?>

    <div class="container mt-5">
        <h2>Edit Thesis</h2>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="post">
            <div class="mb-3">
                <label for="title" class="form-label">Thesis Title</label>
                <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($thesis['TenDeTai']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3" required><?php echo htmlspecialchars($thesis['MoTa']); ?></textarea>
            </div>
            <div class="mb-3">
                <label for="field" class="form-label">Field</label>
                <input type="text" class="form-control" id="field" name="field" value="<?php echo htmlspecialchars($thesis['LinhVuc']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status" required>
                    <option value="đề xuất" <?php echo $thesis['TrangThai'] == 'đề xuất' ? 'selected' : ''; ?>>Đề xuất</option>
                    <option value="được duyệt" <?php echo $thesis['TrangThai'] == 'được duyệt' ? 'selected' : ''; ?>>Được duyệt</option>
                    <option value="đang thực hiện" <?php echo $thesis['TrangThai'] == 'đang thực hiện' ? 'selected' : ''; ?>>Đang thực hiện</option>
                    <option value="hoàn thành" <?php echo $thesis['TrangThai'] == 'hoàn thành' ? 'selected' : ''; ?>>Hoàn thành</option>
                    <option value="hủy" <?php echo $thesis['TrangThai'] == 'hủy' ? 'selected' : ''; ?>>Hủy</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Update Thesis</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>

    <?php include '../../includes/footer.php'; ?>
    <script src="../../assets/js/main.js"></script>
</body>
</html>