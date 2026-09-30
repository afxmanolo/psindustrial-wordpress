# Media Library y fichas técnicas

## Selección antes de importar

La fuente es media-master + media-usage-evidence + product-media-relations.1.355 filas file resuelven a820 rutas; el inventario ampliado tiene1.528 archivos locales y3 embeds.612 archivos tienen referencia documental; no todos son fotos de producto: hay decoración, fuentes, borradores y reglas CSS no aplicadas. Nunca importar las1.355 filas como attachments distintos.

| Estado de medio | Criterio |
|---|---|
| MIGRATE | Original válido, propósito/propietario aprobado y uso de contenido público confirmado; logos y PDFs revisados |
| REVIEW | Referencia sólo inferida, borrador/test, MIME desconocido, recurso ausente, derivado con posible URL externa, duplicado sin identidad aprobada |
| SKIP | Derivado regenerable sin URL/uso que preservar, recurso exclusivo del CMS descartado o artefacto técnico confirmado; exige motivo y aprobación, no borrar fuente |

Fuentes, iconos y decoración fija pertenecen al paquete de assets del tema si su uso se demuestra, no necesariamente a Media Library. Fotos, logos, banners editables y PDFs sí son attachments. Videos YouTube quedan como metadata; no attachments vacíos ni descargas del servicio.

## Identidad y almacenamiento

Un attachment por original seleccionado/bytes aprobados, con clave estable del manifest, SHA-256, MIME comprobado y tamaño. Dos registros con mismo hash pueden compartir attachment si también coinciden propósito, restricciones y política de actualización; no deduplicar por filename. Alt contextual puede variar en bloques/referencias aunque el binario se comparta. No derivar alt de cualquier nombre técnico.

259 originales sin extensión:143 JPEG,44 PNG,72 PDF identificados. Copia de importación con extensión real verificada, nombre saneado y trazable; fuente intacta. MIME/extensión comprobados con Fileinfo y APIs WordPress; no confiar sólo en type del dump. Los12 TTF/EOT UNKNOWN no se importan como PDF/imagen. SVG se convierte a formato seguro o revisa/sanea fuera del upload del gestor; no permitir SVG arbitrario.

Uploads administrados por WordPress, subdirectorios por año/mes estándar. Se almacenan IDs y rutas relativas, nunca hostname de staging en contenido nuevo. `_psi_source_keys`, `_psi_content_sha256` y `_psi_original_name` privados completan trazabilidad. Derivados pequeños/medianos/grandes se generan desde el original; una URL legacy de derivado todavía requerida se conserva aparte, aunque ya no sea un attachment separado.

APIs previstas: [media_handle_sideload](https://developer.wordpress.org/reference/functions/media_handle_sideload/) y [wp_check_filetype_and_ext](https://developer.wordpress.org/reference/functions/wp_check_filetype_and_ext/). El importador no descarga URLs arbitrarias ni abre rutas proporcionadas sin lista permitida.

## Contrato específico de PDFs

159 IDs SQL asociados →72 rutas originales;76 rutas estáticas localizadas referenciadas +2 referencias no resueltas.148 rutas asociadas localizadas son74 hashes PDF, no148 documentos técnicos diferentes.

PDF = attachment application/pdf. Producto guarda lista ordenada {attachment_id,label,language}; el archivo puede pertenecer a varios productos. No usar attachment.parent como relación exclusiva. Título descriptivo y nombre saneado al importar (conservar nombre original en meta). No sustituir un PDF basándose sólo en modelo/nombre: ICARO/LUX exige decisión.

Frontend: enlace accesible con título y tamaño, indicador PDF y apertura normal/nueva pestaña anunciada si se mantiene UX. Vista inline y descarga opcional conservan intención; no visor pesado. Debe funcionar sin JavaScript. Ninguna página HTML de attachment se publica como contenido SEO adicional por defecto.

### Conservación de rutas

1. URLs estáticas /fichas/*.pdf aprobadas: servir **200 con los mismos bytes**, mediante copias de compatibilidad incluidas en un paquete de medios permitido para el nuevo document root. Es conservación selectiva de rutas, no copia del CMS ni modificación de /legacy.
2. /system/file.php?id=…&type=application/pdf: resolver sólo IDs aprobados al attachment y servir localmente con200, MIME/nosniff y Content-Disposition inline o attachment para cmd=download. Sin ejecutar el endpoint viejo ni aceptar una ruta del visitante. HEAD debe devolver cabeceras sin cuerpo; gestionar tamaño/cache y Range si se ofrece.
3. Otras rutas sólo derivadas: INVESTIGATE; no crear todas como rutas públicas por su existencia matemática.
4. Si se decide cambiar URL, requiere entrada aprobada para301 de un salto al archivo final, nunca a una ficha HTML genérica. Aquí no se genera ninguna regla.

PDFs históricos quedan inmutables por defecto: una actualización técnica crea attachment nuevo, sustituye selección del producto y deja las URLs del PDF anterior sirviendo su versión antigua. Reemplazo de bytes en la misma URL sólo con decisión explícita que garantice la misma identidad, backup y purga de cache. No sobreescribir ni eliminar un adjunto compartido desde una sola ficha.

SEO de binarios: preservar indexabilidad conocida y no imponer noindex global a /fichas. Si es UNKNOWN, registrar evaluación; nunca bloquear todos por ser documentos. Canonical HTTP opcional sólo tras equivalencia demostrada; el canonical HTML del producto no controla PDF. No incluir páginas attachment en sitemap; rutas de documentos enlazadas siguen accesibles.

## Recursos problemáticos y controles

images/dura.jpg, images/magic.jpg y FICHATECNICASELLOSSOLMMERS.pdf necesitan recuperación/decisión. INFRACA tiene discrepancia NFC/NFD: registrar bytes de la URL y ruta, no normalizar silenciosamente en Linux. La copia no puede garantizar un200 de un recurso inexistente; cerrar excepción antes de corte o aprobar presentación alternativa sin botón falso.

Bloquear borrado de medios referenciados mediante UI y validación de servidor; sólo Administrator puede borrar, tras informe de referencias en galerías, bloques, términos y rutas. La selección de un nuevo archivo no borra el antiguo. Backups incluyen uploads, copias de compatibilidad y manifest.
