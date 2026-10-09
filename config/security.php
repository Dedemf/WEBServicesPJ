<?php
/**
 * Sesiones seguras + CSRF + rate-limit de login.
 */
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function start_session(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'secure' => $secure,
        'samesite' => 'Lax',
    ]);
    session_start();
    $timeout = (int) env('SESSION_TIMEOUT', '1800');
    if (isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > $timeout) {
        session_unset();
        session_destroy();
        session_start();
    }
    $_SESSION['last_activity'] = time();
}

function current_user(): ?array {
    start_session();
    if (empty($_SESSION['uid'])) return null;
    $st = pdo()->prepare("SELECT id, username, display_name, role, status FROM usuarios WHERE id = ?");
    $st->execute([$_SESSION['uid']]);
    $u = $st->fetch();
    if (!$u || $u['status'] !== 'activo') {
        session_unset();
        return null;
    }
    return $u;
}

function require_role(string ...$roles): array {
    $u = current_user();
    if (!$u) json_error('No autenticado.', 401);
    if (!in_array($u['role'], $roles, true)) json_error('Sin permiso.', 403);
    return $u;
}

function csrf_token(): string {
    start_session();
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function check_csrf(): void {
    start_session();
    $sent = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? input('csrf_token');
    if (empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], (string) $sent)) {
        json_error('Token de seguridad inválido. Recarga la página.', 403);
    }
}

function client_ip(): string {
    return preg_replace('/[^0-9a-fA-F:., ]/', '', $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
}

/** Bloquea tras N intentos fallidos recientes. Devuelve true si está bloqueado. */
function login_blocked(string $ip): bool {
    $max = (int) env('LOGIN_MAX_ATTEMPTS', '5');
    $window = (int) env('LOGIN_BLOCK_SECONDS', '600');
    $st = pdo()->prepare(
        "SELECT COUNT(*) FROM intentos_acceso WHERE ip = ? AND success = 0 AND created_at > (NOW() - INTERVAL {$window} SECOND)"
    );
    $st->execute([$ip]);
    return ((int) $st->fetchColumn()) >= $max;
}

function log_attempt(string $ip, string $username, bool $success): void {
    $st = pdo()->prepare("INSERT INTO intentos_acceso (ip, username, success) VALUES (?, ?, ?)");
    $st->execute([$ip, mb_substr($username, 0, 40), $success ? 1 : 0]);
}

function audit(int $userId, string $action, string $entity, ?int $entityId, array $details = []): void {
    $st = pdo()->prepare(
        "INSERT INTO auditoria (user_id, action, entity, entity_id, details, ip) VALUES (?, ?, ?, ?, ?, ?)"
    );
    $st->execute([$userId, $action, $entity, $entityId, json_encode($details, JSON_UNESCAPED_UNICODE), client_ip()]);
}
