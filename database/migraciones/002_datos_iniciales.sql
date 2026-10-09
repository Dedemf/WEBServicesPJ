-- =====================================================================
-- Portal de Servicios Judiciales · CSJ San Martín
-- Datos iniciales. Uso: mysql -u root < database/seed.sql
-- Los servicios con url NULL están pendientes de validación institucional
-- y NO deben publicarse como enlaces activos.
-- =====================================================================

-- Categorías (slugs compatibles con los filtros actuales del portal)
INSERT INTO categorias (slug, name, description, sort_order, status) VALUES
  ('digital',       'Servicios digitales',        'Trámites y consultas en plataformas oficiales en línea', 1, 'activo'),
  ('atencion',      'Atención al usuario',        'Orientación, archivo y canales de atención presencial',  2, 'activo'),
  ('institucional', 'Información institucional',  'Transparencia, convocatorias y enlaces oficiales',        3, 'activo')
ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description),
  sort_order = VALUES(sort_order), status = VALUES(status);

-- Servicios iniciales (orden de presentación 1-14)
INSERT INTO servicios
  (slug, name, summary, description, category_id, url, link_type, icon, sort_order, featured, status)
SELECT * FROM (
  SELECT 'mesa-de-partes' AS slug, 'Mesa de Partes' AS name,
    'Presenta documentos mediante la mesa de partes virtual del Poder Judicial.' AS summary,
    'Canal oficial para la presentación de documentos por mesa de partes virtual.' AS description,
    (SELECT id FROM categorias WHERE slug = 'digital') AS category_id,
    'https://sgd.pj.gob.pe/mpea/inicio' AS url, 'externo' AS link_type, 'document' AS icon,
    1 AS sort_order, 1 AS featured, 'activo' AS status
  UNION ALL SELECT 'casilla-electronica', 'Casilla Electrónica',
    'Accede al sistema de notificaciones electrónicas del Poder Judicial.',
    'Consulta tus notificaciones electrónicas en el sistema oficial SINOE.',
    (SELECT id FROM categorias WHERE slug = 'digital'),
    'https://casillas.pj.gob.pe/sinoe/login.xhtml', 'externo', 'inbox', 2, 1, 'activo'
  UNION ALL SELECT 'consulta-expedientes', 'Consulta de Expedientes',
    'Consulta el estado de tus expedientes judiciales en el portal nacional CEJ.',
    'Consulta el estado de tus expedientes judiciales por número, partes o materia en el portal nacional CEJ.',
    (SELECT id FROM categorias WHERE slug = 'digital'),
    'https://cej.pj.gob.pe/', 'externo', 'search', 3, 0, 'activo'
  UNION ALL SELECT 'notificaciones-judiciales', 'Notificaciones Judiciales',
    'Encuentra los canales oficiales para consultar notificaciones judiciales.',
    'Canales oficiales de consulta de notificaciones. Enlace pendiente de validación.',
    (SELECT id FROM categorias WHERE slug = 'digital'),
    NULL, 'externo', 'bell', 4, 0, 'inactivo'
  UNION ALL SELECT 'recaudacion-aranceles', 'Recaudación y Aranceles',
    'Información sobre aranceles, tasas y servicios de recaudación judicial.',
    'Información de aranceles y tasas judiciales. Enlace pendiente de validación.',
    (SELECT id FROM categorias WHERE slug = 'digital'),
    NULL, 'externo', 'coins', 5, 0, 'inactivo'
  UNION ALL SELECT 'registro-peritos', 'Registro de Peritos',
    'Información para profesionales y consulta del registro de peritos judiciales.',
    'Orientación sobre el registro de peritos judiciales. Enlace pendiente de validación.',
    (SELECT id FROM categorias WHERE slug = 'institucional'),
    NULL, 'externo', 'person', 6, 0, 'inactivo'
  UNION ALL SELECT 'archivo-central', 'Archivo Central',
    'Orientación sobre los servicios y la atención del Archivo Central.',
    'Servicios y atención del Archivo Central. Enlace pendiente de validación.',
    (SELECT id FROM categorias WHERE slug = 'atencion'),
    NULL, 'externo', 'archive', 7, 0, 'inactivo'
  UNION ALL SELECT 'orientacion-usuario', 'Orientación al Usuario',
    'Encuentra canales institucionales de orientación y atención.',
    'Canales institucionales de orientación y atención al usuario.',
    (SELECT id FROM categorias WHERE slug = 'atencion'),
    'https://www.gob.pe/institucion/csjsanmartin/contacto-y-numeros-de-emergencias', 'externo', 'chat', 8, 1, 'activo'
  UNION ALL SELECT 'remates-judiciales', 'Remates Judiciales',
    'Consulta y participa en procesos de remates judiciales electrónicos.',
    'Plataforma oficial de remates judiciales electrónicos REM@JU.',
    (SELECT id FROM categorias WHERE slug = 'digital'),
    'https://remaju.pj.gob.pe', 'externo', 'gavel', 9, 1, 'activo'
  UNION ALL SELECT 'acceso-informacion-publica', 'Acceso a la Información Pública',
    'Conoce el canal oficial para solicitar información pública.',
    'Solicitudes de acceso a la información pública por el portal de transparencia.',
    (SELECT id FROM categorias WHERE slug = 'institucional'),
    'https://www.transparencia.gob.pe/enlaces/pte_transparencia_enlaces.aspx?id_entidad=10231', 'externo', 'info', 10, 0, 'activo'
  UNION ALL SELECT 'libro-reclamaciones', 'Libro de Reclamaciones',
    'Accede al Libro de Reclamaciones del Poder Judicial.',
    'Libro de reclamaciones oficial del Poder Judicial.',
    (SELECT id FROM categorias WHERE slug = 'atencion'),
    'https://www.pj.gob.pe/wps/wcm/connect/cortesuprema/s_cortes_suprema_home/as_servicios/as_enlaces_de_interes/as_libro_de_reclamaciones/libro_reclamaciones_url', 'externo', 'book', 11, 0, 'activo'
  UNION ALL SELECT 'convocatorias-cas', 'Convocatorias CAS',
    'Consulta oportunidades laborales en los canales institucionales.',
    'Convocatorias y oportunidades laborales publicadas en Gob.pe.',
    (SELECT id FROM categorias WHERE slug = 'institucional'),
    'https://www.gob.pe/institucion/csjsanmartin/tramites-y-servicios', 'externo', 'briefcase', 12, 0, 'activo'
  UNION ALL SELECT 'directorio-sedes', 'Directorio de sedes',
    'Consulta sedes, direcciones y canales de atención publicados oficialmente.',
    'Directorio oficial de sedes del distrito judicial de San Martín.',
    (SELECT id FROM categorias WHERE slug = 'atencion'),
    'https://www.gob.pe/institucion/csjsanmartin/sedes', 'externo', 'pin', 13, 0, 'activo'
  UNION ALL SELECT 'otros-servicios', 'Otros servicios',
    'Explora trámites y servicios publicados por la Corte en Gob.pe.',
    'Trámites y servicios adicionales publicados por la Corte en Gob.pe.',
    (SELECT id FROM categorias WHERE slug = 'institucional'),
    'https://www.gob.pe/institucion/csjsanmartin/tramites-y-servicios', 'externo', 'grid', 14, 0, 'activo'
  UNION ALL SELECT 'redam-deudores-alimentarios', 'Registro de Deudores Alimentarios',
    'Consulta el registro de deudores alimentarios morosos y genera certificados.',
    'Verifica si una persona está registrada como deudora alimentaria morosa y genera certificados oficiales.',
    (SELECT id FROM categorias WHERE slug = 'digital'),
    'https://redam.pj.gob.pe/', 'externo', 'idcard', 15, 0, 'activo'
  UNION ALL SELECT 'odanc-control', 'ODANC · Control y Quejas',
    'Presenta quejas por inconducta de magistrados y servidores judiciales.',
    'La Oficina Descentralizada de la Autoridad Nacional de Control recibe quejas y supervisa la conducta en el distrito judicial. Canal pendiente de validación.',
    (SELECT id FROM categorias WHERE slug = 'atencion'),
    NULL, 'externo', 'shield', 16, 0, 'inactivo'
  UNION ALL SELECT 'violencia-familiar', 'Módulo de Violencia Familiar',
    'Atención de denuncias y medidas de protección frente a la violencia familiar.',
    'Módulos y juzgados que atienden denuncias por violencia contra la mujer e integrantes del grupo familiar. Canal pendiente de validación.',
    (SELECT id FROM categorias WHERE slug = 'atencion'),
    NULL, 'externo', 'heart', 17, 0, 'inactivo'
  UNION ALL SELECT 'formularios', 'Formularios y Formatos',
    'Formatos descargables para tus trámites judiciales.',
    'Formatos para mesa de partes, apersonamientos y otros escritos. Enlace pendiente de validación.',
    (SELECT id FROM categorias WHERE slug = 'digital'),
    NULL, 'externo', 'clip', 18, 0, 'inactivo'
  UNION ALL SELECT 'competencia-territorial', 'Competencia Territorial',
    'Conoce qué sede y juzgado corresponde según la materia y el lugar.',
    'Orientación sobre qué órgano jurisdiccional y sede es competente según materia y territorio. Pendiente de validación.',
    (SELECT id FROM categorias WHERE slug = 'institucional'),
    NULL, 'externo', 'map', 19, 0, 'inactivo'
) AS seed
ON DUPLICATE KEY UPDATE name = seed.name, summary = seed.summary,
  description = seed.description, category_id = seed.category_id, url = seed.url,
  link_type = seed.link_type, icon = seed.icon, sort_order = seed.sort_order,
  featured = seed.featured, status = seed.status;

-- Palabras clave de búsqueda
INSERT IGNORE INTO servicio_palabras_clave (service_id, keyword)
SELECT s.id, k.keyword FROM servicios s JOIN (
  SELECT 'mesa-de-partes' AS slug, 'documentos' AS keyword UNION ALL
  SELECT 'mesa-de-partes', 'virtual' UNION ALL
  SELECT 'casilla-electronica', 'notificaciones' UNION ALL
  SELECT 'casilla-electronica', 'sinoe' UNION ALL
  SELECT 'consulta-expedientes', 'expediente' UNION ALL
  SELECT 'consulta-expedientes', 'estado' UNION ALL
  SELECT 'remates-judiciales', 'remaju' UNION ALL
  SELECT 'remates-judiciales', 'subasta' UNION ALL
  SELECT 'orientacion-usuario', 'ayuda' UNION ALL
  SELECT 'orientacion-usuario', 'atención' UNION ALL
  SELECT 'directorio-sedes', 'dirección' UNION ALL
  SELECT 'directorio-sedes', 'moyobamba' UNION ALL
  SELECT 'libro-reclamaciones', 'reclamo' UNION ALL
  SELECT 'acceso-informacion-publica', 'transparencia' UNION ALL
  SELECT 'consulta-expedientes', 'cej' UNION ALL
  SELECT 'redam-deudores-alimentarios', 'deudores' UNION ALL
  SELECT 'redam-deudores-alimentarios', 'alimentos' UNION ALL
  SELECT 'odanc-control', 'queja' UNION ALL
  SELECT 'odanc-control', 'control' UNION ALL
  SELECT 'violencia-familiar', 'denuncia' UNION ALL
  SELECT 'violencia-familiar', 'protección' UNION ALL
  SELECT 'formularios', 'formatos'
) AS k ON k.slug = s.slug;

-- Sede central (datos de la ficha institucional de Gob.pe; confirmar vigencia)
INSERT INTO sedes (name, address, province_district, phone, sort_order, status) VALUES
  ('Sede Central · Moyobamba', 'Jr. Pedro Canga N.º 354, a media cuadra de la Plaza de Armas',
   'Moyobamba · San Martín', '(042) 582080', 1, 'activo')
ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id);

-- Preguntas frecuentes iniciales
INSERT INTO preguntas_frecuentes (question, answer, sort_order, status) VALUES
  ('¿Cómo encuentro el servicio judicial que necesito?',
   'Usa el buscador del portal o filtra por categoría (Digitales, Atención, Institucional). Cada tarjeta indica si el trámite se realiza en una plataforma externa oficial.', 1, 'activo'),
  ('¿Dónde realizo mis trámites?',
   'Los trámites se realizan en las plataformas oficiales del Poder Judicial (mesa de partes virtual, casilla electrónica, REM@JU) o en las sedes. Este portal orienta y enlaza; no replica los sistemas ni pide tus credenciales.', 2, 'activo'),
  ('¿Cómo consulto mis notificaciones electrónicas?',
   'Ingresa a la Casilla Electrónica (SINOE) con tus credenciales personales en el enlace oficial publicado en este portal. Nunca compartas tu clave.', 3, 'activo'),
  ('¿Dónde encuentro direcciones y horarios de las sedes?',
   'En la sección Contacto está la sede central de Moyobamba y el enlace al directorio oficial de sedes en Gob.pe, donde se publican direcciones y canales vigentes.', 4, 'activo'),
  ('¿Cómo solicito información pública?',
   'A través del canal oficial de Acceso a la Información Pública enlazado en este portal (portal de transparencia).', 5, 'activo');

-- Configuración base del portal
INSERT INTO ajustes_sitio (setting_key, setting_value, description) VALUES
  ('site_name', 'Servicios Judiciales · CSJ San Martín', 'Nombre del portal'),
  ('sede_address', 'Jr. Pedro Canga N.º 354, Moyobamba, San Martín', 'Dirección de la sede central'),
  ('contact_phone', '(042) 582080', 'Teléfono central (confirmar vigencia)'),
  ('url_gob', 'https://www.gob.pe/csjsanmartin', 'Portal institucional en Gob.pe'),
  ('url_tramites', 'https://www.gob.pe/institucion/csjsanmartin/tramites-y-servicios', 'Trámites y servicios en Gob.pe'),
  ('url_sedes', 'https://www.gob.pe/institucion/csjsanmartin/sedes', 'Directorio de sedes en Gob.pe'),
  ('url_contacto', 'https://www.gob.pe/institucion/csjsanmartin/contacto-y-numeros-de-emergencias', 'Contacto en Gob.pe')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), description = VALUES(description);
