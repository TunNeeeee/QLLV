<?php
class Faculty {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    public function createFaculty($data) {
        $this->db->query("INSERT INTO giangvien (MaGV, HoTen, HocVi, ChucVu, Khoa, ChuyenNganh, Email, SoDienThoai, LinhVucHuongDan, SoLuongSinhVienToiDa) VALUES (:maGV, :hoTen, :hocVi, :chucVu, :khoa, :chuyenNganh, :email, :soDienThoai, :linhVucHuongDan, :soLuongSinhVienToiDa)");
        $this->db->bind(':maGV', $data['maGV']);
        $this->db->bind(':hoTen', $data['hoTen']);
        $this->db->bind(':hocVi', $data['hocVi']);
        $this->db->bind(':chucVu', $data['chucVu']);
        $this->db->bind(':khoa', $data['khoa']);
        $this->db->bind(':chuyenNganh', $data['chuyenNganh']);
        $this->db->bind(':email', $data['email']);
        $this->db->bind(':soDienThoai', $data['soDienThoai']);
        $this->db->bind(':linhVucHuongDan', $data['linhVucHuongDan']);
        $this->db->bind(':soLuongSinhVienToiDa', $data['soLuongSinhVienToiDa']);
        
        return $this->db->execute();
    }

    public function getFaculty($id) {
        $this->db->query("SELECT * FROM giangvien WHERE GiangVienID = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    public function updateFaculty($id, $data) {
        $this->db->query("UPDATE giangvien SET MaGV = :maGV, HoTen = :hoTen, HocVi = :hocVi, ChucVu = :chucVu, Khoa = :khoa, ChuyenNganh = :chuyenNganh, Email = :email, SoDienThoai = :soDienThoai, LinhVucHuongDan = :linhVucHuongDan, SoLuongSinhVienToiDa = :soLuongSinhVienToiDa WHERE GiangVienID = :id");
        $this->db->bind(':maGV', $data['maGV']);
        $this->db->bind(':hoTen', $data['hoTen']);
        $this->db->bind(':hocVi', $data['hocVi']);
        $this->db->bind(':chucVu', $data['chucVu']);
        $this->db->bind(':khoa', $data['khoa']);
        $this->db->bind(':chuyenNganh', $data['chuyenNganh']);
        $this->db->bind(':email', $data['email']);
        $this->db->bind(':soDienThoai', $data['soDienThoai']);
        $this->db->bind(':linhVucHuongDan', $data['linhVucHuongDan']);
        $this->db->bind(':soLuongSinhVienToiDa', $data['soLuongSinhVienToiDa']);
        $this->db->bind(':id', $id);
        
        return $this->db->execute();
    }

    public function deleteFaculty($id) {
        $this->db->query("DELETE FROM giangvien WHERE GiangVienID = :id");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    public function getAllFaculty() {
        $this->db->query("SELECT * FROM giangvien");
        return $this->db->resultSet();
    }

    /**
     * Lấy danh sách sinh viên được phân công cho giảng viên
     */
    public function getAssignedStudents($facultyId) {
        $this->db->query("
            SELECT sv.*, dt.TenDeTai, dt.TrangThai, svgv.TrangThaiHuongDan, svgv.TienDo
            FROM sinhvien sv
            JOIN sinhviengiangvienhuongdan svgv ON sv.SinhVienID = svgv.SinhVienID
            LEFT JOIN detai dt ON svgv.DeTaiID = dt.DeTaiID
            WHERE svgv.GiangVienID = :facultyId
            ORDER BY sv.HoTen ASC
        ");
        $this->db->bind(':facultyId', $facultyId);
        return $this->db->resultSet();
    }
    
    /**
     * Lấy thông tin chi tiết của giảng viên
     * @param int $facultyId ID của giảng viên
     * @return array|bool Thông tin giảng viên hoặc false nếu không tìm thấy
     */
    public function getFacultyDetails($facultyId) {
        try {
            $this->db->query("SELECT gv.*, u.Email, u.Username FROM giangvien gv 
                            JOIN users u ON gv.UserID = u.UserID 
                            WHERE gv.GiangVienID = :facultyId");
            $this->db->bind(':facultyId', $facultyId);
            
            return $this->db->single();
        } catch (PDOException $e) {
            error_log('Error fetching faculty details: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Đếm số lượng sinh viên được phân công cho giảng viên
     */
    public function countAssignedStudents($facultyId) {
        $this->db->query("
            SELECT COUNT(*) as total
            FROM sinhviengiangvienhuongdan svgv
            WHERE svgv.GiangVienID = :facultyId
        ");
        $this->db->bind(':facultyId', $facultyId);
        $result = $this->db->single();
        return $result ? $result['total'] : 0;
    }
    
    /**
     * Lấy danh sách đề tài do giảng viên hướng dẫn
     * @param int $facultyId ID của giảng viên
     * @return array Danh sách đề tài
     */
    public function getTheses($facultyId) {
        try {
            $query = "
                SELECT dt.*, COUNT(svgv.SinhVienID) as SoLuongSinhVien
                FROM detai dt
                LEFT JOIN sinhviengiangvienhuongdan svgv ON dt.DeTaiID = svgv.DeTaiID
                WHERE dt.GiangVienID = :facultyId
                GROUP BY dt.DeTaiID
                ORDER BY dt.NgayTao DESC
            ";
            
            $this->db->query($query);
            $this->db->bind(':facultyId', $facultyId);
            
            return $this->db->resultSet();
        } catch (PDOException $e) {
            error_log('Error in getTheses: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Cập nhật thông tin giảng viên
     * @param int $facultyId ID của giảng viên
     * @param array $data Dữ liệu cập nhật
     * @return bool Kết quả cập nhật
     */
    public function updateProfile($facultyId, $data) {
        try {
            $this->db->query("UPDATE giangvien 
                            SET HoTen = :hoTen, 
                                HocVi = :hocVi,
                                ChuyenNganh = :chuyenNganh,
                                SoDienThoai = :soDienThoai,
                                DiaChi = :diaChi,
                                LinhVucHuongDan = :linhVucHuongDan,
                                SoLuongSinhVienToiDa = :soLuongSinhVienToiDa
                            WHERE GiangVienID = :facultyId");
            
            $this->db->bind(':hoTen', $data['HoTen']);
            $this->db->bind(':hocVi', $data['HocVi']);
            $this->db->bind(':chuyenNganh', $data['ChuyenNganh']);
            $this->db->bind(':soDienThoai', $data['SoDienThoai'] ?? null);
            $this->db->bind(':diaChi', $data['DiaChi'] ?? null);
            $this->db->bind(':linhVucHuongDan', $data['LinhVucHuongDan'] ?? null);
            $this->db->bind(':soLuongSinhVienToiDa', $data['SoLuongSinhVienToiDa'] ?? null);
            $this->db->bind(':facultyId', $facultyId);
            
            return $this->db->execute();
        } catch (PDOException $e) {
            error_log('Error updating faculty profile: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Thêm đề tài mới
     * @param array $data Dữ liệu đề tài
     * @return int|bool ID của đề tài mới hoặc false nếu có lỗi
     */
    public function addThesis($data) {
        try {
            $this->db->beginTransaction();
            
            $this->db->query("INSERT INTO detai (TenDeTai, MoTa, TrangThai, NgayTao, GiangVienID) 
                            VALUES (:tenDeTai, :moTa, :trangThai, NOW(), :giangVienID)");
            
            $this->db->bind(':tenDeTai', $data['TenDeTai']);
            $this->db->bind(':moTa', $data['MoTa']);
            $this->db->bind(':trangThai', $data['TrangThai'] ?? 'Chờ phê duyệt');
            $this->db->bind(':giangVienID', $data['GiangVienID']);
            
            $this->db->execute();
            $thesisId = $this->db->lastInsertId();
            
            $this->db->commit();
            return $thesisId;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log('Error adding thesis: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cập nhật tiến độ luận văn
     * @param int $thesisId ID đề tài
     * @param int $studentId ID sinh viên
     * @param int $progress Tiến độ (0-100)
     * @return bool Kết quả cập nhật
     */
    public function updateThesisProgress($thesisId, $studentId, $progress) {
        try {
            $this->db->query("UPDATE sinhviengiangvienhuongdan
                            SET TienDo = :tienDo
                            WHERE DeTaiID = :detaiId AND SinhVienID = :sinhvienId");
            
            $this->db->bind(':tienDo', $progress);
            $this->db->bind(':detaiId', $thesisId);
            $this->db->bind(':sinhvienId', $studentId);
            
            return $this->db->execute();
        } catch (PDOException $e) {
            error_log('Error updating thesis progress: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Lên lịch gặp mới
     * @param array $data Dữ liệu lịch gặp
     * @return int|bool ID của lịch gặp mới hoặc false nếu có lỗi
     */
    public function scheduleAppointment($data) {
        try {
            $this->db->query("INSERT INTO lichgap (SinhVienID, GiangVienID, TieuDe, NoiDung, DiaDiem, NgayGap, TrangThai) 
                            VALUES (:sinhVienID, :giangVienID, :tieuDe, :noiDung, :diaDiem, :ngayGap, :trangThai)");
            
            $this->db->bind(':sinhVienID', $data['SinhVienID']);
            $this->db->bind(':giangVienID', $data['GiangVienID']);
            $this->db->bind(':tieuDe', $data['TieuDe']);
            $this->db->bind(':noiDung', $data['NoiDung']);
            $this->db->bind(':diaDiem', $data['DiaDiem']);
            $this->db->bind(':ngayGap', $data['NgayGap']);
            $this->db->bind(':trangThai', $data['TrangThai'] ?? 'Chờ xác nhận');
            
            return $this->db->execute();
        } catch (PDOException $e) {
            error_log('Error scheduling appointment: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Lấy danh sách lịch gặp của giảng viên
     * @param int $facultyId ID giảng viên
     * @return array Danh sách lịch gặp
     */
    public function getAppointments($facultyId) {
        try {
            $this->db->query("
                SELECT lg.*, sv.HoTen as SinhVienTen
                FROM lichgap lg
                JOIN sinhvien sv ON lg.SinhVienID = sv.SinhVienID
                WHERE lg.GiangVienID = :facultyId
                ORDER BY lg.NgayGap DESC
            ");
            $this->db->bind(':facultyId', $facultyId);
            return $this->db->resultSet();
        } catch (PDOException $e) {
            error_log('Error getting appointments: ' . $e->getMessage());
            return [];
        }
    }

    public function getUpcomingAppointments($facultyId) {
        try {
            $this->db->query("
                SELECT lg.*, sv.HoTen as SinhVienTen
                FROM lichgap lg
                JOIN sinhvien sv ON lg.SinhVienID = sv.SinhVienID
                WHERE lg.GiangVienID = :facultyId
                AND lg.NgayGap >= NOW()
                AND lg.TrangThai = 'Đã xác nhận'
                ORDER BY lg.NgayGap ASC
            ");
            $this->db->bind(':facultyId', $facultyId);
            return $this->db->resultSet();
        } catch (PDOException $e) {
            error_log('Error getting upcoming appointments: ' . $e->getMessage());
            return [];
        }
    }

    public function getPastAppointments($facultyId) {
        try {
            $this->db->query("
                SELECT lg.*, sv.HoTen as SinhVienTen
                FROM lichgap lg
                JOIN sinhvien sv ON lg.SinhVienID = sv.SinhVienID
                WHERE lg.GiangVienID = :facultyId
                AND lg.NgayGap < NOW()
                ORDER BY lg.NgayGap DESC
            ");
            $this->db->bind(':facultyId', $facultyId);
            return $this->db->resultSet();
        } catch (PDOException $e) {
            error_log('Error getting past appointments: ' . $e->getMessage());
            return [];
        }
    }

    public function updateAppointmentStatus($appointmentId, $status) {
        try {
            $this->db->query("
                UPDATE lichgap 
                SET TrangThai = :trangThai
                WHERE LichGapID = :lichGapID
            ");
            $this->db->bind(':trangThai', $status);
            $this->db->bind(':lichGapID', $appointmentId);
            return $this->db->execute();
        } catch (PDOException $e) {
            error_log('Error updating appointment status: ' . $e->getMessage());
            return false;
        }
    }

    public function getAppointmentDetails($appointmentId) {
        try {
            $this->db->query("
                SELECT lg.*, sv.HoTen as SinhVienTen, sv.MaSV
                FROM lichgap lg
                JOIN sinhvien sv ON lg.SinhVienID = sv.SinhVienID
                WHERE lg.LichGapID = :lichGapID
            ");
            $this->db->bind(':lichGapID', $appointmentId);
            return $this->db->single();
        } catch (PDOException $e) {
            error_log('Error getting appointment details: ' . $e->getMessage());
            return false;
        }
    }
}

// fix-column-names.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$db = new Database();

// Kiểm tra và cập nhật cột TrangThai trong bảng LichGap
try {
    $db->query("ALTER TABLE `LichGap` 
                MODIFY COLUMN `TrangThai` ENUM('đã lên lịch', 'đã xác nhận', 'đã hoàn thành', 'đã hủy') 
                DEFAULT 'đã lên lịch'");
    $db->execute();
    echo "Đã cập nhật cột TrangThai trong bảng LichGap.<br>";

} catch (PDOException $e) {
    echo "Lỗi: " . $e->getMessage();
}

// Cập nhật giá trị dữ liệu
try {
    // Cập nhật từ trạng thái viết hoa sang viết thường
    $db->query("UPDATE `LichGap` SET `TrangThai` = 'đã lên lịch' WHERE `TrangThai` = 'Đã đặt' OR `TrangThai` IS NULL");
    $db->execute();
    
    $db->query("UPDATE `LichGap` SET `TrangThai` = 'đã xác nhận' WHERE `TrangThai` = 'Đã chấp nhận'");
    $db->execute();
    
    $db->query("UPDATE `LichGap` SET `TrangThai` = 'đã hoàn thành' WHERE `TrangThai` = 'Đã hoàn thành'");
    $db->execute();
    
    $db->query("UPDATE `LichGap` SET `TrangThai` = 'đã hủy' WHERE `TrangThai` = 'Đã hủy'");
    $db->execute();
    
    echo "Đã cập nhật giá trị TrangThai trong dữ liệu.<br>";
    
} catch (PDOException $e) {
    echo "Lỗi khi cập nhật giá trị TrangThai: " . $e->getMessage();
}
?>