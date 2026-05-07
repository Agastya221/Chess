SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

INSERT INTO students (private_name, public_name, avatar, notes, is_active)
SELECT 'Алиса Петрова', 'Белая Ладья', '♖', 'Демо-ученик', 1
WHERE NOT EXISTS (SELECT 1 FROM students WHERE public_name = 'Белая Ладья');

INSERT INTO students (private_name, public_name, avatar, notes, is_active)
SELECT 'Максим Иванов', 'Смелый Конь', '♘', 'Демо-ученик', 1
WHERE NOT EXISTS (SELECT 1 FROM students WHERE public_name = 'Смелый Конь');

INSERT INTO students (private_name, public_name, avatar, notes, is_active)
SELECT 'София Орлова', 'Тихий Ферзь', '♕', 'Демо-ученик', 1
WHERE NOT EXISTS (SELECT 1 FROM students WHERE public_name = 'Тихий Ферзь');

INSERT INTO students (private_name, public_name, avatar, notes, is_active)
SELECT 'Даниил Морозов', 'Острый Слон', '♗', 'Демо-ученик', 1
WHERE NOT EXISTS (SELECT 1 FROM students WHERE public_name = 'Острый Слон');

INSERT INTO students (private_name, public_name, avatar, notes, is_active)
SELECT 'Мира Соколова', 'Королевский Пешка', '♙', 'Демо-ученик', 1
WHERE NOT EXISTS (SELECT 1 FROM students WHERE public_name = 'Королевский Пешка');

INSERT INTO students (private_name, public_name, avatar, notes, is_active)
SELECT 'Егор Волков', 'Северный Король', '♔', 'Скрыт из топа, если очков меньше', 1
WHERE NOT EXISTS (SELECT 1 FROM students WHERE public_name = 'Северный Король');

INSERT INTO awards (student_id, reward_type_id, season_id, title, icon, points, note, lesson_date)
SELECT s.id, rt.id, season.id, rt.title, rt.icon, 28, 'Демо-награда', CURDATE()
FROM students s
JOIN reward_types rt ON rt.title = 'Красивая партия'
JOIN seasons season ON season.is_active = 1
WHERE s.public_name = 'Белая Ладья'
  AND NOT EXISTS (SELECT 1 FROM awards WHERE student_id = s.id);

INSERT INTO awards (student_id, reward_type_id, season_id, title, icon, points, note, lesson_date)
SELECT s.id, rt.id, season.id, rt.title, rt.icon, 24, 'Демо-награда', CURDATE()
FROM students s
JOIN reward_types rt ON rt.title = 'Решение задач'
JOIN seasons season ON season.is_active = 1
WHERE s.public_name = 'Смелый Конь'
  AND NOT EXISTS (SELECT 1 FROM awards WHERE student_id = s.id);

INSERT INTO awards (student_id, reward_type_id, season_id, title, icon, points, note, lesson_date)
SELECT s.id, rt.id, season.id, rt.title, rt.icon, 20, 'Демо-награда', CURDATE()
FROM students s
JOIN reward_types rt ON rt.title = 'Домашнее задание'
JOIN seasons season ON season.is_active = 1
WHERE s.public_name = 'Тихий Ферзь'
  AND NOT EXISTS (SELECT 1 FROM awards WHERE student_id = s.id);

INSERT INTO awards (student_id, reward_type_id, season_id, title, icon, points, note, lesson_date)
SELECT s.id, rt.id, season.id, rt.title, rt.icon, 16, 'Демо-награда', CURDATE()
FROM students s
JOIN reward_types rt ON rt.title = 'Внимание на уроке'
JOIN seasons season ON season.is_active = 1
WHERE s.public_name = 'Острый Слон'
  AND NOT EXISTS (SELECT 1 FROM awards WHERE student_id = s.id);

INSERT INTO awards (student_id, reward_type_id, season_id, title, icon, points, note, lesson_date)
SELECT s.id, rt.id, season.id, rt.title, rt.icon, 12, 'Демо-награда', CURDATE()
FROM students s
JOIN reward_types rt ON rt.title = 'Хорошее поведение'
JOIN seasons season ON season.is_active = 1
WHERE s.public_name = 'Королевский Пешка'
  AND NOT EXISTS (SELECT 1 FROM awards WHERE student_id = s.id);

INSERT INTO awards (student_id, reward_type_id, season_id, title, icon, points, note, lesson_date)
SELECT s.id, rt.id, season.id, rt.title, rt.icon, 4, 'Демо-награда', CURDATE()
FROM students s
JOIN reward_types rt ON rt.title = 'Помощь другим'
JOIN seasons season ON season.is_active = 1
WHERE s.public_name = 'Северный Король'
  AND NOT EXISTS (SELECT 1 FROM awards WHERE student_id = s.id);
