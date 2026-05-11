<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

try {
    require_student();

    $student  = current_student();
    $settings = app_settings();
    $season   = active_season();
    $seasonId = $season ? (int) $season['id'] : 0;

    $score      = $seasonId ? student_score((int) $student['id'], $seasonId) : 0;
    $awards     = $seasonId ? student_awards((int) $student['id'], $seasonId) : [];
    $schoolName = setting_value($settings, 'school_name', 'Юные шахматисты');
    $rank       = get_student_rank($score);
    $position   = $seasonId ? student_rank_in_leaderboard((int) $student['id'], $seasonId) : 0;
    $flashes    = consume_flash();
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

    <!-- Toast container (flash messages) -->
    <?php foreach ($flashes as $msg): ?>
        <div class="toast toast-<?= e($msg['type']) ?>" role="alert"><?= e($msg['message']) ?></div>
    <?php endforeach; ?>

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

        <!-- ① Score card + leaderboard position -->
        <section class="score-card" id="score-card">
            <div class="score-icon">⭐</div>
            <div class="score-info">
                <p class="score-label">Мои очки</p>
                <h2 class="score-number" data-target="<?= $score ?>"><?= $score ?></h2>
            </div>
                    <?php if ($position > 0): ?>
                <div class="score-position">
                    <span class="position-label">МОЁ МЕСТО</span>
                    <span class="position-number">
                        <?php if ($position === 1): ?>🥇<?php elseif ($position === 2): ?>🥈<?php elseif ($position === 3): ?>🥉<?php endif; ?>
                        <?= $position ?>-е
                    </span>
                    <span class="position-sub">в таблице</span>
                </div>
            <?php endif; ?>
            <div class="score-sparkle" id="score-sparkle"></div>
        </section>

        <!-- ② Rank — full gamified level-up section -->
        <?php
        $allRanks = [
            ['name' => 'Пешка',         'icon' => '♙', 'emoji' => '🏹', 'min' => 0],
            ['name' => 'Конь',          'icon' => '♘', 'emoji' => '🐴', 'min' => 51],
            ['name' => 'Офицер',        'icon' => '♗', 'emoji' => '⚔️',  'min' => 151],
            ['name' => 'Ладья',         'icon' => '♖', 'emoji' => '🏰', 'min' => 301],
            ['name' => 'Ферзь',         'icon' => '♕', 'emoji' => '👑', 'min' => 501],
            ['name' => 'Гроссмейстер', 'icon' => '♔', 'emoji' => '🌟', 'min' => 751],
        ];
        $currentRankIndex = 0;
        foreach ($allRanks as $i => $r) {
            if ($score >= $r['min']) $currentRankIndex = $i;
        }
        ?>
        <section class="rank-section" id="rank-section">

            <!-- Top: current rank hero -->
            <div class="rank-hero">
                <div class="rank-hero-emoji" id="rank-emoji"><?= $rank['current']['emoji'] ?></div>
                <div class="rank-hero-text">
                    <div class="rank-hero-label">Твой ранг сейчас</div>
                    <div class="rank-hero-name"><?= e($rank['current']['icon']) ?> <?= e($rank['current']['name']) ?></div>
                    <div class="rank-hero-score">У тебя <?= $score ?> очков</div>
                </div>
                <?php if ($rank['next'] !== null): ?>
                    <div class="rank-next-preview">
                        <div class="rank-next-preview-piece"><?= $rank['next']['emoji'] ?></div>
                        <div class="rank-next-preview-label">Следующий</div>
                        <div class="rank-next-preview-name"><?= e($rank['next']['name']) ?></div>
                    </div>
                <?php else: ?>
                    <div class="rank-maxed-badge">👑 MAX!</div>
                <?php endif; ?>
            </div>

            <?php if ($rank['next'] !== null): ?>
                <!-- Big motivating call to action -->
                <div class="rank-cta">
                    🎯 Осталось всего <strong><?= $rank['points_to_next'] ?> очков</strong> — и ты станешь <strong>«<?= e($rank['next']['name']) ?>»</strong>! <?= $rank['next']['emoji'] ?>
                </div>

                <!-- XP progress bar -->
                <div class="rank-xp-wrap">
                    <div class="rank-xp-labels">
                        <span class="rank-xp-current"><?= $rank['current']['emoji'] ?> <?= e($rank['current']['name']) ?></span>
                        <span class="rank-xp-pct"><?= $rank['progress'] ?>%</span>
                        <span class="rank-xp-next"><?= $rank['next']['emoji'] ?> <?= e($rank['next']['name']) ?></span>
                    </div>
                    <div class="rank-xp-bar">
                        <div class="rank-xp-fill" data-target="<?= $rank['progress'] ?>" style="width:0%">
                            <span class="rank-xp-glow"></span>
                        </div>
                    </div>
                    <div class="rank-xp-sub">Ещё <?= $rank['points_to_next'] ?> очков → разблокируешь новое звание! 🔓</div>
                </div>
            <?php else: ?>
                <div class="rank-maxed">🌟 Поздравляем! Ты — Гроссмейстер! Высший ранг достигнут!</div>
            <?php endif; ?>

            <!-- Rank road: all levels with current highlighted -->
            <div class="rank-road">
                <?php foreach ($allRanks as $i => $r): ?>
                    <?php
                    $isUnlocked = $score >= $r['min'];
                    $isCurrent  = $i === $currentRankIndex;
                    $stateClass = $isCurrent ? 'current' : ($isUnlocked ? 'unlocked' : 'locked');
                    ?>
                    <div class="rank-road-step <?= $stateClass ?>" title="<?= e($r['name']) ?>: <?= $r['min'] ?>+ очков">
                        <div class="rank-road-piece"><?= $isUnlocked ? $r['emoji'] : '🔒' ?></div>
                        <div class="rank-road-name"><?= e($r['name']) ?></div>
                        <div class="rank-road-pts"><?= $r['min'] ?>+</div>
                    </div>
                    <?php if ($i < count($allRanks) - 1): ?>
                        <div class="rank-road-arrow <?= $isUnlocked ? 'done' : '' ?>">→</div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

        </section>

        <!-- ① Awards history -->
        <section class="student-section">
        <div class="awards-section-header">
            <div class="awards-section-label">
                <span class="awards-section-icon">&#127894;</span>
                <span><?= e(setting_value($settings, 'cms_my_awards_title_text', 'Мои награды')) ?></span>
            </div>
            <span class="awards-count-badge"><?= count($awards) ?></span>
        </div>

            <?php if ($awards === []): ?>
                <div class="empty-state">
                    <div class="empty-piece" style="font-size:48px">🏹</div>
                    <h2><?= e(setting_value($settings, 'cms_no_awards_title', 'Пока нет наград')) ?></h2>
                    <p><?= e(setting_value($settings, 'cms_no_awards_sub', 'Продолжай стараться — скоро они появятся!')) ?></p>
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

        <!-- ④ Quick link to leaderboard -->
        <section class="student-section" style="text-align:center;padding:30px 0">
            <a class="button primary" href="index.php" style="font-size:18px;padding:14px 32px">
                <?= e(setting_value($settings, 'cms_leaderboard_btn', '🏆 Посмотреть Доску почёта')) ?>
            </a>
        </section>
    </main>

    <script src="assets/app.js" defer></script>
</body>
</html>
