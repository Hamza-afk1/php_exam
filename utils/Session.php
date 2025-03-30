<?php
class Session {
    public static function init() {
        if (session_status() == PHP_SESSION_NONE) {
            session_name(SESSION_NAME);
            session_start();
        }
    }
    
    public static function set($key, $value) {
        // Special handling for role to maintain backward compatibility
        if ($key === 'role') {
            $_SESSION['role'] = $value;
            $_SESSION['user_role'] = $value; // Set both for compatibility
        } else {
            $_SESSION[$key] = $value;
        }
    }
    
    /**
     * Get session value
     *
     * @param string $key The key to get
     * @return mixed|false Value or false
     */
    public static function get($key) {
        self::init();
        
        // Special handling for role to maintain backward compatibility
        if ($key === 'role' && !isset($_SESSION['role']) && isset($_SESSION['user_role'])) {
            return $_SESSION['user_role'];
        } else if ($key === 'user_role' && !isset($_SESSION['user_role']) && isset($_SESSION['role'])) {
            return $_SESSION['role'];
        }
        
        return isset($_SESSION[$key]) ? $_SESSION[$key] : false;
    }
    
    public static function remove($key) {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
        
        // Special handling for role to maintain backward compatibility
        if ($key === 'role' && isset($_SESSION['user_role'])) {
            unset($_SESSION['user_role']);
        } else if ($key === 'user_role' && isset($_SESSION['role'])) {
            unset($_SESSION['role']);
        }
    }
    
    public static function destroy() {
        // Clear all session data
        $_SESSION = [];
        
        // If a session cookie is used, clear it too
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Finally, destroy the session
        session_unset();
        session_destroy();
    }
    
    public static function isLoggedIn() {
        self::init();
        return (isset($_SESSION['user_id']) && !empty($_SESSION['user_id']) && 
                isset($_SESSION['username']) && !empty($_SESSION['username']));
    }
    
    public static function checkLogin() {
        self::init();
        if (!self::isLoggedIn()) {
            header('Location: ' . BASE_URL . '/login.php');
            exit;
        }
    }
    
    public static function checkAdmin() {
        self::init();
        self::checkLogin();
        
        // Check both role variables for backward compatibility
        $isAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') ||
                    (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin');
                    
        if (!$isAdmin) {
            error_log("Access denied for user. Role: " . self::get('role'));
            header('Location: ' . BASE_URL . '/index.php');
            exit;
        }
    }
    
    public static function checkFormateur() {
        self::init();
        self::checkLogin();
        
        // Check both role variables for backward compatibility
        $isFormateur = (isset($_SESSION['role']) && ($_SESSION['role'] === 'formateur' || $_SESSION['role'] === 'admin')) ||
                        (isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'formateur' || $_SESSION['user_role'] === 'admin'));
                        
        if (!$isFormateur) {
            // Check if we're not already on the index page to prevent loops
            $currentPage = basename($_SERVER['PHP_SELF']);
            if ($currentPage !== 'index.php') {
                header('Location: ' . BASE_URL . '/index.php');
                exit;
            }
        }
    }
    
    public static function checkStagiaire() {
        self::init();
        self::checkLogin();
        
        // Check both role variables for backward compatibility
        $isStagiaire = (isset($_SESSION['role']) && $_SESSION['role'] === 'stagiaire') ||
                        (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'stagiaire');
                        
        if (!$isStagiaire) {
            // Check if we're not already on the index page to prevent loops
            $currentPage = basename($_SERVER['PHP_SELF']);
            if ($currentPage !== 'index.php') {
                header('Location: ' . BASE_URL . '/index.php');
                exit;
            }
        }
    }
    
    public static function getFlash($key) {
        $value = null;
        
        if (isset($_SESSION[$key])) {
            $value = $_SESSION[$key];
            unset($_SESSION[$key]);
        }
        
        return $value;
    }
    
    public static function setFlash($key, $value) {
        $_SESSION[$key] = $value;
    }
    
    // Debug function to view all session variables
    public static function debug() {
        self::init();
        echo "<pre>";
        print_r($_SESSION);
        echo "</pre>";
    }
}
?>
