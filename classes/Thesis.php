<?php
class Thesis {
    private $db;
    
    // Properties
    public $id;
    public $title;
    public $description;
    public $status;
    
    // Constructor
    public function __construct() {
        $this->db = new Database();
    }
    
    // Read single thesis
    public function read() {
        $this->db->query("SELECT * FROM DeTai WHERE DeTaiID = :id");
        $this->db->bind(':id', $this->id);
        $row = $this->db->single();
        
        if ($row) {
            $this->id = $row['DeTaiID'];
            $this->title = $row['TenDeTai'];
            $this->description = $row['MoTa'];
            $this->status = $row['TrangThai'];
            return true;
        }
        
        return false;
    }
    
    // Read all theses
    public function readAll() {
        $this->db->query("SELECT 
            DeTaiID as id, 
            TenDeTai as title, 
            MoTa as description, 
            TrangThai as status 
        FROM DeTai");
        
        return $this->db->resultSet();
    }
    
    // Create thesis
    public function create() {
        $this->db->query("INSERT INTO DeTai (TenDeTai, MoTa, TrangThai) VALUES (:title, :description, :status)");
        
        // Bind parameters
        $this->db->bind(':title', $this->title);
        $this->db->bind(':description', $this->description);
        $this->db->bind(':status', $this->status);
        
        // Execute
        if ($this->db->execute()) {
            // Sửa lỗi ở dòng 56 - Lấy ID mới thông qua phương thức lastInsertId của Database
            $this->id = $this->db->lastInsertId();
            return true;
        }
        
        return false;
    }
    
    // Update thesis
    public function update() {
        $this->db->query("UPDATE DeTai SET TenDeTai = :title, MoTa = :description, TrangThai = :status WHERE DeTaiID = :id");
        
        // Bind parameters
        $this->db->bind(':id', $this->id);
        $this->db->bind(':title', $this->title);
        $this->db->bind(':description', $this->description);
        $this->db->bind(':status', $this->status);
        
        // Execute
        return $this->db->execute();
    }
    
    // Delete thesis
    public function delete() {
        $this->db->query("DELETE FROM DeTai WHERE DeTaiID = :id");
        
        // Bind parameter
        $this->db->bind(':id', $this->id);
        
        // Execute
        return $this->db->execute();
    }
}
?>