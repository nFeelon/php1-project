<?php
require_once '../includes/db_connection.php';
require_once '../includes/UserManager.php';
require_once '../includes/auth_check.php';

header('Content-Type: application/json');
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Требуется авторизация']);
    exit;
}

$userId = $_SESSION['user_id'];
$userManager = new UserManager();

try {
    $user = $userManager->getUserById($userId);

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Пользователь не найден']);
        exit;
    }

    if (!empty($user['social_links'])) {
        $user['social_links'] = json_decode($user['social_links'], true);
    } else {
        $user['social_links'] = [];
    }

    $response = [
        'success' => true,
        'user' => [
            'user_id' => $user['user_id'],
            'username' => $user['username'],
            'display_name' => $user['display_name'],
            'email' => $user['email'],
            'avatar_url' => $user['avatar_url'],
            'banner_url' => $user['banner_url'],
            'description' => $user['description'],
            'social_links' => $user['social_links'],
            'subscribers_count' => $user['subscribers_count'],
            'created_at' => $user['created_at']
        ]
    ];

    echo json_encode($response);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка при получении данных профиля: ' . $e->getMessage()]);
}
