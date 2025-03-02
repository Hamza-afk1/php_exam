<?php
// Initialize the application
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/utils/Session.php';

// Start the session
Session::init();

// Redirect based on user role if already logged in
if (Session::isLoggedIn()) {
    $userRole = Session::get('user_role');
    
    switch ($userRole) {
        case 'admin':
            header('Location: admin/dashboard.php');
            exit;
        case 'formateur':
            header('Location: formateur/dashboard.php');
            exit;
        case 'stagiaire':
            header('Location: stagiaire/dashboard.php');
            exit;
        default:
            // If role is not recognized, log them out
            Session::destroy();
    }
}

// Redirect to login page
header('Location: login.php');
exit;
?>
