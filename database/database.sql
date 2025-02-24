CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL COMMENT 'Уникальный логин',
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    avatar_url VARCHAR(255),
    banner_url VARCHAR(255) COMMENT 'Шапка канала',
    social_links JSON COMMENT '{"youtube":"url","twitter":"url"}',
    description TEXT COMMENT 'Описание канала',
    subscribers_count INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME,
    is_active BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC;

CREATE TABLE videos (
    video_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    duration INT NOT NULL COMMENT 'Длительность в секундах',
    upload_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    views_count INT DEFAULT 0,
    likes_count INT DEFAULT 0,
    dislikes_count INT DEFAULT 0,
    file_path VARCHAR(255) NOT NULL,
    thumbnail_url VARCHAR(255),
    status ENUM('public', 'private', 'unlisted') DEFAULT 'public',
    tags_cache JSON COMMENT 'Кэш тегов для быстрого доступа',
    FULLTEXT INDEX ft_search (title, description),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE tags (
    tag_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL COMMENT 'Нормализованное имя',
    usage_count INT DEFAULT 0 COMMENT 'Частота использования'
) ENGINE=InnoDB;

CREATE TABLE video_tags (
    video_id INT NOT NULL,
    tag_id INT NOT NULL,
    PRIMARY KEY (video_id, tag_id),
    FOREIGN KEY (video_id) REFERENCES videos(video_id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(tag_id) ON DELETE CASCADE
) ENGINE=InnoDB;

DELIMITER //

CREATE TRIGGER after_video_tag_insert 
AFTER INSERT ON video_tags
FOR EACH ROW
BEGIN
    UPDATE videos 
    SET tags_cache = JSON_ARRAY_APPEND(
        COALESCE(tags_cache, '[]'), 
        '$', 
        (SELECT name FROM tags WHERE tag_id = NEW.tag_id)
    )
    WHERE video_id = NEW.video_id;
    
    UPDATE tags SET usage_count = usage_count + 1 
    WHERE tag_id = NEW.tag_id;
END//

CREATE TRIGGER after_video_tag_delete 
AFTER DELETE ON video_tags
FOR EACH ROW
BEGIN
    UPDATE videos 
    SET tags_cache = JSON_REMOVE(
        tags_cache, 
        JSON_UNQUOTE(JSON_SEARCH(tags_cache, 'one', OLD.tag_id))
    )
    WHERE video_id = OLD.video_id;
    
    UPDATE tags SET usage_count = usage_count - 1 
    WHERE tag_id = OLD.tag_id;
END//

DELIMITER ;

CREATE TABLE view_history (
    view_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    video_id INT NOT NULL,
    viewed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    progress_seconds INT DEFAULT 0,
    is_like BOOLEAN DEFAULT NULL,
    reaction_at DATETIME,
    UNIQUE KEY uniq_user_video (user_id, video_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (video_id) REFERENCES videos(video_id)
) ENGINE=InnoDB ROW_FORMAT=COMPRESSED;

CREATE TABLE comments (
    comment_id INT AUTO_INCREMENT PRIMARY KEY,
    video_id INT NOT NULL,
    user_id INT NOT NULL,
    parent_comment_id INT DEFAULT NULL,
    content TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    depth TINYINT DEFAULT 0,
    likes_count INT DEFAULT 0,
    FOREIGN KEY (video_id) REFERENCES videos(video_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (parent_comment_id) REFERENCES comments(comment_id)
) ENGINE=InnoDB;

CREATE TABLE subscriptions (
    subscriber_id INT NOT NULL,
    channel_id INT NOT NULL,
    subscribed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (subscriber_id, channel_id),
    FOREIGN KEY (subscriber_id) REFERENCES users(user_id),
    FOREIGN KEY (channel_id) REFERENCES users(user_id)
) ENGINE=InnoDB;

DELIMITER //

CREATE TRIGGER update_video_reactions 
AFTER UPDATE ON view_history
FOR EACH ROW
BEGIN
    IF NEW.is_like <> OLD.is_like THEN
        UPDATE videos 
        SET 
            likes_count = likes_count + 
                CASE 
                    WHEN NEW.is_like = TRUE THEN 1 
                    WHEN OLD.is_like = TRUE THEN -1 
                    ELSE 0 
                END,
            dislikes_count = dislikes_count + 
                CASE 
                    WHEN NEW.is_like = FALSE THEN 1 
                    WHEN OLD.is_like = FALSE THEN -1 
                    ELSE 0 
                END
        WHERE video_id = NEW.video_id;
    END IF;
END//

CREATE TRIGGER update_subscribers_count
AFTER INSERT ON subscriptions
FOR EACH ROW
BEGIN
    UPDATE users 
    SET subscribers_count = subscribers_count + 1 
    WHERE user_id = NEW.channel_id;
END//

DELIMITER ;

CREATE INDEX idx_video_upload ON videos(upload_date);
CREATE INDEX idx_video_views ON videos(views_count);
CREATE INDEX idx_tags_name ON tags(name);
CREATE INDEX idx_comments_video ON comments(video_id);