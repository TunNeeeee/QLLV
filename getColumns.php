<?php
require_once 'config/config.php';
require_once 'config/database.php';

// Temporarily disable output
ob_start();
require_once 'classes/Faculty.php';
ob_end_clean();

$db = new Database();
$faculty = new Faculty($db);

// Check DeTai table structure
echo "DeTai Table Structure:\n";
$db->query('SHOW COLUMNS FROM DeTai');
$columns = $db->resultSet();
print_r($columns);

// Look for specific ID columns in DeTai
echo "\nChecking for GiangVienID/id_giangvien in DeTai:\n";
$db->query("SHOW COLUMNS FROM DeTai LIKE 'GiangVienID'");
$result = $db->rowCount();
echo "GiangVienID exists: " . ($result > 0 ? "Yes" : "No") . "\n";

$db->query("SHOW COLUMNS FROM DeTai LIKE 'id_giangvien'");
$result = $db->rowCount();
echo "id_giangvien exists: " . ($result > 0 ? "Yes" : "No") . "\n";

// Test the Faculty->getTheses method
echo "\nTesting Faculty->getTheses method:\n";
try {
    $theses = $faculty->getTheses(1);
    echo "Method successful. Found " . count($theses) . " theses.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 