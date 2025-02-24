<?php
require_once 'includes/auth_check.php';
?>

<button class="burger-btn-open" onclick="toggleNav()">
    <img src="img/navbar/burger.png" alt="Open Menu" class="burger-icon">
</button>

<nav id="sideNav" class="side-nav">
    <div class="nav-header">
        <div class="logo-container">
            <img src="img/navbar/burger.png" alt="Menu" class="burger-icon" onclick="toggleNav()">
            <a href="/" class="logo-text">TrueWatch</a>
            <img src="img/favicon.png" alt="Logo" class="play-icon">
        </div>
    </div>
    <div class="nav-content">
        <a href="/" class="nav-item">
            <img src="img/navbar/main.png" alt="Home" class="nav-icon">
            <span>Главная</span>
        </a>
        <?php if (isLoggedIn()): ?>
            <!-- Профиль пользователя -->
            <a href="/profile.php" class="nav-item">
                <img src="img/navbar/profile.png" alt="Profile" class="nav-icon">
                <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <span class="sub-text">Личный кабинет</span>
            </a>
            <!-- Кнопка выхода -->
            <button class="nav-item logout-btn">
                <img src="img/navbar/logout.png" alt="Logout" class="nav-icon">
                <span>Выйти</span>
            </button>
        <?php else: ?>
            <!-- Кнопка входа -->
            <a href="/login.php" class="nav-item">
                <img src="img/navbar/profile.png" alt="Profile" class="nav-icon">
                <span>Войти</span>
                <span class="sub-text">или создать аккаунт</span>
            </a>
        <?php endif; ?>
    </div>
</nav>

<script>
    function toggleNav() {
        const nav = document.getElementById('sideNav');
        const mainContent = document.getElementById('main-content');

        if (nav.classList.contains('nav-open')) {
            nav.classList.remove('nav-open');
            mainContent.classList.remove('content-shifted');
        } else {
            nav.classList.add('nav-open');
            mainContent.classList.add('content-shifted');
        }
    }
</script>