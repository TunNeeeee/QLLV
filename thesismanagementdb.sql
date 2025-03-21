-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 21, 2025 at 03:46 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `thesismanagementdb`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `AdminID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `HoTen` varchar(255) NOT NULL,
  `Email` varchar(255) NOT NULL,
  `SoDienThoai` varchar(15) DEFAULT NULL,
  `NgayTao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`AdminID`, `UserID`, `HoTen`, `Email`, `SoDienThoai`, `NgayTao`) VALUES
(1, 8, 'Admin', 'admin@gmail.com', '0123456999', '2025-03-13 10:13:26');

-- --------------------------------------------------------

--
-- Table structure for table `danhgia`
--

CREATE TABLE `danhgia` (
  `DanhGiaID` int(11) NOT NULL,
  `HuongDanID` int(11) NOT NULL,
  `DiemSo` decimal(3,1) DEFAULT NULL,
  `NhanXet` text DEFAULT NULL,
  `NgayDanhGia` datetime DEFAULT current_timestamp(),
  `NguoiDanhGia` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `detai`
--

CREATE TABLE `detai` (
  `DeTaiID` int(11) NOT NULL,
  `TenDeTai` varchar(255) NOT NULL,
  `MoTa` text DEFAULT NULL,
  `LinhVuc` varchar(100) DEFAULT NULL,
  `GiangVienID` int(11) NOT NULL,
  `NgayTao` datetime DEFAULT current_timestamp(),
  `NgayCapNhat` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `TrangThai` enum('đề xuất','được duyệt','đang thực hiện','hoàn thành','hủy') DEFAULT 'đề xuất'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `detai`
--

INSERT INTO `detai` (`DeTaiID`, `TenDeTai`, `MoTa`, `LinhVuc`, `GiangVienID`, `NgayTao`, `NgayCapNhat`, `TrangThai`) VALUES
(1, 'Website Quản Lý Booking Khách Sạn', 'Xây dựng website quản lý khách sạn giúp khách sạn có thể nắm bắt được các thông tin cơ bản của khách hàng: tên khách hàng, giới tính, địa chỉ, email, số điện thoại, loại phòng, loại giường, số lượng phòng đặt,… giúp nhân viên dễ dàng tìm kiếm khách hàng theo phòng, tên, số chứng minh thư nhân dân hay số hộ chiếu,… Trong quá trình khách hàng lưu trú có thể bổ sung lưu trữ những thông tin về thói quen, thuộc nhóm khách hàng nào, tìm hiểu khả năng chi trả hay các thói quen tiêu dùng của khách hàng để từ đó thuận tiện cho việc chăm sóc khách hàng vào những lần lưu trú tiếp theo. Quá trình cập nhật, tìm kiếm khách hàng sẽ trở lên nhanh chóng, thuận tiện khi triển khai các chiến dịch tiếp thị hiệu quả khi quảng cáo đến đúng đối tượng khách hàng, từ đó mang về nguồn khách hàng tiềm năng trong tương lai. Tài liệu giúp bạn tham khảo, ôn tập và đạt kết quả cao. ', 'Công nghệ phần mềm', 1, '2025-03-13 20:33:33', '2025-03-17 14:26:27', 'đang thực hiện'),
(2, '2D Fighting Game', 'Học cách tạo một game đối kháng trong Unity với hướng dẫn chi tiết từ cài đặt dự án, thiết lập nhân vật, đến xây dựng cơ chế chiến đấu và tối ưu hóa. ', 'Công nghệ phần mềm', 1, '2025-03-15 13:43:12', '2025-03-17 14:26:04', 'đang thực hiện'),
(3, 'Website Quản Lý Rạp Chiếu Phim', 'website quản lý rạp chiếu phim', 'Công nghệ phần mềm', 1, '2025-03-15 14:21:40', '2025-03-17 14:09:12', 'đang thực hiện'),
(4, 'Website Quản Lý Bất Động Sản', 'QLBDS', 'Công nghệ phần mềm', 1, '2025-03-17 12:59:33', '2025-03-17 14:27:42', 'đang thực hiện'),
(5, 'Website Quản Lý Nhân Sự', 'Website Quản Lý Nhân Sự', 'Công nghệ phần mềm', 1, '2025-03-19 12:10:43', '2025-03-19 12:11:41', 'được duyệt'),
(6, 'Website Quản Lý Luận Văn', 'Website Quản Lý Luận Văn', 'Công nghệ phần mềm', 1, '2025-03-19 12:11:10', '2025-03-19 20:22:10', 'đang thực hiện'),
(7, 'Website Đặt Lịch Khám Bệnh', 'Website Đặt Lịch Khám Bệnh', 'Công nghệ phần mềm', 1, '2025-03-19 12:13:15', '2025-03-19 18:23:34', 'đang thực hiện');

-- --------------------------------------------------------

--
-- Table structure for table `giangvien`
--

CREATE TABLE `giangvien` (
  `GiangVienID` int(11) NOT NULL,
  `UserID` int(11) DEFAULT NULL,
  `MaGV` varchar(20) NOT NULL,
  `HoTen` varchar(100) NOT NULL,
  `HocVi` varchar(50) DEFAULT NULL,
  `ChucVu` varchar(100) DEFAULT NULL,
  `Khoa` varchar(100) DEFAULT NULL,
  `ChuyenNganh` varchar(100) DEFAULT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `SoDienThoai` varchar(20) DEFAULT NULL,
  `LinhVucHuongDan` text DEFAULT NULL,
  `SoLuongSinhVienToiDa` int(11) DEFAULT 10
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `giangvien`
--

INSERT INTO `giangvien` (`GiangVienID`, `UserID`, `MaGV`, `HoTen`, `HocVi`, `ChucVu`, `Khoa`, `ChuyenNganh`, `Email`, `SoDienThoai`, `LinhVucHuongDan`, `SoLuongSinhVienToiDa`) VALUES
(1, 6, 'GV01', 'Nguyễn Đình Tuấn', 'Giáo sư', 'Trưởng bộ môn ATTT', 'Công Nghệ Thông Tin', NULL, 'gs123@gmail.com', '0123456782', NULL, 10);

-- --------------------------------------------------------

--
-- Table structure for table `hoatdongnguoidung`
--

CREATE TABLE `hoatdongnguoidung` (
  `HoatDongID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `LoaiHanhDong` varchar(50) NOT NULL,
  `MoTa` text NOT NULL,
  `DoiTuongID` int(11) DEFAULT NULL,
  `ThoiGian` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hoatdongnguoidung`
--

INSERT INTO `hoatdongnguoidung` (`HoatDongID`, `UserID`, `LoaiHanhDong`, `MoTa`, `DoiTuongID`, `ThoiGian`) VALUES
(1, 6, 'create_thesis', 'Tạo đề tài mới: Website Quản Lý Rạp Chiếu Phim', 3, '2025-03-15 07:21:40'),
(2, 6, 'create_thesis', 'Tạo đề tài mới: Website Quản Lý Bất Động Sản', 4, '2025-03-17 05:59:33'),
(3, 6, 'create_thesis', 'Tạo đề tài mới: Website Quản Lý Nhân Sự', 5, '2025-03-19 05:10:43'),
(4, 6, 'create_thesis', 'Tạo đề tài mới: Website Quản Lý Luận Văn', 6, '2025-03-19 05:11:10'),
(5, 6, 'create_thesis', 'Tạo đề tài mới: Website Đặt Lịch Khám Bệnh', 7, '2025-03-19 05:13:15');

-- --------------------------------------------------------

--
-- Table structure for table `lichgap`
--

CREATE TABLE `lichgap` (
  `LichGapID` int(11) NOT NULL,
  `SinhVienID` int(11) NOT NULL,
  `GiangVienID` int(11) NOT NULL,
  `TieuDe` varchar(255) NOT NULL,
  `NgayGap` datetime NOT NULL,
  `DiaDiem` varchar(255) DEFAULT NULL,
  `NoiDung` text DEFAULT NULL,
  `TrangThai` enum('đã lên lịch','đã xác nhận','đã hoàn thành','đã hủy') DEFAULT 'đã lên lịch',
  `GhiChu` text DEFAULT NULL,
  `NgayTao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lichgap`
--

INSERT INTO `lichgap` (`LichGapID`, `SinhVienID`, `GiangVienID`, `TieuDe`, `NgayGap`, `DiaDiem`, `NoiDung`, `TrangThai`, `GhiChu`, `NgayTao`) VALUES
(1, 6, 1, 'Báo Cáo Tuần 1', '2025-03-19 16:35:00', 'Phòng E2.02.01', NULL, 'đã lên lịch', NULL, '2025-03-17 09:01:03'),
(2, 4, 1, 'Báo Cáo Tuần 1', '2025-03-19 16:35:00', 'Phòng E2.02.01', NULL, 'đã lên lịch', NULL, '2025-03-17 09:01:03'),
(3, 5, 1, 'Báo Cáo Tuần 1', '2025-03-19 16:35:00', 'Phòng E2.02.01', NULL, 'đã lên lịch', NULL, '2025-03-17 09:01:03'),
(4, 7, 1, 'Báo Cáo Tuần 1', '2025-03-19 16:35:00', 'Phòng E2.02.01', NULL, 'đã lên lịch', NULL, '2025-03-17 09:01:03'),
(5, 8, 1, 'Báo Cáo Tuần 2', '2025-03-26 16:35:00', 'Phòng E2.02.01', NULL, 'đã lên lịch', NULL, '2025-03-19 11:35:35'),
(6, 6, 1, 'Báo Cáo Tuần 2', '2025-03-26 16:35:00', 'Phòng E2.02.01', NULL, 'đã lên lịch', NULL, '2025-03-19 11:35:35'),
(7, 4, 1, 'Báo Cáo Tuần 2', '2025-03-26 16:35:00', 'Phòng E2.02.01', NULL, 'đã lên lịch', NULL, '2025-03-19 11:35:35'),
(8, 5, 1, 'Báo Cáo Tuần 2', '2025-03-26 16:35:00', 'Phòng E2.02.01', NULL, 'đã lên lịch', NULL, '2025-03-19 11:35:35'),
(9, 7, 1, 'Báo Cáo Tuần 2', '2025-03-26 16:35:00', 'Phòng E2.02.01', NULL, 'đã lên lịch', NULL, '2025-03-19 11:35:35'),
(10, 8, 1, 'Báo Cáo Tuần 3', '2025-04-02 16:35:00', 'Phòng E2.02.01', NULL, 'đã lên lịch', NULL, '2025-03-19 11:35:56'),
(11, 6, 1, 'Báo Cáo Tuần 3', '2025-04-02 16:35:00', 'Phòng E2.02.01', NULL, 'đã lên lịch', NULL, '2025-03-19 11:35:56'),
(12, 4, 1, 'Báo Cáo Tuần 3', '2025-04-02 16:35:00', 'Phòng E2.02.01', NULL, 'đã lên lịch', NULL, '2025-03-19 11:35:56'),
(13, 5, 1, 'Báo Cáo Tuần 3', '2025-04-02 16:35:00', 'Phòng E2.02.01', NULL, 'đã lên lịch', NULL, '2025-03-19 11:35:56'),
(14, 7, 1, 'Báo Cáo Tuần 3', '2025-04-02 16:35:00', 'Phòng E2.02.01', NULL, 'đã lên lịch', NULL, '2025-03-19 11:35:56');

-- --------------------------------------------------------

--
-- Table structure for table `nhatkyhuongdan`
--

CREATE TABLE `nhatkyhuongdan` (
  `NhatKyID` int(11) NOT NULL,
  `HuongDanID` int(11) NOT NULL,
  `NgayGioHuongDan` datetime DEFAULT NULL,
  `NoiDung` text DEFAULT NULL,
  `NhanXet` text DEFAULT NULL,
  `TienDo` int(11) DEFAULT 0,
  `TaiLieuDinhKem` varchar(255) DEFAULT NULL,
  `NguoiTao` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `passwordresettokens`
--

CREATE TABLE `passwordresettokens` (
  `ID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `Token` varchar(100) NOT NULL,
  `ExpiresAt` datetime NOT NULL,
  `Expired` tinyint(1) NOT NULL DEFAULT 0,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sinhvien`
--

CREATE TABLE `sinhvien` (
  `SinhVienID` int(11) NOT NULL,
  `UserID` int(11) DEFAULT NULL,
  `MaSV` varchar(20) NOT NULL,
  `HoTen` varchar(100) NOT NULL,
  `NgaySinh` date DEFAULT NULL,
  `GioiTinh` enum('Nam','Nữ','Khác') DEFAULT NULL,
  `Khoa` varchar(100) DEFAULT NULL,
  `NganhHoc` varchar(100) DEFAULT NULL,
  `NienKhoa` varchar(20) DEFAULT NULL,
  `SoDienThoai` varchar(20) DEFAULT NULL,
  `DiaChi` text DEFAULT NULL,
  `Avatar` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sinhvien`
--

INSERT INTO `sinhvien` (`SinhVienID`, `UserID`, `MaSV`, `HoTen`, `NgaySinh`, `GioiTinh`, `Khoa`, `NganhHoc`, `NienKhoa`, `SoDienThoai`, `DiaChi`, `Avatar`) VALUES
(4, 5, '2180608888', 'Nguyễn Quốc Nam', '2025-03-04', 'Nam', 'CNTT', 'CNTT', 'K21', '0123456781', 'HN', NULL),
(5, 9, '2180608654', 'Nguyễn Đình Tuấn', '2003-11-19', 'Nam', '', 'CNTT', '2021-2025', '0329902614', 'Đồng Nai', 'uploads/avatars/avatar_9_1742017839.jpg'),
(6, 10, '2180607345', 'Nguyễn Quốc Cường', '2003-11-07', 'Nam', '', 'CNTT', '2021-2025', '0123456999', 'Quảng Ngãi', NULL),
(7, 11, '2180600719', 'Võ Trung Kiên', '2003-10-29', 'Nam', '', 'CNTT', '2021-2025', '0374220124', 'Trà Vinh', NULL),
(8, 12, '2180608000', 'Nguyễn Ngọc Bảo Hân', '2005-03-16', 'Nữ', '', 'Công Nghệ Thông Tin', '2023-2027', '0329902621', 'TP Huế', 'uploads/avatars/avatar_12_1742197426.png'),
(9, 13, '2180608001', 'Trần Văn Minh', '2004-01-19', 'Nam', '', 'Công Nghệ Thông Tin', '2022-2026', '0123456991', 'Bình Dương', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `sinhviengiangvienhuongdan`
--

CREATE TABLE `sinhviengiangvienhuongdan` (
  `ID` int(11) NOT NULL,
  `SinhVienID` int(11) NOT NULL,
  `GiangVienID` int(11) NOT NULL,
  `DeTaiID` int(11) NOT NULL,
  `NgayBatDau` date DEFAULT NULL,
  `NgayKetThucDuKien` date DEFAULT NULL,
  `TrangThai` enum('chờ duyệt','đang hướng dẫn','hoàn thành','hủy') DEFAULT 'chờ duyệt',
  `GhiChu` text DEFAULT NULL,
  `TienDo` int(3) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sinhviengiangvienhuongdan`
--

INSERT INTO `sinhviengiangvienhuongdan` (`ID`, `SinhVienID`, `GiangVienID`, `DeTaiID`, `NgayBatDau`, `NgayKetThucDuKien`, `TrangThai`, `GhiChu`, `TienDo`) VALUES
(17, 4, 1, 1, '2025-03-13', '2025-05-13', 'chờ duyệt', '', 0),
(18, 5, 1, 3, '2025-03-13', '2025-05-13', 'chờ duyệt', '', 0),
(19, 6, 1, 4, '2025-03-13', '2025-05-13', 'chờ duyệt', '', 0),
(20, 7, 1, 2, '2025-03-14', '2025-05-14', 'chờ duyệt', '', 0),
(26, 8, 1, 7, '2025-03-14', '2025-05-14', 'chờ duyệt', '', 0),
(27, 9, 1, 6, '2025-03-19', '2025-05-19', 'chờ duyệt', '', 0);

-- --------------------------------------------------------

--
-- Table structure for table `thongbao`
--

CREATE TABLE `thongbao` (
  `ThongBaoID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `TieuDe` varchar(255) NOT NULL,
  `NoiDung` text DEFAULT NULL,
  `DaDoc` tinyint(1) DEFAULT 0,
  `NgayTao` datetime DEFAULT current_timestamp(),
  `TrangThai` enum('chưa đọc','đã đọc','đã xóa') DEFAULT 'chưa đọc',
  `LoaiThongBao` varchar(50) DEFAULT NULL,
  `LienKet` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `thongbao`
--

INSERT INTO `thongbao` (`ThongBaoID`, `UserID`, `TieuDe`, `NoiDung`, `DaDoc`, `NgayTao`, `TrangThai`, `LoaiThongBao`, `LienKet`) VALUES
(1, 9, 'Được gán đề tài mới', 'Bạn đã được gán đề tài: Website Quản Lý Rạp Chiếu Phim. Vui lòng xem chi tiết và liên hệ với giảng viên hướng dẫn để tiếp tục.', 0, '2025-03-17 14:09:12', 'chưa đọc', 'đề tài', 'student/thesis.php'),
(2, 9, 'Được gán đề tài mới', 'Bạn đã được gán đề tài: Website Quản Lý Rạp Chiếu Phim. Vui lòng xem chi tiết và liên hệ với giảng viên hướng dẫn để tiếp tục.', 0, '2025-03-17 14:14:44', 'chưa đọc', 'đề tài', 'student/thesis.php'),
(3, 10, 'Được gán đề tài mới', 'Bạn đã được gán đề tài: Website Quản Lý Bất Động Sản. Vui lòng xem chi tiết và liên hệ với giảng viên hướng dẫn để tiếp tục.', 0, '2025-03-17 14:27:42', 'chưa đọc', 'đề tài', 'student/thesis.php'),
(4, 10, 'Lịch gặp mới', 'Giảng viên đã tạo một lịch gặp mới: Báo Cáo Tuần 1 vào ngày 2025-03-19 lúc 16:35 tại Phòng E2.02.01', 0, '2025-03-17 16:01:03', 'chưa đọc', 'lịch gặp', 'student/appointments.php'),
(5, 5, 'Lịch gặp mới', 'Giảng viên đã tạo một lịch gặp mới: Báo Cáo Tuần 1 vào ngày 2025-03-19 lúc 16:35 tại Phòng E2.02.01', 0, '2025-03-17 16:01:03', 'chưa đọc', 'lịch gặp', 'student/appointments.php'),
(6, 9, 'Lịch gặp mới', 'Giảng viên đã tạo một lịch gặp mới: Báo Cáo Tuần 1 vào ngày 2025-03-19 lúc 16:35 tại Phòng E2.02.01', 0, '2025-03-17 16:01:03', 'chưa đọc', 'lịch gặp', 'student/appointments.php'),
(7, 11, 'Lịch gặp mới', 'Giảng viên đã tạo một lịch gặp mới: Báo Cáo Tuần 1 vào ngày 2025-03-19 lúc 16:35 tại Phòng E2.02.01', 0, '2025-03-17 16:01:03', 'chưa đọc', 'lịch gặp', 'student/appointments.php'),
(8, 12, 'Được gán đề tài mới', 'Bạn đã được gán đề tài: Website Đặt Lịch Khám Bệnh. Vui lòng xem chi tiết và liên hệ với giảng viên hướng dẫn để tiếp tục.', 0, '2025-03-19 18:23:34', 'chưa đọc', 'đề tài', 'student/thesis.php'),
(9, 12, 'Lịch gặp mới', 'Giảng viên đã tạo một lịch gặp mới: Báo Cáo Tuần 2 vào ngày 2025-03-26 lúc 16:35 tại Phòng E2.02.01', 0, '2025-03-19 18:35:35', 'chưa đọc', 'lịch gặp', 'student/appointments.php'),
(10, 10, 'Lịch gặp mới', 'Giảng viên đã tạo một lịch gặp mới: Báo Cáo Tuần 2 vào ngày 2025-03-26 lúc 16:35 tại Phòng E2.02.01', 0, '2025-03-19 18:35:35', 'chưa đọc', 'lịch gặp', 'student/appointments.php'),
(11, 5, 'Lịch gặp mới', 'Giảng viên đã tạo một lịch gặp mới: Báo Cáo Tuần 2 vào ngày 2025-03-26 lúc 16:35 tại Phòng E2.02.01', 0, '2025-03-19 18:35:35', 'chưa đọc', 'lịch gặp', 'student/appointments.php'),
(12, 9, 'Lịch gặp mới', 'Giảng viên đã tạo một lịch gặp mới: Báo Cáo Tuần 2 vào ngày 2025-03-26 lúc 16:35 tại Phòng E2.02.01', 0, '2025-03-19 18:35:35', 'chưa đọc', 'lịch gặp', 'student/appointments.php'),
(13, 11, 'Lịch gặp mới', 'Giảng viên đã tạo một lịch gặp mới: Báo Cáo Tuần 2 vào ngày 2025-03-26 lúc 16:35 tại Phòng E2.02.01', 0, '2025-03-19 18:35:35', 'chưa đọc', 'lịch gặp', 'student/appointments.php'),
(14, 12, 'Lịch gặp mới', 'Giảng viên đã tạo một lịch gặp mới: Báo Cáo Tuần 3 vào ngày 2025-04-02 lúc 16:35 tại Phòng E2.02.01', 0, '2025-03-19 18:35:56', 'chưa đọc', 'lịch gặp', 'student/appointments.php'),
(15, 10, 'Lịch gặp mới', 'Giảng viên đã tạo một lịch gặp mới: Báo Cáo Tuần 3 vào ngày 2025-04-02 lúc 16:35 tại Phòng E2.02.01', 0, '2025-03-19 18:35:56', 'chưa đọc', 'lịch gặp', 'student/appointments.php'),
(16, 5, 'Lịch gặp mới', 'Giảng viên đã tạo một lịch gặp mới: Báo Cáo Tuần 3 vào ngày 2025-04-02 lúc 16:35 tại Phòng E2.02.01', 0, '2025-03-19 18:35:56', 'chưa đọc', 'lịch gặp', 'student/appointments.php'),
(17, 9, 'Lịch gặp mới', 'Giảng viên đã tạo một lịch gặp mới: Báo Cáo Tuần 3 vào ngày 2025-04-02 lúc 16:35 tại Phòng E2.02.01', 0, '2025-03-19 18:35:56', 'chưa đọc', 'lịch gặp', 'student/appointments.php'),
(18, 11, 'Lịch gặp mới', 'Giảng viên đã tạo một lịch gặp mới: Báo Cáo Tuần 3 vào ngày 2025-04-02 lúc 16:35 tại Phòng E2.02.01', 0, '2025-03-19 18:35:56', 'chưa đọc', 'lịch gặp', 'student/appointments.php'),
(19, 13, 'Được gán đề tài mới', 'Bạn đã được gán đề tài: Website Quản Lý Luận Văn. Vui lòng xem chi tiết và liên hệ với giảng viên hướng dẫn để tiếp tục.', 0, '2025-03-19 20:22:10', 'chưa đọc', 'đề tài', 'student/thesis.php');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `UserID` int(11) NOT NULL,
  `Username` varchar(50) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `Role` enum('student','faculty','admin') NOT NULL,
  `CreatedAt` datetime DEFAULT current_timestamp(),
  `LastLogin` datetime DEFAULT NULL,
  `Status` enum('active','inactive','suspended') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`UserID`, `Username`, `Password`, `Email`, `Role`, `CreatedAt`, `LastLogin`, `Status`) VALUES
(5, '2180608888', '$2y$10$R5UVrFJlXH.q0V3o8Vqn7OLhibPCuksYTDwWFGQS.T9ZXFk.qc1qu', 'tuan123@gmail.com', 'student', '2025-03-13 09:47:05', NULL, 'active'),
(6, 'tuantaka1911', '$2y$10$mwPanlsA/S6.IlApCJ8Y7epHp6RH48k2ZHrZFD4WcdMZSHL1Ws9ty', 'gs123@gmail.com', 'faculty', '2025-03-13 13:04:38', NULL, 'active'),
(8, 'admin', '$2y$10$7vKB.qamhCbTm9WNVXXbSO21U8KAWrmwAFEBd6K560LHk5yFK65Q6', 'admin@gmail.com', 'admin', '2025-03-13 17:13:26', NULL, 'active'),
(9, '2180608654', '$2y$10$wl7EkuPXp0/5PfBEP8k/cOnmrHCbJKmKwhs/Gnupp5VthdQUqBRsW', 'tuanvtvc@gmail.com', 'student', '2025-03-13 20:45:01', NULL, 'active'),
(10, '2180607345', '$2y$10$CCbJV39bA1wYEyEq2L5EDO.bEA/84mMENELA5..gtlFGCozaFMSAS', 'cuongnguyen@gmail.com', 'student', '2025-03-13 21:00:51', NULL, 'active'),
(11, '2180600719', '$2y$10$3mlETuxjm3wEl.zzZ60NleEDd70q.QpaqUlSdI.4ScWaJbEaM1Y/i', 'votrungkien3002@gmail.com', 'student', '2025-03-14 07:39:20', NULL, 'active'),
(12, '2180608000', '$2y$10$GcAt/.rzHrGAXPfgYs1DUe57UMg6exJQKMtoS9mrAi/gQN631F9N2', 'boahan@gmail.com', 'student', '2025-03-17 14:42:27', NULL, 'active'),
(13, '2180608001', '$2y$10$7jD6CZAOtcJglCKh29W1xONIy9vd.QSB9VLQ2dhZ7db6.De/0Lefi', 'boahann@gmail.com', 'student', '2025-03-19 20:21:11', NULL, 'active');

-- --------------------------------------------------------

--
-- Table structure for table `yeucauphancong`
--

CREATE TABLE `yeucauphancong` (
  `YeuCauID` int(11) NOT NULL,
  `GiangVienID` int(11) NOT NULL,
  `SinhVienID` int(11) NOT NULL,
  `DeTaiID` int(11) NOT NULL,
  `GhiChu` text DEFAULT NULL,
  `TrangThai` enum('Chờ duyệt','Đã duyệt','Từ chối') DEFAULT 'Chờ duyệt',
  `NgayYeuCau` datetime DEFAULT current_timestamp(),
  `NgayDuyet` datetime DEFAULT NULL,
  `NguoiDuyetID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`AdminID`),
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `danhgia`
--
ALTER TABLE `danhgia`
  ADD PRIMARY KEY (`DanhGiaID`),
  ADD KEY `HuongDanID` (`HuongDanID`),
  ADD KEY `NguoiDanhGia` (`NguoiDanhGia`);

--
-- Indexes for table `detai`
--
ALTER TABLE `detai`
  ADD PRIMARY KEY (`DeTaiID`),
  ADD KEY `idx_giangvien` (`GiangVienID`);

--
-- Indexes for table `giangvien`
--
ALTER TABLE `giangvien`
  ADD PRIMARY KEY (`GiangVienID`),
  ADD UNIQUE KEY `MaGV` (`MaGV`),
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `hoatdongnguoidung`
--
ALTER TABLE `hoatdongnguoidung`
  ADD PRIMARY KEY (`HoatDongID`);

--
-- Indexes for table `lichgap`
--
ALTER TABLE `lichgap`
  ADD PRIMARY KEY (`LichGapID`),
  ADD KEY `SinhVienID` (`SinhVienID`),
  ADD KEY `GiangVienID` (`GiangVienID`);

--
-- Indexes for table `nhatkyhuongdan`
--
ALTER TABLE `nhatkyhuongdan`
  ADD PRIMARY KEY (`NhatKyID`),
  ADD KEY `HuongDanID` (`HuongDanID`),
  ADD KEY `NguoiTao` (`NguoiTao`);

--
-- Indexes for table `passwordresettokens`
--
ALTER TABLE `passwordresettokens`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `Token` (`Token`),
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `sinhvien`
--
ALTER TABLE `sinhvien`
  ADD PRIMARY KEY (`SinhVienID`),
  ADD UNIQUE KEY `MaSV` (`MaSV`),
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `sinhviengiangvienhuongdan`
--
ALTER TABLE `sinhviengiangvienhuongdan`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `SinhVienID` (`SinhVienID`),
  ADD KEY `GiangVienID` (`GiangVienID`),
  ADD KEY `DeTaiID` (`DeTaiID`);

--
-- Indexes for table `thongbao`
--
ALTER TABLE `thongbao`
  ADD PRIMARY KEY (`ThongBaoID`),
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`UserID`),
  ADD UNIQUE KEY `Username` (`Username`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- Indexes for table `yeucauphancong`
--
ALTER TABLE `yeucauphancong`
  ADD PRIMARY KEY (`YeuCauID`),
  ADD KEY `GiangVienID` (`GiangVienID`),
  ADD KEY `SinhVienID` (`SinhVienID`),
  ADD KEY `DeTaiID` (`DeTaiID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `AdminID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `danhgia`
--
ALTER TABLE `danhgia`
  MODIFY `DanhGiaID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `detai`
--
ALTER TABLE `detai`
  MODIFY `DeTaiID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `giangvien`
--
ALTER TABLE `giangvien`
  MODIFY `GiangVienID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `hoatdongnguoidung`
--
ALTER TABLE `hoatdongnguoidung`
  MODIFY `HoatDongID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `lichgap`
--
ALTER TABLE `lichgap`
  MODIFY `LichGapID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `nhatkyhuongdan`
--
ALTER TABLE `nhatkyhuongdan`
  MODIFY `NhatKyID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `passwordresettokens`
--
ALTER TABLE `passwordresettokens`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sinhvien`
--
ALTER TABLE `sinhvien`
  MODIFY `SinhVienID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `sinhviengiangvienhuongdan`
--
ALTER TABLE `sinhviengiangvienhuongdan`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `thongbao`
--
ALTER TABLE `thongbao`
  MODIFY `ThongBaoID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `UserID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `yeucauphancong`
--
ALTER TABLE `yeucauphancong`
  MODIFY `YeuCauID` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin`
--
ALTER TABLE `admin`
  ADD CONSTRAINT `admin_user_fk` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE;

--
-- Constraints for table `danhgia`
--
ALTER TABLE `danhgia`
  ADD CONSTRAINT `danhgia_ibfk_1` FOREIGN KEY (`HuongDanID`) REFERENCES `sinhviengiangvienhuongdan` (`ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `danhgia_ibfk_2` FOREIGN KEY (`NguoiDanhGia`) REFERENCES `users` (`UserID`) ON DELETE CASCADE;

--
-- Constraints for table `giangvien`
--
ALTER TABLE `giangvien`
  ADD CONSTRAINT `giangvien_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE;

--
-- Constraints for table `lichgap`
--
ALTER TABLE `lichgap`
  ADD CONSTRAINT `lichgap_giangvien_fk` FOREIGN KEY (`GiangVienID`) REFERENCES `giangvien` (`GiangVienID`) ON DELETE CASCADE,
  ADD CONSTRAINT `lichgap_sinhvien_fk` FOREIGN KEY (`SinhVienID`) REFERENCES `sinhvien` (`SinhVienID`) ON DELETE CASCADE;

--
-- Constraints for table `nhatkyhuongdan`
--
ALTER TABLE `nhatkyhuongdan`
  ADD CONSTRAINT `nhatkyhuongdan_ibfk_1` FOREIGN KEY (`HuongDanID`) REFERENCES `sinhviengiangvienhuongdan` (`ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `nhatkyhuongdan_ibfk_2` FOREIGN KEY (`NguoiTao`) REFERENCES `users` (`UserID`) ON DELETE CASCADE;

--
-- Constraints for table `passwordresettokens`
--
ALTER TABLE `passwordresettokens`
  ADD CONSTRAINT `password_reset_tokens_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE;

--
-- Constraints for table `sinhvien`
--
ALTER TABLE `sinhvien`
  ADD CONSTRAINT `sinhvien_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE;

--
-- Constraints for table `sinhviengiangvienhuongdan`
--
ALTER TABLE `sinhviengiangvienhuongdan`
  ADD CONSTRAINT `sinhviengiangvienhuongdan_ibfk_1` FOREIGN KEY (`SinhVienID`) REFERENCES `sinhvien` (`SinhVienID`) ON DELETE CASCADE,
  ADD CONSTRAINT `sinhviengiangvienhuongdan_ibfk_2` FOREIGN KEY (`GiangVienID`) REFERENCES `giangvien` (`GiangVienID`) ON DELETE CASCADE;

--
-- Constraints for table `thongbao`
--
ALTER TABLE `thongbao`
  ADD CONSTRAINT `thongbao_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
