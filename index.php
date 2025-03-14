<?php
session_start();
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    // Redirect based on user role
    switch ($_SESSION['role']) {
        case 'admin':
            header('Location: admin/dashboard.php');
            break;
        case 'faculty':
            header('Location: faculty/dashboard.php');
            break;
        case 'student':
            header('Location: student/dashboard.php');
            break;
        default:
            header('Location: index.php');
    }
    exit;
}

// Include header
include 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thesis Advisor Management System</title>
    <link rel="stylesheet" href="assets/css/bootstrap.custom.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <h1 class="mt-5">Chào mừng đến với Hệ thống Quản lý Hướng dẫn Luận văn</h1>
        <p class="lead">Vui lòng đăng nhập để tiếp tục.</p>
        <a href="auth/login.php" class="btn btn-primary">Đăng nhập</a>
        <a href="auth/register.php" class="btn btn-secondary">Đăng ký tài khoản mới</a>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>