<?php
class VideoManager
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getRandomVideos($limit = 6)
    {
        $query = "SELECT v.*, u.display_name, u.avatar_url 
                  FROM videos v
                  JOIN users u ON v.user_id = u.user_id
                  WHERE v.status = 'public'
                  ORDER BY RAND() 
                  LIMIT :limit";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $videos = $stmt->fetchAll();

        $this->processThumbnailUrls($videos);

        return $videos;
    }

    public function getRecommendedVideos($limit = 10)
    {
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

        $query = "SELECT v.*, u.display_name, u.avatar_url 
                  FROM videos v
                  JOIN users u ON v.user_id = u.user_id
                  LEFT JOIN view_history vh ON v.video_id = vh.video_id AND vh.user_id = :userId
                  WHERE v.status = 'public' 
                  AND v.user_id IN (
                      SELECT channel_id 
                      FROM subscriptions 
                      WHERE subscriber_id = :userId
                  )
                  OR v.video_id IN (
                      SELECT vt1.video_id
                      FROM video_tags vt1
                      JOIN video_tags vt2 ON vt1.tag_id = vt2.tag_id
                      JOIN view_history vh ON vt2.video_id = vh.video_id
                      WHERE vh.user_id = :userId
                      AND vh.is_like = TRUE
                  )
                  AND vh.video_id IS NULL
                  ORDER BY RAND()
                  LIMIT :limit";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $videos = $stmt->fetchAll();
        if (count($videos) < $limit) {
            $remainingLimit = $limit - count($videos);
            $existingIds = array_column($videos, 'video_id');

            if (!empty($existingIds)) {
                $placeholders = array_map(function ($i) {
                    return ':id' . $i;
                }, array_keys($existingIds));
                $placeholdersStr = implode(',', $placeholders);

                $additionalQuery = "SELECT v.*, u.display_name, u.avatar_url 
                                  FROM videos v
                                  JOIN users u ON v.user_id = u.user_id
                                  WHERE v.status = 'public'
                                  AND v.video_id NOT IN ($placeholdersStr)
                                  ORDER BY v.views_count DESC, RAND() 
                                  LIMIT :limit";

                $stmt = $this->db->prepare($additionalQuery);

                foreach ($existingIds as $i => $id) {
                    $stmt->bindValue(':id' . $i, $id, PDO::PARAM_INT);
                }
            } else {
                $additionalQuery = "SELECT v.*, u.display_name, u.avatar_url 
                                  FROM videos v
                                  JOIN users u ON v.user_id = u.user_id
                                  WHERE v.status = 'public'
                                  ORDER BY v.views_count DESC, RAND() 
                                  LIMIT :limit";

                $stmt = $this->db->prepare($additionalQuery);
            }

            $stmt->bindParam(':limit', $remainingLimit, PDO::PARAM_INT);
            $stmt->execute();

            $additionalVideos = $stmt->fetchAll();
            $videos = array_merge($videos, $additionalVideos);
        }

        $this->processThumbnailUrls($videos);

        return $videos;
    }

    public function getVideoById($videoId, $userId = null)
    {
        $query = "SELECT v.*, u.username, u.display_name, u.avatar_url, u.subscribers_count,
                        (SELECT COUNT(*) FROM comments WHERE video_id = v.video_id) as comments_count,
                        CASE WHEN :userId IS NOT NULL THEN 
                            (SELECT is_like FROM view_history 
                             WHERE video_id = v.video_id AND user_id = :userId)
                        ELSE NULL END as user_reaction,
                        (SELECT COUNT(*) FROM view_history WHERE video_id = v.video_id AND is_like = TRUE) as likes_count,
                        (SELECT COUNT(*) FROM view_history WHERE video_id = v.video_id AND is_like = FALSE) as dislikes_count
                  FROM videos v
                  JOIN users u ON v.user_id = u.user_id
                  WHERE v.video_id = :videoId";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':videoId', $videoId, PDO::PARAM_INT);
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $video = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$video) {
            return false;
        }

        $video['likes_count'] = (int) $video['likes_count'];
        $video['dislikes_count'] = (int) $video['dislikes_count'];

        if ($video['user_reaction'] !== null) {
            $video['user_reaction'] = $video['user_reaction'] == 1;
        }

        if ($video['status'] !== 'public') {
            if (!$userId || ($userId !== $video['user_id'])) {
                return false;
            }
        }

        $this->processVideoUrls($video);

        return $video;
    }

    public function incrementViewCount($videoId)
    {
        $query = "UPDATE videos SET views_count = views_count + 1 WHERE video_id = :videoId";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':videoId', $videoId, PDO::PARAM_INT);
        $stmt->execute();
    }

    private function processThumbnailUrls(&$videos, $width = 0, $height = 0)
    {
        foreach ($videos as &$video) {
            $apiUrl = '/api/get_thumbnail.php?video_id=' . $video['video_id'];

            if ($width > 0 && $height > 0) {
                $apiUrl .= '&width=' . $width . '&height=' . $height;
            }

            $video['thumbnail_url'] = $apiUrl;
        }
    }

    private function processVideoUrls(&$video)
    {
        $video['video_url'] = '/api/get_video.php?video_id=' . $video['video_id'];
        $video['thumbnail_url'] = '/api/get_thumbnail.php?video_id=' . $video['video_id'];
    }

    public static function formatDuration($seconds)
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $secs);
        } else {
            return sprintf('%d:%02d', $minutes, $secs);
        }
    }

    public static function formatUploadDate($date)
    {
        $uploadTime = strtotime($date);
        $currentTime = time();
        $diff = $currentTime - $uploadTime;

        // Меньше 24 часов
        if ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' ' . self::pluralize($hours, ['час', 'часа', 'часов']) . ' назад';
        }
        // Меньше 30 дней
        else if ($diff < 2592000) {
            $days = floor($diff / 86400);
            return $days . ' ' . self::pluralize($days, ['день', 'дня', 'дней']) . ' назад';
        }
        // Меньше 12 месяцев
        else if ($diff < 31536000) {
            $months = floor($diff / 2592000);
            return $months . ' ' . self::pluralize($months, ['месяц', 'месяца', 'месяцев']) . ' назад';
        }
        // Больше года
        else {
            $years = floor($diff / 31536000);
            return $years . ' ' . self::pluralize($years, ['год', 'года', 'лет']) . ' назад';
        }
    }

    private static function pluralize($count, $forms)
    {
        $cases = [2, 0, 1, 1, 1, 2];
        $index = ($count % 100 > 4 && $count % 100 < 20) ? 2 : $cases[min($count % 10, 5)];
        return $forms[$index];
    }

    public function searchVideos($query, $limit = 20, $sort = 'relevance', $filters = [])
    {
        $searchTerm = '%' . trim($query) . '%';
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

        $sql = "SELECT 
                v.video_id, 
                v.title, 
                v.description, 
                v.duration, 
                v.views_count, 
                v.upload_date, 
                v.thumbnail_url, 
                v.user_id, 
                u.username,
                u.display_name
                FROM videos v
                JOIN users u ON v.user_id = u.user_id ";

        if (isset($filters['subscriptions']) && $userId) {
            $sql .= "LEFT JOIN subscriptions s ON v.user_id = s.channel_id AND s.subscriber_id = :userId ";
        }

        if (isset($filters['viewed']) || isset($filters['unviewed']) || isset($filters['liked']) || isset($filters['disliked'])) {
            $sql .= "LEFT JOIN view_history vh ON v.video_id = vh.video_id AND vh.user_id = :userId ";
        }

        $sql .= "WHERE v.status = 'public' AND 
                (v.title LIKE :query OR 
                 v.description LIKE :query OR 
                 JSON_SEARCH(v.tags_cache, 'one', :exactQuery) IS NOT NULL) ";

        if (isset($filters['subscriptions']) && $userId) {
            $sql .= "AND s.channel_id IS NOT NULL ";
        }

        if (isset($filters['viewed']) && $userId) {
            $sql .= "AND vh.video_id IS NOT NULL ";
        }

        if (isset($filters['unviewed']) && $userId) {
            $sql .= "AND vh.video_id IS NULL ";
        }

        if (isset($filters['liked']) && $userId) {
            $sql .= "AND vh.is_like = TRUE ";
        }

        if (isset($filters['disliked']) && $userId) {
            $sql .= "AND vh.is_like = FALSE ";
        }

        $sql .= "GROUP BY v.video_id";

        switch ($sort) {
            case 'date_desc':
                $sql .= " ORDER BY v.upload_date DESC";
                break;
            case 'date_asc':
                $sql .= " ORDER BY v.upload_date ASC";
                break;
            case 'views_desc':
                $sql .= " ORDER BY v.views_count DESC";
                break;
            case 'views_asc':
                $sql .= " ORDER BY v.views_count ASC";
                break;
            case 'relevance':
            default:
                $sql .= " ORDER BY 
                    CASE 
                        WHEN v.title LIKE :exactQuery THEN 1
                        WHEN v.title LIKE :startQuery THEN 2
                        ELSE 3
                    END,
                    v.views_count DESC";
                break;
        }

        $sql .= " LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':query', $searchTerm, PDO::PARAM_STR);
        $exactQuery = trim($query);
        $stmt->bindParam(':exactQuery', $exactQuery, PDO::PARAM_STR);
        $startQuery = trim($query) . '%';
        $stmt->bindParam(':startQuery', $startQuery, PDO::PARAM_STR);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);

        if (
            (isset($filters['subscriptions']) || isset($filters['viewed']) ||
                isset($filters['unviewed']) || isset($filters['liked']) || isset($filters['disliked'])) && $userId
        ) {
            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        }

        $stmt->execute();
        $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($videos as &$video) {
            $video['duration_formatted'] = $this->formatDuration($video['duration']);
            $video['upload_date_formatted'] = $this->formatUploadDate($video['upload_date']);
            $viewsCount = (int) $video['views_count'];
            $video['views_formatted'] = number_format($viewsCount, 0, '', ' ') . ' ' .
                $this->pluralize($viewsCount, ['просмотр', 'просмотра', 'просмотров']);
        }

        $this->processThumbnailUrls($videos);

        return $videos;
    }

    public function getUserVideos($userId, $currentUserId = null, $orderBy = 'date_desc', $orderDir = 'DESC')
    {
        $sqlOrderBy = 'upload_date';
        $sqlOrderDir = 'DESC';

        if (strpos($orderBy, '_') !== false) {
            list($field, $direction) = explode('_', $orderBy);

            switch ($field) {
                case 'date':
                    $sqlOrderBy = 'upload_date';
                    break;
                case 'views':
                    $sqlOrderBy = 'views_count';
                    break;
                case 'title':
                    $sqlOrderBy = 'title';
                    break;
                default:
                    $sqlOrderBy = 'upload_date';
            }

            if ($direction === 'asc') {
                $sqlOrderDir = 'ASC';
            } else {
                $sqlOrderDir = 'DESC';
            }
        } else {
            $allowedOrderBy = ['upload_date', 'views_count', 'title'];
            $allowedOrderDir = ['DESC', 'ASC'];

            if (in_array($orderBy, $allowedOrderBy)) {
                $sqlOrderBy = $orderBy;
            }

            if (in_array($orderDir, $allowedOrderDir)) {
                $sqlOrderDir = $orderDir;
            }
        }

        $statusCondition = "v.status = 'public'";
        if ($currentUserId && $currentUserId == $userId) {
            $statusCondition = "1=1"; // Показываем все видео
        }

        $query = "SELECT v.*, u.display_name, u.avatar_url 
                  FROM videos v 
                  JOIN users u ON v.user_id = u.user_id 
                  WHERE v.user_id = :userId 
                  AND {$statusCondition}
                  ORDER BY v.{$sqlOrderBy} {$sqlOrderDir}";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->processThumbnailUrls($videos);

        return $videos;
    }

    public function getVideosByUserId($userId, $currentUserId = null, $limit = 12)
    {
        $statusCondition = "v.status = 'public'";
        if ($currentUserId && $currentUserId == $userId) {
            $statusCondition = "1=1";
        }

        $query = "SELECT v.*, u.display_name, u.avatar_url 
                  FROM videos v 
                  JOIN users u ON v.user_id = u.user_id 
                  WHERE v.user_id = :userId 
                  AND {$statusCondition}
                  ORDER BY v.upload_date DESC
                  LIMIT :limit";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->processThumbnailUrls($videos);

        foreach ($videos as &$video) {
            $video['duration_formatted'] = $this->formatDuration($video['duration']);
            $video['upload_date_formatted'] = $this->formatUploadDate($video['upload_date']);
            $viewsCount = (int) $video['views_count'];
            $video['views_formatted'] = number_format($viewsCount, 0, '', ' ') . ' ' .
                $this->pluralize($viewsCount, ['просмотр', 'просмотра', 'просмотров']);
        }

        return $videos;
    }

    public function getViewHistory($userId)
    {
        $query = "SELECT 
            vh.*, 
            v.title,
            v.description,
            v.duration,
            v.views_count,
            v.thumbnail_url
        FROM view_history vh
        JOIN videos v ON vh.video_id = v.video_id
        WHERE vh.user_id = :userId
        ORDER BY vh.viewed_at DESC";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetchAll();

        $history = [];
        foreach ($result as $row) {
            $history[] = [
                'video_id' => $row['video_id'],
                'title' => $row['title'],
                'description' => $row['description'],
                'duration' => $row['duration'],
                'views_count' => $row['views_count'],
                'thumbnail_url' => $row['thumbnail_url'],
                'viewed_at' => $row['viewed_at'],
                'is_like' => $row['is_like'] !== null ? (int) $row['is_like'] : null,
                'reaction_at' => $row['reaction_at']
            ];
        }

        return $history;
    }
}
