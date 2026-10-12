<?php
session_start();
require_once __DIR__ . '/../config/database.php';
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
$mensaje = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = 'La sesión del formulario venció. Recarga la página e inténtalo nuevamente.';
    } else {
        $documento = trim($_POST['documento_cliente'] ?? '');
        $cliente = trim($_POST['cliente_nombre'] ?? '');
        $telefono = trim($_POST['cliente_telefono'] ?? '');
        $productoId = filter_var($_POST['producto_id'] ?? null, FILTER_VALIDATE_INT);
        $cantidad = filter_var($_POST['cantidad'] ?? null, FILTER_VALIDATE_INT);
        $precioRaw = trim($_POST['precio_unitario'] ?? '');
        $precio = filter_var($precioRaw, FILTER_VALIDATE_FLOAT);
        $formaPago = $_POST['forma_pago'] ?? 'Contado';
        $contadorRaw = trim($_POST['contador_copias'] ?? '');
        $contador = $contadorRaw === '' ? null : filter_var($contadorRaw, FILTER_VALIDATE_INT);
        $observaciones = trim($_POST['observaciones'] ?? '');
        if ($cliente === '' || !$productoId || $cantidad === false || $cantidad === null || $cantidad < 1 || $precioRaw === '' || $precio === false || $precio < 0 || !in_array($formaPago, ['Contado','Crédito'], true) || ($contadorRaw !== '' && ($contador === false || $contador < 0))) {
            $error = 'Completa el cliente, producto, cantidad y precio. Revisa también los valores numéricos.';
        } else {
            try {
                $pdo->beginTransaction();
                // Bloquea el producto para evitar vender más unidades de las disponibles.
                $stmt = $pdo->prepare('SELECT id,codigo,nombre,stock,precio FROM productos WHERE id = ? FOR UPDATE');
                $stmt->execute([$productoId]);
                $producto = $stmt->fetch();
                if (!$producto) {
                    throw new RuntimeException('El producto seleccionado ya no existe.');
                }
                if ((int)$producto['stock'] < $cantidad) {
                    throw new RuntimeException('Stock insuficiente. Disponible: ' . (int)$producto['stock'] . ' unidades.');
                }
                $total = round($cantidad * (float)$precio, 2);
                $ins = $pdo->prepare('INSERT INTO ventas (documento_cliente,cliente_nombre,cliente_telefono,producto_id,producto_codigo,producto_nombre,cantidad,precio_unitario,total,forma_pago,contador_copias,observaciones) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
                $ins->execute([$documento !== '' ? $documento : null, $cliente, $telefono !== '' ? $telefono : null, $producto['id'], $producto['codigo'], $producto['nombre'], $cantidad, number_format((float)$precio,2,'.',''), number_format($total,2,'.',''), $formaPago, $contador, $observaciones !== '' ? $observaciones : null]);
                $ventaId = (int)$pdo->lastInsertId();
                $numero = 'VTA-' . date('Ymd') . '-' . str_pad((string)$ventaId, 5, '0', STR_PAD_LEFT);
                $updVenta = $pdo->prepare('UPDATE ventas SET numero_venta = ? WHERE id = ?');
                $updVenta->execute([$numero, $ventaId]);
                $updStock = $pdo->prepare('UPDATE productos SET stock = stock - ? WHERE id = ? AND stock >= ?');
                $updStock->execute([$cantidad, $productoId, $cantidad]);
                if ($updStock->rowCount() !== 1) throw new RuntimeException('No se pudo descontar el stock. La operación fue cancelada.');
                $pdo->commit();
                $mensaje = 'Venta ' . $numero . ' registrada. Stock actualizado correctamente.';
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            } catch (RuntimeException $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $error = $e->getMessage();
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $error = 'No se pudo registrar la venta. Verifica que hayas importado la versión actualizada de database/conce_sur.sql.';
            }
        }
    }
}
$productos = $pdo->query('SELECT id,codigo,nombre,modelo,tipo,ubicacion,stock,precio FROM productos WHERE stock > 0 ORDER BY nombre,ubicacion')->fetchAll();
$ventas = $pdo->query('SELECT id,numero_venta,fecha,cliente_nombre,producto_nombre,cantidad,total,forma_pago FROM ventas ORDER BY id DESC LIMIT 15')->fetchAll();
$totalHoyStmt = $pdo->query("SELECT COUNT(*) AS cantidad, COALESCE(SUM(total),0) AS importe FROM ventas WHERE fecha >= CURDATE() AND fecha < CURDATE() + INTERVAL 1 DAY");
$resumenHoy = $totalHoyStmt->fetch();
?>
<!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>CONCE SUR | Ventas</title><link rel="stylesheet" href="../css/style.css">
<style>
body{background:#0b1120;color:#e5e7eb}.main{min-width:0}.topbar p,.muted{color:#9ca3af}.stats{display:flex;gap:12px;flex-wrap:wrap;margin:0 0 18px}.stat{background:#111827;border:1px solid #273449;padding:15px 18px;border-radius:10px;min-width:175px}.stat span{display:block;color:#9ca3af;font-size:12px}.stat strong{font-size:23px;display:block;margin-top:5px}.panel{background:#111827;border:1px solid #273449;border-radius:12px;padding:19px;margin-bottom:18px}.layout{display:grid;grid-template-columns:minmax(0,1.7fr) minmax(260px,1fr);gap:18px}.fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:13px}.field{display:flex;flex-direction:column;gap:7px}.field.full{grid-column:1/-1}.field label{font-size:12px;color:#cbd5e1}.field input,.field select,.field textarea{width:100%;box-sizing:border-box;background:#0b1120;color:#e5e7eb;border:1px solid #374151;border-radius:7px;padding:11px;font:inherit}.field textarea{min-height:80px}.btn{display:inline-block;border:0;border-radius:8px;padding:11px 15px;font-weight:600;cursor:pointer;text-decoration:none;font-size:13px}.primary{background:#2563eb;color:white}.secondary{background:#263244;color:#e5e7eb}.notice{padding:12px 14px;border-radius:8px;margin-bottom:15px}.success{background:#064e3b;color:#a7f3d0}.error{background:#7f1d1d;color:#fecaca}.summary-row{display:flex;justify-content:space-between;gap:10px;border-bottom:1px solid #273449;padding:13px 0;color:#cbd5e1}.summary-row.total{font-size:21px;color:white;font-weight:700;border:0}.save{width:100%;margin-top:15px}.table-wrap{overflow-x:auto}table{width:100%;border-collapse:collapse;font-size:13px}th,td{text-align:left;padding:12px;border-bottom:1px solid #273449;white-space:nowrap}th{color:#9ca3af;font-weight:600}.small{font-size:12px;color:#9ca3af;margin-top:10px}@media(max-width:850px){.layout{grid-template-columns:1fr}}@media(max-width:600px){.fields{grid-template-columns:1fr}.field.full{grid-column:auto}}
</style></head><body><div class="app">
<aside class="sidebar"><div class="logo"><h1>CONCE SUR</h1><span>Sistema de Gestión</span></div><nav><a href="../index.php">Dashboard</a><a href="inventario.php">Inventario</a><a href="ventas.php" class="active">Ventas</a><a href="#">Clientes</a><a href="servicios.php">Servicios</a><a href="#">Equipos</a></nav><div class="sidebar-bottom"><a href="#">Configuración</a></div></aside>
<main class="main"><header class="topbar"><div><h2>Nueva venta</h2><p>Registro de operación con descuento automático del inventario</p></div><div class="user"><div class="avatar">A</div><div><strong>Administrador</strong><small>Prototipo local</small></div></div></header>
<?php if($mensaje):?><div class="notice success"><?=h($mensaje)?></div><?php endif;?><?php if($error):?><div class="notice error"><?=h($error)?></div><?php endif;?>
<section class="stats"><div class="stat"><span>Ventas registradas hoy</span><strong><?=number_format((int)$resumenHoy['cantidad'])?></strong></div><div class="stat"><span>Importe vendido hoy</span><strong>S/ <?=number_format((float)$resumenHoy['importe'],2)?></strong></div><div class="stat"><span>Productos disponibles para venta</span><strong><?=number_format(count($productos))?></strong></div></section>
<div class="layout"><section class="panel"><h3 style="margin-bottom:5px">Datos de la venta</h3><p class="muted" style="font-size:13px;margin-bottom:17px">Los campos marcados con * son obligatorios. El sistema valida el stock antes de confirmar.</p>
<form method="post" action="ventas.php" id="venta-form"><input type="hidden" name="csrf_token" value="<?=h($_SESSION['csrf_token'])?>">
<div class="fields"><div class="field"><label for="documento_cliente">DNI / RUC</label><input id="documento_cliente" name="documento_cliente" maxlength="20" placeholder="Documento del cliente"></div><div class="field"><label for="cliente_telefono">Teléfono</label><input id="cliente_telefono" name="cliente_telefono" maxlength="30" placeholder="Teléfono de contacto"></div><div class="field full"><label for="cliente_nombre">Nombre / razón social *</label><input id="cliente_nombre" name="cliente_nombre" maxlength="160" required placeholder="Nombre del cliente"></div>
<div class="field full"><label for="producto_id">Producto o equipo disponible *</label><select id="producto_id" name="producto_id" required><option value="">Seleccionar producto</option><?php foreach($productos as $p):?><option value="<?=h($p['id'])?>" data-precio="<?=h($p['precio'] ?? '')?>" data-stock="<?=h($p['stock'])?>"><?=h($p['codigo'].' — '.$p['nombre'].' '.$p['modelo'].' | '.$p['ubicacion'].' | stock '.$p['stock'])?></option><?php endforeach;?></select><?php if(!$productos):?><span class="small">No hay productos con stock. Registra o repón unidades en Inventario antes de vender.</span><?php endif;?></div>
<div class="field"><label for="cantidad">Cantidad *</label><input type="number" id="cantidad" name="cantidad" value="1" min="1" step="1" required></div><div class="field"><label for="precio_unitario">Precio unitario (S/) *</label><input type="number" id="precio_unitario" name="precio_unitario" min="0" step="0.01" required placeholder="0.00"></div><div class="field"><label for="forma_pago">Forma de pago *</label><select id="forma_pago" name="forma_pago"><option value="Contado">Contado</option><option value="Crédito">Crédito</option></select></div><div class="field"><label for="contador_copias">Contador de copias (opcional)</label><input type="number" id="contador_copias" name="contador_copias" min="0" step="1" placeholder="Solo si corresponde"></div><div class="field full"><label for="observaciones">Observaciones</label><textarea id="observaciones" name="observaciones" maxlength="500" placeholder="Instalación, capacitación, accesorios, etc."></textarea></div></div>
<button class="btn primary save" type="submit" <?=!$productos?'disabled':''?>>Registrar venta y descontar stock</button><p class="small">La venta se confirma en una transacción: si falla el registro o el stock es insuficiente, la operación no se guarda parcialmente.</p></form></section>
<aside class="panel"><h3>Resumen de venta</h3><div class="summary-row"><span>Producto</span><strong id="res-producto">—</strong></div><div class="summary-row"><span>Unidades</span><strong id="res-cantidad">1</strong></div><div class="summary-row"><span>Precio unitario</span><strong id="res-precio">S/ 0.00</strong></div><div class="summary-row"><span>Forma de pago</span><strong id="res-pago">Contado</strong></div><div class="summary-row total"><span>Total</span><strong id="res-total">S/ 0.00</strong></div><p class="small">El total se vuelve a calcular en el servidor antes de guardar.</p></aside></div>
<section class="panel"><h3 style="margin-bottom:12px">Últimas ventas registradas</h3><div class="table-wrap"><table><thead><tr><th>N.º de venta</th><th>Fecha</th><th>Cliente</th><th>Producto</th><th>Cantidad</th><th>Total</th><th>Pago</th></tr></thead><tbody><?php if(!$ventas):?><tr><td colspan="7">Todavía no hay ventas registradas.</td></tr><?php else:foreach($ventas as $v):?><tr><td><?=h($v['numero_venta'] ?: 'Pendiente')?></td><td><?=h(date('d/m/Y H:i',strtotime($v['fecha'])))?></td><td><?=h($v['cliente_nombre'])?></td><td><?=h($v['producto_nombre'])?></td><td><?=number_format((int)$v['cantidad'])?></td><td>S/ <?=number_format((float)$v['total'],2)?></td><td><?=h($v['forma_pago'])?></td></tr><?php endforeach;endif;?></tbody></table></div></section>
</main></div><script>
(()=>{const product=document.getElementById('producto_id'),qty=document.getElementById('cantidad'),price=document.getElementById('precio_unitario'),payment=document.getElementById('forma_pago');const fmt=n=>'S/ '+(Number(n)||0).toLocaleString('es-PE',{minimumFractionDigits:2,maximumFractionDigits:2});function update(){const opt=product.options[product.selectedIndex];const q=Math.max(0,Number(qty.value)||0),p=Math.max(0,Number(price.value)||0);document.getElementById('res-producto').textContent=opt&&opt.value?opt.text.split(' | ')[0]:'—';document.getElementById('res-cantidad').textContent=q;document.getElementById('res-precio').textContent=fmt(p);document.getElementById('res-pago').textContent=payment.value;document.getElementById('res-total').textContent=fmt(q*p);if(opt&&opt.value){qty.max=opt.dataset.stock;if(Number(qty.value)>Number(opt.dataset.stock))qty.setCustomValidity('El stock disponible es '+opt.dataset.stock);else qty.setCustomValidity('');}}product.addEventListener('change',()=>{const o=product.options[product.selectedIndex];if(o&&o.dataset.precio)price.value=o.dataset.precio;update();});[qty,price,payment].forEach(el=>el.addEventListener('input',update));payment.addEventListener('change',update);update();})();
</script></body></html>
