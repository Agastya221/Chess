<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', __DIR__);
define('PUBLIC_PATH', BASE_PATH . DIRECTORY_SEPARATOR . 'public');

require_once APP_PATH . DIRECTORY_SEPARATOR . 'config.php';

load_env_file(BASE_PATH . DIRECTORY_SEPARATOR . '.env');

date_default_timezone_set((string) config_value('app.timezone', 'Europe/Moscow'));

if (PHP_VERSION_ID < 80200) {
    http_response_code(500);
    exit('Нужен PHP 8.2 или новее.');
}

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['SERVER_PORT'] ?? null) === '443');

session_name((string) config_value('session.name'));
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = (string) config_value('database.host');
    $port = (string) config_value('database.port');
    $database = (string) config_value('database.name');
    $charset = (string) config_value('database.charset', 'utf8mb4');

    if ($database === '') {
        throw new RuntimeException('Не указано имя базы данных в .env.');
    }

    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $host, $port, $database, $charset);

    $pdo = new PDO($dsn, (string) config_value('database.username'), (string) config_value('database.password'), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url_path(string $path = ''): string
{
    $path = ltrim($path, '/');
    $base = (string) config_value('app.url', '');

    if ($base !== '') {
        return $path === '' ? $base : $base . '/' . $path;
    }

    return '/' . $path;
}

function redirect_to(string $path, int $status = 302): never
{
    header('Location: ' . url_path($path), true, $status);
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';

    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        exit('Сессия устарела. Обновите страницу и попробуйте снова.');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = [
        'type' => $type,
        'message' => $message,
    ];
}

function consume_flash(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);

    return is_array($messages) ? $messages : [];
}

function post_string(string $key, int $maxLength = 255): string
{
    $value = trim((string) ($_POST[$key] ?? ''));
    $length = function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);

    if ($length > $maxLength) {
        $value = function_exists('mb_substr') ? mb_substr($value, 0, $maxLength) : substr($value, 0, $maxLength);
    }

    return $value;
}

function post_int(string $key, int $default = 0): int
{
    $value = filter_var($_POST[$key] ?? $default, FILTER_VALIDATE_INT);

    return $value === false ? $default : $value;
}

function table_exists(string $table): bool
{
    try {
        db()->query('SELECT 1 FROM `' . str_replace('`', '', $table) . '` LIMIT 1');
        return true;
    } catch (Throwable) {
        return false;
    }
}

function admin_count(): int
{
    if (!table_exists('admin_users')) {
        return 0;
    }

    return (int) db()->query('SELECT COUNT(*) FROM admin_users')->fetchColumn();
}

function current_admin(): ?array
{
    static $admin = null;

    if ($admin !== null) {
        return $admin;
    }

    $adminId = $_SESSION['admin_id'] ?? null;

    if (!$adminId) {
        return null;
    }

    $statement = db()->prepare('SELECT id, username, display_name FROM admin_users WHERE id = :id LIMIT 1');
    $statement->execute(['id' => $adminId]);
    $admin = $statement->fetch() ?: null;

    return $admin;
}

function require_admin(): void
{
    if (current_admin() === null) {
        redirect_to('login.php');
    }
}

function active_season(): ?array
{
    if (!table_exists('seasons')) {
        return null;
    }

    $season = db()->query('SELECT * FROM seasons WHERE is_active = 1 ORDER BY starts_on DESC, id DESC LIMIT 1')->fetch();

    if ($season) {
        return $season;
    }

    $season = db()->query('SELECT * FROM seasons ORDER BY starts_on DESC, id DESC LIMIT 1')->fetch();

    return $season ?: null;
}

function app_settings(): array
{
    if (!table_exists('settings')) {
        return [];
    }

    $rows = db()->query('SELECT `key`, `value` FROM settings')->fetchAll();
    $settings = [];

    foreach ($rows as $row) {
        $settings[$row['key']] = $row['value'];
    }

    return $settings;
}

function setting_value(array $settings, string $key, string $default = ''): string
{
    return isset($settings[$key]) ? (string) $settings[$key] : $default;
}

function leaderboard_top(int $limit = 5): array
{
    if (!table_exists('students') || !table_exists('awards')) {
        return [];
    }

    $season = active_season();

    if (!$season) {
        return [];
    }

    $statement = db()->prepare(
        'SELECT s.public_name, s.avatar, totals.score, totals.awards_count
         FROM students s
         INNER JOIN (
             SELECT student_id, SUM(points) AS score, COUNT(*) AS awards_count
             FROM awards
             WHERE season_id = :season_id
             GROUP BY student_id
         ) totals ON totals.student_id = s.id
         WHERE s.is_active = 1
         ORDER BY totals.score DESC, totals.awards_count DESC, s.created_at ASC
         LIMIT ' . max(1, min(10, $limit))
    );

    $statement->execute(['season_id' => (int) $season['id']]);

    return $statement->fetchAll();
}

/* ── Student Authentication ── */

function generate_access_code(): string
{
    return substr(bin2hex(random_bytes(4)), 0, 4) . '-' . substr(bin2hex(random_bytes(4)), 0, 4);
}

function current_student(): ?array
{
    static $student = null;

    if ($student !== null) {
        return $student;
    }

    $studentId = $_SESSION['student_id'] ?? null;

    if (!$studentId) {
        return null;
    }

    $statement = db()->prepare(
        'SELECT id, private_name, public_name, avatar, access_code
         FROM students WHERE id = :id AND is_active = 1 LIMIT 1'
    );
    $statement->execute(['id' => $studentId]);
    $student = $statement->fetch() ?: null;

    return $student;
}

function require_student(): void
{
    if (current_student() === null) {
        redirect_to('student_login.php');
    }
}

function student_score(int $studentId, int $seasonId): int
{
    $statement = db()->prepare(
        'SELECT COALESCE(SUM(points), 0) FROM awards WHERE student_id = :sid AND season_id = :season'
    );
    $statement->execute(['sid' => $studentId, 'season' => $seasonId]);
    return (int) $statement->fetchColumn();
}

function student_awards(int $studentId, int $seasonId, int $limit = 50): array
{
    $statement = db()->prepare(
        'SELECT a.title, a.icon, a.points, a.note, a.lesson_date, a.created_at
         FROM awards a
         WHERE a.student_id = :sid AND a.season_id = :season
         ORDER BY a.created_at DESC
         LIMIT ' . max(1, min(100, $limit))
    );
    $statement->execute(['sid' => $studentId, 'season' => $seasonId]);
    return $statement->fetchAll();
}

function run_sql_file(string $path): void
{
    if (!is_file($path)) {
        throw new RuntimeException('SQL-файл не найден: ' . $path);
    }

    $sql = file_get_contents($path);

    if ($sql === false || trim($sql) === '') {
        return;
    }

    $statements = preg_split('/;\s*(?:\r?\n|$)/', $sql);

    if ($statements === false) {
        throw new RuntimeException('Не удалось прочитать SQL-файл.');
    }

    foreach ($statements as $statement) {
        $statement = trim($statement);

        if ($statement !== '') {
            db()->exec($statement);
        }
    }
}

function render_error_page(Throwable $exception): never
{
    http_response_code(500);
    $debug = (bool) config_value('app.debug', false);
    ?>
    <!doctype html>
    <html lang="ru">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Ошибка</title>
        <link rel="stylesheet" href="assets/styles.css">
        <meta name="robots" content="noindex, nofollow">
    </head>
    <body class="page-simple">
        <main class="simple-panel">
            <div class="brand-mark">♚</div>
            <h1>Сайт пока не готов</h1>
            <p>Проверьте настройки базы данных и установку.</p>
            <?php if ($debug): ?>
                <pre><?= e($exception->getMessage()) ?></pre>
            <?php endif; ?>
        </main>
    </body>
    </html>
    <?php
    exit;
}
