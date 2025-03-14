<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../classes/Progress.php';
require_once '../../includes/header.php';
require_once '../../includes/faculty-menu.php';

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'faculty') {
    header('Location: ../../auth/login.php');
    exit;
}

$db = new Database();
$progress = new Progress($db);

// Fetch progress reports for the logged-in faculty member
$facultyId = $_SESSION['profile_id'];
$reports = $progress->getReportsByFaculty($facultyId);
?>

<div class="container">
    <h2 class="mt-4">Progress Reports</h2>
    <a href="create.php" class="btn btn-primary mb-3">Add New Progress Report</a>
    
    <?php if (count($reports) > 0): ?>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Student Name</th>
                    <th>Thesis Title</th>
                    <th>Progress</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reports as $report): ?>
                    <tr>
                        <td><?php echo $report['ProgressID']; ?></td>
                        <td><?php echo $report['StudentName']; ?></td>
                        <td><?php echo $report['ThesisTitle']; ?></td>
                        <td><?php echo $report['Progress']; ?></td>
                        <td>
                            <a href="edit.php?id=<?php echo $report['ProgressID']; ?>" class="btn btn-warning btn-sm">Edit</a>
                            <a href="delete.php?id=<?php echo $report['ProgressID']; ?>" class="btn btn-danger btn-sm">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="alert alert-info">No progress reports found.</div>
    <?php endif; ?>
</div>

<?php require_once '../../includes/footer.php'; ?>