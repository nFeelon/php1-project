<?php
header('Content-Type: application/json');
require_once '../../includes/User.php';
require_once '../../includes/auth_check.php';

try {
    // Проверяем авторизацию
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Пользователь не авторизован'
        ]);
        exit;
    }

    $user = new User();
    $result = $user->logout();

    http_response_code($result['success'] ? 200 : 400);
    echo json_encode($result);
} catch (Exception $e) {
    error_log('Ошибка при выходе: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Произошла ошибка при выходе. Попробуйте позже.'
    ]);
}
