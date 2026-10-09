<?php
/**
 * Migraciones versionadas: aplica database/migraciones/*.sql pendientes
 * y registra cada versión en la tabla `migraciones`.
 *
 * Uso:
 *   php database/migrate.php             Aplica lo pendiente y muestra estado
 *   php database/migrate.php --status    Solo muestra estado
 *   php database/migrate.php --baseline  Marca todo como aplicado sin ejecutar
 *                                        (BD que ya tienen la estructura)
 *
 * Flujo en equipo: git pull → php database/migrate.php → listo.
 * BD de pruebas: $env:DB_NAME='portal_csjsm_test'; php database/migrate.php
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

$dir = __DIR__ . '/migraciones';
$mode = $argv[1] ?? '';

try {
    $host = env('DB_HOST', '127.0.0.1');
    $name = env('DB_NAME', 'portal_csjsm');
    $noDb = new PDO(
        'mysql:host=' . $host . ';charset=utf8mb4',
        env('DB_USER', 'root'), env('DB_PASS', ''),
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $noDb->exec("CREATE DATABASE IF NOT EXISTS `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $db = pdo();
    $db->exec("USE `$name`");
} catch (Throwable $e) {
    exit("Sin conexión a MySQL. Revisa .env y que Laragon esté iniciado.\n");
}
$db->exec("CREATE TABLE IF NOT EXISTS migraciones (
  version VARCHAR(40) NOT NULL, applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (version)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$applied = $db->query("SELECT version FROM migraciones")->fetchAll(PDO::FETCH_COLUMN);
$files = glob($dir . '/*.sql') ?: [];
sort($files);

if ($mode === '--status') {
    foreach ($files as $f) {
        $v = basename($f);
        echo (in_array($v, $applied, true) ? '[OK] ' : '[ ..] ') . $v . "\n";
    }
    exit;
}

if ($mode === '--baseline') {
    $ins = $db->prepare("INSERT IGNORE INTO migraciones (version) VALUES (?)");
    foreach ($files as $f) $ins->execute([basename($f)]);
    echo "Baseline registrado: " . count($files) . " migraciones marcadas.\n";
    exit;
}

function split_sql(string $sql): array {
    $stmts = [];
    $buf = '';
    $depth = 0;
    foreach (preg_split('/\r?\n/', $sql) as $line) {
        if (preg_match('/^\s*--/', $line)) continue;               // comentarios de línea
        if (preg_match('/^\s*BEGIN\b/i', $line)) $depth++;
        $buf .= $line . "\n";
        if (preg_match('/^\s*END(?!\s+IF)\b/i', $line)) $depth = max(0, $depth - 1);
        if ($depth === 0 && preg_match('/;\s*$/', rtrim($line))) {
            if (trim($buf, ";\n\r\t ") !== '') $stmts[] = $buf;
            $buf = '';
        }
    }
    if (trim($buf, ";\n\r\t ") !== '') $stmts[] = $buf;
    return $stmts;
}

$done = 0;
foreach ($files as $f) {
    $v = basename($f);
    if (in_array($v, $applied, true)) continue;
    echo "Aplicando $v ... ";
    try {
        foreach (split_sql(file_get_contents($f)) as $stmt) $db->exec($stmt);
        $db->prepare("INSERT INTO migraciones (version) VALUES (?)")->execute([$v]);
        echo "OK\n";
        $done++;
    } catch (Throwable $e) {
        echo "FALLO\n" . $e->getMessage() . "\nDetenido. Corrige y vuelve a ejecutar.\n";
        exit(1);
    }
}
echo $done === 0 ? "Al día: nada pendiente.\n" : "Listo: $done migraciones aplicadas.\n";
