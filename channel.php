<?php
require_once 'includes/auth_check.php';
require_once 'includes/Database.php';
require_once 'includes/VideoManager.php';
require_once 'includes/UserManager.php';
require_once 'includes/SubscriptionManager.php';
require_once 'includes/helpers.php';

$channelId = isset($_GET['id']) ? (int) $_GET['id'] : null;

if (!$channelId) {
    header('Location: /');
    exit;
}

$userManager = new UserManager();
$videoManager = new VideoManager();
$subscriptionManager = new SubscriptionManager();

$channel = $userManager->getUserById($channelId);

if (!$channel) {
    $pageTitle = 'Канал не найден - TrueWatch';
    $error = 'Запрашиваемый канал не существует или был удален';
} else {
    $pageTitle = $channel['display_name'] . ' - TrueWatch';

    if (isLoggedIn()) {
        $videos = $videoManager->getVideosByUserId($channelId, $_SESSION['user_id']);
    } else {
        $videos = $videoManager->getVideosByUserId($channelId);
    }

    $subscriptions = $subscriptionManager->getChannelSubscriptions($channelId, 10);
    $isOwner = isLoggedIn() && $_SESSION['user_id'] == $channelId;
    $isSubscribed = false;
    if (isLoggedIn()) {
        $isSubscribed = $subscriptionManager->isSubscribed($_SESSION['user_id'], $channelId);
    }

    $channel['subscribers_formatted'] = formatSubscribersCount($channel['subscribers_count']);
    $channel['created_at_formatted'] = formatDate($channel['created_at']);
    $channel['social_links'] = json_decode($channel['social_links'], true) ?: [];
}

?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="icon" href="img/favicon.png" type="image/png">
    <link rel="stylesheet" href="css/reset.css">
    <link rel="stylesheet" href="css/fonts.css">
    <link rel="stylesheet" href="css/variables.css">
    <link rel="stylesheet" href="css/general.css">
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="css/video-grid.css">
    <link rel="stylesheet" href="css/channel.css">
    <link rel="stylesheet" href="css/subscribe.css">
    <link rel="stylesheet" href="css/search.css">
</head>

<body>
    <?php include 'search.php'; ?>
    <?php include 'navbar.php'; ?>

    <div id="main-content">
        <?php if (isset($error)): ?>
            <div class="error-message">
                <p><?php echo $error; ?></p>
                <a href="/" class="error-link">Вернуться на главную</a>
            </div>
        <?php else: ?>
            <div id="channel-container" data-channel-id="<?php echo $channelId; ?>">
                <div class="channel-header">
                    <div class="channel-banner <?php echo empty($channel['banner_url']) ? 'channel-banner-default' : ''; ?>"
                        id="channel-banner">
                        <?php if (!empty($channel['banner_url'])): ?>
                            <img src="<?php echo $channel['banner_url']; ?>" alt="Баннер канала">
                        <?php endif; ?>
                    </div>

                    <div class="channel-info-container">
                        <div class="channel-avatar-large">
                            <img src="<?php echo $channel['avatar_url']; ?>"
                                alt="<?php echo htmlspecialchars($channel['display_name']); ?>" id="channel-avatar">
                        </div>

                        <div class="channel-details">
                            <h1 class="channel-name-large" id="channel-name">
                                <?php echo htmlspecialchars($channel['display_name']); ?>
                            </h1>
                            <div class="channel-stats">
                                <span class="subscribers-count"
                                    id="subscribers-count"><?php echo $channel['subscribers_formatted']; ?></span>
                                <span class="videos-count" id="videos-count"><?php echo count($videos); ?> видео</span>
                                <span class="channel-created" id="channel-created">Дата регистрации:
                                    <?php echo $channel['created_at_formatted']; ?></span>
                            </div>

                            <div id="subscribe-container">
                                <?php if (!$isOwner): ?>
                                    <button class="subscribe-btn <?php echo $isSubscribed ? 'subscribed' : ''; ?>"
                                        data-channel-id="<?php echo $channelId; ?>"
                                        data-subscribed="<?php echo $isSubscribed ? 'true' : 'false'; ?>">
                                        <?php echo $isSubscribed ? 'Отписаться' : 'Подписаться'; ?>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="channel-nav">
                    <a href="#videos" class="channel-nav-item active">Видео</a>
                    <a href="#about" class="channel-nav-item">О канале</a>
                    <a href="#subscriptions" class="channel-nav-item">Подписки</a>
                </div>

                <div class="channel-content">
                    <div id="videos" class="channel-section" style="display: block;">
                        <h2 class="section-header">Видео</h2>

                        <div id="video-grid-container">
                            <?php if (count($videos) > 0): ?>
                                <div class="video-grid">
                                    <?php foreach ($videos as $video): ?>
                                        <?php include 'components/video_card.php'; ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="no-videos">На этом канале пока нет видео</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div id="about" class="channel-section" style="display: none;">
                        <h2 class="section-header">О канале</h2>
                        <div class="channel-description" id="channel-description">
                            <?php if (!empty($channel['description'])): ?>
                                <p><?php echo nl2br(htmlspecialchars($channel['description'])); ?></p>
                            <?php else: ?>
                                <p>Нет описания</p>
                            <?php endif; ?>
                        </div>

                        <div class="social-links">
                            <h3 class="social-links-header">Социальные сети</h3>
                            <div id="social-links-container">
                                <?php if (count($channel['social_links']) > 0): ?>
                                    <ul class="social-links-list">
                                        <?php foreach ($channel['social_links'] as $platform => $url): ?>
                                            <li><a href="<?php echo htmlspecialchars($url); ?>" target="_blank"
                                                    rel="noopener noreferrer"><?php echo htmlspecialchars($platform); ?></a></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <p>Социальные сети не указаны</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div id="subscriptions" class="channel-section" style="display: none;">
                        <h2 class="section-header">Подписки</h2>
                        <div id="subscriptions-container">
                            <?php if (count($subscriptions) > 0): ?>
                                <div class="subscriptions-grid">
                                    <?php foreach ($subscriptions as $subscription): ?>
                                        <div class="subscription-card">
                                            <a href="/channel.php?id=<?php echo $subscription['user_id']; ?>"
                                                class="subscription-avatar">
                                                <img src="<?php echo $subscription['avatar_url']; ?>"
                                                    alt="<?php echo htmlspecialchars($subscription['display_name']); ?>">
                                            </a>
                                            <div class="subscription-info">
                                                <a href="/channel.php?id=<?php echo $subscription['user_id']; ?>"
                                                    class="subscription-name"><?php echo htmlspecialchars($subscription['display_name']); ?></a>
                                                <span
                                                    class="subscription-stats"><?php echo formatSubscribersCount($subscription['subscribers_count']); ?></span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="no-subscriptions">Этот канал пока ни на кого не подписан</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="js/channel.js"></script>
    <script src="js/utils.js"></script>
</body>

</html>