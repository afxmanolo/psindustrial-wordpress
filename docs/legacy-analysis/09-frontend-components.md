# Componentes y preservación visual

| Componente | Evidencia en legacy/public | Comportamiento |
|---|---|---|
| Cabecera | header.php/headerv1.php | logos desktop/mobile, menú colapsable Bootstrap, enlaces estáticos vs SQL |
| Pie | footer.php/footerv1.php | contactos, soluciones, marcas, WhatsApp, scripts |
| Hero interior | tit-section, page-title-big-typography | banner1, capa oscura, H1/H2 con data-fancy-text |
| Portada | index.php y variantes | Swiper, fondos banner1–4, parallax, autoplay, soluciones |
| Menú lateral | menu-soluciones*, menu-marcas*, menu-seccionales, links-menu* | navegación y CTA cotización/contacto |
| Cards/listados | shop-box, shop-image, shop-footer, grid | imagen, overlay, flecha y enlace; responsive por clases |
| Detalle | 123 PHP probables y ProductosView | dos columnas, fichas, contenido adicional irregular |
| Logos | iconos-marcas* | grilla de seis columnas y destinos por generación |
| Fichas | images/verficha.png | enlace PDF a nueva pestaña, sin visor propio demostrado |
| Botones | btn-*; back-seccionales.php | retorno, ver más y contacto |
| Formulario | contacto.php | POST local, campos, errores, reCAPTCHA |

No se identificó un componente general de breadcrumbs semánticos en el frontend principal; hay títulos de sección/menús y migas del panel. No añadirlos retrospectivamente al inventario.

Cabeceras cargan css/vendors.min.css, icon.min.css, style.css, responsive.css, marketing.css, business.css, custom.css. Pie carga js/jquery.js, vendors.min.js, main.js. La plantilla inicializa carruseles, animaciones, grids y controles; atributos data-* forman parte del contrato visual. CSS inline repetido modifica h2; estilos duplicados no equivalen a contenido prescindible.

Hay dos generaciones de includes, tres index y contenido con tablas/listas/imágenes adicionales. La familia detalle no implica que 123 cuerpos tengan estructura idéntica. template-fingerprints.csv conserva huella e includes; duplicate-files.csv identifica duplicados exactos también entre JS/CSS y bibliotecas.

images-alt-inventory.csv inventaría imágenes HTML tras expansión estática de fragmentos; page-details.csv recursos por página. Fondos CSS y rutas construidas por JS/PHP requieren revisión complementaria; no se atribuye alt a fondos. Defectos comprobados: alt constante en detalle dinámico, alt vacíos, lang=en en cabeceras españolas, enlaces # en portada y WhatsApp distinto por pie.

No se ejecutó navegador ni se tomaron capturas. La preservación futura necesita referencia de cada familia y variante, desktop/móvil, menú, animación desactivada, formularios y PDFs. No se afirma paridad visual por análisis de clases.
