<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../classes/Thesis.php';

header('Content-Type: application/json');

// Khởi tạo đối tượng Database (sửa dòng này)
$db = new Database();

// Khởi tạo đối tượng Thesis (sửa dòng này, không cần truyền tham số)
$thesis = new Thesis();

$requestMethod = $_SERVER['REQUEST_METHOD'];

switch ($requestMethod) {
    case 'GET':
        if (isset($_GET['id'])) {
            $thesis->id = $_GET['id'];
            $thesis->read();
            if ($thesis->title) {
                echo json_encode([
                    'id' => $thesis->id,
                    'title' => $thesis->title,
                    'description' => $thesis->description,
                    'status' => $thesis->status,
                ]);
            } else {
                echo json_encode(['message' => 'Thesis not found.']);
            }
        } else {
            $result = $thesis->readAll(); // Sửa dòng này
            $thesisArr = [];
            foreach ($result as $row) { // Sửa dòng này để phù hợp với kết quả từ readAll()
                $thesisArr[] = [
                    'id' => $row['id'],
                    'title' => $row['title'],
                    'description' => $row['description'],
                    'status' => $row['status'],
                ];
            }
            echo json_encode($thesisArr);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"));
        if (!empty($data->title) && !empty($data->description)) {
            $thesis->title = $data->title;
            $thesis->description = $data->description;
            $thesis->status = $data->status ?? 'proposed';
            if ($thesis->create()) {
                echo json_encode(['message' => 'Thesis created successfully.']);
            } else {
                echo json_encode(['message' => 'Unable to create thesis.']);
            }
        } else {
            echo json_encode(['message' => 'Incomplete data.']);
        }
        break;

    case 'PUT':
        $data = json_decode(file_get_contents("php://input"));
        if (!empty($data->id) && !empty($data->title) && !empty($data->description)) {
            $thesis->id = $data->id;
            $thesis->title = $data->title;
            $thesis->description = $data->description;
            $thesis->status = $data->status ?? 'proposed';
            if ($thesis->update()) {
                echo json_encode(['message' => 'Thesis updated successfully.']);
            } else {
                echo json_encode(['message' => 'Unable to update thesis.']);
            }
        } else {
            echo json_encode(['message' => 'Incomplete data.']);
        }
        break;

    case 'DELETE':
        if (isset($_GET['id'])) {
            $thesis->id = $_GET['id'];
            if ($thesis->delete()) {
                echo json_encode(['message' => 'Thesis deleted successfully.']);
            } else {
                echo json_encode(['message' => 'Unable to delete thesis.']);
            }
        } else {
            echo json_encode(['message' => 'Thesis ID not provided.']);
        }
        break;

    default:
        echo json_encode(['message' => 'Invalid request method.']);
        break;
}
?>