# Home y estructura global — 2026-09-21

## Fuente visual y alcance

Referencia de código: `legacy/public/index.php` (dinámico), `index-estatico.php`, `header.php`/`headerv1.php`, `footer.php`/`footerv1.php`, `iconos-marcas.php`/`iconos-marcasv1.php`, CSS custom/marketing/business/style/responsive y configuración Swiper inline. Comparación en navegador contra **index-estatico.php**, variante ejecutable sin consultar el CMS/BD legacy. No se ejecutó el index dinámico ni se consultó una BD remota.

Composición comprobada, de arriba abajo:

1. Header transparente con logo blanco y navegación; logo oscuro y menú desplegable móvil.
2. Cuatro fotografías: banner1.jpg, banner2.jpg, banner3.jpeg, banner4.jpg. Altura efectiva del slider 365px, texto30/44.8, CTA rojo con círculo blanco, cuatro indicadores. Swiper legacy: slide/parallax, 1000ms, autoplay4000ms, navegación por teclado; flechas laterales comentadas.
3. Marcas, subtítulo y doce logos en seis columnas desktop/dos filas.
4. “Conoce nuestros Productos”.
5. Siete familias: Industriales, Comercial, Andén de Carga, Salida de Emergencia, Incendio/Explosión/Blindadas, Hospitales y Residenciales. Texto/listas y fotografías alternadas; fondos azul claro #f0f4fd y patrón home-bg-01. Títulos45/50, secciones120px de padding desktop.
6. Acerca de nosotros con quienes1.jpg, descripción comercial existente y enlaces.
7. CTA rojo superpuesto al footer azul, logo/descripción, Soluciones y Contacto.

No hay bloques adicionales de testimonios, noticias o métricas que reconstruir. Home no reutiliza la card del archivo porque el legacy presenta familias con fotografías grandes, no un grid de fichas individuales.

## Implementación

- `global.css`: estilos compartidos extraídos de catalog.css conservando tokens/declaraciones; header, footer, fuentes y contenedores. `catalog.css` conserva únicamente presentación de catálogo. `home.css` y `home.js` se cargan sólo en Home. Sin segundo sistema visual.
- Header/footer existentes se usan en Home, Pages, catálogo, productos, taxonomías y404. Páginas sin hero tienen cabecera azul independiente para que el menú blanco siga legible. Ninguna página institucional se reconstruyó.
- Misma navegación accesible ya aprobada, sin segundo sistema. Inicio usa aria-current en Home. Destinos mediante APIs WP; datos y política pública no cambian.
- `inc/home-data.php`: composición histórica comprobada y dimensiones de assets; `home-presentation.php`: imágenes responsive y consulta pública de destinos de familia. No leen CSV/SQL/legacy en runtime; no guardan ni crean entidades editoriales.
- Slider vanilla con controles nativos, teclado, indicadores, swipe horizontal, pausa explícita. Se detiene con foco/hover, pestaña oculta o fuera de viewport; reduced-motion desactiva autoplay y transición, mantiene selección manual. Sin JS muestra la primera diapositiva y CTA; navegación global sigue operable sin JS.
- H1 único para la empresa, H2 de secciones, H3 de familias; landmarks y alt/dimensiones. Primera imagen eager/fetchpriority high; demás imágenes lazy. Inter local. No se configura SEO final.
- 32 derivados WebP conservan fotografías/logos originales. Hero con variantes640/1280/1920 cuando el original permite; no se amplían fuentes pequeñas. Primer hero: fuente338KB →202KB a1920 /32KB a640. [Procedencia, SHA-256 y derivados](home-asset-provenance.json). No se modifica legacy ni Media Library.

## Diferencias deliberadas y pendientes

- Se eliminan parallax y animaciones de entrada que ocultan texto temporalmente. Transición corta de opacidad en lugar de desplazamiento/parallax; se añade pausa accesible discreta. Sin margen corporal negativo ni errores móviles del legacy que ocultaban logos. Móvil: logos2 columnas, tablet3, familias apiladas, hero390px para texto/controles sin colisión.
- Textos históricos de familias se muestran como texto, no como enlaces a términos review. “Ver todos” usa la familia si es pública; por ahora va al catálogo general con nombre accesible explícito. CTAs “Ver más” del legacy que tenían #/soluciones.php llevan al catálogo real. No se interpretan como aprobación editorial.
- Los doce logos son **contenido visual histórico del Home**, no asociaciones producto/marca ni términos publicados. Sin enlaces a marcas review. El index dinámico usa listas de BD; las listas visibles se conservan desde la variante estática demostrada. No se intenta reconciliar sus diferencias con SQL.
- Se corrigen únicamente errores de encoding/puntuación en la copia de texto estático del theme; ningún contenido almacenado se modifica.
- “Conócenos” aparece cuando Nosotros esté publicado; hoy se conserva descripción y “Nuestros servicios”. Contacto, privacidad y nombres de familias pendientes se rigen por disponibilidad pública, sin inventar páginas.
- Footer completa la descripción comercial demostrada. Contactos proceden de Settings existentes, no se sustituyen silenciosamente por datos legacy. Confirmar contactos definitivos con cliente.
- **WhatsApp pendiente**: footer.php y footerv1.php contienen destinos distintos. La configuración WordPress no ofrece destino habilitado. No se eligió número ni se añadió botón sin destino comprobado. Privacidad/contacto como páginas siguen pendientes de la siguiente fase.

## Validación

- **111/111 anteriores PASS**, sin modificar sus asserts.
- **33/33 Home/global PASS**: HTTP200, landmarks/H1 único, cuatro slides, doce logos, siete familias, assets y srcset, CTA/URLs reales, footer, CSS/JS por pantalla, Page existente renderizada sólo en memoria, ausencia de errores/encoding roto, hashes de tablas de contenido sin cambios.
- **11/11 slider PASS** con Node estándar: autoplay, manual, teclado circular/foco, pausa, reduced-motion inicial/dinámico, foco/hover, visibilidad y swipe horizontal/vertical. Total **155/155**.
- Navegador real: desktop1440/1280/1024, tablet768, móvil375/320. Sin overflow horizontal ni imágenes rotas observadas. Comparación desktop con hero/logos/familias del legacy. Slider por teclado y foco, menú móvil/Soluciones/Escape, CTA y footer revisados. Swipe/reduced-motion cubiertos con pruebas de eventos del script, no se afirma prueba en hardware táctil.
- Post1371 sigue publish. Las tablas posts/postmeta/terms/termmeta/term_taxonomy/term_relationships conservan hashes. No hay escrituras de contenido, importaciones, aprobaciones, cambios en plugin ni eliminación de attachments.

Sintaxis: 34 archivos PHP correctos en PHP 8.4 y ambos scripts frontend válidos. Baseline: 3.862 archivos legacy idénticos, cero extras. Los hashes de 25 fuentes y sus 32 derivados coinciden con el manifiesto.

Checkpoints b7f49e6 y2f67987 intactos. Sin push. Próxima fase: Nosotros, Contacto y privacidad; confirmar datos de contacto/WhatsApp. No son bloqueos técnicos de esta entrega.
