# Despliegue en cPanel — Portal CSJ San Martín (PREPARACIÓN, no lanzar aún)

> Estado: paquete listo. El lanzamiento queda pendiente de aprobación institucional
> (dominio, proveedor y publicación). No publicar en cuentas personales.

## 1. Requisitos a confirmar con el proveedor / área de informática

- PHP 8.x con extensiones: `pdo_mysql`, `mbstring`, `fileinfo`, `gd`, `intl`, `openssl`
- MySQL o MariaDB (1 BD) con `utf8mb4`
- Certificado SSL/TLS (AutoSSL) + posibilidad de document root en `public/`
- Acceso cPanel: Administrador de archivos o SFTP, logs de errores
- Copias de seguridad y política de restauración

## 2. Estructura de despliegue recomendada

```
cuenta/
├── privado/               # FUERA del alcance web
│   ├── config/  database/  database/crear-admin.php  .env
└── public_html/           # contenido de public/ (incluye api/ y uploads/)
    ├── index.html, *.html, styles.css, app.js, admin.js, assets/
    ├── api/  uploads/  favicon.svg  robots.txt  sitemap.xml  .htaccess
```

> `api/` vive dentro de `public/` porque debe ser accesible por el portal;
> `config/` y `.env` quedan siempre fuera del alcance web. Las rutas
> `../../config` en `public/api/*` ya apuntan a esa distribución.

Si el hosting permite fijar el document root en `public/`, subir tal cual.
Si NO lo permite: copiar el contenido de `public/` a `public_html/` y
`config/` con `.env` a una carpeta hermana NO pública (las rutas ya sirven).

## 3. Pasos de instalación

1. En cPanel → MySQL: crear BD `portal_csjsm` (utf8mb4_unicode_ci) y usuario
   con permisos mínimos (SELECT, INSERT, UPDATE, DELETE).
2. Crea la BD vacía `portal_csjsm` (utf8mb4_unicode_ci) y ejecuta el
   migrador por SSH/terminal: `php database/migrate.php`
   (aplica `database/migraciones/` en orden y deja todo listo).
3. Copiar `.env.example` a `.env` (fuera del alcance web) con credenciales
   reales, `APP_ENV=production` y la URL definitiva. **Nunca subir `.env` al repo.**
4. Subir archivos por SFTP/Administrador de archivos. Permisos: 644 archivos, 755 carpetas.
5. Crear el admin por SSH: `php database/crear-admin.php` (o temporalmente por
   terminal de cPanel). Borrar o bloquear ese script después.
6. Activar SSL y comprobar la redirección HTTPS del `.htaccess`.
7. Entrar al panel, cambiar la clave inicial (obligatorio) y publicar el contenido.

## 4. Pruebas de aceptación (antes de publicar)

- [ ] Portada carga sin errores y el catálogo viene de MySQL
- [ ] Login admin/editor, roles limitan operaciones (usuarios solo admin)
- [ ] 5 intentos fallidos bloquean 10 minutos (rate-limit)
- [ ] CSRF: formularios sin token son rechazados (403)
- [ ] Servicios sin URL no se publican como botón
- [ ] Subida rechaza PHP/Ejecutables y archivos >5 MB
- [ ] `tudominio/config/`, `/database/`, `/.env` devuelven 403
- [ ] Responsive móvil/tablet/escritorio, foco visible, sin errores en consola
- [ ] Copia de seguridad de BD probada + procedimiento de restauración

## 5. Problemas conocidos pendientes

- Fotos del equipo (5) y datos de contacto: confirmar vigencia antes de publicar.
- URLs de 5 servicios marcadas `inactivo` hasta validación institucional.
- Dominio/subdominio y proveedor: pendientes de aprobación.
