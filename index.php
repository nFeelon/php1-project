<?php
require_once 'includes/auth_check.php';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TrueWatch - Главная</title>
    <link rel="icon" type="image/png" href="img/favicon.png">
    <link rel="stylesheet" href="css/reset.css">
    <link rel="stylesheet" href="css/variables.css">
    <link rel="stylesheet" href="css/fonts.css">
    <link rel="stylesheet" href="css/general.css">
    <link rel="stylesheet" href="css/navbar.css">
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div id="main-content">
        <h1>Добро пожаловать<?php 
            if (isLoggedIn()) {
                echo ', ' . htmlspecialchars($_SESSION['username']);
            }
        ?>!</h1>
        <!-- Здесь основной контент -->
    </div>

    <script src="js/auth.js"></script>
</body>
</html>