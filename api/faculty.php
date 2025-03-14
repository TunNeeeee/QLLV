<?php
require_once '../config/database.php';
require_once '../classes/Faculty.php';

header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();

$faculty = new Faculty($db);

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            $faculty->id = $_GET['id'];
            $faculty->read();
            if ($faculty->name) {
                echo json_encode([
                    'id' => $faculty->id,
                    'name' => $faculty->name,
                    'email' => $faculty->email,
                    'department' => $faculty->department,
                ]);
            } else {
                echo json_encode(['message' => 'Faculty not found.']);
            }
        } else {
            $stmt = $faculty->readAll();
            $facultyList = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $facultyList[] = [
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'email' => $row['email'],
                    'department' => $row['department'],
                ];
            }
            echo json_encode($facultyList);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"));
        if (!empty($data->name) && !empty($data->email) && !empty($data->department)) {
            $faculty->name = $data->name;
            $faculty->email = $data->email;
            $faculty->department = $data->department;

            if ($faculty->create()) {
                echo json_encode(['message' => 'Faculty member created successfully.']);
            } else {
                echo json_encode(['message' => 'Unable to create faculty member.']);
            }
        } else {
            echo json_encode(['message' => 'Incomplete data.']);
        }
        break;

    case 'PUT':
        $data = json_decode(file_get_contents("php://input"));
        if (!empty($data->id) && !empty($data->name) && !empty($data->email) && !empty($data->department)) {
            $faculty->id = $data->id;
            $faculty->name = $data->name;
            $faculty->email = $data->email;
            $faculty->department = $data->department;

            if ($faculty->update()) {
                echo json_encode(['message' => 'Faculty member updated successfully.']);
            } else {
                echo json_encode(['message' => 'Unable to update faculty member.']);
            }
        } else {
            echo json_encode(['message' => 'Incomplete data.']);
        }
        break;

    case 'DELETE':
        if (isset($_GET['id'])) {
            $faculty->id = $_GET['id'];
            if ($faculty->delete()) {
                echo json_encode(['message' => 'Faculty member deleted successfully.']);
            } else {
                echo json_encode(['message' => 'Unable to delete faculty member.']);
            }
        } else {
            echo json_encode(['message' => 'No faculty ID provided.']);
        }
        break;

    default:
        echo json_encode(['message' => 'Invalid request method.']);
        break;
}
?>