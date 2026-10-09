<?php
/** Catálogo público: solo servicios activos. GET ?q=&categoria=&destacados=1 */
declare(strict_types=1);
require_once __DIR__ . '/../../config/security.php';

$q = mb_substr($_GET['q'] ?? '', 0, 80);
$cat = $_GET['categoria'] ?? 'todos';
$allowed = ['todos', 'digital', 'atencion', 'institucional'];
if (!in_array($cat, $allowed, true)) $cat = 'todos';
$featured = isset($_GET['destacados']) ? (int) (bool) $_GET['destacados'] : null;

$sql = "SELECT s.slug, s.name, s.summary, s.description, c.slug AS category,
        c.name AS label, s.url, s.link_type, s.icon, s.sort_order, s.featured
        FROM servicios s JOIN categorias c ON c.id = s.category_id
        WHERE s.status = 'activo'";
$params = [];
if ($cat !== 'todos') { $sql .= " AND c.slug = ?"; $params[] = $cat; }
if ($featured !== null) { $sql .= " AND s.featured = ?"; $params[] = $featured; }
if ($q !== '') { $sql .= " AND (s.name LIKE ? OR s.summary LIKE ? OR s.description LIKE ?)"; array_push($params, "%$q%", "%$q%", "%$q%"); }
$sql .= " ORDER BY s.sort_order, s.name";

$st = pdo()->prepare($sql);
$st->execute($params);
json_out(['ok' => true, 'data' => $st->fetchAll()]);
