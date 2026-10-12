# Pruebas manuales — CONCE SUR

> Este documento es una guía de ejecución. No está marcado como aprobado porque las pruebas deben hacerse en el WAMP del usuario con su propia base de datos.

| ID | Módulo | Prueba | Resultado esperado | Evidencia a capturar tras ejecutar |
|---|---|---|---|---|
| INV-01 | Inventario | Registrar producto de prueba con código único y stock 5. | Mensaje de confirmación y producto visible en la tabla. | Página de inventario y fila en phpMyAdmin. |
| INV-02 | Inventario | Recargar la pantalla y buscar el código exacto. | El producto permanece y aparece en el resultado. | Resultado de búsqueda. |
| INV-03 | Inventario | Filtrar por ubicación. | Solo se muestran los productos de esa ubicación. | Filtro aplicado. |
| VEN-01 | Ventas | Vender 2 unidades del producto con stock 5. | Venta registrada y stock actualizado a 3. | Venta en historial, fila en `ventas` y stock en `productos`. |
| VEN-02 | Ventas | Intentar vender 4 unidades si quedan 3. | Rechazo por stock insuficiente y ninguna venta parcial. | Mensaje de validación y verificación de BD. |
| VEN-03 | Ventas | Registrar venta con campos obligatorios vacíos. | El navegador/servidor impide completar el registro. | Pantalla de validación. |
| SRV-01 | Servicios | Registrar un servicio con cliente, equipo y descripción. | Genera código y aparece en historial como Pendiente. | Formulario e historial. |
| SRV-02 | Servicios | Cambiar estado a En revisión y luego Finalizado. | Se actualiza el estado y se registra la fecha de cierre al finalizar. | Historial antes/después y fila en `servicios`. |
| DASH-01 | Dashboard | Cambiar inventario/registrar venta/servicio y volver al panel. | Indicadores y tablas muestran los datos guardados. | Dashboard después de las operaciones. |

## Procedimiento recomendado

1. Iniciar Apache y MySQL/MariaDB en WAMP.
2. Importar `database/conce_sur.sql` en phpMyAdmin.
3. Revisar las credenciales en `config/database.php`.
4. Ejecutar cada caso de la tabla y anotar **Aprobado / Fallido**, fecha y observación.
5. Usar datos ficticios de prueba. No reemplazar por capturas generadas ni afirmar que se ejecutó una prueba si no se ejecutó.
