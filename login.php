<?php
require_once 'includes/auth_check.php';
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
    <meta name="description" content="Войдите в свой аккаунт TrueWatch для доступа к видео">
    <meta name="theme-color" content="#1a1a1a">
    <title>TrueWatch - Вход</title>

    <link rel="preload" href="css/auth.css" as="style">
    <link rel="preload" href="js/utils.js" as="script">
    <link rel="preload" href="js/login.js" as="script">
    <link rel="preload" href="js/navigation.js" as="script">

    <link rel="icon" type="image/png" href="img/favicon.png">

    <link rel="stylesheet" href="css/reset.css">
    <link rel="stylesheet" href="css/variables.css">
    <link rel="stylesheet" href="css/fonts.css">
    <link rel="stylesheet" href="css/general.css">
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="css/auth.css">
    <noscript>
        <link rel="stylesheet" href="css/general.css">
        <link rel="stylesheet" href="css/navbar.css">
    </noscript>
</head>
<body>
    
    <?php include 'navbar.php'; ?>

    <div id="main-content">
        <div class="auth-container">
            <div class="auth-form">
                <a href="/" class="back-button" aria-label="Вернуться назад">←</a>
                <p class="welcome-text">С возвращением!</p>
                <h1>Войти в аккаунт</h1>

                <form id="loginForm" novalidate>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <div class="input-wrapper">
                            <input type="email" id="email" class="form-input" placeholder="Введите email" autocomplete="email">
                            <div id="emailError" class="validation-message" aria-live="polite"></div>
                            <ul class="validation-requirements" aria-hidden="true">
                                <li id="email-format">Корректный email адрес</li>
                            </ul>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">Пароль</label>
                        <div class="input-wrapper">
                            <input type="password" id="password" class="form-input" placeholder="Введите пароль" autocomplete="current-password">
                            <div id="passwordError" class="validation-message" aria-live="polite"></div>
                            <ul class="validation-requirements" aria-hidden="true">
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

    <script src="js/utils.js" defer></script>
    <script src="js/login.js" defer></script>
    <script src="js/navigation.js" defer></script>
</body>
</html>