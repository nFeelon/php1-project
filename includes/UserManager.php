<?php
class UserManager
{
    private $db;
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getUserById($userId)
    {
        $query = "SELECT user_id, username, display_name, avatar_url, banner_url, description, social_links, subscribers_count, created_at 
                  FROM users 
                  WHERE user_id = :userId";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: false;
    }

    public function updateSubscribersCount($userId, $change)
    {
        $query = "UPDATE users 
                  SET subscribers_count = subscribers_count + :change 
                  WHERE user_id = :userId";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':change', $change, PDO::PARAM_INT);
        $stmt->execute();

        $query = "SELECT subscribers_count FROM users WHERE user_id = :userId";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ? (int) $result['subscribers_count'] : 0;
    }
}
