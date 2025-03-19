<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../classes/Auth.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Auth check
$auth = new Auth();
if (!$auth->isLoggedIn() || $auth->getUserRole() != 'student') {
    header('Location: ' . BASE_URL . 'auth/login.php?error=unauthorized_access');
    exit;
}

$db = new Database();
$userId = $_SESSION['user_id'];
$error = '';
$success = '';
$currentPage = 'profile';
$pageTitle = 'Chỉnh sửa hồ sơ';

// Get current student information
try {
    $db->query("SELECT s.*, u.Email FROM SinhVien s JOIN Users u ON s.UserID = u.UserID WHERE s.UserID = :userId");
    $db->bind(':userId', $userId);
    $student = $db->single();
} catch (PDOException $e) {
    $error = "Lỗi khi lấy thông tin sinh viên: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hoTen = isset($_POST['ho_ten']) ? trim($_POST['ho_ten']) : '';
    $soDienThoai = isset($_POST['so_dien_thoai']) ? trim($_POST['so_dien_thoai']) : '';
    $diaChi = isset($_POST['dia_chi']) ? trim($_POST['dia_chi']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';

    // Validate input
    if (empty($hoTen)) {
        $error = "Họ tên không được để trống";
    } else {
        try {
            // Update student information
            $db->query("UPDATE SinhVien SET 
                        HoTen = :hoTen, 
                        SoDienThoai = :soDienThoai, 
                        DiaChi = :diaChi
                        WHERE UserID = :userId");
            $db->bind(':hoTen', $hoTen);
            $db->bind(':soDienThoai', $soDienThoai);
            $db->bind(':diaChi', $diaChi);
            $db->bind(':userId', $userId);
            $db->execute();

            // Update email in Users table
            $db->query("UPDATE Users SET Email = :email WHERE UserID = :userId");
            $db->bind(':email', $email);
            $db->bind(':userId', $userId);
            $db->execute();

            // Handle profile picture upload
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                $filename = $_FILES['avatar']['name'];
                $filesize = $_FILES['avatar']['size'];
                $filetype = $_FILES['avatar']['type'];
                $tmp_name = $_FILES['avatar']['tmp_name'];

                $ext = pathinfo($filename, PATHINFO_EXTENSION);
                
                if (!in_array($ext, $allowed)) {
                    $error = "Chỉ chấp nhận file ảnh (jpg, jpeg, png, gif)";
                } elseif ($filesize > 5242880) { // 5MB max
                    $error = "Kích thước file quá lớn. Tối đa 5MB";
                } else {
                    $new_filename = 'avatar_' . $userId . '_' . time() . '.' . $ext;
                    
                    // Use relative path from the current script location
                    $upload_dir = dirname(__DIR__) . '/uploads/avatars/';
                    $upload_path = $upload_dir . $new_filename;
                    
                    // Try creating the directory with recursive option
                    if (!is_dir($upload_dir)) {
                        if (!@mkdir($upload_dir, 0777, true)) {
                            $error = "Không thể tạo thư mục upload: " . error_get_last()['message'];
                        }
                    }
                    
                    if (empty($error)) {
                        if (@move_uploaded_file($tmp_name, $upload_path)) {
                            // Update avatar path in database
                            $db->query("UPDATE SinhVien SET Avatar = :avatar WHERE UserID = :userId");
                            $db->bind(':avatar', 'uploads/avatars/' . $new_filename);
                            $db->bind(':userId', $userId);
                            $db->execute();
                        } else {
                            $error = "Lỗi khi tải lên ảnh đại diện: " . error_get_last()['message'];
                        }
                    }
                }
            }

            $success = "Cập nhật thông tin thành công!";
            
            // Refresh student data
            $db->query("SELECT s.*, u.Email FROM SinhVien s JOIN Users u ON s.UserID = u.UserID WHERE s.UserID = :userId");
            $db->bind(':userId', $userId);
            $student = $db->single();
            
        } catch (PDOException $e) {
            $error = "Lỗi khi cập nhật thông tin: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chỉnh sửa hồ sơ - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #f8f9fc;
            --accent-color: #3a0ca3;
            --text-color: #333;
            --light-text: #6c757d;
            --border-color: #e3e6f0;
            --success-color: #2ecc71;
            --info-color: #3498db;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #f5f7fb;
            color: var(--text-color);
        }
        
        .app-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            width: 250px;
            background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
            color: #fff;
            position: fixed;
            height: 100%;
            z-index: 100;
            transition: all 0.3s;
        }
        
        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-brand {
            color: white;
            text-decoration: none;
            font-weight: 700;
            font-size: 1.25rem;
            display: flex;
            align-items: center;
        }
        
        .sidebar-brand:hover {
            color: white;
            opacity: 0.9;
        }
        
        .sidebar-user {
            display: flex;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-user-img {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            font-size: 1.25rem;
            font-weight: 600;
            color: white;
        }
        
        .sidebar-user-name {
            font-weight: 600;
            margin-bottom: 0.25rem;
            display: block;
        }
        
        .sidebar-user-role {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.7);
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 1rem 0;
            margin: 0;
        }
        
        .sidebar-menu li {
            margin-bottom: 0.25rem;
        }
        
        .sidebar-menu a {
            padding: 0.85rem 1.5rem;
            display: flex;
            align-items: center;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: all 0.3s;
            border-radius: 0.25rem;
            margin: 0 0.5rem;
        }
        
        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-menu a i {
            margin-right: 0.75rem;
            font-size: 1rem;
            width: 20px;
            text-align: center;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 2rem;
            transition: all 0.3s;
        }

        .sidebar-user-img img, .profile-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .profile-avatar img {
            border: 4px solid white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        /* Responsive */
        @media (max-width: 991.98px) {
            .sidebar {
                width: 70px;
            }
            
            .sidebar-brand span,
            .sidebar-user-info,
            .sidebar-menu a span {
                display: none;
            }
            
            .sidebar-menu a i {
                margin-right: 0;
            }
            
            .main-content {
                margin-left: 70px;
            }
        }

        /* Card styles */
        .card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.07);
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            background-color: #fff;
            border-bottom: 1px solid #eee;
            padding: 1.25rem 1.5rem;
        }
        
        .avatar-edit .btn {
            width: 32px;
            height: 32px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <a href="dashboard.php" class="sidebar-brand">
                    <i class="fas fa-graduation-cap me-2"></i>
                    <span><?php echo SITE_NAME; ?></span>
                </a>
            </div>
            
            <div class="sidebar-user">
                <div class="sidebar-user-img">
                    <?php if (!empty($student['Avatar'])): ?>
                        <img src="<?php echo BASE_URL . $student['Avatar']; ?>" alt="Avatar" class="rounded-circle w-100 h-100" style="object-fit: cover;">
                    <?php else: ?>
                        <?php echo strtoupper(substr($student['HoTen'] ?? 'U', 0, 1)); ?>
                    <?php endif; ?>
                </div>
                <div class="sidebar-user-info">
                    <span class="sidebar-user-name"><?php echo htmlspecialchars($student['HoTen']); ?></span>
                    <span class="sidebar-user-role">Sinh viên</span>
                </div>
            </div>
            
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Bảng điều khiển</span></a></li>
                <li><a href="thesis.php"><i class="fas fa-book"></i> <span>Luận văn</span></a></li>
                <li><a href="appointments.php"><i class="fas fa-calendar-alt"></i> <span>Lịch gặp</span></a></li>
                <li><a href="documents.php"><i class="fas fa-file-alt"></i> <span>Tài liệu</span></a></li>
                <li><a href="profile.php" class="active"><i class="fas fa-user"></i> <span>Hồ sơ cá nhân</span></a></li>
                <li><a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> <span>Đăng xuất</span></a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <div class="container-fluid p-0">
                <h1 class="h3 mb-3">Chỉnh sửa hồ sơ</h1>
                
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Trang chủ</a></li>
                        <li class="breadcrumb-item"><a href="profile.php">Hồ sơ cá nhân</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Chỉnh sửa</li>
                    </ol>
                </nav>

                <div class="row">
                    <div class="col-xl-8 mx-auto">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($success)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?php echo $success; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Thông tin cá nhân</h5>
                            </div>
                            <div class="card-body">
                                <form method="post" enctype="multipart/form-data">
                                    <div class="row mb-4">
                                        <div class="col-md-12 text-center">
                                            <div class="position-relative d-inline-block">
                                                <?php if (!empty($student['Avatar'])): ?>
                                                    <img src="<?php echo BASE_URL . $student['Avatar']; ?>" alt="Avatar" class="rounded-circle img-thumbnail" style="width: 120px; height: 120px; object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 120px; height: 120px; margin: 0 auto; font-size: 48px; font-weight: 600;">
                                                        <?php echo strtoupper(substr($student['HoTen'] ?? 'U', 0, 1)); ?>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="avatar-edit position-absolute bottom-0 end-0">
                                                    <label for="avatar" class="btn btn-sm btn-primary rounded-circle">
                                                        <i class="fas fa-camera"></i>
                                                    </label>
                                                    <input type="file" id="avatar" name="avatar" class="d-none">
                                                </div>
                                            </div>
                                            <p class="text-muted small mt-2">Click vào biểu tượng máy ảnh để thay đổi ảnh đại diện</p>
                                        </div>
                                    </div>

                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="ho_ten" class="form-label">Họ và tên <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="ho_ten" name="ho_ten" value="<?php echo htmlspecialchars($student['HoTen'] ?? ''); ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="ma_sv" class="form-label">Mã sinh viên</label>
                                            <input type="text" class="form-control" id="ma_sv" value="<?php echo htmlspecialchars($student['MaSV'] ?? ''); ?>" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($student['Email'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="so_dien_thoai" class="form-label">Số điện thoại</label>
                                            <input type="text" class="form-control" id="so_dien_thoai" name="so_dien_thoai" value="<?php echo htmlspecialchars($student['SoDienThoai'] ?? ''); ?>">
                                        </div>
                                        <div class="col-12">
                                            <label for="dia_chi" class="form-label">Địa chỉ</label>
                                            <textarea class="form-control" id="dia_chi" name="dia_chi" rows="2"><?php echo htmlspecialchars($student['DiaChi'] ?? ''); ?></textarea>
                                        </div>
                                        <div class="col-12 mt-4">
                                            <div class="d-flex justify-content-between">
                                                <a href="profile.php" class="btn btn-outline-secondary">Hủy bỏ</a>
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="fas fa-save me-1"></i> Lưu thay đổi
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="card mt-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">Đổi mật khẩu</h5>
                            </div>
                            <div class="card-body">
                                <p class="text-muted mb-4">Để đảm bảo an toàn cho tài khoản, bạn nên đổi mật khẩu thường xuyên.</p>
                                <a href="change-password.php" class="btn btn-outline-primary">
                                    <i class="fas fa-key me-1"></i> Đổi mật khẩu
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Footer -->
                <footer class="footer mt-5">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-0">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?> - Hệ thống quản lý luận văn</p>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <div class="footer-links">
                                    <a href="#" class="me-3">Điều khoản</a>
                                    <a href="#" class="me-3">Chính sách</a>
                                    <a href="#">Hỗ trợ</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </footer>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // Preview avatar before upload
    document.getElementById('avatar').addEventListener('change', function(event) {
        if (event.target.files && event.target.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const imgElement = event.target.closest('.position-relative').querySelector('img');
                if (imgElement) {
                    imgElement.src = e.target.result;
                } else {
                    // If no img element exists (using the default initial), create one
                    const initialElement = event.target.closest('.position-relative').querySelector('div.rounded-circle');
                    if (initialElement) {
                        initialElement.style.display = 'none';
                        
                        const newImg = document.createElement('img');
                        newImg.src = e.target.result;
                        newImg.alt = 'Avatar';
                        newImg.className = 'rounded-circle img-thumbnail';
                        newImg.style = 'width: 120px; height: 120px; object-fit: cover;';
                        
                        initialElement.parentNode.insertBefore(newImg, initialElement);
                    }
                }
            }
            reader.readAsDataURL(event.target.files[0]);
        }
    });

    // Responsive sidebar
    document.addEventListener('DOMContentLoaded', function() {
        // Kiểm tra và thiết lập trạng thái sidebar dựa trên kích thước màn hình
        const handleResize = () => {
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');
            
            if (window.innerWidth < 992) {
                sidebar.style.width = '70px';
                mainContent.style.marginLeft = '70px';
                
                const userInfo = document.querySelector('.sidebar-user-info');
                const menuTexts = document.querySelectorAll('.sidebar-menu a span');
                const brandText = document.querySelector('.sidebar-brand span');
                
                if (userInfo) userInfo.style.display = 'none';
                menuTexts.forEach(span => span.style.display = 'none');
                if (brandText) brandText.style.display = 'none';
            } else {
                sidebar.style.width = '250px';
                mainContent.style.marginLeft = '250px';
                
                const userInfo = document.querySelector('.sidebar-user-info');
                const menuTexts = document.querySelectorAll('.sidebar-menu a span');
                const brandText = document.querySelector('.sidebar-brand span');
                
                if (userInfo) userInfo.style.display = 'block';
                menuTexts.forEach(span => span.style.display = 'inline');
                if (brandText) brandText.style.display = 'inline';
            }
        };
        
        // Khởi tạo và đăng ký sự kiện resize
        handleResize();
        window.addEventListener('resize', handleResize);
    });
    </script>
</body>
</html>