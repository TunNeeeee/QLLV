<?php
class Student {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function create($data) {
        $this->db->query("INSERT INTO sinhvien (
            UserID, 
            MaSV, 
            HoTen, 
            NgaySinh, 
            GioiTinh, 
            Khoa, 
            NganhHoc, 
            NienKhoa,
            SoDienThoai,
            DiaChi,
            Avatar
        ) VALUES (
            :userId, 
            :maSv, 
            :hoTen, 
            :ngaySinh, 
            :gioiTinh, 
            :khoa, 
            :nganhHoc, 
            :nienKhoa,
            :soDienThoai,
            :diaChi,
            :avatar
        )");
        
        $this->db->bind(':userId', $data['userId']);
        $this->db->bind(':maSv', $data['maSv']);
        $this->db->bind(':hoTen', $data['hoTen']);
        $this->db->bind(':ngaySinh', $data['ngaySinh']);
        $this->db->bind(':gioiTinh', $data['gioiTinh']);
        $this->db->bind(':khoa', $data['khoa']);
        $this->db->bind(':nganhHoc', $data['nganhHoc']);
        $this->db->bind(':nienKhoa', $data['nienKhoa']);
        $this->db->bind(':soDienThoai', $data['soDienThoai'] ?? null);
        $this->db->bind(':diaChi', $data['diaChi'] ?? null);
        $this->db->bind(':avatar', $data['avatar'] ?? null);
        
        return $this->db->execute();
    }

    public function read($id) {
        $this->db->query("SELECT * FROM sinhvien WHERE SinhVienID = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    public function update($data) {
        $this->db->query("UPDATE sinhvien SET 
            MaSV = :maSv, 
            HoTen = :hoTen, 
            NgaySinh = :ngaySinh, 
            GioiTinh = :gioiTinh, 
            Khoa = :khoa, 
            NganhHoc = :nganhHoc, 
            NienKhoa = :nienKhoa,
            SoDienThoai = :soDienThoai,
            DiaChi = :diaChi,
            Avatar = :avatar
            WHERE SinhVienID = :id");
            
        $this->db->bind(':id', $data['id']);
        $this->db->bind(':maSv', $data['maSv']);
        $this->db->bind(':hoTen', $data['hoTen']);
        $this->db->bind(':ngaySinh', $data['ngaySinh']);
        $this->db->bind(':gioiTinh', $data['gioiTinh']);
        $this->db->bind(':khoa', $data['khoa']);
        $this->db->bind(':nganhHoc', $data['nganhHoc']);
        $this->db->bind(':nienKhoa', $data['nienKhoa']);
        $this->db->bind(':soDienThoai', $data['soDienThoai'] ?? null);
        $this->db->bind(':diaChi', $data['diaChi'] ?? null);
        $this->db->bind(':avatar', $data['avatar'] ?? null);
        
        return $this->db->execute();
    }

    public function delete($id) {
        $this->db->query("DELETE FROM sinhvien WHERE SinhVienID = :id");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    public function getAllStudents() {
        $this->db->query("SELECT * FROM sinhvien");
        return $this->db->resultSet();
    }

    public function getAdvisor($studentId) {
        $this->db->query("SELECT gv.* 
                         FROM giangvien gv 
                         JOIN sinhviengiangvienhuongdan svgv ON gv.GiangVienID = svgv.GiangVienID 
                         WHERE svgv.SinhVienID = :studentId 
                         AND svgv.TrangThai = 'đang hướng dẫn'");
        $this->db->bind(':studentId', $studentId);
        
        return $this->db->single();
    }
    
    public function getStudentInfo($studentId) {
        $this->db->query("SELECT * FROM sinhvien WHERE SinhVienID = :studentId");
        $this->db->bind(':studentId', $studentId);
        
        return $this->db->single();
    }
    
    public function getTheses($studentId) {
        $this->db->query("SELECT 
                          dt.DeTaiID, 
                          dt.TenDeTai, 
                          dt.MoTa, 
                          dt.TrangThai,
                          dt.LinhVuc,
                          svgv.NgayBatDau,
                          svgv.NgayKetThucDuKien,
                          svgv.TrangThai as TrangThaiHuongDan,
                          svgv.TienDo
                        FROM detai dt
                        JOIN sinhviengiangvienhuongdan svgv ON dt.DeTaiID = svgv.DeTaiID
                        WHERE svgv.SinhVienID = :studentId");
        $this->db->bind(':studentId', $studentId);
        
        return $this->db->resultSet();
    }
    
    public function updateProfile($studentId, $data) {
        $this->db->query("UPDATE sinhvien 
                         SET HoTen = :hoTen, 
                             MaSV = :maSV, 
                             Khoa = :khoa, 
                             NganhHoc = :nganhHoc, 
                             NienKhoa = :nienKhoa,
                             SoDienThoai = :soDienThoai,
                             DiaChi = :diaChi,
                             Avatar = :avatar
                         WHERE SinhVienID = :studentId");
        
        $this->db->bind(':hoTen', $data['HoTen']);
        $this->db->bind(':maSV', $data['MaSV']);
        $this->db->bind(':khoa', $data['Khoa']);
        $this->db->bind(':nganhHoc', $data['NganhHoc']);
        $this->db->bind(':nienKhoa', $data['NienKhoa']);
        $this->db->bind(':soDienThoai', $data['SoDienThoai'] ?? null);
        $this->db->bind(':diaChi', $data['DiaChi'] ?? null);
        $this->db->bind(':avatar', $data['Avatar'] ?? null);
        $this->db->bind(':studentId', $studentId);
        
        return $this->db->execute();
    }

    public function getAppointments($studentId) {
        $this->db->query("SELECT lg.*, gv.HoTen AS TenGiangVien, gv.HocVi 
                         FROM lichgap lg
                         JOIN giangvien gv ON lg.GiangVienID = gv.GiangVienID
                         WHERE lg.SinhVienID = :studentId
                         ORDER BY lg.NgayGap DESC");
        $this->db->bind(':studentId', $studentId);
        
        return $this->db->resultSet();
    }

    public function getUpcomingAppointments($studentId) {
        $this->db->query("SELECT lg.*, gv.HoTen AS TenGiangVien, gv.HocVi 
                         FROM lichgap lg
                         JOIN giangvien gv ON lg.GiangVienID = gv.GiangVienID
                         WHERE lg.SinhVienID = :studentId 
                         AND lg.NgayGap >= CURDATE()
                         ORDER BY lg.NgayGap ASC");
        $this->db->bind(':studentId', $studentId);
        
        return $this->db->resultSet();
    }

    public function getPastAppointments($studentId) {
        $this->db->query("SELECT lg.*, gv.HoTen AS TenGiangVien, gv.HocVi 
                         FROM lichgap lg
                         JOIN giangvien gv ON lg.GiangVienID = gv.GiangVienID
                         WHERE lg.SinhVienID = :studentId 
                         AND lg.NgayGap < CURDATE()
                         ORDER BY lg.NgayGap DESC");
        $this->db->bind(':studentId', $studentId);
        
        return $this->db->resultSet();
    }
}
?>