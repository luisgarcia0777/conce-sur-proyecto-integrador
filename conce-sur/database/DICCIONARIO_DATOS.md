# Diccionario de datos — CONCE SUR

El esquema de esta versión local usa MySQL/MariaDB e InnoDB. Importar primero `conce_sur.sql`.

## productos

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | INT UNSIGNED PK | Identificador interno autoincremental. |
| `codigo` | VARCHAR(40), único | Código interno del producto o equipo. |
| `nombre` | VARCHAR(120) | Nombre visible. |
| `modelo` | VARCHAR(120) | Marca/modelo/variante. |
| `tipo` | ENUM | Fotocopiadora, Tóner, Repuesto, Plotter, Equipo de oficina u Otro. |
| `ubicacion` | ENUM | Local o Almacén. |
| `stock` | INT UNSIGNED | Unidades disponibles registradas. |
| `stock_minimo` | INT UNSIGNED | Umbral para alerta de stock. |
| `precio` | DECIMAL(10,2), NULL | Precio de referencia opcional. |
| `observaciones` | VARCHAR(500), NULL | Notas del producto. |
| `creado_en`, `actualizado_en` | TIMESTAMP | Fechas automáticas de control. |

## ventas

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | BIGINT UNSIGNED PK | Identificador interno. |
| `numero_venta` | VARCHAR(30), único | Código generado `VTA-fecha-id`. |
| `fecha` | DATETIME | Fecha y hora de registro. |
| `documento_cliente`, `cliente_telefono` | VARCHAR | Datos del cliente capturados al vender, opcionales. |
| `cliente_nombre` | VARCHAR(160) | Nombre/razón social registrada en la operación. |
| `producto_id` | INT UNSIGNED FK | Referencia a `productos.id`; impide borrar producto con ventas. |
| `producto_codigo`, `producto_nombre` | VARCHAR | Copia descriptiva del producto en el momento de venta. |
| `cantidad` | INT UNSIGNED | Unidades vendidas. |
| `precio_unitario`, `total` | DECIMAL | Precio de unidad e importe total de la línea. |
| `forma_pago` | ENUM | Contado o Crédito. |
| `contador_copias` | INT UNSIGNED, NULL | Contador del equipo si aplica. |
| `observaciones` | VARCHAR(500), NULL | Notas de la operación. |

El formulario actual registra un solo producto por operación. La venta y el descuento de stock se realizan dentro de una transacción.

## servicios

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | BIGINT UNSIGNED PK | Identificador interno. |
| `codigo_servicio` | VARCHAR(30), único | Código generado `SRV-fecha-id`. |
| `fecha_ingreso` | DATETIME | Fecha/hora de registro. |
| `cliente_nombre`, `cliente_telefono` | VARCHAR | Datos de contacto capturados para el servicio. |
| `equipo`, `modelo` | VARCHAR | Equipo atendido y modelo o serie. |
| `tipo_servicio` | ENUM | Instalación, Capacitación, Reparación, Mantenimiento, Soporte técnico u Otro. |
| `descripcion` | VARCHAR(1000) | Solicitud o trabajo descrito por el usuario. |
| `estado` | ENUM | Pendiente, En revisión o Finalizado. |
| `diagnostico`, `solucion` | VARCHAR(1000), NULL | Anotaciones técnicas; `solucion` está previsto para evolución del módulo. |
| `costo` | DECIMAL(10,2), NULL | Costo estimado opcional. |
| `fecha_cierre` | DATETIME, NULL | Fecha de finalización si el estado es Finalizado. |

## Consideraciones

- Los clientes aún se guardan como datos de cada venta/servicio; no hay catálogo único de clientes en esta versión.
- No se almacenan fotos adjuntas ni documentos electrónicos tributarios.
- Los productos con prefijo `DEMO-` son demostrativos, no datos de inventario verificados.
