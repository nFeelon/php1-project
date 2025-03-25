<?php
require_once '../includes/db_connection.php';

header('Content-Type: application/json');

$channelId = isset($_GET['id']) ? (int) $_GET['id'] : null;
if (!$channelId) {
    echo json_encode(['success' => false, 'message' => 'Не указан ID канала']);
    exit;
}

session_start();
$currentUserId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

$db = Database::getInstance()->getConnection();

try {
    $query = "SELECT user_id, username, display_name, avatar_url, banner_url, description, 
              social_links, subscribers_count, created_at 
              FROM users 
              WHERE user_id = :channelId";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':channelId', $channelId, PDO::PARAM_INT);
    $stmt->execute();
    $channel = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$channel) {
        echo json_encode(['success' => false, 'message' => 'Канал не найден']);
        exit;
    }

    $isOwner = $currentUserId && $currentUserId == $channelId;

    $isSubscribed = false;
    if ($currentUserId && !$isOwner) {
        $query = "SELECT 1 FROM subscriptions WHERE subscriber_id = :subscriber_id AND channel_id = :channel_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':subscriber_id', $currentUserId, PDO::PARAM_INT);
        $stmt->bindParam(':channel_id', $channelId, PDO::PARAM_INT);
        $stmt->execute();
        $isSubscribed = (bool) $stmt->fetchColumn();
    }

    $createdDate = new DateTime($channel['created_at']);
    $formattedCreatedDate = $createdDate->format('d.m.Y');
    $socialLinks = $channel['social_links'] ? json_decode($channel['social_links'], true) : [];

    $response = [
        'success' => true,
        'channel' => [
            'user_id' => $channel['user_id'],
            'username' => $channel['username'],
            'display_name' => $channel['display_name'] ?? $channel['username'],
            'avatar_url' => '/api/get_avatar.php?user_id=' . $channel['user_id'] . '&size=120',
            'banner_url' => $channel['banner_url'],
            'description' => $channel['description'],
            'social_links' => $socialLinks,
            'subscribers_count' => $channel['subscribers_count'],
            'subscribers_formatted' => number_format($channel['subscribers_count'], 0, '', ' ') . ' подписчиков',
            'created_at' => $channel['created_at'],
            'created_at_formatted' => $formattedCreatedDate
        ],
        'is_owner' => $isOwner,
        'is_subscribed' => $isSubscribed
    ];

    echo json_encode($response);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Произошла ошибка: ' . $e->getMessage()]);
}
