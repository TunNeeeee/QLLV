<?php
// config.php - Configuration file for database connection and site settings

// Database connection settings
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'thesisManagementDB');

// Site settings
define('SITE_NAME', 'HUTECH - LUẬN VĂN SINH VIÊN');
define('BASE_URL', 'http://localhost/thesis-management-system/');
define('UPLOAD_DIR', 'uploads/');
define('SESSION_TIME', 3600); // Session time in seconds

// Set timezone
date_default_timezone_set('Asia/Ho_Chi_Minh');
?>