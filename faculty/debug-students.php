<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../classes/Auth.php';

// Bắt đầu phiên nếu chưa được khởi tạo
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$auth = new Auth();

// Kiểm tra đăng nhập và vai trò
if (!$auth->isLoggedIn() || $auth->getUserRole() != 'faculty') {
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit;
}

$db = new Database();

// Lấy thông tin profile ID của giảng viên
$userID = $_SESSION['user_id'];
$db->query("SELECT GiangVienID FROM GiangVien WHERE UserID = :userId");
$db->bind(':userId', $userID);
$result = $db->single();
$facultyId = $result ? $result['GiangVienID'] : 0;

echo "<h1>Debug Thông Tin Giảng Viên</h1>";
echo "<p>User ID: $userID</p>";
echo "<p>Faculty ID: $facultyId</p>";

// Kiểm tra xem có bản ghi phân công nào không
$db->query("SELECT COUNT(*) as total FROM SinhVienGiangVienHuongDan WHERE GiangVienID = :facultyId");
$db->bind(':facultyId', $facultyId);
$count = $db->single()['total'];

echo "<h2>Kiểm tra phân công</h2>";
echo "<p>Số lượng phân công: $count</p>";

if ($count > 0) {
    // Hiển thị chi tiết các phân công
    $db->query("SELECT svgv.*, sv.HoTen, sv.MaSV 
                FROM SinhVienGiangVienHuongDan svgv
                JOIN SinhVien sv ON svgv.SinhVienID = sv.SinhVienID
                WHERE svgv.GiangVienID = :facultyId");
    $db->bind(':facultyId', $facultyId);
    $assignments = $db->resultSet();
    
    echo "<h3>Chi tiết phân công:</h3>";
    echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
    echo "<tr>
            <th>ID</th>
            <th>Sinh viên ID</th>
            <th>Họ tên</th>
            <th>MSSV</th>
            <th>Đề tài ID</th>
            <th>Ngày bắt đầu</th>
            <th>Ngày kết thúc</th>
          </tr>";
    
    foreach ($assignments as $a) {
        echo "<tr>";
        echo "<td>".$a['ID']."</td>";
        echo "<td>".$a['SinhVienID']."</td>";
        echo "<td>".$a['HoTen']."</td>";
        echo "<td>".$a['MaSV']."</td>";
        echo "<td>".($a['DeTaiID'] ?: 'NULL')."</td>";
        echo "<td>".$a['NgayBatDau']."</td>";
        echo "<td>".($a['NgayKetThucDuKien'] ?: 'NULL')."</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Kiểm tra truy vấn giống với trong Faculty class
    echo "<h2>Kiểm tra truy vấn trong Faculty class</h2>";
    $db->query("SELECT sv.*, svgv.NgayBatDau, svgv.NgayKetThucDuKien, 
               dt.DeTaiID, dt.TenDeTai, dt.TrangThai
               FROM SinhVienGiangVienHuongDan svgv
               JOIN SinhVien sv ON svgv.SinhVienID = sv.SinhVienID
               LEFT JOIN DeTai dt ON svgv.DeTaiID = dt.DeTaiID
               WHERE svgv.GiangVienID = :facultyId
               ORDER BY svgv.NgayBatDau DESC");
    $db->bind(':facultyId', $facultyId);
    $students = $db->resultSet();
    
    echo "<p>Số lượng sinh viên từ truy vấn: ".count($students)."</p>";
    
    if (count($students) > 0) {
        echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
        echo "<tr>
                <th>Sinh viên ID</th>
                <th>Họ tên</th>
                <th>MSSV</th>
                <th>Đề tài ID</th>
                <th>Tên đề tài</th>
                <th>Ngày bắt đầu</th>
              </tr>";
        
        foreach ($students as $s) {
            echo "<tr>";
            echo "<td>".$s['SinhVienID']."</td>";
            echo "<td>".$s['HoTen']."</td>";
            echo "<td>".$s['MaSV']."</td>";
            echo "<td>".($s['DeTaiID'] ?: 'NULL')."</td>";
            echo "<td>".($s['TenDeTai'] ?: 'NULL')."</td>";
            echo "<td>".$s['NgayBatDau']."</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} else {
    echo "<p>Không tìm thấy phân công nào cho giảng viên ID: $facultyId</p>";
    
    // Kiểm tra xem có phân công nào trong hệ thống không
    $db->query("SELECT COUNT(*) as total FROM SinhVienGiangVienHuongDan");
    $totalAssignments = $db->single()['total'];
    echo "<p>Tổng số phân công trong hệ thống: $totalAssignments</p>";
    
    if ($totalAssignments > 0) {
        // Hiển thị vài phân công để kiểm tra
        $db->query("SELECT svgv.*, sv.HoTen as TenSinhVien, gv.HoTen as TenGiangVien
                   FROM SinhVienGiangVienHuongDan svgv
                   JOIN SinhVien sv ON svgv.SinhVienID = sv.SinhVienID
                   JOIN GiangVien gv ON svgv.GiangVienID = gv.GiangVienID
                   LIMIT 5");
        $sampleAssignments = $db->resultSet();
        
        echo "<h3>Các phân công mẫu:</h3>";
        echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
        echo "<tr>
                <th>ID</th>
                <th>Sinh viên ID</th>
                <th>Tên sinh viên</th>
                <th>Giảng viên ID</th>
                <th>Tên giảng viên</th>
              </tr>";
        
        foreach ($sampleAssignments as $a) {
            echo "<tr>";
            echo "<td>".$a['ID']."</td>";
            echo "<td>".$a['SinhVienID']."</td>";
            echo "<td>".$a['TenSinhVien']."</td>";
            echo "<td>".$a['GiangVienID']."</td>";
            echo "<td>".$a['TenGiangVien']."</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
}
?>