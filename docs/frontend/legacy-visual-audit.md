# Referencia visual legacy → WordPress

2026-09-21. Alcance: ficha, catálogo, categorías y marcas. Checkpoint de migración `b7f49e6` intacto. El frontend debe preservar con alta fidelidad la identidad visual, estructura y composición legacy. Modernizar no significa rediseñar; cambios visuales significativos requieren justificación y aprobación previa.

## Fuentes y medidas

Referencia ejecutada en navegador local: `puerta-seccional-de-acero-thermacore-594-uso-medio.php`, `puertas-seccionales-industriales.php`, `overhead-door.php`. Inspección adicional: `cortina-en-aluminio-serie-511-521.php`, `industrial.php`, `productos.php`, `header.php`, `headerv1.php`, `footer.php`, `footerv1.php`, `menu-seccionales.php`, `menu-soluciones.php`, `menu-marcas.php`, `links-menu.php`, `links-menu-marcas.php`, variantes v1 y `system/view/ProductosView.php`. `CategoriasView.php` y `MarcasView.php` sólo muestran debug: NO son la referencia visual de los listados públicos.

CSS efectivo, en orden: vendors/Bootstrap, icon, style, responsive, marketing, business, custom y overrides inline. `marketing.css` sustituye ambas fuentes por **Inter**, azul oscuro por **#00394f**, acento por **#e41e25**. `business.css` aporta el degradado; `custom.css` controla banner, logo, tarjetas y PDF. No se importan estos paquetes completos.

Medición desktop 1440: contenedor 1220 px; cuerpo 16/30 px; título principal 44.8/44.8 px, 700; rótulo superior 17/20 px, 500, tracking 2 px, blanco al 60%. Hero visible ≈380 px (legacy obtiene 410 menos un margen negativo de 30); contenido empieza a y≈470. Sidebar ocupa 1/3, contenido 2/3; dentro de la ficha dos mitades, texto primero. PDF mide ≈141 px (18% del área principal), alineado a la derecha. Logo blanco 95 px de alto. No hay breadcrumb general.

Variante importante: `puertas-seccionales-industriales.php` usa `small-screen` de 400 px, no `small-screen-int` de 300; su hero desktop visible es 480 px. El archivo de categoría conserva esa altura. La marca y ficha conservan 380 px. En los listados legacy no hay un gran logo/imagen del término antes de las cards: se retiró esa inserción de presentación, sin tocar term meta; los logos siguen disponibles en la relación de marca del single.

## Mapa de reconstrucción

| Elemento | Fuente legacy / assets | Comportamiento | WordPress existente y cambio necesario |
|---|---|---|---|
| Ancho | responsive.css + Bootstrap | 1220 ≥1400; 1140 ≥1200; 960 ≥992; 720 ≥768 | Reemplazar shell 1120/92% del catálogo por esos tokens y gutters de 15/30 px |
| Header | header.php, logo-white@2x / logo-black-big@2x | Transparente sobre hero, blanco desktop, logo oscuro móvil | Cabecera mínima de catálogo con logos originales; menú WP. No cerrar navegación global ni tocar Home |
| Hero | fichas/listados; banner1.jpg, business.css | Fotografía cover, overlay 75%, título centrado | Quitar bloque moderno de gradiente plano y enlace superior; recuperar fondo y proporciones |
| Títulos | h1/h2 animados por main.js | Rótulo de sección pequeño y título grande | Texto servidor visible sin JS; H1 único para nombre real WP. No dividir/inventar títulos editoriales |
| Sidebar | menu-* → links-menu*; Feather | Separadores, iconos finos, sticky, CTA azul/rojo | Componente reutilizable; enlaces sólo a destinos WP públicos. Rótulos de navegación legacy sin destino se presentan como texto, no links falsos |
| Descripción | ficha estática / ProductosView | Izquierda de imágenes, 16/30, párrafos 25px | Mover columna; conservar contenido almacenado íntegro |
| Galería | col-md-6 de fichas | Imágenes naturales apiladas, sin visor demostrado | Mantener APIs/srcset y ampliación accesible; quitar cajas, bordes y mosaico del intento anterior |
| PDF | images/verficha.png | Botón pequeño arriba/derecha → PDF | Mantener componente y attachment IDs; sólo ajustar tamaño/alineación; varios documentos con etiquetas distinguibles |
| Tablas | cuerpos de fichas | Tablas HTML o imágenes técnicas | Conservar contenido; scroll local y teclado, sin overflow de página |
| Categorías/marcas | textos hero y menús | No hay chips grandes modernos | Metadatos discretos; nunca inferir ni aprobar relaciones para lograr paridad |
| Cards | shop-boxed/shop-image/shop-footer | 3 columnas junto a sidebar, foto 187px, título 13/16 semibold centrado, borde blanco6px; hover sombra/flecha | Reutilizar product-card; quitar caja gris, imagen220px y título grande |
| Catálogo general | industrial.php vs listados de marca/categoría | Industrial usa 4 columnas de categorías; marca/categoría usan 3 de productos | Archivo de productos reutiliza familia de listado de productos con sidebar; no convertirlo en página Industrial |
| Footer | footer.php + demo-marketing-footer-dot.svg | Fondo azul oscuro, logo, CTA roja superpuesta, tres columnas | Marco mínimo sólo para catálogo; destino/contacto desde WP. Composición global completa diferida |

## Assets y dependencias

- Identidad vigente: logo blanco/oscuro y banner1.jpg; se copian exactamente al theme.
- UI reutilizable: verficha.png (ya canónico), patrón de puntos del footer y fuente de iconos Feather con sólo los glifos utilizados declarados en CSS.
- Fuente Inter: misma familia ya cargada por legacy; versión WOFF2 latina alojada localmente, licencia OFL incluida. Sin Google Fonts en runtime.
- Contenido: fotos, logos de marcas, PDFs, videos siguen en Media Library. No se copian al theme.
- No reutilizados en esta fase: banners Home, sprites/recurso de backoffice, loaders, cursor personalizado, paquetes Bootstrap/jQuery/Isotope/Swiper, animación de entrada y títulos. Son ajenos al alcance o reemplazados por CSS/HTML; no se declara que todos estén obsoletos globalmente.

## Límites deliberados

No se cambian títulos, marcas, categorías, borradores, REVIEW ni SKIP para igualar el corpus legacy. La política existente oculta términos pendientes y mantiene borradores fuera del catálogo público. Por eso cantidad/orden/texto pueden diferir. No se inventan URLs .php ni destinos para navegación pendiente. Header/footer son un marco mínimo de referencia, no un cierre de su fase global.

Las mejoras autorizadas son semántica, foco visible, texto sin dependencia JS, Grid/Flex, imágenes responsive, un solo enlace accesible por card y apilado móvil. No se busca reproducir bugs como margen corporal negativo, tipografía raíz móvil de 12px o imágenes forzadas a 82px.

## Resultado y validación

- Se conservó la arquitectura del trabajo anterior: helpers de presentación, componentes PDF/card/terms/video, templates single/archive/taxonomy y pruebas. Se cambió su composición y CSS, no el modelo ni el importador.
- Tokens medidos en WordPress a 1440: contenedor **1220**, inicio de contenido single **y=470**, texto **x≈524**, imágenes **x≈931**, botón **141 px**, cuerpo **16/30 Inter**. Coinciden con la estructura medida en la ficha legacy. Categoría: hero **480 px**; cards **187 px** de imagen y títulos **13/16, 600**.
- Comparación visual en el mismo navegador/viewport con las fichas y los listados legacy citados. Desktop ancho 1440 y normal 1280; tablet 768; móvil 375; comprobación adicional de tabla a 320. Diez vistas comprobadas a 1280/768/375: sin overflow de página ni imágenes rotas. Menú móvil probado con clic y Enter; tablas con foco y scroll propio. No se afirma pixel-perfect.
- **83/83 comprobaciones de lectura PASS**, 30 archivos PHP sin errores de sintaxis, JavaScript válido. [Resultados](catalog-test-results.json). La prueba compara hashes de todas las tablas de posts, metadatos y taxonomías antes/después: sin cambios. Post 1371 continúa publicado. No se ejecutó el importador ni se reabrió su deuda editorial.
- Casos: 1371 (publicado, Grupo A, sin marca), 1360 (Grupo B/una imagen/PDF), 1354 (varias imágenes/PDFs), 1359 (multicategoría/tabla), 133 (Q04), 1171 (sin imágenes/PDF), 1110 (seis imágenes). Las aprobaciones de términos para probar el aspecto se simulan sólo en memoria dentro del proceso CLI; no se guardan.
- Assets: [procedencia y SHA-256](asset-provenance.json), seis copias idénticas al legacy incluido el botón previamente existente. **3.862/3.862 archivos legacy idénticos** al baseline, sin archivos extra.

### Revisar visualmente

1. Ficha publicada: `http://localhost/psindustrial-wordpress/wordpress/producto/puerta-seccional-de-acero-thermacore-594-uso-medio/`.
2. Catálogo público: `http://localhost/psindustrial-wordpress/wordpress/productos/` (sólo publicaciones reales).
3. Previews estáticos locales: `http://127.0.0.1:8766/catalog.html`, `psi_categoria.html`, `psi_marca.html`, `product-1354.html`. Servidor ligado únicamente a loopback y archivos fuera del repositorio, en `C:/laragon/tmp/psi-catalog-preview`. Permiten revisar borradores sin publicarlos. Sus enlaces a borradores/términos/paginación no constituyen rutas públicas habilitadas.

Reproducir previews: ejecutar `tests/product-catalog.php` del theme con PHP 8.4 y un directorio privado de salida, después servirlo sólo en `127.0.0.1`. Sin ese argumento, la prueba no exporta HTML. Los previews son evidencia de presentación, no una modificación de permisos.

Pendiente de aprobación visual: proporción del banner y sidebar, columnas/fotos del single, densidad de cards, adaptación móvil. Diferencias inevitables actuales: nombres completos WP frente a títulos legacy divididos, corpus/orden según publicaciones y relaciones existentes, términos pendientes ocultos, navegación todavía sin destinos públicos y contactos provenientes de la configuración actual. No se sustituyen por datos legacy. Footer mínimo sin todos los enlaces/textos históricos y sin cierre global; Home intacto. No hay commit de frontend ni push.

## Aprobación y navegación posterior (2026-09-21)

El usuario aprobó la apariencia general. No se rediseñaron single, catálogo, sidebar ni footer. Se completaron navegación y enlaces respetando las restricciones públicas de los términos. La validación vigente es **111/111**, descrita en [Navegación y destinos pendientes](navigation-validation.md); reemplaza los pendientes de navegación y la cifra de pruebas de la revisión visual anterior. La aprobación editorial de destinos sigue pendiente. El frontend se integra mediante el commit expresamente autorizado, sin push.

## Estructura global y Home (2026-09-21)

El sistema visual aprobado se comparte ahora globalmente sin cambiar la composición del catálogo. Home y validación nueva se documentan en [Auditoría Home/global](home-global-audit.md). Los assets históricos de portada son una excepción explícitamente autorizada a la separación anterior de contenido/Media Library: sólo reproducen la composición visual demostrada, sin modificar taxonomías ni asociaciones editoriales.
