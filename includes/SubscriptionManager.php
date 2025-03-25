<?php
class SubscriptionManager
{
    private $db;
    private $userManager;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->userManager = new UserManager();
    }

    public function isSubscribed($subscriberId, $channelId)
    {
        $query = "SELECT 1 FROM subscriptions 
                  WHERE subscriber_id = :subscriberId AND channel_id = :channelId";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':subscriberId', $subscriberId, PDO::PARAM_INT);
        $stmt->bindParam(':channelId', $channelId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchColumn() ? true : false;
    }

    public function subscribe($subscriberId, $channelId)
    {
        if ($this->isSubscribed($subscriberId, $channelId)) {
            return true;
        }
        $query = "INSERT INTO subscriptions (subscriber_id, channel_id, subscribed_at) 
                  VALUES (:subscriberId, :channelId, NOW())";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':subscriberId', $subscriberId, PDO::PARAM_INT);
        $stmt->bindParam(':channelId', $channelId, PDO::PARAM_INT);
        $result = $stmt->execute();

        if ($result) {
            // $this->userManager->updateSubscribersCount($channelId, 1);
            return true;
        }

        return false;
    }

    public function unsubscribe($subscriberId, $channelId)
    {
        if (!$this->isSubscribed($subscriberId, $channelId)) {
            return true;
        }

        $query = "DELETE FROM subscriptions 
                  WHERE subscriber_id = :subscriberId AND channel_id = :channelId";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':subscriberId', $subscriberId, PDO::PARAM_INT);
        $stmt->bindParam(':channelId', $channelId, PDO::PARAM_INT);
        $result = $stmt->execute();

        if ($result) {
            // $this->userManager->updateSubscribersCount($channelId, -1);
            return true;
        }

        return false;
    }

    public function getChannelSubscriptions($channelId, $limit = 10)
    {
        $query = "SELECT u.user_id, u.username, u.display_name, u.avatar_url, u.subscribers_count 
                  FROM subscriptions s 
                  JOIN users u ON s.channel_id = u.user_id 
                  WHERE s.subscriber_id = :channelId 
                  ORDER BY s.subscribed_at DESC 
                  LIMIT :limit";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':channelId', $channelId, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
