<?php
// Configuración de conexión PDO. Para credenciales personalizadas, crea database.local.php
// usando database.example.php como plantilla; ese archivo queda excluido del repositorio.
$localFile = __DIR__ . '/database.local.php';
$local = is_file($localFile) ? require $localFile : [];
if (!is_array($local)) $local = [];
$DB_HOST = $local['host'] ?? (getenv('DB_HOST') ?: '127.0.0.1');
$DB_NAME = $local['name'] ?? (getenv('DB_NAME') ?: 'conce_sur');
$DB_USER = $local['user'] ?? (getenv('DB_USER') ?: 'root');
$DB_PASS = $local['pass'] ?? (getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
         PDO::ATTR_EMULATE_PREPARES => false]
    );
} catch (PDOException $e) {
    error_log('CONCE SUR DB connection error: ' . $e->getMessage());
    http_response_code(500);
    exit('No se pudo conectar a la base de datos. Verifica la configuración local de CONCE SUR y el estado de MySQL/MariaDB.');
}
