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
        $this->db->query("SELECT * FROM Users WHERE Username = :username AND Status = 'active'");
        $this->db->bind(':username', $username);
        $user = $this->db->single();

        if ($user && password_verify($password, $user['Password'])) {
            $_SESSION['user_id'] = $user['UserID'];
            $_SESSION['username'] = $user['Username'];
            $_SESSION['role'] = $user['Role'];
            
            // Thêm debug log
            error_log("Logged in: UserID=" . $user['UserID'] . ", Role=" . $user['Role']);
            
            // Lấy profile_id tương ứng với vai trò
            if ($user['Role'] == 'student') {
                $this->db->query("SELECT SinhVienID FROM SinhVien WHERE UserID = :userId");
                $this->db->bind(':userId', $user['UserID']);
                $profile = $this->db->single();
                if ($profile) {
                    $_SESSION['profile_id'] = $profile['SinhVienID'];
                    error_log("Set profile_id for student: " . $profile['SinhVienID']);
                } else {
                    error_log("No student profile found for UserID: " . $user['UserID']);
                }
            } elseif ($user['Role'] == 'faculty') {
                $this->db->query("SELECT GiangVienID FROM GiangVien WHERE UserID = :userId");
                $this->db->bind(':userId', $user['UserID']);
                $profile = $this->db->single();
                if ($profile) {
                    $_SESSION['profile_id'] = $profile['GiangVienID'];
                    error_log("Set profile_id for faculty: " . $profile['GiangVienID']);
                }
            } elseif ($user['Role'] == 'admin') {
                $this->db->query("SELECT AdminID FROM Admin WHERE UserID = :userId");
                $this->db->bind(':userId', $user['UserID']);
                $profile = $this->db->single();
                if ($profile) {
                    $_SESSION['profile_id'] = $profile['AdminID'];
                    error_log("Set profile_id for admin: " . $profile['AdminID']);
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
        // Kiểm tra xem username hoặc email đã tồn tại chưa
        $this->db->query("SELECT * FROM Users WHERE Username = :username OR Email = :email");
        $this->db->bind(':username', $data['username']);
        $this->db->bind(':email', $data['email']);
        
        // Nếu đã tồn tại, return false
        if ($this->db->rowCount() > 0) {
            return false;
        }

        // Thêm user mới
        $this->db->query("INSERT INTO Users (Username, Password, Email, Role, Status) VALUES (:username, :password, :email, :role, 'active')");
        $this->db->bind(':username', $data['username']);
        $this->db->bind(':password', password_hash($data['password'], PASSWORD_DEFAULT));
        $this->db->bind(':email', $data['email']);
        $this->db->bind(':role', $data['role']);
        
        // Nếu thêm user thành công, thêm thông tin vào bảng tương ứng với vai trò
        if ($this->db->execute()) {
            $userId = $this->db->lastInsertId();
            
            // Thêm thông tin vào bảng tương ứng với vai trò
            switch ($data['role']) {
                case 'student':
                    $this->db->query("INSERT INTO SinhVien (
                        UserID, 
                        MaSV, 
                        HoTen, 
                        NgaySinh, 
                        GioiTinh, 
                        Khoa, 
                        NganhHoc, 
                        NienKhoa, 
                        SoDienThoai, 
                        DiaChi
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
                        :diaChi
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
                    break;
                    
                case 'faculty':
                    $this->db->query("INSERT INTO GiangVien (
                        UserID, 
                        MaGV, 
                        HoTen, 
                        HocVi, 
                        ChucVu, 
                        Khoa, 
                        Email, 
                        SoDienThoai
                    ) VALUES (
                        :userId, 
                        :maGV, 
                        :hoTen, 
                        :hocVi, 
                        :chucVu, 
                        :khoa, 
                        :email, 
                        :soDienThoai
                    )");
                    
                    $this->db->bind(':userId', $userId);
                    $this->db->bind(':maGV', $data['maGV'] ?? $data['username']);
                    $this->db->bind(':hoTen', $data['hoTen'] ?? '');
                    $this->db->bind(':hocVi', $data['hocVi'] ?? null);
                    $this->db->bind(':chucVu', $data['chucVu'] ?? null);
                    $this->db->bind(':khoa', $data['khoa'] ?? null);
                    $this->db->bind(':email', $data['email']);
                    $this->db->bind(':soDienThoai', $data['soDienThoai'] ?? null);
                    break;
                    
                case 'admin':
                    $this->db->query("INSERT INTO Admin (
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
        // Kiểm tra email có tồn tại không
        $this->db->query("SELECT * FROM Users WHERE Email = :email AND Status = 'active'");
        $this->db->bind(':email', $email);
        $user = $this->db->single();
        
        if (!$user) {
            return false;
        }
        
        // Tạo token ngẫu nhiên
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Lưu token vào database
        $this->db->query("INSERT INTO PasswordResetTokens (UserID, Token, ExpiresAt) VALUES (:userId, :token, :expiresAt)");
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
        // Bước 1: Xác minh token
        $this->db->query("SELECT * FROM PasswordResetTokens WHERE Token = :token AND Expired = 0 AND ExpiresAt > NOW()");
        $this->db->bind(':token', $token);
        $tokenData = $this->db->single();
        
        if (!$tokenData) {
            return false; // Token không hợp lệ hoặc đã hết hạn
        }
        
        // Bước 2: Cập nhật mật khẩu mới
        $this->db->query("UPDATE Users SET Password = :password WHERE UserID = :userId");
        $this->db->bind(':password', password_hash($newPassword, PASSWORD_DEFAULT));
        $this->db->bind(':userId', $tokenData['UserID']);
        
        // Bước 3: Đánh dấu token đã được sử dụng
        if ($this->db->execute()) {
            $this->db->query("UPDATE PasswordResetTokens SET Expired = 1 WHERE Token = :token");
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
            
            $db->query("SELECT GiangVienID FROM GiangVien WHERE UserID = :userId");
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