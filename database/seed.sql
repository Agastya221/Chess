SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

INSERT INTO seasons (title, starts_on, is_active)
SELECT 'Первый сезон', CURDATE(), 1
WHERE NOT EXISTS (SELECT 1 FROM seasons);

INSERT INTO reward_types (title, description, icon, color, points, sort_order)
SELECT 'Внимание на уроке', 'Слушал и работал внимательно', '♘', '#2f7d5a', 5, 10
WHERE NOT EXISTS (SELECT 1 FROM reward_types WHERE title = 'Внимание на уроке');

INSERT INTO reward_types (title, description, icon, color, points, sort_order)
SELECT 'Хорошее поведение', 'Вел себя спокойно и уважительно', '♙', '#4169a8', 5, 20
WHERE NOT EXISTS (SELECT 1 FROM reward_types WHERE title = 'Хорошее поведение');

INSERT INTO reward_types (title, description, icon, color, points, sort_order)
SELECT 'Решение задач', 'Нашел сильные ходы в задачах', '♗', '#c6802b', 10, 30
WHERE NOT EXISTS (SELECT 1 FROM reward_types WHERE title = 'Решение задач');

INSERT INTO reward_types (title, description, icon, color, points, sort_order)
SELECT 'Домашнее задание', 'Подготовился к занятию', '♖', '#8a5bb8', 8, 40
WHERE NOT EXISTS (SELECT 1 FROM reward_types WHERE title = 'Домашнее задание');

INSERT INTO reward_types (title, description, icon, color, points, sort_order)
SELECT 'Красивая партия', 'Сыграл аккуратную партию', '♕', '#c85050', 12, 50
WHERE NOT EXISTS (SELECT 1 FROM reward_types WHERE title = 'Красивая партия');

INSERT INTO reward_types (title, description, icon, color, points, sort_order)
SELECT 'Помощь другим', 'Поддержал другого ученика', '♔', '#3f7380', 6, 60
WHERE NOT EXISTS (SELECT 1 FROM reward_types WHERE title = 'Помощь другим');

INSERT INTO settings (`key`, `value`) VALUES
('school_name', 'Юные шахматисты'),
('public_title', 'Шахматная доска почёта'),
('public_subtitle', 'Лучшие результаты сезона'),
('leaderboard_limit', '5')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);
