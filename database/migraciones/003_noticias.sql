-- Noticias del portal + control de intentos de login.
-- Uso: mysql -u root < database/migrate_noticias.sql

CREATE TABLE IF NOT EXISTS noticias (
  id VARCHAR(40) NOT NULL,
  title VARCHAR(160) NOT NULL,
  summary VARCHAR(280) NOT NULL,
  body MEDIUMTEXT NOT NULL,
  category VARCHAR(60) NOT NULL DEFAULT 'Institucional',
  image VARCHAR(500) NULL,
  photos JSON NULL,
  primary_photo_index INT NOT NULL DEFAULT 0,
  external VARCHAR(500) NULL,
  video_url VARCHAR(500) NULL,
  featured TINYINT(1) NOT NULL DEFAULT 0,
  official TINYINT(1) NOT NULL DEFAULT 0,
  status ENUM('activo','inactivo','archivado') NOT NULL DEFAULT 'activo',
  published_at DATE NULL,
  created_by INT UNSIGNED NULL,
  updated_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_noticias_status_date (status, published_at),
  KEY ix_noticias_featured (featured, published_at),
  FULLTEXT KEY ft_noticias_search (title, summary, body),
  CONSTRAINT fk_noticias_created_by FOREIGN KEY (created_by) REFERENCES usuarios (id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_noticias_updated_by FOREIGN KEY (updated_by) REFERENCES usuarios (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS intentos_acceso (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  ip VARCHAR(45) NOT NULL,
  username VARCHAR(40) NULL,
  success TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_attempts_ip_time (ip, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Noticia oficial inicial (visible en portada)
INSERT INTO noticias (id, title, summary, body, category, image, external, featured, official, status, published_at)
VALUES ('official-1415894',
  'Corte de San Martín impulsa jornada educativa sobre la ruta de la flagrancia delictiva',
  'Estudiantes conocieron de manera vivencial el funcionamiento del sistema de justicia y reflexionaron sobre la prevención del delito.',
  'La Corte Superior de Justicia de San Martín desarrolló una jornada educativa dirigida a estudiantes de secundaria, como parte de las acciones orientadas a fortalecer la cultura de legalidad y prevenir el delito entre adolescentes.\n\nLa actividad acercó a las y los estudiantes al funcionamiento del sistema de justicia y promovió la reflexión sobre sus derechos y responsabilidades.',
  'Servicios judiciales', 'assets/noticia-oficial-2026.jpg',
  'https://www.gob.pe/institucion/csjsanmartin/noticias/1415894-corte-de-san-martin-impulsa-jornada-educativa-sobre-la-ruta-de-la-flagrancia-delictiva-para-prevenir-el-delito-en-adolescentes',
  1, 1, 'activo', '2026-07-03')
ON DUPLICATE KEY UPDATE title = VALUES(title), summary = VALUES(summary), body = VALUES(body);
