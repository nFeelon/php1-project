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
            <!-- Мой канал -->
            <a href="/channel.php?id=<?php echo $_SESSION['user_id']; ?>" class="nav-item">
                <img src="img/navbar/mychannel.png" alt="My Channel" class="nav-icon">
                <span>Мой канал</span>
            </a>
            <!-- Профиль пользователя -->
            <a href="/profile.php" class="nav-item">
                <div class="nav-icon profile-avatar">
                    <?php /* Отладочная информация */ ?>
                    <?php /* ID пользователя: <?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'не установлен'; ?> */ ?>
                    <img src="/api/get_avatar.php?user_id=<?php echo $_SESSION['user_id']; ?>&size=24" alt="Profile" class="avatar-img">
                </div>
                <span><?php echo htmlspecialchars($_SESSION['display_name'] ?? $_SESSION['username']); ?></span>
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

<script src="js/auth.js"></script>
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