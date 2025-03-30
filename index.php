<?php
/**
 * Main Entry Point - Redirects to Login Page
 * 
 * This file serves as the entry point for the Exam Management System
 * and redirects users to the login page.
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    // Redirect based on role
    if ($_SESSION['role'] == 'admin') {
        header("Location: admin/index.php");
        exit;
    } elseif ($_SESSION['role'] == 'formateur') {
        header("Location: formateur/dashboard.php");
        exit;
    } elseif ($_SESSION['role'] == 'student' || $_SESSION['role'] == 'stagiaire') {
        header("Location: stagiaire/dashboard.php");
        exit;
    }
}

// If not logged in or session invalid, redirect to login
header("Location: login.php");
exit;
?> 