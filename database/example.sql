INSERT INTO users (username, email, password_hash, avatar_url, banner_url, social_links, description) VALUES
('user1', 'user1@example.com', 'hash1', 'https://avatar.com/user1.png', 'https://banner.com/user1.jpg', '{"youtube":"https://youtube.com/user1","twitter":"https://twitter.com/user1"}', 'Технический блогер'),
('user2', 'user2@example.com', 'hash2', 'https://avatar.com/user2.png', 'https://banner.com/user2.jpg', '{"youtube":"https://youtube.com/user2"}', 'Гейминг и обзоры'),
('user3', 'user3@example.com', 'hash3', 'https://avatar.com/user3.png', NULL, '{}', 'Музыкальный энтузиаст'),
('user4', 'user4@example.com', 'hash4', NULL, 'https://banner.com/user4.jpg', '{"twitter":"https://twitter.com/user4"}', NULL),
('user5', 'user5@example.com', 'hash5', 'https://avatar.com/user5.png', 'https://banner.com/user5.jpg', '{"youtube":"https://youtube.com/user5","twitter":"https://twitter.com/user5"}', 'Путешествия и приключения');

INSERT INTO tags (name) VALUES
('Tech'), ('Gaming'), ('Music'), ('Travel'), ('Tutorial');

INSERT INTO videos (user_id, title, description, duration, file_path, thumbnail_url, status) VALUES
(1, 'Обзор нового смартфона', 'Технические характеристики и тесты', 600, '/videos/phone_review.mp4', 'https://thumbnail.com/phone.jpg', 'public'),
(1, 'Программирование на Python', 'Базовые концепции языка', 1200, '/videos/python.mp4', 'https://thumbnail.com/python.jpg', 'public'),
(2, 'Лучшие игры 2023', 'Топ-10 игр года', 900, '/videos/games2023.mp4', 'https://thumbnail.com/games.jpg', 'public'),
(3, 'Классическая гитара', 'Играем Бетховена', 1800, '/videos/guitar.mp4', 'https://thumbnail.com/guitar.jpg', 'unlisted'),
(5, 'Тайланд: острова', 'Путеводитель по Пхукету', 1500, '/videos/thailand.mp4', 'https://thumbnail.com/thai.jpg', 'public');

INSERT INTO video_tags (video_id, tag_id) VALUES
(1, 1), (1, 5),   -- Tech, Tutorial
(2, 1), (2, 5),   -- Tech, Tutorial
(3, 2),           -- Gaming
(4, 3),           -- Music
(5, 4), (5, 5);   -- Travel, Tutorial

INSERT INTO view_history (user_id, video_id, progress_seconds, is_like) VALUES
(2, 1, 300, TRUE),
(3, 1, 500, NULL),
(4, 3, 900, FALSE),
(5, 5, 200, TRUE);

INSERT INTO comments (video_id, user_id, content, parent_comment_id, depth) VALUES
(1, 2, 'Отличный обзор!', NULL, 0),
(1, 3, 'Спасибо за детали', 1, 1),
(3, 5, 'Не согласен с подборкой', NULL, 0),
(5, 1, 'Красивые места!', NULL, 0);

INSERT INTO subscriptions (subscriber_id, channel_id) VALUES
(2, 1),  -- user2 подписан на user1
(3, 1),
(4, 2),
(5, 1),
(5, 5);  -- Самоподписка

UPDATE videos SET views_count = 100 WHERE video_id = 1;
UPDATE videos SET views_count = 50 WHERE video_id = 3;