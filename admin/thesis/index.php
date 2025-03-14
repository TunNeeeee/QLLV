<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../classes/Thesis.php';

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit;
}

$db = new Database();
$thesis = new Thesis($db);

$thesisList = $thesis->getAllThesis();

include '../../includes/header.php';
include '../../includes/admin-menu.php';
?>

<div class="container">
    <h1 class="mt-4">Thesis Topics</h1>
    <a href="create.php" class="btn btn-primary mb-3">Add New Thesis Topic</a>
    
    <?php if (count($thesisList) > 0): ?>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($thesisList as $thesisItem): ?>
                    <tr>
                        <td><?php echo $thesisItem['DeTaiID']; ?></td>
                        <td><?php echo $thesisItem['TenDeTai']; ?></td>
                        <td><?php echo $thesisItem['MoTa']; ?></td>
                        <td><?php echo ucfirst($thesisItem['TrangThai']); ?></td>
                        <td>
                            <a href="edit.php?id=<?php echo $thesisItem['DeTaiID']; ?>" class="btn btn-warning btn-sm">Edit</a>
                            <a href="delete.php?id=<?php echo $thesisItem['DeTaiID']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this thesis topic?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="alert alert-info">No thesis topics found.</div>
    <?php endif; ?>
</div>

<?php include '../../includes/footer.php'; ?>