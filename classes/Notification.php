<?php
class Notification {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    public function createNotification($userId, $title, $content) {
        $this->db->query("INSERT INTO ThongBao (UserID, TieuDe, NoiDung) VALUES (:userId, :title, :content)");
        $this->db->bind(':userId', $userId);
        $this->db->bind(':title', $title);
        $this->db->bind(':content', $content);
        return $this->db->execute();
    }

    public function getNotifications($userId) {
        $this->db->query("SELECT * FROM ThongBao WHERE UserID = :userId ORDER BY NgayTao DESC");
        $this->db->bind(':userId', $userId);
        return $this->db->resultSet();
    }

    public function markAsRead($notificationId) {
        $this->db->query("UPDATE ThongBao SET TrangThai = 'đã đọc' WHERE ThongBaoID = :notificationId");
        $this->db->bind(':notificationId', $notificationId);
        return $this->db->execute();
    }

    public function deleteNotification($notificationId) {
        $this->db->query("DELETE FROM ThongBao WHERE ThongBaoID = :notificationId");
        $this->db->bind(':notificationId', $notificationId);
        return $this->db->execute();
    }
}
?>