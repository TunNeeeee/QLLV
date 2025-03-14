<?php
require_once '../config/config.php';
require_once '../config/database.php';

echo "<h1>Sửa cấu trúc bảng SinhVienGiangVienHuongDan</h1>";

$db = new Database();

try {
    // 1. Kiểm tra cấu trúc bảng hiện tại
    $db->query("DESCRIBE SinhVienGiangVienHuongDan");
    $currentStructure = $db->resultSet();
    
    echo "<h3>Cấu trúc hiện tại:</h3>";
    echo "<pre>";
    print_r($currentStructure);
    echo "</pre>";
    
    // 2. Sao lưu dữ liệu hiện tại
    $db->query("SELECT * FROM SinhVienGiangVienHuongDan");
    $existingData = $db->resultSet();
    
    echo "<p>Đã sao lưu " . count($existingData) . " bản ghi.</p>";
    
    // 3. Xóa bảng cũ
    $db->query("DROP TABLE IF EXISTS SinhVienGiangVienHuongDan");
    $db->execute();
    echo "<p>Đã xóa bảng cũ.</p>";
    
    // 4. Tạo lại bảng với cấu trúc đúng
    $db->query("CREATE TABLE SinhVienGiangVienHuongDan (
        ID int(11) NOT NULL AUTO_INCREMENT,
        SinhVienID int(11) NOT NULL,
        GiangVienID int(11) NOT NULL,
        DeTaiID int(11) DEFAULT NULL,
        NgayBatDau date NOT NULL,
        NgayKetThucDuKien date DEFAULT NULL,
        TienDo int(3) DEFAULT 0,
        GhiChu text DEFAULT NULL,
        NgayTao timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (ID),
        UNIQUE KEY SinhVien_GiangVien_Unique (SinhVienID,GiangVienID),
        KEY GiangVienID (GiangVienID),
        KEY DeTaiID (DeTaiID),
        CONSTRAINT svgvhd_sv_fk FOREIGN KEY (SinhVienID) REFERENCES SinhVien (SinhVienID) ON DELETE CASCADE,
        CONSTRAINT svgvhd_gv_fk FOREIGN KEY (GiangVienID) REFERENCES GiangVien (GiangVienID) ON DELETE CASCADE,
        CONSTRAINT svgvhd_dt_fk FOREIGN KEY (DeTaiID) REFERENCES DeTai (DeTaiID) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    $db->execute();
    echo "<p>Đã tạo lại bảng với cấu trúc đúng.</p>";
    
    // 5. Khôi phục dữ liệu
    if (count($existingData) > 0) {
        echo "<p>Đang khôi phục dữ liệu...</p>";
        $restoredCount = 0;
        
        foreach ($existingData as $record) {
            try {
                $db->query("INSERT INTO SinhVienGiangVienHuongDan 
                          (SinhVienID, GiangVienID, DeTaiID, NgayBatDau, NgayKetThucDuKien, TienDo, GhiChu, NgayTao) 
                          VALUES 
                          (:svID, :gvID, :dtID, :start, :end, :progress, :note, :created)");
                $db->bind(':svID', $record['SinhVienID']);
                $db->bind(':gvID', $record['GiangVienID']);
                $db->bind(':dtID', $record['DeTaiID'] ?: null);
                $db->bind(':start', $record['NgayBatDau']);
                $db->bind(':end', $record['NgayKetThucDuKien']);
                $db->bind(':progress', $record['TienDo']);
                $db->bind(':note', $record['GhiChu']);
                $db->bind(':created', $record['NgayTao']);
                
                if ($db->execute()) {
                    $restoredCount++;
                }
            } catch (PDOException $e) {
                echo "<p>Lỗi khi khôi phục bản ghi: " . $e->getMessage() . "</p>";
            }
        }
        
        echo "<p>Đã khôi phục $restoredCount/" . count($existingData) . " bản ghi.</p>";
    }
    
    // 6. Kiểm tra lại cấu trúc bảng
    $db->query("DESCRIBE SinhVienGiangVienHuongDan");
    $newStructure = $db->resultSet();
    
    echo "<h3>Cấu trúc mới:</h3>";
    echo "<pre>";
    print_r($newStructure);
    echo "</pre>";
    
    // 7. Kiểm tra ràng buộc khóa ngoại
    $db->query("SELECT * FROM information_schema.KEY_COLUMN_USAGE
               WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'sinhviengiangvienhuongdan'
               AND REFERENCED_TABLE_NAME IS NOT NULL");
    $constraints = $db->resultSet();
    
    echo "<h3>Ràng buộc khóa ngoại:</h3>";
    echo "<pre>";
    print_r($constraints);
    echo "</pre>";
    
    echo "<div style='margin-top:20px; padding:10px; background:#dff0d8; border:1px solid #d6e9c6;'>";
    echo "<h4 style='color:#3c763d'>Sửa cấu trúc bảng thành công!</h4>";
    echo "<p>Bảng SinhVienGiangVienHuongDan đã được tạo lại với cấu trúc đúng và ràng buộc khóa ngoại chính xác.</p>";
    echo "<p>Bạn có thể quay lại <a href='assign-students.php' style='font-weight:bold;'>trang phân công sinh viên</a> để tiếp tục.</p>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div style='margin-top:20px; padding:10px; background:#f2dede; border:1px solid #ebccd1;'>";
    echo "<h4 style='color:#a94442'>Lỗi!</h4>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    margin: 20px;
    line-height: 1.6;
}
h1, h3, h4 {
    color: #333;
}
pre {
    background: #f8f9fa;
    padding: 10px;
    border: 1px solid #ddd;
    overflow: auto;
}
p {
    margin-bottom: 10px;
}
</style>