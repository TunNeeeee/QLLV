<?php
class User {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    public function createUser($username, $password, $email, $role) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $this->db->query("
            INSERT INTO users (
                Username, 
                Password, 
                Email, 
                Role,
                NgayTao
            ) VALUES (
                :username, 
                :password, 
                :email, 
                :role,
                NOW()
            )
        ");
        $this->db->bind(':username', $username);
        $this->db->bind(':password', $hashedPassword);
        $this->db->bind(':email', $email);
        $this->db->bind(':role', $role);
        return $this->db->execute();
    }

    public function getUserById($userId) {
        $this->db->query("
            SELECT u.*, 
                   CASE 
                       WHEN u.Role = 'student' THEN sv.HoTen
                       WHEN u.Role = 'faculty' THEN gv.HoTen
                       WHEN u.Role = 'admin' THEN a.HoTen
                   END as FullName,
                   CASE 
                       WHEN u.Role = 'student' THEN sv.MaSV
                       WHEN u.Role = 'faculty' THEN gv.MaGV
                       WHEN u.Role = 'admin' THEN a.MaAdmin
                   END as Code
            FROM users u
            LEFT JOIN sinhvien sv ON u.UserID = sv.UserID
            LEFT JOIN giangvien gv ON u.UserID = gv.UserID
            LEFT JOIN admin a ON u.UserID = a.UserID
            WHERE u.UserID = :userId
        ");
        $this->db->bind(':userId', $userId);
        return $this->db->single();
    }

    public function updateUser($userId, $username, $email, $role) {
        $this->db->query("
            UPDATE users 
            SET 
                Username = :username, 
                Email = :email, 
                Role = :role,
                NgayCapNhat = NOW()
            WHERE UserID = :userId
        ");
        $this->db->bind(':username', $username);
        $this->db->bind(':email', $email);
        $this->db->bind(':role', $role);
        $this->db->bind(':userId', $userId);
        return $this->db->execute();
    }

    public function deleteUser($userId) {
        $this->db->query("DELETE FROM users WHERE UserID = :userId");
        $this->db->bind(':userId', $userId);
        return $this->db->execute();
    }

    public function getAllUsers() {
        $this->db->query("
            SELECT u.*, 
                   CASE 
                       WHEN u.Role = 'student' THEN sv.HoTen
                       WHEN u.Role = 'faculty' THEN gv.HoTen
                       WHEN u.Role = 'admin' THEN a.HoTen
                   END as FullName,
                   CASE 
                       WHEN u.Role = 'student' THEN sv.MaSV
                       WHEN u.Role = 'faculty' THEN gv.MaGV
                       WHEN u.Role = 'admin' THEN a.MaAdmin
                   END as Code
            FROM users u
            LEFT JOIN sinhvien sv ON u.UserID = sv.UserID
            LEFT JOIN giangvien gv ON u.UserID = gv.UserID
            LEFT JOIN admin a ON u.UserID = a.UserID
            ORDER BY u.NgayTao DESC
        ");
        return $this->db->resultSet();
    }

    public function getUserByUsername($username) {
        $this->db->query("
            SELECT u.*, 
                   CASE 
                       WHEN u.Role = 'student' THEN sv.HoTen
                       WHEN u.Role = 'faculty' THEN gv.HoTen
                       WHEN u.Role = 'admin' THEN a.HoTen
                   END as FullName,
                   CASE 
                       WHEN u.Role = 'student' THEN sv.MaSV
                       WHEN u.Role = 'faculty' THEN gv.MaGV
                       WHEN u.Role = 'admin' THEN a.MaAdmin
                   END as Code
            FROM users u
            LEFT JOIN sinhvien sv ON u.UserID = sv.UserID
            LEFT JOIN giangvien gv ON u.UserID = gv.UserID
            LEFT JOIN admin a ON u.UserID = a.UserID
            WHERE u.Username = :username
        ");
        $this->db->bind(':username', $username);
        return $this->db->single();
    }

    public function getUserByEmail($email) {
        $this->db->query("
            SELECT u.*, 
                   CASE 
                       WHEN u.Role = 'student' THEN sv.HoTen
                       WHEN u.Role = 'faculty' THEN gv.HoTen
                       WHEN u.Role = 'admin' THEN a.HoTen
                   END as FullName,
                   CASE 
                       WHEN u.Role = 'student' THEN sv.MaSV
                       WHEN u.Role = 'faculty' THEN gv.MaGV
                       WHEN u.Role = 'admin' THEN a.MaAdmin
                   END as Code
            FROM users u
            LEFT JOIN sinhvien sv ON u.UserID = sv.UserID
            LEFT JOIN giangvien gv ON u.UserID = gv.UserID
            LEFT JOIN admin a ON u.UserID = a.UserID
            WHERE u.Email = :email
        ");
        $this->db->bind(':email', $email);
        return $this->db->single();
    }

    public function updatePassword($userId, $newPassword) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $this->db->query("
            UPDATE users 
            SET 
                Password = :password,
                NgayCapNhat = NOW()
            WHERE UserID = :userId
        ");
        $this->db->bind(':password', $hashedPassword);
        $this->db->bind(':userId', $userId);
        return $this->db->execute();
    }

    public function updateLastLogin($userId) {
        $this->db->query("
            UPDATE users 
            SET 
                LanDangNhapCuoi = NOW()
            WHERE UserID = :userId
        ");
        $this->db->bind(':userId', $userId);
        return $this->db->execute();
    }
}
?>