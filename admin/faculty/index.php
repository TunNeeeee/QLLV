<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Create a new database connection
$db = new Database();
$conn = $db->connect();

// Fetch all faculty members
$query = "SELECT * FROM GiangVien";
$stmt = $conn->prepare($query);
$stmt->execute();
$facultyMembers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Include header and menu
include '../../includes/header.php';
include '../../includes/admin-menu.php';
?>

<div class="container-fluid py-4">
    <h2 class="mb-4">Faculty Members</h2>
    <a href="create.php" class="btn btn-primary mb-3">Add New Faculty Member</a>
    
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Code</th>
                <th>Name</th>
                <th>Position</th>
                <th>Department</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($facultyMembers as $faculty): ?>
                <tr>
                    <td><?php echo $faculty['GiangVienID']; ?></td>
                    <td><?php echo $faculty['MaGV']; ?></td>
                    <td><?php echo $faculty['HoTen']; ?></td>
                    <td><?php echo $faculty['ChucVu']; ?></td>
                    <td><?php echo $faculty['Khoa']; ?></td>
                    <td>
                        <a href="edit.php?id=<?php echo $faculty['GiangVienID']; ?>" class="btn btn-warning btn-sm">Edit</a>
                        <a href="delete.php?id=<?php echo $faculty['GiangVienID']; ?>" class="btn btn-danger btn-sm">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include '../../includes/footer.php'; ?>