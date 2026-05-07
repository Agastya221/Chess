<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

try {
    require_student();

    $student = current_student();
    $settings = app_settings();
    $season = active_season();
    $seasonId = $season ? (int) $season['id'] : 0;

    $score = $seasonId ? student_score((int) $student['id'], $seasonId) : 0;
    $awards = $seasonId ? student_awards((int) $student['id'], $seasonId) : [];
    $schoolName = setting_value($settings, 'school_name', 'Юные шахматисты');
} catch (Throwable $exception) {
    render_error_page($exception);
}
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Мой кабинет — <?= e($student['public_name']) ?></title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;800;900&display=swap">
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body class="public-page student-cabinet">
    <header class="public-header">
        <div class="public-header-nav">
            <a class="header-btn" href="index.php">🏆 Доска почёта</a>
            <form method="post" action="logout.php" style="display:inline; margin:0;">
                <?= csrf_field() ?>
                <button class="header-btn danger" type="submit">Выйти</button>
            </form>
        </div>

        <div class="public-hero">
            <div class="student-avatar-big" id="student-avatar"><?= e($student['avatar']) ?></div>
            <p class="eyebrow"><?= e($schoolName) ?></p>
            <h1 class="student-name">Привет, <?= e($student['public_name']) ?>! 👋</h1>
            <?php if ($season): ?>
                <span class="season-pill"><?= e($season['title']) ?></span>
            <?php endif; ?>
        </div>
    </header>

    <main class="leaderboard-wrap student-content">
        <!-- Score card -->
        <section class="score-card" id="score-card">
            <div class="score-icon">⭐</div>
            <div class="score-info">
                <p class="score-label">Мои очки</p>
                <h2 class="score-number" data-target="<?= $score ?>"><?= $score ?></h2>
            </div>
            <div class="score-sparkle" id="score-sparkle"></div>
        </section>

        <!-- Awards history -->
        <section class="student-section">
            <h2 class="student-section-title">🎖 Мои награды</h2>

            <?php if ($awards === []): ?>
                <div class="empty-state">
                    <div class="empty-piece">♙</div>
                    <h2>Пока нет наград</h2>
                    <p>Продолжай стараться — скоро они появятся!</p>
                </div>
            <?php else: ?>
                <div class="awards-timeline">
                    <?php foreach ($awards as $index => $award): ?>
                        <article class="award-card" data-index="<?= $index ?>">
                            <div class="award-icon-ring"><?= e($award['icon']) ?></div>
                            <div class="award-info">
                                <h3><?= e($award['title']) ?></h3>
                                <p class="award-points">+<?= (int) $award['points'] ?> очков</p>
                                <?php if (!empty($award['note'])): ?>
                                    <p class="award-note"><?= e($award['note']) ?></p>
                                <?php endif; ?>
                                <p class="award-date"><?= e($award['lesson_date']) ?></p>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- Quick link to leaderboard -->
        <section class="student-section" style="text-align:center;padding:30px 0">
            <a class="button primary" href="index.php" style="font-size:18px;padding:14px 32px">
                🏆 Посмотреть Доску почёта
            </a>
        </section>
    </main>

    <script src="assets/app.js" defer></script>
</body>
</html>
