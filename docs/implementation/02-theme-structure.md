# Theme psindustrial
Theme clásico PHP con theme.json; versión 0.1.0. Sin page builder ni assets legacy. Text domain `psindustrial`.

| Ruta relativa al theme | Responsabilidad |
|---|---|
| style.css | Cabecera que identifica el theme |
| functions.php | Carga explícita de setup y assets |
| inc/setup.php | Soportes WordPress, traducciones, menú primary |
| inc/assets.php | CSS reutilizable y JS de navegación diferido |
| theme.json | Ancho de contenido, colores y tamaños básicos del editor |
| assets/css/base.css | Presentación temporal y grid adaptable |
| assets/js/navigation.js | Control progresivo del menú |
| header.php, footer.php | Shell con wp_head, wp_body_open, wp_footer |
| template-parts/site/ | Cabecera y pie compartidos |
| front-page.php | Portada básica sin crear contenido ficticio |
| page.php, templates/page-basic.php | Page y template seleccionable |
| single-psi_producto.php | Ficha: contenido, términos y medios |
| archive-psi_producto.php | Archivo temporal /productos/ |
| taxonomy-psi_categoria.php, taxonomy-psi_marca.php | Archivos de categorías y marcas |
| archive.php | Composición compartida de listados |
| index.php, 404.php | Fallback y página inexistente |
| template-parts/content-page.php | Presentación de Page |
| template-parts/components/ | Grid/cards y medios de producto |

HTML semántico, salto al contenido, labels de navegación y salida escapada. Soportes title-tag, thumbnails, HTML5, editor-styles y responsive embeds. La galería utiliza attachments; los PDFs enlazan al archivo; videos son enlaces YouTube, sin carga de reproductores externos.

El theme no registra CPT, taxonomías, opciones ni persistencia. El plugin puede desactivarse sin fatal en la portada. Diseño temporal, sin paridad visual legacy ni SEO específico; todavía no se desarrollaron heroes, breadcrumbs, colecciones, landings ni componentes finales.
