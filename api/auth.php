<?php
require_once '../config/database.php';
require_once '../classes/Auth.php';

header('Content-Type: application/json');

$auth = new Auth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"));

    if (isset($data->username) && isset($data->password)) {
        $username = $data->username;
        $password = $data->password;

        if ($auth->login($username, $password)) {
            echo json_encode(['message' => 'Login successful', 'user' => $_SESSION]);
        } else {
            echo json_encode(['message' => 'Invalid username or password'], JSON_UNESCAPED_SLASHES);
        }
    } else {
        echo json_encode(['message' => 'Username and password are required'], JSON_UNESCAPED_SLASHES);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_SESSION['user_id'])) {
        echo json_encode(['message' => 'User is logged in', 'user' => $_SESSION]);
    } else {
        echo json_encode(['message' => 'User is not logged in'], JSON_UNESCAPED_SLASHES);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $auth->logout();
    echo json_encode(['message' => 'Logout successful']);
} else {
    echo json_encode(['message' => 'Method not allowed'], JSON_UNESCAPED_SLASHES);
}
?>