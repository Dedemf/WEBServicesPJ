<?php
/**
 * Autenticación: login / logout / cambio de clave.
 * POST action=login {username, password} · POST action=logout · POST action=password {current, new}
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config/security.php';
start_session();

$method = $_SERVER['REQUEST_METHOD'];
$action = input('action', $_GET['action'] ?? '');

if ($method === 'GET' && $action === 'status') {
    $u = current_user();
    json_out(['ok' => true, 'user' => $u
        ? ['id' => $u['id'], 'username' => $u['username'], 'name' => $u['display_name'], 'role' => $u['role']]
        : null]);
}

if ($method !== 'POST') json_error('Método no permitido.', 405);

if ($action === 'login') {
    $ip = client_ip();
    if (login_blocked($ip)) {
        json_error('Demasiados intentos. Espera 10 minutos.', 429);
    }
    $username = mb_substr(input('username'), 0, 40);
    $password = input('password');
    if ($username === '' || $password === '') {
        log_attempt($ip, $username, false);
        json_error('Ingresa usuario y contraseña.', 400);
    }
    $st = pdo()->prepare("SELECT * FROM usuarios WHERE username = ?");
    $st->execute([$username]);
    $u = $st->fetch();
    if (!$u || $u['status'] !== 'activo' || !password_verify($password, $u['password_hash'])) {
        log_attempt($ip, $username, false);
        // Respuesta genérica: no revela si el usuario existe.
        json_error('Credenciales inválidas.', 401);
    }
    log_attempt($ip, $username, true);
    session_regenerate_id(true);
    $_SESSION['uid'] = (int) $u['id'];
    $_SESSION['last_activity'] = time();
    pdo()->prepare("UPDATE usuarios SET last_login_at = NOW() WHERE id = ?")->execute([$u['id']]);
    audit((int) $u['id'], 'login', 'usuarios', (int) $u['id']);
    json_out(['ok' => true, 'user' => [
        'id' => $u['id'], 'username' => $u['username'],
        'name' => $u['display_name'], 'role' => $u['role'],
        'mustChange' => !empty($u['must_change_password']),
    ], 'csrf' => csrf_token()]);
}

if ($action === 'logout') {
    $u = current_user();
    if ($u) audit((int) $u['id'], 'logout', 'usuarios', (int) $u['id']);
    session_unset();
    session_destroy();
    json_out(['ok' => true]);
}

if ($action === 'password') {
    $u = require_role('admin', 'editor');
    check_csrf();
    $current = input('current');
    $new = input('new');
    if (mb_strlen($new) < 10) json_error('La nueva clave debe tener al menos 10 caracteres.', 400);
    $st = pdo()->prepare("SELECT password_hash FROM usuarios WHERE id = ?");
    $st->execute([$u['id']]);
    if (!password_verify($current, (string) $st->fetchColumn())) {
        json_error('La clave actual no es correcta.', 401);
    }
    pdo()->prepare("UPDATE usuarios SET password_hash = ?, must_change_password = 0 WHERE id = ?")
        ->execute([password_hash($new, PASSWORD_DEFAULT), $u['id']]);
    audit((int) $u['id'], 'cambio-clave', 'usuarios', (int) $u['id']);
    json_out(['ok' => true]);
}

json_error('Acción no válida.', 400);
