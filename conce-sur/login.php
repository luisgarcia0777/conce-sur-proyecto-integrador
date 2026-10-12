<?php
require_once __DIR__ . '/includes/auth.php';
if (auth_user()) { header('Location: ' . app_url('index.php')); exit; }
$showSetup = true;
try { $showSetup = ((int)$pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn() === 0); } catch (Throwable $e) { $showSetup = true; }
$mensaje = ''; $error = '';
if (isset($_GET['disabled'])) $error = 'La cuenta está desactivada o ya no tiene un rol válido. Consulta al administrador.';
if (isset($_GET['expired'])) $error = 'La sesión caducó por inactividad. Inicia sesión nuevamente.';
$blockedUntil = (int)($_SESSION['login_blocked_until'] ?? 0);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        $error = 'El formulario venció. Recarga la página e inténtalo nuevamente.';
    } elseif (time() < $blockedUntil) {
        $error = 'Demasiados intentos. Espera un minuto antes de volver a intentarlo.';
    } else {
        $username = trim((string)($_POST['usuario'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        try {
            $q = $pdo->prepare("SELECT u.id_usuario,u.usuario,u.nombre,u.email,u.estado,u.id_rol,u.password_hash,r.nombre_rol FROM usuarios u JOIN roles r ON r.id_rol=u.id_rol WHERE u.usuario=? AND u.estado=1 LIMIT 1");
            $q->execute([$username]); $row = $q->fetch();
            if ($row && password_verify($password, $row['password_hash'])) {
                session_regenerate_id(true);
                unset($row['password_hash']);
                $_SESSION['auth_user'] = $row;
                $_SESSION['login_failures'] = 0; unset($_SESSION['login_blocked_until']);
                csrf_rotate();
                header('Location: ' . app_url('index.php')); exit;
            }
            $fails = (int)($_SESSION['login_failures'] ?? 0) + 1;
            $_SESSION['login_failures'] = $fails;
            if ($fails >= 5) { $_SESSION['login_blocked_until'] = time() + 60; $_SESSION['login_failures'] = 0; }
            $error = 'Usuario o contraseña incorrectos.';
        } catch (Throwable $e) {
            error_log('CONCE SUR login error: ' . $e->getMessage());
            $error = 'No se pudo validar el acceso. Comprueba que aplicaste database/actualizacion_conce_sur_compatible.sql.';
        }
        csrf_rotate();
    }
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Ingresar | CONCE SUR</title><link rel="stylesheet" href="css/style.css"><style>
body{min-height:100vh;background:#0b1120;color:#e5e7eb;display:grid;place-items:center;padding:24px}.auth-card{width:min(430px,100%);background:#111827;border:1px solid #273449;border-radius:18px;padding:30px;box-shadow:0 20px 60px #0005}.brand{color:#60a5fa;font-size:12px;font-weight:700;letter-spacing:2px}.auth-card h1{margin:12px 0 5px;font-size:28px}.sub{color:#9ca3af;font-size:13px;margin-bottom:24px}.field{display:flex;flex-direction:column;gap:8px;margin:16px 0}.field label{font-size:13px;color:#cbd5e1}.field input{box-sizing:border-box;width:100%;background:#0b1120;color:#fff;border:1px solid #374151;border-radius:8px;padding:12px;font:inherit}.btn{width:100%;margin-top:8px;background:#2563eb;color:white;border:0;border-radius:8px;padding:12px;font-weight:700;cursor:pointer}.note{font-size:12px;color:#9ca3af;line-height:1.5;margin-top:20px}.notice{padding:11px 13px;border-radius:8px;font-size:13px;margin-bottom:12px}.error{background:#7f1d1d;color:#fecaca}.setup-link{display:block;text-align:center;margin-top:15px;color:#93c5fd;font-size:13px}
</style></head><body><main class="auth-card"><div class="brand">CONCE SUR</div><h1>Iniciar sesión</h1><p class="sub">Sistema de gestión de ventas, inventario y servicios</p><?php if($error): ?><div class="notice error"><?=h($error)?></div><?php endif; ?><form method="post" autocomplete="on"><input type="hidden" name="csrf_token" value="<?=h($_SESSION['csrf_token'])?>"><div class="field"><label for="usuario">Usuario</label><input id="usuario" name="usuario" autocomplete="username" maxlength="50" required autofocus></div><div class="field"><label for="password">Contraseña</label><input type="password" id="password" name="password" autocomplete="current-password" required></div><button class="btn" type="submit">Ingresar</button></form><p class="note">El acceso depende del rol asignado por el administrador. Si es la primera instalación, configura el primer administrador mediante el asistente de inicialización.</p><?php if($showSetup): ?><a class="setup-link" href="setup_admin.php">Configurar primer administrador (solo primera instalación)</a><?php endif; ?></main></body></html>
