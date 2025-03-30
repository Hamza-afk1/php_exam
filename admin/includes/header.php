<?php
// Include required files
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../utils/Session.php';

// Check if user is admin
Session::checkAdmin();

// Get user data
$username = Session::get('username');
$email = Session::get('email');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/assets/css/dark-mode.css" rel="stylesheet">
    <style>
        :root {
            --apple-bg: #f5f5f7;
            --apple-card: #ffffff;
            --apple-text: #1d1d1f;
            --apple-text-secondary: #86868b;
            --apple-blue: #0071e3;
            --apple-green: #34c759;
            --apple-orange: #ff9500;
            --apple-border: rgba(0, 0, 0, 0.06);
            --apple-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: var(--apple-bg);
            color: var(--apple-text);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            padding-top: 60px;
            line-height: 1.5;
        }
        
        /* Navigation */
        .navbar {
            background-color: rgba(255, 255, 255, 0.8);
            backdrop-filter: saturate(180%) blur(20px);
            -webkit-backdrop-filter: saturate(180%) blur(20px);
            box-shadow: 0 1px 0 rgba(0, 0, 0, 0.1);
            padding: 0.5rem 1rem;
        }
        
        .navbar-brand {
            font-weight: 600;
            color: var(--apple-text) !important;
            font-size: 1.2rem;
        }
        
        /* Sidebar */
        .sidebar {
            background-color: #fff;
            border-right: 1px solid var(--apple-border);
            padding-top: 1rem;
            height: calc(100vh - 60px);
            position: sticky;
            top: 60px;
        }
        
        .sidebar .nav-link {
            color: var(--apple-text-secondary);
            border-radius: 8px;
            margin: 0.25rem 0.5rem;
            padding: 0.75rem 1rem;
            transition: all 0.2s ease;
        }
        
        .sidebar .nav-link:hover {
            background-color: rgba(0, 0, 0, 0.04);
            color: var(--apple-text);
        }
        
        .sidebar .nav-link.active {
            background-color: rgba(0, 113, 227, 0.1);
            color: var(--apple-blue);
            font-weight: 500;
        }
        
        .sidebar .nav-link i {
            color: var(--apple-text-secondary);
            transition: all 0.2s ease;
            width: 20px;
            text-align: center;
            margin-right: 0.5rem;
        }
        
        .sidebar .nav-link.active i {
            color: var(--apple-blue);
        }
        
        .sidebar-heading {
            color: var(--apple-text-secondary);
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
            padding: 0 1.5rem;
            margin-top: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        /* Cards */
        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            overflow: hidden;
            margin-bottom: 1.5rem;
            background-color: var(--apple-card);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        
        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.09);
        }
        
        .card-header {
            background-color: var(--apple-card);
            border-bottom: 1px solid var(--apple-border);
            font-weight: 600;
            padding: 1.25rem 1.5rem;
            font-size: 1rem;
            letter-spacing: -0.01em;
        }
        
        .card-body {
            padding: 1.75rem;
        }
        
        /* Welcome Section */
        .welcome-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 50%, #f5f7fa 100%);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            position: relative;
            overflow: hidden;
        }
        
        .welcome-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 40%;
            height: 100%;
            background: linear-gradient(135deg, rgba(255,255,255,0) 0%, rgba(255,255,255,0.8) 100%);
            z-index: 1;
        }
        
        .welcome-title {
            font-weight: 700;
            font-size: 1.85rem;
            margin-bottom: 0.5rem;
            color: var(--apple-text);
            letter-spacing: -0.02em;
            position: relative;
            z-index: 2;
        }
        
        .welcome-date {
            color: var(--apple-text-secondary);
            font-size: 1rem;
            margin-bottom: 0;
            position: relative;
            z-index: 2;
        }
        
        /* Buttons */
        .btn-primary {
            background-color: var(--apple-blue);
            border-color: var(--apple-blue);
            border-radius: 8px;
            font-weight: 500;
            padding: 0.5rem 1.25rem;
            transition: all 0.2s ease;
        }
        
        .btn-primary:hover {
            background-color: #0062cc;
            border-color: #0062cc;
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(0, 113, 227, 0.3);
        }
        
        .btn-light {
            background-color: rgba(0, 0, 0, 0.05);
            border-color: transparent;
            color: var(--apple-text);
            border-radius: 8px;
            font-weight: 500;
            padding: 0.5rem 1.25rem;
            transition: all 0.2s ease;
        }
        
        .btn-light:hover {
            background-color: rgba(0, 0, 0, 0.08);
            transform: translateY(-1px);
        }
        
        .btn-sm {
            font-size: 0.875rem;
            padding: 0.375rem 0.75rem;
        }
        
        .btn-outline-primary {
            color: var(--apple-blue);
            border-color: var(--apple-blue);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--apple-blue);
            color: white;
        }
        
        .btn-outline-secondary {
            color: var(--apple-text-secondary);
            border-color: rgba(0, 0, 0, 0.1);
        }
        
        .btn-outline-secondary:hover {
            background-color: rgba(0, 0, 0, 0.05);
            color: var(--apple-text);
            border-color: rgba(0, 0, 0, 0.1);
        }
        
        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        /* Status Indicators */
        .status-pill {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 0.75rem;
            background-color: rgba(52, 199, 89, 0.1);
            color: var(--apple-green);
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: currentColor;
            margin-right: 0.5rem;
        }
        
        /* Tables */
        .table {
            color: var(--apple-text);
            margin-bottom: 0;
        }
        
        .table th {
            font-weight: 600;
            color: var(--apple-text-secondary);
            border-top: none;
            padding: 1.2rem 1.5rem;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        
        .table td {
            vertical-align: middle;
            border-color: var(--apple-border);
            padding: 1.2rem 1.5rem;
            font-size: 0.95rem;
        }
        
        .table tbody tr {
            transition: background-color 0.2s ease;
        }
        
        .table tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.02);
        }
        
        .score-badge {
            border-radius: 20px;
            padding: 0.4rem 0.85rem;
            font-weight: 500;
            font-size: 0.85rem;
            letter-spacing: -0.01em;
            display: inline-flex;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s ease;
        }
        
        .score-badge:hover {
            transform: translateY(-1px);
        }
        
        .badge-success {
            background-color: rgba(52, 199, 89, 0.1);
            color: var(--apple-green);
        }
        
        .badge-danger {
            background-color: rgba(255, 59, 48, 0.1);
            color: #ff3b30;
        }
        
        /* Search */
        .search-box {
            position: relative;
            max-width: 300px;
        }
        
        .search-input {
            background-color: rgba(0, 0, 0, 0.03);
            border: none;
            border-radius: 8px;
            padding-left: 2.5rem;
            height: calc(1.5em + 0.75rem + 2px);
            transition: all 0.2s ease;
        }
        
        .search-input:focus {
            background-color: rgba(0, 0, 0, 0.06);
            box-shadow: none;
        }
        
        .search-icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--apple-text-secondary);
        }
        
        /* Form Controls */
        .form-control {
            border-radius: 8px;
            border: 1px solid var(--apple-border);
            padding: 0.5rem 0.75rem;
            transition: all 0.2s ease;
        }
        
        .form-control:focus {
            border-color: var(--apple-blue);
            box-shadow: 0 0 0 3px rgba(0, 113, 227, 0.15);
        }
        
        .form-group label {
            font-weight: 500;
            color: var(--apple-text);
            margin-bottom: 0.5rem;
        }
        
        /* Dark Mode */
        .dark-mode {
            --apple-bg: #121212;
            --apple-card: #1c1c1e;
            --apple-text: #f5f5f7;
            --apple-text-secondary: #98989d;
            --apple-blue: #0a84ff;
            --apple-green: #30d158;
            --apple-orange: #ff9f0a;
            --apple-border: rgba(255, 255, 255, 0.1);
            --apple-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }
        
        .dark-mode .navbar {
            background-color: rgba(28, 28, 30, 0.8);
        }
        
        .dark-mode .welcome-card {
            background: linear-gradient(135deg, #2c2c2e 0%, #1c1c1e 50%, #2c2c2e 100%);
        }
        
        .dark-mode .welcome-card::before {
            background: linear-gradient(135deg, rgba(28,28,30,0) 0%, rgba(28,28,30,0.8) 100%);
        }
        
        .dark-mode .card {
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }
        
        .dark-mode .card:hover {
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
        }
        
        .dark-mode .search-input {
            background-color: rgba(255, 255, 255, 0.08);
        }
        
        .dark-mode .search-input:focus {
            background-color: rgba(255, 255, 255, 0.12);
        }
        
        .dark-mode .btn-light {
            background-color: rgba(255, 255, 255, 0.08);
            color: var(--apple-text);
        }
        
        .dark-mode .btn-light:hover {
            background-color: rgba(255, 255, 255, 0.12);
        }
        
        .dark-mode .form-control {
            background-color: #2d2d2d;
            border-color: rgba(255, 255, 255, 0.1);
            color: var(--apple-text);
            -webkit-text-fill-color: var(--apple-text);
        }
        
        .dark-mode .form-control:focus {
            background-color: #363636;
            border-color: var(--apple-blue);
            box-shadow: 0 0 0 3px rgba(10, 132, 255, 0.3);
            color: var(--apple-text);
            -webkit-text-fill-color: var(--apple-text);
        }
        
        .dark-mode .form-control::placeholder {
            color: var(--apple-text-secondary);
            opacity: 0.7;
        }
        
        .dark-mode select.form-control {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23ffffff' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 16px 12px;
            padding-right: 2.5rem;
        }
        
        .dark-mode select.form-control option {
            background-color: #2d2d2d;
            color: var(--apple-text);
        }
        
        .dark-mode .system-info-item:hover,
        .dark-mode .table tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.03);
        }
        
        .dark-mode .badge-success {
            background-color: rgba(48, 209, 88, 0.15);
        }
        
        .dark-mode .badge-danger {
            background-color: rgba(255, 69, 58, 0.15);
        }
        
        /* Mobile Styles */
        @media (max-width: 767.98px) {
            .sidebar {
                position: fixed;
                top: 0;
                bottom: 0;
                left: -100%;
                z-index: 1000;
                transition: all 0.3s ease;
                box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
                width: 80%;
                max-width: 250px;
                margin-top: 60px;
                height: calc(100vh - 60px);
            }
            
            .sidebar.show {
                left: 0;
            }
            
            .sidebar-backdrop {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: rgba(0, 0, 0, 0.5);
                z-index: 999;
                display: none;
            }
            
            .sidebar-backdrop.show {
                display: block;
            }
            
            .navbar-toggler {
                padding: 0.25rem 0.5rem;
                border: none;
            }
        }
    </style>
</head>
<body class="admin-page">
    <nav class="navbar navbar-light fixed-top">
        <a class="navbar-brand" href="<?php echo BASE_URL; ?>/admin/index.php"><?php echo SITE_NAME; ?></a>
        <button class="navbar-toggler d-md-none" type="button" id="sidebarToggler">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="search-box d-none d-md-block ml-auto mr-3">
            <i class="fas fa-search search-icon"></i>
            <input class="form-control search-input" type="text" placeholder="Search..." aria-label="Search">
        </div>
        <ul class="navbar-nav flex-row">
            <li class="nav-item mr-3">
                <button id="dark-mode-toggle" class="btn btn-light btn-sm">
                    <i class="fas fa-moon mr-1"></i> Mode
                </button>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle text-body" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-user-circle mr-1"></i> <?php echo htmlspecialchars($username); ?>
                </a>
                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
                    <a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/settings.php">
                        <i class="fas fa-cog mr-2"></i> Settings
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="<?php echo BASE_URL; ?>/logout.php">
                        <i class="fas fa-sign-out-alt mr-2"></i> Logout
                    </a>
                </div>
            </li>
        </ul>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
            <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block sidebar">
                <div class="pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/admin/index.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/admin/users.php">
                                <i class="fas fa-users"></i> Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'exams.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/admin/exams.php">
                                <i class="fas fa-clipboard-list"></i> Exams
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'results.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/admin/results.php">
                                <i class="fas fa-chart-bar"></i> Results
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/admin/settings.php">
                                <i class="fas fa-cog"></i> Settings
                            </a>
                        </li>
                    </ul>

                    <div class="sidebar-heading">System</div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/logout.php">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4 pt-4 pb-4">
                <!-- Page content starts here -->

    <script src="../assets/js/dark-mode.js"></script>
