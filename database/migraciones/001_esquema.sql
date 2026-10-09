-- =====================================================================
-- 001: esquema base (tablas en español, utf8mb4). La BD la crea migrate.php.
-- =====================================================================


-- Usuarios del panel (hash con password_hash() de PHP, nunca texto plano)
CREATE TABLE IF NOT EXISTS usuarios (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username VARCHAR(40) NOT NULL,
  display_name VARCHAR(80) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','editor') NOT NULL DEFAULT 'editor',
  status ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
  must_change_password TINYINT(1) NOT NULL DEFAULT 0,
  last_login_at DATETIME NULL,
  created_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_usuarios_username (username),
  KEY ix_usuarios_role_status (role, status),
  CONSTRAINT fk_usuarios_created_by FOREIGN KEY (created_by) REFERENCES usuarios (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Categorías de servicios (slug coincide con filtros del portal: digital, atencion, institucional)
CREATE TABLE IF NOT EXISTS categorias (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  slug VARCHAR(40) NOT NULL,
  name VARCHAR(80) NOT NULL,
  description VARCHAR(255) NULL,
  sort_order INT NOT NULL DEFAULT 0,
  status ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_categorias_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Catálogo de servicios. url NULL = enlace pendiente de validación (no se publica como botón).
CREATE TABLE IF NOT EXISTS servicios (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  slug VARCHAR(60) NOT NULL,
  name VARCHAR(120) NOT NULL,
  summary VARCHAR(255) NOT NULL,
  description TEXT NULL,
  category_id INT UNSIGNED NOT NULL,
  url VARCHAR(500) NULL,
  link_type ENUM('interno','externo') NOT NULL DEFAULT 'externo',
  icon VARCHAR(50) NOT NULL DEFAULT 'grid',
  sort_order INT NOT NULL DEFAULT 0,
  featured TINYINT(1) NOT NULL DEFAULT 0,
  status ENUM('activo','inactivo','archivado') NOT NULL DEFAULT 'inactivo',
  created_by INT UNSIGNED NULL,
  updated_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_servicios_slug (slug),
  KEY ix_servicios_category_status (category_id, status),
  KEY ix_servicios_featured (featured, sort_order),
  FULLTEXT KEY ft_servicios_search (name, summary, description),
  CONSTRAINT fk_servicios_category FOREIGN KEY (category_id) REFERENCES categorias (id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_servicios_created_by FOREIGN KEY (created_by) REFERENCES usuarios (id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_servicios_updated_by FOREIGN KEY (updated_by) REFERENCES usuarios (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Palabras clave para búsqueda avanzada por servicio
CREATE TABLE IF NOT EXISTS servicio_palabras_clave (
  service_id INT UNSIGNED NOT NULL,
  keyword VARCHAR(60) NOT NULL,
  PRIMARY KEY (service_id, keyword),
  CONSTRAINT fk_keywords_service FOREIGN KEY (service_id) REFERENCES servicios (id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Directorio de sedes (fuente de comprobación: Gob.pe /sedes)
CREATE TABLE IF NOT EXISTS sedes (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  address VARCHAR(255) NOT NULL,
  province_district VARCHAR(120) NULL,
  hours VARCHAR(255) NULL,
  mesa_hours VARCHAR(255) NULL,
  phone VARCHAR(60) NULL,
  email VARCHAR(120) NULL,
  map_url VARCHAR(500) NULL,
  sort_order INT NOT NULL DEFAULT 0,
  status ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_sedes_status (status, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Preguntas frecuentes administrables
CREATE TABLE IF NOT EXISTS preguntas_frecuentes (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  question VARCHAR(255) NOT NULL,
  answer TEXT NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  status ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_preguntas_frecuentes_status (status, sort_order),
  FULLTEXT KEY ft_preguntas_frecuentes_search (question, answer)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Configuración editable no secreta del portal
CREATE TABLE IF NOT EXISTS ajustes_sitio (
  setting_key VARCHAR(60) NOT NULL,
  setting_value TEXT NULL,
  description VARCHAR(255) NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Historial de cambios administrativos (sin contraseñas ni datos sensibles)
CREATE TABLE IF NOT EXISTS auditoria (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NULL,
  action VARCHAR(40) NOT NULL,
  entity VARCHAR(40) NOT NULL,
  entity_id INT UNSIGNED NULL,
  details JSON NULL,
  ip VARCHAR(45) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_audit_user (user_id, created_at),
  KEY ix_audit_entity (entity, entity_id),
  CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES usuarios (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
