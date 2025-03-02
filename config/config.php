<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'exam_management');

// Application configuration
define('BASE_URL', 'http://localhost/new_exam_php');
define('SITE_NAME', 'Exam Management System');

// File upload configuration
define('UPLOAD_DIR', $_SERVER['DOCUMENT_ROOT'] . '/new_exam_php/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// Session configuration
define('SESSION_NAME', 'exam_management_session');
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
?>
