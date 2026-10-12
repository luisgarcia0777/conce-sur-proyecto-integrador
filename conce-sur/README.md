# CONCE SUR — Sistema web (prototipo local)

Aplicación PHP 8.x + PDO + MySQL/MariaDB diseñada para ejecución local en WAMP.

## Instalación (primera vez)
1. Copia `conce-sur` a `C:\wamp64\www\conce-sur`.
2. Inicia Apache y MySQL/MariaDB en WAMP.
3. Abre phpMyAdmin e importa `database/conce_sur.sql`.
4. Importa `database/actualizacion_conce_sur_compatible.sql`.
5. Copia `config/database.example.php` como `config/database.local.php` si necesitas ajustar las credenciales. Ese archivo está excluido por `.gitignore`.
6. Abre `http://localhost/conce-sur/setup_admin.php` desde el mismo equipo y crea un administrador. Debe tener contraseña de 12 caracteres o más. No existen credenciales por defecto.
7. Ingresa en `http://localhost/conce-sur/login.php`.

## Si la base de datos ya tiene datos
No vuelvas a importar el SQL base sobre una instalación existente sin revisar el contenido. Exporta primero un respaldo desde phpMyAdmin y aplica solamente el script de ampliación, después de verificar que corresponde a la estructura existente. El script no elimina las tablas operativas, pero modifica metadatos y agrega relaciones; siempre respalda primero.

## Roles
- **Administrador:** acceso al panel, inventario, ventas, servicios y usuarios.
- **Vendedor:** panel, consulta de inventario y registro de ventas; no registra ni elimina productos ni administra cuentas.
- **Técnico:** panel y gestión de servicios.

La página **Usuarios** permite crear cuentas, asignar roles, activar/desactivar usuarios y restablecer la contraseña de otra cuenta. **Mi cuenta** permite al usuario cambiar su propia contraseña comprobando la actual. El asistente del primer administrador funciona solo cuando no hay usuarios y solo desde `localhost`.

## Seguridad incluida en el código
Sesiones con cookie HttpOnly/SameSite, renovación del ID al iniciar sesión, inactividad máxima de 30 minutos, CSRF en formularios sensibles, `password_hash`/`password_verify`, consultas PDO preparadas, escape HTML, validaciones y bloqueo temporal de intentos de acceso fallidos por sesión.

## Pruebas
Sigue `PRUEBAS_MANUALES.md`; registra fecha, resultados y capturas reales. La revisión local de sintaxis PHP no sustituye las pruebas de integración contra tu propia instancia de MySQL/MariaDB.

## Limitaciones conocidas
- Esta implementación es PHP + MySQL/MariaDB; no es la arquitectura objetivo Laravel/PostgreSQL.
- No hay recuperación automática por correo, registro de auditoría ni respaldos automáticos.
- Clientes e historial normalizados no cuentan aún con una interfaz PHP completa.
- No se incluye integración de comprobantes electrónicos SUNAT ni despliegue público.
