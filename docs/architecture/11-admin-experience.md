# Experiencia administrativa

## Menú del Gestor de contenidos

Inicio (estado editorial simple), Productos (todos/añadir), Categorías, Marcas, Páginas, Medios, Contacto del sitio y Perfil. SEO se muestra como panel de edición contextual de producto/página/término; no como decenas de submenús de configuración. Administrator conserva administración completa y pantalla privada Migración/Compatibilidad.

Ocultar Entradas, Comentarios, Herramientas, Ajustes generales, Apariencia, Plugins, Usuarios, actualizaciones, salud avanzada, exportaciones y opciones de marketing del proveedor SEO al gestor. Esto simplifica UI; seguridad real son capacidades y validación del servidor, incluidas URLs directas y REST.

Listados de producto: nombre, miniatura, categorías, marca o «sin verificar», estado, revisión, fecha; filtro por categoría/marca/revisión. No columnas de SQL/hashes para el cliente. Acciones masivas acotadas: editar estado/selecciones, nunca merge por similitud. Panel «Pendientes» distingue falta de datos de error bloqueante.

## Flujo de alta

1. Añadir nombre, descripción y resumen con editor restringido.
2. Seleccionar imagen destacada; añadir/reordenar galería desde Media.
3. Seleccionar una o varias categorías; opcional principal para navegación.
4. Elegir una marca verificada o dejar sin marca; no introducir fabricante libre en la ficha.
5. Añadir PDFs desde biblioteca: título, idioma opcional y orden. Previsualizar/enlace de comprobación; MIME incorrecto impide guardar relación.
6. Pegar enlace YouTube si existe; core extrae ID y muestra preview, no solicita iframe.
7. Revisar H1, title/description y vista previa. Publicar sólo si _psi_review_state=approved y validación pasa.

Un producto puede guardarse en borrador incompleto. Marca/categoría desconocidas no bloquean por sí solas publicar contenido aprobado: advertencia y ausencia del bloque correspondiente. Datos contradictorios no se ocultan asignando la primera opción. La revisión editorial debe resolver o aceptar explícitamente la omisión antes del corte.

## Flujos específicos

PDF nuevo: subir → ver validación tipo/tamaño → confirmar título → seleccionar en relación → guardar. Actualización crea nueva versión como attachment distinto y actualiza la asociación; pantalla avisa si otras fichas usan el viejo. No botón «reemplazar todo» ni borrado automático.

Marca: formulario nombre, logo, descripción, orden, H1/SEO y revisión. Logo usa wp.media con filtro imágenes. Preview del destino; enlace derivado del término, no editable como URL libre. Categorías: árbol, nombre/descripcion, imagen, orden; selector de padre evita ciclos, muestra impacto en breadcrumb. Mover un padre requiere autorización Administrator si modifica navegación publicada.

Pages: escoger template editorial/colección/landing/contacto; patrones preservan diseño, cliente cambia contenido. Las secciones globales no se repiten en todas las páginas. Menús principales y rutas son de Administrator.

## Protección contra cambios accidentales

Gestor puede editar productos/Pages/términos públicos y SEO title/description, pero no cambiar rutas publicadas, canonical arbitrario, noindex global, propiedad de fuente, merges, redirects o configuración SMTP. «Retirar» contenido publicado crea solicitud editorial/aviso para Administrator, no elimina URL inmediata. Borrar borradores propios permitido según14; medios compartidos y términos no se borran por gestor.

No se importan vacíos151–164 ni Prueba como catálogo publicable. Se muestran en reporte de migración privado del administrador, no como tareas de alta genéricas para el cliente. La UI no tiene que replicar exportaciones Excel o permisos Super del CMS.
