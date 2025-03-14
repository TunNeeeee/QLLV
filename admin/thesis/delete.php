<?php
require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $thesisId = $_POST['thesis_id'];

    if (!empty($thesisId)) {
        $db = new Database();
        $conn = $db->connect();

        $query = "DELETE FROM DeTai WHERE DeTaiID = :thesis_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':thesis_id', $thesisId);

        if ($stmt->execute()) {
            header('Location: index.php?message=Thesis deleted successfully');
            exit;
        } else {
            header('Location: index.php?error=Failed to delete thesis');
            exit;
        }
    } else {
        header('Location: index.php?error=Invalid thesis ID');
        exit;
    }
} else {
    header('Location: index.php');
    exit;
}
?>