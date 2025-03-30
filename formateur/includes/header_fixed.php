<?php
// Include authentication check
require_once __DIR__ . '/../auth_check.php';

// Get user data - we know user is authenticated at this point
$username = Session::get('username');
$email = Session::get('email');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FFFFFF">
    <meta name="color-scheme" content="light dark">
    <title>Formateur Dashboard - <?php echo SITE_NAME; ?></title>
    
    <!-- CSS Styles -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/formateur/includes/apple-theme.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/formateur/includes/layout-fix.css" rel="stylesheet">
    
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "SF Pro Text", "Helvetica Neue", Arial, sans-serif;
            background-color: var(--bg-primary);
            color: var(--text-primary);
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        
        .page-container {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }
        
        /* Top Navbar */
        .top-navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 56px;
            background-color: var(--bg-secondary);
            display: flex;
            align-items: center;
            padding: 0 1rem;
            box-shadow: 0 1px 10px rgba(0, 0, 0, 0.1);
            z-index: 1030;
        }
        
        .navbar-brand {
            font-weight: 600;
            color: var(--text-primary);
            text-decoration: none;
            display: flex;
            align-items: center;
        }
        
        .navbar-brand i {
            margin-right: 0.5rem;
        }
        
        .navbar-brand:hover {
            color: var(--apple-blue);
            text-decoration: none;
        }
        
        .toggle-sidebar {
            display: none;
            background: transparent;
            border: none;
            cursor: pointer;
            color: var(--text-primary);
            font-size: 1.25rem;
            padding: 0.25rem;
            margin-right: 0.5rem;
        }
        
        .navbar-nav {
            margin-left: auto;
            display: flex;
            list-style: none;
            margin: 0 0 0 auto;
            padding: 0;
        }
        
        .navbar-nav .nav-item {
            margin-left: 0.5rem;
        }
        
        .navbar-nav .nav-link {
            color: var(--text-secondary);
            padding: 0.5rem;
            display: flex;
            align-items: center;
            text-decoration: none;
        }
        
        .navbar-nav .nav-link:hover {
            color: var(--apple-blue);
        }
        
        .dropdown-menu {
            position: absolute;
            right: 0;
            top: 100%;
            background-color: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            padding: 0.5rem 0;
            min-width: 10rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
            z-index: 1000;
            display: none;
        }
        
        .dropdown-item {
            display: block;
            padding: 0.5rem 1.5rem;
            color: var(--text-primary);
            text-decoration: none;
        }
        
        .dropdown-item:hover {
            background-color: var(--bg-tertiary);
            color: var(--text-primary);
            text-decoration: none;
        }
        
        .dropdown-divider {
            height: 0;
            margin: 0.5rem 0;
            overflow: hidden;
            border-top: 1px solid var(--border-color);
        }
        
        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 56px;
            left: 0;
            bottom: 0;
            width: 240px;
            background-color: var(--bg-secondary);
            overflow-y: auto;
            box-shadow: 1px 0 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            z-index: 100;
        }
        
        .sidebar-sticky {
            padding-top: 1rem;
        }
        
        .sidebar .nav {
            display: flex;
            flex-direction: column;
            padding-left: 0;
            margin-bottom: 0;
            list-style: none;
        }
        
        .sidebar .nav-item {
            margin: 0;
        }
        
        .sidebar .nav-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.25rem;
            color: var(--text-secondary);
            text-decoration: none;
            transition: all 0.2s ease;
        }
        
        .sidebar .nav-link i {
            width: 24px;
            margin-right: 0.75rem;
            text-align: center;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: var(--apple-blue);
            background-color: var(--bg-tertiary);
        }
        
        .sidebar .nav-link.active {
            font-weight: 600;
        }
        
        /* Main Content */
        .main-content {
            margin-left: 240px;
            padding: 72px 16px 16px;
            width: calc(100% - 240px);
            min-height: 100vh;
            transition: margin-left 0.3s ease, width 0.3s ease;
            position: relative;
            z-index: 10;
            overflow-y: auto;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .toggle-sidebar {
                display: block;
            }
            
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
            }
            
            body.sidebar-visible::after {
                content: '';
                position: fixed;
                top: 56px;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: rgba(0, 0, 0, 0.5);
                z-index: 99;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="top-navbar">
        <button class="toggle-sidebar" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>
        
        <a class="navbar-brand" href="<?php echo BASE_URL; ?>/formateur/dashboard.php">
            <i class="fas fa-graduation-cap"></i> <?php echo SITE_NAME; ?>
        </a>
        
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" href="#" id="themeToggle">
                    <i class="fas fa-moon"></i>
                </a>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link" href="#" id="userDropdown">
                    <i class="fas fa-user-circle mr-1"></i> <?php echo htmlspecialchars($username); ?>
                </a>
                <div class="dropdown-menu" id="userMenu">
                    <a class="dropdown-item" href="<?php echo BASE_URL; ?>/formateur/profile.php">
                        <i class="fas fa-user mr-2"></i> Profile
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="<?php echo BASE_URL; ?>/logout.php">
                        <i class="fas fa-sign-out-alt mr-2"></i> Sign Out
                    </a>
                </div>
            </li>
        </ul>
    </nav>

    <div class="page-container">
        <!-- Sidebar -->
        <nav class="sidebar" id="sidebar">
            <div class="sidebar-sticky">
                <ul class="nav">
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/formateur/dashboard.php">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'exams.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/formateur/exams.php">
                            <i class="fas fa-clipboard-list"></i> My Exams
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'questions.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/formateur/questions.php">
                            <i class="fas fa-question-circle"></i> Questions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'results.php' || basename($_SERVER['PHP_SELF']) == 'view_result.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/formateur/results.php">
                            <i class="fas fa-chart-bar"></i> Results
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/formateur/profile.php">
                            <i class="fas fa-user"></i> Profile
                        </a>
                    </li>
                    <li class="nav-item mt-4">
                        <a class="nav-link text-danger" href="<?php echo BASE_URL; ?>/logout.php">
                            <i class="fas fa-sign-out-alt"></i> Sign Out
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="main-content" id="mainContent"> 