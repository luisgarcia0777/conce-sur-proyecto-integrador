<?php
require_once __DIR__ . '/../includes/auth.php';
auth_require_login();
$u = auth_user(); $mensaje=''; $error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!csrf_valid()) $error='El formulario venció. Recarga la página.';
    else {
        $current=(string)($_POST['password_actual']??''); $new=(string)($_POST['password_nueva']??''); $confirm=(string)($_POST['confirmar']??'');
        try {
            $q=$pdo->prepare('SELECT password_hash FROM usuarios WHERE id_usuario=? AND estado=1'); $q->execute([(int)$u['id_usuario']]); $hash=$q->fetchColumn();
            if (!$hash || !password_verify($current,(string)$hash)) $error='La contraseña actual es incorrecta.';
            elseif (strlen($new)<12) $error='La nueva contraseña debe tener al menos 12 caracteres.';
            elseif ($new !== $confirm) $error='La nueva contraseña y la confirmación no coinciden.';
            elseif ($new === $current) $error='La nueva contraseña debe ser diferente de la actual.';
            else { $q=$pdo->prepare('UPDATE usuarios SET password_hash=? WHERE id_usuario=?'); $q->execute([password_hash($new,PASSWORD_DEFAULT),(int)$u['id_usuario']]); csrf_rotate(); $mensaje='Contraseña actualizada correctamente.'; }
        } catch(Throwable $e) { error_log('CONCE SUR change password: '.$e->getMessage()); $error='No se pudo actualizar la contraseña. Intenta nuevamente.'; }
    }
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Mi cuenta | CONCE SUR</title><link rel="stylesheet" href="../css/style.css"><style>body{background:#0b1120;color:#e5e7eb}.main{min-width:0}.panel{max-width:650px;background:#111827;border:1px solid #273449;border-radius:12px;padding:22px}.muted{color:#9ca3af}.field{display:flex;flex-direction:column;gap:7px;margin:16px 0}.field label{font-size:13px;color:#cbd5e1}.field input{background:#0b1120;color:#fff;border:1px solid #374151;border-radius:7px;padding:11px;font:inherit}.btn{background:#2563eb;color:white;border:0;border-radius:8px;padding:11px 15px;font-weight:600;cursor:pointer}.notice{padding:12px;border-radius:8px;margin:10px 0}.success{background:#064e3b;color:#a7f3d0}.error{background:#7f1d1d;color:#fecaca}</style></head><body><div class="app"><?php auth_render_sidebar('cuenta'); ?><main class="main"><header class="topbar"><div><h2>Mi cuenta</h2><p class="muted">Actualiza tu contraseña de acceso</p></div><?php auth_render_user_badge(); ?></header><?php if($mensaje):?><div class="notice success"><?=h($mensaje)?></div><?php endif;?><?php if($error):?><div class="notice error"><?=h($error)?></div><?php endif;?><section class="panel"><h3>Información de cuenta</h3><p class="muted">Usuario: <strong><?=h($u['usuario'])?></strong> · Rol: <strong><?=h($u['nombre_rol'])?></strong></p><form method="post"><input type="hidden" name="csrf_token" value="<?=h($_SESSION['csrf_token'])?>"><div class="field"><label for="current">Contraseña actual</label><input id="current" name="password_actual" type="password" required autocomplete="current-password"></div><div class="field"><label for="new">Nueva contraseña (mínimo 12 caracteres)</label><input id="new" name="password_nueva" type="password" required minlength="12" autocomplete="new-password"></div><div class="field"><label for="confirm">Confirmar nueva contraseña</label><input id="confirm" name="confirmar" type="password" required minlength="12" autocomplete="new-password"></div><button class="btn" type="submit">Cambiar contraseña</button></form></section></main></div></body></html>
