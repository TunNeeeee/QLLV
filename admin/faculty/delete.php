<?php
require_once '../../config/database.php';

if (isset($_GET['id'])) {
    $facultyId = $_GET['id'];

    // Create a new database connection
    $database = new Database();
    $db = $database->getConnection();

    // Prepare the delete statement
    $query = "DELETE FROM GiangVien WHERE GiangVienID = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $facultyId);

    if ($stmt->execute()) {
        // Redirect to the faculty index page with a success message
        header("Location: index.php?message=Faculty member deleted successfully.");
        exit();
    } else {
        // Redirect to the faculty index page with an error message
        header("Location: index.php?message=Unable to delete faculty member.");
        exit();
    }
} else {
    // Redirect to the faculty index page if no ID is provided
    header("Location: index.php");
    exit();
}
?>