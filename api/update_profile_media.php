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
$userManager = new UserManager();

$mediaType = isset($_POST['media_type']) ? $_POST['media_type'] : null;

if (!$mediaType || !in_array($mediaType, ['avatar', 'banner'])) {
    echo json_encode(['success' => false, 'message' => 'Неверный тип медиа']);
    exit;
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $errorMessage = 'Ошибка при загрузке файла';
    if (isset($_FILES['file']['error'])) {
        switch ($_FILES['file']['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $errorMessage = 'Размер файла превышает допустимый';
                break;
            case UPLOAD_ERR_PARTIAL:
                $errorMessage = 'Файл был загружен частично';
                break;
            case UPLOAD_ERR_NO_FILE:
                $errorMessage = 'Файл не был загружен';
                break;
        }
    }
    echo json_encode(['success' => false, 'message' => $errorMessage]);
    exit;
}

try {
    $uploadDir = $mediaType === 'avatar' ? '../server/user/avatars/' : '../server/user/banners/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileInfo = pathinfo($_FILES['file']['name']);
    $extension = strtolower($fileInfo['extension']);

    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($extension, $allowedTypes)) {
        echo json_encode([
            'success' => false,
            'message' => 'Недопустимый тип файла. Разрешены только JPG, PNG и GIF'
        ]);
        exit;
    }

    $newFileName = $userId . '_' . time() . '.' . $extension;
    $targetFile = $uploadDir . $newFileName;

    if (move_uploaded_file($_FILES['file']['tmp_name'], $targetFile)) {
        $relativePath = '/' . str_replace('../', '', $targetFile);

        $db = Database::getInstance()->getConnection();
        $field = $mediaType === 'avatar' ? 'avatar_url' : 'banner_url';
        $stmt = $db->prepare("UPDATE users SET $field = ? WHERE user_id = ?");
        $stmt->execute([$relativePath, $userId]);

        $updatedUser = $userManager->getUserById($userId);

        echo json_encode([
            'success' => true,
            'message' => ($mediaType === 'avatar' ? 'Аватар' : 'Баннер') . ' успешно обновлен',
            'url' => $relativePath,
            'user' => [
                'user_id' => $updatedUser['user_id'],
                'avatar_url' => $updatedUser['avatar_url'],
                'banner_url' => $updatedUser['banner_url']
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка при перемещении загруженного файла'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка при обновлении ' . ($mediaType === 'avatar' ? 'аватара' : 'баннера') . ': ' . $e->getMessage()
    ]);
}
