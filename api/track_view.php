<?php
header('Content-Type: application/json');
require_once '../includes/auth_check.php';
require_once '../includes/Database.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['video_id']) || !isset($data['progress_seconds'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Отсутствуют обязательные параметры'
    ]);
    exit;
}

$videoId = (int) $data['video_id'];
$progressSeconds = (int) $data['progress_seconds'];
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

try {
    $db = Database::getInstance()->getConnection();

    if ($userId) {
        $stmt = $db->prepare("SELECT view_id, progress_seconds FROM view_history WHERE user_id = ? AND video_id = ?");
        $stmt->execute([$userId, $videoId]);
        $existingView = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingView) {
            // Обновляем только прогресс просмотра, если он больше текущего
            if ($progressSeconds > $existingView['progress_seconds']) {
                $stmt = $db->prepare("
                    UPDATE view_history 
                    SET progress_seconds = ?, viewed_at = CURRENT_TIMESTAMP 
                    WHERE view_id = ?
                ");
                $stmt->execute([$progressSeconds, $existingView['view_id']]);
            }
        } else {
            $stmt = $db->prepare("
                INSERT INTO view_history (user_id, video_id, progress_seconds) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$userId, $videoId, $progressSeconds]);

            // Увеличиваем счетчик просмотров видео только при первом просмотре
            $stmt = $db->prepare("UPDATE videos SET views_count = views_count + 1 WHERE video_id = ?");
            $stmt->execute([$videoId]);
        }
    } else {
        $ipAddress = $_SERVER['REMOTE_ADDR'];

        // Проверяем, просматривал ли этот IP данное видео в течение последних 24 часов
        $stmt = $db->prepare("
            SELECT COUNT(*) as count 
            FROM anonymous_views 
            WHERE ip_address = ? AND video_id = ? AND viewed_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        $stmt->execute([$ipAddress, $videoId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['count'] == 0) {
            // Записываем анонимный просмотр
            $stmt = $db->prepare("
                INSERT INTO anonymous_views (ip_address, video_id) 
                VALUES (?, ?)
            ");
            $stmt->execute([$ipAddress, $videoId]);
            $stmt = $db->prepare("UPDATE videos SET views_count = views_count + 1 WHERE video_id = ?");
            $stmt->execute([$videoId]);
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Просмотр успешно отслежен'
    ]);

} catch (Exception $e) {
    error_log('Ошибка при отслеживании просмотра: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Произошла ошибка при обработке запроса'
    ]);
}
