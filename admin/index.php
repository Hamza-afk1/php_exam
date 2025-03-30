<?php
// Turn on error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load configuration and session
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/Session.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Exam.php';
require_once __DIR__ . '/../models/Result.php';

// Start the session
Session::init();

// Check login status
if (!Session::isLoggedIn()) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

// Check if user is admin
if (Session::get('role') !== 'admin') {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// Get user data
$username = Session::get('username');
$email = Session::get('email');

// Get statistics (safely)
try {
    $userModel = new User();
    $examModel = new Exam();
    $resultModel = new Result();
    
    $formateurCount = count($userModel->getUsersByRole('formateur'));
    $stagiaireCount = count($userModel->getUsersByRole('stagiaire'));
    $examCount = count($examModel->getAll());
    
    // Get recent results safely
    $recentResults = [];
    try {
        // Use the proper getRecentResults method or execute a custom query
        if (method_exists($resultModel, 'getRecentResults')) {
            $recentResults = $resultModel->getRecentResults(5);
        } else {
            // Create a custom query that doesn't directly access protected properties
            $db = new Database();
            $query = "SELECT r.*, e.name as exam_name, u.username as stagiaire_name 
                    FROM results r
                    JOIN exams e ON r.exam_id = e.id
                    JOIN users u ON r.stagiaire_id = u.id
                    ORDER BY r.created_at DESC LIMIT 5";
            $stmt = $db->prepare($query);
            $db->execute($stmt);
            $recentResults = $db->resultSet($stmt);
        }
    } catch (Exception $e) {
        // Silently fail and continue with empty results
    }
} catch (Exception $e) {
    // Just continue with default values if there's an error
    $formateurCount = 0;
    $stagiaireCount = 0;
    $examCount = 0;
    $recentResults = [];
}

// Get date for dashboard
$today = date('l, F j, Y');
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
    <link href="../assets/css/dark-mode.css" rel="stylesheet">
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
        
        /* Stats */
        .stat-card {
            padding: 2rem;
            height: 100%;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        
        .stat-card::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, 
                rgba(0, 122, 255, 0.7) 0%, 
                rgba(0, 122, 255, 0.3) 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .card:hover .stat-card::after {
            opacity: 1;
        }
        
        .stat-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 56px;
            height: 56px;
            border-radius: 16px;
            margin-bottom: 1.25rem;
            font-size: 1.4rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card:hover .stat-icon {
            transform: scale(1.05);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.12);
        }
        
        .icon-formateurs {
            background: linear-gradient(135deg, rgba(0, 122, 255, 0.2) 0%, rgba(0, 122, 255, 0.1) 100%);
            color: #007aff;
        }
        
        .icon-stagiaires {
            background: linear-gradient(135deg, rgba(52, 199, 89, 0.2) 0%, rgba(52, 199, 89, 0.1) 100%);
            color: var(--apple-green);
        }
        
        .icon-exams {
            background: linear-gradient(135deg, rgba(255, 149, 0, 0.2) 0%, rgba(255, 149, 0, 0.1) 100%);
            color: var(--apple-orange);
        }
        
        .stat-label {
            color: var(--apple-text-secondary);
            margin-bottom: 0.5rem;
            font-weight: 500;
            font-size: 0.9rem;
            letter-spacing: 0.02em;
            text-transform: uppercase;
        }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1.25rem;
            color: var(--apple-text);
            letter-spacing: -0.03em;
            line-height: 1;
        }
        
        .stat-card .btn {
            margin-top: auto;
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
        
        /* Quick Actions */
        .action-list-item {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--apple-border);
            transition: all 0.25s ease;
            position: relative;
            overflow: hidden;
        }
        
        .action-list-item:last-child {
            border-bottom: none;
        }
        
        .action-list-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background-color: var(--apple-blue);
            opacity: 0;
            transform: translateX(-4px);
            transition: opacity 0.3s ease, transform 0.3s ease;
        }
        
        .action-list-item:hover {
            background-color: rgba(0, 0, 0, 0.02);
        }
        
        .action-list-item:hover::before {
            opacity: 1;
            transform: translateX(0);
        }
        
        .action-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            font-size: 1.25rem;
            transition: transform 0.3s ease;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }
        
        .action-list-item:hover .action-icon {
            transform: scale(1.05);
        }
        
        .action-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
            color: var(--apple-text);
            font-size: 1.05rem;
        }
        
        .action-subtitle {
            color: var(--apple-text-secondary);
            font-size: 0.9rem;
        }
        
        .action-arrow {
            opacity: 0.5;
            transition: transform 0.3s ease, opacity 0.3s ease;
        }
        
        .action-list-item:hover .action-arrow {
            opacity: 0.8;
            transform: translateX(3px);
        }
        
        /* System Info */
        .system-info-item {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
            padding: 0.75rem;
            border-radius: 12px;
            transition: background-color 0.2s ease;
        }
        
        .system-info-item:hover {
            background-color: rgba(0, 0, 0, 0.02);
        }
        
        .system-info-item:last-child {
            margin-bottom: 0;
        }
        
        .info-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            font-size: 1.1rem;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease;
        }
        
        .system-info-item:hover .info-icon {
            transform: scale(1.05);
        }
        
        .info-label {
            color: var(--apple-text-secondary);
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 0.25rem;
        }
        
        .info-value {
            font-weight: 600;
            color: var(--apple-text);
            font-size: 1rem;
        }
        
        /* Recent Results Table */
        .table-card {
            overflow: hidden;
        }
        
        .table-card .card-header {
            position: relative;
            overflow: hidden;
        }
        
        .table-card .card-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, var(--apple-border) 0%, rgba(0,0,0,0.01) 100%);
        }
        
        .card-footer {
            background-color: var(--apple-card);
            border-top: 1px solid var(--apple-border);
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
        
        .dark-mode .system-info-item:hover,
        .dark-mode .table tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.03);
        }
        
        .dark-mode .action-list-item:hover {
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
            
            .welcome-card {
                padding: 1.25rem;
            }
            
            .welcome-title {
                font-size: 1.5rem;
            }
            
            .card-header, .card-body, .table th, .table td {
                padding: 1rem;
            }
            
            .action-list-item {
                padding: 0.875rem 1rem;
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
                            <a class="nav-link active" href="<?php echo BASE_URL; ?>/admin/index.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/admin/users.php">
                                <i class="fas fa-users"></i> Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/admin/exams.php">
                                <i class="fas fa-clipboard-list"></i> Exams
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/admin/results.php">
                                <i class="fas fa-chart-bar"></i> Results
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/admin/settings.php">
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
                <div class="welcome-card">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <div>
                            <h1 class="welcome-title">Dashboard</h1>
                            <p class="welcome-date"><?php echo $today; ?></p>
                        </div>
                        <div class="d-flex flex-wrap mt-3 mt-md-0">
                            <div class="status-pill mr-2 mb-2 mb-md-0">
                                <span class="status-dot"></span> System Online
                            </div>
                            <div class="d-flex">
                                <a href="users.php?action=add" class="btn btn-primary action-btn mr-2">
                                    <i class="fas fa-user-plus"></i> Add User
                                </a>
                                <a href="exams.php?action=add" class="btn btn-light action-btn">
                                    <i class="fas fa-plus"></i> New Exam
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row">
                    <div class="col-md-4 mb-4">
                        <div class="card">
                            <div class="stat-card">
                                <div class="stat-icon icon-formateurs">
                                    <i class="fas fa-chalkboard-teacher"></i>
                                </div>
                                <div class="stat-label">Formateurs</div>
                                <div class="stat-value"><?php echo $formateurCount; ?></div>
                                <a href="<?php echo BASE_URL; ?>/admin/users.php?role=formateur" class="btn btn-light btn-sm">View all</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card">
                            <div class="stat-card">
                                <div class="stat-icon icon-stagiaires">
                                    <i class="fas fa-user-graduate"></i>
                                </div>
                                <div class="stat-label">Stagiaires</div>
                                <div class="stat-value"><?php echo $stagiaireCount; ?></div>
                                <a href="<?php echo BASE_URL; ?>/admin/users.php?role=stagiaire" class="btn btn-light btn-sm">View all</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card">
                            <div class="stat-card">
                                <div class="stat-icon icon-exams">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                                <div class="stat-label">Exams</div>
                                <div class="stat-value"><?php echo $examCount; ?></div>
                                <a href="<?php echo BASE_URL; ?>/admin/exams.php" class="btn btn-light btn-sm">View all</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Recent Results Table -->
                    <div class="col-lg-8 mb-4">
                        <div class="card table-card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>Recent Results</div>
                                <div class="search-box">
                                    <i class="fas fa-search search-icon"></i>
                                    <input type="text" class="form-control form-control-sm search-input" id="resultSearch" placeholder="Filter...">
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table" id="resultTable">
                                    <thead>
                                        <tr>
                                            <th>Stagiaire</th>
                                            <th>Exam</th>
                                            <th>Score</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($recentResults)): ?>
                                            <tr>
                                                <td colspan="5" class="text-center py-3">No results found</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($recentResults as $result): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($result['stagiaire_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($result['exam_name']); ?></td>
                                                    <td>
                                                        <span class="score-badge badge-<?php echo $result['score'] >= 50 ? 'success' : 'danger'; ?>">
                                                            <?php if ($result['score'] >= 50): ?>
                                                                <i class="fas fa-check-circle mr-1"></i>
                                                            <?php else: ?>
                                                                <i class="fas fa-times-circle mr-1"></i>
                                                            <?php endif; ?>
                                                            <?php echo $result['score']; ?>%
                                                        </span>
                                                    </td>
                                                    <td><?php echo date('M d, Y', strtotime($result['created_at'])); ?></td>
                                                    <td>
                                                        <div class="btn-group btn-group-sm">
                                                            <a href="results.php?id=<?php echo $result['id']; ?>" class="btn btn-outline-primary">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <a href="results.php?action=edit&id=<?php echo $result['id']; ?>" class="btn btn-outline-secondary">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="card-footer text-center py-3">
                                <a href="results.php" class="btn btn-primary">View All Results</a>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Links and System Info -->
                    <div class="col-lg-4">
                        <div class="card mb-4">
                            <div class="card-header">Quick Actions</div>
                            <div class="card-body p-0">
                                <a href="users.php?action=add" class="action-list-item d-flex align-items-center">
                                    <div class="action-icon icon-formateurs">
                                        <i class="fas fa-user-plus"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="action-title">Add New User</div>
                                        <div class="action-subtitle">Create user account</div>
                                    </div>
                                    <div class="action-arrow">
                                        <i class="fas fa-chevron-right text-muted"></i>
                                    </div>
                                </a>
                                <a href="exams.php?action=add" class="action-list-item d-flex align-items-center">
                                    <div class="action-icon icon-exams">
                                        <i class="fas fa-file-medical"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="action-title">Create New Exam</div>
                                        <div class="action-subtitle">Set up exam questions</div>
                                    </div>
                                    <div class="action-arrow">
                                        <i class="fas fa-chevron-right text-muted"></i>
                                    </div>
                                </a>
                                <a href="results.php" class="action-list-item d-flex align-items-center">
                                    <div class="action-icon icon-stagiaires">
                                        <i class="fas fa-chart-line"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="action-title">View Results</div>
                                        <div class="action-subtitle">Review exam scores</div>
                                    </div>
                                    <div class="action-arrow">
                                        <i class="fas fa-chevron-right text-muted"></i>
                                    </div>
                                </a>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">System Information</div>
                            <div class="card-body">
                                <div class="system-info-item">
                                    <div class="info-icon icon-formateurs">
                                        <i class="fas fa-server"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="info-label">PHP Version</div>
                                        <div class="info-value"><?php echo phpversion(); ?></div>
                                    </div>
                                </div>
                                <div class="system-info-item">
                                    <div class="info-icon icon-stagiaires">
                                        <i class="fas fa-database"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="info-label">Database</div>
                                        <div class="info-value">MySQL</div>
                                    </div>
                                </div>
                                <div class="system-info-item">
                                    <div class="info-icon icon-exams">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="info-label">Server Time</div>
                                        <div class="info-value"><?php echo date('H:i:s'); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap core JavaScript -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Dashboard JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Dark mode toggle
            const darkModeToggle = document.getElementById('dark-mode-toggle');
            const body = document.body;
            
            // Check for saved mode preference
            const darkMode = localStorage.getItem('darkMode');
            
            // Apply dark mode if previously saved
            if (darkMode === 'enabled') {
                body.classList.add('dark-mode');
                darkModeToggle.innerHTML = '<i class="fas fa-sun mr-1"></i> Light';
            }
            
            // Add toggle functionality
            darkModeToggle.addEventListener('click', function() {
                if (body.classList.contains('dark-mode')) {
                    body.classList.remove('dark-mode');
                    localStorage.setItem('darkMode', null);
                    darkModeToggle.innerHTML = '<i class="fas fa-moon mr-1"></i> Dark';
                } else {
                    body.classList.add('dark-mode');
                    localStorage.setItem('darkMode', 'enabled');
                    darkModeToggle.innerHTML = '<i class="fas fa-sun mr-1"></i> Light';
                }
            });

            // Mobile sidebar toggle
            const sidebarToggler = document.getElementById('sidebarToggler');
            const sidebar = document.getElementById('sidebarMenu');
            const sidebarBackdrop = document.getElementById('sidebarBackdrop');
            
            if (sidebarToggler) {
                sidebarToggler.addEventListener('click', function() {
                    sidebar.classList.toggle('show');
                    sidebarBackdrop.classList.toggle('show');
                });
            }
            
            if (sidebarBackdrop) {
                sidebarBackdrop.addEventListener('click', function() {
                    sidebar.classList.remove('show');
                    sidebarBackdrop.classList.remove('show');
                });
            }

            // Result table search functionality
            const resultSearch = document.getElementById('resultSearch');
            const resultTable = document.getElementById('resultTable');
            
            if (resultSearch && resultTable) {
                resultSearch.addEventListener('keyup', function() {
                    const searchTerm = resultSearch.value.toLowerCase();
                    const rows = resultTable.querySelectorAll('tbody tr');
                    
                    rows.forEach(row => {
                        const text = row.textContent.toLowerCase();
                        row.style.display = text.includes(searchTerm) ? '' : 'none';
                    });
                });
            }
        });
    </script>
</body>
</html>
