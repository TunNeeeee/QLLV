<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../classes/Auth.php';
require_once '../../classes/Thesis.php';

session_start();

$auth = new Auth();
$thesis = new Thesis();

if (!$auth->isLoggedIn() || !$auth->hasRole('student')) {
    header('Location: ../../auth/login.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $field = trim($_POST['field']);

    if (empty($title) || empty($description) || empty($field)) {
        $error = 'Please fill in all fields.';
    } else {
        $thesisId = $thesis->registerThesis($_SESSION['user_id'], $title, $description, $field);
        if ($thesisId) {
            $success = 'Thesis registered successfully!';
        } else {
            $error = 'Failed to register thesis. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Thesis - Thesis Management System</title>
    <link rel="stylesheet" href="../../assets/css/bootstrap.custom.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    <?php include '../../includes/student-menu.php'; ?>

    <div class="container mt-5">
        <h2>Register Thesis</h2>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <form method="post" action="">
            <div class="mb-3">
                <label for="title" class="form-label">Thesis Title</label>
                <input type="text" class="form-control" id="title" name="title" required>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
            </div>
            <div class="mb-3">
                <label for="field" class="form-label">Field of Study</label>
                <input type="text" class="form-control" id="field" name="field" required>
            </div>
            <button type="submit" class="btn btn-primary">Register Thesis</button>
        </form>
    </div>

    <?php include '../../includes/footer.php'; ?>
    <script src="../../assets/js/main.js"></script>
</body>
</html>