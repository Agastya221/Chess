<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

$error = null;
$canInstall = false;
$adminExists = false;

try {
    $adminExists = admin_count() > 0;
    $canInstall = (bool) config_value('installer.enabled', false) || !$adminExists;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verify_csrf();

        if (!$canInstall) {
            http_response_code(403);
            exit('Установка отключена.');
        }

        $username = post_string('username', 80);
        $displayName = post_string('display_name', 120);
        $password = (string) ($_POST['password'] ?? '');
        $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

        if ($username === '' || $displayName === '') {
            throw new RuntimeException('Заполните имя и логин.');
        }

        if (strlen($password) < 10) {
            throw new RuntimeException('Пароль должен быть не короче 10 символов.');
        }

        if ($password !== $passwordConfirm) {
            throw new RuntimeException('Пароли не совпадают.');
        }

        run_sql_file(BASE_PATH . '/database/schema.sql');
        run_sql_file(BASE_PATH . '/database/seed.sql');

        if (admin_count() === 0) {
            $statement = db()->prepare(
                'INSERT INTO admin_users (username, password_hash, display_name)
                 VALUES (:username, :password_hash, :display_name)'
            );
            $statement->execute([
                'username' => $username,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'display_name' => $displayName,
            ]);
        }

        flash('success', 'Установка завершена. Теперь можно войти.');
        redirect_to('login.php');
    }
} catch (Throwable $exception) {
    $error = $exception->getMessage();
}
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Установка</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body class="page-simple">
    <main class="simple-panel wide">
        <a class="back-link" href="login.php">← Вход</a>
        <div class="brand-mark">♚</div>
        <h1>Установка сайта</h1>

        <?php if ($error): ?>
            <div class="flash error"><?= e($error) ?></div>
        <?php endif; ?>

        <?php if (!$canInstall): ?>
            <div class="flash info">Установка отключена, потому что администратор уже создан.</div>
        <?php else: ?>
            <form method="post" class="form-stack">
                <?= csrf_field() ?>
                <label>
                    <span>Имя учителя</span>
                    <input type="text" name="display_name" value="Учитель" required>
                </label>
                <label>
                    <span>Логин</span>
                    <input type="text" name="username" value="teacher" autocomplete="username" required>
                </label>
                <label>
                    <span>Пароль</span>
                    <input type="password" name="password" autocomplete="new-password" required minlength="10">
                </label>
                <label>
                    <span>Повторите пароль</span>
                    <input type="password" name="password_confirm" autocomplete="new-password" required minlength="10">
                </label>
                <button class="button primary full" type="submit">Создать сайт</button>
            </form>
        <?php endif; ?>
    </main>
</body>
</html>

