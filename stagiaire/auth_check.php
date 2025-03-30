<?php
// Include required files
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/Session.php';

// Start session if not already started
Session::init();

// Check if user is not logged in
if (!Session::isLoggedIn()) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

// Check if user is not a stagiaire
$role = Session::get('role');
if ($role !== 'stagiaire') {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}
?> 