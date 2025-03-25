<?php
require_once '../includes/db_connection.php';
require_once '../includes/UserManager.php';
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

if (!isset($data['display_name'])) {
    echo json_encode(['success' => false, 'message' => 'Не указано отображаемое имя']);
    exit;
}

$userManager = new UserManager();

try {
    $user = $userManager->getUserById($userId);

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Пользователь не найден']);
        exit;
    }

    $displayName = trim($data['display_name']);
    $description = isset($data['description']) ? trim($data['description']) : $user['description'];

    $socialLinks = [];
    if (isset($data['social_links']) && is_array($data['social_links'])) {
        $socialLinks = $data['social_links'];
    } elseif (isset($data['vk']) || isset($data['instagram']) || isset($data['facebook'])) {
        // Для обратной совместимости с формой
        if (!empty($data['vk']))
            $socialLinks['vk'] = $data['vk'];
        if (!empty($data['instagram']))
            $socialLinks['instagram'] = $data['instagram'];
        if (!empty($data['facebook']))
            $socialLinks['facebook'] = $data['facebook'];
    } else {
        $socialLinks = json_decode($user['social_links'], true) ?: [];
    }

    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("UPDATE users SET display_name = ?, description = ?, social_links = ? WHERE user_id = ?");
    $stmt->execute([$displayName, $description, json_encode($socialLinks), $userId]);

    $_SESSION['display_name'] = $displayName;

    $updatedUser = $userManager->getUserById($userId);
    if (!empty($updatedUser['social_links'])) {
        $updatedUser['social_links'] = json_decode($updatedUser['social_links'], true);
    } else {
        $updatedUser['social_links'] = [];
    }

    echo json_encode([
        'success' => true,
        'message' => 'Профиль успешно обновлен',
        'user' => [
            'user_id' => $updatedUser['user_id'],
            'username' => $updatedUser['username'],
            'display_name' => $updatedUser['display_name'],
            'description' => $updatedUser['description'],
            'social_links' => $updatedUser['social_links']
        ]
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка при обновлении профиля: ' . $e->getMessage()]);
}
