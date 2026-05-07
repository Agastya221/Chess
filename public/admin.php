<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

function valid_avatar(string $avatar): string
{
    $allowed = ['♟', '♞', '♝', '♜', '♛', '♚', '♙', '♘', '♗', '♖', '♕', '♔'];

    return in_array($avatar, $allowed, true) ? $avatar : '♟';
}

function valid_color(string $color): string
{
    return preg_match('/^#[0-9a-fA-F]{6}$/', $color) === 1 ? $color : '#2f7d5a';
}

function require_text(string $value, string $label): string
{
    if ($value === '') {
        throw new InvalidArgumentException($label . ': заполните поле.');
    }

    return $value;
}

function clean_date(string $date, ?string $default = null): string
{
    $date = trim($date);

    if ($date === '') {
        return $default ?? date('Y-m-d');
    }

    $parsed = DateTime::createFromFormat('Y-m-d', $date);

    if (!$parsed || $parsed->format('Y-m-d') !== $date) {
        throw new InvalidArgumentException('Дата указана неверно.');
    }

    return $date;
}

function handle_admin_post(): void
{
    verify_csrf();

    $action = (string) ($_POST['action'] ?? '');
    $admin = current_admin();

    if (!$admin) {
        redirect_to('login.php');
    }

    match ($action) {
        'add_student' => add_student_action(),
        'update_student' => update_student_action(),
        'toggle_student' => toggle_student_action(),
        'regenerate_code' => regenerate_code_action(),
        'add_award' => add_award_action((int) $admin['id']),
        'update_award' => update_award_action(),
        'delete_award' => delete_award_action(),
        'add_reward_type' => add_reward_type_action(),
        'update_reward_type' => update_reward_type_action(),
        'toggle_reward_type' => toggle_reward_type_action(),
        'add_season' => add_season_action(),
        'activate_season' => activate_season_action(),
        'update_settings' => update_settings_action(),
        default => throw new InvalidArgumentException('Неизвестное действие.'),
    };
}

function add_student_action(): void
{
    $code = generate_access_code();

    $statement = db()->prepare(
        'INSERT INTO students (private_name, public_name, avatar, notes, access_code)
         VALUES (:private_name, :public_name, :avatar, :notes, :access_code)'
    );

    $statement->execute([
        'private_name' => require_text(post_string('private_name', 160), 'Имя'),
        'public_name' => require_text(post_string('public_name', 80), 'Псевдоним'),
        'avatar' => valid_avatar(post_string('avatar', 16)),
        'notes' => post_string('notes', 1000) ?: null,
        'access_code' => $code,
    ]);

    flash('success', 'Ученик добавлен. Код доступа: ' . $code);
    redirect_to('admin.php#students');
}

function regenerate_code_action(): void
{
    $id = post_int('student_id');

    if ($id < 1) {
        throw new InvalidArgumentException('Ученик не найден.');
    }

    $code = generate_access_code();

    db()->prepare('UPDATE students SET access_code = :code WHERE id = :id')
        ->execute(['code' => $code, 'id' => $id]);

    flash('success', 'Новый код доступа: ' . $code);
    redirect_to('admin.php#students');
}

function update_student_action(): void
{
    $id = post_int('student_id');

    if ($id < 1) {
        throw new InvalidArgumentException('Ученик не найден.');
    }

    $statement = db()->prepare(
        'UPDATE students
         SET private_name = :private_name, public_name = :public_name, avatar = :avatar, notes = :notes
         WHERE id = :id'
    );

    $statement->execute([
        'private_name' => require_text(post_string('private_name', 160), 'Имя'),
        'public_name' => require_text(post_string('public_name', 80), 'Псевдоним'),
        'avatar' => valid_avatar(post_string('avatar', 16)),
        'notes' => post_string('notes', 1000) ?: null,
        'id' => $id,
    ]);

    flash('success', 'Карточка ученика обновлена.');
    redirect_to('admin.php#students');
}

function toggle_student_action(): void
{
    $id = post_int('student_id');
    $isActive = post_int('is_active') === 1 ? 1 : 0;

    if ($id < 1) {
        throw new InvalidArgumentException('Ученик не найден.');
    }

    $statement = db()->prepare(
        'UPDATE students
         SET is_active = :is_active, archived_at = :archived_at
         WHERE id = :id'
    );

    $statement->execute([
        'is_active' => $isActive,
        'archived_at' => $isActive ? null : date('Y-m-d H:i:s'),
        'id' => $id,
    ]);

    flash('success', $isActive ? 'Ученик возвращён.' : 'Ученик архивирован.');
    redirect_to('admin.php#students');
}

function add_award_action(int $adminId): void
{
    $studentId = post_int('student_id');
    $rewardTypeId = post_int('reward_type_id');
    $note = post_string('note', 1000) ?: null;
    $lessonDate = clean_date((string) ($_POST['lesson_date'] ?? ''), date('Y-m-d'));
    $season = active_season();

    if (!$season) {
        throw new InvalidArgumentException('Создайте активный сезон.');
    }

    $studentStatement = db()->prepare('SELECT id FROM students WHERE id = :id AND is_active = 1 LIMIT 1');
    $studentStatement->execute(['id' => $studentId]);

    if (!$studentStatement->fetch()) {
        throw new InvalidArgumentException('Выберите активного ученика.');
    }

    $rewardStatement = db()->prepare('SELECT * FROM reward_types WHERE id = :id AND is_active = 1 LIMIT 1');
    $rewardStatement->execute(['id' => $rewardTypeId]);
    $reward = $rewardStatement->fetch();

    if (!$reward) {
        throw new InvalidArgumentException('Выберите активную награду.');
    }

    $statement = db()->prepare(
        'INSERT INTO awards
            (student_id, reward_type_id, season_id, title, icon, points, note, lesson_date, created_by)
         VALUES
            (:student_id, :reward_type_id, :season_id, :title, :icon, :points, :note, :lesson_date, :created_by)'
    );

    $statement->execute([
        'student_id' => $studentId,
        'reward_type_id' => (int) $reward['id'],
        'season_id' => (int) $season['id'],
        'title' => (string) $reward['title'],
        'icon' => (string) $reward['icon'],
        'points' => (int) $reward['points'],
        'note' => $note,
        'lesson_date' => $lessonDate,
        'created_by' => $adminId,
    ]);

    flash('success', 'Награда выдана.');
    redirect_to('admin.php#award');
}

function update_award_action(): void
{
    $id = post_int('award_id');
    $points = max(0, min(100, post_int('points')));
    $note = post_string('note', 1000) ?: null;
    $lessonDate = clean_date((string) ($_POST['lesson_date'] ?? ''), date('Y-m-d'));

    if ($id < 1) {
        throw new InvalidArgumentException('Награда не найдена.');
    }

    $statement = db()->prepare(
        'UPDATE awards SET points = :points, note = :note, lesson_date = :lesson_date WHERE id = :id'
    );
    $statement->execute([
        'points' => $points,
        'note' => $note,
        'lesson_date' => $lessonDate,
        'id' => $id,
    ]);

    flash('success', 'Награда обновлена.');
    redirect_to('admin.php#history');
}

function delete_award_action(): void
{
    $id = post_int('award_id');

    if ($id < 1) {
        throw new InvalidArgumentException('Награда не найдена.');
    }

    db()->prepare('DELETE FROM awards WHERE id = :id')->execute(['id' => $id]);
    flash('success', 'Награда отменена.');
    redirect_to('admin.php#history');
}

function add_reward_type_action(): void
{
    $statement = db()->prepare(
        'INSERT INTO reward_types (title, description, icon, color, points, sort_order)
         VALUES (:title, :description, :icon, :color, :points, :sort_order)'
    );

    $statement->execute([
        'title' => require_text(post_string('title', 120), 'Название'),
        'description' => post_string('description', 255) ?: null,
        'icon' => valid_avatar(post_string('icon', 16)),
        'color' => valid_color(post_string('color', 16)),
        'points' => max(0, min(100, post_int('points', 5))),
        'sort_order' => max(1, min(999, post_int('sort_order', 100))),
    ]);

    flash('success', 'Тип награды добавлен.');
    redirect_to('admin.php#rewards');
}

function update_reward_type_action(): void
{
    $id = post_int('reward_type_id');

    if ($id < 1) {
        throw new InvalidArgumentException('Тип награды не найден.');
    }

    $statement = db()->prepare(
        'UPDATE reward_types
         SET title = :title, description = :description, icon = :icon, color = :color,
             points = :points, sort_order = :sort_order
         WHERE id = :id'
    );

    $statement->execute([
        'title' => require_text(post_string('title', 120), 'Название'),
        'description' => post_string('description', 255) ?: null,
        'icon' => valid_avatar(post_string('icon', 16)),
        'color' => valid_color(post_string('color', 16)),
        'points' => max(0, min(100, post_int('points', 5))),
        'sort_order' => max(1, min(999, post_int('sort_order', 100))),
        'id' => $id,
    ]);

    flash('success', 'Тип награды обновлён.');
    redirect_to('admin.php#rewards');
}

function toggle_reward_type_action(): void
{
    $id = post_int('reward_type_id');
    $isActive = post_int('is_active') === 1 ? 1 : 0;

    if ($id < 1) {
        throw new InvalidArgumentException('Тип награды не найден.');
    }

    db()->prepare('UPDATE reward_types SET is_active = :is_active WHERE id = :id')
        ->execute(['is_active' => $isActive, 'id' => $id]);

    flash('success', $isActive ? 'Награда включена.' : 'Награда скрыта.');
    redirect_to('admin.php#rewards');
}

function add_season_action(): void
{
    $title = require_text(post_string('title', 120), 'Название сезона');
    $startsOn = clean_date((string) ($_POST['starts_on'] ?? ''), date('Y-m-d'));
    $endsOnRaw = trim((string) ($_POST['ends_on'] ?? ''));
    $endsOn = $endsOnRaw === '' ? null : clean_date($endsOnRaw);
    $makeActive = isset($_POST['make_active']);

    db()->beginTransaction();

    if ($makeActive) {
        db()->exec('UPDATE seasons SET is_active = 0');
    }

    $statement = db()->prepare(
        'INSERT INTO seasons (title, starts_on, ends_on, is_active)
         VALUES (:title, :starts_on, :ends_on, :is_active)'
    );
    $statement->execute([
        'title' => $title,
        'starts_on' => $startsOn,
        'ends_on' => $endsOn,
        'is_active' => $makeActive ? 1 : 0,
    ]);

    db()->commit();

    flash('success', 'Сезон добавлен.');
    redirect_to('admin.php#seasons');
}

function activate_season_action(): void
{
    $id = post_int('season_id');

    if ($id < 1) {
        throw new InvalidArgumentException('Сезон не найден.');
    }

    db()->beginTransaction();
    db()->exec('UPDATE seasons SET is_active = 0');
    db()->prepare('UPDATE seasons SET is_active = 1 WHERE id = :id')->execute(['id' => $id]);
    db()->commit();

    flash('success', 'Активный сезон изменён.');
    redirect_to('admin.php#seasons');
}

function update_settings_action(): void
{
    $settings = [
        'school_name' => require_text(post_string('school_name', 120), 'Название'),
        'public_title' => require_text(post_string('public_title', 120), 'Заголовок'),
        'public_subtitle' => post_string('public_subtitle', 180),
        'leaderboard_limit' => (string) max(1, min(10, post_int('leaderboard_limit', 5))),
    ];

    $statement = db()->prepare(
        'INSERT INTO settings (`key`, `value`) VALUES (:setting_key, :setting_value)
         ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)'
    );

    foreach ($settings as $key => $value) {
        $statement->execute([
            'setting_key' => $key,
            'setting_value' => $value,
        ]);
    }

    flash('success', 'Настройки сохранены.');
    redirect_to('admin.php#settings');
}

try {
    require_admin();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            handle_admin_post();
        } catch (Throwable $exception) {
            try {
                if (db()->inTransaction()) {
                    db()->rollBack();
                }
            } catch (Throwable) {
                // Keep the original save error visible.
            }

            $message = $exception instanceof InvalidArgumentException || (bool) config_value('app.debug', false)
                ? $exception->getMessage()
                : 'Не удалось сохранить изменения.';

            flash('error', $message);
            redirect_to('admin.php');
        }
    }

    $admin = current_admin();
    $settings = app_settings();
    $season = active_season();
    $seasonId = $season ? (int) $season['id'] : 0;

    $studentsStatement = db()->prepare(
        'SELECT s.*,
                COALESCE(totals.score, 0) AS score,
                COALESCE(totals.awards_count, 0) AS awards_count,
                totals.last_award_at
         FROM students s
         LEFT JOIN (
             SELECT student_id, SUM(points) AS score, COUNT(*) AS awards_count, MAX(created_at) AS last_award_at
             FROM awards
             WHERE season_id = :season_id
             GROUP BY student_id
         ) totals ON totals.student_id = s.id
         ORDER BY s.is_active DESC, score DESC, s.private_name ASC'
    );
    $studentsStatement->execute(['season_id' => $seasonId]);
    $students = $studentsStatement->fetchAll();
    $activeStudents = array_values(array_filter($students, static fn ($student) => (int) $student['is_active'] === 1));

    $rewardTypes = db()->query(
        'SELECT * FROM reward_types ORDER BY is_active DESC, sort_order ASC, title ASC'
    )->fetchAll();
    $activeRewardTypes = array_values(array_filter($rewardTypes, static fn ($reward) => (int) $reward['is_active'] === 1));

    $seasons = db()->query('SELECT * FROM seasons ORDER BY starts_on DESC, id DESC')->fetchAll();

    $recentAwards = db()->query(
        'SELECT a.*, s.private_name, s.public_name
         FROM awards a
         INNER JOIN students s ON s.id = a.student_id
         ORDER BY a.created_at DESC
         LIMIT 30'
    )->fetchAll();

    $statsStatement = db()->prepare(
        'SELECT
            (SELECT COUNT(*) FROM students WHERE is_active = 1) AS active_students,
            (SELECT COUNT(*) FROM awards WHERE season_id = :season_id_awards) AS season_awards,
            (SELECT COALESCE(SUM(points), 0) FROM awards WHERE season_id = :season_id_points) AS season_points'
    );
    $statsStatement->execute([
        'season_id_awards' => $seasonId,
        'season_id_points' => $seasonId,
    ]);
    $stats = $statsStatement->fetch();
} catch (Throwable $exception) {
    render_error_page($exception);
}

$avatarOptions = ['♟', '♞', '♝', '♜', '♛', '♚', '♙', '♘', '♗', '♖', '♕', '♔'];
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Кабинет учителя</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body class="admin-page">
    <header class="admin-topbar">
        <div>
            <p class="eyebrow">Кабинет</p>
            <h1>Здравствуйте, <?= e($admin['display_name'] ?? 'Учитель') ?></h1>
        </div>
        <nav class="admin-actions" aria-label="Навигация">
            <a class="button ghost" href="index.php">♚ Доска</a>
            <form method="post" action="logout.php">
                <?= csrf_field() ?>
                <button class="button ghost" type="submit">Выйти</button>
            </form>
        </nav>
    </header>

    <main class="admin-shell">
        <aside class="admin-nav" aria-label="Разделы">
            <a href="#award">Награда</a>
            <a href="#students">Ученики</a>
            <a href="#history">История</a>
            <a href="#rewards">Типы</a>
            <a href="#seasons">Сезоны</a>
            <a href="#settings">Настройки</a>
        </aside>

        <div class="admin-content">
            <?php foreach (consume_flash() as $message): ?>
                <div class="flash <?= e($message['type']) ?>"><?= e($message['message']) ?></div>
            <?php endforeach; ?>

            <section class="stats-grid" aria-label="Статистика">
                <article class="stat-card">
                    <span>Ученики</span>
                    <strong><?= (int) ($stats['active_students'] ?? 0) ?></strong>
                </article>
                <article class="stat-card">
                    <span>Награды</span>
                    <strong><?= (int) ($stats['season_awards'] ?? 0) ?></strong>
                </article>
                <article class="stat-card accent">
                    <span>Очки сезона</span>
                    <strong><?= (int) ($stats['season_points'] ?? 0) ?></strong>
                </article>
            </section>

            <section class="admin-section" id="award">
                <div class="section-heading">
                    <div>
                        <p class="eyebrow"><?= $season ? e($season['title']) : 'Сезон не выбран' ?></p>
                        <h2>Выдать награду</h2>
                    </div>
                </div>

                <form method="post" class="award-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="add_award">

                    <div class="form-grid two">
                        <label>
                            <span>Ученик</span>
                            <select name="student_id" required>
                                <option value="">Выберите</option>
                                <?php foreach ($activeStudents as $student): ?>
                                    <option value="<?= (int) $student['id'] ?>">
                                        <?= e($student['private_name']) ?> · <?= e($student['public_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>
                            <span>Дата урока</span>
                            <input type="date" name="lesson_date" value="<?= e(date('Y-m-d')) ?>" required>
                        </label>
                    </div>

                    <div class="reward-picker" role="radiogroup" aria-label="Тип награды">
                        <?php foreach ($activeRewardTypes as $index => $reward): ?>
                            <label class="reward-choice <?= $index === 0 ? 'is-selected' : '' ?>" style="--reward-color: <?= e($reward['color']) ?>">
                                <input type="radio" name="reward_type_id" value="<?= (int) $reward['id'] ?>" <?= $index === 0 ? 'checked' : '' ?>>
                                <span class="reward-icon"><?= e($reward['icon']) ?></span>
                                <span class="reward-title"><?= e($reward['title']) ?></span>
                                <strong>+<?= (int) $reward['points'] ?></strong>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <label>
                        <span>Заметка</span>
                        <textarea name="note" rows="3" placeholder="Например: хорошо решал задачи"></textarea>
                    </label>

                    <button class="button primary" type="submit" <?= $activeStudents === [] || $activeRewardTypes === [] || !$season ? 'disabled' : '' ?>>
                        ♕ Выдать награду
                    </button>
                </form>
            </section>

            <section class="admin-section" id="students">
                <div class="section-heading">
                    <div>
                        <p class="eyebrow">Список</p>
                        <h2>Ученики</h2>
                    </div>
                </div>

                <form method="post" class="compact-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="add_student">
                    <label>
                        <span>Имя</span>
                        <input type="text" name="private_name" required>
                    </label>
                    <label>
                        <span>Псевдоним</span>
                        <input type="text" name="public_name" required>
                    </label>
                    <label>
                        <span>Фигура</span>
                        <select name="avatar">
                            <?php foreach ($avatarOptions as $avatar): ?>
                                <option value="<?= e($avatar) ?>"><?= e($avatar) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <button class="button primary" type="submit">+ Добавить</button>
                </form>

                <div class="item-list">
                    <?php foreach ($students as $student): ?>
                        <article class="student-item <?= (int) $student['is_active'] === 1 ? '' : 'muted' ?>">
                            <div class="item-main">
                                <div class="avatar-ring small"><?= e($student['avatar']) ?></div>
                                <div>
                                    <h3><?= e($student['private_name']) ?></h3>
                                    <p><?= e($student['public_name']) ?> · <?= (int) $student['score'] ?> очков</p>
                                    <?php if (!empty($student['access_code'])): ?>
                                        <p style="font-size:13px;color:var(--accent);font-weight:800">🔑 Код: <?= e($student['access_code']) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="item-actions">
                                <details>
                                    <summary class="button small">Изменить</summary>
                                    <form method="post" class="drawer-form">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="update_student">
                                        <input type="hidden" name="student_id" value="<?= (int) $student['id'] ?>">
                                        <label>
                                            <span>Имя</span>
                                            <input type="text" name="private_name" value="<?= e($student['private_name']) ?>" required>
                                        </label>
                                        <label>
                                            <span>Псевдоним</span>
                                            <input type="text" name="public_name" value="<?= e($student['public_name']) ?>" required>
                                        </label>
                                        <label>
                                            <span>Фигура</span>
                                            <select name="avatar">
                                                <?php foreach ($avatarOptions as $avatar): ?>
                                                    <option value="<?= e($avatar) ?>" <?= $avatar === $student['avatar'] ? 'selected' : '' ?>>
                                                        <?= e($avatar) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </label>
                                        <label>
                                            <span>Заметки</span>
                                            <textarea name="notes" rows="3"><?= e($student['notes'] ?? '') ?></textarea>
                                        </label>
                                        <button class="button primary small" type="submit">Сохранить</button>
                                    </form>
                                </details>

                                <form method="post" style="display:inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="regenerate_code">
                                    <input type="hidden" name="student_id" value="<?= (int) $student['id'] ?>">
                                    <button class="button small" type="submit" title="Новый код доступа">🔑</button>
                                </form>

                                <form method="post">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="toggle_student">
                                    <input type="hidden" name="student_id" value="<?= (int) $student['id'] ?>">
                                    <input type="hidden" name="is_active" value="<?= (int) $student['is_active'] === 1 ? 0 : 1 ?>">
                                    <button class="button small" type="submit" data-confirm="<?= (int) $student['is_active'] === 1 ? 'Архивировать ученика?' : 'Вернуть ученика?' ?>">
                                        <?= (int) $student['is_active'] === 1 ? 'Архив' : 'Вернуть' ?>
                                    </button>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="admin-section" id="history">
                <div class="section-heading">
                    <div>
                        <p class="eyebrow">Последние записи</p>
                        <h2>История наград</h2>
                    </div>
                </div>

                <div class="item-list">
                    <?php if ($recentAwards === []): ?>
                        <p class="soft-text">Пока нет наград.</p>
                    <?php endif; ?>

                    <?php foreach ($recentAwards as $award): ?>
                        <article class="history-item">
                            <div class="item-main">
                                <div class="avatar-ring small"><?= e($award['icon']) ?></div>
                                <div>
                                    <h3><?= e($award['title']) ?> · +<?= (int) $award['points'] ?></h3>
                                    <p><?= e($award['private_name']) ?> · <?= e($award['lesson_date']) ?></p>
                                </div>
                            </div>
                            <div class="item-actions">
                                <details>
                                    <summary class="button small">Правка</summary>
                                    <form method="post" class="drawer-form">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="update_award">
                                        <input type="hidden" name="award_id" value="<?= (int) $award['id'] ?>">
                                        <label>
                                            <span>Очки</span>
                                            <input type="number" name="points" min="0" max="100" value="<?= (int) $award['points'] ?>" required>
                                        </label>
                                        <label>
                                            <span>Дата</span>
                                            <input type="date" name="lesson_date" value="<?= e($award['lesson_date']) ?>" required>
                                        </label>
                                        <label>
                                            <span>Заметка</span>
                                            <textarea name="note" rows="3"><?= e($award['note'] ?? '') ?></textarea>
                                        </label>
                                        <button class="button primary small" type="submit">Сохранить</button>
                                    </form>
                                </details>

                                <form method="post">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete_award">
                                    <input type="hidden" name="award_id" value="<?= (int) $award['id'] ?>">
                                    <button class="button danger small" type="submit" data-confirm="Отменить награду?">Отменить</button>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="admin-section" id="rewards">
                <div class="section-heading">
                    <div>
                        <p class="eyebrow">Шаблоны</p>
                        <h2>Типы наград</h2>
                    </div>
                </div>

                <form method="post" class="compact-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="add_reward_type">
                    <label>
                        <span>Название</span>
                        <input type="text" name="title" required>
                    </label>
                    <label>
                        <span>Очки</span>
                        <input type="number" name="points" min="0" max="100" value="5" required>
                    </label>
                    <label>
                        <span>Фигура</span>
                        <select name="icon">
                            <?php foreach ($avatarOptions as $avatar): ?>
                                <option value="<?= e($avatar) ?>"><?= e($avatar) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>
                        <span>Цвет</span>
                        <input type="color" name="color" value="#2f7d5a">
                    </label>
                    <input type="hidden" name="sort_order" value="100">
                    <button class="button primary" type="submit">+ Добавить</button>
                </form>

                <div class="item-list compact">
                    <?php foreach ($rewardTypes as $reward): ?>
                        <article class="reward-row <?= (int) $reward['is_active'] === 1 ? '' : 'muted' ?>" style="--reward-color: <?= e($reward['color']) ?>">
                            <div class="item-main">
                                <div class="avatar-ring small"><?= e($reward['icon']) ?></div>
                                <div>
                                    <h3><?= e($reward['title']) ?></h3>
                                    <p>+<?= (int) $reward['points'] ?> · <?= e($reward['description'] ?? '') ?></p>
                                </div>
                            </div>
                            <div class="item-actions">
                                <details>
                                    <summary class="button small">Правка</summary>
                                    <form method="post" class="drawer-form">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="update_reward_type">
                                        <input type="hidden" name="reward_type_id" value="<?= (int) $reward['id'] ?>">
                                        <label>
                                            <span>Название</span>
                                            <input type="text" name="title" value="<?= e($reward['title']) ?>" required>
                                        </label>
                                        <label>
                                            <span>Описание</span>
                                            <input type="text" name="description" value="<?= e($reward['description'] ?? '') ?>">
                                        </label>
                                        <div class="form-grid three">
                                            <label>
                                                <span>Очки</span>
                                                <input type="number" name="points" min="0" max="100" value="<?= (int) $reward['points'] ?>" required>
                                            </label>
                                            <label>
                                                <span>Порядок</span>
                                                <input type="number" name="sort_order" min="1" max="999" value="<?= (int) $reward['sort_order'] ?>">
                                            </label>
                                            <label>
                                                <span>Цвет</span>
                                                <input type="color" name="color" value="<?= e($reward['color']) ?>">
                                            </label>
                                        </div>
                                        <label>
                                            <span>Фигура</span>
                                            <select name="icon">
                                                <?php foreach ($avatarOptions as $avatar): ?>
                                                    <option value="<?= e($avatar) ?>" <?= $avatar === $reward['icon'] ? 'selected' : '' ?>>
                                                        <?= e($avatar) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </label>
                                        <button class="button primary small" type="submit">Сохранить</button>
                                    </form>
                                </details>

                                <form method="post">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="toggle_reward_type">
                                    <input type="hidden" name="reward_type_id" value="<?= (int) $reward['id'] ?>">
                                    <input type="hidden" name="is_active" value="<?= (int) $reward['is_active'] === 1 ? 0 : 1 ?>">
                                    <button class="button small" type="submit">
                                        <?= (int) $reward['is_active'] === 1 ? 'Скрыть' : 'Включить' ?>
                                    </button>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="admin-section" id="seasons">
                <div class="section-heading">
                    <div>
                        <p class="eyebrow">Периоды</p>
                        <h2>Сезоны</h2>
                    </div>
                </div>

                <form method="post" class="compact-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="add_season">
                    <label>
                        <span>Название</span>
                        <input type="text" name="title" placeholder="Май 2026" required>
                    </label>
                    <label>
                        <span>Начало</span>
                        <input type="date" name="starts_on" value="<?= e(date('Y-m-d')) ?>" required>
                    </label>
                    <label>
                        <span>Конец</span>
                        <input type="date" name="ends_on">
                    </label>
                    <label class="check-line">
                        <input type="checkbox" name="make_active" checked>
                        <span>Активный</span>
                    </label>
                    <button class="button primary" type="submit">+ Добавить</button>
                </form>

                <div class="item-list compact">
                    <?php foreach ($seasons as $seasonRow): ?>
                        <article class="season-row <?= (int) $seasonRow['is_active'] === 1 ? 'current' : '' ?>">
                            <div>
                                <h3><?= e($seasonRow['title']) ?></h3>
                                <p><?= e($seasonRow['starts_on']) ?><?= $seasonRow['ends_on'] ? ' — ' . e($seasonRow['ends_on']) : '' ?></p>
                            </div>
                            <?php if ((int) $seasonRow['is_active'] === 1): ?>
                                <span class="status-pill">Активный</span>
                            <?php else: ?>
                                <form method="post">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="activate_season">
                                    <input type="hidden" name="season_id" value="<?= (int) $seasonRow['id'] ?>">
                                    <button class="button small" type="submit">Сделать активным</button>
                                </form>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="admin-section" id="settings">
                <div class="section-heading">
                    <div>
                        <p class="eyebrow">Публичная страница</p>
                        <h2>Настройки</h2>
                    </div>
                </div>

                <form method="post" class="form-stack settings-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update_settings">
                    <label>
                        <span>Название</span>
                        <input type="text" name="school_name" value="<?= e(setting_value($settings, 'school_name', 'Юные шахматисты')) ?>" required>
                    </label>
                    <label>
                        <span>Заголовок</span>
                        <input type="text" name="public_title" value="<?= e(setting_value($settings, 'public_title', 'Шахматная доска почёта')) ?>" required>
                    </label>
                    <label>
                        <span>Подзаголовок</span>
                        <input type="text" name="public_subtitle" value="<?= e(setting_value($settings, 'public_subtitle', 'Лучшие результаты сезона')) ?>">
                    </label>
                    <label>
                        <span>Размер топа</span>
                        <input type="number" name="leaderboard_limit" min="1" max="10" value="<?= e(setting_value($settings, 'leaderboard_limit', '5')) ?>">
                    </label>
                    <button class="button primary" type="submit">Сохранить</button>
                </form>
            </section>
        </div>
    </main>

    <script src="assets/app.js"></script>
</body>
</html>
