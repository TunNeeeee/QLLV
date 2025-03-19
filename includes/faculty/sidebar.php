<?php
// Lấy thông tin giảng viên nếu chưa có
if (!isset($facultyDetails) && isset($userId)) {
    try {
        $db->query("SELECT * FROM GiangVien WHERE UserID = :userId");
        $db->bind(':userId', $userId);
        $facultyDetails = $db->single();
        $facultyId = $facultyDetails['GiangVienID'];
    } catch (PDOException $e) {
        // Bỏ qua lỗi
    }
}

// Xác định trang hiện tại nếu chưa được đặt
if (!isset($currentPage)) {
    $currentPage = '';
}
?>

<!-- Sidebar -->
<nav class="sidebar">
    <div class="sidebar-header">
        <a href="dashboard.php" class="sidebar-brand">
            <i class="fas fa-graduation-cap me-2"></i>
            <span><?php echo SITE_NAME ?? 'Hệ thống quản lý luận văn'; ?></span>
        </a>
    </div>
    
    <div class="sidebar-user">
        <div class="sidebar-user-img">
            <?php 
            if (isset($facultyDetails) && !empty($facultyDetails['Avatar'])): ?>
                <img src="<?php echo BASE_URL . $facultyDetails['Avatar']; ?>" alt="Avatar" class="rounded-circle w-100 h-100" style="object-fit: cover;">
            <?php else: ?>
                <?php echo isset($facultyDetails['HoTen']) ? strtoupper(substr($facultyDetails['HoTen'], 0, 1)) : 'GV'; ?>
            <?php endif; ?>
        </div>
        <div>
            <span class="sidebar-user-name"><?php echo isset($facultyDetails['HoTen']) ? $facultyDetails['HoTen'] : 'Giảng viên'; ?></span>
            <span class="sidebar-user-role">Giảng viên</span>
        </div>
    </div>
    
    <ul class="sidebar-menu">
        <li>
            <a href="dashboard.php" class="<?php echo $currentPage == 'dashboard' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i>
                <span>Trang chủ</span>
            </a>
        </li>
        <li>
            <a href="thesis-list.php" class="<?php echo $currentPage == 'thesis' ? 'active' : ''; ?>">
                <i class="fas fa-book"></i>
                <span>Đề tài luận văn</span>
            </a>
        </li>
        <li>
            <a href="students.php" class="<?php echo $currentPage == 'students' ? 'active' : ''; ?>">
                <i class="fas fa-user-graduate"></i>
                <span>Sinh viên</span>
            </a>
        </li>
        <li>
            <a href="assign-thesis.php" class="<?php echo $currentPage == 'assign' ? 'active' : ''; ?>">
                <i class="fas fa-tasks"></i>
                <span>Gán đề tài</span>
            </a>
        </li>
        <li>
            <a href="meetings.php" class="<?php echo $currentPage == 'meetings' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-alt"></i>
                <span>Lịch gặp</span>
            </a>
        </li>
        <li>
            <a href="messages.php" class="<?php echo $currentPage == 'messages' ? 'active' : ''; ?>">
                <i class="fas fa-envelope"></i>
                <span>Tin nhắn</span>
            </a>
        </li>
        <li>
            <a href="profile.php" class="<?php echo $currentPage == 'profile' ? 'active' : ''; ?>">
                <i class="fas fa-user"></i>
                <span>Hồ sơ</span>
            </a>
        </li>
        <li>
            <a href="../auth/logout.php">
                <i class="fas fa-sign-out-alt"></i>
                <span>Đăng xuất</span>
            </a>
        </li>
    </ul>
</nav>