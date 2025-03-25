-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Хост: MySQL-8.2
-- Время создания: Мар 23 2025 г., 23:29
-- Версия сервера: 8.2.0
-- Версия PHP: 8.3.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `truewatch`
--
CREATE DATABASE IF NOT EXISTS `truewatch` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE `truewatch`;

-- --------------------------------------------------------

--
-- Структура таблицы `comments`
--

DROP TABLE IF EXISTS `comments`;
CREATE TABLE IF NOT EXISTS `comments` (
  `comment_id` int NOT NULL AUTO_INCREMENT,
  `video_id` int NOT NULL,
  `user_id` int NOT NULL,
  `parent_comment_id` int DEFAULT NULL,
  `content` text NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `depth` tinyint DEFAULT '0',
  `likes_count` int DEFAULT '0',
  PRIMARY KEY (`comment_id`),
  KEY `user_id` (`user_id`),
  KEY `idx_comments_video` (`video_id`),
  KEY `idx_parent_comment_id` (`parent_comment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Триггеры `comments`
--
DROP TRIGGER IF EXISTS `before_comment_delete`;
DELIMITER $$
CREATE TRIGGER `before_comment_delete` BEFORE DELETE ON `comments` FOR EACH ROW BEGIN
    -- Проверяем, есть ли ответы на комментарий
    DECLARE reply_count INT;
    
    SELECT COUNT(*) INTO reply_count 
    FROM comments 
    WHERE parent_comment_id = OLD.comment_id;
    
    -- Если есть ответы, отменяем удаление
    IF reply_count > 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Невозможно удалить комментарий, на который есть ответы';
    END IF;
END
$$
DELIMITER ;
DROP TRIGGER IF EXISTS `before_comment_update`;
DELIMITER $$
CREATE TRIGGER `before_comment_update` BEFORE UPDATE ON `comments` FOR EACH ROW BEGIN
    DECLARE reply_count INT;
    
    -- Проверяем, есть ли ответы на комментарий и изменяется ли содержимое
    IF OLD.content != NEW.content THEN
        SELECT COUNT(*) INTO reply_count 
        FROM comments 
        WHERE parent_comment_id = OLD.comment_id;
        
        -- Если есть ответы, отменяем изменение содержимого
        IF reply_count > 0 THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Невозможно редактировать комментарий, на который есть ответы';
        END IF;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Структура таблицы `comment_likes`
--

DROP TABLE IF EXISTS `comment_likes`;
CREATE TABLE IF NOT EXISTS `comment_likes` (
  `like_id` int NOT NULL AUTO_INCREMENT,
  `comment_id` int NOT NULL,
  `user_id` int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`like_id`),
  UNIQUE KEY `unique_comment_user` (`comment_id`,`user_id`),
  KEY `comment_id` (`comment_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Триггеры `comment_likes`
--
DROP TRIGGER IF EXISTS `after_comment_like_delete`;
DELIMITER $$
CREATE TRIGGER `after_comment_like_delete` AFTER DELETE ON `comment_likes` FOR EACH ROW BEGIN
    UPDATE comments 
    SET likes_count = likes_count - 1 
    WHERE comment_id = OLD.comment_id;
END
$$
DELIMITER ;
DROP TRIGGER IF EXISTS `after_comment_like_insert`;
DELIMITER $$
CREATE TRIGGER `after_comment_like_insert` AFTER INSERT ON `comment_likes` FOR EACH ROW BEGIN
    UPDATE comments 
    SET likes_count = likes_count + 1 
    WHERE comment_id = NEW.comment_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Структура таблицы `remember_tokens`
--

DROP TABLE IF EXISTS `remember_tokens`;
CREATE TABLE IF NOT EXISTS `remember_tokens` (
  `token_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`token_id`),
  UNIQUE KEY `token` (`token`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `subscriptions`
--

DROP TABLE IF EXISTS `subscriptions`;
CREATE TABLE IF NOT EXISTS `subscriptions` (
  `subscriber_id` int NOT NULL,
  `channel_id` int NOT NULL,
  `subscribed_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`subscriber_id`,`channel_id`),
  KEY `channel_id` (`channel_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Триггеры `subscriptions`
--
DROP TRIGGER IF EXISTS `update_subscribers_count`;
DELIMITER $$
CREATE TRIGGER `update_subscribers_count` AFTER INSERT ON `subscriptions` FOR EACH ROW BEGIN
    UPDATE users 
    SET subscribers_count = subscribers_count + 1 
    WHERE user_id = NEW.channel_id;
END
$$
DELIMITER ;

-- Триггер для уменьшения счетчика подписчиков при удалении подписки
DROP TRIGGER IF EXISTS `update_subscribers_count_delete`;
DELIMITER $$
CREATE TRIGGER `update_subscribers_count_delete` AFTER DELETE ON `subscriptions` FOR EACH ROW BEGIN
    UPDATE users 
    SET subscribers_count = subscribers_count - 1 
    WHERE user_id = OLD.channel_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Структура таблицы `tags`
--

DROP TABLE IF EXISTS `tags`;
CREATE TABLE IF NOT EXISTS `tags` (
  `tag_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT 'Нормализованное имя',
  `usage_count` int DEFAULT '0' COMMENT 'Частота использования',
  PRIMARY KEY (`tag_id`),
  UNIQUE KEY `name` (`name`),
  KEY `idx_tags_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL COMMENT 'Уникальный логин',
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `avatar_url` varchar(255) DEFAULT NULL,
  `banner_url` varchar(255) DEFAULT NULL COMMENT 'Шапка канала',
  `social_links` json DEFAULT NULL COMMENT '{"youtube":"url","twitter":"url"}',
  `description` text COMMENT 'Описание канала',
  `subscribers_count` int DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `last_login` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `display_name` varchar(100) DEFAULT NULL COMMENT 'Отображаемое имя пользователя',
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Структура таблицы `videos`
--

DROP TABLE IF EXISTS `videos`;
CREATE TABLE IF NOT EXISTS `videos` (
  `video_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `duration` int NOT NULL COMMENT 'Длительность в секундах',
  `upload_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `views_count` int DEFAULT '0',
  `likes_count` int DEFAULT '0',
  `dislikes_count` int DEFAULT '0',
  `file_path` varchar(255) NOT NULL,
  `thumbnail_url` varchar(255) DEFAULT NULL,
  `status` enum('public','private','unlisted') DEFAULT 'public',
  `tags_cache` json DEFAULT NULL COMMENT 'Кэш тегов для быстрого доступа',
  PRIMARY KEY (`video_id`),
  KEY `user_id` (`user_id`),
  KEY `idx_video_upload` (`upload_date`),
  KEY `idx_video_views` (`views_count`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Триггеры `videos`
--
DROP TRIGGER IF EXISTS `set_thumbnail_on_insert`;
DELIMITER $$
CREATE TRIGGER `set_thumbnail_on_insert` BEFORE INSERT ON `videos` FOR EACH ROW BEGIN
    IF NEW.file_path IS NOT NULL AND (NEW.thumbnail_url IS NULL OR NEW.thumbnail_url = '') THEN
        SET NEW.thumbnail_url = CONCAT('/server/video/thumbnails/', 
                                      SUBSTRING_INDEX(
                                          SUBSTRING_INDEX(NEW.file_path, '/', -1), 
                                          '.', 
                                          1
                                      ), 
                                      '.jpg');
    END IF;
END
$$
DELIMITER ;
DROP TRIGGER IF EXISTS `set_thumbnail_on_update`;
DELIMITER $$
CREATE TRIGGER `set_thumbnail_on_update` BEFORE UPDATE ON `videos` FOR EACH ROW BEGIN
    IF NEW.file_path != OLD.file_path OR (NEW.thumbnail_url IS NULL OR NEW.thumbnail_url = '') THEN
        SET NEW.thumbnail_url = CONCAT('/server/video/thumbnails/', 
                                      SUBSTRING_INDEX(
                                          SUBSTRING_INDEX(NEW.file_path, '/', -1), 
                                          '.', 
                                          1
                                      ), 
                                      '.jpg');
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Структура таблицы `video_tags`
--

DROP TABLE IF EXISTS `video_tags`;
CREATE TABLE IF NOT EXISTS `video_tags` (
  `video_id` int NOT NULL,
  `tag_id` int NOT NULL,
  PRIMARY KEY (`video_id`,`tag_id`),
  KEY `tag_id` (`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Триггеры `video_tags`
--
DROP TRIGGER IF EXISTS `after_video_tag_delete`;
DELIMITER $$
CREATE TRIGGER `after_video_tag_delete` AFTER DELETE ON `video_tags` FOR EACH ROW BEGIN
    UPDATE videos 
    SET tags_cache = JSON_REMOVE(
        tags_cache, 
        JSON_UNQUOTE(JSON_SEARCH(tags_cache, 'one', OLD.tag_id))
    )
    WHERE video_id = OLD.video_id;
    
    UPDATE tags SET usage_count = usage_count - 1 
    WHERE tag_id = OLD.tag_id;
END
$$
DELIMITER ;
DROP TRIGGER IF EXISTS `after_video_tag_insert`;
DELIMITER $$
CREATE TRIGGER `after_video_tag_insert` AFTER INSERT ON `video_tags` FOR EACH ROW BEGIN
    UPDATE videos 
    SET tags_cache = JSON_ARRAY_APPEND(
        COALESCE(tags_cache, '[]'), 
        '$', 
        (SELECT name FROM tags WHERE tag_id = NEW.tag_id)
    )
    WHERE video_id = NEW.video_id;
    
    UPDATE tags SET usage_count = usage_count + 1 
    WHERE tag_id = NEW.tag_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Структура таблицы `view_history`
--

DROP TABLE IF EXISTS `view_history`;
CREATE TABLE IF NOT EXISTS `view_history` (
  `view_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `video_id` int NOT NULL,
  `viewed_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `progress_seconds` int DEFAULT '0',
  `is_like` tinyint(1) DEFAULT NULL,
  `reaction_at` datetime DEFAULT NULL,
  PRIMARY KEY (`view_id`),
  UNIQUE KEY `uniq_user_video` (`user_id`,`video_id`),
  KEY `video_id` (`video_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci ROW_FORMAT=COMPRESSED;

--
-- Триггеры `view_history`
--
DROP TRIGGER IF EXISTS `insert_video_reactions`;
DELIMITER $$
CREATE TRIGGER `insert_video_reactions` AFTER INSERT ON `view_history` FOR EACH ROW BEGIN
    IF NEW.is_like IS NOT NULL THEN
        UPDATE videos 
        SET 
            likes_count = (
                SELECT COUNT(*) 
                FROM view_history 
                WHERE video_id = NEW.video_id AND is_like = TRUE
            ),
            dislikes_count = (
                SELECT COUNT(*) 
                FROM view_history 
                WHERE video_id = NEW.video_id AND is_like = FALSE
            )
        WHERE video_id = NEW.video_id;
    END IF;
END
$$
DELIMITER ;
DROP TRIGGER IF EXISTS `update_video_reactions`;
DELIMITER $$
CREATE TRIGGER `update_video_reactions` AFTER UPDATE ON `view_history` FOR EACH ROW BEGIN
    IF NEW.is_like IS NOT NULL OR OLD.is_like IS NOT NULL THEN
        UPDATE videos 
        SET 
            likes_count = (
                SELECT COUNT(*) 
                FROM view_history 
                WHERE video_id = NEW.video_id AND is_like = TRUE
            ),
            dislikes_count = (
                SELECT COUNT(*) 
                FROM view_history 
                WHERE video_id = NEW.video_id AND is_like = FALSE
            )
        WHERE video_id = NEW.video_id;
    END IF;
END
$$
DELIMITER ;

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `videos`
--
ALTER TABLE `videos` ADD FULLTEXT KEY `ft_search` (`title`,`description`);

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`video_id`) REFERENCES `videos` (`video_id`),
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `comments_ibfk_3` FOREIGN KEY (`parent_comment_id`) REFERENCES `comments` (`comment_id`);

--
-- Ограничения внешнего ключа таблицы `comment_likes`
--
ALTER TABLE `comment_likes`
  ADD CONSTRAINT `comment_likes_ibfk_1` FOREIGN KEY (`comment_id`) REFERENCES `comments` (`comment_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comment_likes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD CONSTRAINT `remember_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD CONSTRAINT `subscriptions_ibfk_1` FOREIGN KEY (`subscriber_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `subscriptions_ibfk_2` FOREIGN KEY (`channel_id`) REFERENCES `users` (`user_id`);

--
-- Ограничения внешнего ключа таблицы `videos`
--
ALTER TABLE `videos`
  ADD CONSTRAINT `videos_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `video_tags`
--
ALTER TABLE `video_tags`
  ADD CONSTRAINT `video_tags_ibfk_1` FOREIGN KEY (`video_id`) REFERENCES `videos` (`video_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `video_tags_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`tag_id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `view_history`
--
ALTER TABLE `view_history`
  ADD CONSTRAINT `view_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `view_history_ibfk_2` FOREIGN KEY (`video_id`) REFERENCES `videos` (`video_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
