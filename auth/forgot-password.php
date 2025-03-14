<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../classes/Auth.php';

session_start();

$auth = new Auth();
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);

    if (empty($email)) {
        $error = 'Please enter your email address.';
    } else {
        // Tạo token đặt lại mật khẩu
        $token = $auth->createPasswordResetToken($email);
        
        if ($token) {
            // Gửi liên kết đặt lại mật khẩu đến email
            $resetLink = BASE_URL . "auth/reset-password.php?token=" . $token;
            $subject = "Password Reset Request";
            $message = "Click the link below to reset your password:\n" . $resetLink;
            mail($email, $subject, $message);

            $success = 'A password reset link has been sent to your email address.';
        } else {
            $error = 'No account found with that email address.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quên mật khẩu - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/bootstrap.custom.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white text-center">
                        <h4><?php echo SITE_NAME; ?></h4>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title text-center mb-4">Quên mật khẩu</h5>

                        <?php if ($error): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo $error; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success" role="alert">
                                <?php echo $success; ?>
                            </div>
                        <?php endif; ?>

                        <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                            <div class="mb-3">
                                <label for="email" class="form-label">Địa chỉ email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Gửi liên kết đặt lại mật khẩu</button>
                            </div>
                        </form>

                        <div class="mt-3 text-center">
                            <a href="login.php">Đăng nhập</a> | 
                            <a href="register.php">Đăng ký tài khoản mới</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html>