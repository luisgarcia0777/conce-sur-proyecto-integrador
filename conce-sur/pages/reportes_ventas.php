<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/CsvExport.php';
auth_require_roles(['Administrador', 'Vendedor']);

$desde = trim((string)($_GET['desde'] ?? ''));
$hasta = trim((string)($_GET['hasta'] ?? ''));
$formaPago = trim((string)($_GET['forma_pago'] ?? ''));
$buscar = trim((string)($_GET['buscar'] ?? ''));
$error = '';

$validDate = static function (string $value): bool {
    if ($value === '') return true;
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date instanceof DateTimeImmutable && $date->format('Y-m-d') === $value;
};

if (!$validDate($desde) || !$validDate($hasta)) {
    $error = 'Ingresa las fechas en formato válido (AAAA-MM-DD).';
}
if ($desde !== '' && $hasta !== '' && $desde > $hasta) {
    $error = 'La fecha inicial no puede ser posterior a la fecha final.';
}
if ($formaPago !== '' && !in_array($formaPago, ['Contado', 'Crédito'], true)) {
    $error = 'La forma de pago seleccionada no es válida.';
}

$where = [];
$params = [];
if ($desde !== '') { $where[] = 'fecha >= ?'; $params[] = $desde . ' 00:00:00'; }
if ($hasta !== '') { $where[] = 'fecha < DATE_ADD(?, INTERVAL 1 DAY)'; $params[] = $hasta; }
if ($formaPago !== '') { $where[] = 'forma_pago = ?'; $params[] = $formaPago; }
if ($buscar !== '') {
    $where[] = '(numero_venta LIKE ? OR cliente_nombre LIKE ? OR documento_cliente LIKE ? OR producto_nombre LIKE ?)';
    $like = '%' . $buscar . '%';
    array_push($params, $like, $like, $like, $like);
}
$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

$ventas = [];
$resumen = ['cantidad' => 0, 'unidades' => 0, 'importe' => 0.0];
if ($error === '') {
    try {
        $summary = $pdo->prepare('SELECT COUNT(*) AS cantidad, COALESCE(SUM(cantidad),0) AS unidades, COALESCE(SUM(total),0) AS importe FROM ventas' . $whereSql);
        $summary->execute($params);
        $resumen = $summary->fetch(PDO::FETCH_ASSOC) ?: $resumen;

        if (($_GET['exportar'] ?? '') === 'csv') {
            $stmt = $pdo->prepare('SELECT numero_venta,fecha,cliente_nombre,documento_cliente,producto_codigo,producto_nombre,cantidad,precio_unitario,total,forma_pago FROM ventas' . $whereSql . ' ORDER BY fecha DESC, id DESC');
            $stmt->execute($params);
            $rows = [];
            while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $rows[] = [$r['numero_venta'], $r['fecha'], $r['cliente_nombre'], $r['documento_cliente'], $r['producto_codigo'], $r['producto_nombre'], $r['cantidad'], $r['precio_unitario'], $r['total'], $r['forma_pago']];
            }
            CsvExport::stream('reporte_ventas_conce_sur_' . date('Ymd_His') . '.csv', ['N.º venta','Fecha','Cliente','Documento','Código producto','Producto','Cantidad','Precio unitario','Total','Forma de pago'], $rows);
        }
        $stmt = $pdo->prepare('SELECT id,numero_venta,fecha,cliente_nombre,documento_cliente,producto_nombre,cantidad,precio_unitario,total,forma_pago FROM ventas' . $whereSql . ' ORDER BY fecha DESC, id DESC LIMIT 500');
        $stmt->execute($params);
        $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        error_log('CONCE SUR reportes ventas: ' . $e->getMessage());
        $error = 'No se pudo generar el reporte. Verifica la conexión y la estructura de la tabla ventas.';
    }
}
?>
<!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Reportes de ventas | CONCE SUR</title><link rel="stylesheet" href="../css/style.css">
<style>
body{background:#0b1120;color:#e5e7eb}.main{min-width:0}.muted{color:#9ca3af}.panel{background:#111827;border:1px solid #273449;border-radius:12px;padding:18px;margin-bottom:18px}.filters{display:grid;grid-template-columns:repeat(5,minmax(130px,1fr));gap:10px;align-items:end}.filters label{font-size:12px;color:#cbd5e1;display:flex;flex-direction:column;gap:6px}.filters input,.filters select{background:#0b1120;color:#e5e7eb;border:1px solid #374151;border-radius:7px;padding:10px;min-width:0}.btn{display:inline-block;border:0;border-radius:8px;padding:10px 13px;font-weight:600;text-decoration:none;cursor:pointer;font-size:13px}.primary{background:#2563eb;color:#fff}.secondary{background:#263244;color:#e5e7eb}.stats{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:16px}.stat{background:#111827;border:1px solid #273449;border-radius:10px;padding:14px 18px;min-width:155px}.stat span{display:block;color:#9ca3af;font-size:12px}.stat strong{display:block;font-size:22px;margin-top:5px}.notice{padding:12px;border-radius:8px;margin-bottom:14px}.error{background:#7f1d1d;color:#fecaca}.wrap{overflow-x:auto}table{width:100%;border-collapse:collapse;font-size:13px}th,td{text-align:left;padding:10px;border-bottom:1px solid #273449;white-space:nowrap}th{color:#9ca3af}.actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:12px}@media(max-width:950px){.filters{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:560px){.filters{grid-template-columns:1fr}}
</style></head><body><div class="app">
<?php auth_render_sidebar('reportes_ventas'); ?>
<main class="main"><header class="topbar"><div><h2>Reporte de ventas</h2><p class="muted">Consulta operaciones por periodo, cliente y forma de pago.</p></div><?php auth_render_user_badge(); ?></header>
<?php if($error !== ''): ?><div class="notice error"><?=h($error)?></div><?php endif; ?>
<section class="stats"><div class="stat"><span>Operaciones encontradas</span><strong><?=number_format((int)$resumen['cantidad'])?></strong></div><div class="stat"><span>Unidades vendidas</span><strong><?=number_format((int)$resumen['unidades'])?></strong></div><div class="stat"><span>Importe total</span><strong>S/ <?=number_format((float)$resumen['importe'],2)?></strong></div></section>
<section class="panel"><h3>Filtros del reporte</h3><form method="get" class="filters">
<label>Desde<input type="date" name="desde" value="<?=h($desde)?>"></label>
<label>Hasta<input type="date" name="hasta" value="<?=h($hasta)?>"></label>
<label>Forma de pago<select name="forma_pago"><option value="">Todas</option><option value="Contado" <?=$formaPago==='Contado'?'selected':''?>>Contado</option><option value="Crédito" <?=$formaPago==='Crédito'?'selected':''?>>Crédito</option></select></label>
<label>Buscar<input type="search" name="buscar" value="<?=h($buscar)?>" placeholder="Venta, cliente o producto"></label>
<div class="actions"><button class="btn primary" type="submit">Aplicar filtros</button><a class="btn secondary" href="reportes_ventas.php">Limpiar</a></div>
</form><div class="actions"><a class="btn secondary" href="?<?=h(http_build_query(['desde'=>$desde,'hasta'=>$hasta,'forma_pago'=>$formaPago,'buscar'=>$buscar,'exportar'=>'csv']))?>">Exportar resultados CSV</a></div></section>
<section class="panel"><h3>Resultados</h3><p class="muted" style="font-size:12px;margin:5px 0 12px">Se muestran hasta 500 registros. La exportación incluye todos los resultados del filtro.</p><div class="wrap"><table><thead><tr><th>N.º venta</th><th>Fecha</th><th>Cliente</th><th>Documento</th><th>Producto</th><th>Cantidad</th><th>Precio unitario</th><th>Total</th><th>Pago</th></tr></thead><tbody>
<?php if(!$ventas): ?><tr><td colspan="9">No se encontraron ventas para los filtros seleccionados.</td></tr><?php else: foreach($ventas as $v): ?><tr><td><?=h($v['numero_venta'] ?: ('VTA-'.$v['id']))?></td><td><?=h($v['fecha'])?></td><td><?=h($v['cliente_nombre'])?></td><td><?=h($v['documento_cliente'] ?: '—')?></td><td><?=h($v['producto_nombre'])?></td><td><?=number_format((int)$v['cantidad'])?></td><td>S/ <?=number_format((float)$v['precio_unitario'],2)?></td><td>S/ <?=number_format((float)$v['total'],2)?></td><td><?=h($v['forma_pago'])?></td></tr><?php endforeach; endif; ?>
</tbody></table></div></section><p class="muted" style="font-size:12px">El reporte es de consulta y exportación; no modifica las ventas ni el inventario.</p></main></div></body></html>
