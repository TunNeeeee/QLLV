<?php
require_once '../config/config.php';
require_once '../config/database.php';

echo "<h1>Sửa cấu trúc bảng ThongBao</h1>";

$db = new Database();

try {
    // Kiểm tra bảng tồn tại - sử dụng cách kiểm tra chính xác hơn
    $db->query("SELECT COUNT(*) AS table_exists FROM information_schema.tables 
                WHERE table_schema = DATABASE() 
                AND table_name = 'ThongBao'");
    $result = $db->single();
    $tableExists = ($result['table_exists'] > 0);
    
    if ($tableExists) {
        echo "<p>Bảng ThongBao đã tồn tại, đang kiểm tra cấu trúc...</p>";
        
        // Kiểm tra cột LoaiThongBao
        $db->query("SHOW COLUMNS FROM ThongBao LIKE 'LoaiThongBao'");
        $loaiThongBaoExists = $db->rowCount() > 0;
        
        if (!$loaiThongBaoExists) {
            echo "<p>Thêm cột LoaiThongBao...</p>";
            $db->query("ALTER TABLE ThongBao ADD COLUMN LoaiThongBao varchar(50) DEFAULT NULL");
            $db->execute();
            echo "<p>Đã thêm cột LoaiThongBao thành công!</p>";
        } else {
            echo "<p>Cột LoaiThongBao đã tồn tại.</p>";
        }
        
        // Kiểm tra cột LienKet
        $db->query("SHOW COLUMNS FROM ThongBao LIKE 'LienKet'");
        $lienKetExists = $db->rowCount() > 0;
        
        if (!$lienKetExists) {
            echo "<p>Thêm cột LienKet...</p>";
            $db->query("ALTER TABLE ThongBao ADD COLUMN LienKet varchar(255) DEFAULT NULL");
            $db->execute();
            echo "<p>Đã thêm cột LienKet thành công!</p>";
        } else {
            echo "<p>Cột LienKet đã tồn tại.</p>";
        }
        
        echo "<p>Cấu trúc bảng ThongBao đã được cập nhật thành công!</p>";
    } else {
        echo "<p>Bảng ThongBao chưa tồn tại, đang tạo mới...</p>";
        
        // Sử dụng IF NOT EXISTS để tránh lỗi nếu bảng đã tồn tại
        $db->query("CREATE TABLE IF NOT EXISTS `ThongBao` (
            `ThongBaoID` int(11) NOT NULL AUTO_INCREMENT,
            `UserID` int(11) NOT NULL,
            `TieuDe` varchar(255) NOT NULL,
            `NoiDung` text NOT NULL,
            `DaDoc` tinyint(1) DEFAULT 0,
            `NgayTao` timestamp NOT NULL DEFAULT current_timestamp(),
            `LoaiThongBao` varchar(50) DEFAULT NULL,
            `LienKet` varchar(255) DEFAULT NULL,
            PRIMARY KEY (`ThongBaoID`),
            KEY `UserID` (`UserID`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $db->execute();
        
        echo "<p>Đã tạo bảng ThongBao thành công!</p>";
    }
    
    // Kiểm tra lại cấu trúc bảng sau khi thực hiện các thay đổi
    $db->query("DESCRIBE ThongBao");
    $tableStructure = $db->resultSet();
    
    echo "<h3>Cấu trúc hiện tại của bảng ThongBao:</h3>";
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    foreach ($tableStructure as $column) {
        echo "<tr>";
        echo "<td>".$column['Field']."</td>";
        echo "<td>".$column['Type']."</td>";
        echo "<td>".$column['Null']."</td>";
        echo "<td>".$column['Key']."</td>";
        echo "<td>".($column['Default'] === NULL ? 'NULL' : $column['Default'])."</td>";
        echo "<td>".$column['Extra']."</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<div style='margin-top: 20px;'>";
    echo "<a href='assign-students.php' class='btn btn-primary'>Quay lại trang phân công sinh viên</a>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Lỗi: " . $e->getMessage() . "</p>";
    
    // Nếu lỗi là "table already exists", chỉ cần tiếp tục quy trình kiểm tra cột
    if (strpos($e->getMessage(), "already exists") !== false) {
        echo "<p>Bảng đã tồn tại, đang tiếp tục kiểm tra cấu trúc...</p>";
        
        // Kiểm tra cột LoaiThongBao
        try {
            $db->query("SHOW COLUMNS FROM ThongBao LIKE 'LoaiThongBao'");
            $loaiThongBaoExists = $db->rowCount() > 0;
            
            if (!$loaiThongBaoExists) {
                echo "<p>Thêm cột LoaiThongBao...</p>";
                $db->query("ALTER TABLE ThongBao ADD COLUMN LoaiThongBao varchar(50) DEFAULT NULL");
                $db->execute();
                echo "<p>Đã thêm cột LoaiThongBao thành công!</p>";
            } else {
                echo "<p>Cột LoaiThongBao đã tồn tại.</p>";
            }
            
            // Kiểm tra cột LienKet
            $db->query("SHOW COLUMNS FROM ThongBao LIKE 'LienKet'");
            $lienKetExists = $db->rowCount() > 0;
            
            if (!$lienKetExists) {
                echo "<p>Thêm cột LienKet...</p>";
                $db->query("ALTER TABLE ThongBao ADD COLUMN LienKet varchar(255) DEFAULT NULL");
                $db->execute();
                echo "<p>Đã thêm cột LienKet thành công!</p>";
            } else {
                echo "<p>Cột LienKet đã tồn tại.</p>";
            }
            
            echo "<div style='margin-top: 20px;'>";
            echo "<a href='assign-students.php' class='btn btn-primary'>Quay lại trang phân công sinh viên</a>";
            echo "</div>";
        } catch (PDOException $innerE) {
            echo "<p style='color: red;'>Lỗi khi kiểm tra cấu trúc cột: " . $innerE->getMessage() . "</p>";
        }
    }
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    margin: 20px;
    line-height: 1.6;
}
h1 {
    color: #4361ee;
}
p {
    margin-bottom: 10px;
}
.btn {
    display: inline-block;
    padding: 8px 16px;
    background-color: #4361ee;
    color: white;
    text-decoration: none;
    border-radius: 4px;
}
.btn:hover {
    background-color: #3a0ca3;
}
</style>