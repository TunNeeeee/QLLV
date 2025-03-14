<?php
class Student {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function create($data) {
        $this->db->query("INSERT INTO SinhVien (UserID, MaSV, HoTen, NgaySinh, GioiTinh, Khoa, NganhHoc, NienKhoa) VALUES (:userId, :maSv, :hoTen, :ngaySinh, :gioiTinh, :khoa, :nganhHoc, :nienKhoa)");
        $this->db->bind(':userId', $data['userId']);
        $this->db->bind(':maSv', $data['maSv']);
        $this->db->bind(':hoTen', $data['hoTen']);
        $this->db->bind(':ngaySinh', $data['ngaySinh']);
        $this->db->bind(':gioiTinh', $data['gioiTinh']);
        $this->db->bind(':khoa', $data['khoa']);
        $this->db->bind(':nganhHoc', $data['nganhHoc']);
        $this->db->bind(':nienKhoa', $data['nienKhoa']);
        return $this->db->execute();
    }

    public function read($id) {
        $this->db->query("SELECT * FROM SinhVien WHERE SinhVienID = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    public function update($data) {
        $this->db->query("UPDATE SinhVien SET MaSV = :maSv, HoTen = :hoTen, NgaySinh = :ngaySinh, GioiTinh = :gioiTinh, Khoa = :khoa, NganhHoc = :nganhHoc, NienKhoa = :nienKhoa WHERE SinhVienID = :id");
        $this->db->bind(':id', $data['id']);
        $this->db->bind(':maSv', $data['maSv']);
        $this->db->bind(':hoTen', $data['hoTen']);
        $this->db->bind(':ngaySinh', $data['ngaySinh']);
        $this->db->bind(':gioiTinh', $data['gioiTinh']);
        $this->db->bind(':khoa', $data['khoa']);
        $this->db->bind(':nganhHoc', $data['nganhHoc']);
        $this->db->bind(':nienKhoa', $data['nienKhoa']);
        return $this->db->execute();
    }

    public function delete($id) {
        $this->db->query("DELETE FROM SinhVien WHERE SinhVienID = :id");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    public function getAllStudents() {
        $this->db->query("SELECT * FROM SinhVien");
        return $this->db->resultSet();
    }

    // Phương thức lấy thông tin giảng viên hướng dẫn của sinh viên
    public function getAdvisor($studentId) {
        $this->db->query("SELECT gv.* 
                         FROM GiangVien gv 
                         JOIN SinhVienGiangVienHuongDan svgv ON gv.GiangVienID = svgv.GiangVienID 
                         WHERE svgv.SinhVienID = :studentId 
                         AND svgv.TrangThai = 'active'");
        $this->db->bind(':studentId', $studentId);
        
        return $this->db->single();
    }
    
    // Phương thức lấy thông tin sinh viên
    public function getStudentInfo($studentId) {
        $this->db->query("SELECT * FROM SinhVien WHERE SinhVienID = :studentId");
        $this->db->bind(':studentId', $studentId);
        
        return $this->db->single();
    }
    
    // Phương thức lấy danh sách đề tài của sinh viên
    public function getTheses($studentId) {
        $this->db->query("SELECT 
                          dt.DeTaiID, 
                          dt.TenDeTai, 
                          dt.MoTa, 
                          dt.TrangThai,
                          svgv.NgayBatDau,
                          svgv.NgayKetThucDuKien
                        FROM DeTai dt
                        JOIN SinhVienGiangVienHuongDan svgv ON dt.DeTaiID = svgv.DeTaiID
                        WHERE svgv.SinhVienID = :studentId");
        $this->db->bind(':studentId', $studentId);
        
        return $this->db->resultSet();
    }
    
    // Phương thức cập nhật thông tin sinh viên
    public function updateProfile($studentId, $data) {
        $this->db->query("UPDATE SinhVien 
                         SET HoTen = :hoTen, 
                             MaSV = :maSV, 
                             Khoa = :khoa, 
                             NganhHoc = :nganhHoc, 
                             NienKhoa = :nienKhoa 
                         WHERE SinhVienID = :studentId");
        
        $this->db->bind(':hoTen', $data['HoTen']);
        $this->db->bind(':maSV', $data['MaSV']);
        $this->db->bind(':khoa', $data['Khoa']);
        $this->db->bind(':nganhHoc', $data['NganhHoc']);
        $this->db->bind(':nienKhoa', $data['NienKhoa']);
        $this->db->bind(':studentId', $studentId);
        
        return $this->db->execute();
    }
}
?>