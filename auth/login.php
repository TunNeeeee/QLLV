<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../classes/Auth.php';

// Bắt đầu phiên
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$auth = new Auth();
$error = '';
$success = '';

// Kiểm tra nếu đã đăng nhập thì chuyển hướng
if ($auth->isLoggedIn()) {
    $role = $auth->getUserRole();
    
    if ($role == 'student') {
        header('Location: ../student/dashboard.php');
    } elseif ($role == 'faculty') {
        header('Location: ../faculty/dashboard.php');
    } elseif ($role == 'admin') {
        header('Location: ../admin/dashboard.php');
    }
    exit;
}

// Kiểm tra thông báo thành công từ trang đăng ký
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $success = 'Đăng ký thành công! Vui lòng đăng nhập để tiếp tục.';
}

// Xử lý đăng nhập
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    if (empty($username) || empty($password)) {
        $error = 'Vui lòng nhập tên đăng nhập và mật khẩu.';
    } else {
        if ($auth->login($username, $password)) {
            $role = $auth->getUserRole();
            
            if ($role == 'student') {
                header('Location: ../student/dashboard.php');
            } elseif ($role == 'faculty') {
                header('Location: ../faculty/dashboard.php');
            } elseif ($role == 'admin') {
                header('Location: ../admin/dashboard.php');
            }
            exit;
        } else {
            $error = 'Tên đăng nhập hoặc mật khẩu không đúng.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Xóa các liên kết Google Fonts -->
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #f8f9fc;
            --accent-color: #3a0ca3;
            --text-color: #333;
            --light-text: #6c757d;
            --border-color: #e3e6f0;
            --success-color: #4cc9f0;
            --info-color: #4895ef;
            --warning-color: #f72585;
            --danger-color: #e63946;
            --dark-bg: #343a40;
        }
        
        body {
            /* Sử dụng font system stack thay vì Google Fonts */
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            background-color: #f8f9fa;
            color: var(--text-color);
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 2rem 0;
        }
        
        .auth-wrapper {
            width: 100%;
            max-width: 450px;
            margin: 0 auto;
        }
        
        .auth-card {
            border: none;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            background-color: #fff;
        }
        
        .card-header {
            background: linear-gradient(135deg, #4cc9f0 0%, #4361ee 50%, #3a0ca3 100%);
            padding: 2rem;
            text-align: center;
            border-bottom: none;
        }
        
        .auth-brand {
            font-weight: 700;
            font-size: 1.8rem;
            color: white;
            margin-bottom: 0.5rem;
        }
        
        .auth-description {
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 0;
        }
        
        .card-body {
            padding: 2.5rem;
        }
        
        .section-title {
            color: var(--text-color);
            font-weight: 600;
            font-size: 1.25rem;
            margin-bottom: 1.5rem;
        }
        
        /* Form Controls */
        .form-control, .form-select {
            padding: 0.75rem 1.25rem;
            font-size: 0.95rem;
            border-radius: 8px;
            border: 1px solid #dce1e9;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.25);
        }
        
        .input-group-text {
            background-color: #f8f9fa;
            border-color: #dce1e9;
            color: var(--light-text);
        }
        
        .form-label {
            color: var(--text-color);
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        
        /* Buttons */
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--accent-color);
            border-color: var (--accent-color);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }
        
        /* Auth Footer */
        .auth-footer {
            text-align: center;
            margin-top: 2rem;
            color: var (--light-text);
        }
        
        .auth-footer a {
            color: var(--primary-color);
            font-weight: 500;
            text-decoration: none;
        }
        
        .auth-footer a:hover {
            text-decoration: underline;
        }
        
        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 1.5rem 0;
        }
        
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid var(--border-color);
        }
        
        .divider-text {
            padding: 0 1rem;
            color: var(--light-text);
        }
        
        .social-login {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin: 1rem 0;
        }
        
        .social-btn {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: #fff;
            transition: all 0.2s;
        }
        
        .social-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .btn-facebook {
            background-color: #3b5998;
        }
        
        .btn-google {
            background-color: #db4437;
        }
        
        .btn-twitter {
            background-color: #1da1f2;
        }
        
        .forgot-password {
            display: block;
            text-align: right;
            font-size: 0.9rem;
            margin-top: 0.5rem;
            margin-bottom: 1.5rem;
        }
        
        @media (max-width: 768px) {
            .card-body {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="auth-wrapper">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12">
                    <div class="card auth-card">
                        <div class="card-header py-4">
                            <h1 class="auth-brand"><?php echo SITE_NAME; ?></h1>
                            <p class="auth-description">Hệ thống quản lý hướng dẫn luận văn</p>
                        </div>
                        <div class="card-body">
                            <h4 class="section-title text-center mb-4">Đăng nhập</h4>
                            
                            <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($success): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Thêm đoạn code này vào phần trên form đăng nhập -->
                            <?php if (isset($_GET['logout']) && $_GET['logout'] == 'success'): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i> Đăng xuất thành công!
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                            <?php endif; ?>
                            
                            <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                                <div class="mb-4">
                                    <label for="username" class="form-label">Tên đăng nhập hoặc Email</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        <input type="text" class="form-control" id="username" name="username" placeholder="Nhập tên đăng nhập hoặc email" required autofocus>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="password" class="form-label">Mật khẩu</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" id="password" name="password" placeholder="Nhập mật khẩu" required>
                                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="rememberMe" name="remember">
                                        <label class="form-check-label" for="rememberMe">
                                            Ghi nhớ đăng nhập
                                        </label>
                                    </div>
                                    <a href="forgot-password.php" class="forgot-password">Quên mật khẩu?</a>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">
                                        Đăng nhập <i class="fas fa-sign-in-alt ms-2"></i>
                                    </button>
                                </div>
                            </form>
                            
                            <div class="divider">
                                <span class="divider-text">hoặc đăng nhập với</span>
                            </div>
                            
                            <div class="social-login">
                                <a href="#" class="social-btn btn-facebook">
                                    <i class="fab fa-facebook-f"></i>
                                </a>
                                <a href="#" class="social-btn btn-google">
                                    <i class="fab fa-google"></i>
                                </a>
                                <a href="#" class="social-btn btn-twitter">
                                    <i class="fab fa-twitter"></i>
                                </a>
                            </div>
                            
                            <div class="auth-footer">
                                <p>Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Hiện/ẩn mật khẩu
        const togglePasswordBtn = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        
        if (togglePasswordBtn) {
            togglePasswordBtn.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.querySelector('i').classList.toggle('fa-eye');
                this.querySelector('i').classList.toggle('fa-eye-slash');
            });
        }
    });
    </script>
    <script src="../assets/js/main.js"></script>
</body>
</html>