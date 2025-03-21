<?php
class Progress {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    public function createProgress($data) {
        $this->db->query("
            INSERT INTO nhatkyhuongdan (
                HuongDanID, 
                NgayGioHuongDan, 
                NoiDung, 
                NhanXet, 
                TienDo, 
                TaiLieuDinhKem, 
                NguoiTao,
                NgayTao
            ) VALUES (
                :huongDanId, 
                :ngayGioHuongDan, 
                :noiDung, 
                :nhanXet, 
                :tienDo, 
                :taiLieuDinhKem, 
                :nguoiTao,
                NOW()
            )
        ");
        $this->db->bind(':huongDanId', $data['huongDanId']);
        $this->db->bind(':ngayGioHuongDan', $data['ngayGioHuongDan']);
        $this->db->bind(':noiDung', $data['noiDung']);
        $this->db->bind(':nhanXet', $data['nhanXet']);
        $this->db->bind(':tienDo', $data['tienDo']);
        $this->db->bind(':taiLieuDinhKem', $data['taiLieuDinhKem']);
        $this->db->bind(':nguoiTao', $data['nguoiTao']);
        
        return $this->db->execute();
    }

    public function getProgressById($id) {
        $this->db->query("
            SELECT nk.*, 
                   sv.HoTen as SinhVienTen,
                   sv.MaSV,
                   gv.HoTen as GiangVienTen,
                   gv.MaGV
            FROM nhatkyhuongdan nk
            JOIN sinhviengiangvienhuongdan svgv ON nk.HuongDanID = svgv.HuongDanID
            JOIN sinhvien sv ON svgv.SinhVienID = sv.SinhVienID
            JOIN giangvien gv ON svgv.GiangVienID = gv.GiangVienID
            WHERE nk.NhatKyID = :id
        ");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    public function updateProgress($id, $data) {
        $this->db->query("
            UPDATE nhatkyhuongdan 
            SET 
                NgayGioHuongDan = :ngayGioHuongDan, 
                NoiDung = :noiDung, 
                NhanXet = :nhanXet, 
                TienDo = :tienDo, 
                TaiLieuDinhKem = :taiLieuDinhKem,
                NguoiTao = :nguoiTao
            WHERE NhatKyID = :id
        ");
        $this->db->bind(':ngayGioHuongDan', $data['ngayGioHuongDan']);
        $this->db->bind(':noiDung', $data['noiDung']);
        $this->db->bind(':nhanXet', $data['nhanXet']);
        $this->db->bind(':tienDo', $data['tienDo']);
        $this->db->bind(':taiLieuDinhKem', $data['taiLieuDinhKem']);
        $this->db->bind(':nguoiTao', $data['nguoiTao']);
        $this->db->bind(':id', $id);
        
        return $this->db->execute();
    }

    public function deleteProgress($id) {
        $this->db->query("DELETE FROM nhatkyhuongdan WHERE NhatKyID = :id");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    public function getAllProgressByHuongDanId($huongDanId) {
        $this->db->query("
            SELECT nk.*, 
                   sv.HoTen as SinhVienTen,
                   sv.MaSV,
                   gv.HoTen as GiangVienTen,
                   gv.MaGV
            FROM nhatkyhuongdan nk
            JOIN sinhviengiangvienhuongdan svgv ON nk.HuongDanID = svgv.HuongDanID
            JOIN sinhvien sv ON svgv.SinhVienID = sv.SinhVienID
            JOIN giangvien gv ON svgv.GiangVienID = gv.GiangVienID
            WHERE nk.HuongDanID = :huongDanId 
            ORDER BY nk.NgayGioHuongDan DESC
        ");
        $this->db->bind(':huongDanId', $huongDanId);
        return $this->db->resultSet();
    }

    public function getProgressByStudentId($studentId) {
        $this->db->query("
            SELECT nk.*, 
                   sv.HoTen as SinhVienTen,
                   sv.MaSV,
                   gv.HoTen as GiangVienTen,
                   gv.MaGV,
                   dt.TenDeTai
            FROM nhatkyhuongdan nk
            JOIN sinhviengiangvienhuongdan svgv ON nk.HuongDanID = svgv.HuongDanID
            JOIN sinhvien sv ON svgv.SinhVienID = sv.SinhVienID
            JOIN giangvien gv ON svgv.GiangVienID = gv.GiangVienID
            JOIN detai dt ON svgv.DeTaiID = dt.DeTaiID
            WHERE sv.SinhVienID = :studentId
            ORDER BY nk.NgayGioHuongDan DESC
        ");
        $this->db->bind(':studentId', $studentId);
        return $this->db->resultSet();
    }

    public function getProgressByFacultyId($facultyId) {
        $this->db->query("
            SELECT nk.*, 
                   sv.HoTen as SinhVienTen,
                   sv.MaSV,
                   gv.HoTen as GiangVienTen,
                   gv.MaGV,
                   dt.TenDeTai
            FROM nhatkyhuongdan nk
            JOIN sinhviengiangvienhuongdan svgv ON nk.HuongDanID = svgv.HuongDanID
            JOIN sinhvien sv ON svgv.SinhVienID = sv.SinhVienID
            JOIN giangvien gv ON svgv.GiangVienID = gv.GiangVienID
            JOIN detai dt ON svgv.DeTaiID = dt.DeTaiID
            WHERE gv.GiangVienID = :facultyId
            ORDER BY nk.NgayGioHuongDan DESC
        ");
        $this->db->bind(':facultyId', $facultyId);
        return $this->db->resultSet();
    }

    public function getLatestProgress($huongDanId) {
        $this->db->query("
            SELECT nk.*, 
                   sv.HoTen as SinhVienTen,
                   sv.MaSV,
                   gv.HoTen as GiangVienTen,
                   gv.MaGV
            FROM nhatkyhuongdan nk
            JOIN sinhviengiangvienhuongdan svgv ON nk.HuongDanID = svgv.HuongDanID
            JOIN sinhvien sv ON svgv.SinhVienID = sv.SinhVienID
            JOIN giangvien gv ON svgv.GiangVienID = gv.GiangVienID
            WHERE nk.HuongDanID = :huongDanId
            ORDER BY nk.NgayGioHuongDan DESC
            LIMIT 1
        ");
        $this->db->bind(':huongDanId', $huongDanId);
        return $this->db->single();
    }
}
?>