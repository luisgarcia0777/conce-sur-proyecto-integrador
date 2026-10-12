<?php
session_start();
require_once __DIR__ . '/../config/database.php';
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
$mensaje = ''; $error = '';
$tipos = ['Fotocopiadora','Tóner','Repuesto','Plotter','Equipo de oficina','Otro'];
$ubicaciones = ['Local','Almacén'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $error = 'La sesión del formulario venció. Recarga la página e inténtalo nuevamente.';
    } else {
        $accion = $_POST['accion'] ?? '';
        if ($accion === 'crear') {
            $codigo = strtoupper(trim($_POST['codigo'] ?? ''));
            $nombre = trim($_POST['nombre'] ?? '');
            $modelo = trim($_POST['modelo'] ?? '');
            $tipo = $_POST['tipo'] ?? 'Otro';
            $ubicacion = $_POST['ubicacion'] ?? 'Local';
            $stock = filter_var($_POST['stock'] ?? null, FILTER_VALIDATE_INT);
            $minimo = filter_var($_POST['stock_minimo'] ?? null, FILTER_VALIDATE_INT);
            $precioRaw = trim($_POST['precio'] ?? '');
            $precio = $precioRaw === '' ? null : filter_var($precioRaw, FILTER_VALIDATE_FLOAT);
            $observaciones = trim($_POST['observaciones'] ?? '');
            if ($codigo === '' || $nombre === '' || $stock === false || $stock === null || $stock < 0 || $minimo === false || $minimo === null || $minimo < 0 || !in_array($tipo,$tipos,true) || !in_array($ubicacion,$ubicaciones,true) || ($precioRaw !== '' && ($precio === false || $precio < 0))) {
                $error = 'Revisa los campos obligatorios y los valores numéricos. El stock, mínimo y precio no pueden ser negativos.';
            } else {
                try {
                    $q = $pdo->prepare('INSERT INTO productos (codigo,nombre,modelo,tipo,ubicacion,stock,stock_minimo,precio,observaciones) VALUES (?,?,?,?,?,?,?,?,?)');
                    $q->execute([$codigo,$nombre,$modelo,$tipo,$ubicacion,$stock,$minimo,$precio,$observaciones !== '' ? $observaciones : null]);
                    $mensaje = 'Producto registrado y guardado en la base de datos.';
                } catch (PDOException $e) {
                    $error = $e->getCode() === '23000' ? 'Ese código ya existe. Usa un código único.' : 'No se pudo guardar el producto. Verifica la conexión y la estructura de la base de datos.';
                }
            }
        } elseif ($accion === 'eliminar') {
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
            if ($id) {
                try {
                    $q = $pdo->prepare('DELETE FROM productos WHERE id = ?'); $q->execute([$id]);
                    $mensaje = $q->rowCount() ? 'Producto eliminado.' : 'No se encontró el producto.';
                } catch (PDOException $e) {
                    $error = $e->getCode() === '23000'
                        ? 'No se puede eliminar este producto porque tiene ventas asociadas. Conserva el historial y ajusta su stock a cero si ya no se comercializa.'
                        : 'No se pudo eliminar el producto. Verifica la conexión a la base de datos.';
                }
            }
        }
    }
}
$buscar = trim($_GET['buscar'] ?? ''); $fTipo = $_GET['tipo'] ?? ''; $fUbicacion = $_GET['ubicacion'] ?? '';
$sql = 'SELECT * FROM productos WHERE 1=1'; $params = [];
if ($buscar !== '') { $sql .= ' AND (codigo LIKE ? OR nombre LIKE ? OR modelo LIKE ?)'; $like = '%' . $buscar . '%'; array_push($params,$like,$like,$like); }
if (in_array($fTipo,$tipos,true)) { $sql .= ' AND tipo = ?'; $params[] = $fTipo; }
if (in_array($fUbicacion,$ubicaciones,true)) { $sql .= ' AND ubicacion = ?'; $params[] = $fUbicacion; }
$sql .= ' ORDER BY nombre ASC';
$q = $pdo->prepare($sql); $q->execute($params); $productos = $q->fetchAll();
$totalProductos = (int)$pdo->query('SELECT COUNT(*) FROM productos')->fetchColumn();
$totalUnidades = (int)$pdo->query('SELECT COALESCE(SUM(stock),0) FROM productos')->fetchColumn();
$bajoStock = (int)$pdo->query('SELECT COUNT(*) FROM productos WHERE stock <= stock_minimo')->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>CONCE SUR | Inventario</title><link rel="stylesheet" href="../css/style.css">
<style>
body{background:#0b1120;color:#e5e7eb}.main{min-width:0}.topbar p,.muted{color:#9ca3af}.inventory-header,.filters,.stats{display:flex;gap:12px;align-items:center;justify-content:space-between;flex-wrap:wrap;margin-bottom:18px}.btn{display:inline-block;border:0;border-radius:8px;padding:11px 15px;font-weight:600;cursor:pointer;text-decoration:none;font-size:13px}.btn-primary{background:#2563eb;color:#fff}.btn-secondary{background:#263244;color:#e5e7eb}.btn-danger{background:#7f1d1d;color:#fecaca;padding:7px 9px}.panel-dark,.form-panel{background:#111827;border:1px solid #273449;border-radius:12px;padding:18px;margin-bottom:18px}.stats{justify-content:flex-start}.stat{background:#111827;border:1px solid #273449;padding:15px 18px;border-radius:10px;min-width:150px}.stat span{display:block;color:#9ca3af;font-size:12px}.stat strong{font-size:24px;display:block;margin-top:5px}.filters input,.filters select,.form-grid input,.form-grid select,.form-grid textarea{width:100%;background:#0b1120;color:#e5e7eb;border:1px solid #374151;border-radius:7px;padding:11px}.filters .grow{flex:1;min-width:200px}.form-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:13px}.form-grid label{font-size:12px;color:#cbd5e1;display:flex;flex-direction:column;gap:7px}.form-grid .full{grid-column:1/-1}.form-grid textarea{min-height:75px}.actions{display:flex;gap:8px;align-items:center}.notice{padding:12px 14px;border-radius:8px;margin-bottom:15px}.success{background:#064e3b;color:#a7f3d0}.error{background:#7f1d1d;color:#fecaca}.table-wrap{overflow-x:auto}table{min-width:760px;color:#e5e7eb}th,td{border-bottom:1px solid #273449}th{color:#9ca3af}.stock-ok,.stock-low{display:inline-block;padding:5px 8px;border-radius:6px;font-size:12px}.stock-ok{background:#064e3b;color:#a7f3d0}.stock-low{background:#78350f;color:#fde68a}.product-name{font-weight:600}.product-type{font-size:12px;color:#9ca3af;margin-top:4px}.empty{text-align:center;padding:28px;color:#9ca3af}.inline-form{display:inline}.user strong{color:#e5e7eb}.user small{color:#9ca3af}@media(max-width:760px){.form-grid{grid-template-columns:1fr}.form-grid .full{grid-column:auto}.main{padding:16px}.sidebar{width:190px}}
</style></head><body><div class="app">
<aside class="sidebar"><div class="logo"><h1>CONCE SUR</h1><span>Sistema de Gestión</span></div><nav><a href="../index.php">Dashboard</a><a href="inventario.php" class="active">Inventario</a><a href="ventas.php">Ventas</a><a href="#">Clientes</a><a href="servicios.php">Servicios</a><a href="#">Equipos</a></nav><div class="sidebar-bottom"><a href="#">Configuración</a><a href="#">Cerrar sesión</a></div></aside>
<main class="main"><header class="topbar"><div><h2>Inventario</h2><p>Control de productos y equipos por ubicación</p></div><div class="user"><div class="avatar">A</div><div><strong>Administrador</strong><small>Prototipo local</small></div></div></header>
<?php if($mensaje): ?><div class="notice success"><?=h($mensaje)?></div><?php endif; ?><?php if($error): ?><div class="notice error"><?=h($error)?></div><?php endif; ?>
<section class="stats"><div class="stat"><span>Productos registrados</span><strong><?=number_format($totalProductos)?></strong></div><div class="stat"><span>Unidades en stock</span><strong><?=number_format($totalUnidades)?></strong></div><div class="stat"><span>Productos en mínimo o bajo</span><strong><?=number_format($bajoStock)?></strong></div></section>
<div class="inventory-header"><div><h3>Productos registrados</h3><p class="muted" style="margin-top:5px">Los registros se consultan desde la base de datos.</p></div><a class="btn btn-primary" href="#form-producto">+ Registrar producto</a></div>
<section class="form-panel" id="form-producto"><h3 style="margin-bottom:15px">Registrar producto</h3><form method="post" action="inventario.php"><input type="hidden" name="csrf_token" value="<?=h($_SESSION['csrf_token'])?>"><input type="hidden" name="accion" value="crear"><div class="form-grid">
<label>Código *<input name="codigo" maxlength="40" required placeholder="Ej. EQ-003"></label><label>Nombre *<input name="nombre" maxlength="120" required placeholder="Nombre del producto"></label><label>Modelo<input name="modelo" maxlength="120" placeholder="Marca o modelo"></label>
<label>Tipo *<select name="tipo" required><?php foreach($tipos as $t):?><option value="<?=h($t)?>"><?=h($t)?></option><?php endforeach;?></select></label><label>Ubicación *<select name="ubicacion" required><?php foreach($ubicaciones as $u):?><option value="<?=h($u)?>"><?=h($u)?></option><?php endforeach;?></select></label><label>Stock actual *<input type="number" name="stock" min="0" step="1" value="0" required></label>
<label>Stock mínimo *<input type="number" name="stock_minimo" min="0" step="1" value="1" required></label><label>Precio (opcional)<input type="number" name="precio" min="0" step="0.01" placeholder="0.00"></label><label class="full">Observaciones<textarea name="observaciones" maxlength="500" placeholder="Estado, accesorios, notas... "></textarea></label></div><div class="actions" style="margin-top:14px"><button class="btn btn-primary" type="submit">Guardar producto</button><button class="btn btn-secondary" type="reset">Limpiar</button></div></form></section>
<section class="panel-dark"><h3 style="margin-bottom:14px">Consultar inventario</h3><form method="get" class="filters"><div class="grow"><input name="buscar" value="<?=h($buscar)?>" placeholder="Buscar por código, producto o modelo"></div><div><select name="tipo"><option value="">Todos los tipos</option><?php foreach($tipos as $t):?><option value="<?=h($t)?>" <?=$fTipo===$t?'selected':''?>><?=h($t)?></option><?php endforeach;?></select></div><div><select name="ubicacion"><option value="">Todas las ubicaciones</option><?php foreach($ubicaciones as $u):?><option value="<?=h($u)?>" <?=$fUbicacion===$u?'selected':''?>><?=h($u)?></option><?php endforeach;?></select></div><button class="btn btn-primary" type="submit">Buscar</button><a class="btn btn-secondary" href="inventario.php">Limpiar filtros</a></form>
<div class="table-wrap"><table><thead><tr><th>Producto</th><th>Modelo</th><th>Tipo</th><th>Ubicación</th><th>Stock</th><th>Estado</th><th>Acción</th></tr></thead><tbody>
<?php if(!$productos):?><tr><td colspan="7" class="empty">No hay productos que coincidan con la búsqueda.</td></tr><?php else: foreach($productos as $p):?><tr><td><span class="product-name"><?=h($p['nombre'])?></span><div class="product-type">Código: <?=h($p['codigo'])?></div></td><td><?=h($p['modelo'] ?: '—')?></td><td><?=h($p['tipo'])?></td><td><?=h($p['ubicacion'])?></td><td><?=number_format((int)$p['stock'])?></td><td><?php if((int)$p['stock'] <= (int)$p['stock_minimo']):?><span class="stock-low">Stock bajo</span><?php else:?><span class="stock-ok">Disponible</span><?php endif;?></td><td><form class="inline-form" method="post" onsubmit="return confirm('¿Eliminar este producto del inventario?')"><input type="hidden" name="csrf_token" value="<?=h($_SESSION['csrf_token'])?>"><input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id" value="<?=h($p['id'])?>"><button class="btn btn-danger" type="submit">Eliminar</button></form></td></tr><?php endforeach; endif;?>
</tbody></table></div></section><p class="muted" style="font-size:12px">Importante: los productos con prefijo DEMO son datos de prueba. Verifica y reemplaza esos valores por el inventario real antes de presentar resultados operativos.</p>
</main></div></body></html>
