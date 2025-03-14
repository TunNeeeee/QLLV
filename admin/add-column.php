<?php
require_once '../config/config.php';
require_once '../config/database.php';

$db = new Database();

try {
    // Kiểm tra cột TienDo
    $db->query("SHOW COLUMNS FROM SinhVienGiangVienHuongDan LIKE 'TienDo'");
    $tienDoExists = $db->rowCount() > 0;
    
    if (!$tienDoExists) {
        $db->query("ALTER TABLE SinhVienGiangVienHuongDan 
                   ADD COLUMN TienDo int(3) DEFAULT 0");
        $db->execute();
        echo "Đã thêm cột TienDo vào bảng SinhVienGiangVienHuongDan.";
    } else {
        echo "Cột TienDo đã tồn tại.";
    }
} catch (PDOException $e) {
    echo "Lỗi: " . $e->getMessage();
}
?>