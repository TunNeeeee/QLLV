<?php
// functions.php - Utility functions for the Thesis Advisor Management System

/**
 * Redirect to a specified URL
 *
 * @param string $url
 * @return void
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * Sanitize input data
 *
 * @param mixed $data
 * @return mixed
 */
function sanitize($data) {
    return htmlspecialchars(trim($data));
}

/**
 * Flash message for one-time use
 *
 * @param string $message
 * @param string $type
 * @return void
 */
function flashMessage($message, $type = 'success') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

/**
 * Get flash message
 *
 * @return array
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'];
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

/**
 * Check if user is logged in
 *
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Get the current user's role
 *
 * @return string|null
 */
function getUserRole() {
    return isLoggedIn() ? $_SESSION['role'] : null;
}

/**
 * Generate a random token for password reset
 *
 * @return string
 */
function generateToken() {
    return bin2hex(random_bytes(16));
}

/**
 * Validate email format
 *
 * @param string $email
 * @return bool
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}
?>