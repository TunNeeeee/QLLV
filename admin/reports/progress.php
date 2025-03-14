<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../classes/Progress.php';
require_once '../../includes/header.php';
require_once '../../includes/admin-menu.php';

$db = new Database();
$progress = new Progress($db);

// Fetch progress reports
$reports = $progress->getAllReports();

?>

<div class="container">
    <h2 class="mt-4">Thesis Progress Reports</h2>
    <table class="table table-bordered mt-3">
        <thead>
            <tr>
                <th>ID</th>
                <th>Student Name</th>
                <th>Thesis Title</th>
                <th>Progress</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($reports): ?>
                <?php foreach ($reports as $report): ?>
                    <tr>
                        <td><?php echo $report['ProgressID']; ?></td>
                        <td><?php echo $report['StudentName']; ?></td>
                        <td><?php echo $report['ThesisTitle']; ?></td>
                        <td><?php echo $report['Progress']; ?></td>
                        <td><?php echo date('d/m/Y', strtotime($report['Date'])); ?></td>
                        <td>
                            <a href="edit.php?id=<?php echo $report['ProgressID']; ?>" class="btn btn-warning btn-sm">Edit</a>
                            <a href="delete.php?id=<?php echo $report['ProgressID']; ?>" class="btn btn-danger btn-sm">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center">No progress reports found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
require_once '../../includes/footer.php';
?>