<?php
/**
 * Configuración central: carga .env, expone env() y pdo().
 * Las credenciales NUNCA se versionan (.env local, .env.example como plantilla).
 */
declare(strict_types=1);

function env(string $key, string $default = ''): string {
    $server = getenv($key);
    if ($server !== false && $server !== '') return $server;
    static $vars = null;
    if ($vars === null) {
        $vars = [];
        foreach (['../.env', __DIR__ . '/../.env', __DIR__ . '/../../.env'] as $path) {
            $file = realpath($path) ?: $path;
            if (is_readable($file)) {
                foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                    $line = trim($line);
                    if ($line === '' || str_starts_with($line, '#')) continue;
                    [$k, $v] = array_pad(explode('=', $line, 2), 2, '');
                    $vars[trim($k)] = trim($v);
                }
                break;
            }
        }
    }
    return $vars[$key] ?? $default;
}

function pdo(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=' . env('DB_HOST', '127.0.0.1') . ';dbname=' . env('DB_NAME', 'portal_csjsm') . ';charset=utf8mb4',
            env('DB_USER', 'root'),
            env('DB_PASS', ''),
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
    }
    return $pdo;
}

function json_out(mixed $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function json_error(string $message, int $code = 400): void {
    json_out(['ok' => false, 'error' => $message], $code);
}

/** Lee JSON del cuerpo o parámetros POST saneados. */
function input(string $key, string $default = ''): string {
    static $body = null;
    if ($body === null) {
        $raw = file_get_contents('php://input') ?: '';
        $body = str_starts_with($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')
            ? (json_decode($raw, true) ?? []) : $_POST;
    }
    $v = $body[$key] ?? $default;
    return is_string($v) ? trim($v) : $default;
}
