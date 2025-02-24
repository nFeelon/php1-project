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
    if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
        throw new Exception('Все поля обязательны для заполнения');
    }

    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Неверный формат email');
    }

    if (strlen($data['password']) < 8) {
        throw new Exception('Пароль должен быть не менее 8 символов');
    }

    // Регистрация пользователя
    $user = new User();
    $result = $user->register(
        $data['username'],
        $data['email'],
        $data['password']
    );

    http_response_code($result['success'] ? 200 : 400);
    echo json_encode($result);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
