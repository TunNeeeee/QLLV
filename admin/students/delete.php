<?php
require_once '../../config/database.php';

if (isset($_GET['id'])) {
    $studentId = $_GET['id'];

    // Create a new database connection
    $database = new Database();
    $db = $database->getConnection();

    // Prepare the delete statement
    $query = "DELETE FROM SinhVien WHERE SinhVienID = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $studentId);

    // Execute the statement
    if ($stmt->execute()) {
        header("Location: index.php?message=Student deleted successfully");
        exit();
    } else {
        header("Location: index.php?error=Unable to delete student");
        exit();
    }
} else {
    header("Location: index.php?error=Invalid request");
    exit();
}
?>