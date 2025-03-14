<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../classes/User.php';

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit;
}

$db = new Database();
$user = new User($db);

if (isset($_GET['id'])) {
    $userId = $_GET['id'];
    $userData = $user->getUserById($userId);

    if (!$userData) {
        header('Location: index.php?error=User not found');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $status = $_POST['status'];

    if ($user->updateUser($userId, $username, $email, $role, $status)) {
        header('Location: index.php?success=User updated successfully');
        exit;
    } else {
        $error = 'Failed to update user. Please try again.';
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - Thesis Management System</title>
    <link rel="stylesheet" href="../../assets/css/bootstrap.custom.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    <?php include '../../includes/admin-menu.php'; ?>

    <div class="container mt-5">
        <h2>Edit User</h2>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="post" action="">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($userData['Username']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($userData['Email']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="role" class="form-label">Role</label>
                <select class="form-select" id="role" name="role" required>
                    <option value="admin" <?php echo ($userData['Role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                    <option value="faculty" <?php echo ($userData['Role'] == 'faculty') ? 'selected' : ''; ?>>Faculty</option>
                    <option value="student" <?php echo ($userData['Role'] == 'student') ? 'selected' : ''; ?>>Student</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status" required>
                    <option value="active" <?php echo ($userData['Status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo ($userData['Status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                    <option value="suspended" <?php echo ($userData['Status'] == 'suspended') ? 'selected' : ''; ?>>Suspended</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Update User</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>

    <?php include '../../includes/footer.php'; ?>
    <script src="../../assets/js/main.js"></script>
</body>
</html>