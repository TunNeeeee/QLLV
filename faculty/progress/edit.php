<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../classes/Progress.php';

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'faculty') {
    header('Location: ../../auth/login.php');
    exit;
}

$db = new Database();
$progress = new Progress($db);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $progressId = $_POST['progress_id'];
    $status = $_POST['status'];
    $comments = $_POST['comments'];

    if ($progress->updateProgress($progressId, $status, $comments)) {
        header('Location: index.php?message=Progress updated successfully');
        exit;
    } else {
        $error = 'Failed to update progress. Please try again.';
    }
} else {
    $progressId = $_GET['id'];
    $currentProgress = $progress->getProgressById($progressId);
}

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Progress - Thesis Management System</title>
    <link rel="stylesheet" href="../../assets/css/bootstrap.custom.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    <?php include '../../includes/faculty-menu.php'; ?>

    <div class="container mt-5">
        <h2>Edit Progress Report</h2>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST" action="edit.php">
            <input type="hidden" name="progress_id" value="<?php echo $currentProgress['id']; ?>">
            <div class="mb-3">
                <label for="status" class="form-label">Status</label>
                <select name="status" id="status" class="form-select" required>
                    <option value="In Progress" <?php echo ($currentProgress['status'] == 'In Progress') ? 'selected' : ''; ?>>In Progress</option>
                    <option value="Completed" <?php echo ($currentProgress['status'] == 'Completed') ? 'selected' : ''; ?>>Completed</option>
                    <option value="On Hold" <?php echo ($currentProgress['status'] == 'On Hold') ? 'selected' : ''; ?>>On Hold</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="comments" class="form-label">Comments</label>
                <textarea name="comments" id="comments" class="form-control" rows="5" required><?php echo $currentProgress['comments']; ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Update Progress</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>

    <?php include '../../includes/footer.php'; ?>
    <script src="../../assets/js/main.js"></script>
</body>
</html>