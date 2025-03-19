<?php
// filepath: c:\xampp\htdocs\thesis-management-system\admin\fix-avatar-path.php
require_once '../config/config.php';
require_once '../config/database.php';

echo "<h1>Sửa lỗi đường dẫn avatar</h1>";

$db = new Database();

try {
    // Kiểm tra và sửa đường dẫn bị trùng lặp
    $db->query("UPDATE SinhVien SET Avatar = REPLACE(Avatar, 'uploads/avatars/uploads/avatars/', 'uploads/avatars/') WHERE Avatar LIKE 'uploads/avatars/uploads/avatars/%'");
    $db->execute();
    $affectedRows = $db->rowCount();
    
    echo "<p style='color:green'>Đã sửa $affectedRows đường dẫn avatar bị trùng lặp.</p>";
    
    // Kiểm tra thư mục upload
    $uploadDir = '../uploads/avatars/';
    if (!file_exists($uploadDir)) {
        if (mkdir($uploadDir, 0777, true)) {
            echo "<p style='color:green'>Đã tạo thư mục uploads/avatars.</p>";
        } else {
            echo "<p style='color:red'>Không thể tạo thư mục uploads/avatars. Vui lòng kiểm tra quyền truy cập.</p>";
        }
    } else {
        echo "<p>Thư mục uploads/avatars đã tồn tại.</p>";
    }
    
    echo "<p><a href='../faculty/dashboard.php'>Quay lại trang tổng quan</a></p>";
} catch (PDOException $e) {
    echo "<p style='color:red'>Lỗi: " . $e->getMessage() . "</p>";
}
?>