# Navegación del catálogo — 2026-09-21

Se conserva el diseño aprobado. Cambios limitados a enlaces, interacción y accesibilidad; no se alteran datos WordPress ni el plugin/importador.

## Destinos y evidencia

- `legacy/public/header.php:71–137`: Industrial, Comercial, Andén, Salida de emergencia, puertas contra incendio/explosión/blindadas, Hospitales, Residencial. Marcas está comentado en este header; sí existe en `links-menu.php`. `headerv1.php` obtiene categorías padre de MySQL.
- La presentación usa `wp_nav_menu()` para menús asignados. Sin menú asignado, `inc/catalog-navigation.php` obtiene términos y enlaces mediante `get_terms()`, `get_term_link()` y `get_post_type_archive_link()`; nombres desde WordPress. Orden legacy para slugs inequívocos; ningún destino absoluto hardcodeado. Se conservan iconos equivalentes y filas completas clickeables.
- Estado real local: **34 categorías y 12 marcas, todas review**, cero menús asignados. `TermPolicy` existente devuelve 404 para esos términos. Se omiten deliberadamente, incluso en sesiones administrativas: no se cambia ni elude la política. Hoy Soluciones/sidebar/footer muestran **Productos**. Al aprobar términos aparecen automáticamente. No se inventa un archivo global de marcas: cada marca pública enlaza a su propia taxonomía. El sidebar de marca muestra marcas y catálogo; categorías muestran familias públicas y el término actual cuando es hijo.
- Industrial, Comercial, Andén, Emergencia, Incendio/Explosión/Blindadas, Hospitales y Residenciales existen como términos pero no como destinos públicos. No se sustituyen por categorías parecidas. Los textos con codificación defectuosa se dejan para revisión editorial.
- Nosotros sigue borrador; Contacto y privacidad no tienen página pública. Contacto utiliza el correo configurado como alternativa funcional `mailto:`. Footer conserva teléfono/email de Settings; privacidad se muestra sólo cuando `get_privacy_policy_url()` devuelve una URL. No se crean páginas ni se cambian opciones. Datos de contacto definitivos requieren confirmación del cliente.
- WhatsApp pendiente: `footer.php:1` y `footerv1.php:1` usan números distintos; configuración WordPress desactivada/sin destino utilizable. No se eligió uno arbitrariamente.

## Interacción

Soluciones conserva un enlace directo al catálogo y un botón independiente para desplegar. JS crea el botón con nombre accesible, `aria-controls`, `aria-expanded`; mouse hover, click/touch, Enter/Espacio, flecha abajo, Tab, Escape, cierre al salir del foco/clic exterior. Escape devuelve foco al botón; en móvil otro Escape cierra el menú principal. Los enlaces existen en HTML servidor. Sin JS, desktop permite hover/focus-within y móvil muestra los enlaces sin colapsar. Estado exacto del sidebar: `is-active` y `aria-current="page"`, sin marcar padres como página actual.

## Validación

- **111/111** pruebas frontend de lectura: siete productos reales, Grupo A/B, PDF real/asset UI, imágenes, taxonomías, URLs nativas, sidebar sin `#`, estado activo, exclusión review/borradores en navegación y hash de tablas de contenido sin cambios. [Resultado estructurado](catalog-test-results.json).
- **31 archivos PHP**: sintaxis correcta en PHP 8.4; `node --check` correcto; diff-check limpio.
- Navegador: 1440/1280 desktop, 768 tablet, 375 móvil; sin overflow horizontal. Apertura por click y teclado, flecha abajo/Tab/Escape, foco visible, menú móvil, enlace a catálogo, sidebar→catálogo→producto publicado 1371. Click PDF verdadero; comprobación HTTP 200/application/pdf también automatizada.
- Categoría y marca: previews estáticos fuera de WordPress con aprobación exclusivamente en memoria, estado activo y enlaces nativos comprobados. Seguir esos enlaces en el WordPress real confirma el **404 editorial esperado**. No se afirma recorrido público completo de taxonomías mientras sigan review.
- Preview sin scripts: cero etiquetas script, enlaces disponibles por Tab en desktop y visibles a 375px. Sin errores JS observados en el catálogo público.
- **3.862/3.862** archivos legacy coinciden con SHA-256 baseline, sin extras. Post 1371 permanece publish. No se ejecutó importación ni se cambiaron relaciones/medios/contenido.

Checkpoint migración `b7f49e6` intacto. Frontend completo preparado para el commit autorizado `feat: rebuild legacy product catalog frontend`. No push. Home y páginas institucionales no abordadas. La deuda editorial de tests del importador continúa fuera de este alcance.
