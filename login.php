<?php
require_once 'includes/auth_check.php';

// Если пользователь уже авторизован, перенаправляем на главную
if (isLoggedIn()) {
    header('Location: /');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TrueWatch - Вход</title>
    <link rel="icon" type="image/png" href="img/favicon.png">
    <link rel="stylesheet" href="css/reset.css">
    <link rel="stylesheet" href="css/variables.css">
    <link rel="stylesheet" href="css/fonts.css">
    <link rel="stylesheet" href="css/general.css">
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="css/auth.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div id="main-content">
        <div class="auth-container">
            <div class="auth-form">
                <a href="/" class="back-button">←</a>
                <p class="welcome-text">С возвращением!</p>
                <h1>Войти в аккаунт</h1>

                <form id="loginForm">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <div class="input-wrapper">
                            <input type="email" id="email" class="form-input" placeholder="Введите email">
                            <div id="emailError" class="validation-message"></div>
                            <ul class="validation-requirements">
                                <li id="email-format">Корректный email адрес</li>
                            </ul>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">Пароль</label>
                        <div class="input-wrapper">
                            <input type="password" id="password" class="form-input" placeholder="Введите пароль">
                            <div id="passwordError" class="validation-message"></div>
                            <ul class="validation-requirements">
                                <li id="password-filled">Введите пароль</li>
                            </ul>
                        </div>
                    </div>

                    <div class="checkbox">
                        <input type="checkbox" id="remember">
                        <label for="remember">Запомнить меня</label>
                    </div>

                    <button type="submit" class="btn-primary" disabled>Войти</button>

                    <div class="auth-links">
                        <a href="/register.php">Создать аккаунт</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="js/utils.js"></script>
    <script src="js/login.js"></script>
    <script src="js/auth.js"></script>
</body>
</html>