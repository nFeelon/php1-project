<?php
require_once 'includes/auth_check.php';
require_once 'includes/Database.php';
require_once 'includes/VideoManager.php';
require_once 'includes/CommentManager.php';

if (!isset($_GET['id'])) {
    header('Location: /');
    exit;
}

$videoId = (int) $_GET['id'];
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

$videoManager = new VideoManager();
$video = $videoManager->getVideoById($videoId, $userId);
if (!$video) {
    header('Location: /');
    exit;
}

$isSubscribed = false;
if (isLoggedIn() && $userId != $video['user_id']) {
    $db = Database::getInstance()->getConnection();
    $query = "SELECT 1 FROM subscriptions WHERE subscriber_id = :subscriber_id AND channel_id = :channel_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':subscriber_id', $userId, PDO::PARAM_INT);
    $stmt->bindParam(':channel_id', $video['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    $isSubscribed = (bool) $stmt->fetchColumn();
}

$commentManager = new CommentManager();
$commentsCount = $commentManager->getCommentsCount($videoId);
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($video['title']); ?> - TrueWatch</title>
    <link rel="icon" type="image/png" href="img/favicon.png">
    <link rel="stylesheet" href="css/reset.css">
    <link rel="stylesheet" href="css/variables.css">
    <link rel="stylesheet" href="css/fonts.css">
    <link rel="stylesheet" href="css/general.css">
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="css/video.css">
    <link rel="stylesheet" href="css/subscribe.css">
    <link rel="stylesheet" href="css/search.css">
    <link rel="stylesheet" href="css/comments.css">
</head>

<body>
    <?php include 'search.php'; ?>
    <?php include 'navbar.php'; ?>

    <div id="main-content">
        <div class="video-container">
            <div class="video-player">
                <video controls data-video-id="<?php echo $video['video_id']; ?>">
                    <source src="/api/get_video.php?video_id=<?php echo $video['video_id']; ?>" type="video/mp4">
                    Ваш браузер не поддерживает видео.
                </video>
            </div>

            <div class="video-info">
                <div class="video-header">
                    <h1><?php echo htmlspecialchars($video['title']); ?></h1>
                    <div class="video-stats">
                        <span class="views"><?php echo number_format($video['views_count']); ?> просмотров</span>
                        <span class="dot">•</span>
                        <span class="date"><?php echo VideoManager::formatUploadDate($video['upload_date']); ?></span>
                    </div>
                </div>

                <div class="channel-info">
                    <a href="channel.php?id=<?php echo $video['user_id']; ?>" class="channel-link">
                        <div class="channel-avatar">
                            <img src="/api/get_avatar.php?user_id=<?php echo $video['user_id']; ?>&size=48"
                                alt="<?php echo htmlspecialchars($video['display_name'] ?? $video['username']); ?>">
                        </div>
                        <div class="channel-details">
                            <h3><?php echo htmlspecialchars($video['display_name'] ?? $video['username']); ?></h3>
                            <div class="channel-stats">
                                <span
                                    class="subscribers subscribers-count"><?php echo number_format($video['subscribers_count'] ?? 0); ?>
                                    подписчиков</span>
                                <?php if (isset($video['videos_count'])): ?>
                                    <span class="dot">•</span>
                                    <span class="videos"><?php echo number_format($video['videos_count']); ?> видео</span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($video['channel_description'])): ?>
                                <p class="channel-description">
                                    <?php echo htmlspecialchars($video['channel_description']); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </a>
                    <?php if (isLoggedIn() && $userId != $video['user_id']): ?>
                        <button class="subscribe-btn <?php echo $isSubscribed ? 'subscribed' : ''; ?>"
                            data-channel-id="<?php echo $video['user_id']; ?>"
                            data-subscribed="<?php echo $isSubscribed ? 'true' : 'false'; ?>">
                            <?php echo $isSubscribed ? 'Отписаться' : 'Подписаться'; ?>
                        </button>
                    <?php else: ?>
                        <?php if (!isLoggedIn()): ?>
                            <a href="login.php" class="subscribe-btn">Войдите, чтобы подписаться</a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <div class="video-actions">
                    <div class="likes">
                        <?php if (isLoggedIn()): ?>
                            <button class="like-btn <?php echo $video['user_reaction'] === TRUE ? 'active' : ''; ?>"
                                data-video-id="<?php echo $video['video_id']; ?>">
                                <span><?php echo number_format($video['likes_count']); ?></span>
                            </button>
                            <button class="dislike-btn <?php echo $video['user_reaction'] === FALSE ? 'active' : ''; ?>"
                                data-video-id="<?php echo $video['video_id']; ?>">
                                <span><?php echo number_format($video['dislikes_count']); ?></span>
                            </button>
                        <?php else: ?>
                            <a href="login.php" class="action-btn like-btn">
                                <span><?php echo number_format($video['likes_count']); ?></span>
                            </a>
                            <a href="login.php" class="action-btn dislike-btn">
                                <span><?php echo number_format($video['dislikes_count']); ?></span>
                            </a>
                        <?php endif; ?>
                    </div>
                    <button class="share-btn" data-video-id="<?php echo $video['video_id']; ?>">Поделиться</button>
                </div>

                <div class="video-description">
                    <?php if (!empty($video['description'])): ?>
                        <p><?php echo nl2br(htmlspecialchars($video['description'])); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="comments-section">
                <h3><?php echo number_format($commentsCount); ?>
                    <?php echo $commentManager->pluralizeRussian($commentsCount, 'комментарий', 'комментария', 'комментариев'); ?>
                </h3>
                <!-- Контент будет загружен через JavaScript -->
            </div>
        </div>
    </div>

    <script>
        const isLoggedIn = <?php echo isLoggedIn() ? 'true' : 'false'; ?>;
        <?php if (isLoggedIn()): ?>
            const userId = <?php echo $userId; ?>;
        <?php endif; ?>
    </script>

    <script src="/js/subscribe.js"></script>
    <script src="js/video-rating.js"></script>
    <script src="js/video-tracking.js"></script>
    <script src="js/comments.js"></script>
    <script src="js/utils.js"></script>
</body>

</html>