<?php
require_once '../includes/auth_check.php';
require_once '../includes/Database.php';
require_once '../includes/UserManager.php';
require_once '../includes/SubscriptionManager.php';
require_once '../includes/helpers.php';

if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Требуется авторизация']);
    exit;
}

$channelId = isset($_POST['channel_id']) ? (int) $_POST['channel_id'] : null;
$action = isset($_POST['action']) ? $_POST['action'] : null;

if (!$channelId || !in_array($action, ['subscribe', 'unsubscribe'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Некорректные параметры']);
    exit;
}

$subscriberId = $_SESSION['user_id'];
if ($subscriberId == $channelId) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Нельзя подписаться на свой канал']);
    exit;
}

$userManager = new UserManager();
$subscriptionManager = new SubscriptionManager();

try {
    $channel = $userManager->getUserById($channelId);
    
    if (!$channel) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Канал не найден']);
        exit;
    }
    
    $result = false;
    $newSubscribersCount = 0;
    
    if ($action === 'subscribe') {
        $result = $subscriptionManager->subscribe($subscriberId, $channelId);
        if ($result) {
            $message = 'Вы успешно подписались на канал';
            $newSubscribersCount = $channel['subscribers_count'] + 1;
        } else {
            $message = 'Не удалось подписаться на канал';
        }
    } else {
        $result = $subscriptionManager->unsubscribe($subscriberId, $channelId);
        if ($result) {
            $message = 'Вы успешно отписались от канала';
            $newSubscribersCount = max(0, $channel['subscribers_count'] - 1);
        } else {
            $message = 'Не удалось отписаться от канала';
        }
    }
    
    $subscribersFormatted = formatSubscribersCount($newSubscribersCount);

    header('Content-Type: application/json');
    echo json_encode([
        'success' => $result,
        'message' => $message,
        'subscribers_count' => $newSubscribersCount,
        'subscribers_formatted' => $subscribersFormatted,
        'is_subscribed' => ($action === 'subscribe' && $result)
    ]);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Произошла ошибка: ' . $e->getMessage()]);
}
