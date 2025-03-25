<?php
require_once 'includes/auth_check.php';
require_once 'includes/Database.php';
require_once 'includes/VideoManager.php';

// Инициализация менеджера видео
$videoManager = new VideoManager();

// Получение списка видео в зависимости от статуса авторизации
if (isLoggedIn()) {
    $userId = $_SESSION['user_id'];
    $videos = $videoManager->getRecommendedVideos();
    $title = 'Рекомендованные видео';
} else {
    $videos = $videoManager->getRandomVideos();
    $title = 'Популярные видео';
}
// Отладочная информация
echo "<!-- Отладка массива \$videos: " . count($videos) . " видео -->";
foreach ($videos as $key => $video) {
    echo "<!-- Видео #" . $key . ": ";
    echo "ID=" . $video['video_id'] . ", ";
    echo "Название='" . htmlspecialchars($video['title']) . "', ";
    echo "Статус='" . $video['status'] . "', ";
    echo "Дата=" . $video['upload_date'] . ", ";
    echo "Просмотры=" . $video['views_count'];
    echo " -->";
}

?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TrueWatch - Главная</title>
    <link rel="icon" type="image/png" href="img/favicon.png">

    <link rel="preload" href="css/reset.css" as="style">
    <link rel="preload" href="css/variables.css" as="style">
    <link rel="preload" href="css/fonts.css" as="style">
    <link rel="preload" href="css/general.css" as="style">
    <link rel="preload" href="fonts/Ubuntu-Regular.ttf" as="font" type="font/ttf" crossorigin>

    <link rel="stylesheet" href="css/reset.css">
    <link rel="stylesheet" href="css/variables.css">
    <link rel="stylesheet" href="css/fonts.css">
    <link rel="stylesheet" href="css/general.css">
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="css/video-grid.css">
    <link rel="stylesheet" href="css/search.css">
</head>

<body>
    <?php include 'navbar.php'; ?>
    <?php include 'search.php'; ?>

    <div id="main-content">
        <h1>Добро пожаловать<?php
        if (isLoggedIn()) {
            echo ', ' . htmlspecialchars($_SESSION['display_name'] ?? $_SESSION['username']);
        }
        ?>!</h1>

        <h2 class="section-header"><?php echo $title; ?></h2>

        <div class="video-grid">
            <?php foreach ($videos as $video): ?>
                <?php include 'components/video_card.php'; ?>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="js/utils.js"></script>
</body>

</html>