<?php
require_once dirname(__DIR__) . '/config/database.php';

class Auth {
    private $db;

    public function __construct() {
        // Đảm bảo session đã được khởi tạo
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $this->db = new Database();
    }

    public function login($username, $password) {
        $this->db->query("SELECT * FROM users WHERE Username = :username AND Status = 'active'");
        $this->db->bind(':username', $username);
        $user = $this->db->single();

        if ($user && password_verify($password, $user['Password'])) {
            $_SESSION['user_id'] = $user['UserID'];
            $_SESSION['username'] = $user['Username'];
            $_SESSION['role'] = $user['Role'];
            
            // Cập nhật thời gian đăng nhập cuối
            $this->db->query("UPDATE users SET LastLogin = NOW() WHERE UserID = :userId");
            $this->db->bind(':userId', $user['UserID']);
            $this->db->execute();
            
            // Lấy profile_id tương ứng với vai trò
            if ($user['Role'] == 'student') {
                $this->db->query("SELECT SinhVienID FROM sinhvien WHERE UserID = :userId");
                $this->db->bind(':userId', $user['UserID']);
                $profile = $this->db->single();
                if ($profile) {
                    $_SESSION['profile_id'] = $profile['SinhVienID'];
                }
            } elseif ($user['Role'] == 'faculty') {
                $this->db->query("SELECT GiangVienID FROM giangvien WHERE UserID = :userId");
                $this->db->bind(':userId', $user['UserID']);
                $profile = $this->db->single();
                if ($profile) {
                    $_SESSION['profile_id'] = $profile['GiangVienID'];
                }
            } elseif ($user['Role'] == 'admin') {
                $this->db->query("SELECT AdminID FROM admin WHERE UserID = :userId");
                $this->db->bind(':userId', $user['UserID']);
                $profile = $this->db->single();
                if ($profile) {
                    $_SESSION['profile_id'] = $profile['AdminID'];
                }
            }
            
            return true;
        }
        return false;
    }

    public function logout() {
        session_unset();
        session_destroy();
    }

    public function register($data) {
        // Kiểm tra username hoặc email đã tồn tại chưa
        $this->db->query("SELECT * FROM users WHERE Username = :username OR Email = :email");
        $this->db->bind(':username', $data['username']);
        $this->db->bind(':email', $data['email']);
        
        if ($this->db->rowCount() > 0) {
            return false;
        }

        // Thêm user mới
        $this->db->query("INSERT INTO users (Username, Password, Email, Role, Status) VALUES (:username, :password, :email, :role, 'active')");
        $this->db->bind(':username', $data['username']);
        $this->db->bind(':password', password_hash($data['password'], PASSWORD_DEFAULT));
        $this->db->bind(':email', $data['email']);
        $this->db->bind(':role', $data['role']);
        
        if ($this->db->execute()) {
            $userId = $this->db->lastInsertId();
            
            switch ($data['role']) {
                case 'student':
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
                        :maSV, 
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
                    
                    $this->db->bind(':userId', $userId);
                    $this->db->bind(':maSV', $data['maSV'] ?? $data['username']);
                    $this->db->bind(':hoTen', $data['hoTen'] ?? '');
                    $this->db->bind(':ngaySinh', $data['ngaySinh'] ?? null);
                    $this->db->bind(':gioiTinh', $data['gioiTinh'] ?? null);
                    $this->db->bind(':khoa', $data['khoa'] ?? null);
                    $this->db->bind(':nganhHoc', $data['nganhHoc'] ?? null);
                    $this->db->bind(':nienKhoa', $data['nienKhoa'] ?? null);
                    $this->db->bind(':soDienThoai', $data['soDienThoai'] ?? null);
                    $this->db->bind(':diaChi', $data['diaChi'] ?? null);
                    $this->db->bind(':avatar', $data['avatar'] ?? null);
                    break;
                    
                case 'faculty':
                    $this->db->query("INSERT INTO giangvien (
                        UserID, 
                        MaGV, 
                        HoTen, 
                        HocVi, 
                        ChucVu, 
                        Khoa, 
                        ChuyenNganh,
                        Email, 
                        SoDienThoai,
                        LinhVucHuongDan,
                        SoLuongSinhVienToiDa
                    ) VALUES (
                        :userId, 
                        :maGV, 
                        :hoTen, 
                        :hocVi, 
                        :chucVu, 
                        :khoa, 
                        :chuyenNganh,
                        :email, 
                        :soDienThoai,
                        :linhVucHuongDan,
                        :soLuongSinhVienToiDa
                    )");
                    
                    $this->db->bind(':userId', $userId);
                    $this->db->bind(':maGV', $data['maGV'] ?? $data['username']);
                    $this->db->bind(':hoTen', $data['hoTen'] ?? '');
                    $this->db->bind(':hocVi', $data['hocVi'] ?? null);
                    $this->db->bind(':chucVu', $data['chucVu'] ?? null);
                    $this->db->bind(':khoa', $data['khoa'] ?? null);
                    $this->db->bind(':chuyenNganh', $data['chuyenNganh'] ?? null);
                    $this->db->bind(':email', $data['email']);
                    $this->db->bind(':soDienThoai', $data['soDienThoai'] ?? null);
                    $this->db->bind(':linhVucHuongDan', $data['linhVucHuongDan'] ?? null);
                    $this->db->bind(':soLuongSinhVienToiDa', $data['soLuongSinhVienToiDa'] ?? 10);
                    break;
                    
                case 'admin':
                    $this->db->query("INSERT INTO admin (
                        UserID, 
                        HoTen, 
                        Email, 
                        SoDienThoai
                    ) VALUES (
                        :userId, 
                        :hoTen, 
                        :email, 
                        :soDienThoai
                    )");
                    
                    $this->db->bind(':userId', $userId);
                    $this->db->bind(':hoTen', $data['hoTen'] ?? $data['username']);
                    $this->db->bind(':email', $data['email']);
                    $this->db->bind(':soDienThoai', $data['soDienThoai'] ?? null);
                    break;
            }
            return $this->db->execute();
        }
        
        return false;
    }

    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    public function getUserRole() {
        return isset($_SESSION['role']) ? $_SESSION['role'] : null;
    }

    public function getUserId() {
        return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    }
    
    // Thêm phương thức hasRole()
    public function hasRole($role) {
        return $this->getUserRole() === $role;
    }
    
    // Phương thức tạo token quên mật khẩu
    public function createPasswordResetToken($email) {
        $this->db->query("SELECT * FROM users WHERE Email = :email AND Status = 'active'");
        $this->db->bind(':email', $email);
        $user = $this->db->single();
        
        if (!$user) {
            return false;
        }
        
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $this->db->query("INSERT INTO passwordresettokens (UserID, Token, ExpiresAt) VALUES (:userId, :token, :expiresAt)");
        $this->db->bind(':userId', $user['UserID']);
        $this->db->bind(':token', $token);
        $this->db->bind(':expiresAt', $expiresAt);
        
        if ($this->db->execute()) {
            return $token;
        }
        
        return false;
    }
    
    // Phương thức đặt lại mật khẩu
    public function resetPassword($token, $newPassword) {
        $this->db->query("SELECT * FROM passwordresettokens WHERE Token = :token AND Expired = 0 AND ExpiresAt > NOW()");
        $this->db->bind(':token', $token);
        $tokenData = $this->db->single();
        
        if (!$tokenData) {
            return false;
        }
        
        $this->db->query("UPDATE users SET Password = :password WHERE UserID = :userId");
        $this->db->bind(':password', password_hash($newPassword, PASSWORD_DEFAULT));
        $this->db->bind(':userId', $tokenData['UserID']);
        
        if ($this->db->execute()) {
            $this->db->query("UPDATE passwordresettokens SET Expired = 1 WHERE Token = :token");
            $this->db->bind(':token', $token);
            return $this->db->execute();
        }
        
        return false;
    }

    // Thêm phương thức getFacultyId() để lấy ID của giảng viên
    public function getFacultyId() {
        if ($this->isLoggedIn() && $this->getUserRole() == 'faculty') {
            $db = new Database();
            $userId = $_SESSION['user_id'];
            
            $db->query("SELECT GiangVienID FROM giangvien WHERE UserID = :userId");
            $db->bind(':userId', $userId);
            $result = $db->single();
            
            if ($result) {
                return $result['GiangVienID'];
            }
        }
        return null;
    }
}
?>