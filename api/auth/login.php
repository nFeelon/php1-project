<?php
header('Content-Type: application/json');
require_once '../../includes/User.php';

try {
    // Получаем данные из запроса
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        throw new Exception('Неверный формат данных');
    }

    // Валидация данных
    if (empty($data['email']) || empty($data['password'])) {
        throw new Exception('Email и пароль обязательны');
    }

    // Авторизация пользователя
    $user = new User();
    $result = $user->login(
        $data['email'], 
        $data['password'],
        isset($data['remember']) ? $data['remember'] : false
    );

    http_response_code($result['success'] ? 200 : 401);
    echo json_encode($result);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
