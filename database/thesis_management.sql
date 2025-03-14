-- Tạo cơ sở dữ liệu ThesisManagementDB
CREATE DATABASE IF NOT EXISTS ThesisManagementDB;
USE ThesisManagementDB;

-- Bảng Người dùng (Users)
CREATE TABLE Users (
    UserID INT AUTO_INCREMENT PRIMARY KEY,
    Username VARCHAR(50) NOT NULL UNIQUE,
    Password VARCHAR(255) NOT NULL,
    Email VARCHAR(100) NOT NULL UNIQUE,
    Role ENUM('student', 'faculty', 'admin') NOT NULL,
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    LastLogin DATETIME,
    Status ENUM('active', 'inactive', 'suspended') DEFAULT 'active'
);

-- Bảng Sinh viên (SinhVien)
CREATE TABLE SinhVien (
    SinhVienID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT,
    MaSV VARCHAR(20) NOT NULL UNIQUE,
    HoTen VARCHAR(100) NOT NULL,
    NgaySinh DATE,
    GioiTinh ENUM('Nam', 'Nữ', 'Khác'),
    Khoa VARCHAR(100),
    NganhHoc VARCHAR(100),
    NienKhoa VARCHAR(20),
    SoDienThoai VARCHAR(20),
    DiaChi TEXT,
    Avatar VARCHAR(255),
    FOREIGN KEY (UserID) REFERENCES Users(UserID) ON DELETE CASCADE
);

-- Bảng Giảng viên (GiangVien)
CREATE TABLE GiangVien (
    GiangVienID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT,
    MaGV VARCHAR(20) NOT NULL UNIQUE,
    HoTen VARCHAR(100) NOT NULL,
    HocVi VARCHAR(50),
    ChucVu VARCHAR(100),
    Khoa VARCHAR(100),
    ChuyenNganh VARCHAR(100),
    Email VARCHAR(100),
    SoDienThoai VARCHAR(20),
    LinhVucHuongDan TEXT,
    SoLuongSinhVienToiDa INT DEFAULT 10,
    FOREIGN KEY (UserID) REFERENCES Users(UserID) ON DELETE CASCADE
);

-- Bảng đề tài luận văn (DeTai)
CREATE TABLE DeTai (
    DeTaiID INT AUTO_INCREMENT PRIMARY KEY,
    TenDeTai VARCHAR(255) NOT NULL,
    MoTa TEXT,
    LinhVuc VARCHAR(100),
    NgayTao DATETIME DEFAULT CURRENT_TIMESTAMP,
    NgayCapNhat DATETIME ON UPDATE CURRENT_TIMESTAMP,
    TrangThai ENUM('đề xuất', 'được duyệt', 'đang thực hiện', 'hoàn thành', 'hủy') DEFAULT 'đề xuất'
);

-- Bảng phân công hướng dẫn (SinhVienGiangVienHuongDan)
CREATE TABLE SinhVienGiangVienHuongDan (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    SinhVienID INT NOT NULL,
    GiangVienID INT NOT NULL,
    DeTaiID INT NOT NULL,
    NgayBatDau DATE,
    NgayKetThucDuKien DATE,
    TrangThai ENUM('chờ duyệt', 'đang hướng dẫn', 'hoàn thành', 'hủy') DEFAULT 'chờ duyệt',
    GhiChu TEXT,
    FOREIGN KEY (SinhVienID) REFERENCES SinhVien(SinhVienID) ON DELETE CASCADE,
    FOREIGN KEY (GiangVienID) REFERENCES GiangVien(GiangVienID) ON DELETE CASCADE,
    FOREIGN KEY (DeTaiID) REFERENCES DeTai(DeTaiID) ON DELETE CASCADE
);

-- Bảng nhật ký hướng dẫn (NhatKyHuongDan)
CREATE TABLE NhatKyHuongDan (
    NhatKyID INT AUTO_INCREMENT PRIMARY KEY,
    HuongDanID INT NOT NULL,
    NgayGioHuongDan DATETIME,
    NoiDung TEXT,
    NhanXet TEXT,
    TienDo INT DEFAULT 0,
    TaiLieuDinhKem VARCHAR(255),
    NguoiTao INT NOT NULL,
    FOREIGN KEY (HuongDanID) REFERENCES SinhVienGiangVienHuongDan(ID) ON DELETE CASCADE,
    FOREIGN KEY (NguoiTao) REFERENCES Users(UserID) ON DELETE CASCADE
);

-- Bảng đánh giá luận văn (DanhGia)
CREATE TABLE DanhGia (
    DanhGiaID INT AUTO_INCREMENT PRIMARY KEY,
    HuongDanID INT NOT NULL,
    DiemSo DECIMAL(3,1),
    NhanXet TEXT,
    NgayDanhGia DATETIME DEFAULT CURRENT_TIMESTAMP,
    NguoiDanhGia INT,
    FOREIGN KEY (HuongDanID) REFERENCES SinhVienGiangVienHuongDan(ID) ON DELETE CASCADE,
    FOREIGN KEY (NguoiDanhGia) REFERENCES Users(UserID) ON DELETE CASCADE
);

-- Bảng thông báo (ThongBao)
CREATE TABLE ThongBao (
    ThongBaoID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT NOT NULL,
    TieuDe VARCHAR(255) NOT NULL,
    NoiDung TEXT,
    DaDoc BOOLEAN DEFAULT FALSE,
    NgayTao DATETIME DEFAULT CURRENT_TIMESTAMP,
    TrangThai ENUM('chưa đọc', 'đã đọc', 'đã xóa') DEFAULT 'chưa đọc',
    FOREIGN KEY (UserID) REFERENCES Users(UserID) ON DELETE CASCADE
);