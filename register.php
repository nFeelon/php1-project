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
    <meta name="description" content="Создайте аккаунт на TrueWatch для доступа к видео">
    <meta name="theme-color" content="#1a1a1a">
    <title>TrueWatch - Регистрация</title>
    
    <!-- Preload критических ресурсов -->
    <link rel="preload" href="css/auth.css" as="style">
    <link rel="preload" href="fonts/Ubuntu-Regular.ttf" as="font" type="font/ttf" crossorigin>
    <link rel="preload" href="js/utils.js" as="script">
    <link rel="preload" href="js/register.js" as="script">
    <link rel="preload" href="js/auth.js" as="script">
    <link rel="preload" href="js/navigation.js" as="script">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="img/favicon.png">
    
    <!-- Стили -->
    <link rel="stylesheet" href="css/reset.css">
    <link rel="stylesheet" href="css/variables.css">
    <link rel="stylesheet" href="css/fonts.css">
    <link rel="stylesheet" href="css/general.css" media="print" onload="this.media='all'">
    <link rel="stylesheet" href="css/navbar.css" media="print" onload="this.media='all'">
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
                <p class="welcome-text">Добро пожаловать!</p>
                <h1>Создать аккаунт</h1>

                <form id="registerForm" novalidate>
                    <div class="form-group">
                        <label for="displayName">Имя пользователя</label>
                        <div class="input-wrapper">
                            <input type="text" id="displayName" class="form-input" placeholder="Введите имя" autocomplete="name">
                            <div id="displayNameError" class="validation-message" aria-live="polite"></div>
                            <ul class="validation-requirements" aria-hidden="true">
                                <li id="name-length">Минимум 2 символа</li>
                                <li id="name-letters">Только буквы</li>
                                <li id="name-no-digits">Без цифр</li>
                            </ul>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <div class="input-wrapper">
                            <input type="email" id="email" class="form-input" placeholder="Введите email" autocomplete="email">
                            <div id="emailError" class="validation-message" aria-live="polite"></div>
                            <ul class="validation-requirements" aria-hidden="true">
                                <li id="email-format">Корректный email адрес</li>
                                <li id="email-at">Содержит @</li>
                                <li id="email-domain">Указан домен</li>
                            </ul>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">Пароль</label>
                        <div class="input-wrapper">
                            <input type="password" id="password" class="form-input" placeholder="Придумайте пароль" autocomplete="new-password">
                            <div id="passwordError" class="validation-message" aria-live="polite"></div>
                            <ul class="validation-requirements" aria-hidden="true">
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
                            <input type="password" id="confirmPassword" class="form-input" placeholder="Повторите пароль" autocomplete="new-password">
                            <div id="confirmPasswordError" class="validation-message" aria-live="polite"></div>
                            <ul class="validation-requirements" aria-hidden="true">
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

    <!-- Scripts -->
    <script src="js/utils.js" defer></script>
    <script src="js/register.js" defer></script>
    <script src="js/auth.js" defer></script>
    <script src="js/navigation.js" defer></script>
</body>
</html>