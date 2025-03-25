<?php
class CommentManager
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getVideoComments($videoId, $limit = 20, $offset = 0, $userId = null)
    {
        $query = "
            SELECT 
                c.comment_id, 
                c.video_id, 
                c.user_id, 
                c.content, 
                c.created_at,
                c.likes_count,
                c.parent_comment_id,
                (SELECT COUNT(*) FROM comments WHERE parent_comment_id = c.comment_id) as replies_count,
                u.username,
                u.display_name,
                u.avatar_url,
                " . ($userId ? "CASE WHEN c.user_id = :user_id THEN 1 ELSE 0 END AS is_author," : "0 AS is_author,") . "
                " . ($userId ? "CASE WHEN cl.user_id IS NOT NULL THEN 1 ELSE 0 END AS is_liked" : "0 AS is_liked") . "
            FROM 
                comments c
            JOIN 
                users u ON c.user_id = u.user_id
            " . ($userId ? "LEFT JOIN comment_likes cl ON c.comment_id = cl.comment_id AND cl.user_id = :user_id" : "") . "
            WHERE 
                c.video_id = :video_id
                AND c.parent_comment_id IS NULL
            ORDER BY 
                c.created_at DESC
            LIMIT 
                :limit OFFSET :offset
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':video_id', $videoId, PDO::PARAM_INT);

        if ($userId) {
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        }

        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($comments as &$comment) {
            $comment['total_replies_count'] = $this->getAllCommentRepliesCount($comment['comment_id']);
        }

        return $comments;
    }

    public function addComment($videoId, $userId, $content, $parentCommentId = null)
    {
        if (empty(trim($content))) {
            return false;
        }

        $query = "INSERT INTO comments (video_id, user_id, parent_comment_id, content, created_at)
                  VALUES (:video_id, :user_id, :parent_comment_id, :content, NOW())";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':video_id', $videoId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':parent_comment_id', $parentCommentId, $parentCommentId ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindParam(':content', $content, PDO::PARAM_STR);

        if ($stmt->execute()) {
            $commentId = $this->db->lastInsertId();

            $query = "SELECT c.*, u.username, u.display_name, u.avatar_url
                      FROM comments c
                      JOIN users u ON c.user_id = u.user_id
                      WHERE c.comment_id = :comment_id";

            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':comment_id', $commentId, PDO::PARAM_INT);
            $stmt->execute();

            $comment = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($comment) {
                $comment['display_name'] = $comment['display_name'] ?? $comment['username'];
                $comment['created_at_formatted'] = $this->formatCommentDate($comment['created_at']);
                return $comment;
            }
        }

        return false;
    }

    public function isCommentAuthor($commentId, $userId)
    {
        $query = "SELECT user_id FROM comments WHERE comment_id = :comment_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':comment_id', $commentId, PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result && $result['user_id'] == $userId;
    }

    public function updateComment($commentId, $content)
    {
        $query = "UPDATE comments SET content = :content WHERE comment_id = :comment_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':content', $content, PDO::PARAM_STR);
        $stmt->bindParam(':comment_id', $commentId, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function deleteComment($commentId)
    {
        $query = "DELETE FROM comments WHERE comment_id = :comment_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':comment_id', $commentId, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function hasUserLikedComment($commentId, $userId)
    {
        $query = "SELECT 1 FROM comment_likes WHERE comment_id = :comment_id AND user_id = :user_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':comment_id', $commentId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return (bool) $stmt->fetchColumn();
    }

    public function likeComment($commentId, $userId)
    {
        if ($this->hasUserLikedComment($commentId, $userId)) {
            return true;
        }

        $query = "INSERT INTO comment_likes (comment_id, user_id) VALUES (:comment_id, :user_id)";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':comment_id', $commentId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function unlikeComment($commentId, $userId)
    {
        $query = "DELETE FROM comment_likes WHERE comment_id = :comment_id AND user_id = :user_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':comment_id', $commentId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function getCommentLikesCount($commentId)
    {
        $query = "SELECT COUNT(*) FROM comment_likes WHERE comment_id = :comment_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':comment_id', $commentId, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    public function getCommentsCount($videoId)
    {
        $query = "SELECT COUNT(*) FROM comments WHERE video_id = :video_id";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':video_id', $videoId, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    public function getCommentReplies($parentCommentId, $limit = 5, $offset = 0, $userId = null)
    {
        $query = "
            SELECT 
                c.comment_id, 
                c.video_id, 
                c.user_id, 
                c.content, 
                c.created_at,
                c.likes_count,
                c.parent_comment_id,
                (SELECT COUNT(*) FROM comments WHERE parent_comment_id = c.comment_id) as replies_count,
                u.username,
                u.display_name,
                u.avatar_url,
                (SELECT username FROM users WHERE user_id = (SELECT user_id FROM comments WHERE comment_id = c.parent_comment_id)) as parent_username,
                (SELECT display_name FROM users WHERE user_id = (SELECT user_id FROM comments WHERE comment_id = c.parent_comment_id)) as parent_display_name,
                " . ($userId ? "CASE WHEN c.user_id = :user_id THEN 1 ELSE 0 END AS is_author," : "0 AS is_author,") . "
                " . ($userId ? "CASE WHEN cl.user_id IS NOT NULL THEN 1 ELSE 0 END AS is_liked" : "0 AS is_liked") . "
            FROM 
                comments c
            JOIN 
                users u ON c.user_id = u.user_id
            " . ($userId ? "LEFT JOIN comment_likes cl ON c.comment_id = cl.comment_id AND cl.user_id = :user_id" : "") . "
            WHERE 
                c.parent_comment_id = :parent_comment_id
            ORDER BY 
                c.created_at ASC
            LIMIT 
                :limit OFFSET :offset
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':parent_comment_id', $parentCommentId, PDO::PARAM_INT);

        if ($userId) {
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        }

        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCommentRepliesCount($parentCommentId)
    {
        $query = "SELECT COUNT(*) FROM comments WHERE parent_comment_id = :parent_comment_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':parent_comment_id', $parentCommentId, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    public function hasReplies($commentId)
    {
        $query = "SELECT COUNT(*) FROM comments WHERE parent_comment_id = :comment_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':comment_id', $commentId, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn() > 0;
    }

    public function getAllCommentReplies($parentCommentId, $limit = 10, $offset = 0, $userId = null)
    {
        $directReplies = $this->getCommentReplies($parentCommentId, $limit, $offset, $userId);
        if (empty($directReplies)) {
            return [];
        }

        $replyIds = array_column($directReplies, 'comment_id');

        // Получаем вложенные ответы для каждого ответа
        $query = "
            WITH RECURSIVE reply_tree AS (
                SELECT 
                    c.comment_id, 
                    c.video_id, 
                    c.user_id, 
                    c.content, 
                    c.created_at,
                    c.likes_count,
                    c.parent_comment_id,
                    (SELECT COUNT(*) FROM comments WHERE parent_comment_id = c.comment_id) as replies_count,
                    u.username,
                    u.display_name,
                    u.avatar_url,
                    (SELECT username FROM users WHERE user_id = (SELECT user_id FROM comments WHERE comment_id = c.parent_comment_id)) as parent_username,
                    (SELECT display_name FROM users WHERE user_id = (SELECT user_id FROM comments WHERE comment_id = c.parent_comment_id)) as parent_display_name,
                    " . ($userId ? "CASE WHEN c.user_id = :user_id THEN 1 ELSE 0 END AS is_author," : "0 AS is_author,") . "
                    " . ($userId ? "CASE WHEN cl.user_id IS NOT NULL THEN 1 ELSE 0 END AS is_liked" : "0 AS is_liked") . "
                FROM 
                    comments c
                JOIN 
                    users u ON c.user_id = u.user_id
                " . ($userId ? "LEFT JOIN comment_likes cl ON c.comment_id = cl.comment_id AND cl.user_id = :user_id" : "") . "
                WHERE 
                    c.parent_comment_id IN (" . implode(',', $replyIds) . ")
                
                UNION ALL
                
                SELECT 
                    c.comment_id, 
                    c.video_id, 
                    c.user_id, 
                    c.content, 
                    c.created_at,
                    c.likes_count,
                    c.parent_comment_id,
                    (SELECT COUNT(*) FROM comments WHERE parent_comment_id = c.comment_id) as replies_count,
                    u.username,
                    u.display_name,
                    u.avatar_url,
                    (SELECT username FROM users WHERE user_id = (SELECT user_id FROM comments WHERE comment_id = c.parent_comment_id)) as parent_username,
                    (SELECT display_name FROM users WHERE user_id = (SELECT user_id FROM comments WHERE comment_id = c.parent_comment_id)) as parent_display_name,
                    " . ($userId ? "CASE WHEN c.user_id = :user_id THEN 1 ELSE 0 END AS is_author," : "0 AS is_author,") . "
                    " . ($userId ? "CASE WHEN cl2.user_id IS NOT NULL THEN 1 ELSE 0 END AS is_liked" : "0 AS is_liked") . "
                FROM 
                    comments c
                JOIN 
                    users u ON c.user_id = u.user_id
                JOIN 
                    reply_tree rt ON c.parent_comment_id = rt.comment_id
                " . ($userId ? "LEFT JOIN comment_likes cl2 ON c.comment_id = cl2.comment_id AND cl2.user_id = :user_id" : "") . "
            )
            SELECT * FROM reply_tree
            ORDER BY created_at ASC
        ";

        try {
            $stmt = $this->db->prepare($query);

            if ($userId) {
                $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            }

            $stmt->execute();
            $nestedReplies = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return array_merge($directReplies, $nestedReplies);
        } catch (PDOException $e) {
            $nestedReplies = [];

            foreach ($replyIds as $replyId) {
                $nestedReplies = array_merge($nestedReplies, $this->getCommentReplies($replyId, 100, 0, $userId));
            }

            return array_merge($directReplies, $nestedReplies);
        }
    }

    public function getAllCommentRepliesCount($parentCommentId)
    {
        try {
            // Пробуем использовать рекурсивный запрос для подсчета всех вложенных ответов
            $query = "
                WITH RECURSIVE reply_tree AS (
                    SELECT 
                        comment_id
                    FROM 
                        comments
                    WHERE 
                        parent_comment_id = :parent_comment_id
                    
                    UNION ALL
                    
                    SELECT 
                        c.comment_id
                    FROM 
                        comments c
                    JOIN 
                        reply_tree rt ON c.parent_comment_id = rt.comment_id
                )
                SELECT COUNT(*) FROM reply_tree
            ";

            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':parent_comment_id', $parentCommentId, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            // Если рекурсивные запросы не поддерживаются, используем альтернативный подход
            $directRepliesCount = $this->getCommentRepliesCount($parentCommentId);
            if ($directRepliesCount === 0) {
                return 0;
            }

            $query = "SELECT comment_id FROM comments WHERE parent_comment_id = :parent_comment_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':parent_comment_id', $parentCommentId, PDO::PARAM_INT);
            $stmt->execute();
            $replyIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (empty($replyIds)) {
                return 0;
            }

            $nestedRepliesCount = 0;
            foreach ($replyIds as $replyId) {
                $nestedRepliesCount += $this->getAllCommentRepliesCount($replyId);
            }

            return $directRepliesCount + $nestedRepliesCount;
        }
    }

    private function formatCommentDate($date)
    {
        $commentDate = new DateTime($date);
        $now = new DateTime();
        $interval = $now->diff($commentDate);

        if ($interval->y > 0) {
            return $interval->y . ' ' . $this->pluralizeRussian($interval->y, 'год', 'года', 'лет') . ' назад';
        } elseif ($interval->m > 0) {
            return $interval->m . ' ' . $this->pluralizeRussian($interval->m, 'месяц', 'месяца', 'месяцев') . ' назад';
        } elseif ($interval->d > 0) {
            return $interval->d . ' ' . $this->pluralizeRussian($interval->d, 'день', 'дня', 'дней') . ' назад';
        } elseif ($interval->h > 0) {
            return $interval->h . ' ' . $this->pluralizeRussian($interval->h, 'час', 'часа', 'часов') . ' назад';
        } elseif ($interval->i > 0) {
            return $interval->i . ' ' . $this->pluralizeRussian($interval->i, 'минуту', 'минуты', 'минут') . ' назад';
        } else {
            return 'только что';
        }
    }

    public function pluralizeRussian($number, $one, $two, $many)
    {
        $number = abs($number) % 100;
        $mod10 = $number % 10;

        if ($number > 10 && $number < 20) {
            return $many;
        }

        if ($mod10 > 1 && $mod10 < 5) {
            return $two;
        }

        if ($mod10 == 1) {
            return $one;
        }

        return $many;
    }
}
