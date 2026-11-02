<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/CsvExport.php';
auth_require_roles(['Administrador', 'Vendedor']);

$buscar = trim((string)($_GET['buscar'] ?? ''));
$where = '';
$params = [];
if ($buscar !== '') {
    $where = " WHERE nombres LIKE ? OR apellidos LIKE ? OR documento LIKE ? OR telefono LIKE ? OR correo LIKE ? ";
    $like = '%' . $buscar . '%';
    $params = [$like, $like, $like, $like, $like];
}

try {
    if (($_GET['exportar'] ?? '') === 'csv') {
        $stmt = $pdo->prepare("SELECT id_cliente,nombres,apellidos,documento,telefono,correo,direccion,tipo_cliente,fecha_registro FROM clientes {$where} ORDER BY apellidos,nombres");
        $stmt->execute($params);
        $rows = [];
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $rows[] = [$r['id_cliente'], $r['nombres'], $r['apellidos'], $r['documento'], $r['telefono'], $r['correo'], $r['direccion'], $r['tipo_cliente'], $r['fecha_registro']];
        }
        CsvExport::stream('clientes_conce_sur_' . date('Ymd_His') . '.csv', ['ID', 'Nombres', 'Apellidos', 'Documento', 'Teléfono', 'Correo', 'Dirección', 'Tipo de cliente', 'Fecha de registro'], $rows);
    }

    $stmt = $pdo->prepare("SELECT id_cliente,nombres,apellidos,documento,telefono,correo,direccion,tipo_cliente,fecha_registro FROM clientes {$where} ORDER BY apellidos,nombres LIMIT 300");
    $stmt->execute($params);
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total = (int)$pdo->query('SELECT COUNT(*) FROM clientes')->fetchColumn();
    $error = '';
} catch (Throwable $e) {
    error_log('CONCE SUR clientes: ' . $e->getMessage());
    $clientes = [];
    $total = 0;
    $error = 'No se pudo consultar la tabla de clientes. Verifica que se haya aplicado la ampliación SQL compatible.';
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Clientes | CONCE SUR</title><link rel="stylesheet" href="../css/style.css">
<style>
body{background:#0b1120;color:#e5e7eb}.main{min-width:0}.muted{color:#9ca3af}.panel{background:#111827;border:1px solid #273449;border-radius:12px;padding:18px;margin-bottom:18px}.toolbar{display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin:14px 0}.toolbar input{flex:1;min-width:220px;background:#0b1120;color:#e5e7eb;border:1px solid #374151;border-radius:7px;padding:11px}.btn{display:inline-block;border:0;border-radius:8px;padding:10px 14px;font-weight:600;cursor:pointer;text-decoration:none;font-size:13px}.primary{background:#2563eb;color:white}.secondary{background:#263244;color:#e5e7eb}.notice{padding:12px;border-radius:8px;margin-bottom:14px;background:#78350f;color:#fde68a}.wrap{overflow-x:auto}table{width:100%;border-collapse:collapse;font-size:13px}th,td{text-align:left;padding:11px;border-bottom:1px solid #273449;vertical-align:top}th{color:#9ca3af}.count{font-size:13px;color:#9ca3af;margin-top:4px}@media(max-width:600px){.toolbar input{min-width:100%}}
</style>
</head>
<body><div class="app">
<?php auth_render_sidebar('clientes'); ?>
<main class="main">
<header class="topbar"><div><h2>Clientes</h2><p class="muted">Consulta y exportación del registro de clientes.</p></div><?php auth_render_user_badge(); ?></header>
<?php if ($error !== ''): ?><div class="notice"><?=h($error)?></div><?php endif; ?>
<section class="panel">
  <h3>Listado de clientes</h3>
  <p class="count">Registros disponibles: <?=number_format($total)?></p>
  <form method="get" class="toolbar">
    <input type="search" name="buscar" value="<?=h($buscar)?>" placeholder="Buscar por nombre, documento, teléfono o correo">
    <button class="btn primary" type="submit">Buscar</button>
    <a class="btn secondary" href="clientes.php">Limpiar</a>
    <a class="btn secondary" href="?<?=h(http_build_query(['buscar'=>$buscar,'exportar'=>'csv']))?>">Exportar CSV</a>
  </form>
  <div class="wrap"><table>
  <thead><tr><th>ID</th><th>Cliente</th><th>Documento</th><th>Teléfono</th><th>Correo</th><th>Tipo</th><th>Registro</th></tr></thead>
  <tbody>
  <?php if (!$clientes): ?><tr><td colspan="7">No hay clientes registrados para mostrar.</td></tr>
  <?php else: foreach ($clientes as $c): ?><tr>
    <td><?=h($c['id_cliente'])?></td>
    <td><strong><?=h(trim($c['nombres'].' '.$c['apellidos']))?></strong><br><span class="muted"><?=h($c['direccion'] ?? '')?></span></td>
    <td><?=h($c['documento'] ?? '—')?></td><td><?=h($c['telefono'] ?? '—')?></td><td><?=h($c['correo'] ?? '—')?></td>
    <td><?=h($c['tipo_cliente'])?></td><td><?=h($c['fecha_registro'])?></td>
  </tr><?php endforeach; endif; ?>
  </tbody></table></div>
  <p class="count">Se muestran hasta 300 registros en pantalla. La exportación respeta el filtro aplicado y descarga todos los resultados coincidentes.</p>
</section>
</main></div></body></html>
