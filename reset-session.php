<?php
// Xóa tất cả cookie
if (isset($_SERVER['HTTP_COOKIE'])) {
    $cookies = explode(';', $_SERVER['HTTP_COOKIE']);
    foreach($cookies as $cookie) {
        $parts = explode('=', $cookie);
        $name = trim($parts[0]);
        setcookie($name, '', time()-1000);
        setcookie($name, '', time()-1000, '/');
    }
}

// Hủy session
session_start();
session_unset();
session_destroy();

// Hiển thị thông báo
echo "All cookies and sessions have been cleared. <a href='index.php'>Go back to homepage</a>";
?>