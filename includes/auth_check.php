<?php
require_once 'User.php';

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

if (isset($_COOKIE['remember_token']) && !isLoggedIn()) {
    $user = new User();
    $userData = $user->checkRememberToken($_COOKIE['remember_token']);
    
    if ($userData) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $_SESSION['user_id'] = $userData['user_id'];
        $_SESSION['username'] = $userData['username'];
        $_SESSION['display_name'] = $userData['display_name'] ?? $userData['username'];
    } else {
        setcookie('remember_token', '', time() - 3600, '/');
    }
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getCurrentUser() {
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? null,
        'display_name' => $_SESSION['display_name'] ?? null
    ];
}
