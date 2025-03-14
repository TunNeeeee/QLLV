<?php
require_once '../config/config.php';
require_once '../config/database.php';

$db = new Database();

echo "<h1>Kiểm tra dữ liệu phân công</h1>";

// Lấy tất cả phân công
$db->query("SELECT svgv.*, sv.HoTen as TenSinhVien, sv.MaSV, gv.HoTen as TenGiangVien 
           FROM SinhVienGiangVienHuongDan svgv 
           JOIN SinhVien sv ON svgv.SinhVienID = sv.SinhVienID 
           JOIN GiangVien gv ON svgv.GiangVienID = gv.GiangVienID");
$assignments = $db->resultSet();

echo "<h2>Tất cả phân công: " . count($assignments) . "</h2>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr>
        <th>ID</th>
        <th>Sinh viên ID</th>
        <th>Tên sinh viên</th>
        <th>Giảng viên ID</th>
        <th>Tên giảng viên</th>
        <th>Ngày bắt đầu</th>
        <th>Đề tài ID</th>
      </tr>";

foreach ($assignments as $a) {
    echo "<tr>";
    echo "<td>" . $a['ID'] . "</td>";
    echo "<td>" . $a['SinhVienID'] . "</td>";
    echo "<td>" . $a['TenSinhVien'] . "</td>";
    echo "<td>" . $a['GiangVienID'] . "</td>";
    echo "<td>" . $a['TenGiangVien'] . "</td>";
    echo "<td>" . $a['NgayBatDau'] . "</td>";
    echo "<td>" . ($a['DeTaiID'] ? $a['DeTaiID'] : "NULL") . "</td>";
    echo "</tr>";
}
echo "</table>";

// Kiểm tra từng giảng viên
$db->query("SELECT * FROM GiangVien");
$faculty = $db->resultSet();

foreach ($faculty as $f) {
    echo "<h2>Giảng viên: " . $f['HoTen'] . " (ID: " . $f['GiangVienID'] . ")</h2>";
    
    $db->query("SELECT svgv.*, sv.HoTen as TenSinhVien 
               FROM SinhVienGiangVienHuongDan svgv 
               JOIN SinhVien sv ON svgv.SinhVienID = sv.SinhVienID 
               WHERE svgv.GiangVienID = :gvID");
    $db->bind(':gvID', $f['GiangVienID']);
    $facAssignments = $db->resultSet();
    
    if (count($facAssignments) > 0) {
        echo "<p>Số sinh viên hướng dẫn: " . count($facAssignments) . "</p>";
        echo "<ul>";
        foreach ($facAssignments as $a) {
            echo "<li>" . $a['TenSinhVien'] . " (SV ID: " . $a['SinhVienID'] . ")</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>Không có sinh viên hướng dẫn</p>";
    }
}
?>