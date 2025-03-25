<?php
/**
 * Компонент карточки видео
 * 
 * Ожидает массив $video с ключами:
 * - video_id: ID видео
 * - title: Название видео
 * - duration_formatted: Отформатированная длительность (или duration для прямого использования)
 * - thumbnail_url: URL превью (или генерируется автоматически)
 * - user_id: ID пользователя
 * - username: Имя пользователя
 * - display_name: Отображаемое имя пользователя (опционально)
 * - views_formatted: Отформатированное количество просмотров (или views_count для прямого использования)
 * - upload_date_formatted: Отформатированная дата загрузки (или upload_date для прямого использования)
 */

// Форматирование данных, если они не были отформатированы ранее
if (!isset($video['duration_formatted']) && isset($video['duration'])) {
    $video['duration_formatted'] = isset($videoManager) ? 
        $videoManager->formatDuration($video['duration']) : 
        (function($seconds) {
            $minutes = floor($seconds / 60);
            $seconds = $seconds % 60;
            return sprintf('%d:%02d', $minutes, $seconds);
        })($video['duration']);
}

if (!isset($video['views_formatted']) && isset($video['views_count'])) {
    $viewsCount = (int)$video['views_count'];
    $video['views_formatted'] = number_format($viewsCount, 0, '', ' ') . ' ' . 
        (function($count) {
            $forms = ['просмотр', 'просмотра', 'просмотров'];
            $cases = [2, 0, 1, 1, 1, 2];
            return $forms[($count % 100 > 4 && $count % 100 < 20) ? 2 : $cases[min($count % 10, 5)]];
        })($viewsCount);
}

if (!isset($video['upload_date_formatted']) && isset($video['upload_date'])) {
    $uploadDate = new DateTime($video['upload_date']);
    $now = new DateTime();
    $interval = $uploadDate->diff($now);
    
    if ($interval->y > 0) {
        $video['upload_date_formatted'] = $interval->y . ' ' . 
            (function($count) {
                $forms = ['год', 'года', 'лет'];
                $cases = [2, 0, 1, 1, 1, 2];
                return $forms[($count % 100 > 4 && $count % 100 < 20) ? 2 : $cases[min($count % 10, 5)]];
            })($interval->y) . ' назад';
    } elseif ($interval->m > 0) {
        $video['upload_date_formatted'] = $interval->m . ' ' . 
            (function($count) {
                $forms = ['месяц', 'месяца', 'месяцев'];
                $cases = [2, 0, 1, 1, 1, 2];
                return $forms[($count % 100 > 4 && $count % 100 < 20) ? 2 : $cases[min($count % 10, 5)]];
            })($interval->m) . ' назад';
    } elseif ($interval->d > 0) {
        $video['upload_date_formatted'] = $interval->d . ' ' . 
            (function($count) {
                $forms = ['день', 'дня', 'дней'];
                $cases = [2, 0, 1, 1, 1, 2];
                return $forms[($count % 100 > 4 && $count % 100 < 20) ? 2 : $cases[min($count % 10, 5)]];
            })($interval->d) . ' назад';
    } else {
        $video['upload_date_formatted'] = 'Сегодня';
    }
}

// Если thumbnail_url отсутствует или не является абсолютным путем, генерируем его
if (!isset($video['thumbnail_url']) || strpos($video['thumbnail_url'], 'http') !== 0 && strpos($video['thumbnail_url'], '/api') !== 0) {
    $video['thumbnail_url'] = '/api/get_thumbnail.php?video_id=' . $video['video_id'];
}
?>
<div class="video-card">
    <a href="video.php?id=<?php echo $video['video_id']; ?>">
        <div class="video-thumbnail">
            <img loading="lazy" src="<?php echo $video['thumbnail_url']; ?>" alt="<?php echo htmlspecialchars($video['title']); ?>">
            <div class="video-duration"><?php echo $video['duration_formatted']; ?></div>
        </div>
    </a>
    <div class="video-info">
        <div class="channel-info">
            <a href="channel.php?id=<?php echo $video['user_id']; ?>" class="channel-link">
                <div class="channel-avatar">
                    <img loading="lazy" src="/api/get_avatar.php?user_id=<?php echo $video['user_id']; ?>&size=36" alt="<?php echo htmlspecialchars($video['display_name'] ?? $video['username']); ?>">
                </div>
                <div class="channel-name"><?php echo htmlspecialchars($video['display_name'] ?? $video['username']); ?></div>
            </a>
        </div>
        <a href="video.php?id=<?php echo $video['video_id']; ?>">
            <h3 class="video-title"><?php echo htmlspecialchars($video['title']); ?></h3>
        </a>
        <div class="video-stats">
            <div class="video-views"><?php echo $video['views_formatted']; ?></div>
            <div class="video-upload-date"><?php echo $video['upload_date_formatted']; ?></div>
        </div>
    </div>
</div>
