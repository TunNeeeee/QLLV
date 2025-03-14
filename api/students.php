<?php
require_once '../config/database.php';

class StudentsAPI {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getAllStudents() {
        $this->db->query("SELECT * FROM SinhVien");
        return $this->db->resultSet();
    }

    public function getStudentById($id) {
        $this->db->query("SELECT * FROM SinhVien WHERE SinhVienID = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    public function createStudent($data) {
        $this->db->query("INSERT INTO SinhVien (MaSV, HoTen, NgaySinh, GioiTinh, Khoa, NganhHoc, NienKhoa) VALUES (:maSv, :hoTen, :ngaySinh, :gioiTinh, :khoa, :nganhHoc, :nienKhoa)");
        $this->db->bind(':maSv', $data['maSv']);
        $this->db->bind(':hoTen', $data['hoTen']);
        $this->db->bind(':ngaySinh', $data['ngaySinh']);
        $this->db->bind(':gioiTinh', $data['gioiTinh']);
        $this->db->bind(':khoa', $data['khoa']);
        $this->db->bind(':nganhHoc', $data['nganhHoc']);
        $this->db->bind(':nienKhoa', $data['nienKhoa']);
        return $this->db->execute();
    }

    public function updateStudent($id, $data) {
        $this->db->query("UPDATE SinhVien SET MaSV = :maSv, HoTen = :hoTen, NgaySinh = :ngaySinh, GioiTinh = :gioiTinh, Khoa = :khoa, NganhHoc = :nganhHoc, NienKhoa = :nienKhoa WHERE SinhVienID = :id");
        $this->db->bind(':id', $id);
        $this->db->bind(':maSv', $data['maSv']);
        $this->db->bind(':hoTen', $data['hoTen']);
        $this->db->bind(':ngaySinh', $data['ngaySinh']);
        $this->db->bind(':gioiTinh', $data['gioiTinh']);
        $this->db->bind(':khoa', $data['khoa']);
        $this->db->bind(':nganhHoc', $data['nganhHoc']);
        $this->db->bind(':nienKhoa', $data['nienKhoa']);
        return $this->db->execute();
    }

    public function deleteStudent($id) {
        $this->db->query("DELETE FROM SinhVien WHERE SinhVienID = :id");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }
}

header("Content-Type: application/json");
$api = new StudentsAPI();

$requestMethod = $_SERVER['REQUEST_METHOD'];
switch ($requestMethod) {
    case 'GET':
        if (isset($_GET['id'])) {
            $student = $api->getStudentById($_GET['id']);
            echo json_encode($student);
        } else {
            $students = $api->getAllStudents();
            echo json_encode($students);
        }
        break;
    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);
        $result = $api->createStudent($data);
        echo json_encode(['success' => $result]);
        break;
    case 'PUT':
        $data = json_decode(file_get_contents("php://input"), true);
        $id = $_GET['id'];
        $result = $api->updateStudent($id, $data);
        echo json_encode(['success' => $result]);
        break;
    case 'DELETE':
        $id = $_GET['id'];
        $result = $api->deleteStudent($id);
        echo json_encode(['success' => $result]);
        break;
    default:
        echo json_encode(['error' => 'Invalid request method']);
        break;
}
?>