<?php
class Faculty {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    public function createFaculty($data) {
        $this->db->query("INSERT INTO GiangVien (MaGV, HoTen, HocVi, ChucVu, Khoa, ChuyenNganh, Email, SoDienThoai) VALUES (:maGV, :hoTen, :hocVi, :chucVu, :khoa, :chuyenNganh, :email, :soDienThoai)");
        $this->db->bind(':maGV', $data['maGV']);
        $this->db->bind(':hoTen', $data['hoTen']);
        $this->db->bind(':hocVi', $data['hocVi']);
        $this->db->bind(':chucVu', $data['chucVu']);
        $this->db->bind(':khoa', $data['khoa']);
        $this->db->bind(':chuyenNganh', $data['chuyenNganh']);
        $this->db->bind(':email', $data['email']);
        $this->db->bind(':soDienThoai', $data['soDienThoai']);
        
        return $this->db->execute();
    }

    public function getFaculty($id) {
        $this->db->query("SELECT * FROM GiangVien WHERE GiangVienID = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    public function updateFaculty($id, $data) {
        $this->db->query("UPDATE GiangVien SET MaGV = :maGV, HoTen = :hoTen, HocVi = :hocVi, ChucVu = :chucVu, Khoa = :khoa, ChuyenNganh = :chuyenNganh, Email = :email, SoDienThoai = :soDienThoai WHERE GiangVienID = :id");
        $this->db->bind(':maGV', $data['maGV']);
        $this->db->bind(':hoTen', $data['hoTen']);
        $this->db->bind(':hocVi', $data['hocVi']);
        $this->db->bind(':chucVu', $data['chucVu']);
        $this->db->bind(':khoa', $data['khoa']);
        $this->db->bind(':chuyenNganh', $data['chuyenNganh']);
        $this->db->bind(':email', $data['email']);
        $this->db->bind(':soDienThoai', $data['soDienThoai']);
        $this->db->bind(':id', $id);
        
        return $this->db->execute();
    }

    public function deleteFaculty($id) {
        $this->db->query("DELETE FROM GiangVien WHERE GiangVienID = :id");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    public function getAllFaculty() {
        $this->db->query("SELECT * FROM GiangVien");
        return $this->db->resultSet();
    }

    /**
     * Lấy danh sách sinh viên được phân công cho giảng viên
     */
    public function getAssignedStudents($facultyId) {
        $this->db->query("
            SELECT sv.*, dt.TenDeTai, dt.TrangThai
            FROM SinhVien sv
            JOIN SinhVienGiangVienHuongDan svgv ON sv.SinhVienID = svgv.SinhVienID
            LEFT JOIN DeTai dt ON svgv.DeTaiID = dt.DeTaiID
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
            $this->db->query("SELECT gv.*, u.Email, u.Username FROM GiangVien gv 
                            JOIN Users u ON gv.UserID = u.UserID 
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
            FROM SinhVienGiangVienHuongDan svgv
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
            // Kiểm tra xem cột nào tồn tại trong bảng DeTai
            $this->db->query("SHOW COLUMNS FROM DeTai LIKE 'GiangVienID'");
            $hasGiangVienID = $this->db->rowCount() > 0;
            
            // Chỉ kiểm tra id_giangvien nếu GiangVienID không tồn tại
            if (!$hasGiangVienID) {
                $this->db->query("SHOW COLUMNS FROM DeTai LIKE 'id_giangvien'");
                $hasIdGiangVien = $this->db->rowCount() > 0;
                
                // Cập nhật cấu trúc bảng nếu cần
                if ($hasIdGiangVien) {
                    // Đổi tên cột từ id_giangvien thành GiangVienID
                    $this->db->query("ALTER TABLE `DeTai` CHANGE COLUMN `id_giangvien` `GiangVienID` INT NOT NULL");
                    $this->db->execute();
                    $hasGiangVienID = true;
                }
            }
            
            // Luôn dùng GiangVienID
            $query = "
                SELECT dt.*, COUNT(svgv.SinhVienID) as SoLuongSinhVien
                FROM DeTai dt
                LEFT JOIN SinhVienGiangVienHuongDan svgv ON dt.DeTaiID = svgv.DeTaiID
                WHERE dt.GiangVienID = :facultyId
                GROUP BY dt.DeTaiID
                ORDER BY dt.NgayTao DESC
            ";
            
            $this->db->query($query);
            $this->db->bind(':facultyId', $facultyId);
            
            return $this->db->resultSet();
        } catch (PDOException $e) {
            $errorMessage = 'Error in getTheses: ' . $e->getMessage();
            if (isset($query)) {
                $errorMessage .= ' - Query: ' . $query;
            }
            error_log($errorMessage);
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
            $this->db->query("UPDATE GiangVien 
                            SET HoTen = :hoTen, 
                                HocVi = :hocVi,
                                ChuyenNganh = :chuyenNganh,
                                SoDienThoai = :soDienThoai,
                                DiaChi = :diaChi
                            WHERE GiangVienID = :facultyId");
            
            $this->db->bind(':hoTen', $data['HoTen']);
            $this->db->bind(':hocVi', $data['HocVi']);
            $this->db->bind(':chuyenNganh', $data['ChuyenNganh']);
            $this->db->bind(':soDienThoai', $data['SoDienThoai'] ?? null);
            $this->db->bind(':diaChi', $data['DiaChi'] ?? null);
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
            
            $this->db->query("INSERT INTO DeTai (TenDeTai, MoTa, TrangThai, NgayTao) 
                            VALUES (:tenDeTai, :moTa, :trangThai, NOW())");
            
            $this->db->bind(':tenDeTai', $data['TenDeTai']);
            $this->db->bind(':moTa', $data['MoTa']);
            $this->db->bind(':trangThai', $data['TrangThai'] ?? 'Chờ phê duyệt');
            
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
            $this->db->query("UPDATE SinhVienGiangVienHuongDan
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
            // Kiểm tra xem bảng LichGap có tồn tại không
            $this->db->query("SHOW TABLES LIKE 'LichGap'");
            $tableExists = $this->db->rowCount() > 0;
            
            if (!$tableExists) {
                // Tạo bảng LichGap nếu chưa tồn tại
                $this->db->query("CREATE TABLE IF NOT EXISTS `LichGap` (
                    `LichGapID` int(11) NOT NULL AUTO_INCREMENT,
                    `SinhVienID` int(11) NOT NULL,
                    `GiangVienID` int(11) NOT NULL,
                    `NgayGap` datetime NOT NULL,
                    `DiaDiem` varchar(255) DEFAULT NULL,
                    `NoiDung` text DEFAULT NULL,
                    `TrangThai` enum('đã lên lịch', 'đã xác nhận', 'đã hoàn thành', 'đã hủy') DEFAULT 'đã lên lịch',
                    `GhiChu` text DEFAULT NULL,
                    `NgayTao` timestamp NOT NULL DEFAULT current_timestamp(),
                    PRIMARY KEY (`LichGapID`),
                    KEY `SinhVienID` (`SinhVienID`),
                    KEY `GiangVienID` (`GiangVienID`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
                $this->db->execute();
            }
            
            $this->db->query("INSERT INTO LichGap (SinhVienID, GiangVienID, NgayGap, DiaDiem, NoiDung, TrangThai, GhiChu) 
                            VALUES (:sinhVienId, :giangVienId, :ngayGap, :diaDiem, :noiDung, :trangThai, :ghiChu)");
            
            $this->db->bind(':sinhVienId', $data['SinhVienID']);
            $this->db->bind(':giangVienId', $data['GiangVienID']);
            $this->db->bind(':ngayGap', $data['NgayGap']);
            $this->db->bind(':diaDiem', $data['DiaDiem'] ?? null);
            $this->db->bind(':noiDung', $data['NoiDung'] ?? null);
            $this->db->bind(':trangThai', $data['TrangThai'] ?? 'đã lên lịch');
            $this->db->bind(':ghiChu', $data['GhiChu'] ?? null);
            
            $this->db->execute();
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log('Error scheduling appointment: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Lấy danh sách lịch gặp của giảng viên
     * @param int $facultyId ID giảng viên
     * @param string $filter Bộ lọc (upcoming, past, all)
     * @return array Danh sách lịch gặp
     */
    public function getAppointments($facultyId, $filter = 'all') {
        try {
            // Kiểm tra xem bảng LichGap có tồn tại không
            $this->db->query("SHOW TABLES LIKE 'LichGap'");
            $tableExists = $this->db->rowCount() > 0;
            
            if (!$tableExists) {
                return [];
            }
            
            $where = "WHERE lg.GiangVienID = :facultyId";
            
            if ($filter === 'upcoming') {
                $where .= " AND lg.NgayGap > NOW()";
            } elseif ($filter === 'past') {
                $where .= " AND lg.NgayGap < NOW()";
            }
            
            $this->db->query("SELECT lg.*, sv.HoTen as TenSinhVien, sv.MaSV
                           FROM LichGap lg
                           JOIN SinhVien sv ON lg.SinhVienID = sv.SinhVienID
                           $where
                           ORDER BY lg.NgayGap ASC");
            
            $this->db->bind(':facultyId', $facultyId);
            
            return $this->db->resultSet();
        } catch (PDOException $e) {
            error_log('Error fetching appointments: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Gửi thông báo
     * @param array $data Dữ liệu thông báo
     * @return int|bool ID của thông báo mới hoặc false nếu có lỗi
     */
    public function sendNotification($data) {
        try {
            // Kiểm tra xem bảng ThongBao có tồn tại không
            $this->db->query("SHOW TABLES LIKE 'ThongBao'");
            $tableExists = $this->db->rowCount() > 0;
            
            if (!$tableExists) {
                // Tạo bảng ThongBao nếu chưa tồn tại
                $this->db->query("CREATE TABLE IF NOT EXISTS `ThongBao` (
                    `ThongBaoID` int(11) NOT NULL AUTO_INCREMENT,
                    `UserID` int(11) NOT NULL,
                    `TieuDe` varchar(255) NOT NULL,
                    `NoiDung` text NOT NULL,
                    `LoaiThongBao` varchar(50) DEFAULT NULL,
                    `DaDoc` tinyint(1) NOT NULL DEFAULT 0,
                    `NgayTao` timestamp NOT NULL DEFAULT current_timestamp(),
                    PRIMARY KEY (`ThongBaoID`),
                    KEY `UserID` (`UserID`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
                $this->db->execute();
            }
            
            $this->db->query("INSERT INTO ThongBao (UserID, TieuDe, NoiDung, LoaiThongBao) 
                            VALUES (:userId, :tieuDe, :noiDung, :loaiThongBao)");
            
            $this->db->bind(':userId', $data['UserID']);
            $this->db->bind(':tieuDe', $data['TieuDe']);
            $this->db->bind(':noiDung', $data['NoiDung']);
            $this->db->bind(':loaiThongBao', $data['LoaiThongBao'] ?? null);
            
            $this->db->execute();
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log('Error sending notification: ' . $e->getMessage());
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