<?php
require_once 'includes/auth_check.php';
require_once 'includes/Database.php';
require_once 'includes/VideoManager.php';
require_once 'includes/UserManager.php';
require_once 'includes/helpers.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];

$userManager = new UserManager();
$videoManager = new VideoManager();

$user = $userManager->getUserById($userId);

$sort = 'date_desc';
$videos = $videoManager->getUserVideos($userId, $userId, $sort);
$viewHistory = $videoManager->getViewHistory($userId);

$user['social_links'] = json_decode($user['social_links'], true) ?: [];

$subscribersCount = isset($user['subscribers_count']) ? $user['subscribers_count'] : 0;
$user['subscribers_formatted'] = formatSubscribersCount($subscribersCount);
$user['created_at_formatted'] = formatDate($user['created_at']);

$baseUrl = $_SERVER['PHP_SELF'];
$currentSort = $sort;

$pageTitle = 'Личный кабинет - TrueWatch';
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
    <link rel="stylesheet" href="css/search.css">
    <link rel="stylesheet" href="css/profile.css">
    <link rel="stylesheet" href="css/channel.css">
</head>

<body>
    <?php include 'search.php'; ?>
    <?php include 'navbar.php'; ?>

    <div id="main-content"
        class="<?php echo isset($_COOKIE['navbar_expanded']) && $_COOKIE['navbar_expanded'] === 'true' ? 'content-shifted' : ''; ?>">
        <div id="profile-container" data-user-id="<?php echo $userId; ?>">
            <div class="profile-header">
                <div class="profile-banner <?php echo empty($user['banner_url']) ? 'profile-banner-default' : ''; ?>">
                    <?php if (!empty($user['banner_url'])): ?>
                        <img src="<?php echo $user['banner_url']; ?>" alt="Баннер профиля">
                    <?php endif; ?>
                </div>

                <div class="profile-info-container">
                    <div class="profile-avatar-large">
                        <img src="<?php echo $user['avatar_url']; ?>"
                            alt="<?php echo htmlspecialchars($user['display_name']); ?>" id="profile-avatar">
                    </div>

                    <div class="profile-details">
                        <h1 class="profile-name-large" id="display-name">
                            <?php echo htmlspecialchars($user['display_name']); ?>
                        </h1>
                        <div class="profile-stats">
                            <span class="subscribers-count"><?php echo $user['subscribers_formatted']; ?></span>
                            <span class="videos-count"><?php echo count($videos); ?> видео</span>
                            <span class="profile-created">Дата регистрации:
                                <?php echo $user['created_at_formatted']; ?></span>
                        </div>

                        <div class="social-links">
                            <?php if (!empty($user['social_links']['vk'])): ?>
                                <a href="<?php echo $user['social_links']['vk']; ?>" class="social-link vk" target="_blank">
                                    <i class="fab fa-vk"></i>
                                </a>
                            <?php endif; ?>

                            <?php if (!empty($user['social_links']['instagram'])): ?>
                                <a href="<?php echo $user['social_links']['instagram']; ?>" class="social-link instagram"
                                    target="_blank">
                                    <i class="fab fa-instagram"></i>
                                </a>
                            <?php endif; ?>

                            <?php if (!empty($user['social_links']['facebook'])): ?>
                                <a href="<?php echo $user['social_links']['facebook']; ?>" class="social-link facebook"
                                    target="_blank">
                                    <i class="fab fa-facebook"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="profile-nav">
                <a href="#videos" class="profile-nav-item active">Мои видео</a>
                <a href="#history" class="profile-nav-item">История просмотров</a>
                <a href="#edit-profile" class="profile-nav-item">Редактировать профиль</a>
                <a href="#upload" class="profile-nav-item">Загрузить видео</a>
            </div>

            <div class="profile-content">
                <div id="videos" class="profile-section" style="display: block;">
                    <h2 class="section-header">Мои видео</h2>
                    <?php if (count($videos) === 0): ?>
                        <p class="no-videos">У вас пока нет видео</p>
                    <?php else: ?>
                        <div class="videos-table-container">
                            <table class="videos-table">
                                <thead>
                                    <tr>
                                        <th>Превью</th>
                                        <th>Название</th>
                                        <th>Просмотры</th>
                                        <th>Дата загрузки</th>
                                        <th>Статус</th>
                                        <th>Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($videos as $video): ?>
                                        <tr class="video-row" data-video-id="<?php echo $video['video_id']; ?>">
                                            <td class="video-thumbnail-cell">
                                                <a href="video.php?id=<?php echo $video['video_id']; ?>" class="thumbnail-link">
                                                    <div class="thumbnail-container">
                                                        <img src="<?php echo $video['thumbnail_url']; ?>"
                                                            alt="<?php echo htmlspecialchars($video['title']); ?>">
                                                        <span
                                                            class="video-duration"><?php echo VideoManager::formatDuration($video['duration']); ?></span>
                                                    </div>
                                                </a>
                                            </td>
                                            <td class="video-title-cell">
                                                <a href="video.php?id=<?php echo $video['video_id']; ?>" class="video-title">
                                                    <?php echo htmlspecialchars($video['title']); ?>
                                                </a>
                                                <div class="video-description">
                                                    <?php echo htmlspecialchars(substr($video['description'], 0, 100) . (strlen($video['description']) > 100 ? '...' : '')); ?>
                                                </div>
                                            </td>
                                            <td class="video-views-cell">
                                                <?php echo number_format($video['views_count'], 0, '', ' '); ?>
                                            </td>
                                            <td class="video-date-cell">
                                                <?php echo VideoManager::formatUploadDate($video['upload_date']); ?>
                                            </td>
                                            <td class="video-status-cell">
                                                <span class="video-status <?php echo $video['status']; ?>">
                                                    <?php
                                                    switch ($video['status']) {
                                                        case 'public':
                                                            echo 'Публичное';
                                                            break;
                                                        case 'private':
                                                            echo 'Приватное';
                                                            break;
                                                        case 'unlisted':
                                                            echo 'По ссылке';
                                                            break;
                                                        default:
                                                            echo $video['status'];
                                                    }
                                                    ?>
                                                </span>
                                            </td>
                                            <td class="video-actions-cell">
                                                <div class="video-actions">
                                                    <form class="status-form">
                                                        <input type="hidden" name="video_id"
                                                            value="<?php echo $video['video_id']; ?>">
                                                        <select name="status" class="status-select">
                                                            <option value="public" <?php echo $video['status'] === 'public' ? 'selected' : ''; ?>>Публичное</option>
                                                            <option value="private" <?php echo $video['status'] === 'private' ? 'selected' : ''; ?>>Приватное</option>
                                                            <option value="unlisted" <?php echo $video['status'] === 'unlisted' ? 'selected' : ''; ?>>По ссылке</option>
                                                        </select>
                                                        <button type="submit" class="btn-small">Изменить</button>
                                                    </form>
                                                    <form class="delete-video-form">
                                                        <input type="hidden" name="video_id"
                                                            value="<?php echo $video['video_id']; ?>">
                                                        <button type="submit" class="btn-small btn-danger">Удалить</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <div id="history" class="profile-section" style="display: none;">
                    <h2 class="section-header">История просмотров</h2>

                    <?php 
                    if (empty($viewHistory)): 
                    ?>
                        <p class="no-videos">У вас пока нет просмотренных видео</p>
                    <?php else: ?>
                        <div class="videos-table-container">
                            <table class="videos-table">
                                <thead>
                                    <tr>
                                        <th>Превью</th>
                                        <th>Название</th>
                                        <th>Просмотры</th>
                                        <th>Дата просмотра</th>
                                        <th>Оценка</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($viewHistory as $video): ?>
                                        <tr class="video-row" data-video-id="<?php echo $video['video_id']; ?>">
                                            <td class="video-thumbnail-cell">
                                                <a href="video.php?id=<?php echo $video['video_id']; ?>" class="thumbnail-link">
                                                    <div class="thumbnail-container">
                                                        <img src="<?php echo $video['thumbnail_url']; ?>"
                                                            alt="<?php echo htmlspecialchars($video['title']); ?>">
                                                        <span class="video-duration"><?php echo VideoManager::formatDuration($video['duration']); ?></span>
                                                    </div>
                                                </a>
                                            </td>
                                            <td class="video-title-cell">
                                                <a href="video.php?id=<?php echo $video['video_id']; ?>" class="video-title">
                                                    <?php echo htmlspecialchars($video['title']); ?>
                                                </a>
                                                <div class="video-description">
                                                    <?php echo htmlspecialchars(substr($video['description'], 0, 100) . (strlen($video['description']) > 100 ? '...' : '')); ?>
                                                </div>
                                            </td>
                                            <td class="video-views-cell">
                                                <?php echo number_format($video['views_count'], 0, '', ' '); ?>
                                            </td>
                                            <td class="video-date-cell">
                                                <?php echo date('d.m.Y H:i', strtotime($video['viewed_at'])); ?>
                                            </td>
                                            <td class="video-reaction-cell">
                                                <?php if ($video['is_like'] !== null): ?>
                                                    <?php echo $video['is_like'] ? 'Лайк' : 'Дизлайк'; ?>
                                                <?php else: ?>
                                                    Нет оценки
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <div id="edit-profile" class="profile-section" style="display: none;">
                    <h2 class="section-header">Редактирование профиля</h2>

                    <div class="profile-edit-container">
                        <div class="profile-edit-media">
                            <div class="avatar-upload">
                                <h3>Аватар профиля</h3>
                                <div class="avatar-preview">
                                    <img src="<?php echo $user['avatar_url']; ?>" alt="Аватар" id="avatar-preview">
                                </div>
                                <label for="avatar-input" class="btn">Загрузить аватар</label>
                                <input type="file" id="avatar-input" accept="image/*" style="display: none;">
                            </div>

                            <div class="banner-upload">
                                <h3>Баннер профиля</h3>
                                <div class="banner-preview">
                                    <?php if (!empty($user['banner_url'])): ?>
                                        <img src="<?php echo $user['banner_url']; ?>" alt="Баннер" id="banner-preview">
                                    <?php else: ?>
                                        <div class="no-banner">Баннер не загружен</div>
                                    <?php endif; ?>
                                </div>
                                <label for="banner-input" class="btn">Загрузить баннер</label>
                                <input type="file" id="banner-input" accept="image/*" style="display: none;">
                            </div>
                        </div>

                        <div class="profile-edit-info">
                            <form id="profile-form">
                                <div class="form-group">
                                    <label for="display_name">Отображаемое имя</label>
                                    <input type="text" id="display_name" name="display_name"
                                        value="<?php echo htmlspecialchars($user['display_name']); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label for="description">О себе</label>
                                    <textarea id="description" name="description"
                                        rows="5"><?php echo htmlspecialchars($user['description']); ?></textarea>
                                </div>

                                <h3>Социальные сети</h3>

                                <div class="form-group">
                                    <label for="vk">ВКонтакте</label>
                                    <input type="url" id="vk" name="vk"
                                        value="<?php echo htmlspecialchars($user['social_links']['vk'] ?? ''); ?>"
                                        placeholder="https://vk.com/username">
                                </div>

                                <div class="form-group">
                                    <label for="instagram">Instagram</label>
                                    <input type="url" id="instagram" name="instagram"
                                        value="<?php echo htmlspecialchars($user['social_links']['instagram'] ?? ''); ?>"
                                        placeholder="https://instagram.com/username">
                                </div>

                                <div class="form-group">
                                    <label for="facebook">Facebook</label>
                                    <input type="url" id="facebook" name="facebook"
                                        value="<?php echo htmlspecialchars($user['social_links']['facebook'] ?? ''); ?>"
                                        placeholder="https://facebook.com/username">
                                </div>

                                <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div id="upload" class="profile-section" style="display: none;">
                    <h2 class="section-header">Загрузка видео</h2>

                    <div class="upload-container">
                        <p>В разработке (вечной)</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/profile.js"></script>
    <script src="js/utils.js"></script>
</body>

</html>