<nav class="sidebar">
    <div class="sidebar-header">
        <a href="dashboard.php" class="sidebar-brand">
            <i class="fas fa-graduation-cap me-2"></i>
            <span><?php echo SITE_NAME; ?></span>
        </a>
    </div>
    
    <div class="sidebar-user">
        <div class="sidebar-user-img">
            <?php echo strtoupper(substr($facultyDetails['HoTen'] ?? 'G', 0, 1)); ?>
        </div>
        <div class="sidebar-user-info">
            <span class="sidebar-user-name"><?php echo htmlspecialchars($facultyDetails['HoTen'] ?? 'Giảng viên'); ?></span>
            <span class="sidebar-user-role">Giảng viên hướng dẫn</span>
        </div>
    </div>
    
    <ul class="sidebar-menu">
        <li><a href="dashboard.php" <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'class="active"' : ''; ?>>
            <i class="fas fa-tachometer-alt"></i> <span>Tổng quan</span>
        </a></li>
        <li><a href="assigned-students.php" <?php echo basename($_SERVER['PHP_SELF']) == 'assigned-students.php' ? 'class="active"' : ''; ?>>
            <i class="fas fa-users"></i> <span>Sinh viên được phân công</span>
        </a></li>
        <li><a href="propose-thesis.php" <?php echo basename($_SERVER['PHP_SELF']) == 'propose-thesis.php' ? 'class="active"' : ''; ?>>
            <i class="fas fa-lightbulb"></i> <span>Đề xuất đề tài</span>
        </a></li>
        <li><a href="thesis-list.php" <?php echo basename($_SERVER['PHP_SELF']) == 'thesis-list.php' ? 'class="active"' : ''; ?>>
            <i class="fas fa-book"></i> <span>Danh sách đề tài</span>
        </a></li>
        <li><a href="meetings.php" <?php echo basename($_SERVER['PHP_SELF']) == 'meetings.php' ? 'class="active"' : ''; ?>>
            <i class="fas fa-calendar-alt"></i> <span>Lịch gặp sinh viên</span>
        </a></li>
        <li><a href="profile.php" <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'class="active"' : ''; ?>>
            <i class="fas fa-user-circle"></i> <span>Hồ sơ cá nhân</span>
        </a></li>
        <li><a href="../auth/logout.php">
            <i class="fas fa-sign-out-alt"></i> <span>Đăng xuất</span>
        </a></li>
    </ul>
</nav>

<style>
    .sidebar {
        width: 250px;
        background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
        color: #fff;
        position: fixed;
        height: 100%;
        z-index: 100;
        transition: all 0.3s;
    }
    
    .sidebar-header {
        padding: 1.5rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .sidebar-brand {
        color: white;
        text-decoration: none;
        font-weight: 700;
        font-size: 1.25rem;
        display: flex;
        align-items: center;
    }
    
    .sidebar-brand:hover {
        color: white;
        opacity: 0.9;
    }
    
    .sidebar-user {
        display: flex;
        align-items: center;
        padding: 1.5rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .sidebar-user-img {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        background-color: rgba(255, 255, 255, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 1rem;
        font-size: 1.25rem;
        font-weight: 600;
        color: white;
    }
    
    .sidebar-user-name {
        font-weight: 600;
        margin-bottom: 0.25rem;
        display: block;
    }
    
    .sidebar-user-role {
        font-size: 0.75rem;
        color: rgba(255, 255, 255, 0.7);
    }
    
    .sidebar-menu {
        list-style: none;
        padding: 1rem 0;
        margin: 0;
    }
    
    .sidebar-menu li {
        margin-bottom: 0.25rem;
    }
    
    .sidebar-menu a {
        padding: 0.85rem 1.5rem;
        display: flex;
        align-items: center;
        color: rgba(255, 255, 255, 0.7);
        text-decoration: none;
        transition: all 0.3s;
        border-radius: 0.25rem;
        margin: 0 0.5rem;
    }
    
    .sidebar-menu a:hover,
    .sidebar-menu a.active {
        color: white;
        background-color: rgba(255, 255, 255, 0.1);
    }
    
    .sidebar-menu a i {
        margin-right: 0.75rem;
        font-size: 1rem;
        width: 20px;
        text-align: center;
    }
    
    @media (max-width: 991.98px) {
        .sidebar {
            width: 70px;
        }
        
        .sidebar-brand span,
        .sidebar-user-info,
        .sidebar-menu a span {
            display: none;
        }
        
        .sidebar-menu a i {
            margin-right: 0;
        }
        
        .main-content {
            margin-left: 70px;
        }
    }
</style>