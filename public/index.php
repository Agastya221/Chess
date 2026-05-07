<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

try {
    $settings = app_settings();
    $season = active_season();
    $limit = (int) setting_value($settings, 'leaderboard_limit', '5');
    $leaders = leaderboard_top($limit);
} catch (Throwable $exception) {
    render_error_page($exception);
}

$schoolName = setting_value($settings, 'school_name', 'Юные шахматисты');
$title = setting_value($settings, 'public_title', 'Шахматная доска почёта');
$subtitle = setting_value($settings, 'public_subtitle', 'Лучшие результаты сезона');
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?></title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;800;900&display=swap">
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body class="public-page">
    <header class="public-header">
        <div class="public-header-nav">
            <a class="header-btn" href="login.php">Вход</a>
        </div>
        <div class="public-hero">
            <div class="brand-mark">♚</div>
            <p class="eyebrow"><?= e($schoolName) ?></p>
            <h1><?= e($title) ?></h1>
            <p class="subtitle"><?= e($subtitle) ?></p>
            <?php if ($season): ?>
                <span class="season-pill"><?= e($season['title']) ?></span>
            <?php endif; ?>
        </div>
    </header>

    <main class="leaderboard-wrap" aria-label="Доска почёта">
        <?php if ($leaders === []): ?>
            <section class="empty-state">
                <div class="empty-piece">♙</div>
                <h2>Доска скоро откроется</h2>
                <p>Первые награды появятся после занятий.</p>
            </section>
        <?php else: ?>
            <ol class="leaderboard">
                <?php foreach ($leaders as $index => $leader): ?>
                    <?php
                    $place = $index + 1;
                    $placeClass = match ($place) {
                        1 => 'gold',
                        2 => 'silver',
                        3 => 'bronze',
                        default => 'plain',
                    };
                    ?>
                    <li class="leader-card <?= e($placeClass) ?>">
                        <div class="rank-box">
                            <span class="rank-number"><?= $place ?></span>
                            <span class="rank-label">место</span>
                        </div>
                        <div class="avatar-ring" aria-hidden="true"><?= e($leader['avatar']) ?></div>
                        <div class="leader-copy">
                            <h2><?= e($leader['public_name']) ?></h2>
                            <p><?= (int) $leader['score'] ?> очков</p>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>
    </main>
    <script src="assets/app.js" defer></script>
</body>
</html>

