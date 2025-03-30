<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/utils/Session.php';

// Initialize session
Session::init();

// Destroy session
Session::destroy();

// Redirect to login page
header('Location: ' . BASE_URL . '/login.php');
exit;
?> 