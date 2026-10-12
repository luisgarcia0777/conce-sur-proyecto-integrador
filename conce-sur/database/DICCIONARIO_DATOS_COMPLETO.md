# Diccionario de datos — CONCE SUR

**Base de datos:** `conce_sur`  
**Motor:** MySQL/MariaDB, InnoDB, juego de caracteres `utf8mb4`  
**Versión descrita:** SQL base `conce_sur.sql` + ampliación `actualizacion_conce_sur_compatible.sql`.

Este diccionario describe los campos definidos por ambos scripts SQL. Las tablas de apoyo que se incorporan en la ampliación están identificadas como tales; que existan en el esquema no significa que todos sus campos ya sean utilizados por los formularios PHP.

**Convenciones:** PK = clave primaria; FK = clave foránea; UNIQUE = valor único; NULL = admite valor nulo; AUTO_INCREMENT = numeración automática.

## Tabla `roles`

Catálogo de roles que describe los perfiles previstos para el sistema.

| Campo | Tipo | Clave/índice | Nulo / valor por defecto | Descripción |
|---|---|---|---|---|
| `id_rol` | INT UNSIGNED | PK, AUTO_INCREMENT | No nulo | Identificador único del rol. |
| `nombre_rol` | VARCHAR(50) | UNIQUE | No nulo | Nombre del rol; el script precarga Administrador, Vendedor y Técnico. |
| `descripcion` | VARCHAR(255) | — | NULL | Descripción de las responsabilidades del rol. |

## Tabla `usuarios`

Cuentas previstas para futura autenticación; el código actual no implementa login ni autorización por roles.

| Campo | Tipo | Clave/índice | Nulo / valor por defecto | Descripción |
|---|---|---|---|---|
| `id_usuario` | INT UNSIGNED | PK, AUTO_INCREMENT | No nulo | Identificador único del usuario. |
| `id_rol` | INT UNSIGNED | FK → roles.id_rol | NULL | Rol asignado; al borrar el rol la referencia queda NULL. |
| `usuario` | VARCHAR(50) | UNIQUE | No nulo | Nombre de acceso único. |
| `password_hash` | VARCHAR(255) | — | No nulo | Hash de contraseña; nunca debe almacenar texto plano. |
| `nombre` | VARCHAR(120) | — | No nulo | Nombre visible del usuario. |
| `email` | VARCHAR(120) | — | NULL | Correo electrónico. |
| `estado` | TINYINT(1) | — | NOT NULL DEFAULT 1 | Estado lógico de la cuenta; 1 activo, 0 inactivo. |
| `fecha_registro` | DATETIME | — | NOT NULL DEFAULT CURRENT_TIMESTAMP | Fecha y hora de creación del usuario. |

## Tabla `clientes`

Catálogo normalizado de clientes. El script migra los nombres y teléfonos históricos encontrados en ventas/servicios; los datos no conocidos no se inventan.

| Campo | Tipo | Clave/índice | Nulo / valor por defecto | Descripción |
|---|---|---|---|---|
| `id_cliente` | INT UNSIGNED | PK, AUTO_INCREMENT | No nulo | Identificador único del cliente. |
| `nombres` | VARCHAR(120) | Índice idx_clientes_nombre | No nulo | Nombres o razón principal capturada. |
| `apellidos` | VARCHAR(120) | Índice idx_clientes_nombre | NOT NULL DEFAULT '' | Apellidos; se deja vacío cuando la fuente histórica no los separa. |
| `documento` | VARCHAR(20) | Índice idx_clientes_documento | NULL | DNI/RUC u otro documento si se conoce. |
| `telefono` | VARCHAR(30) | Índice idx_clientes_telefono | NULL | Teléfono de contacto. |
| `correo` | VARCHAR(120) | — | NULL | Correo electrónico. |
| `direccion` | VARCHAR(220) | — | NULL | Dirección del cliente. |
| `tipo_cliente` | ENUM('Particular','Institucional') | — | NOT NULL DEFAULT 'Particular' | Clasificación del cliente. |
| `fecha_registro` | DATETIME | — | NOT NULL DEFAULT CURRENT_TIMESTAMP | Fecha y hora de registro. |

## Tabla `categorias`

Catálogo opcional de categorías de productos; se relaciona con productos.id_categoria.

| Campo | Tipo | Clave/índice | Nulo / valor por defecto | Descripción |
|---|---|---|---|---|
| `id_categoria` | INT UNSIGNED | PK, AUTO_INCREMENT | No nulo | Identificador único de categoría. |
| `nombre` | VARCHAR(80) | UNIQUE | No nulo | Nombre de categoría; se precargan Fotocopiadora, Tóner, Repuesto, Plotter, Equipo de oficina y Otro. |
| `descripcion` | VARCHAR(255) | — | NULL | Descripción de la categoría. |
| `estado` | TINYINT(1) | — | NOT NULL DEFAULT 1 | Estado lógico de categoría; 1 activa, 0 inactiva. |

## Tabla `productos`

Catálogo principal y fuente canónica de stock. Los campos originales se mantienen para la compatibilidad con el PHP actual; la ampliación agrega id_categoria.

| Campo | Tipo | Clave/índice | Nulo / valor por defecto | Descripción |
|---|---|---|---|---|
| `id` | INT UNSIGNED | PK, AUTO_INCREMENT | No nulo | Identificador interno del producto. |
| `codigo` | VARCHAR(40) | UNIQUE uq_productos_codigo | No nulo | Código interno único. |
| `nombre` | VARCHAR(120) | Índice idx_productos_nombre | No nulo | Nombre del producto o equipo. |
| `modelo` | VARCHAR(120) | — | NOT NULL DEFAULT '' | Marca, modelo o variante. |
| `tipo` | ENUM('Fotocopiadora','Tóner','Repuesto','Plotter','Equipo de oficina','Otro') | — | NOT NULL DEFAULT 'Otro' | Tipo de producto; se conserva por compatibilidad con los formularios actuales. |
| `id_categoria` | INT UNSIGNED | FK → categorias.id_categoria | NULL | Categoría normalizada opcional; se asigna automáticamente a partir de tipo cuando encuentra coincidencia. |
| `ubicacion` | ENUM('Local','Almacén') | Índice idx_productos_ubicacion | NOT NULL DEFAULT 'Local' | Ubicación del stock. |
| `stock` | INT UNSIGNED | — | NOT NULL DEFAULT 0 | Cantidad disponible; fuente única/canónica de stock. |
| `stock_minimo` | INT UNSIGNED | — | NOT NULL DEFAULT 1 | Umbral para identificar stock bajo. |
| `precio` | DECIMAL(10,2) | — | NULL | Precio de referencia opcional. |
| `observaciones` | VARCHAR(500) | — | NULL | Notas sobre el producto. |
| `creado_en` | TIMESTAMP | — | NOT NULL DEFAULT CURRENT_TIMESTAMP | Fecha y hora de creación. |
| `actualizado_en` | TIMESTAMP | — | NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Fecha y hora de última modificación. |

## Tabla `ventas`

Cabecera/registro operativo de ventas del formulario actual: una línea de producto por operación. La ampliación agrega referencias opcionales a cliente y usuario.

| Campo | Tipo | Clave/índice | Nulo / valor por defecto | Descripción |
|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | No nulo | Identificador único de venta. |
| `numero_venta` | VARCHAR(30) | UNIQUE uq_ventas_numero | NULL | Código de venta cuando se genera; debe ser único si no es NULL. |
| `fecha` | DATETIME | Índice idx_ventas_fecha | NOT NULL DEFAULT CURRENT_TIMESTAMP | Fecha y hora de operación. |
| `documento_cliente` | VARCHAR(20) | — | NULL | Documento del cliente capturado en la venta. |
| `cliente_nombre` | VARCHAR(160) | Índice idx_ventas_cliente | No nulo | Nombre o razón social guardada como fotografía histórica de la operación. |
| `cliente_telefono` | VARCHAR(30) | — | NULL | Teléfono capturado al vender. |
| `producto_id` | INT UNSIGNED | FK → productos.id | No nulo | Producto vendido; no se permite borrarlo si está referenciado. |
| `producto_codigo` | VARCHAR(40) | — | No nulo | Copia del código del producto al momento de la venta. |
| `producto_nombre` | VARCHAR(120) | — | No nulo | Copia del nombre del producto al momento de la venta. |
| `cantidad` | INT UNSIGNED | — | No nulo | Unidades vendidas. |
| `precio_unitario` | DECIMAL(10,2) | — | No nulo | Precio aplicado a una unidad. |
| `total` | DECIMAL(12,2) | — | No nulo | Importe total registrado para la línea de venta. |
| `forma_pago` | ENUM('Contado','Crédito') | — | NOT NULL DEFAULT 'Contado' | Forma de pago. |
| `contador_copias` | INT UNSIGNED | — | NULL | Contador de copias, cuando aplica al equipo. |
| `observaciones` | VARCHAR(500) | — | NULL | Notas de la operación. |
| `id_cliente` | INT UNSIGNED | FK → clientes.id_cliente | NULL | Referencia normalizada al cliente; opcional para no romper el formulario actual. |
| `id_usuario` | INT UNSIGNED | FK → usuarios.id_usuario | NULL | Usuario que registró la venta; aún no asignado automáticamente por login en el PHP actual. |

## Tabla `detalle_ventas`

Detalle normalizado derivado de las ventas existentes; un trigger sincroniza esta tabla con los INSERT/UPDATE realizados en ventas.

| Campo | Tipo | Clave/índice | Nulo / valor por defecto | Descripción |
|---|---|---|---|---|
| `id_detalle` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | No nulo | Identificador de la fila de detalle. |
| `id_venta` | BIGINT UNSIGNED | FK → ventas.id | No nulo | Venta a la que pertenece; se elimina el detalle si se elimina la venta. |
| `id_producto` | INT UNSIGNED | FK → productos.id | No nulo | Producto de la línea; producto referenciado no puede borrarse. |
| `cantidad` | INT UNSIGNED | — | No nulo | Cantidad de unidades de la línea. |
| `precio_unitario` | DECIMAL(10,2) | — | No nulo | Precio por unidad registrado en la venta. |
| `subtotal` | DECIMAL(12,2) | — | No nulo | Subtotal de la línea, sincronizado con ventas.total en el trigger actual. |

## Tabla `servicios`

Registro de instalaciones, capacitaciones, reparaciones, mantenimiento y soporte técnico. Se conservan campos de cliente en texto para compatibilidad; se agregan referencias opcionales.

| Campo | Tipo | Clave/índice | Nulo / valor por defecto | Descripción |
|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | No nulo | Identificador del servicio. |
| `codigo_servicio` | VARCHAR(30) | UNIQUE uq_servicios_codigo | NULL | Código del servicio si fue generado. |
| `fecha_ingreso` | DATETIME | Índice idx_servicios_fecha | NOT NULL DEFAULT CURRENT_TIMESTAMP | Fecha/hora de ingreso. |
| `cliente_nombre` | VARCHAR(160) | Índice idx_servicios_cliente | No nulo | Nombre del cliente capturado en el registro. |
| `cliente_telefono` | VARCHAR(30) | — | NULL | Teléfono del cliente. |
| `equipo` | VARCHAR(160) | — | No nulo | Equipo atendido. |
| `modelo` | VARCHAR(120) | — | NULL | Modelo del equipo. |
| `tipo_servicio` | ENUM('Instalación','Capacitación','Reparación','Mantenimiento','Soporte técnico','Otro') | — | NOT NULL DEFAULT 'Reparación' | Tipo de trabajo solicitado. |
| `descripcion` | VARCHAR(1000) | — | No nulo | Descripción de la falla, solicitud o trabajo. |
| `estado` | ENUM('Pendiente','En revisión','Finalizado') | Índice idx_servicios_estado | NOT NULL DEFAULT 'Pendiente' | Estado de servicio usado por el código actual. |
| `diagnostico` | VARCHAR(1000) | — | NULL | Diagnóstico técnico. |
| `solucion` | VARCHAR(1000) | — | NULL | Solución aplicada o prevista. |
| `costo` | DECIMAL(10,2) | — | NULL | Costo del servicio si se registra. |
| `fecha_cierre` | DATETIME | — | NULL | Fecha/hora de cierre. |
| `id_cliente` | INT UNSIGNED | FK → clientes.id_cliente | NULL | Referencia normalizada al cliente, cuando se logra asociar. |
| `id_usuario` | INT UNSIGNED | FK → usuarios.id_usuario | NULL | Usuario responsable; opcional en el PHP actual. |
| `id_estado` | INT UNSIGNED | FK → estados_servicio.id_estado | NULL | Estado normalizado opcional; se sincroniza a partir del ENUM estado. |

## Tabla `estados_servicio`

Catálogo normalizado de estados de servicio relacionado con servicios.id_estado.

| Campo | Tipo | Clave/índice | Nulo / valor por defecto | Descripción |
|---|---|---|---|---|
| `id_estado` | INT UNSIGNED | PK, AUTO_INCREMENT | No nulo | Identificador del estado. |
| `nombre_estado` | VARCHAR(50) | UNIQUE | No nulo | Nombre único; se precargan Pendiente, En revisión, Finalizado y Cancelado. |
| `descripcion` | VARCHAR(255) | — | NULL | Descripción del estado. |

## Tabla `detalle_servicios`

Detalle opcional de productos/repuestos utilizados en cada servicio. Está preparado en la base, pero el formulario actual aún no lo llena.

| Campo | Tipo | Clave/índice | Nulo / valor por defecto | Descripción |
|---|---|---|---|---|
| `id_detalle_servicio` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | No nulo | Identificador de detalle. |
| `id_servicio` | BIGINT UNSIGNED | FK → servicios.id | No nulo | Servicio al que corresponde; se elimina el detalle al eliminar el servicio. |
| `id_producto` | INT UNSIGNED | FK → productos.id | No nulo | Producto o repuesto utilizado; no se puede borrar mientras esté referenciado. |
| `cantidad` | INT UNSIGNED | — | NOT NULL DEFAULT 1 | Cantidad utilizada. |
| `observaciones` | VARCHAR(500) | — | NULL | Observaciones sobre el material o repuesto. |

## Vista `v_inventario_actual`

Vista de consulta basada en `productos`; no almacena ni duplica cantidades.

| Campo de salida | Tipo | Origen / descripción |
|---|---|---|
| `id_producto` | INT UNSIGNED | Alias de productos.id. |
| `codigo` | VARCHAR(40) | Código interno. |
| `nombre` | VARCHAR(120) | Nombre del producto. |
| `modelo` | VARCHAR(120) | Modelo. |
| `tipo` | ENUM | Tipo de producto. |
| `ubicacion` | ENUM | Local o Almacén. |
| `stock_actual` | INT UNSIGNED | Alias de productos.stock; no duplica el dato. |
| `stock_minimo` | INT UNSIGNED | Umbral de stock bajo. |
| `precio` | DECIMAL(10,2) | Precio de referencia. |
| `fecha_actualizacion` | TIMESTAMP | Alias de productos.actualizado_en. |

## Relaciones principales

- `roles` 1:N `usuarios` mediante `usuarios.id_rol`.
- `categorias` 1:N `productos` mediante `productos.id_categoria` (opcional).
- `productos` 1:N `ventas` mediante `ventas.producto_id`.
- `clientes` 1:N `ventas` mediante `ventas.id_cliente` (opcional).
- `usuarios` 1:N `ventas` mediante `ventas.id_usuario` (opcional).
- `ventas` 1:N `detalle_ventas` mediante `detalle_ventas.id_venta`.
- `productos` 1:N `detalle_ventas` mediante `detalle_ventas.id_producto`.
- `clientes` 1:N `servicios` mediante `servicios.id_cliente` (opcional).
- `usuarios` 1:N `servicios` mediante `servicios.id_usuario` (opcional).
- `estados_servicio` 1:N `servicios` mediante `servicios.id_estado` (opcional).
- `servicios` 1:N `detalle_servicios` mediante `detalle_servicios.id_servicio`.
- `productos` 1:N `detalle_servicios` mediante `detalle_servicios.id_producto`.

## Triggers y reglas implementadas por la ampliación

- `trg_conce_sur_ventas_detalle_ai`: crea el detalle al insertar una venta.
- `trg_conce_sur_ventas_detalle_au`: actualiza el detalle cuando cambia la venta.
- `trg_conce_sur_productos_categoria_bi` y `..._bu`: asignan categoría a partir de `productos.tipo` cuando hay coincidencia.
- `trg_conce_sur_servicios_estado_bi` y `..._bu`: sincronizan `servicios.id_estado` con el campo `servicios.estado`.

## Alcance y limitaciones

- El stock canónico permanece en `productos.stock` y la ubicación en `productos.ubicacion`; la vista `v_inventario_actual` los expone para consulta.
- El formulario PHP actual registra una sola línea de producto por venta en `ventas`; `detalle_ventas` normaliza esa línea y se sincroniza con triggers.
- `clientes`, `roles`, `usuarios`, `categorias` y `estados_servicio` se agregan para normalización y evolución. No debe afirmarse que todos están plenamente integrados a la interfaz.
- La tabla `usuarios` no implica que ya exista autenticación o control de acceso en el PHP. `password_hash` debe guardar únicamente hashes.
- `detalle_servicios` está preparado para materiales/repuestos, pero el formulario actual no registra sus filas.
- La descripción de la estructura se basa en los scripts entregados; se debe confirmar que la ampliación se importó correctamente en phpMyAdmin antes de afirmar que el esquema completo está instalado.

Fuente: Elaboración propia a partir de `conce_sur.sql` y `actualizacion_conce_sur_compatible.sql`.