<?php
require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $userId = $_POST['user_id'];

    if (!empty($userId)) {
        $db = new Database();
        $conn = $db->connect();

        $query = "DELETE FROM Users WHERE UserID = :user_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);

        if ($stmt->execute()) {
            header('Location: index.php?message=User deleted successfully');
            exit;
        } else {
            header('Location: index.php?error=Failed to delete user');
            exit;
        }
    } else {
        header('Location: index.php?error=Invalid user ID');
        exit;
    }
} else {
    header('Location: index.php');
    exit;
}
?>