<?php
// Initialize the application
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/utils/Session.php';

// Start the session
Session::init();

// Destroy the session
Session::destroy();

// Redirect to login page
header('Location: login.php');
exit;
?>
