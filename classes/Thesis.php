<?php
class Thesis {
    private $db;
    
    // Properties
    public $id;
    public $title;
    public $description;
    public $status;
    public $facultyId;
    public $createdAt;
    
    // Constructor
    public function __construct() {
        $this->db = new Database();
    }
    
    // Read single thesis
    public function read() {
        $this->db->query("
            SELECT dt.*, gv.HoTen as GiangVienTen, gv.MaGV
            FROM detai dt
            LEFT JOIN giangvien gv ON dt.GiangVienID = gv.GiangVienID
            WHERE dt.DeTaiID = :id
        ");
        $this->db->bind(':id', $this->id);
        $row = $this->db->single();
        
        if ($row) {
            $this->id = $row['DeTaiID'];
            $this->title = $row['TenDeTai'];
            $this->description = $row['MoTa'];
            $this->status = $row['TrangThai'];
            $this->facultyId = $row['GiangVienID'];
            $this->createdAt = $row['NgayTao'];
            return true;
        }
        
        return false;
    }
    
    // Read all theses
    public function readAll() {
        $this->db->query("
            SELECT 
                dt.DeTaiID as id, 
                dt.TenDeTai as title, 
                dt.MoTa as description, 
                dt.TrangThai as status,
                dt.GiangVienID as facultyId,
                dt.NgayTao as createdAt,
                gv.HoTen as facultyName,
                gv.MaGV as facultyCode
            FROM detai dt
            LEFT JOIN giangvien gv ON dt.GiangVienID = gv.GiangVienID
            ORDER BY dt.NgayTao DESC
        ");
        
        return $this->db->resultSet();
    }
    
    // Create thesis
    public function create() {
        $this->db->query("
            INSERT INTO detai (
                TenDeTai, 
                MoTa, 
                TrangThai,
                GiangVienID,
                NgayTao
            ) VALUES (
                :title, 
                :description, 
                :status,
                :facultyId,
                NOW()
            )
        ");
        
        // Bind parameters
        $this->db->bind(':title', $this->title);
        $this->db->bind(':description', $this->description);
        $this->db->bind(':status', $this->status);
        $this->db->bind(':facultyId', $this->facultyId);
        
        // Execute
        if ($this->db->execute()) {
            $this->id = $this->db->lastInsertId();
            return true;
        }
        
        return false;
    }
    
    // Update thesis
    public function update() {
        $this->db->query("
            UPDATE detai 
            SET 
                TenDeTai = :title, 
                MoTa = :description, 
                TrangThai = :status,
                GiangVienID = :facultyId
            WHERE DeTaiID = :id
        ");
        
        // Bind parameters
        $this->db->bind(':id', $this->id);
        $this->db->bind(':title', $this->title);
        $this->db->bind(':description', $this->description);
        $this->db->bind(':status', $this->status);
        $this->db->bind(':facultyId', $this->facultyId);
        
        // Execute
        return $this->db->execute();
    }
    
    // Delete thesis
    public function delete() {
        $this->db->query("DELETE FROM detai WHERE DeTaiID = :id");
        
        // Bind parameter
        $this->db->bind(':id', $this->id);
        
        // Execute
        return $this->db->execute();
    }

    // Get theses by faculty
    public function getThesesByFaculty($facultyId) {
        $this->db->query("
            SELECT 
                dt.*,
                COUNT(svgv.SinhVienID) as SoLuongSinhVien
            FROM detai dt
            LEFT JOIN sinhviengiangvienhuongdan svgv ON dt.DeTaiID = svgv.DeTaiID
            WHERE dt.GiangVienID = :facultyId
            GROUP BY dt.DeTaiID
            ORDER BY dt.NgayTao DESC
        ");
        
        $this->db->bind(':facultyId', $facultyId);
        return $this->db->resultSet();
    }

    // Get thesis with assigned students
    public function getThesisWithStudents($thesisId) {
        $this->db->query("
            SELECT 
                dt.*,
                gv.HoTen as GiangVienTen,
                gv.MaGV,
                sv.HoTen as SinhVienTen,
                sv.MaSV,
                svgv.TrangThaiHuongDan,
                svgv.TienDo
            FROM detai dt
            LEFT JOIN giangvien gv ON dt.GiangVienID = gv.GiangVienID
            LEFT JOIN sinhviengiangvienhuongdan svgv ON dt.DeTaiID = svgv.DeTaiID
            LEFT JOIN sinhvien sv ON svgv.SinhVienID = sv.SinhVienID
            WHERE dt.DeTaiID = :thesisId
        ");
        
        $this->db->bind(':thesisId', $thesisId);
        return $this->db->resultSet();
    }

    // Get available theses for student
    public function getAvailableTheses() {
        $this->db->query("
            SELECT 
                dt.*,
                gv.HoTen as GiangVienTen,
                gv.MaGV,
                COUNT(svgv.SinhVienID) as SoLuongSinhVien,
                gv.SoLuongSinhVienToiDa
            FROM detai dt
            LEFT JOIN giangvien gv ON dt.GiangVienID = gv.GiangVienID
            LEFT JOIN sinhviengiangvienhuongdan svgv ON dt.DeTaiID = svgv.DeTaiID
            WHERE dt.TrangThai = 'Đã phê duyệt'
            GROUP BY dt.DeTaiID
            HAVING SoLuongSinhVien < gv.SoLuongSinhVienToiDa
            ORDER BY dt.NgayTao DESC
        ");
        
        return $this->db->resultSet();
    }
}
?>