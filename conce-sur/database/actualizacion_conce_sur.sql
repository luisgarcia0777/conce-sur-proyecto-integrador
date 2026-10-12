-- =====================================================================
-- CONCE SUR | Ampliacion compatible de la base de datos existente
-- Motor: MySQL/MariaDB (WAMP / phpMyAdmin)
--
-- IMPORTANTE:
-- 1) Hacer primero una exportacion/backup de la base conce_sur.
-- 2) Ejecutar este script DESPUES del SQL original database/conce_sur.sql.
-- 3) No elimina tablas ni filas existentes y no reinicia AUTO_INCREMENT.
-- 4) Conserva los campos que usa el PHP actual en productos, ventas y servicios.
-- 5) El stock canonico sigue en productos.stock/ubicacion; se expone tambien
--    mediante la vista v_inventario_actual para evitar duplicar stock.
--
-- Tablas principales esperadas en el esquema actual: productos, ventas, servicios.
-- =====================================================================

SET NAMES utf8mb4;
CREATE DATABASE IF NOT EXISTS conce_sur CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE conce_sur;

-- 1. Catalogo de roles (sin crear usuarios ni contrasenas por defecto).
CREATE TABLE IF NOT EXISTS roles (
  id_rol INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre_rol VARCHAR(50) NOT NULL,
  descripcion VARCHAR(255) NULL,
  PRIMARY KEY (id_rol),
  UNIQUE KEY uq_roles_nombre (nombre_rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO roles (nombre_rol, descripcion) VALUES
 ('Administrador', 'Gestion general del sistema'),
 ('Vendedor', 'Registro y consulta de ventas'),
 ('Tecnico', 'Gestion de servicios tecnicos');

-- 2. Usuarios: el sistema actual no implementa login; esta tabla queda lista
--    para una futura autenticacion. password_hash debe contener un hash, nunca
--    una contrasena en texto plano.
CREATE TABLE IF NOT EXISTS usuarios (
  id_usuario INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_rol INT UNSIGNED NULL,
  usuario VARCHAR(50) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  nombre VARCHAR(120) NOT NULL,
  email VARCHAR(120) NULL,
  estado TINYINT(1) NOT NULL DEFAULT 1,
  fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_usuario),
  UNIQUE KEY uq_usuarios_usuario (usuario),
  KEY idx_usuarios_rol (id_rol),
  CONSTRAINT fk_conce_usuarios_rol FOREIGN KEY (id_rol) REFERENCES roles(id_rol)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Clientes. Los registros historicos de ventas/servicios se migran abajo
--    conservando nombre y telefono; no se inventan DNI ni correos.
CREATE TABLE IF NOT EXISTS clientes (
  id_cliente INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombres VARCHAR(120) NOT NULL,
  apellidos VARCHAR(120) NOT NULL DEFAULT '',
  documento VARCHAR(20) NULL,
  telefono VARCHAR(30) NULL,
  correo VARCHAR(120) NULL,
  direccion VARCHAR(220) NULL,
  tipo_cliente ENUM('Particular','Institucional') NOT NULL DEFAULT 'Particular',
  fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_cliente),
  KEY idx_clientes_documento (documento),
  KEY idx_clientes_nombre (nombres, apellidos),
  KEY idx_clientes_telefono (telefono)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Categorias normalizadas opcionales, compatibles con productos.tipo.
CREATE TABLE IF NOT EXISTS categorias (
  id_categoria INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(80) NOT NULL,
  descripcion VARCHAR(255) NULL,
  estado TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id_categoria),
  UNIQUE KEY uq_categorias_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO categorias (nombre, descripcion) VALUES
 ('Fotocopiadora', 'Fotocopiadoras y equipos multifuncion'),
 ('Tóner', 'Toner y consumibles'),
 ('Repuesto', 'Repuestos y piezas'),
 ('Plotter', 'Plotters y equipos de gran formato'),
 ('Equipo de oficina', 'Equipos complementarios de oficina'),
 ('Otro', 'Otros productos');

-- Agregar id_categoria solo si falta. El campo queda nullable para que los INSERT
-- del PHP actual sigan funcionando sin modificaciones.
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS
 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'productos' AND COLUMN_NAME = 'id_categoria');
SET @sql := IF(@col_exists = 0,
 'ALTER TABLE productos ADD COLUMN id_categoria INT UNSIGNED NULL AFTER tipo',
 'SELECT ''productos.id_categoria ya existe'' AS nota');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk_exists := (SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS
 WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'productos' AND CONSTRAINT_NAME = 'fk_conce_productos_categoria');
SET @sql := IF(@fk_exists = 0,
 'ALTER TABLE productos ADD CONSTRAINT fk_conce_productos_categoria FOREIGN KEY (id_categoria) REFERENCES categorias(id_categoria) ON UPDATE CASCADE ON DELETE SET NULL',
 'SELECT ''FK de categoria ya existe'' AS nota');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

UPDATE productos p
JOIN categorias c ON c.nombre = p.tipo
SET p.id_categoria = c.id_categoria
WHERE p.id_categoria IS NULL;

-- 5. Estados de servicio normalizados; servicios.estado se conserva para el PHP actual.
CREATE TABLE IF NOT EXISTS estados_servicio (
  id_estado INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre_estado VARCHAR(50) NOT NULL,
  descripcion VARCHAR(255) NULL,
  PRIMARY KEY (id_estado),
  UNIQUE KEY uq_estados_servicio_nombre (nombre_estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO estados_servicio (nombre_estado, descripcion) VALUES
 ('Pendiente', 'Servicio registrado, pendiente de atencion'),
 ('En revisión', 'Servicio en proceso de revision'),
 ('Finalizado', 'Servicio atendido y cerrado'),
 ('Cancelado', 'Servicio cancelado');

-- 6. Extensiones opcionales en las tablas existentes. Son NULL para no romper
--    los formularios actuales que aun no envian estos identificadores.
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS
 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ventas' AND COLUMN_NAME = 'id_cliente');
SET @sql := IF(@col_exists = 0, 'ALTER TABLE ventas ADD COLUMN id_cliente INT UNSIGNED NULL', 'SELECT ''ventas.id_cliente ya existe'' AS nota');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS
 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ventas' AND COLUMN_NAME = 'id_usuario');
SET @sql := IF(@col_exists = 0, 'ALTER TABLE ventas ADD COLUMN id_usuario INT UNSIGNED NULL', 'SELECT ''ventas.id_usuario ya existe'' AS nota');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS
 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'servicios' AND COLUMN_NAME = 'id_cliente');
SET @sql := IF(@col_exists = 0, 'ALTER TABLE servicios ADD COLUMN id_cliente INT UNSIGNED NULL', 'SELECT ''servicios.id_cliente ya existe'' AS nota');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS
 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'servicios' AND COLUMN_NAME = 'id_usuario');
SET @sql := IF(@col_exists = 0, 'ALTER TABLE servicios ADD COLUMN id_usuario INT UNSIGNED NULL', 'SELECT ''servicios.id_usuario ya existe'' AS nota');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS
 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'servicios' AND COLUMN_NAME = 'id_estado');
SET @sql := IF(@col_exists = 0, 'ALTER TABLE servicios ADD COLUMN id_estado INT UNSIGNED NULL', 'SELECT ''servicios.id_estado ya existe'' AS nota');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 7. Foreign keys opcionales, agregadas solo una vez.
SET @fk_exists := (SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='ventas' AND CONSTRAINT_NAME='fk_conce_ventas_cliente');
SET @sql := IF(@fk_exists=0, 'ALTER TABLE ventas ADD CONSTRAINT fk_conce_ventas_cliente FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente) ON UPDATE CASCADE ON DELETE SET NULL', 'SELECT ''FK ventas-cliente ya existe'' AS nota');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk_exists := (SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='ventas' AND CONSTRAINT_NAME='fk_conce_ventas_usuario');
SET @sql := IF(@fk_exists=0, 'ALTER TABLE ventas ADD CONSTRAINT fk_conce_ventas_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON UPDATE CASCADE ON DELETE SET NULL', 'SELECT ''FK ventas-usuario ya existe'' AS nota');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk_exists := (SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='servicios' AND CONSTRAINT_NAME='fk_conce_servicios_cliente');
SET @sql := IF(@fk_exists=0, 'ALTER TABLE servicios ADD CONSTRAINT fk_conce_servicios_cliente FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente) ON UPDATE CASCADE ON DELETE SET NULL', 'SELECT ''FK servicios-cliente ya existe'' AS nota');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk_exists := (SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='servicios' AND CONSTRAINT_NAME='fk_conce_servicios_usuario');
SET @sql := IF(@fk_exists=0, 'ALTER TABLE servicios ADD CONSTRAINT fk_conce_servicios_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON UPDATE CASCADE ON DELETE SET NULL', 'SELECT ''FK servicios-usuario ya existe'' AS nota');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk_exists := (SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='servicios' AND CONSTRAINT_NAME='fk_conce_servicios_estado');
SET @sql := IF(@fk_exists=0, 'ALTER TABLE servicios ADD CONSTRAINT fk_conce_servicios_estado FOREIGN KEY (id_estado) REFERENCES estados_servicio(id_estado) ON UPDATE CASCADE ON DELETE SET NULL', 'SELECT ''FK servicios-estado ya existe'' AS nota');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 8. Pasar clientes historicos a la tabla normalizada sin borrar su copia
--    original de ventas/servicios. Los campos no conocidos quedan NULL.
INSERT INTO clientes (nombres, apellidos, documento, telefono, tipo_cliente)
SELECT TRIM(v.cliente_nombre), '', NULLIF(TRIM(v.documento_cliente), ''), NULLIF(TRIM(v.cliente_telefono), ''), 'Particular'
FROM ventas v
WHERE TRIM(COALESCE(v.cliente_nombre,'')) <> ''
  AND NOT EXISTS (
    SELECT 1 FROM clientes c
    WHERE TRIM(c.nombres)=TRIM(v.cliente_nombre)
      AND COALESCE(NULLIF(TRIM(c.telefono),''),'')=COALESCE(NULLIF(TRIM(v.cliente_telefono),''),'')
      AND COALESCE(NULLIF(TRIM(c.documento),''),'')=COALESCE(NULLIF(TRIM(v.documento_cliente),''),'')
  );

INSERT INTO clientes (nombres, apellidos, telefono, tipo_cliente)
SELECT TRIM(s.cliente_nombre), '', NULLIF(TRIM(s.cliente_telefono), ''), 'Particular'
FROM servicios s
WHERE TRIM(COALESCE(s.cliente_nombre,'')) <> ''
  AND NOT EXISTS (
    SELECT 1 FROM clientes c
    WHERE TRIM(c.nombres)=TRIM(s.cliente_nombre)
      AND COALESCE(NULLIF(TRIM(c.telefono),''),'')=COALESCE(NULLIF(TRIM(s.cliente_telefono),''),'')
  );

UPDATE ventas v
SET v.id_cliente = (
  SELECT MIN(c.id_cliente) FROM clientes c
  WHERE TRIM(c.nombres)=TRIM(v.cliente_nombre)
    AND COALESCE(NULLIF(TRIM(c.telefono),''),'')=COALESCE(NULLIF(TRIM(v.cliente_telefono),''),'')
    AND (NULLIF(TRIM(v.documento_cliente),'') IS NULL OR COALESCE(NULLIF(TRIM(c.documento),''),'')=COALESCE(NULLIF(TRIM(v.documento_cliente),''),''))
)
WHERE v.id_cliente IS NULL AND TRIM(COALESCE(v.cliente_nombre,'')) <> '';

UPDATE servicios s
SET s.id_cliente = (
  SELECT MIN(c.id_cliente) FROM clientes c
  WHERE TRIM(c.nombres)=TRIM(s.cliente_nombre)
    AND COALESCE(NULLIF(TRIM(c.telefono),''),'')=COALESCE(NULLIF(TRIM(s.cliente_telefono),''),'')
)
WHERE s.id_cliente IS NULL AND TRIM(COALESCE(s.cliente_nombre,'')) <> '';

UPDATE servicios s
JOIN estados_servicio e ON e.nombre_estado = s.estado
SET s.id_estado = e.id_estado
WHERE s.id_estado IS NULL;

-- 9. Detalle de venta. El PHP existente guarda una linea por venta en `ventas`;
--    esta tabla espejo normaliza el detalle sin cambiar dicho formulario.
CREATE TABLE IF NOT EXISTS detalle_ventas (
  id_detalle BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_venta BIGINT UNSIGNED NOT NULL,
  id_producto INT UNSIGNED NOT NULL,
  cantidad INT UNSIGNED NOT NULL,
  precio_unitario DECIMAL(10,2) NOT NULL,
  subtotal DECIMAL(12,2) NOT NULL,
  PRIMARY KEY (id_detalle),
  KEY idx_detalle_ventas_venta (id_venta),
  KEY idx_detalle_ventas_producto (id_producto),
  CONSTRAINT fk_conce_detalle_venta_venta FOREIGN KEY (id_venta) REFERENCES ventas(id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_conce_detalle_venta_producto FOREIGN KEY (id_producto) REFERENCES productos(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Copiar operaciones ya existentes solo si todavia no tienen detalle asociado.
INSERT INTO detalle_ventas (id_venta, id_producto, cantidad, precio_unitario, subtotal)
SELECT v.id, v.producto_id, v.cantidad, v.precio_unitario, v.total
FROM ventas v
WHERE NOT EXISTS (SELECT 1 FROM detalle_ventas d WHERE d.id_venta=v.id);

-- Mantener el detalle actualizado con el PHP actual, que inserta en `ventas`.
-- Se recrean unicamente los triggers con estos nombres reservados para este script.
DROP TRIGGER IF EXISTS trg_conce_sur_ventas_detalle_ai;
CREATE TRIGGER trg_conce_sur_ventas_detalle_ai
AFTER INSERT ON ventas FOR EACH ROW
INSERT INTO detalle_ventas (id_venta, id_producto, cantidad, precio_unitario, subtotal)
VALUES (NEW.id, NEW.producto_id, NEW.cantidad, NEW.precio_unitario, NEW.total);

DROP TRIGGER IF EXISTS trg_conce_sur_ventas_detalle_au;
CREATE TRIGGER trg_conce_sur_ventas_detalle_au
AFTER UPDATE ON ventas FOR EACH ROW
UPDATE detalle_ventas
SET id_producto=NEW.producto_id, cantidad=NEW.cantidad,
    precio_unitario=NEW.precio_unitario, subtotal=NEW.total
WHERE id_venta=NEW.id;

-- 10. Materiales/repuestos usados durante un servicio. Es opcional; los formularios
--     actuales no agregan detalle de repuestos todavia.
CREATE TABLE IF NOT EXISTS detalle_servicios (
  id_detalle_servicio BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_servicio BIGINT UNSIGNED NOT NULL,
  id_producto INT UNSIGNED NOT NULL,
  cantidad INT UNSIGNED NOT NULL DEFAULT 1,
  observaciones VARCHAR(500) NULL,
  PRIMARY KEY (id_detalle_servicio),
  KEY idx_detalle_servicios_servicio (id_servicio),
  KEY idx_detalle_servicios_producto (id_producto),
  CONSTRAINT fk_conce_detalle_servicio_servicio FOREIGN KEY (id_servicio) REFERENCES servicios(id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_conce_detalle_servicio_producto FOREIGN KEY (id_producto) REFERENCES productos(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Vista de inventario basada en el stock real de productos. No duplica stock.
CREATE OR REPLACE VIEW v_inventario_actual AS
SELECT id AS id_producto, codigo, nombre, modelo, tipo, ubicacion,
       stock AS stock_actual, stock_minimo, precio,
       actualizado_en AS fecha_actualizacion
FROM productos;

-- 12. Sincronizar categorias cuando el PHP actual inserte/edite un producto por tipo.
DROP TRIGGER IF EXISTS trg_conce_sur_productos_categoria_bi;
CREATE TRIGGER trg_conce_sur_productos_categoria_bi
BEFORE INSERT ON productos FOR EACH ROW
SET NEW.id_categoria = COALESCE(NEW.id_categoria,
  (SELECT c.id_categoria FROM categorias c WHERE c.nombre = NEW.tipo LIMIT 1));

DROP TRIGGER IF EXISTS trg_conce_sur_productos_categoria_bu;
CREATE TRIGGER trg_conce_sur_productos_categoria_bu
BEFORE UPDATE ON productos FOR EACH ROW
SET NEW.id_categoria = COALESCE(NEW.id_categoria,
  (SELECT c.id_categoria FROM categorias c WHERE c.nombre = NEW.tipo LIMIT 1));

-- 13. Sincronizar id_estado a partir del campo ENUM `servicios.estado` existente.
DROP TRIGGER IF EXISTS trg_conce_sur_servicios_estado_bi;
CREATE TRIGGER trg_conce_sur_servicios_estado_bi
BEFORE INSERT ON servicios FOR EACH ROW
SET NEW.id_estado = COALESCE(NEW.id_estado,
  (SELECT e.id_estado FROM estados_servicio e WHERE e.nombre_estado = NEW.estado LIMIT 1));

DROP TRIGGER IF EXISTS trg_conce_sur_servicios_estado_bu;
CREATE TRIGGER trg_conce_sur_servicios_estado_bu
BEFORE UPDATE ON servicios FOR EACH ROW
SET NEW.id_estado = COALESCE(
  (SELECT e.id_estado FROM estados_servicio e WHERE e.nombre_estado = NEW.estado LIMIT 1),
  NEW.id_estado);

-- 14. Inventario de verificacion posterior. Estos SELECT no modifican datos.
SELECT 'Tablas principales' AS verificacion, TABLE_NAME AS objeto
FROM information_schema.TABLES
WHERE TABLE_SCHEMA=DATABASE() AND TABLE_TYPE='BASE TABLE'
  AND TABLE_NAME IN ('productos','ventas','servicios','roles','usuarios','clientes','categorias','estados_servicio','detalle_ventas','detalle_servicios')
ORDER BY TABLE_NAME;

SELECT 'Registros actuales' AS verificacion,
       (SELECT COUNT(*) FROM productos) AS productos,
       (SELECT COUNT(*) FROM ventas) AS ventas,
       (SELECT COUNT(*) FROM servicios) AS servicios,
       (SELECT COUNT(*) FROM clientes) AS clientes_migrados,
       (SELECT COUNT(*) FROM detalle_ventas) AS detalles_ventas;
