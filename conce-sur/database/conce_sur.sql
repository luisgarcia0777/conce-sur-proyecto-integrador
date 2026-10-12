CREATE DATABASE IF NOT EXISTS conce_sur CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE conce_sur;

CREATE TABLE IF NOT EXISTS productos (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  codigo VARCHAR(40) NOT NULL,
  nombre VARCHAR(120) NOT NULL,
  modelo VARCHAR(120) NOT NULL DEFAULT '',
  tipo ENUM('Fotocopiadora','Tóner','Repuesto','Plotter','Equipo de oficina','Otro') NOT NULL DEFAULT 'Otro',
  ubicacion ENUM('Local','Almacén') NOT NULL DEFAULT 'Local',
  stock INT UNSIGNED NOT NULL DEFAULT 0,
  stock_minimo INT UNSIGNED NOT NULL DEFAULT 1,
  precio DECIMAL(10,2) NULL,
  observaciones VARCHAR(500) NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_productos_codigo (codigo),
  KEY idx_productos_nombre (nombre),
  KEY idx_productos_ubicacion (ubicacion)
) ENGINE=InnoDB;

-- Datos demostrativos iniciales; reemplázalos por el inventario real de CONCE SUR.
INSERT IGNORE INTO productos (codigo,nombre,modelo,tipo,ubicacion,stock,stock_minimo,observaciones) VALUES
('DEMO-EQ-001','Fotocopiadora Ricoh','MP 5002','Fotocopiadora','Local',2,1,'Registro demostrativo; confirmar datos reales'),
('DEMO-EQ-002','Fotocopiadora Kyocera','M2040','Fotocopiadora','Almacén',1,1,'Registro demostrativo; confirmar datos reales'),
('DEMO-TN-001','Tóner Ricoh','MP 2014','Tóner','Local',2,3,'Registro demostrativo; confirmar datos reales'),
('DEMO-RP-001','Rodillo de alimentación','Universal','Repuesto','Almacén',1,2,'Registro demostrativo; confirmar datos reales');


-- Registro de ventas (una línea de producto por venta en esta versión inicial).
CREATE TABLE IF NOT EXISTS ventas (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  numero_venta VARCHAR(30) NULL,
  fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  documento_cliente VARCHAR(20) NULL,
  cliente_nombre VARCHAR(160) NOT NULL,
  cliente_telefono VARCHAR(30) NULL,
  producto_id INT UNSIGNED NOT NULL,
  producto_codigo VARCHAR(40) NOT NULL,
  producto_nombre VARCHAR(120) NOT NULL,
  cantidad INT UNSIGNED NOT NULL,
  precio_unitario DECIMAL(10,2) NOT NULL,
  total DECIMAL(12,2) NOT NULL,
  forma_pago ENUM('Contado','Crédito') NOT NULL DEFAULT 'Contado',
  contador_copias INT UNSIGNED NULL,
  observaciones VARCHAR(500) NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_ventas_numero (numero_venta),
  KEY idx_ventas_fecha (fecha),
  KEY idx_ventas_cliente (cliente_nombre),
  CONSTRAINT fk_ventas_producto FOREIGN KEY (producto_id) REFERENCES productos(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;


-- Seguimiento de instalaciones, capacitaciones, reparaciones y mantenimiento.
CREATE TABLE IF NOT EXISTS servicios (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  codigo_servicio VARCHAR(30) NULL,
  fecha_ingreso DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  cliente_nombre VARCHAR(160) NOT NULL,
  cliente_telefono VARCHAR(30) NULL,
  equipo VARCHAR(160) NOT NULL,
  modelo VARCHAR(120) NULL,
  tipo_servicio ENUM('Instalación','Capacitación','Reparación','Mantenimiento','Soporte técnico','Otro') NOT NULL DEFAULT 'Reparación',
  descripcion VARCHAR(1000) NOT NULL,
  estado ENUM('Pendiente','En revisión','Finalizado') NOT NULL DEFAULT 'Pendiente',
  diagnostico VARCHAR(1000) NULL,
  solucion VARCHAR(1000) NULL,
  costo DECIMAL(10,2) NULL,
  fecha_cierre DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_servicios_codigo (codigo_servicio),
  KEY idx_servicios_estado (estado),
  KEY idx_servicios_fecha (fecha_ingreso),
  KEY idx_servicios_cliente (cliente_nombre)
) ENGINE=InnoDB;
