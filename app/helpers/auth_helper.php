<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * Authentication Helper Functions
 */

/**
 * Check if user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Get current user data
 */
function get_current_user_data() {
    if (!is_logged_in()) {
        return null;
    }
    
    $user_data = [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'],
        'email' => $_SESSION['user_email'],
        'role' => $_SESSION['user_role'],
        'student_id' => $_SESSION['student_id'] ?? null,
        'student_photo' => $_SESSION['student_photo'] ?? null
    ];
    
    
    return $user_data;
}

/**
 * Check if user has specific role
 */
function has_role($role) {
    $user = get_current_user_data();
    return $user && $user['role'] === $role;
}

/**
 * Check if user is admin
 */
function is_admin() {
    return has_role('admin');
}

/**
 * Check if user is student
 */
function is_student() {
    return has_role('student');
}

/**
 * Require authentication
 */
function require_auth() {
    if (!is_logged_in()) {
        redirect('auth/login');
    }
}

/**
 * Require specific role
 */
function require_role($role) {
    require_auth();
    if (!has_role($role)) {
        show_auth_error('Access denied. Insufficient permissions.');
    }
}

/**
 * Require admin access
 */
function require_admin() {
    require_role('admin');
}

/**
 * Generate random filename for uploads (using framework's random_string)
 */
function generate_upload_filename($extension) {
    return random_string('alnum', 16) . '_' . time() . '.' . $extension;
}

/**
 * Get file extension from filename
 */
function get_file_extension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

/**
 * Check if file is image
 */
function is_image_file($filename) {
    $extension = get_file_extension($filename);
    $image_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    return in_array($extension, $image_extensions);
}

/**
 * Format file size
 */
function format_file_size($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 2) . ' ' . $units[$pow];
}

/**
 * Show custom error page (using framework's show_error)
 */
function show_auth_error($message, $code = 500) {
    show_error('Authentication Error', $message, 'error_general', $code);
}

/**
 * Log authentication error (using framework's logging)
 */
function log_auth_error($message, $context = []) {
    $log_message = 'AUTH: ' . $message;
    if (!empty($context)) {
        $log_message .= ' - Context: ' . json_encode($context);
    }
    error_log($log_message);
}
