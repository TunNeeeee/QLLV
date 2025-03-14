<?php
require_once '../config/config.php';
require_once '../config/database.php';

echo "<h1>Thêm cột TienDo vào bảng SinhVienGiangVienHuongDan</h1>";

$db = new Database();

try {
    // Kiểm tra xem cột TienDo đã tồn tại chưa
    $db->query("SHOW COLUMNS FROM SinhVienGiangVienHuongDan LIKE 'TienDo'");
    $exists = $db->rowCount() > 0;
    
    if (!$exists) {
        // Thêm cột TienDo nếu chưa tồn tại
        $db->query("ALTER TABLE SinhVienGiangVienHuongDan ADD COLUMN TienDo int(3) DEFAULT 0");
        $db->execute();
        echo "<p style='color:green'>Đã thêm cột TienDo vào bảng SinhVienGiangVienHuongDan thành công!</p>";
    } else {
        echo "<p>Cột TienDo đã tồn tại trong bảng SinhVienGiangVienHuongDan.</p>";
    }
    
    echo "<p><a href='../faculty/dashboard.php'>Quay lại trang tổng quan</a></p>";
} catch (PDOException $e) {
    echo "<p style='color:red'>Lỗi: " . $e->getMessage() . "</p>";
}
?>

<style>
    body { 
        font-family: Arial, sans-serif;
        max-width: 800px;
        margin: 0 auto;
        padding: 20px;
        line-height: 1.6;
    }
    h1 { color: #4361ee; }
    p { margin-bottom: 15px; }
    a { 
        color: #4361ee;
        text-decoration: none;
        font-weight: bold;
    }
    a:hover { text-decoration: underline; }
</style>