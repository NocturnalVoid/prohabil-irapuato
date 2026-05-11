-- ============================================
-- PROHABIL - Base de Datos
-- Irapuato, Guanajuato, México
-- Compatible con phpMyAdmin / MySQL
-- ============================================

CREATE DATABASE IF NOT EXISTS prohabil_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE prohabil_db;

-- ============================================
-- TABLA: oficios (catálogo de oficios)
-- ============================================
CREATE TABLE IF NOT EXISTS oficios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  icono VARCHAR(50) DEFAULT '🔧',
  descripcion TEXT,
  activo TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO oficios (nombre, icono, descripcion) VALUES
('Albañil',        '🧱', 'Construcción, mampostería y obra civil'),
('Carpintero',     '🪵', 'Muebles, puertas, ventanas y estructuras de madera'),
('Electricista',   '⚡', 'Instalaciones eléctricas residenciales y comerciales'),
('Plomero',        '🔧', 'Instalaciones hidráulicas y sanitarias'),
('Pintor',         '🎨', 'Pintura de interiores y exteriores'),
('Herrero',        '⚙️',  'Herrería, soldadura y trabajos en metal'),
('Jardinero',      '🌿', 'Diseño y mantenimiento de jardines'),
('Mecánico',       '🔩', 'Reparación y mantenimiento de vehículos'),
('Fontanero',      '🚿', 'Sistemas de agua y gas'),
('Techador',       '🏠', 'Instalación y reparación de techos'),
('Cerrajero',      '🔐', 'Apertura y mantenimiento de cerraduras'),
('Yesero',         '🪣', 'Aplicación de yeso, aplanados y acabados'),
('Soldador',       '🔥', 'Soldadura MIG, TIG y de punto'),
('Gasfitero',      '🛠️',  'Instalaciones de gas LP y natural'),
('Tapicero',       '🛋️',  'Tapizado de muebles y automóviles');

-- ============================================
-- TABLA: usuarios (clientes y trabajadores)
-- ============================================
CREATE TABLE IF NOT EXISTS usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tipo ENUM('cliente','trabajador') NOT NULL,
  nombre VARCHAR(100) NOT NULL,
  apellido_paterno VARCHAR(100) NOT NULL,
  apellido_materno VARCHAR(100),
  telefono VARCHAR(15) NOT NULL,
  correo VARCHAR(150) NOT NULL UNIQUE,
  contrasena VARCHAR(255) NOT NULL,  -- bcrypt hash
  foto_perfil VARCHAR(255) DEFAULT NULL,
  colonia VARCHAR(150),
  direccion TEXT,
  fecha_nacimiento DATE,
  genero ENUM('masculino','femenino','otro','prefiero_no_decir'),
  activo TINYINT(1) DEFAULT 1,
  verificado TINYINT(1) DEFAULT 0,
  token_verificacion VARCHAR(64) DEFAULT NULL,
  ultimo_login TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_correo (correo),
  INDEX idx_tipo (tipo)
) ENGINE=InnoDB;

-- ============================================
-- TABLA: trabajadores (datos extra del trabajador)
-- ============================================
CREATE TABLE IF NOT EXISTS trabajadores (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  oficio_id INT NOT NULL,
  descripcion TEXT,
  años_experiencia TINYINT UNSIGNED DEFAULT 0,
  precio_hora DECIMAL(8,2) DEFAULT NULL,
  precio_dia DECIMAL(8,2) DEFAULT NULL,
  disponible TINYINT(1) DEFAULT 1,
  calificacion_promedio DECIMAL(3,2) DEFAULT 0.00,
  total_trabajos INT DEFAULT 0,
  ine_verificada TINYINT(1) DEFAULT 0,
  zona_servicio VARCHAR(255) DEFAULT 'Irapuato y área metropolitana',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  FOREIGN KEY (oficio_id) REFERENCES oficios(id)
) ENGINE=InnoDB;

-- ============================================
-- TABLA: solicitudes (clientes contratan trabajadores)
-- ============================================
CREATE TABLE IF NOT EXISTS solicitudes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cliente_id INT NOT NULL,
  trabajador_id INT NOT NULL,
  descripcion_trabajo TEXT NOT NULL,
  direccion_trabajo TEXT NOT NULL,
  fecha_requerida DATE,
  estado ENUM('pendiente','aceptado','en_progreso','completado','cancelado') DEFAULT 'pendiente',
  precio_acordado DECIMAL(10,2) DEFAULT NULL,
  notas_cliente TEXT,
  notas_trabajador TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (cliente_id) REFERENCES usuarios(id),
  FOREIGN KEY (trabajador_id) REFERENCES trabajadores(id)
) ENGINE=InnoDB;

-- ============================================
-- TABLA: calificaciones (reseñas)
-- ============================================
CREATE TABLE IF NOT EXISTS calificaciones (
  id INT AUTO_INCREMENT PRIMARY KEY,
  solicitud_id INT NOT NULL,
  cliente_id INT NOT NULL,
  trabajador_id INT NOT NULL,
  estrellas TINYINT NOT NULL CHECK (estrellas BETWEEN 1 AND 5),
  comentario TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (solicitud_id) REFERENCES solicitudes(id),
  FOREIGN KEY (cliente_id) REFERENCES usuarios(id),
  FOREIGN KEY (trabajador_id) REFERENCES trabajadores(id)
) ENGINE=InnoDB;

-- ============================================
-- TABLA: sesiones (manejo de tokens de sesión)
-- ============================================
CREATE TABLE IF NOT EXISTS sesiones (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  token VARCHAR(128) NOT NULL UNIQUE,
  ip_address VARCHAR(45),
  user_agent TEXT,
  expira_en TIMESTAMP NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  INDEX idx_token (token)
) ENGINE=InnoDB;

-- ============================================
-- VISTA: vista_trabajadores (útil para búsquedas)
-- ============================================
CREATE OR REPLACE VIEW vista_trabajadores AS
SELECT
  t.id AS trabajador_id,
  u.id AS usuario_id,
  CONCAT(u.nombre, ' ', u.apellido_paterno) AS nombre_completo,
  u.telefono,
  u.correo,
  u.foto_perfil,
  u.colonia,
  o.nombre AS oficio,
  o.icono AS oficio_icono,
  t.descripcion,
  t.años_experiencia,
  t.precio_hora,
  t.precio_dia,
  t.disponible,
  t.calificacion_promedio,
  t.total_trabajos,
  t.ine_verificada,
  t.zona_servicio
FROM trabajadores t
JOIN usuarios u ON t.usuario_id = u.id
JOIN oficios o ON t.oficio_id = o.id
WHERE u.activo = 1;

-- ============================================
-- TRIGGER: actualizar calificación promedio
-- ============================================
DELIMITER $$
CREATE TRIGGER after_calificacion_insert
AFTER INSERT ON calificaciones
FOR EACH ROW
BEGIN
  UPDATE trabajadores
  SET
    calificacion_promedio = (
      SELECT ROUND(AVG(estrellas), 2)
      FROM calificaciones
      WHERE trabajador_id = NEW.trabajador_id
    ),
    total_trabajos = (
      SELECT COUNT(*)
      FROM calificaciones
      WHERE trabajador_id = NEW.trabajador_id
    )
  WHERE id = NEW.trabajador_id;
END$$
DELIMITER ;

-- ============================================
-- Datos de prueba (opcional — comentar si no se quieren)
-- ============================================
-- Contraseña para todos: Test1234! (hash bcrypt)
INSERT INTO usuarios (tipo, nombre, apellido_paterno, apellido_materno, telefono, correo, contrasena, colonia, activo, verificado) VALUES
('trabajador', 'Juan', 'Martínez', 'López',   '4621234567', 'juan.martinez@prohabil.mx',  '$2y$12$example_hash_juan',   'Centro',          1, 1),
('trabajador', 'Carlos', 'Pérez', 'García',   '4629876543', 'carlos.perez@prohabil.mx',   '$2y$12$example_hash_carlos', 'Paraíso',         1, 1),
('cliente',    'María',  'González', 'Torres', '4621122334', 'maria.gonzalez@prohabil.mx', '$2y$12$example_hash_maria',  'Villas del Rey',  1, 1);

SELECT 'Base de datos ProHabil creada exitosamente ✅' AS mensaje;
