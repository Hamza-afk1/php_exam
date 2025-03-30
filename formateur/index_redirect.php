<?php
// Include required files
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/Session.php';

// Start the session
Session::init();

// Check login status - redirect to login page if not logged in
if (!Session::isLoggedIn()) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

// Check if user is formateur - redirect to main index if not formateur
if (Session::get('role') !== 'formateur') {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// Redirect to the dashboard
header('Location: ' . BASE_URL . '/formateur/dashboard.php');
exit;
?> 