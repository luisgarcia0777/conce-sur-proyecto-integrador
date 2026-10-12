<?php
// Configuración local para WAMP (MariaDB/MySQL). Cambia estos datos si tu entorno usa otros.
$DB_HOST = '127.0.0.1';
$DB_NAME = 'conce_sur';
$DB_USER = 'root';
$DB_PASS = '';

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
    http_response_code(500);
    exit('No se pudo conectar a la base de datos. Verifica config/database.php e importa database/conce_sur.sql en phpMyAdmin. Detalle técnico: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}
