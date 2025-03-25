<?php
require_once '../includes/auth_check.php';
require_once '../includes/Database.php';
require_once '../includes/VideoManager.php';

header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

if (!isset($_GET['video_id'])) {
    http_response_code(400);
    exit('Не указан ID видео');
}

$videoId = (int)$_GET['video_id'];
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

try {
    $videoManager = new VideoManager();
    $video = $videoManager->getVideoById($videoId, $userId);

    if (!$video) {
        http_response_code(404);
        exit('Видео не найдено или у вас нет прав для его просмотра');
    }

    $videoPath = $_SERVER['DOCUMENT_ROOT'] . $video['file_path'];

    if (!file_exists($videoPath)) {
        http_response_code(404);
        exit('Файл видео не найден');
    }

    // Определяем MIME тип
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $videoPath);
    finfo_close($finfo);

    header('Content-Type: ' . $mimeType);
    header('Accept-Ranges: bytes');

    $size = filesize($videoPath);

    // Поддержка частичной загрузки для перемотки видео
    if (isset($_SERVER['HTTP_RANGE'])) {
        // Парсим заголовок Range
        list($start, $end) = sscanf($_SERVER['HTTP_RANGE'], 'bytes=%d-%d');
        
        if (!isset($end)) {
            $end = $size - 1;
        }
        
        // Устанавливаем заголовки для частичного контента
        header('HTTP/1.1 206 Partial Content');
        header("Content-Range: bytes $start-$end/$size");
        header('Content-Length: ' . ($end - $start + 1));
        
        $fp = fopen($videoPath, 'rb');
        fseek($fp, $start);
        
        // Отправляем часть файла
        $bufferSize = 8192;
        $bytesRemaining = $end - $start + 1;
        
        while (!feof($fp) && $bytesRemaining > 0) {
            $bytes = min($bufferSize, $bytesRemaining);
            echo fread($fp, $bytes);
            $bytesRemaining -= $bytes;
            flush();
        }
        
        fclose($fp);
    } else {
        // Отправляем весь файл
        header('Content-Length: ' . $size);
        readfile($videoPath);
    }
} catch (Exception $e) {
    http_response_code(500);
    exit('Ошибка сервера: ' . $e->getMessage());
}
