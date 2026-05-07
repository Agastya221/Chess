<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

try {
    if (current_admin() !== null) {
        redirect_to('admin.php');
    }

    if (current_student() !== null) {
        redirect_to('student.php');
    }

    $needsInstall = admin_count() === 0;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verify_csrf();

        $loginType = (string) ($_POST['login_type'] ?? 'teacher');

        if ($loginType === 'student') {
            /* ── Student login via access code ── */
            $code = trim((string) ($_POST['access_code'] ?? ''));

            if ($code === '') {
                flash('error', 'Введите код доступа.');
                redirect_to('login.php');
            }

            $statement = db()->prepare(
                'SELECT id FROM students WHERE access_code = :code AND is_active = 1 LIMIT 1'
            );
            $statement->execute(['code' => $code]);
            $student = $statement->fetch();

            if ($student) {
                session_regenerate_id(true);
                $_SESSION['student_id'] = (int) $student['id'];
                unset($_SESSION['admin_id']);
                redirect_to('student.php');
            }

            flash('error', 'Неверный код доступа.');
            redirect_to('login.php');
        }

        /* ── Teacher login ── */
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
            unset($_SESSION['student_id']);

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
    <title>Вход</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;800;900&display=swap">
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body class="page-simple">
    <main class="simple-panel wide" id="login-panel">
        <a class="back-link" href="index.php">← Доска почёта</a>
        <div class="brand-mark">♚</div>

        <?php foreach (consume_flash() as $message): ?>
            <div class="flash <?= e($message['type']) ?>"><?= e($message['message']) ?></div>
        <?php endforeach; ?>

        <?php if ($needsInstall): ?>
            <h1>Добро пожаловать!</h1>
            <div class="flash info">Нужно создать первого администратора.</div>
            <a class="button primary full" href="install.php">Открыть установку</a>
        <?php else: ?>
            <!-- Role chooser tabs -->
            <div class="login-tabs" id="login-tabs">
                <button class="login-tab active" data-tab="teacher" id="tab-teacher">
                    <span class="tab-icon">👨‍🏫</span>
                    <span>Я учитель</span>
                </button>
                <button class="login-tab" data-tab="student" id="tab-student">
                    <span class="tab-icon">🎓</span>
                    <span>Я ученик</span>
                </button>
            </div>

            <!-- Teacher form -->
            <form method="post" class="form-stack login-form" id="form-teacher">
                <?= csrf_field() ?>
                <input type="hidden" name="login_type" value="teacher">
                <h1>Вход для учителя</h1>
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

            <!-- Student form -->
            <form method="post" class="form-stack login-form" id="form-student" style="display:none">
                <?= csrf_field() ?>
                <input type="hidden" name="login_type" value="student">
                <h1>Вход для ученика</h1>
                <p class="soft-text" style="margin-bottom:4px">Введи код, который дал учитель</p>
                <label>
                    <span>Код доступа</span>
                    <input type="text" name="access_code" placeholder="xxxx-xxxx"
                           autocomplete="off" required maxlength="32"
                           style="text-align:center; font-size:22px; font-weight:800; letter-spacing:4px">
                </label>
                <button class="button primary full" type="submit">🚀 Войти</button>
            </form>
        <?php endif; ?>
    </main>

    <script>
    (function() {
        const tabs = document.querySelectorAll('.login-tab');
        const formTeacher = document.getElementById('form-teacher');
        const formStudent = document.getElementById('form-student');
        if (!tabs.length || !formTeacher || !formStudent) return;

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                tabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                if (tab.dataset.tab === 'student') {
                    formTeacher.style.display = 'none';
                    formStudent.style.display = '';
                } else {
                    formTeacher.style.display = '';
                    formStudent.style.display = 'none';
                }
            });
        });
    })();
    </script>
</body>
</html>
