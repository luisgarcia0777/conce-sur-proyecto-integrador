<?php
require_once __DIR__ . '/includes/auth.php';
$remoteAddress = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($remoteAddress, ['127.0.0.1', '::1'], true)) { http_response_code(403); exit('Por seguridad, el asistente del primer administrador solo funciona desde localhost.'); }
$error = ''; $done = false; $userCount = null;
try {
    $userCount = (int)$pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
} catch (Throwable $e) {
    error_log('CONCE SUR setup-admin: ' . $e->getMessage());
    http_response_code(500); exit('Primero importa el script database/actualizacion_conce_sur_compatible.sql en la base conce_sur.');
}
if ($userCount > 0) {
    http_response_code(403); exit('El asistente de primer administrador está bloqueado porque ya existe un usuario. Inicia sesión o pide al administrador que gestione cuentas.');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) $error = 'Formulario vencido. Recarga e intenta nuevamente.';
    else {
        $username = trim((string)($_POST['usuario'] ?? ''));
        $name = trim((string)($_POST['nombre'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $confirm = (string)($_POST['confirmar'] ?? '');
        if (!preg_match('/^[a-zA-Z0-9._-]{3,50}$/', $username)) $error = 'El usuario debe tener 3–50 caracteres: letras, números, punto, guion o guion bajo.';
        elseif (strlen($name) < 2 || strlen($name) > 120) $error = 'Ingresa tu nombre (2–120 caracteres).';
        elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $error = 'El correo no tiene un formato válido.';
        elseif (strlen($password) < 12) $error = 'La contraseña debe tener al menos 12 caracteres.';
        elseif ($password !== $confirm) $error = 'Las contraseñas no coinciden.';
        else {
            try {
                $pdo->beginTransaction();
                if ((int)$pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn() > 0) throw new RuntimeException('Ya se creó un usuario. El asistente está cerrado.');
                $roleId = (int)$pdo->query("SELECT id_rol FROM roles WHERE nombre_rol='Administrador' LIMIT 1")->fetchColumn();
                if (!$roleId) throw new RuntimeException('No existe el rol Administrador. Vuelve a importar el script de actualización.');
                $q = $pdo->prepare('INSERT INTO usuarios (id_rol,usuario,password_hash,nombre,email,estado) VALUES (?,?,?,?,?,1)');
                $q->execute([$roleId,$username,password_hash($password, PASSWORD_DEFAULT),$name,$email!==''?$email:null]);
                $pdo->commit(); csrf_rotate(); $done = true;
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                error_log('CONCE SUR setup-admin insert: ' . $e->getMessage());
                $error = $e instanceof RuntimeException ? $e->getMessage() : 'No se pudo crear el administrador. Comprueba que el usuario no esté repetido.';
            }
        }
    }
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Primer administrador | CONCE SUR</title><link rel="stylesheet" href="css/style.css"><style>body{min-height:100vh;background:#0b1120;color:#e5e7eb;display:grid;place-items:center;padding:24px}.card{width:min(520px,100%);background:#111827;border:1px solid #273449;border-radius:16px;padding:28px}.sub{color:#9ca3af;font-size:13px}.field{display:flex;flex-direction:column;gap:7px;margin:14px 0}.field label{font-size:13px}.field input{box-sizing:border-box;width:100%;background:#0b1120;color:white;border:1px solid #374151;border-radius:8px;padding:11px;font:inherit}.btn{width:100%;background:#2563eb;color:white;border:0;border-radius:8px;padding:12px;font-weight:700;cursor:pointer}.notice{padding:12px;border-radius:8px;margin:12px 0;font-size:13px}.error{background:#7f1d1d;color:#fecaca}.success{background:#064e3b;color:#a7f3d0}a{color:#93c5fd}</style></head><body><main class="card"><h1>Configurar primer administrador</h1><p class="sub">Este formulario solo funciona cuando todavía no existen usuarios. No hay contraseñas predeterminadas.</p><?php if($error):?><div class="notice error"><?=h($error)?></div><?php endif;?><?php if($done):?><div class="notice success">Administrador creado. Ya puedes iniciar sesión.</div><a href="login.php">Ir al inicio de sesión</a><?php else:?><form method="post"><input type="hidden" name="csrf_token" value="<?=h($_SESSION['csrf_token'])?>"><div class="field"><label for="nombre">Nombre completo</label><input id="nombre" name="nombre" required maxlength="120"></div><div class="field"><label for="usuario">Usuario</label><input id="usuario" name="usuario" required minlength="3" maxlength="50" pattern="[A-Za-z0-9._-]+" autocomplete="username"></div><div class="field"><label for="email">Correo (opcional)</label><input id="email" type="email" name="email" maxlength="120"></div><div class="field"><label for="password">Contraseña (mínimo 12 caracteres)</label><input id="password" type="password" name="password" required minlength="12" autocomplete="new-password"></div><div class="field"><label for="confirmar">Confirmar contraseña</label><input id="confirmar" type="password" name="confirmar" required minlength="12" autocomplete="new-password"></div><button class="btn" type="submit">Crear administrador</button></form><?php endif;?></main></body></html>
