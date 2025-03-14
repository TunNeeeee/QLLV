<?php
class Progress {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    public function createProgress($data) {
        $this->db->query("INSERT INTO NhatKyHuongDan (HuongDanID, NgayGioHuongDan, NoiDung, NhanXet, TienDo, TaiLieuDinhKem, NguoiTao) 
                          VALUES (:huongDanId, :ngayGioHuongDan, :noiDung, :nhanXet, :tienDo, :taiLieuDinhKem, :nguoiTao)");
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
        $this->db->query("SELECT * FROM NhatKyHuongDan WHERE NhatKyID = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    public function updateProgress($id, $data) {
        $this->db->query("UPDATE NhatKyHuongDan SET 
                          NgayGioHuongDan = :ngayGioHuongDan, 
                          NoiDung = :noiDung, 
                          NhanXet = :nhanXet, 
                          TienDo = :tienDo, 
                          TaiLieuDinhKem = :taiLieuDinhKem 
                          WHERE NhatKyID = :id");
        $this->db->bind(':ngayGioHuongDan', $data['ngayGioHuongDan']);
        $this->db->bind(':noiDung', $data['noiDung']);
        $this->db->bind(':nhanXet', $data['nhanXet']);
        $this->db->bind(':tienDo', $data['tienDo']);
        $this->db->bind(':taiLieuDinhKem', $data['taiLieuDinhKem']);
        $this->db->bind(':id', $id);
        
        return $this->db->execute();
    }

    public function deleteProgress($id) {
        $this->db->query("DELETE FROM NhatKyHuongDan WHERE NhatKyID = :id");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    public function getAllProgressByHuongDanId($huongDanId) {
        $this->db->query("SELECT * FROM NhatKyHuongDan WHERE HuongDanID = :huongDanId ORDER BY NgayGioHuongDan DESC");
        $this->db->bind(':huongDanId', $huongDanId);
        return $this->db->resultSet();
    }
}
?>