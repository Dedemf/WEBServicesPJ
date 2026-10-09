<?php
/** Sesión actual + token CSRF (público, sin datos sensibles). */
declare(strict_types=1);
require_once __DIR__ . '/../../config/security.php';
start_session();
$u = current_user();
json_out(['ok' => true, 'user' => $u
    ? ['id' => $u['id'], 'username' => $u['username'], 'name' => $u['display_name'], 'role' => $u['role']]
    : null, 'csrf' => csrf_token()]);
