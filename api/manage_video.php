<?php
require_once '../includes/db_connection.php';
require_once '../includes/VideoManager.php';
require_once '../includes/auth_check.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Требуется авторизация']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Метод не поддерживается']);
    exit;
}

$userId = $_SESSION['user_id'];

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    $data = $_POST;
}

if (!isset($data['action']) || !isset($data['video_id'])) {
    echo json_encode(['success' => false, 'message' => 'Не указаны обязательные параметры']);
    exit;
}

$action = $data['action'];
$videoId = (int)$data['video_id'];

if (!in_array($action, ['update_status', 'delete'])) {
    echo json_encode(['success' => false, 'message' => 'Неизвестное действие']);
    exit;
}

$videoManager = new VideoManager();

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT * FROM videos WHERE video_id = ?");
    $stmt->execute([$videoId]);
    $video = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$video || $video['user_id'] != $userId) {
        echo json_encode(['success' => false, 'message' => 'У вас нет прав для управления этим видео']);
        exit;
    }

    if ($action === 'update_status') {
        if (!isset($data['status'])) {
            echo json_encode(['success' => false, 'message' => 'Не указан статус видео']);
            exit;
        }

        $status = $data['status'];
        if (!in_array($status, ['public', 'private', 'unlisted'])) {
            echo json_encode(['success' => false, 'message' => 'Неверный статус видео']);
            exit;
        }

        $stmt = $db->prepare("UPDATE videos SET status = ? WHERE video_id = ?");
        $stmt->execute([$status, $videoId]);

        echo json_encode([
            'success' => true,
            'message' => 'Статус видео успешно изменен',
            'video' => [
                'video_id' => $videoId,
                'status' => $status
            ]
        ]);
    } elseif ($action === 'delete') {
        $stmt = $db->prepare("DELETE FROM videos WHERE video_id = ?");
        $stmt->execute([$videoId]);

        // Удаляем файлы видео и превью (в реальном приложении)
        // Примечание: в этой версии мы только удаляем запись из БД

        echo json_encode([
            'success' => true,
            'message' => 'Видео успешно удалено',
            'video_id' => $videoId
        ]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка при управлении видео: ' . $e->getMessage()]);
}
