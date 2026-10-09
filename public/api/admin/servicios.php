<?php
/**
 * Gestión del catálogo. Roles admin y editor.
 * GET action=listar · POST action=guardar|estado|archivar (con X-CSRF-Token)
 */
declare(strict_types=1);
require_once __DIR__ . '/../../../config/security.php';

$me = require_role('admin', 'editor');
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? input('action');
$db = pdo();

if ($method === 'GET' && $action === 'listar') {
    $st = $db->query(
        "SELECT s.*, c.slug AS category_slug, c.name AS category_name
         FROM servicios s JOIN categorias c ON c.id = s.category_id
         ORDER BY s.sort_order, s.name"
    );
    $cats = $db->query("SELECT id, slug, name FROM categorias WHERE status = 'activo' ORDER BY sort_order")->fetchAll();
    json_out(['ok' => true, 'data' => $st->fetchAll(), 'categorias' => $cats]);
}
if ($method !== 'POST') json_error('Método no permitido.', 405);
check_csrf();

function slugify(string $t): string {
    $t = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', mb_strtolower($t)) ?: '';
    return trim(preg_replace('/[^a-z0-9]+/', '-', $t), '-') ?: 'servicio';
}

if ($action === 'guardar') {
    $id = (int) input('id');
    $name = mb_substr(input('name'), 0, 120);
    $summary = mb_substr(input('summary'), 0, 255);
    $description = mb_substr(input('description'), 0, 5000);
    $categoryId = (int) input('category_id');
    $url = input('url');
    $linkType = input('link_type') === 'interno' ? 'interno' : 'externo';
    $icon = preg_replace('/[^a-z-]/', '', mb_substr(input('icon'), 0, 50)) ?: 'grid';
    $order = max(0, (int) input('sort_order'));
    $featured = input('featured') === '1' ? 1 : 0;
    $status = in_array(input('status'), ['activo', 'inactivo', 'archivado'], true) ? input('status') : 'inactivo';
    if (mb_strlen($name) < 3) json_error('El nombre es muy corto.', 400);
    if (mb_strlen($summary) < 10) json_error('Agrega un resumen descriptivo.', 400);
    if ($url !== '' && !preg_match('#^https://#i', $url)) json_error('La URL debe empezar con https://', 400);
    if ($url === '') { $url = null; $status = 'inactivo'; } // Sin URL validada no se publica
    $slug = slugify(mb_substr(input('slug'), 0, 60) ?: $name);
    if ($id > 0) {
        $db->prepare(
            "UPDATE servicios SET slug=?, name=?, summary=?, description=?, category_id=?, url=?, link_type=?, icon=?, sort_order=?, featured=?, status=?, updated_by=? WHERE id=?"
        )->execute([$slug, $name, $summary, $description ?: null, $categoryId, $url, $linkType, $icon, $order, $featured, $status, $me['id'], $id]);
        audit((int) $me['id'], 'actualizar', 'servicios', $id, ['name' => $name, 'status' => $status]);
        json_out(['ok' => true, 'id' => $id]);
    }
    try {
        $db->prepare(
            "INSERT INTO servicios (slug, name, summary, description, category_id, url, link_type, icon, sort_order, featured, status, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        )->execute([$slug, $name, $summary, $description ?: null, $categoryId, $url, $linkType, $icon, $order, $featured, $status, $me['id']]);
    } catch (PDOException $e) {
        if (($e->errorInfo[1] ?? 0) === 1062) json_error('Ya existe un servicio con ese nombre.', 409);
        throw $e;
    }
    $id = (int) $db->lastInsertId();
    audit((int) $me['id'], 'crear', 'servicios', $id, ['name' => $name]);
    json_out(['ok' => true, 'id' => $id]);
}

$id = (int) input('id');
if ($id <= 0) json_error('ID inválido.', 400);

if ($action === 'estado') {
    $status = in_array(input('status'), ['activo', 'inactivo'], true) ? input('status') : 'inactivo';
    if ($status === 'activo') {
        $url = $db->query("SELECT url FROM servicios WHERE id = $id")->fetchColumn();
        if (!$url) json_error('Valida la URL oficial antes de publicar.', 400);
    }
    $db->prepare("UPDATE servicios SET status = ?, updated_by = ? WHERE id = ?")->execute([$status, $me['id'], $id]);
    audit((int) $me['id'], 'estado', 'servicios', $id, ['status' => $status]);
    json_out(['ok' => true]);
}

if ($action === 'archivar') {
    $db->prepare("UPDATE servicios SET status = 'archivado', updated_by = ? WHERE id = ?")->execute([$me['id'], $id]);
    audit((int) $me['id'], 'archivar', 'servicios', $id);
    json_out(['ok' => true]);
}

json_error('Acción no válida.', 400);
