# Portal de Servicios Judiciales · CSJ San Martín

Portal institucional con frontend HTML/CSS/JS y backend PHP 8 + MySQL
(`public/` raíz web con `api/`; `config/`, `database/` y `.env` privados).

## Desarrollo local con Laragon

1. Inicia Laragon (Apache + MySQL) y crea un enlace a `public/` en
   `C:\laragon\www\servicios-judiciales` (junction), o abre Laragon como
   administrador para el dominio `servicios-judiciales.test`.
2. Crea y actualiza la BD siempre con el migrador (vale para BD nueva o existente):
   `php database/migrate.php`
   Tablas en español: `usuarios`, `categorias`, `servicios`, `noticias`,
   `sedes`, `preguntas_frecuentes`, `ajustes_sitio`, `auditoria`, etc.
   Cada cambio de estructura/datos versionado va en `database/migraciones/`
   y se aplica solo una vez (tabla `migraciones`).

## Trabajo en equipo con Git

1. `git pull` para traer la versión del compañero.
2. `php database/migrate.php` para aplicar las migraciones nuevas de BD.
3. Trabaja, haz commit y `git push`. Tu compañero hace lo mismo al recibir
   los tuyos: la estructura de BD viaja con el código.
3. Revisa `.env` (copia de `.env.example`, nunca se sube al repo).
4. Abre `http://localhost/servicios-judiciales` y entra a **Panel de noticias**.

## Panel de noticias

Ingresa con tu usuario del panel (cuenta inicial `admin`, cambia su clave
temporal en cuanto entres: el sistema lo exige). Desde **Usuarios** (solo
administradores) crea cuentas de editor o administrador. Las noticias con
fotos (JPG/PNG/WebP, máx. 5 MB) se guardan en MySQL (`uploads/noticias/`)
y aparecen en Inicio y Noticias para todos los visitantes.

## Despliegue

Ver `DEPLOY-cpanel.md` (paquete preparado; lanzamiento pendiente de aprobación).

## Páginas

- `index.html`: portada, accesos rápidos, preview de servicios y noticias destacadas.
- `servicios.html`: catálogo completo con buscador y filtros por categoría.
- `nosotros.html`: reseña, Presidencia de Corte y espacios del equipo.
- `noticias.html`: archivo de noticias publicadas.
- `noticia.html`: ficha de cada noticia creada desde el panel.
- `contacto.html`: sede central, teléfono y mapa.
- `admin.html`: acceso y editor de noticias conectado a MySQL.

## Probar el panel de noticias (modo sin backend)

Si abres el portal con `py -m http.server` (sin PHP/MySQL), el panel funciona
en modo local de respaldo: la primera vez el usuario será `admin` y definirás
su contraseña (mínimo 10 caracteres). Desde **Usuarios**, crea una cuenta para el encargado y asigna el rol de editor o administrador. Después podrá iniciar sesión, redactar noticias, cargar una imagen, publicarlas y editarlas o eliminarlas. Las noticias creadas aparecerán en Inicio y en la sección Noticias mientras visites el portal desde el mismo navegador y dirección.

La contraseña y los usuarios quedan guardados localmente en el navegador; las noticias y sus galerías se guardan en IndexedDB del mismo navegador. Las fotos se procesan al cargarse: se conserva su orientación, se reducen a un máximo de 1600 × 1200 px y se comprimen a WebP (con JPEG como alternativa). No se impone un límite fijo de 8 MB al archivo de origen, aunque el navegador y el espacio disponible sí condicionan el tamaño que se puede procesar y guardar. Este modo no crea cuentas institucionales ni sube contenido a un servidor. Para uso público se necesita integrar autenticación del lado servidor, roles, almacenamiento de imágenes, base de datos y protección de formularios.

## Imágenes y datos institucionales

- `Poder Judicial.png` se conserva como archivo original.
- `assets/presidente-edward-sanchez.jpg` es la fotografía publicada en el directorio de funcionarios de Gob.pe.
- `assets/noticia-oficial-2026.jpg` corresponde a una noticia de la Corte publicada en Gob.pe.
- Los cinco perfiles del equipo están listos para recibir nombres, cargos y fotografías cuando se proporcionen.
- La sede y el teléfono se tomaron de la ficha institucional de la Corte. Confirma vigencia antes de publicar el sitio.

## Enlaces consultados

- [Portal de la Corte](https://www.gob.pe/es/csjsanmartin)
- [Funcionarios](https://www.gob.pe/institucion/csjsanmartin/funcionarios)
- [Sedes](https://www.gob.pe/institucion/csjsanmartin/sedes)
- [Noticias](https://www.gob.pe/institucion/csjsanmartin/noticias)
