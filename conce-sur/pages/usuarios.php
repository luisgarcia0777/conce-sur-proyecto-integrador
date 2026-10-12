<?php
require_once __DIR__ . '/../includes/auth.php';
auth_require_roles(['Administrador']);
$mensaje=''; $error='';
$roles=$pdo->query('SELECT id_rol,nombre_rol FROM roles ORDER BY id_rol')->fetchAll();
if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!csrf_valid()) $error='El formulario venció. Recarga la página.';
    else {
        $accion=$_POST['accion']??'';
        try {
            if ($accion==='crear') {
                $nombre=trim((string)($_POST['nombre']??'')); $usuario=trim((string)($_POST['usuario']??'')); $email=trim((string)($_POST['email']??''));
                $roleId=filter_var($_POST['id_rol']??null,FILTER_VALIDATE_INT); $password=(string)($_POST['password']??'');
                if (!preg_match('/^[a-zA-Z0-9._-]{3,50}$/',$usuario)) throw new RuntimeException('Usuario inválido: usa 3–50 letras, números, punto, guion o guion bajo.');
                if (mb_strlen($nombre)<2 || mb_strlen($nombre)>120) throw new RuntimeException('Ingresa un nombre válido.');
                if ($email!=='' && !filter_var($email,FILTER_VALIDATE_EMAIL)) throw new RuntimeException('El correo no tiene un formato válido.');
                if (strlen($password)<12) throw new RuntimeException('La contraseña debe tener al menos 12 caracteres.');
                $validRole=array_filter($roles,fn($r)=>(int)$r['id_rol']===(int)$roleId);
                if (!$validRole) throw new RuntimeException('Selecciona un rol válido.');
                $q=$pdo->prepare('INSERT INTO usuarios (id_rol,usuario,password_hash,nombre,email,estado) VALUES (?,?,?,?,?,1)');
                $q->execute([(int)$roleId,$usuario,password_hash($password,PASSWORD_DEFAULT),$nombre,$email!==''?$email:null]);
                $mensaje='Usuario creado. La contraseña fue almacenada mediante hash seguro.'; csrf_rotate();
            } elseif ($accion==='estado') {
                $id=filter_var($_POST['id_usuario']??null,FILTER_VALIDATE_INT); $nuevo=(int)($_POST['nuevo_estado']??0);
                if (!$id || !in_array($nuevo,[0,1],true)) throw new RuntimeException('Solicitud de estado no válida.');
                if ($id===(int)auth_user()['id_usuario'] && $nuevo===0) throw new RuntimeException('No puedes desactivar tu propia cuenta.');
                $q=$pdo->prepare('SELECT r.nombre_rol,u.estado FROM usuarios u LEFT JOIN roles r ON r.id_rol=u.id_rol WHERE u.id_usuario=?'); $q->execute([$id]); $target=$q->fetch();
                if (!$target) throw new RuntimeException('El usuario no existe.');
                if ($target['nombre_rol']==='Administrador' && (int)$target['estado']===1 && $nuevo===0) {
                    $admins=(int)$pdo->query("SELECT COUNT(*) FROM usuarios u JOIN roles r ON r.id_rol=u.id_rol WHERE r.nombre_rol='Administrador' AND u.estado=1")->fetchColumn();
                    if ($admins<=1) throw new RuntimeException('No puedes desactivar el último administrador activo.');
                }
                $q=$pdo->prepare('UPDATE usuarios SET estado=? WHERE id_usuario=?'); $q->execute([$nuevo,$id]); $mensaje=$nuevo?'Usuario activado.':'Usuario desactivado.'; csrf_rotate();
            } elseif ($accion==='reset_password') {
                $id=filter_var($_POST['id_usuario']??null,FILTER_VALIDATE_INT); $password=(string)($_POST['password_nueva']??'');
                if (!$id || strlen($password)<12) throw new RuntimeException('La nueva contraseña debe tener al menos 12 caracteres.');
                $q=$pdo->prepare('UPDATE usuarios SET password_hash=? WHERE id_usuario=?'); $q->execute([password_hash($password,PASSWORD_DEFAULT),$id]);
                if (!$q->rowCount()) throw new RuntimeException('No se encontró el usuario o la contraseña es igual a la registrada.');
                $mensaje='Contraseña restablecida por el administrador.'; csrf_rotate();
            } else throw new RuntimeException('Acción no reconocida.');
        } catch (RuntimeException $e) { $error=$e->getMessage(); }
          catch (PDOException $e) { error_log('CONCE SUR users: '.$e->getMessage()); $error=$e->getCode()==='23000'?'El nombre de usuario ya existe. Usa otro.':'No se pudo completar la operación.'; }
          catch (Throwable $e) { error_log('CONCE SUR users error: '.$e->getMessage()); $error='No se pudo completar la operación.'; }
    }
}
$usuarios=$pdo->query('SELECT u.id_usuario,u.usuario,u.nombre,u.email,u.estado,u.fecha_registro,r.nombre_rol,u.id_rol FROM usuarios u LEFT JOIN roles r ON r.id_rol=u.id_rol ORDER BY u.id_usuario')->fetchAll();
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Usuarios y roles | CONCE SUR</title><link rel="stylesheet" href="../css/style.css"><style>body{background:#0b1120;color:#e5e7eb}.main{min-width:0}.muted{color:#9ca3af}.panel{background:#111827;border:1px solid #273449;border-radius:12px;padding:18px;margin-bottom:18px}.grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}.field{display:flex;flex-direction:column;gap:7px}.field label{font-size:12px;color:#cbd5e1}.field input,.field select{box-sizing:border-box;width:100%;background:#0b1120;color:#e5e7eb;border:1px solid #374151;border-radius:7px;padding:10px}.btn{display:inline-block;border:0;border-radius:7px;padding:8px 10px;font-size:12px;cursor:pointer;text-decoration:none;font-weight:600}.primary{background:#2563eb;color:white}.secondary{background:#263244;color:#e5e7eb}.danger{background:#7f1d1d;color:#fecaca}.notice{padding:12px;border-radius:8px;margin:12px 0}.success{background:#064e3b;color:#a7f3d0}.error{background:#7f1d1d;color:#fecaca}.wrap{overflow-x:auto}table{width:100%;border-collapse:collapse;font-size:12px}td,th{padding:10px;text-align:left;border-bottom:1px solid #273449;vertical-align:top}th{color:#9ca3af}.inline{display:flex;gap:6px;flex-wrap:wrap}.badge{padding:4px 7px;border-radius:5px;background:#064e3b;color:#a7f3d0}.off{background:#7f1d1d;color:#fecaca}.form-small{display:flex;gap:5px;margin-top:7px}.form-small input{background:#0b1120;color:white;border:1px solid #374151;border-radius:6px;padding:6px;max-width:170px}@media(max-width:900px){.grid{grid-template-columns:1fr 1fr}}@media(max-width:600px){.grid{grid-template-columns:1fr}}</style></head><body><div class="app"><?php auth_render_sidebar('usuarios'); ?><main class="main"><header class="topbar"><div><h2>Usuarios y roles</h2><p class="muted">Solo el administrador puede administrar cuentas y permisos.</p></div><?php auth_render_user_badge(); ?></header><?php if($mensaje):?><div class="notice success"><?=h($mensaje)?></div><?php endif;?><?php if($error):?><div class="notice error"><?=h($error)?></div><?php endif;?><section class="panel"><h3>Crear usuario</h3><p class="muted">Las contraseñas se guardan mediante password_hash; nunca se muestran en la tabla.</p><form method="post"><input type="hidden" name="csrf_token" value="<?=h($_SESSION['csrf_token'])?>"><input type="hidden" name="accion" value="crear"><div class="grid"><div class="field"><label>Nombre completo *</label><input name="nombre" required maxlength="120"></div><div class="field"><label>Usuario *</label><input name="usuario" required pattern="[A-Za-z0-9._-]+" minlength="3" maxlength="50"></div><div class="field"><label>Correo (opcional)</label><input type="email" name="email" maxlength="120"></div><div class="field"><label>Rol *</label><select name="id_rol" required><?php foreach($roles as $r):?><option value="<?=h($r['id_rol'])?>"><?=h($r['nombre_rol']==='Tecnico'?'Técnico':$r['nombre_rol'])?></option><?php endforeach;?></select></div><div class="field"><label>Contraseña inicial (12+ caracteres) *</label><input type="password" name="password" required minlength="12" autocomplete="new-password"></div></div><p style="margin-top:13px"><button class="btn primary" type="submit">Crear usuario</button></p></form></section><section class="panel"><h3>Cuentas registradas</h3><div class="wrap"><table><thead><tr><th>ID</th><th>Nombre / Usuario</th><th>Rol</th><th>Estado</th><th>Acciones</th></tr></thead><tbody><?php foreach($usuarios as $u):?><tr><td><?=h($u['id_usuario'])?></td><td><strong><?=h($u['nombre'])?></strong><br><span class="muted"><?=h($u['usuario'])?><?=($u['email']?' · '.h($u['email']):'')?></span></td><td><?=h($u['nombre_rol']==='Tecnico'?'Técnico':$u['nombre_rol'])?></td><td><span class="badge <?=$u['estado']?'':'off'?>"><?=$u['estado']?'Activo':'Inactivo'?></span></td><td><div class="inline"><form method="post"><input type="hidden" name="csrf_token" value="<?=h($_SESSION['csrf_token'])?>"><input type="hidden" name="accion" value="estado"><input type="hidden" name="id_usuario" value="<?=h($u['id_usuario'])?>"><input type="hidden" name="nuevo_estado" value="<?=$u['estado']?0:1?>"><button class="btn <?=$u['estado']?'danger':'primary'?>" type="submit"><?=$u['estado']?'Desactivar':'Activar'?></button></form></div><?php if((int)$u['id_usuario']!==(int)auth_user()['id_usuario']):?><form method="post" class="form-small"><input type="hidden" name="csrf_token" value="<?=h($_SESSION['csrf_token'])?>"><input type="hidden" name="accion" value="reset_password"><input type="hidden" name="id_usuario" value="<?=h($u['id_usuario'])?>"><input type="password" name="password_nueva" minlength="12" required placeholder="Nueva clave 12+" aria-label="Nueva contraseña"><button class="btn secondary" type="submit">Restablecer</button></form><?php endif;?></td></tr><?php endforeach;?></tbody></table></div></section></main></div></body></html>
