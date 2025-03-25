<?php
require_once '../includes/auth_check.php';
require_once '../includes/Database.php';
require_once '../includes/CommentManager.php';

$method = $_SERVER['REQUEST_METHOD'];

$commentManager = new CommentManager();
$data = json_decode(file_get_contents('php://input'), true);

if ($method === 'GET') {
    if (isset($_GET['parent_comment_id'])) {
        $parentCommentId = (int)$_GET['parent_comment_id'];
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        $includeNested = isset($_GET['include_nested']) && $_GET['include_nested'] === 'true';
        $userId = isLoggedIn() ? $_SESSION['user_id'] : null;

        if ($includeNested) {
            $replies = $commentManager->getAllCommentReplies($parentCommentId, $limit, $offset, $userId);
            $total = $commentManager->getAllCommentRepliesCount($parentCommentId);
        } else {
            $replies = $commentManager->getCommentReplies($parentCommentId, $limit, $offset, $userId);
            $total = $commentManager->getCommentRepliesCount($parentCommentId);
        }

        echo json_encode([
            'success' => true,
            'replies' => $replies,
            'total' => $total
        ]);
        exit;
    } elseif (!isset($_GET['video_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Не указан ID видео или ID родительского комментария']);
        exit;
    }
    
    $videoId = (int)$_GET['video_id'];
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    $userId = isLoggedIn() ? $_SESSION['user_id'] : null;
    $comments = $commentManager->getVideoComments($videoId, $limit, $offset, $userId);

    echo json_encode([
        'success' => true,
        'comments' => $comments,
        'total' => $commentManager->getCommentsCount($videoId)
    ]);
    exit;
}

// добавление комментария
if ($method === 'POST') {
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Необходима авторизация']);
        exit;
    }

    $action = isset($data['action']) ? $data['action'] : 'add';
    if ($action === 'like' || $action === 'unlike') {
        if (!isset($data['comment_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Не указан ID комментария']);
            exit;
        }
        
        $commentId = (int)$data['comment_id'];

        if ($commentId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Неверный ID комментария']);
            exit;
        }

        $userId = $_SESSION['user_id'];

        $commentExists = $commentManager->getCommentLikesCount($commentId) !== false;
        if (!$commentExists) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Комментарий не найден']);
            exit;
        }

        $result = false;
        if ($action === 'like') {
            $result = $commentManager->likeComment($commentId, $userId);
        } else {
            $result = $commentManager->unlikeComment($commentId, $userId);
        }
        
        if ($result) {
            $likesCount = $commentManager->getCommentLikesCount($commentId);
            
            echo json_encode([
                'success' => true,
                'message' => $action === 'like' ? 'Лайк добавлен' : 'Лайк удален',
                'likes_count' => $likesCount,
                'is_liked' => $action === 'like'
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Ошибка при обработке лайка']);
        }
        exit;
    }

    if ($action === 'edit') {
        if (!isset($data['comment_id']) || !isset($data['content'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Не указаны все необходимые параметры']);
            exit;
        }
        
        $commentId = (int)$data['comment_id'];
        $content = trim($data['content']);

        if ($commentId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Неверный ID комментария']);
            exit;
        }
        
        if (empty($content)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Текст комментария не может быть пустым']);
            exit;
        }

        if (!$commentManager->isCommentAuthor($commentId, $_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'У вас нет прав на редактирование этого комментария']);
            exit;
        }

        if ($commentManager->updateComment($commentId, $content)) {
            echo json_encode([
                'success' => true, 
                'message' => 'Комментарий успешно обновлен',
                'comment' => [
                    'comment_id' => $commentId,
                    'content' => $content
                ]
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Ошибка при обновлении комментария']);
        }
        exit;
    }

    if (!isset($data['video_id']) || !isset($data['content'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Не указаны обязательные параметры']);
        exit;
    }
    
    $videoId = (int)$data['video_id'];
    $content = trim($data['content']);
    $userId = $_SESSION['user_id'];
    $parentCommentId = isset($data['parent_comment_id']) ? (int)$data['parent_comment_id'] : null;

    if ($videoId <= 0 || empty($content)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Неверные данные']);
        exit;
    }
    
    // Проверяем, существует ли родительский комментарий, если указан
    if ($parentCommentId !== null) {
        $parentExists = $commentManager->getCommentLikesCount($parentCommentId) !== false;
        if (!$parentExists) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Родительский комментарий не найден']);
            exit;
        }
    }

    $comment = $commentManager->addComment($videoId, $userId, $content, $parentCommentId);
    
    if ($comment) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'comment' => $comment
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Не удалось добавить комментарий']);
    }
    exit;
}

//удаление комментария
if ($method === 'DELETE') {
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Необходима авторизация']);
        exit;
    }

    if (!isset($_GET['comment_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Не указан ID комментария']);
        exit;
    }
    
    $commentId = (int)$_GET['comment_id'];
    $userId = $_SESSION['user_id'];
    
    // Проверяем, является ли пользователь автором комментария
    if (!$commentManager->isCommentAuthor($commentId, $userId)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Вы не можете удалить чужой комментарий']);
        exit;
    }
    
    // Проверяем, есть ли ответы на комментарий
    if ($commentManager->hasReplies($commentId)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Невозможно удалить комментарий, на который есть ответы']);
        exit;
    }
    
    // Удаляем комментарий
    if ($commentManager->deleteComment($commentId)) {
        echo json_encode(['success' => true, 'message' => 'Комментарий успешно удален']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Ошибка при удалении комментария']);
    }
    exit;
}

// Если метод запроса не поддерживается
http_response_code(405);
echo json_encode(['error' => 'Метод не поддерживается']);
exit;
