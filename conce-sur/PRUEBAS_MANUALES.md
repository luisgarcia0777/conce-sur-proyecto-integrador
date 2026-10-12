# Matriz de pruebas manuales — CONCE SUR

Ejecutar en WAMP después de importar `database/conce_sur.sql` y `database/actualizacion_conce_sur_compatible.sql`. Completar fecha, resultado y adjuntar capturas únicamente tras ejecutar cada prueba.

| ID | Prueba | Resultado esperado | Estado / evidencia |
|---|---|---|---|
| AUTH-01 | Configurar primer administrador en `setup_admin.php` con tabla usuarios vacía | Se crea una cuenta admin; password_hash queda almacenado, no la contraseña original | Pendiente en WAMP |
| AUTH-02 | Abrir Dashboard sin iniciar sesión | Redirección a `login.php` | Pendiente en WAMP |
| AUTH-03 | Iniciar sesión con contraseña correcta | Se crea sesión y se abre Dashboard | Pendiente en WAMP |
| AUTH-04 | Iniciar sesión con contraseña incorrecta | Mensaje genérico; no se revela si usuario existe | Pendiente en WAMP |
| AUTH-05 | Hacer cinco intentos fallidos en la misma sesión | Bloqueo temporal de un minuto | Pendiente en WAMP |
| AUTH-06 | Administrador crea cuenta Vendedor/Técnico | Usuario aparece en lista y hash se guarda en DB | Pendiente en WAMP |
| AUTH-07 | Vendedor intenta abrir `pages/usuarios.php` directamente | HTTP 403 | Pendiente en WAMP |
| AUTH-08 | Vendedor intenta enviar POST para crear/eliminar producto | Solicitud rechazada; inventario no cambia | Pendiente en WAMP |
| AUTH-09 | Técnico intenta abrir `pages/ventas.php` directamente | HTTP 403 | Pendiente en WAMP |
| AUTH-10 | Usuario cambia su contraseña desde Mi cuenta | Requiere contraseña actual y mínimo 12 caracteres | Pendiente en WAMP |
| AUTH-11 | Usuario desactivado intenta continuar con una sesión existente | Sesión invalida y vuelve al login | Pendiente en WAMP |
| AUTH-12 | Cerrar sesión | La sesión queda destruida; volver a página privada redirige al login | Pendiente en WAMP |
| DB-01 | Registrar producto como administrador y recargar | Producto persiste en `productos` | Pendiente en WAMP |
| DB-02 | Registrar venta de 3 unidades | Venta queda guardada y stock disminuye en 3 | Pendiente en WAMP |
| DB-03 | Intentar vender más de las unidades disponibles | Operación rechazada y ninguna fila nueva de venta | Pendiente en WAMP |
| DB-04 | Crear servicio como administrador/técnico y cambiar estado | Registro persiste y estado se actualiza | Pendiente en WAMP |
| SEC-01 | Enviar formulario de cambio de datos sin token CSRF correcto | Solicitud rechazada | Pendiente en WAMP |
| SEC-02 | Introducir texto con HTML en campos de cliente/observaciones | Salida HTML escapada al mostrar datos | Pendiente en WAMP |
| OPS-01 | Exportar base en phpMyAdmin | Archivo SQL generado | Pendiente en WAMP |
| OPS-02 | Restaurar copia en base de pruebas | Se recuperan tablas y registros esperados | Pendiente en WAMP |

## Límites conocidos
- El primer administrador se crea una sola vez mediante un asistente que deja de funcionar cuando existe cualquier usuario. Si se pierde la contraseña del único administrador, debe restablecerse de forma controlada desde la base local o usando otro administrador.
- El bloqueo por intentos de login es una limitación básica por sesión, no un control distribuido por IP/cuenta para producción.
- No existe recuperación de contraseña por correo ni copia de seguridad automática.
- Los controles deben probarse en el entorno WAMP del estudiante; el análisis estático/sintaxis no sustituye dichas pruebas.
