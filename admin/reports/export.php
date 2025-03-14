<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

session_start();

// Check if user is logged in and has admin role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit;
}

// Fetch data for export
$db = new Database();
$db->query("SELECT sv.HoTen AS StudentName, gv.HoTen AS FacultyName, dt.TenDeTai AS ThesisTitle, svgv.TrangThai AS Status
            FROM SinhVienGiangVienHuongDan svgv
            JOIN SinhVien sv ON svgv.SinhVienID = sv.SinhVienID
            JOIN GiangVien gv ON svgv.GiangVienID = gv.GiangVienID
            JOIN DeTai dt ON svgv.DeTaiID = dt.DeTaiID");

$results = $db->resultSet();

// Prepare CSV file for download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="thesis_report.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['Student Name', 'Faculty Name', 'Thesis Title', 'Status']); // CSV header

foreach ($results as $row) {
    fputcsv($output, $row); // Write each row to CSV
}

fclose($output);
exit;
?>