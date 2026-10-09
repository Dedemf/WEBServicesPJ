<?php
/**
 * Gestión de usuarios. SOLO rol admin.
 * GET action=listar · POST action=crear|actualizar|clave|desactivar (con X-CSRF-Token)
 */
declare(strict_types=1);
require_once __DIR__ . '/../../../config/security.php';

$me = require_role('admin');
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? input('action');

if ($method === 'GET' && $action === 'listar') {
    $st = pdo()->query(
        "SELECT id, username, display_name, role, status, last_login_at, created_at FROM usuarios ORDER BY created_at"
    );
    json_out(['ok' => true, 'data' => $st->fetchAll()]);
}
if ($method !== 'POST') json_error('Método no permitido.', 405);
check_csrf();
$db = pdo();

if ($action === 'crear') {
    $username = mb_substr(input('username'), 0, 40);
    $name = mb_substr(input('name'), 0, 80);
    $pass = input('password');
    $role = input('role') === 'admin' ? 'admin' : 'editor';
    if (!preg_match('/^[a-z0-9._-]{3,40}$/i', $username)) json_error('Usuario inválido (3-40, letras/números/._-).', 400);
    if (mb_strlen($name) < 3) json_error('Indica el nombre del encargado.', 400);
    if (mb_strlen($pass) < 10) json_error('La clave debe tener al menos 10 caracteres.', 400);
    try {
        $st = $db->prepare(
            "INSERT INTO usuarios (username, display_name, password_hash, role, status, created_by)
             VALUES (?, ?, ?, ?, 'activo', ?)"
        );
        $st->execute([$username, $name, password_hash($pass, PASSWORD_DEFAULT), $role, $me['id']]);
    } catch (PDOException $e) {
        if (($e->errorInfo[1] ?? 0) === 1062) json_error('Ese usuario ya existe.', 409);
        throw $e;
    }
    $id = (int) $db->lastInsertId();
    audit((int) $me['id'], 'crear', 'usuarios', $id, ['username' => $username, 'role' => $role]);
    json_out(['ok' => true, 'id' => $id]);
}

$id = (int) input('id');
if ($id <= 0) json_error('ID inválido.', 400);

if ($action === 'actualizar') {
    $name = mb_substr(input('name'), 0, 80);
    $role = input('role') === 'admin' ? 'admin' : 'editor';
    $status = input('status') === 'inactivo' ? 'inactivo' : 'activo';
    if ($id === (int) $me['id'] && ($status === 'inactivo' || $role !== 'admin')) {
        json_error('No puedes quitarte tu propio acceso de administrador.', 400);
    }
    $db->prepare("UPDATE usuarios SET display_name = ?, role = ?, status = ? WHERE id = ?")
        ->execute([$name ?: 'Encargado', $role, $status, $id]);
    audit((int) $me['id'], 'actualizar', 'usuarios', $id, ['role' => $role, 'status' => $status]);
    json_out(['ok' => true]);
}

if ($action === 'clave') {
    $pass = input('password');
    if (mb_strlen($pass) < 10) json_error('La clave debe tener al menos 10 caracteres.', 400);
    $db->prepare("UPDATE usuarios SET password_hash = ? WHERE id = ?")
        ->execute([password_hash($pass, PASSWORD_DEFAULT), $id]);
    audit((int) $me['id'], 'cambio-clave', 'usuarios', $id);
    json_out(['ok' => true]);
}

if ($action === 'desactivar') {
    if ($id === (int) $me['id']) json_error('No puedes desactivar tu propia cuenta.', 400);
    $db->prepare("UPDATE usuarios SET status = 'inactivo' WHERE id = ?")->execute([$id]);
    audit((int) $me['id'], 'desactivar', 'usuarios', $id);
    json_out(['ok' => true]);
}

json_error('Acción no válida.', 400);
