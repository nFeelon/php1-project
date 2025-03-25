<?php
require_once '../includes/db_connection.php';
require_once '../includes/VideoManager.php';
require_once '../includes/auth_check.php';
require_once '../includes/helpers.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Требуется авторизация']);
    exit;
}

$userId = $_SESSION['user_id'];
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'date_desc';
$videoManager = new VideoManager();

try {
    $videos = $videoManager->getUserVideos($userId, $userId, $sort);
    $formattedVideos = [];
    foreach ($videos as $video) {
        $formattedVideos[] = [
            'video_id' => $video['video_id'],
            'title' => $video['title'],
            'description' => $video['description'],
            'thumbnail_url' => $video['thumbnail_url'],
            'file_path' => $video['file_path'],
            'duration' => $video['duration'],
            'duration_formatted' => formatDuration($video['duration']),
            'views_count' => $video['views_count'],
            'views_formatted' => formatViewsCount($video['views_count']),
            'likes_count' => $video['likes_count'],
            'dislikes_count' => $video['dislikes_count'],
            'status' => $video['status'],
            'uploaded_at' => $video['uploaded_at'],
            'uploaded_at_formatted' => formatDate($video['uploaded_at'])
        ];
    }

    echo json_encode([
        'success' => true,
        'videos' => $formattedVideos,
        'sort' => $sort
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка при получении видео: ' . $e->getMessage()]);
}
