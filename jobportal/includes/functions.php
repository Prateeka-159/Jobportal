<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ------------------------------------------------------------------
 * Database
 * ----------------------------------------------------------------*/
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', DB_HOST, DB_PORT, DB_NAME);
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $ex) {
            http_response_code(500);
            exit('Database connection failed. Check config/config.php and make sure PostgreSQL is running.');
        }
    }
    return $pdo;
}

/** Run a prepared statement and return the statement. */
function q(string $sql, array $params = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}

/* ------------------------------------------------------------------
 * Output / routing helpers
 * ----------------------------------------------------------------*/
function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $message];
}

function take_flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function money_range(?int $min, ?int $max): string
{
    if (!$min && !$max) {
        return 'Not disclosed';
    }
    $fmt = fn(int $n) => '₹' . number_format($n);
    if ($min && $max) {
        return $fmt($min) . ' – ' . $fmt($max) . ' / yr';
    }
    return ($min ? 'From ' . $fmt($min) : 'Up to ' . $fmt((int)$max)) . ' / yr';
}

function status_badge(string $status): string
{
    $map = [
        'applied'     => 'secondary',
        'shortlisted' => 'primary',
        'rejected'    => 'danger',
        'hired'       => 'success',
        'open'        => 'success',
        'closed'      => 'secondary',
    ];
    return '<span class="badge text-bg-' . ($map[$status] ?? 'secondary') . '">' . e(ucfirst($status)) . '</span>';
}

/* ------------------------------------------------------------------
 * CSRF protection
 * ----------------------------------------------------------------*/
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

/** Call at the top of every POST handler. */
function verify_csrf(): void
{
    $sent = $_POST['csrf'] ?? '';
    if (!is_string($sent) || !hash_equals($_SESSION['csrf'] ?? '', $sent)) {
        http_response_code(419);
        exit('Invalid or expired form token. Go back, refresh the page and try again.');
    }
}

function is_post(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/* ------------------------------------------------------------------
 * Authentication / authorisation
 * ----------------------------------------------------------------*/
function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id'    => (int)$user['user_id'],
        'name'  => $user['full_name'],
        'email' => $user['email'],
        'role'  => $user['role'],
    ];
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function require_role(string ...$roles): array
{
    $user = current_user();
    if (!$user) {
        flash('warning', 'Please log in to continue.');
        redirect('login.php');
    }
    if (!in_array($user['role'], $roles, true)) {
        http_response_code(403);
        exit('403 – You do not have permission to view this page.');
    }
    return $user;
}

function dashboard_url(string $role): string
{
    return match ($role) {
        'admin'    => 'admin/dashboard.php',
        'employer' => 'employer/dashboard.php',
        default    => 'seeker/dashboard.php',
    };
}

/** Seeker row for the logged-in user (null if not a seeker). */
function current_seeker(): ?array
{
    $u = current_user();
    if (!$u || $u['role'] !== 'seeker') {
        return null;
    }
    $row = q('SELECT * FROM job_seekers WHERE user_id = ?', [$u['id']])->fetch();
    return $row ?: null;
}

/** Employer row for the logged-in user (null if not an employer). */
function current_employer(): ?array
{
    $u = current_user();
    if (!$u || $u['role'] !== 'employer') {
        return null;
    }
    $row = q('SELECT * FROM employers WHERE user_id = ?', [$u['id']])->fetch();
    return $row ?: null;
}

/* ------------------------------------------------------------------
 * Resume / skill matching
 * ----------------------------------------------------------------*/
function split_skills(?string $text): array
{
    $parts = preg_split('/[,;\n\/|]+/', strtolower((string)$text)) ?: [];
    $parts = array_map('trim', $parts);
    return array_values(array_unique(array_filter($parts, fn($s) => $s !== '')));
}

/** Percentage (0-100) of the job's required skills the candidate lists. */
function match_score(?string $required, ?string $candidate): int
{
    $need = split_skills($required);
    if (!$need) {
        return 0;
    }
    $have = split_skills($candidate);
    $hit  = count(array_intersect($need, $have));
    return (int)round($hit / count($need) * 100);
}

/* ------------------------------------------------------------------
 * Validation helpers
 * ----------------------------------------------------------------*/
function post_str(string $key, int $max = 255): string
{
    $v = trim((string)($_POST[$key] ?? ''));
    return mb_substr($v, 0, $max);
}

function post_int(string $key): ?int
{
    $v = $_POST[$key] ?? '';
    return ($v === '' || !is_numeric($v)) ? null : (int)$v;
}

function valid_password(string $pw): bool
{
    return strlen($pw) >= 8 && preg_match('/[A-Za-z]/', $pw) && preg_match('/\d/', $pw);
}
