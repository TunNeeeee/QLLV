<?php
class User {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    public function createUser($username, $password, $email, $role) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $this->db->query("INSERT INTO Users (Username, Password, Email, Role) VALUES (:username, :password, :email, :role)");
        $this->db->bind(':username', $username);
        $this->db->bind(':password', $hashedPassword);
        $this->db->bind(':email', $email);
        $this->db->bind(':role', $role);
        return $this->db->execute();
    }

    public function getUserById($userId) {
        $this->db->query("SELECT * FROM Users WHERE UserID = :userId");
        $this->db->bind(':userId', $userId);
        return $this->db->single();
    }

    public function updateUser($userId, $username, $email, $role) {
        $this->db->query("UPDATE Users SET Username = :username, Email = :email, Role = :role WHERE UserID = :userId");
        $this->db->bind(':username', $username);
        $this->db->bind(':email', $email);
        $this->db->bind(':role', $role);
        $this->db->bind(':userId', $userId);
        return $this->db->execute();
    }

    public function deleteUser($userId) {
        $this->db->query("DELETE FROM Users WHERE UserID = :userId");
        $this->db->bind(':userId', $userId);
        return $this->db->execute();
    }

    public function getAllUsers() {
        $this->db->query("SELECT * FROM Users");
        return $this->db->resultSet();
    }
}
?>