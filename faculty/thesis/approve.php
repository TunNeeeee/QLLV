<?php
session_start();
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../classes/Thesis.php';

$thesis = new Thesis();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $thesisId = $_POST['thesis_id'];
    $status = 'approved'; // Set the status to approved

    // Approve the thesis
    if ($thesis->updateThesisStatus($thesisId, $status)) {
        $_SESSION['success'] = 'Thesis approved successfully.';
    } else {
        $_SESSION['error'] = 'Failed to approve thesis. Please try again.';
    }

    header('Location: index.php'); // Redirect to the thesis index page
    exit;
}

// Fetch thesis details for approval
if (isset($_GET['id'])) {
    $thesisId = $_GET['id'];
    $thesisDetails = $thesis->getThesisById($thesisId);
} else {
    $_SESSION['error'] = 'Invalid thesis ID.';
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approve Thesis - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/bootstrap.custom.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    <?php include '../../includes/faculty-menu.php'; ?>

    <div class="container mt-5">
        <h2>Approve Thesis</h2>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="approve.php">
            <input type="hidden" name="thesis_id" value="<?php echo $thesisDetails['DeTaiID']; ?>">
            <div class="mb-3">
                <label for="title" class="form-label">Thesis Title</label>
                <input type="text" class="form-control" id="title" name="title" value="<?php echo $thesisDetails['TenDeTai']; ?>" readonly>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="4" readonly><?php echo $thesisDetails['MoTa']; ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Approve Thesis</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>

    <?php include '../../includes/footer.php'; ?>
    <script src="../../assets/js/main.js"></script>
</body>
</html>