# PROMPT TÉCNICO DEFINITIVO
## Portal independiente de Servicios Judiciales — Corte Superior de Justicia de San Martín

> **Instrucción de uso:** copia este documento completo en tu asistente de programación con IA. Adjunta también el archivo del logotipo institucional proporcionado. El objetivo es desarrollar una aplicación real, documentada y preparada para instalarse en cPanel, no solo una maqueta.

---

## 1. Rol que debes asumir

Actúa como un equipo senior formado por:

- Arquitecto de software para portales institucionales.
- Diseñador UI/UX especializado en servicios públicos y justicia.
- Desarrollador frontend con HTML5, CSS3, JavaScript y Bootstrap 5.
- Desarrollador backend con PHP 8.x y MySQL/MariaDB.
- Especialista en seguridad de aplicaciones web, accesibilidad, SEO y rendimiento.
- Especialista en despliegue, operación y mantenimiento de aplicaciones en cPanel.

Debes explicar las decisiones técnicas relevantes y entregar código completo, coherente, mantenible y listo para pruebas. No entregues solo ejemplos aislados, pseudocódigo o una maqueta sin funcionalidad.

## 2. Objetivo del proyecto

Desarrollar un **portal independiente de Servicios Judiciales de la Corte Superior de Justicia de San Martín**, perteneciente al Poder Judicial del Perú.

El portal será una web propia, separada de la página institucional existente, que centralice accesos y orientación sobre los servicios de la Corte. Debe ayudar a ciudadanos, abogados, litigantes y usuarios judiciales a localizar rápidamente el servicio correcto.

Debe ser institucional, moderno, elegante, rápido, accesible, responsivo, seguro y sencillo de actualizar mediante un panel de administración privado.

### Referencias que deben estudiarse

- Página de referencia visual y funcional de Arequipa: https://csjarequipa.pj.gob.pe/main/servicios-judiciales/
- Página oficial de la Corte Superior de Justicia de San Martín en Gob.pe: https://www.gob.pe/csjsanmartin
- Trámites y servicios publicados en Gob.pe: https://www.gob.pe/institucion/csjsanmartin/tramites-y-servicios
- Sedes publicadas en Gob.pe: https://www.gob.pe/institucion/csjsanmartin/sedes
- Contacto institucional publicado en Gob.pe: https://www.gob.pe/institucion/csjsanmartin/contacto-y-numeros-de-emergencias

Analiza la referencia de Arequipa para aprender de su organización y navegación, pero no copies literalmente su código, diseño, textos ni identidad gráfica. La identidad final debe corresponder a la Corte Superior de Justicia de San Martín.

Los enlaces de Gob.pe son referencias oficiales para validar y enlazar información existente. Antes de publicar, vuelve a comprobar cada URL y cada dato. No asumas que un enlace seguirá vigente solo porque aparece en este prompt.

## 3. Logotipo e identidad visual institucional

Se proporcionará un archivo con el logotipo oficial de la Corte Superior de Justicia de San Martín. Utiliza ese archivo como recurso institucional:

- No redibujar, deformar, recolorear ni recortar el logotipo.
- Conservar sus proporciones y, si existe, su transparencia.
- No crear escudos ni símbolos que aparenten ser oficiales.
- Preparar una versión web optimizada únicamente si puede hacerse sin alterar la identidad; conservar el original como referencia.
- En fondos claros u oscuros, comprobar que el logotipo se visualice correctamente sin modificarlo.

### Paleta

Usa como referencia inicial los colores visibles en el logotipo proporcionado: **rojo/guinda institucional, gris, blanco y neutros**. El rojo/guinda y el gris deben protagonizar la interfaz. Evita incorporar colores saturados ajenos a la identidad.

Los códigos HEX/RGB definitivos deben verificarse con el manual de identidad visual vigente del Poder Judicial o con el área institucional competente. Hasta entonces, marca los valores CSS como aproximados y configurables; no los declares oficialmente aprobados.

Centraliza los colores en variables CSS, por ejemplo:

- `--color-institucional`: rojo/guinda, pendiente de validación oficial.
- `--color-gris-institucional`: gris, pendiente de validación oficial.
- `--color-fondo`: blanco o gris muy claro.
- `--color-texto`: gris carbón con contraste suficiente.
- `--color-borde`: gris claro.

Diseño esperado: sobrio, actual, limpio, con jerarquía visual clara, espacios generosos, tipografía legible, iconos consistentes, bordes discretos y animaciones moderadas. No utilizar demasiadas sombras, degradados, carruseles automáticos o elementos decorativos que dificulten encontrar los servicios.

## 4. Arquitectura técnica y compatibilidad con cPanel

### Stack requerido

- PHP 8.x, seleccionando una versión compatible con el hosting que finalmente se contrate.
- HTML5 semántico.
- CSS3.
- Bootstrap 5.
- JavaScript moderno sin dependencias innecesarias.
- MySQL o MariaDB para servicios, categorías, usuarios y configuración del portal.
- PDO con consultas preparadas para acceder a la base de datos.
- Composer solo si las dependencias elegidas y el hosting permiten instalarlo o desplegar sus dependencias previamente.

No exigir Node.js, Docker, procesos permanentes, Redis ni servicios que normalmente no estén disponibles en un hosting compartido. Si una característica necesita una capacidad especial, documentarla como requisito y ofrecer una alternativa compatible.

### Arquitectura recomendada

Organiza la aplicación con separación clara de responsabilidades:

- **Presentación:** vistas PHP, plantillas reutilizables, CSS y JavaScript.
- **Lógica:** controladores y servicios de aplicación.
- **Datos:** modelos o repositorios para consultar MySQL/MariaDB.
- **Configuración:** variables de entorno o archivo de configuración protegido fuera del directorio público cuando el hosting lo permita.
- **Administración:** rutas y vistas privadas separadas de la zona pública.
- **Recursos estáticos:** imágenes, hojas de estilo, scripts e iconos.

No mezcles consultas SQL, lógica de negocio y grandes bloques HTML en un único archivo. Evita construir un framework propio complejo si una estructura PHP sencilla y segura cubre las necesidades.

### Estructura orientativa

```text
portal-servicios/
├── app/
│   ├── Controllers/
│   ├── Models/
│   ├── Services/
│   ├── Views/
│   │   ├── layouts/
│   │   ├── pages/
│   │   └── admin/
│   └── Middleware/
├── config/
├── database/
│   ├── migrations/
│   └── seeders/
├── public/
│   ├── index.php
│   └── assets/
│       ├── css/
│       ├── js/
│       ├── img/
│       └── icons/
├── routes/
├── storage/
│   └── logs/
├── .env.example
├── .htaccess
├── composer.json
└── README.md
```

Adapta esta estructura a las limitaciones del hosting. En producción, el directorio público del dominio o subdominio debe apuntar a `public/` cuando cPanel lo permita. Si no se puede cambiar el document root, define una distribución alternativa segura: nunca expongas directamente archivos `.env`, copias de seguridad, logs, SQL, código de configuración ni dependencias privadas. Protege mediante reglas de servidor los directorios que deban permanecer dentro del área de la cuenta.

No incluyas secretos reales en el repositorio ni en el ZIP de entrega.

## 5. Módulos del portal público

### 5.1 Página de inicio

Debe contener:

1. Cabecera institucional con el logotipo, nombre completo de la Corte y menú.
2. Sección principal con el título **“Servicios Judiciales”**.
3. Texto introductorio sugerido: “Encuentra de manera sencilla los servicios y canales de atención de la Corte Superior de Justicia de San Martín”.
4. Buscador principal con el texto “¿Qué servicio judicial necesitas?”.
5. Accesos rápidos a los servicios destacados.
6. Catálogo de servicios por categorías.
7. Bloque de orientación y preguntas frecuentes.
8. Acceso al directorio de sedes.
9. Enlaces a portales institucionales oficiales.
10. Pie de página con datos verificados, aviso de privacidad y enlaces relevantes.

No inventes estadísticas de uso, cifras de atención, testimonios, horarios ni mensajes que aparenten ser declaraciones oficiales.

### 5.2 Catálogo de servicios

Cada servicio debe mostrar:

- Nombre.
- Descripción corta y clara.
- Categoría.
- Icono uniforme.
- Botón “Acceder al servicio” o “Ver información”.
- Tipo de destino: enlace interno o enlace externo.
- Estado de publicación.
- Indicador de enlace externo cuando corresponda.

El catálogo debe permitir:

- Buscar por nombre, descripción y palabras clave.
- Filtrar por categoría.
- Ordenar alfabéticamente.
- Mostrar resultados sin recargar toda la página cuando sea razonable.
- Informar cuando no haya coincidencias.
- Limpiar filtros.
- Adaptar la cuadrícula a móvil, tablet y escritorio.

### 5.3 Servicios iniciales solicitados

Crea registros iniciales para los siguientes servicios. Estos registros son una base de contenido que debe poder editarse desde el panel administrativo:

1. **Mesa de Partes**
2. **Casilla Electrónica**
3. **Consulta de Expedientes**
4. **Notificaciones Judiciales**
5. **Recaudación y Aranceles**
6. **Registro de Peritos**
7. **Archivo Central**
8. **Orientación al Usuario**
9. **Remates Judiciales**
10. **Acceso a la Información Pública**
11. **Libro de Reclamaciones**
12. **Convocatorias CAS**
13. **Directorio de sedes**
14. **Otros servicios**

Cada registro puede incluir: slug, nombre, resumen, descripción detallada, categoría, palabras clave, URL de destino, tipo de enlace, icono, orden de presentación, estado activo/inactivo, indicador de destacado y fechas de creación/actualización.

**No inventes las URLs específicas de los sistemas.** Cuando el enlace no haya sido verificado, registra `URL_PENDIENTE_DE_VALIDACION` en los datos de desarrollo o deja el campo vacío con un aviso visible solo para administradores. Nunca muestres ese marcador a visitantes ni conviertas un enlace desconocido en un botón operativo. No actives en producción servicios con URL sin validar.

Los servicios de Mesa de Partes, casilla electrónica, consultas y notificaciones pueden llevar a sistemas oficiales existentes. El portal debe orientar y enlazar; no debe replicar los sistemas transaccionales ni solicitar credenciales de usuarios.

### 5.4 Ficha de servicio

Permite una página individual para los servicios que necesiten mayor explicación, con campos opcionales:

- Qué es el servicio.
- A quién está dirigido.
- Requisitos.
- Pasos para acceder.
- Documentos necesarios.
- Horario de atención confirmado.
- Teléfono y correo autorizados.
- Enlace oficial.
- Preguntas frecuentes relacionadas.

Distingue claramente la información orientativa de los trámites que se realizan en otra plataforma. No ofrezcas asesoría jurídica personalizada.

### 5.5 Directorio de sedes

Implementa un directorio administrable que pueda presentar:

- Nombre de la sede.
- Dirección.
- Provincia/distrito o ubicación administrativa.
- Horario de atención.
- Horario de Mesa de Partes, si corresponde.
- Teléfono y correo autorizados.
- Enlace a mapa, cuando esté validado.

Toma la página oficial de sedes como fuente de comprobación: https://www.gob.pe/institucion/csjsanmartin/sedes

No copies el listado de sedes de forma permanente al código fuente. Permite administrarlo desde la base de datos. Verifica las direcciones y horarios antes de publicarlos; deben poder actualizarse sin desplegar una nueva versión.

### 5.6 Orientación y preguntas frecuentes

Incluye una sección de preguntas frecuentes administrable. Debe explicar cómo localizar servicios, dónde realizar trámites, cómo acceder a plataformas oficiales y cómo contactar a la institución, usando solo información confirmada.

No simules respuestas oficiales de un chatbot. Si se incorpora un chatbot en el futuro, deberá contar con autorización, límites claros y un aviso de que no sustituye los canales oficiales.

### 5.7 Enlaces institucionales

Incluye enlaces comprobados a:

- Portal oficial de la Corte en Gob.pe: https://www.gob.pe/csjsanmartin
- Trámites y servicios: https://www.gob.pe/institucion/csjsanmartin/tramites-y-servicios
- Sedes: https://www.gob.pe/institucion/csjsanmartin/sedes
- Contacto: https://www.gob.pe/institucion/csjsanmartin/contacto-y-numeros-de-emergencias
- Página de referencia visual de Arequipa: https://csjarequipa.pj.gob.pe/main/servicios-judiciales/

La página de Arequipa es referencia de diseño, no un destino que deba aparecer como servicio de San Martín salvo que exista una razón institucional. Revalida los enlaces antes de la publicación final.

## 6. Panel de administración privado

El panel es un requisito del proyecto. Debe estar protegido y ser independiente de la interfaz pública.

### Funcionalidades

- Inicio y cierre de sesión.
- Dashboard sencillo con resumen de contenidos.
- Crear, editar, ordenar, destacar, publicar, despublicar y archivar servicios.
- Gestionar categorías.
- Gestionar sedes, direcciones, horarios y canales de contacto.
- Gestionar preguntas frecuentes.
- Editar enlaces institucionales.
- Previsualizar contenido antes de publicarlo, si es viable.
- Buscar y filtrar registros.
- Mostrar quién creó o modificó un registro y cuándo.
- Registrar un historial de cambios administrativos.
- Permitir la edición de textos sin modificar archivos PHP.

### Roles

Implementa al menos dos roles:

- **Administrador:** administra usuarios y toda la configuración del portal.
- **Editor de contenidos:** gestiona servicios, sedes y preguntas frecuentes, pero no administra usuarios ni parámetros sensibles.

Aplica autorización en el servidor a cada ruta y operación. Ocultar un botón en la interfaz no constituye control de acceso.

### Cuenta inicial

No codifiques una contraseña predeterminada ni credenciales permanentes. Proporciona un procedimiento seguro para crear al primer administrador durante la instalación, con una contraseña robusta y cambio inicial cuando sea necesario. No incluyas credenciales reales en documentación, repositorio ni ZIP.

## 7. Modelo de datos MySQL/MariaDB

Diseña un esquema normalizado y documentado. Como mínimo, evalúa estas tablas:

- `users`: usuarios administrativos, hash de contraseña, rol, estado y fechas.
- `categories`: categorías de servicios.
- `services`: datos de cada servicio, destino, estado, orden y metadatos.
- `service_keywords`: palabras clave si se requiere búsqueda avanzada.
- `branches`: sedes y datos de ubicación.
- `faqs`: preguntas frecuentes y respuestas.
- `site_settings`: configuración editable no secreta del portal.
- `audit_logs`: registro de cambios administrativos.

Define claves primarias, claves foráneas, índices, restricciones y codificación `utf8mb4`. Añade migraciones o un archivo SQL reproducible. No guardes información de expedientes judiciales ni datos sensibles de los ciudadanos en esta base de datos.

## 8. Seguridad obligatoria

Aplica controles apropiados para una aplicación pública institucional:

- PDO y consultas preparadas.
- Validación de datos en el servidor.
- Escape de salida para evitar XSS.
- Protección CSRF en formularios y operaciones que modifican datos.
- Contraseñas con `password_hash()` y verificación con `password_verify()`.
- Regeneración del identificador de sesión al iniciar sesión.
- Cookies `HttpOnly`, `Secure` en HTTPS y `SameSite` apropiado.
- Cierre de sesión seguro.
- Límites y demoras ante intentos de autenticación repetidos.
- Control de acceso basado en roles.
- Validación de URLs permitidas y uso de HTTPS para destinos externos cuando corresponda.
- Manejo de errores sin mostrar rutas, consultas SQL, credenciales ni trazas al visitante.
- Logs privados, con acceso restringido y sin registrar contraseñas ni datos innecesarios.
- Cabeceras de seguridad adecuadas al hosting.
- Protección de directorios sensibles y desactivación de listado de archivos.
- Copias de seguridad de base de datos y procedimiento documentado de restauración.
- Actualización periódica de PHP y dependencias.
- No almacenar credenciales de casillas electrónicas ni de plataformas oficiales externas.
- No crear formularios que recojan información judicial sensible sin aprobación institucional y evaluación de seguridad.

La configuración de producción debe forzar HTTPS cuando el certificado esté correctamente instalado. No afirmes que el sistema es seguro solo por aplicar estas medidas: documenta y ejecuta pruebas.

## 9. Diseño adaptable, accesibilidad y rendimiento

- Diseña primero para móviles.
- Usa componentes Bootstrap con personalización institucional propia.
- Mantén contraste adecuado, foco visible y navegación por teclado.
- Etiqueta formularios y botones de forma comprensible.
- Usa HTML semántico, jerarquía correcta de encabezados y textos alternativos.
- Apunta a WCAG 2.2 AA, documentando cualquier limitación.
- Evita animaciones innecesarias y respeta `prefers-reduced-motion`.
- Optimiza imágenes; utiliza formatos modernos cuando el servidor y los navegadores lo permitan.
- Usa carga diferida para imágenes fuera de pantalla.
- Minimiza dependencias y peticiones externas.
- Implementa paginación si el catálogo crece.
- Añade metadatos, títulos únicos, descripción SEO, favicon autorizado y sitemap cuando corresponda.
- No uses datos personales de usuarios para analítica sin autorización y revisión de privacidad.

## 10. Hosting pendiente: diseño para facilitar la contratación

El hosting aún debe contratarse o coordinarse. Por ello, **no supongas que ya existe dominio, subdominio, base de datos o acceso a cPanel**.

Prepara una lista de requisitos para que el área de informática o el proveedor confirme:

- Versión PHP compatible y soporte de extensiones necesarias.
- MySQL/MariaDB y límites de bases de datos.
- Certificado SSL/TLS.
- Posibilidad de configurar el document root.
- Acceso a cPanel y administrador de archivos/SFTP.
- Límites de espacio, tráfico, procesos y memoria.
- Copias de seguridad y política de restauración.
- Disponibilidad de logs.
- Política institucional para alojamiento, acceso y tratamiento de datos.
- Posibilidad de usar un dominio o subdominio aprobado por la institución.

No recomiendes publicar el portal institucional en una cuenta personal o alojamiento no autorizado. El área competente debe aprobar dominio, proveedor, seguridad y publicación.

## 11. Instalación y despliegue en cPanel

Prepara instrucciones paso a paso para:

1. Confirmar requisitos del hosting.
2. Crear la base de datos y un usuario con permisos mínimos.
3. Configurar variables de entorno o archivo de configuración protegido.
4. Cargar los archivos mediante SFTP o Administrador de archivos.
5. Ubicar la aplicación de forma que el código privado no quede expuesto.
6. Ejecutar migraciones o importar el SQL inicial.
7. Crear la cuenta de administrador mediante un procedimiento seguro.
8. Configurar HTTPS y redirección a HTTPS.
9. Verificar permisos de archivos y carpetas.
10. Revisar errores, logs y enlaces.
11. Ejecutar pruebas de aceptación.
12. Configurar copias de seguridad y documentar la restauración.
13. Definir cómo actualizar la aplicación sin perder datos.

Incluye una alternativa de instalación para el caso de que el hosting no permita establecer `public/` como raíz del dominio. Esta alternativa debe proteger los archivos sensibles; no resuelvas el problema copiando todo sin control dentro de `public_html`.

## 12. Plan de implementación por fases

### Fase 1: descubrimiento y diseño
- Revisar las páginas de referencia.
- Confirmar los requisitos del hosting.
- Definir mapa del sitio y categorías.
- Presentar wireframe y propuesta visual.
- Identificar datos y enlaces pendientes de validación.

### Fase 2: prototipo visual
- Crear cabecera, inicio, buscador, tarjetas, catálogo, ficha de servicio y pie de página.
- Integrar el logotipo proporcionado.
- Aplicar colores configurables y diseño responsivo.
- Revisar la interfaz con datos de ejemplo claramente marcados.

### Fase 3: backend y base de datos
- Implementar conexión segura.
- Crear esquema y migraciones.
- Desarrollar catálogo y directorio dinámicos.
- Implementar búsqueda, filtros y detalle de servicio.

### Fase 4: panel administrativo
- Autenticación y roles.
- CRUD de servicios, categorías, sedes, preguntas frecuentes y enlaces.
- Validaciones, historial de cambios y controles de acceso.

### Fase 5: seguridad y calidad
- Probar validación, autorización, CSRF, XSS, sesiones y errores.
- Revisar accesibilidad, rendimiento y compatibilidad con dispositivos.
- Verificar enlaces y textos institucionales.
- Resolver errores antes de producción.

### Fase 6: despliegue
- Instalar en un entorno aprobado.
- Configurar HTTPS.
- Realizar pruebas de aceptación.
- Entregar manual técnico y guía de administración.
- Obtener aprobación institucional antes de publicar.

No inventes plazos ni afirmes que una fase se ha completado si no se ha realizado.

## 13. Pruebas y criterios de aceptación

Antes de declarar el proyecto terminado, verifica y documenta:

1. La página pública carga sin errores.
2. El diseño funciona en escritorio, tablet y móvil.
3. El logotipo mantiene proporciones y colores originales.
4. La búsqueda, filtros y navegación funcionan.
5. Los servicios sin URL validada no se publican como enlaces activos.
6. El panel requiere autenticación.
7. Los roles limitan correctamente las operaciones.
8. Los formularios están protegidos contra CSRF y validan los datos en el servidor.
9. Las consultas usan parámetros preparados.
10. No se exponen archivos de configuración ni logs.
11. Los enlaces oficiales han sido revisados.
12. Los errores no muestran información interna.
13. La instalación se ha probado con la versión real de PHP y la base de datos del hosting.
14. Existe una guía de copia de seguridad y restauración.
15. Se han documentado los problemas pendientes.

No afirmes que se superaron pruebas que no se ejecutaron. Si no se dispone aún del hosting, distingue claramente entre pruebas locales y pruebas pendientes en el servidor de destino.

## 14. Entregables obligatorios

Entrega:

- Proyecto completo con código fuente.
- Archivo ZIP de despliegue para cPanel.
- Script SQL o migraciones.
- `.env.example` sin secretos.
- Guía `README.md` de instalación.
- Manual de administración de contenidos.
- Manual de actualización y mantenimiento.
- Lista de enlaces que requieren validación institucional.
- Lista de requisitos del hosting.
- Lista de pruebas realizadas y pendientes.

No incluyas credenciales reales, datos personales, copias de seguridad, archivos temporales ni secretos dentro del paquete.

## 15. Instrucción final al asistente de programación

Comienza por analizar las referencias y proponer el mapa del sitio, el diseño de la página de inicio, el esquema de datos y la arquitectura compatible con cPanel. Señala las decisiones que dependen del hosting o de la aprobación institucional.

Después desarrolla el portal por fases, entregando código completo y archivos reales. Usa el logotipo proporcionado y una paleta institucional pendiente de validación oficial. Mantén los enlaces desconocidos como pendientes; no inventes URLs ni información de contacto. Prioriza seguridad, accesibilidad, facilidad de mantenimiento y claridad para la ciudadanía.

**Resultado final:** un portal independiente de Servicios Judiciales de la Corte Superior de Justicia de San Martín, con catálogo administrable, panel privado, enlaces oficiales verificados y paquete documentado para desplegar en un hosting cPanel aprobado por la institución.
