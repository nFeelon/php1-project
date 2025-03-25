<?php
// Заголовки для предотвращения кэширования
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Content-Type: image/jpeg');

require_once '../includes/Database.php';

if (!isset($_GET['video_id']) || empty($_GET['video_id'])) {
    http_response_code(400);
    exit('Missing video_id parameter');
}

$videoId = (int)$_GET['video_id'];

try {
    $db = Database::getInstance()->getConnection();
    
    $query = "SELECT thumbnail_url, file_path FROM videos WHERE video_id = :video_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':video_id', $videoId, PDO::PARAM_INT);
    $stmt->execute();
    
    $video = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$video) {
        http_response_code(404);
        exit('Video not found');
    }

    $thumbnailPath = '';
    
    if (!empty($video['thumbnail_url'])) {
        // Если путь к превью указан в БД
        $thumbnailPath = $_SERVER['DOCUMENT_ROOT'] . $video['thumbnail_url'];
    } else {
        // Если путь не указан, генерируем его на основе пути к видео
        $videoFileName = pathinfo($video['file_path'], PATHINFO_FILENAME);
        $thumbnailPath = $_SERVER['DOCUMENT_ROOT'] . '/server/video/thumbnails/' . $videoFileName . '.jpg';
    }

    if (!file_exists($thumbnailPath)) {
        // Если файл не найден, возвращаем заглушку
        $thumbnailPath = $_SERVER['DOCUMENT_ROOT'] . '/img/default-thumbnail.jpg';
        
        // Если заглушки нет, выдаем ошибку
        if (!file_exists($thumbnailPath)) {
            http_response_code(404);
            exit('Thumbnail not found');
        }
    }
    
    $width = isset($_GET['width']) ? (int)$_GET['width'] : 0;
    $height = isset($_GET['height']) ? (int)$_GET['height'] : 0;
    
    if ($width > 0 && $height > 0 && extension_loaded('gd')) {
        $sourceImage = imagecreatefromjpeg($thumbnailPath);
        $targetImage = imagecreatetruecolor($width, $height);

        imagecopyresampled(
            $targetImage, $sourceImage,
            0, 0, 0, 0,
            $width, $height,
            imagesx($sourceImage), imagesy($sourceImage)
        );
        
        imagejpeg($targetImage, null, 85);
        imagedestroy($sourceImage);
        imagedestroy($targetImage);
    } else {
        // Если не нужно изменять размер или нет GD, просто выводим файл
        readfile($thumbnailPath);
    }
} catch (Exception $e) {
    http_response_code(500);
    exit('Server error: ' . $e->getMessage());
}
