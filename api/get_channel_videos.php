<?php
require_once '../includes/db_connection.php';
require_once '../includes/VideoManager.php';

header('Content-Type: application/json');

$channelId = isset($_GET['id']) ? (int) $_GET['id'] : null;
if (!$channelId) {
    echo json_encode(['success' => false, 'message' => 'Не указан ID канала']);
    exit;
}

session_start();
$currentUserId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

$videoManager = new VideoManager();

try {
    // Получаем видео канала с учетом прав доступа
    // Если пользователь просматривает свой канал, он видит все свои видео
    // Если пользователь просматривает чужой канал, он видит только публичные видео
    $videos = $videoManager->getUserVideos($channelId, $currentUserId);

    $db = Database::getInstance()->getConnection();
    $query = "SELECT v.* 
              FROM videos v 
              WHERE v.user_id = :channelId 
              AND v.status = 'public' 
              ORDER BY v.views_count DESC 
              LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':channelId', $channelId, PDO::PARAM_INT);
    $stmt->execute();
    $trailer = $stmt->fetch(PDO::FETCH_ASSOC);

    $formattedVideos = [];
    foreach ($videos as $key => $video) {
        $formattedVideo = $video;
        $formattedVideo['duration_formatted'] = $videoManager->formatDuration($video['duration']);
        $formattedVideo['upload_date_formatted'] = $videoManager->formatUploadDate($video['upload_date']);
        $viewsCount = (int) $video['views_count'];
        $formattedVideo['views_formatted'] = number_format($viewsCount, 0, '', ' ') . ' просмотров';
        $formattedVideos[$key] = $formattedVideo;
    }

    if ($trailer) {
        $trailer['duration_formatted'] = $videoManager->formatDuration($trailer['duration']);
    }

    $response = [
        'success' => true,
        'videos' => $formattedVideos,
        'trailer' => $trailer,
        'videos_count' => count($formattedVideos)
    ];

    echo json_encode($response);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Произошла ошибка: ' . $e->getMessage()]);
}
