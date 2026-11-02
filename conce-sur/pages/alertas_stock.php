<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/CsvExport.php';
auth_require_roles(['Administrador', 'Vendedor']);

$buscar = trim((string)($_GET['buscar'] ?? ''));
$ubicacion = trim((string)($_GET['ubicacion'] ?? ''));
$tipo = trim((string)($_GET['tipo'] ?? ''));
$estado = trim((string)($_GET['estado'] ?? ''));
$ubicaciones = ['Local', 'Almacén'];
$tipos = ['Fotocopiadora','Tóner','Repuesto','Plotter','Equipo de oficina','Otro'];
$estados = ['Todos','Sin stock','Por debajo del mínimo','En mínimo'];
$error = '';
if ($ubicacion !== '' && !in_array($ubicacion, $ubicaciones, true)) $error = 'La ubicación seleccionada no es válida.';
if ($tipo !== '' && !in_array($tipo, $tipos, true)) $error = 'El tipo de producto seleccionado no es válido.';
if ($estado !== '' && !in_array($estado, $estados, true)) $error = 'El estado seleccionado no es válido.';

$where = ['stock <= stock_minimo'];
$params = [];
if ($buscar !== '') { $where[] = '(codigo LIKE ? OR nombre LIKE ? OR modelo LIKE ?)'; $like = '%' . $buscar . '%'; array_push($params,$like,$like,$like); }
if ($ubicacion !== '') { $where[] = 'ubicacion = ?'; $params[] = $ubicacion; }
if ($tipo !== '') { $where[] = 'tipo = ?'; $params[] = $tipo; }
if ($estado === 'Sin stock') $where[] = 'stock = 0';
elseif ($estado === 'Por debajo del mínimo') $where[] = 'stock > 0 AND stock < stock_minimo';
elseif ($estado === 'En mínimo') $where[] = 'stock = stock_minimo';
$whereSql = ' WHERE ' . implode(' AND ', $where);
$productos = [];
$resumen = ['productos'=>0,'sin_stock'=>0,'debajo'=>0,'en_minimo'=>0];
if ($error === '') {
    try {
        $summary = $pdo->query("SELECT COUNT(*) AS productos, SUM(stock=0) AS sin_stock, SUM(stock>0 AND stock<stock_minimo) AS debajo, SUM(stock=stock_minimo) AS en_minimo FROM productos WHERE stock<=stock_minimo");
        $resumen = $summary->fetch(PDO::FETCH_ASSOC) ?: $resumen;
        if (($_GET['exportar'] ?? '') === 'csv') {
            $stmt = $pdo->prepare('SELECT codigo,nombre,modelo,tipo,ubicacion,stock,stock_minimo,GREATEST(stock_minimo-stock,0) AS reponer,precio FROM productos' . $whereSql . ' ORDER BY ubicacion,nombre');
            $stmt->execute($params);
            $rows = [];
            while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $estadoFila = (int)$r['stock'] === 0 ? 'Sin stock' : ((int)$r['stock'] < (int)$r['stock_minimo'] ? 'Por debajo del mínimo' : 'En mínimo');
                $rows[] = [$r['codigo'],$r['nombre'],$r['modelo'],$r['tipo'],$r['ubicacion'],$r['stock'],$r['stock_minimo'],$r['reponer'],$estadoFila,$r['precio']];
            }
            CsvExport::stream('alertas_stock_conce_sur_' . date('Ymd_His') . '.csv', ['Código','Producto','Modelo','Tipo','Ubicación','Stock actual','Stock mínimo','Unidades para llegar al mínimo','Estado','Precio'], $rows);
        }
        $stmt = $pdo->prepare('SELECT id,codigo,nombre,modelo,tipo,ubicacion,stock,stock_minimo,precio,GREATEST(stock_minimo-stock,0) AS reponer FROM productos' . $whereSql . ' ORDER BY (stock=0) DESC, ubicacion, nombre LIMIT 500');
        $stmt->execute($params);
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        error_log('CONCE SUR alertas stock: ' . $e->getMessage());
        $error = 'No se pudo consultar el reporte de stock. Verifica la conexión y la estructura de productos.';
    }
}
?>
<!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Alertas de stock | CONCE SUR</title><link rel="stylesheet" href="../css/style.css">
<style>
body{background:#0b1120;color:#e5e7eb}.main{min-width:0}.muted{color:#9ca3af}.panel{background:#111827;border:1px solid #273449;border-radius:12px;padding:18px;margin-bottom:18px}.stats{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:16px}.stat{background:#111827;border:1px solid #273449;border-radius:10px;padding:14px 18px;min-width:140px}.stat span{display:block;color:#9ca3af;font-size:12px}.stat strong{display:block;font-size:22px;margin-top:5px}.filters{display:grid;grid-template-columns:repeat(5,minmax(130px,1fr));gap:10px;align-items:end}.filters label{font-size:12px;color:#cbd5e1;display:flex;flex-direction:column;gap:6px}.filters input,.filters select{background:#0b1120;color:#e5e7eb;border:1px solid #374151;border-radius:7px;padding:10px;min-width:0}.btn{display:inline-block;border:0;border-radius:8px;padding:10px 13px;font-weight:600;text-decoration:none;cursor:pointer;font-size:13px}.primary{background:#2563eb;color:#fff}.secondary{background:#263244;color:#e5e7eb}.notice{padding:12px;border-radius:8px;margin-bottom:14px;background:#7f1d1d;color:#fecaca}.wrap{overflow-x:auto}table{width:100%;border-collapse:collapse;font-size:13px}th,td{text-align:left;padding:10px;border-bottom:1px solid #273449;white-space:nowrap}th{color:#9ca3af}.pill{display:inline-block;border-radius:6px;padding:4px 8px;font-size:12px}.zero{background:#7f1d1d;color:#fecaca}.low{background:#78350f;color:#fde68a}.min{background:#1e3a8a;color:#bfdbfe}.actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:12px}@media(max-width:950px){.filters{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:560px){.filters{grid-template-columns:1fr}}
</style></head><body><div class="app">
<?php auth_render_sidebar('alertas_stock'); ?>
<main class="main"><header class="topbar"><div><h2>Alertas de stock</h2><p class="muted">Productos sin stock o en su nivel mínimo de reposición.</p></div><?php auth_render_user_badge(); ?></header>
<?php if($error!==''): ?><div class="notice"><?=h($error)?></div><?php endif; ?>
<section class="stats"><div class="stat"><span>Productos en alerta</span><strong><?=number_format((int)$resumen['productos'])?></strong></div><div class="stat"><span>Sin stock</span><strong><?=number_format((int)$resumen['sin_stock'])?></strong></div><div class="stat"><span>Por debajo del mínimo</span><strong><?=number_format((int)$resumen['debajo'])?></strong></div><div class="stat"><span>En nivel mínimo</span><strong><?=number_format((int)$resumen['en_minimo'])?></strong></div></section>
<section class="panel"><h3>Filtrar alertas</h3><form method="get" class="filters"><label>Buscar<input type="search" name="buscar" value="<?=h($buscar)?>" placeholder="Código, producto o modelo"></label><label>Ubicación<select name="ubicacion"><option value="">Todas</option><?php foreach($ubicaciones as $u):?><option value="<?=h($u)?>" <?=$ubicacion===$u?'selected':''?>><?=h($u)?></option><?php endforeach;?></select></label><label>Tipo<select name="tipo"><option value="">Todos</option><?php foreach($tipos as $t):?><option value="<?=h($t)?>" <?=$tipo===$t?'selected':''?>><?=h($t)?></option><?php endforeach;?></select></label><label>Estado<select name="estado"><option value="">Todos</option><?php foreach(array_slice($estados,1) as $e):?><option value="<?=h($e)?>" <?=$estado===$e?'selected':''?>><?=h($e)?></option><?php endforeach;?></select></label><div class="actions"><button class="btn primary" type="submit">Aplicar filtros</button><a class="btn secondary" href="alertas_stock.php">Limpiar</a></div></form><div class="actions"><a class="btn secondary" href="?<?=h(http_build_query(['buscar'=>$buscar,'ubicacion'=>$ubicacion,'tipo'=>$tipo,'estado'=>$estado,'exportar'=>'csv']))?>">Exportar alertas CSV</a></div></section>
<section class="panel"><h3>Productos que requieren atención</h3><div class="wrap"><table><thead><tr><th>Código</th><th>Producto</th><th>Tipo</th><th>Ubicación</th><th>Stock actual</th><th>Stock mínimo</th><th>Unidades para llegar al mínimo</th><th>Estado</th></tr></thead><tbody>
<?php if(!$productos): ?><tr><td colspan="8">No hay productos que coincidan con los filtros y el umbral configurado.</td></tr><?php else: foreach($productos as $p): $st=(int)$p['stock'];$mn=(int)$p['stock_minimo'];$label=$st===0?'Sin stock':($st<$mn?'Por debajo del mínimo':'En mínimo');$cls=$st===0?'zero':($st<$mn?'low':'min'); ?><tr><td><?=h($p['codigo'])?></td><td><strong><?=h($p['nombre'])?></strong><br><span class="muted"><?=h($p['modelo'])?></span></td><td><?=h($p['tipo'])?></td><td><?=h($p['ubicacion'])?></td><td><?=number_format($st)?></td><td><?=number_format($mn)?></td><td><?=number_format((int)$p['reponer'])?></td><td><span class="pill <?=$cls?>"><?=h($label)?></span></td></tr><?php endforeach; endif; ?>
</tbody></table></div><p class="muted" style="font-size:12px;margin-top:10px">El reporte es de consulta; no modifica cantidades ni genera órdenes de compra. La cantidad sugerida solo indica unidades necesarias para alcanzar el mínimo configurado.</p></section></main></div></body></html>
