<?php
require_once '../includes/auth_check.php';
require_once '../includes/Database.php';
require_once '../includes/VideoManager.php';

header('Content-Type: application/json');

if (!isset($_GET['video_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Не указан ID видео']);
    exit;
}

$videoId = (int)$_GET['video_id'];
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

$videoManager = new VideoManager();
$video = $videoManager->getVideoById($videoId, $userId);

if (!$video) {
    http_response_code(404);
    echo json_encode(['error' => 'Видео не найдено или у вас нет прав для его просмотра']);
    exit;
}

echo json_encode([
    'success' => true,
    'video' => $video
]);
