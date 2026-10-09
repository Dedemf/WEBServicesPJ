<?php
/**
 * Gestión de noticias. Roles admin y editor. Fotos JPG/PNG/WebP ≤5 MB c/u, máx 6.
 * GET action=listar · POST action=guardar|archivar (multipart para fotos, con X-CSRF-Token)
 */
declare(strict_types=1);
require_once __DIR__ . '/../../../config/security.php';

$me = require_role('admin', 'editor');
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? ($_POST['action'] ?? '');
$db = pdo();
$upDir = realpath(__DIR__ . '/../../uploads') . DIRECTORY_SEPARATOR . 'noticias';
if (!is_dir($upDir)) mkdir($upDir, 0755, true);
define('NEWS_UPDIR', $upDir);

if ($method === 'GET' && $action === 'listar') {
    $st = $db->query("SELECT * FROM noticias ORDER BY published_at DESC, created_at DESC");
    $rows = $st->fetchAll();
    foreach ($rows as &$r) $r['photos'] = $r['photos'] ? json_decode($r['photos'], true) : [];
    json_out(['ok' => true, 'data' => $rows]);
}
if ($method !== 'POST') json_error('Método no permitido.', 405);
check_csrf();

/** Normaliza watch/youtu.be/shorts/live a embed. '' = sin video. Lanza error si no es YouTube. */
function youtube_embed(string $url): string {
    $url = trim($url);
    if ($url === '') return '';
    if (!preg_match('#^(https://)?(www\.|m\.)?(youtube\.com|youtu\.be)/#i', $url)) {
        json_error('El video debe ser un enlace de YouTube (watch, youtu.be, embed o shorts).', 400);
    }
    if (preg_match('#(?:youtube\.com/(?:watch\?v=|embed/|shorts/|live/)|youtu\.be/)([\w-]{11})#i', $url, $m)) {
        return 'https://www.youtube.com/embed/' . $m[1];
    }
    json_error('No se reconoció el video de YouTube. Revisa el enlace.', 400);
}

function guardar_fotos(array $files): array {
    $out = [];
    $map = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $items = [];
    if (isset($files['name']) && is_array($files['name'])) {
        foreach ($files['name'] as $i => $n) {
            $items[] = ['name' => $n, 'type' => $files['type'][$i] ?? '', 'tmp_name' => $files['tmp_name'][$i] ?? '', 'error' => $files['error'][$i] ?? 4, 'size' => $files['size'][$i] ?? 0];
        }
    }
    foreach (array_slice($items, 0, 6) as $f) {
        if (($f['error'] ?? 4) !== UPLOAD_ERR_OK) continue;
        if (($f['size'] ?? 0) > 5 * 1024 * 1024) continue;
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($f['tmp_name']);
        if (!isset($map[$mime])) continue;
        if (!@getimagesize($f['tmp_name'])) continue; // contenido realmente imagen
        $name = bin2hex(random_bytes(12)) . '.' . $map[$mime];
        if (!move_uploaded_file($f['tmp_name'], NEWS_UPDIR . DIRECTORY_SEPARATOR . $name)) continue;
        $out[] = ['src' => 'uploads/noticias/' . $name];
    }
    return $out;
}

function destacadas(PDO $db, string $excepto = ''): array {
    $sql = "SELECT id, title, published_at FROM noticias WHERE status = 'activo' AND featured = 1";
    $params = [];
    if ($excepto !== '') { $sql .= " AND id <> ?"; $params[] = $excepto; }
    $sql .= " ORDER BY published_at DESC";
    $st = $db->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

if ($action === 'destacar') {
    $id = mb_substr(trim($_POST['id'] ?? ''), 0, 40);
    $on = ($_POST['featured'] ?? '') === '1' ? 1 : 0;
    if ($id === '') json_error('ID inválido.', 400);
    if ($on === 1 && count(destacadas($db, $id)) >= 3) {
        json_out(['ok' => false, 'error' => 'Ya hay 3 destacadas.', 'destacadas' => destacadas($db, $id)], 409);
    }
    $db->prepare("UPDATE noticias SET featured = ?, updated_by = ? WHERE id = ?")->execute([$on, $me['id'], $id]);
    audit((int) $me['id'], $on ? 'destacar' : 'quitar-destacado', 'noticias', null, ['id' => $id]);
    json_out(['ok' => true]);
}

if ($action === 'guardar') {
    $in = fn(string $k, int $max = 5000): string => mb_substr(trim($_POST[$k] ?? ''), 0, $max);
    $id = $in('id', 40);
    $title = $in('title', 160);
    $summary = $in('summary', 280);
    $body = $in('body', 20000);
    $category = $in('category', 60) ?: 'Institucional';
    $external = $in('external', 500);
    $video = youtube_embed($in('video', 500));
    if (mb_strlen($title) < 5) json_error('El título es muy corto.', 400);
    if (mb_strlen($summary) < 10) json_error('Agrega un resumen.', 400);
    if ($body === '') json_error('El contenido está vacío.', 400);
    if ($external !== '' && !preg_match('#^https://#i', $external)) json_error('El enlace externo debe usar https://', 400);
    $featured = ($_POST['featured'] ?? '') === '1' ? 1 : 0;
    $status = in_array($_POST['status'] ?? '', ['activo', 'inactivo', 'archivado'], true) ? $_POST['status'] : 'activo';
    if ($featured === 1 && count(destacadas($db, $id)) >= 3) {
        json_out(['ok' => false, 'error' => 'Ya hay 3 noticias destacadas en portada.', 'destacadas' => destacadas($db, $id)], 409);
    }
    $date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['published_at'] ?? '') ? $_POST['published_at'] : date('Y-m-d');
    $primary = max(0, (int) ($_POST['primary_photo_index'] ?? 0));

    $existing = [];
    if ($id !== '') {
        $st = $db->prepare("SELECT photos, image FROM noticias WHERE id = ?");
        $st->execute([$id]);
        $row = $st->fetch();
        if ($row) $existing = $row['photos'] ? json_decode($row['photos'], true) : [];
    }
    $keep = json_decode($_POST['keep_photos'] ?? '[]', true);
    $kept = is_array($keep) ? array_values(array_filter($existing, fn($p, $i) => in_array($i, array_map('intval', $keep), true), ARRAY_FILTER_USE_BOTH)) : $existing;
    $new = isset($_FILES['photos']) ? guardar_fotos($_FILES['photos']) : [];
    $photos = array_values(array_slice(array_merge($kept, $new), 0, 6));
    $image = $photos[$primary]['src'] ?? $photos[0]['src'] ?? ($existing[0]['src'] ?? null);

    if ($id === '') $id = 'n' . date('YmdHis') . bin2hex(random_bytes(3));
    $db->prepare(
        "INSERT INTO noticias (id, title, summary, body, category, image, photos, primary_photo_index, external, video_url, featured, official, status, published_at, created_by, updated_by)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE title=VALUES(title), summary=VALUES(summary), body=VALUES(body), category=VALUES(category),
           image=VALUES(image), photos=VALUES(photos), primary_photo_index=VALUES(primary_photo_index), external=VALUES(external),
           video_url=VALUES(video_url), featured=VALUES(featured), status=VALUES(status), published_at=VALUES(published_at), updated_by=VALUES(updated_by)"
    )->execute([$id, $title, $summary, $body, $category, $image, json_encode($photos, JSON_UNESCAPED_UNICODE), $primary,
        $external ?: null, $video ?: null, $featured, $status, $date, $me['id'], $me['id']]);
    audit((int) $me['id'], 'guardar', 'noticias', null, ['id' => $id, 'title' => $title]);
    json_out(['ok' => true, 'id' => $id]);
}

if ($action === 'archivar') {
    $id = mb_substr(trim($_POST['id'] ?? ''), 0, 40);
    if ($id === '') json_error('ID inválido.', 400);
    $db->prepare("UPDATE noticias SET status = 'archivado', updated_by = ? WHERE id = ?")->execute([$me['id'], $id]);
    audit((int) $me['id'], 'archivar', 'noticias', null, ['id' => $id]);
    json_out(['ok' => true]);
}

json_error('Acción no válida.', 400);
