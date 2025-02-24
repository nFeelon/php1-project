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
    <title>TrueWatch - Регистрация</title>
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
                <p class="welcome-text">Добро пожаловать!</p>
                <h1>Создать аккаунт</h1>

                <form id="registerForm">
                    <div class="form-group">
                        <label for="displayName">Имя пользователя</label>
                        <div class="input-wrapper">
                            <input type="text" id="displayName" class="form-input" placeholder="Введите имя">
                            <div id="displayNameError" class="validation-message"></div>
                            <ul class="validation-requirements">
                                <li id="name-length">Минимум 2 символа</li>
                                <li id="name-letters">Только буквы</li>
                                <li id="name-no-digits">Без цифр</li>
                            </ul>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <div class="input-wrapper">
                            <input type="email" id="email" class="form-input" placeholder="Введите email">
                            <div id="emailError" class="validation-message"></div>
                            <ul class="validation-requirements">
                                <li id="email-format">Корректный email адрес</li>
                                <li id="email-at">Содержит @</li>
                                <li id="email-domain">Указан домен</li>
                            </ul>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">Пароль</label>
                        <div class="input-wrapper">
                            <input type="password" id="password" class="form-input" placeholder="Придумайте пароль">
                            <div id="passwordError" class="validation-message"></div>
                            <ul class="validation-requirements">
                                <li id="length">Минимум 8 символов</li>
                                <li id="uppercase">Заглавная буква</li>
                                <li id="lowercase">Строчная буква</li>
                                <li id="number">Цифра</li>
                                <li id="special">Специальный символ (!@#$%^&*)</li>
                            </ul>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirmPassword">Подтверждение пароля</label>
                        <div class="input-wrapper">
                            <input type="password" id="confirmPassword" class="form-input" placeholder="Повторите пароль">
                            <div id="confirmPasswordError" class="validation-message"></div>
                            <ul class="validation-requirements">
                                <li id="passwords-match">Пароли совпадают</li>
                            </ul>
                        </div>
                    </div>

                    <button type="submit" class="btn-primary" disabled>Создать аккаунт</button>

                    <div class="auth-links">
                        <a href="/login.php">Уже есть аккаунт? Войти</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="js/utils.js"></script>
    <script src="js/register.js"></script>
    <script src="js/auth.js"></script>
</body>
</html>