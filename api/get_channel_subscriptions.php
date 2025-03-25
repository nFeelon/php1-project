<?php
require_once '../includes/db_connection.php';

header('Content-Type: application/json');

$channelId = isset($_GET['id']) ? (int) $_GET['id'] : null;
if (!$channelId) {
    echo json_encode(['success' => false, 'message' => 'Не указан ID канала']);
    exit;
}

$db = Database::getInstance()->getConnection();

try {
    $query = "SELECT u.user_id, u.username, u.display_name, u.avatar_url, u.subscribers_count
              FROM subscriptions s
              JOIN users u ON s.channel_id = u.user_id
              WHERE s.subscriber_id = :channelId
              ORDER BY s.subscribed_at DESC
              LIMIT 10";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':channelId', $channelId, PDO::PARAM_INT);
    $stmt->execute();
    $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($subscriptions as &$subscription) {
        $subscription['display_name'] = $subscription['display_name'] ?? $subscription['username'];
        $subscription['avatar_url'] = '/api/get_avatar.php?user_id=' . $subscription['user_id'] . '&size=50';
        $subscription['subscribers_formatted'] = number_format($subscription['subscribers_count'], 0, '', ' ') . ' подписчиков';
    }

    $response = [
        'success' => true,
        'subscriptions' => $subscriptions,
        'subscriptions_count' => count($subscriptions)
    ];

    echo json_encode($response);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Произошла ошибка: ' . $e->getMessage()]);
}
