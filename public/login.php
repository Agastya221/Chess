<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

try {
    if (current_admin() !== null) {
        redirect_to('admin.php');
    }

    $needsInstall = admin_count() === 0;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verify_csrf();

        $attempts = $_SESSION['login_attempts'] ?? [];
        $now = time();
        $attempts = array_values(array_filter($attempts, static fn ($time) => is_int($time) && $time > $now - 900));

        if (count($attempts) >= 8) {
            flash('error', 'Слишком много попыток. Попробуйте позже.');
            redirect_to('login.php');
        }

        $username = post_string('username', 80);
        $password = (string) ($_POST['password'] ?? '');

        $statement = db()->prepare('SELECT * FROM admin_users WHERE username = :username LIMIT 1');
        $statement->execute(['username' => $username]);
        $admin = $statement->fetch();

        if ($admin && password_verify($password, (string) $admin['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int) $admin['id'];
            $_SESSION['login_attempts'] = [];

            db()->prepare('UPDATE admin_users SET last_login_at = NOW() WHERE id = :id')
                ->execute(['id' => (int) $admin['id']]);

            if (password_needs_rehash((string) $admin['password_hash'], PASSWORD_DEFAULT)) {
                db()->prepare('UPDATE admin_users SET password_hash = :hash WHERE id = :id')
                    ->execute([
                        'hash' => password_hash($password, PASSWORD_DEFAULT),
                        'id' => (int) $admin['id'],
                    ]);
            }

            redirect_to('admin.php');
        }

        $attempts[] = $now;
        $_SESSION['login_attempts'] = $attempts;
        flash('error', 'Неверный логин или пароль.');
        redirect_to('login.php');
    }
} catch (Throwable $exception) {
    render_error_page($exception);
}
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Вход для учителя</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;800;900&display=swap">
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body class="page-simple">
    <main class="simple-panel">
        <a class="back-link" href="index.php">← Доска почёта</a>
        <div class="brand-mark">♚</div>
        <h1>Вход для учителя</h1>

        <?php foreach (consume_flash() as $message): ?>
            <div class="flash <?= e($message['type']) ?>"><?= e($message['message']) ?></div>
        <?php endforeach; ?>

        <?php if ($needsInstall): ?>
            <div class="flash info">Нужно создать первого администратора.</div>
            <a class="button primary full" href="install.php">Открыть установку</a>
        <?php else: ?>
            <form method="post" class="form-stack">
                <?= csrf_field() ?>
                <label>
                    <span>Логин</span>
                    <input type="text" name="username" autocomplete="username" required>
                </label>
                <label>
                    <span>Пароль</span>
                    <input type="password" name="password" autocomplete="current-password" required>
                </label>
                <button class="button primary full" type="submit">Войти</button>
            </form>
        <?php endif; ?>
    </main>
</body>
</html>

