# Tema psindustrial

Tema propio clásico PHP con editor de bloques, theme.json y patrones acotados; sin page builder ni edición libre del sitio completo para el gestor. Se elige esta composición porque el frontend legacy usa layouts estables, menú lateral y fichas técnicas; un tema de bloques completamente configurable aumenta superficie de diseño sin necesidad demostrada. No se porta Savant ni se copian198 PHP.

El tema presenta datos. Puede registrar tamaños de imagen, ubicaciones de menú, soportes visuales, patrones y estilos. No registra CPT/taxonomías, permisos, rutas, importación, envío de correo ni opciones de negocio. Un cambio de tema no borra productos/relaciones. Las funciones de core que consulta son de lectura, con fallback controlado si el plugin falta.

## Árbol propuesto (documental; no creado)

- style.css, functions.php, theme.json, screenshot.png
- inc/setup.php, inc/assets.php, inc/view-helpers.php
- front-page.php, page.php, single-psi_producto.php
- taxonomy-psi_categoria.php, taxonomy-psi_marca.php
- search.php,404.php,index.php
- templates/page-landing.php, page-collection.php, page-contact.php
- template-parts/site/header.php, navigation.php, footer.php
- template-parts/components/hero.php, breadcrumbs.php, product-card.php, product-grid.php, category-grid.php, brand-grid.php, brand-logo.php, pdf-links.php, contact-cta.php, whatsapp-cta.php, related-products.php
- patterns/landing-technical.php, editorial-sections.php, home-sections.php
- assets/css/tokens.css, base.css, layout.css, components.css
- assets/js/navigation.js, gallery.js, carousel.js
- assets/images/ y assets/fonts/ sólo recursos visuales necesarios y con licencia

Los nombres expresan límites, no obligación de una clase/archivo por función mínima. La distribución podrá empaquetar CSS/JS sin exigir Node en hosting. No hay controlador de dominio en templates.

## Componentes y familias legacy

| Familia de referencia | Composición destino | Qué se conserva |
|---|---|---|
| Inicio (3 variantes) | front-page + hero/carrusel opcional + category/brand grids | Identidad, bloques y enlaces; decidir alias por contenido |
| Detalle estático (123 iniciales; luego114 producto +9 landing) | single producto o landing | Dos columnas, texto técnico, menús laterales, PDFs |
| Detalle dinámico ProductosView | single producto | Galería/relaciones ordenadas; no alt fijo copiado |
| Listados estáticos (35) | archivo término o Page colección | Orden/composición explícita cuando sea editorial |
| ProductosMain categoría | taxonomy categoría | Filtro exacto, no descendientes automáticos |
| ProductosMain marca/combinación | taxonomy marca + filtro validado | Agrupación/intersección de productos |
| CategoriasMain | taxonomy categoría padre | Subcategorías y directos |
| Marca estática (12 + variante) | taxonomy marca o Page variante revisada | Logo, contexto y cards |
| Directorio de marcas (2) | Page colección de marcas | Logos enlazan objeto correcto |
| Institucional/privacidad | page editorial | Cuerpo legible, mismo shell |
| Contacto | page-contact + bloque core | Campos/estados accesibles, contacto |
| Soluciones/landing compuesta | page-collection/page-landing | Intención SEO y múltiples secciones |

Son12 composiciones de referencia, no12 diseños rígidos ni reproducción de cada huella HTML. Header/footer/nav/hero/cards se reutilizan en todas. Breadcrumb es mejora nueva documentada, no hallazgo del legacy.

## Contratos de componentes

Card recibe objeto publicado, permalink resuelto, título, resumen e imagen; nunca IDs legacy ni SQL. Grid recibe colección ya filtrada, orden/paginación y empty-state. Logo usa attachment y enlace de término; PDF usa relación validada con etiqueta/tamaño. CTA WhatsApp recibe enlace generado desde configuración única. Hero recibe H1, eyebrow opcional, fondo y contraste; texto existe antes de JS. Secciones técnicas usan bloques core con estilos del tema.

Navegación principal/pie mediante menús WordPress configurados por Administrator; sidebars calculados desde taxonomía o colección explícita. No generar menús dinámicos por index posicional. El gestor edita contenido, no PHP/CSS ni estructura global de navegación.

## Paridad visual y accesibilidad

Conservar colores, tipografía licenciada, proporciones, espacios y patrón de interacción de las familias. Obtener baseline visual controlado antes de implementación visual; hoy no existe verificación por capturas. No arrastrar vendors.min entero por conveniencia. Carrusel sólo donde se demuestre, controles teclado/pausa y reduced-motion. Imágenes con tamaño reservado; menú móvil accesible; foco visible; enlaces PDF descritos; JS no crea el único H1. No prometer paridad pixel a pixel sin ensayo.
