<?php
class Notification {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    public function createNotification($userId, $title, $content, $type = null) {
        $this->db->query("
            INSERT INTO thongbao (
                UserID, 
                TieuDe, 
                NoiDung,
                LoaiThongBao,
                DaDoc,
                NgayTao
            ) VALUES (
                :userId, 
                :title, 
                :content,
                :type,
                0,
                NOW()
            )
        ");
        $this->db->bind(':userId', $userId);
        $this->db->bind(':title', $title);
        $this->db->bind(':content', $content);
        $this->db->bind(':type', $type);
        return $this->db->execute();
    }

    public function getNotifications($userId, $limit = null) {
        $query = "
            SELECT * 
            FROM thongbao 
            WHERE UserID = :userId 
            ORDER BY NgayTao DESC
        ";
        
        if ($limit) {
            $query .= " LIMIT :limit";
        }
        
        $this->db->query($query);
        $this->db->bind(':userId', $userId);
        if ($limit) {
            $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        }
        
        return $this->db->resultSet();
    }

    public function getUnreadNotifications($userId) {
        $this->db->query("
            SELECT * 
            FROM thongbao 
            WHERE UserID = :userId 
            AND DaDoc = 0
            ORDER BY NgayTao DESC
        ");
        $this->db->bind(':userId', $userId);
        return $this->db->resultSet();
    }

    public function countUnreadNotifications($userId) {
        $this->db->query("
            SELECT COUNT(*) as total
            FROM thongbao
            WHERE UserID = :userId
            AND DaDoc = 0
        ");
        $this->db->bind(':userId', $userId);
        $result = $this->db->single();
        return $result ? $result['total'] : 0;
    }

    public function markAsRead($notificationId) {
        $this->db->query("
            UPDATE thongbao 
            SET DaDoc = 1 
            WHERE ThongBaoID = :notificationId
        ");
        $this->db->bind(':notificationId', $notificationId);
        return $this->db->execute();
    }

    public function markAllAsRead($userId) {
        $this->db->query("
            UPDATE thongbao 
            SET DaDoc = 1 
            WHERE UserID = :userId
            AND DaDoc = 0
        ");
        $this->db->bind(':userId', $userId);
        return $this->db->execute();
    }

    public function deleteNotification($notificationId) {
        $this->db->query("DELETE FROM thongbao WHERE ThongBaoID = :notificationId");
        $this->db->bind(':notificationId', $notificationId);
        return $this->db->execute();
    }

    public function deleteAllNotifications($userId) {
        $this->db->query("DELETE FROM thongbao WHERE UserID = :userId");
        $this->db->bind(':userId', $userId);
        return $this->db->execute();
    }

    public function getNotificationById($notificationId) {
        $this->db->query("
            SELECT * 
            FROM thongbao 
            WHERE ThongBaoID = :notificationId
        ");
        $this->db->bind(':notificationId', $notificationId);
        return $this->db->single();
    }
}
?>