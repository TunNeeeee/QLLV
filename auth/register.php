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
$pageTitle = 'Đăng ký';

// Chuyển hướng nếu đã đăng nhập
if ($auth->isLoggedIn()) {
    $role = $auth->getUserRole();
    header('Location: ../' . $role . '/dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirmPassword = trim($_POST['confirm_password']);
    $email = trim($_POST['email']);
    $role = trim($_POST['role']);

    if (empty($username) || empty($password) || empty($confirmPassword) || empty($email) || empty($role)) {
        $error = 'Vui lòng điền đầy đủ thông tin.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Mật khẩu và xác nhận mật khẩu không khớp.';
    } else {
        // Thu thập tất cả dữ liệu từ form
        $userData = [
            'username' => $username,
            'password' => $password,
            'email' => $email,
            'role' => $role,
            'hoTen' => $_POST['hoTen'] ?? '',
        ];
        
        // Thêm dữ liệu tùy theo vai trò
        if ($role == 'student') {
            $userData['maSV'] = $_POST['maSV'] ?? '';
            $userData['ngaySinh'] = $_POST['ngaySinh'] ?? null;
            $userData['gioiTinh'] = $_POST['gioiTinh'] ?? null;
            $userData['khoa'] = $_POST['khoa'] ?? null;
            $userData['nganhHoc'] = $_POST['nganhHoc'] ?? null;
            $userData['nienKhoa'] = $_POST['nienKhoa'] ?? null;
            $userData['soDienThoai'] = $_POST['soDienThoai'] ?? null;
            $userData['diaChi'] = $_POST['diaChi'] ?? null;
        } elseif ($role == 'faculty') {
            $userData['maGV'] = $_POST['maGV'] ?? '';
            $userData['hocVi'] = $_POST['hocVi'] ?? null;
            $userData['chucVu'] = $_POST['chucVu'] ?? null;
            $userData['khoa'] = $_POST['khoa'] ?? null;
            $userData['soDienThoai'] = $_POST['soDienThoai'] ?? null;
        } elseif ($role == 'admin') {
            $userData['soDienThoai'] = $_POST['soDienThoai'] ?? null;
        }

        if ($auth->register($userData)) {
            header('Location: login.php?success=1');
            exit;
        } else {
            $error = 'Tên đăng nhập hoặc email đã tồn tại.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký - <?php echo SITE_NAME; ?></title>
    <!-- Sửa link Bootstrap chính xác -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            color: var(--text-color);
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 2rem 0;
        }
        
        .auth-wrapper {
            width: 100%;
            max-width: 1100px;
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
        
        /* Progress Steps */
        .progress-steps {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin-bottom: 2.5rem;
        }
        
        .progress-steps::before {
            content: '';
            position: absolute;
            top: 16px;
            left: 0;
            width: 100%;
            height: 2px;
            background-color: var(--border-color);
            z-index: 0;
        }
        
        .progress-step {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 33.333%;
        }
        
        .step-number {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background-color: white;
            border: 2px solid var(--border-color);
            color: var(--light-text);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .progress-step.active .step-number {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .progress-step.completed .step-number {
            background-color: var(--success-color);
            color: white;
            border-color: var(--success-color);
        }
        
        .step-label {
            color: var(--light-text);
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .progress-step.active .step-label {
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .progress-step.completed .step-label {
            color: var(--success-color);
        }
        
        /* Form Steps */
        .form-step {
            display: none;
        }
        
        .form-step.active {
            display: block;
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
            background-color: var (--accent-color);
            border-color: var(--accent-color);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }
        
        .btn-outline-secondary {
            color: var(--light-text);
            border-color: #dce1e9;
        }
        
        .btn-outline-secondary:hover {
            background-color: #f8f9fa;
            color: var(--text-color);
        }
        
        /* Role Cards */
        .role-card {
            padding: 1.5rem;
            border-radius: 10px;
            border: 2px solid var(--border-color);
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            height: 100%;
        }
        
        .role-card:hover {
            border-color: #b8c2cc;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transform: translateY(-3px);
        }
        
        .role-card.active {
            border-color: var(--primary-color);
            background-color: rgba(67, 97, 238, 0.05);
        }
        
        .role-icon {
            font-size: 2.5rem;
            color: var(--light-text);
            margin-bottom: 1rem;
        }
        
        .role-card.active .role-icon {
            color: var(--primary-color);
        }
        
        .role-title {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 0.75rem;
            color: var(--text-color);
        }
        
        .role-description {
            color: var(--light-text);
            font-size: 0.85rem;
            margin-bottom: 0;
        }
        
        /* Password Strength */
        .password-strength {
            height: 5px;
            background-color: var(--border-color);
            margin-top: 0.5rem;
            border-radius: 2px;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0;
            border-radius: 2px;
            transition: width 0.3s ease, background-color 0.3s ease;
        }
        
        /* Auth Footer */
        .auth-footer {
            text-align: center;
            margin-top: 2rem;
            color: var(--light-text);
        }
        
        .auth-footer a {
            color: var (--primary-color);
            font-weight: 500;
            text-decoration: none;
        }
        
        .auth-footer a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 992px) {
            .card-body {
                padding: 2rem;
            }
        }
        
        @media (max-width: 768px) {
            .card-body {
                padding: 1.5rem;
            }
            
            .progress-step .step-label {
                font-size: 0.75rem;
            }
        }
    </style>
</head>
<body>
    <div class="auth-wrapper">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10 col-xl-9">
                    <div class="card auth-card">
                        <div class="card-header py-4">
                            <h1 class="auth-brand"><?php echo SITE_NAME; ?></h1>
                            <p class="auth-description">Hệ thống quản lý hướng dẫn luận văn</p>
                        </div>
                        <div class="card-body">
                            <div class="progress-steps">
                                <div class="progress-step active" data-step="1">
                                    <div class="step-number">1</div>
                                    <div class="step-label">Tài khoản</div>
                                </div>
                                <div class="progress-step" data-step="2">
                                    <div class="step-number">2</div>
                                    <div class="step-label">Vai trò</div>
                                </div>
                                <div class="progress-step" data-step="3">
                                    <div class="step-number">3</div>
                                    <div class="step-label">Thông tin</div>
                                </div>
                            </div>
                            
                            <?php if ($error): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>
                            
                            <form id="registrationForm" method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="needs-validation" novalidate>
                                <!-- Step 1: Account Information -->
                                <div class="form-step active" id="step1">
                                    <h4 class="section-title">Thông tin tài khoản</h4>
                                    
                                    <div class="mb-4">
                                        <label for="username" class="form-label">Tên đăng nhập</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                                            <input type="text" class="form-control" id="username" name="username" required>
                                        </div>
                                        <div class="invalid-feedback">Vui lòng nhập tên đăng nhập.</div>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label for="email" class="form-label">Email</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                            <input type="email" class="form-control" id="email" name="email" required>
                                        </div>
                                        <div class="invalid-feedback">Vui lòng nhập email hợp lệ.</div>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label for="password" class="form-label">Mật khẩu</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                            <input type="password" class="form-control" id="password" name="password" required>
                                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                        <div class="password-strength mt-2">
                                            <div class="password-strength-bar"></div>
                                        </div>
                                        <small class="text-muted">Mật khẩu phải có ít nhất 8 ký tự</small>
                                        <div class="invalid-feedback">Vui lòng nhập mật khẩu.</div>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label for="confirm_password" class="form-label">Xác nhận mật khẩu</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                            <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                        <div class="invalid-feedback">Mật khẩu không khớp.</div>
                                    </div>
                                    
                                    <div class="d-flex justify-content-end">
                                        <button type="button" class="btn btn-primary next-step" data-next="2">
                                            Tiếp theo <i class="fas fa-arrow-right ms-2"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Step 2: Role Selection -->
                                <div class="form-step" id="step2">
                                    <h4 class="section-title">Chọn vai trò</h4>
                                    
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <div class="role-card" data-role="student">
                                                <div class="role-icon">
                                                    <i class="fas fa-user-graduate"></i>
                                                </div>
                                                <div class="role-title">Sinh viên</div>
                                                <div class="role-description">Dành cho sinh viên đăng ký đề tài và được hướng dẫn luận văn</div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            <div class="role-card" data-role="faculty">
                                                <div class="role-icon">
                                                    <i class="fas fa-chalkboard-teacher"></i>
                                                </div>
                                                <div class="role-title">Giảng viên</div>
                                                <div class="role-description">Dành cho giảng viên hướng dẫn và quản lý đề tài luận văn</div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            <div class="role-card" data-role="admin">
                                                <div class="role-icon">
                                                    <i class="fas fa-user-shield"></i>
                                                </div>
                                                <div class="role-title">Quản trị viên</div>
                                                <div class="role-description">Dành cho người quản lý hệ thống và giám sát luận văn</div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <input type="hidden" name="role" id="selectedRole" required>
                                    <div class="invalid-feedback d-block role-error mt-3" style="display: none !important;">Vui lòng chọn vai trò.</div>
                                    
                                    <div class="d-flex justify-content-between mt-4">
                                        <button type="button" class="btn btn-outline-secondary prev-step" data-prev="1">
                                            <i class="fas fa-arrow-left me-2"></i> Quay lại
                                        </button>
                                        <button type="button" class="btn btn-primary next-step" data-next="3">
                                            Tiếp theo <i class="fas fa-arrow-right ms-2"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Step 3: Personal Information -->
                                <div class="form-step" id="step3">
                                    <h4 class="section-title">Thông tin cá nhân</h4>
                                    
                                    <!-- Common Information -->
                                    <div class="mb-4">
                                        <label for="hoTen" class="form-label">Họ và tên</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-user-tag"></i></span>
                                            <input type="text" class="form-control" id="hoTen" name="hoTen" required>
                                        </div>
                                        <div class="invalid-feedback">Vui lòng nhập họ và tên.</div>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label for="soDienThoai" class="form-label">Số điện thoại</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                            <input type="text" class="form-control" id="soDienThoai" name="soDienThoai">
                                        </div>
                                    </div>
                                    
                                    <!-- Student Information -->
                                    <div id="student-fields" style="display: none;">
                                        <h5 class="section-title">Thông tin sinh viên</h5>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-4">
                                                    <label for="maSV" class="form-label">Mã sinh viên</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                                        <input type="text" class="form-control" id="maSV" name="maSV">
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <div class="mb-4">
                                                    <label for="ngaySinh" class="form-label">Ngày sinh</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                                        <input type="date" class="form-control" id="ngaySinh" name="ngaySinh">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-4">
                                                    <label for="gioiTinh" class="form-label">Giới tính</label>
                                                    <select class="form-select" id="gioiTinh" name="gioiTinh">
                                                        <option value="">Chọn giới tính</option>
                                                        <option value="Nam">Nam</option>
                                                        <option value="Nữ">Nữ</option>
                                                    </select>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <div class="mb-4">
                                                    <label for="khoa-sv" class="form-label">Khoa</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i class="fas fa-university"></i></span>
                                                        <input type="text" class="form-control" id="khoa-sv" name="khoa">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-4">
                                                    <label for="nganhHoc" class="form-label">Ngành học</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i class="fas fa-book"></i></span>
                                                        <input type="text" class="form-control" id="nganhHoc" name="nganhHoc">
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <div class="mb-4">
                                                    <label for="nienKhoa" class="form-label">Niên khóa</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i class="fas fa-graduation-cap"></i></span>
                                                        <input type="text" class="form-control" id="nienKhoa" name="nienKhoa" placeholder="VD: 2020-2024">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-4">
                                            <label for="diaChi" class="form-label">Địa chỉ</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                                <textarea class="form-control" id="diaChi" name="diaChi" rows="3"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Faculty Information -->
                                    <div id="faculty-fields" style="display: none;">
                                        <h5 class="section-title">Thông tin giảng viên</h5>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-4">
                                                    <label for="maGV" class="form-label">Mã giảng viên</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                                        <input type="text" class="form-control" id="maGV" name="maGV">
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <div class="mb-4">
                                                    <label for="hocVi" class="form-label">Học vị</label>
                                                    <select class="form-select" id="hocVi" name="hocVi">
                                                        <option value="">Chọn học vị</option>
                                                        <option value="Cử nhân">Cử nhân</option>
                                                        <option value="Thạc sĩ">Thạc sĩ</option>
                                                        <option value="Tiến sĩ">Tiến sĩ</option>
                                                        <option value="Phó Giáo sư">Phó Giáo sư</option>
                                                        <option value="Giáo sư">Giáo sư</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-4">
                                                    <label for="chucVu" class="form-label">Chức vụ</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i class="fas fa-briefcase"></i></span>
                                                        <input type="text" class="form-control" id="chucVu" name="chucVu">
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <div class="mb-4">
                                                    <label for="khoa-gv" class="form-label">Khoa</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i class="fas fa-university"></i></span>
                                                        <input type="text" class="form-control" id="khoa-gv" name="khoa">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between">
                                        <button type="button" class="btn btn-outline-secondary prev-step" data-prev="2">
                                            <i class="fas fa-arrow-left me-2"></i> Quay lại
                                        </button>
                                        <button type="submit" class="btn btn-primary">
                                            Đăng ký <i class="fas fa-check ms-2"></i>
                                        </button>
                                    </div>
                                </div>
                            </form>
                            
                            <div class="auth-footer">
                                <p>Đã có tài khoản? <a href="login.php">Đăng nhập</a></p>
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
        // Biến để theo dõi bước hiện tại
        let currentStep = 1;
        
        // Các phần tử DOM
        const steps = document.querySelectorAll('.form-step');
        const progressSteps = document.querySelectorAll('.progress-step');
        const nextButtons = document.querySelectorAll('.next-step');
        const prevButtons = document.querySelectorAll('.prev-step');
        const roleCards = document.querySelectorAll('.role-card');
        const selectedRoleInput = document.getElementById('selectedRole');
        const studentFields = document.getElementById('student-fields');
        const facultyFields = document.getElementById('faculty-fields');
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        const togglePasswordBtn = document.getElementById('togglePassword');
        const toggleConfirmPasswordBtn = document.getElementById('toggleConfirmPassword');
        const passwordStrengthBar = document.querySelector('.password-strength-bar');
        const roleError = document.querySelector('.role-error');
        const registrationForm = document.getElementById('registrationForm');
        
        // Hiện/ẩn mật khẩu
        if (togglePasswordBtn) {
            togglePasswordBtn.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.querySelector('i').classList.toggle('fa-eye');
                this.querySelector('i').classList.toggle('fa-eye-slash');
            });
        }
        
        if (toggleConfirmPasswordBtn) {
            toggleConfirmPasswordBtn.addEventListener('click', function() {
                const type = confirmPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                confirmPasswordInput.setAttribute('type', type);
                this.querySelector('i').classList.toggle('fa-eye');
                this.querySelector('i').classList.toggle('fa-eye-slash');
            });
        }
        
        // Các nút Tiếp theo
        nextButtons.forEach(button => {
            button.addEventListener('click', function() {
                const nextStep = parseInt(this.dataset.next);
                
                // Kiểm tra hợp lệ trước khi chuyển bước
                if (nextStep === 2) {
                    // Kiểm tra bước 1: thông tin tài khoản
                    const username = document.getElementById('username').value;
                    const email = document.getElementById('email').value;
                    const password = passwordInput.value;
                    const confirmPassword = confirmPasswordInput.value;
                    
                    if (!username || !email || !password || !confirmPassword) {
                        alert('Vui lòng điền đầy đủ thông tin tài khoản.');
                        return;
                    }
                    
                    if (password !== confirmPassword) {
                        alert('Mật khẩu và xác nhận mật khẩu không khớp.');
                        return;
                    }
                    
                    if (password.length < 8) {
                        alert('Mật khẩu phải có ít nhất 8 ký tự.');
                        return;
                    }
                } else if (nextStep === 3) {
                    // Kiểm tra bước 2: chọn vai trò
                    if (!selectedRoleInput.value) {
                        roleError.style.display = 'block';
                        return;
                    } else {
                        roleError.style.display = 'none';
                        
                        // Hiển thị các trường dữ liệu tùy theo vai trò
                        studentFields.style.display = 'none';
                        facultyFields.style.display = 'none';
                        
                        if (selectedRoleInput.value === 'student') {
                            studentFields.style.display = 'block';
                        } else if (selectedRoleInput.value === 'faculty') {
                            facultyFields.style.display = 'block';
                        }
                    }
                }
                
                // Chuyển đến bước tiếp theo
                goToStep(nextStep);
            });
        });
        
        // Các nút Quay lại
        prevButtons.forEach(button => {
            button.addEventListener('click', function() {
                const prevStep = parseInt(this.dataset.prev);
                goToStep(prevStep);
            });
        });
        
        // Xử lý khi chọn vai trò
        roleCards.forEach(card => {
            card.addEventListener('click', function() {
                const role = this.dataset.role;
                
                // Xóa class active khỏi tất cả các cards
                roleCards.forEach(c => c.classList.remove('active'));
                
                // Thêm class active cho card được chọn
                this.classList.add('active');
                
                // Lưu giá trị role vào input ẩn
                selectedRoleInput.value = role;
                
                // Ẩn thông báo lỗi nếu có
                roleError.style.display = 'none';
            });
        });
        
        // Kiểm tra độ mạnh của mật khẩu
        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                let strength = 0;
                
                if (password.length >= 8) strength += 20;
                if (password.match(/[A-Z]/)) strength += 20;
                if (password.match(/[a-z]/)) strength += 20;
                if (password.match(/[0-9]/)) strength += 20;
                if (password.match(/[^A-Za-z0-9]/)) strength += 20;
                
                passwordStrengthBar.style.width = strength + '%';
                
                if (strength <= 20) {
                    passwordStrengthBar.style.backgroundColor = '#e63946'; // yếu
                } else if (strength <= 60) {
                    passwordStrengthBar.style.backgroundColor = '#ffb703'; // trung bình
                } else {
                    passwordStrengthBar.style.backgroundColor = '#4cc9f0'; // mạnh
                }
            });
        }
        
        // Kiểm tra khớp mật khẩu
        if (confirmPasswordInput) {
            confirmPasswordInput.addEventListener('input', function() {
                if (this.value !== passwordInput.value) {
                    this.classList.add('is-invalid');
                } else {
                    this.classList.remove('is-invalid');
                }
            });
        }
        
        // Hàm chuyển đến bước cụ thể
        function goToStep(step) {
            // Ẩn tất cả các bước
            steps.forEach(s => s.classList.remove('active'));
            progressSteps.forEach(ps => ps.classList.remove('active', 'completed'));
            
            // Hiển thị bước hiện tại
            document.getElementById(`step${step}`).classList.add('active');
            
            // Cập nhật hiển thị thanh tiến trình
            for (let i = 1; i <= progressSteps.length; i++) {
                const progressStep = document.querySelector(`.progress-step[data-step="${i}"]`);
                if (i < step) {
                    progressStep.classList.add('completed');
                } else if (i === step) {
                    progressStep.classList.add('active');
                }
            }
            
            // Cập nhật bước hiện tại
            currentStep = step;
        }
        
        // Xác thực form khi submit
        if (registrationForm) {
            registrationForm.addEventListener('submit', function(event) {
                if (currentStep !== 3) {
                    event.preventDefault();
                    alert('Vui lòng hoàn thành tất cả các bước.');
                    return false;
                }
                
                // Form validation khi ở bước 3
                const hoTen = document.getElementById('hoTen').value;
                
                if (!hoTen) {
                    event.preventDefault();
                    alert('Vui lòng nhập họ và tên.');
                    return false;
                }
                
                return true;
            });
        }
    });
    </script>
    <script src="../assets/js/main.js"></script>
</body>
</html>