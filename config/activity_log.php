<?php
/**
 * Ghi lại hoạt động người dùng
 * 
 * @param object $db Đối tượng Database
 * @param int $userId ID của người dùng thực hiện hành động
 * @param string $actionType Loại hành động (create_thesis, update_thesis, etc.)
 * @param string $description Mô tả hành động
 * @param int|null $relatedId ID của đối tượng liên quan (nếu có)
 * @return bool Trạng thái ghi log (true nếu thành công, false nếu thất bại)
 */
function logActivity($db, $userId, $actionType, $description, $relatedId = null) {
    try {
        // Tạo bảng log nếu chưa tồn tại
        $db->query("CREATE TABLE IF NOT EXISTS `HoatDongNguoiDung` (
            `HoatDongID` int(11) NOT NULL AUTO_INCREMENT,
            `UserID` int(11) NOT NULL,
            `LoaiHanhDong` varchar(50) NOT NULL,
            `MoTa` text NOT NULL,
            `DoiTuongID` int(11) DEFAULT NULL,
            `ThoiGian` timestamp NOT NULL DEFAULT current_timestamp(),
            `IP` varchar(50) DEFAULT NULL,
            PRIMARY KEY (`HoatDongID`),
            KEY `UserID` (`UserID`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        // Lấy địa chỉ IP của người dùng
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        
        // Ghi log
        $db->query("INSERT INTO HoatDongNguoiDung 
                   (UserID, LoaiHanhDong, MoTa, DoiTuongID, IP) 
                   VALUES 
                   (:userId, :actionType, :description, :relatedId, :ip)");
        
        $db->bind(':userId', $userId);
        $db->bind(':actionType', $actionType);
        $db->bind(':description', $description);
        $db->bind(':relatedId', $relatedId);
        $db->bind(':ip', $ip);
        
        return $db->execute();
    } catch (PDOException $e) {
        // Ghi lỗi vào file log hệ thống
        $errorMessage = date('Y-m-d H:i:s') . " - Lỗi ghi log: " . $e->getMessage() . "\n";
        error_log($errorMessage, 3, dirname(__DIR__) . '/logs/system_error.log');
        return false;
    }
}
?>