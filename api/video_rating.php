<?php
header('Content-Type: application/json');
require_once '../includes/auth_check.php';
require_once '../includes/Database.php';

// Включаем отображение ошибок для отладки
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

error_log('Что-то с запросом =( : ' . file_get_contents('php://input'));

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Необходима авторизация'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Метод не поддерживается'
    ]);
    exit;
}

$rawData = file_get_contents('php://input');
error_log('Raw request data: ' . $rawData);
$data = json_decode($rawData, true);
error_log('Decoded data: ' . print_r($data, true));

if (!isset($data['video_id']) || !isset($data['action'])) {
    error_log('Missing parameters. Data: ' . print_r($data, true));
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Отсутствуют обязательные параметры'
    ]);
    exit;
}

$videoId = (int)$data['video_id'];
$action = $data['action'];
$userId = $_SESSION['user_id'];

error_log("Processing rating: video_id=$videoId, action=$action, user_id=$userId");

try {
    $db = Database::getInstance()->getConnection();

    $stmt = $db->prepare("SELECT user_id FROM videos WHERE video_id = ?");
    $stmt->execute([$videoId]);
    $video = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$video) {
        error_log("Video not found: $videoId");
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Видео не найдено'
        ]);
        exit;
    }

    $stmt = $db->prepare("SELECT is_like FROM view_history WHERE user_id = ? AND video_id = ?");
    $stmt->execute([$userId, $videoId]);
    $existingRating = $stmt->fetch(PDO::FETCH_ASSOC);
    error_log("Existing rating: " . print_r($existingRating, true));

    $isLike = null;
    if ($action === 'like') {
        $isLike = 1;
    } elseif ($action === 'dislike') {
        $isLike = 0;
    }
    error_log("New is_like value: " . var_export($isLike, true));

    if ($existingRating) {
        // Если текущая оценка совпадает с новой, снимаем её
        if ($existingRating['is_like'] === $isLike && $action !== 'remove') {
            $isLike = null;
        }
        
        error_log("Updating existing rating to: " . var_export($isLike, true));
        $stmt = $db->prepare("
            UPDATE view_history 
            SET is_like = ?, reaction_at = CURRENT_TIMESTAMP 
            WHERE user_id = ? AND video_id = ?
        ");
        $stmt->execute([$isLike, $userId, $videoId]);
    } else {
        error_log("Creating new rating with is_like = " . var_export($isLike, true));
        $stmt = $db->prepare("
            INSERT INTO view_history (user_id, video_id, is_like, reaction_at) 
            VALUES (?, ?, ?, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([$userId, $videoId, $isLike]);
    }

    $stmt = $db->prepare("
        SELECT 
            (SELECT COUNT(*) FROM view_history WHERE video_id = ? AND is_like = 1) as likes_count,
            (SELECT COUNT(*) FROM view_history WHERE video_id = ? AND is_like = 0) as dislikes_count
    ");
    $stmt->execute([$videoId, $videoId]);
    $counts = $stmt->fetch(PDO::FETCH_ASSOC);
    error_log("Обновление оценок: " . print_r($counts, true));

    $currentRating = null;
    if ($isLike !== null) {
        $currentRating = $isLike == 1;
    }
    
    $response = [
        'success' => true,
        'message' => 'Оценка успешно обновлена',
        'data' => [
            'likes_count' => (int)$counts['likes_count'],
            'dislikes_count' => (int)$counts['dislikes_count'],
            'current_rating' => $currentRating
        ]
    ];
    error_log("Sending response: " . print_r($response, true));
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log('Error processing video rating: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Произошла ошибка при обработке запроса: ' . $e->getMessage()
    ]);
}
