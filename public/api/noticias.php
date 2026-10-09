<?php
/** Noticias públicas activas. GET ?q=&id= */
declare(strict_types=1);
require_once __DIR__ . '/../../config/security.php';

$id = mb_substr($_GET['id'] ?? '', 0, 40);
$q = mb_substr($_GET['q'] ?? '', 0, 80);

if ($id !== '') {
    $st = pdo()->prepare(
        "SELECT id, title, summary, body, category, image, photos, primary_photo_index,
                external, video_url, featured, official, published_at AS date
         FROM noticias WHERE id = ? AND status = 'activo'"
    );
    $st->execute([$id]);
    $n = $st->fetch();
    if (!$n) json_error('Noticia no disponible.', 404);
    $n['photos'] = $n['photos'] ? json_decode($n['photos'], true) : [];
    json_out(['ok' => true, 'data' => $n]);
}

$sql = "SELECT id, title, summary, body, category, image, photos, primary_photo_index,
               external, video_url, featured, official, published_at AS date
        FROM noticias WHERE status = 'activo'";
$params = [];
if ($q !== '') { $sql .= " AND (title LIKE ? OR summary LIKE ? OR category LIKE ?)"; array_push($params, "%$q%", "%$q%", "%$q%"); }
$sql .= " ORDER BY featured DESC, published_at DESC";
$st = pdo()->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll();
foreach ($rows as &$r) $r['photos'] = $r['photos'] ? json_decode($r['photos'], true) : [];
json_out(['ok' => true, 'data' => $rows]);
