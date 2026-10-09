<?php
/**
 * Crea el primer administrador del portal (procedimiento seguro de instalación).
 * Uso desde la carpeta del proyecto:  php database/crear-admin.php
 * - Solo CLI. Pide usuario, nombre y contraseña interactiva (mínimo 10 caracteres).
 * - Guarda hash con password_hash(), nunca texto plano. No deja rastros en el repo.
 * Requiere: PHP 8.x con pdo_mysql y MySQL local con BD portal_csjsm.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Solo puede ejecutarse desde la terminal.\n");
}

function ask(string $prompt): string {
    echo $prompt;
    $line = fgets(STDIN);
    return $line === false ? '' : trim($line);
}

function askSecret(string $prompt): string {
    // Sin eco en Windows: usa PowerShell SecureString si está disponible.
    if (PHP_OS_FAMILY === 'Windows') {
        $ps = 'powershell -NoProfile -Command "$p = Read-Host -AsSecureString \'' .
              addslashes($prompt) .
              '\'; [Runtime.InteropServices.Marshal]::PtrToStringAuto(' .
              '[Runtime.InteropServices.Marshal]::SecureStringToBSTR($p))"';
        $out = shell_exec($ps);
        echo "\n";
        return $out === null ? '' : trim($out);
    }
    echo $prompt;
    $stty = shell_exec('stty -g 2>/dev/null');
    if ($stty !== null) { shell_exec('stty -echo'); }
    $line = fgets(STDIN);
    if ($stty !== null) { shell_exec('stty ' . trim($stty)); }
    echo "\n";
    return $line === false ? '' : trim($line);
}

$host = getenv('DB_HOST') ?: '127.0.0.1';
$db   = getenv('DB_NAME') ?: 'portal_csjsm';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$db};charset=utf8mb4",
        $user, $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]
    );
} catch (PDOException $e) {
    exit("No se pudo conectar a MySQL ({$host}/{$db}). Revisa que Laragon esté iniciado.\n");
}

$admins = (int) $pdo->query(
    "SELECT COUNT(*) FROM usuarios WHERE role = 'admin' AND status = 'activo'"
)->fetchColumn();
if ($admins > 0) {
    exit("Ya existe al menos un administrador activo. Usa el panel para crear más cuentas.\n");
}

echo "== Crear administrador inicial · CSJ San Martín ==\n";
$username = '';
while (!preg_match('/^[a-z0-9._-]{3,40}$/i', $username)) {
    $username = ask("Usuario de acceso (3-40, letras/números/._-): ");
}
$display = '';
while (mb_strlen($display) < 3) {
    $display = ask("Nombre y apellido: ");
}
do {
    $pwd = askSecret('Contraseña (mínimo 10 caracteres): ');
    if (mb_strlen($pwd) < 10) { echo "Muy corta, intenta de nuevo.\n"; continue; }
    $pwd2 = askSecret('Repite la contraseña: ');
    if ($pwd !== $pwd2) { echo "No coinciden, intenta de nuevo.\n"; $pwd = ''; }
} while (mb_strlen($pwd) < 10);

$hash = password_hash($pwd, PASSWORD_DEFAULT);
unset($pwd, $pwd2);

$stmt = $pdo->prepare(
    "INSERT INTO usuarios (username, display_name, password_hash, role, status)
     VALUES (:u, :d, :h, 'admin', 'activo')"
);
$stmt->execute([':u' => $username, ':d' => $display, ':h' => $hash]);
$id = (int) $pdo->lastInsertId();

$pdo->prepare(
    "INSERT INTO auditoria (user_id, action, entity, entity_id, details)
     VALUES (:uid, 'crear', 'usuarios', :eid, :det)"
)->execute([
    ':uid' => $id,
    ':eid' => $id,
    ':det' => json_encode(['origen' => 'instalacion', 'rol' => 'admin'], JSON_UNESCAPED_UNICODE),
]);

echo "Administrador '{$username}' creado (id {$id}). Ya puedes cerrar esta ventana.\n";
