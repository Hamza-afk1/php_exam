<?php
class Session {
    public static function init() {
        if (session_status() == PHP_SESSION_NONE) {
            session_name(SESSION_NAME);
            session_start();
        }
    }
    
    public static function set($key, $value) {
        $_SESSION[$key] = $value;
    }
    
    public static function get($key) {
        return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
    }
    
    public static function remove($key) {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }
    
    public static function destroy() {
        session_unset();
        session_destroy();
    }
    
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public static function checkLogin() {
        if (!self::isLoggedIn()) {
            header('Location: ' . BASE_URL . '/login.php');
            exit;
        }
    }
    
    public static function checkAdmin() {
        self::checkLogin();
        if ($_SESSION['user_role'] !== 'admin') {
            header('Location: ' . BASE_URL . '/index.php');
            exit;
        }
    }
    
    public static function checkFormateur() {
        self::checkLogin();
        if ($_SESSION['user_role'] !== 'formateur' && $_SESSION['user_role'] !== 'admin') {
            header('Location: ' . BASE_URL . '/index.php');
            exit;
        }
    }
    
    public static function checkStagiaire() {
        self::checkLogin();
        if ($_SESSION['user_role'] !== 'stagiaire') {
            header('Location: ' . BASE_URL . '/index.php');
            exit;
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
}
?>
