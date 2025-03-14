<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit;
}

$db = new Database();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $field = trim($_POST['field']);
    
    if (empty($title) || empty($description) || empty($field)) {
        $error = 'Please fill in all fields.';
    } else {
        $db->query("INSERT INTO DeTai (TenDeTai, MoTa, LinhVuc) VALUES (:title, :description, :field)");
        $db->bind(':title', $title);
        $db->bind(':description', $description);
        $db->bind(':field', $field);
        
        if ($db->execute()) {
            $success = 'Thesis topic created successfully.';
        } else {
            $error = 'Failed to create thesis topic. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Thesis Topic - Thesis Management System</title>
    <link rel="stylesheet" href="../../assets/css/bootstrap.custom.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    <?php include '../../includes/admin-menu.php'; ?>

    <div class="container mt-5">
        <h2>Create Thesis Topic</h2>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="mb-3">
                <label for="title" class="form-label">Thesis Title</label>
                <input type="text" class="form-control" id="title" name="title" required>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
            </div>
            <div class="mb-3">
                <label for="field" class="form-label">Field</label>
                <input type="text" class="form-control" id="field" name="field" required>
            </div>
            <button type="submit" class="btn btn-primary">Create Thesis Topic</button>
        </form>
    </div>

    <?php include '../../includes/footer.php'; ?>
    <script src="../../assets/js/main.js"></script>
</body>
</html>