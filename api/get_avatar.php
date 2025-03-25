<?php
// Заголовки для предотвращения кэширования
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Content-Type: image/png');

require_once '../includes/Database.php';

// Проверка наличия ID пользователя
if (!isset($_GET['user_id']) || empty($_GET['user_id'])) {
    http_response_code(400);
    exit('Отсутствует параметр user_id');
}

$userId = (int)$_GET['user_id'];

try {
    $db = Database::getInstance()->getConnection();
    
    $query = "SELECT avatar_url FROM users WHERE user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        // Если пользователь не найден, используем заглушку
        $avatarPath = $_SERVER['DOCUMENT_ROOT'] . '/img/default-avatar.png';
    } else {
        // Если путь к аватару указан и файл существует
        if (!empty($user['avatar_url'])) {
            // Проверяем, является ли путь URL или локальным файлом
            if (filter_var($user['avatar_url'], FILTER_VALIDATE_URL)) {
                // Если это URL, пытаемся получить изображение
                $avatarContent = @file_get_contents($user['avatar_url']);
                if ($avatarContent !== false) {
                    // Если удалось получить изображение, выводим его
                    $size = isset($_GET['size']) ? (int)$_GET['size'] : 0;
                    if ($size > 0 && extension_loaded('gd')) {
                        // Создаем изображение из контента
                        $image = imagecreatefromstring($avatarContent);
                        if ($image !== false) {
                            // Создаем новое изображение с указанным размером
                            $newImage = imagecreatetruecolor($size, $size);
                            imagecopyresampled(
                                $newImage, $image,
                                0, 0, 0, 0,
                                $size, $size,
                                imagesx($image), imagesy($image)
                            );
                            imagepng($newImage);
                            imagedestroy($image);
                            imagedestroy($newImage);
                            exit;
                        }
                    } else {
                        echo $avatarContent;
                        exit;
                    }
                }
            } else {
                $avatarPath = $_SERVER['DOCUMENT_ROOT'] . $user['avatar_url'];
                if (!file_exists($avatarPath)) {
                    $avatarPath = $_SERVER['DOCUMENT_ROOT'] . '/img/default-avatar.png';
                }
            }
        } else {
            $avatarPath = $_SERVER['DOCUMENT_ROOT'] . '/img/default-avatar.png';
        }
    }
    
    if (!isset($avatarPath) || !file_exists($avatarPath)) {
        http_response_code(404);
        exit('Аватар не найден');
    }

    $size = isset($_GET['size']) ? (int)$_GET['size'] : 0;

    if ($size > 0 && extension_loaded('gd')) {
        $imageInfo = getimagesize($avatarPath);
        $mimeType = $imageInfo['mime'];

        switch ($mimeType) {
            case 'image/jpeg':
            case 'image/jpg':
                $sourceImage = imagecreatefromjpeg($avatarPath);
                break;
            case 'image/png':
                $sourceImage = imagecreatefrompng($avatarPath);
                break;
            case 'image/gif':
                $sourceImage = imagecreatefromgif($avatarPath);
                break;
            default:
                readfile($avatarPath);
                exit;
        }
        
        $targetImage = imagecreatetruecolor($size, $size);
        
        // Если это PNG, сохраняем прозрачность
        if ($mimeType == 'image/png') {
            imagealphablending($targetImage, false);
            imagesavealpha($targetImage, true);
            $transparent = imagecolorallocatealpha($targetImage, 255, 255, 255, 127);
            imagefilledrectangle($targetImage, 0, 0, $size, $size, $transparent);
        }

        imagecopyresampled(
            $targetImage, $sourceImage,
            0, 0, 0, 0,
            $size, $size,
            imagesx($sourceImage), imagesy($sourceImage)
        );

        imagepng($targetImage);
        imagedestroy($sourceImage);
        imagedestroy($targetImage);
    } else {
        // Если не нужно изменять размер или нет GD, просто выводим файл
        readfile($avatarPath);
    }
} catch (Exception $e) {
    http_response_code(500);
    exit('Ошибка сервера: ' . $e->getMessage());
}
