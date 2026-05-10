<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

try {
    $settings   = app_settings();
    $allSeasons = all_seasons();
    $season     = active_season();

    /* Allow viewing past seasons via ?season=ID */
    $viewSeasonId = isset($_GET['season']) ? (int) $_GET['season'] : null;
    $viewSeason   = null;

    if ($viewSeasonId !== null) {
        foreach ($allSeasons as $s) {
            if ((int) $s['id'] === $viewSeasonId) {
                $viewSeason = $s;
                break;
            }
        }
    }

    $displaySeason = $viewSeason ?? $season;
    $limit         = (int) setting_value($settings, 'leaderboard_limit', '5');
    $leaders       = $displaySeason ? leaderboard_top($limit, (int) $displaySeason['id']) : [];
} catch (Throwable $exception) {
    render_error_page($exception);
}

$schoolName = setting_value($settings, 'school_name', 'Юные шахматисты');
$title      = setting_value($settings, 'public_title', 'Шахматная доска почёта');
$subtitle   = setting_value($settings, 'public_subtitle', 'Лучшие результаты сезона');
$isArchive  = $viewSeason !== null && (isset($season['id']) ? (int) $viewSeason['id'] !== (int) $season['id'] : true);
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
            <?php if (count($allSeasons) > 1): ?>
                <form method="get" class="season-select-form">
                    <select name="season" class="season-select" onchange="this.form.submit()" title="Выбрать сезон">
                        <option value="">📅 Текущий сезон</option>
                        <?php foreach ($allSeasons as $s): ?>
                            <?php if ($season && (int) $s['id'] === (int) $season['id']) continue; ?>
                            <option value="<?= (int) $s['id'] ?>" <?= $viewSeasonId === (int) $s['id'] ? 'selected' : '' ?>>
                                🏛 <?= e($s['title']) ?> (<?= e($s['starts_on']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            <?php endif; ?>
            <a class="header-btn" href="login.php">Вход</a>
        </div>
        <div class="public-hero">
            <div class="brand-mark">♚</div>
            <p class="eyebrow"><?= e($schoolName) ?></p>
            <h1><?= e($title) ?></h1>
            <p class="subtitle"><?= e($subtitle) ?></p>
            <?php if ($displaySeason): ?>
                <span class="season-pill <?= $isArchive ? 'archive-pill' : '' ?>">
                    <?= $isArchive ? '🏛 ' : '' ?><?= e($displaySeason['title']) ?>
                </span>
            <?php endif; ?>
            <?php if ($isArchive): ?>
                <p style="margin-top:12px">
                    <a href="index.php" class="header-btn" style="display:inline-flex">← Вернуться к текущему</a>
                </p>
            <?php endif; ?>
        </div>
    </header>

    <main class="leaderboard-wrap" aria-label="Доска почёта">
        <?php if ($leaders === []): ?>
            <section class="empty-state">
                <div class="empty-piece">♙</div>
                <h2><?= $isArchive ? 'Нет данных за этот сезон' : 'Доска скоро откроется' ?></h2>
                <p><?= $isArchive ? 'Награды за этот период не найдены.' : 'Первые награды появятся после занятий.' ?></p>
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
