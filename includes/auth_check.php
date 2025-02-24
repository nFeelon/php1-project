<?php
require_once 'User.php';

// Проверяем статус сессии
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

// Проверяем наличие сессии
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    // Если сессии нет, но есть токен - проверяем его
    $user = new User();
    $userData = $user->checkRememberToken($_COOKIE['remember_token']);
    
    if ($userData) {
        $_SESSION['user_id'] = $userData['user_id'];
        $_SESSION['username'] = $userData['username'];
    } else {
        // Удаляем невалидный токен
        setcookie('remember_token', '', time() - 3600, '/');
    }
}

// Функция для проверки авторизации
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Функция для получения данных пользователя
function getCurrentUser() {
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? null
    ];
}
