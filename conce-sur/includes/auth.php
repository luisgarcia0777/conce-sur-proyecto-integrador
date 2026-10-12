<?php
// Autenticación y autorización de CONCE SUR para PHP 8.x + PDO + MySQL/MariaDB.
// Este archivo no hace login automáticamente: cada página privada llama auth_require_login().
$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}
require_once __DIR__ . '/../config/database.php';
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

if (!function_exists('h')) {
    function h($value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
}
function app_url(string $relative=''): string {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
    $dir = rtrim(dirname($script), '/');
    if (basename($dir) === 'pages') $dir = rtrim(dirname($dir), '/');
    if ($dir === '/' || $dir === '.') $dir = '';
    return $dir . '/' . ltrim($relative, '/');
}
function csrf_valid(): bool {
    return isset($_SESSION['csrf_token'], $_POST['csrf_token']) && is_string($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}
function csrf_rotate(): void { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }
function auth_user(): ?array { return isset($_SESSION['auth_user']) && is_array($_SESSION['auth_user']) ? $_SESSION['auth_user'] : null; }
function auth_has_role(string $role): bool { return (auth_user()['nombre_rol'] ?? '') === $role; }
function auth_require_login(): void {
    global $pdo;
    if (!empty($_SESSION['auth_user']['id_usuario']) && isset($_SESSION['last_activity']) && (time() - (int)$_SESSION['last_activity']) > 1800) {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], '', (bool)$p['secure'], (bool)$p['httponly']);
        }
        session_destroy();
        header('Location: ' . app_url('login.php?expired=1')); exit;
    }
    if (empty($_SESSION['auth_user']['id_usuario'])) {
        header('Location: ' . app_url('login.php')); exit;
    }
    // Refrescar estado y rol desde BD en cada petición para revocar usuarios desactivados.
    try {
        $q = $pdo->prepare("SELECT u.id_usuario,u.usuario,u.nombre,u.email,u.estado,u.id_rol,r.nombre_rol FROM usuarios u LEFT JOIN roles r ON r.id_rol=u.id_rol WHERE u.id_usuario=? AND u.estado=1 LIMIT 1");
        $q->execute([(int)$_SESSION['auth_user']['id_usuario']]);
        $user = $q->fetch();
    } catch (Throwable $e) {
        error_log('CONCE SUR auth refresh: ' . $e->getMessage());
        http_response_code(503); exit('No se pudo validar la sesión. Verifica la base de datos y vuelve a intentarlo.');
    }
    if (!$user || empty($user['nombre_rol'])) {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], '', (bool)$p['secure'], (bool)$p['httponly']);
        }
        session_destroy();
        header('Location: ' . app_url('login.php?disabled=1')); exit;
    }
    $_SESSION['auth_user'] = $user;
    $_SESSION['last_activity'] = time();
}
function auth_require_roles(array $roles): void {
    auth_require_login();
    if (!in_array(auth_user()['nombre_rol'] ?? '', $roles, true)) {
        http_response_code(403);
        echo '<!doctype html><html lang="es"><meta charset="utf-8"><title>Acceso denegado</title><body style="font-family:Arial;background:#0b1120;color:#e5e7eb;padding:40px"><h1>Acceso denegado</h1><p>Tu rol no tiene permiso para acceder a esta función.</p><p><a style="color:#93c5fd" href="' . h(app_url('index.php')) . '">Volver al panel</a></p></body></html>';
        exit;
    }
}
function auth_render_user_badge(): void {
    $u = auth_user() ?? [];
    $name = trim((string)($u['nombre'] ?? $u['usuario'] ?? 'Usuario'));
    $initial = strtoupper(substr($name, 0, 1));
    $role = ($u['nombre_rol'] ?? '') === 'Tecnico' ? 'Técnico' : ($u['nombre_rol'] ?? 'Usuario');
    echo '<div class="user"><div class="avatar">' . h($initial) . '</div><div><strong>' . h($name) . '</strong><small>' . h($role) . '</small></div></div>';
}
function auth_render_sidebar(string $active): void {
    $u = auth_user() ?? []; $role = $u['nombre_rol'] ?? '';
    $links = [
      ['dashboard','Dashboard','index.php',['Administrador','Vendedor','Tecnico']],
      ['inventario','Inventario','pages/inventario.php',['Administrador','Vendedor']],
      ['ventas','Ventas','pages/ventas.php',['Administrador','Vendedor']],
      ['servicios','Servicios','pages/servicios.php',['Administrador','Tecnico']],
      ['usuarios','Usuarios','pages/usuarios.php',['Administrador']],
      ['cuenta','Mi cuenta','pages/mi_cuenta.php',['Administrador','Vendedor','Tecnico']],
    ];
    echo '<aside class="sidebar"><div class="logo"><h1>CONCE SUR</h1><span>Sistema de Gestión</span></div><nav>';
    foreach ($links as [$key,$label,$url,$allowed]) {
      if (in_array($role, $allowed, true)) echo '<a href="' . h(app_url($url)) . '"' . ($active===$key?' class="active"':'') . '>' . h($label) . '</a>';
    }
    echo '</nav><div class="sidebar-bottom"><form method="post" action="' . h(app_url('logout.php')) . '" style="margin:0"><input type="hidden" name="csrf_token" value="' . h($_SESSION['csrf_token'] ?? '') . '"><button type="submit" style="width:100%;border:0;background:transparent;color:#cbd5e1;text-align:left;padding:10px 14px;cursor:pointer;font:inherit">Cerrar sesión</button></form></div></aside>';
}
