# Arquitectura SEO

## Un motor SEO: Yoast SEO gratuito

Decisión de diseño: usar Yoast SEO gratuito, sin Premium ni módulos ecommerce/IA. Delegar emisión de title/meta description/canonical/robots, sitemap XML, Open Graph y grafo básico WebSite/Organization/WebPage. Ofrece esas funciones y mecanismos de extensión; no se instala ahora. [Ficha oficial](https://wordpress.org/plugins/wordpress-seo/), [Metadata API](https://developer.yoast.com/customization/apis/metadata-api/).

Alternativas: sólo core WordPress carece del flujo editorial completo de meta descriptions y controles requeridos; desarrollar todo SEO añade mantenimiento de sitemaps/schema innecesario. Otro plugin ligero es viable, pero se fija uno para evitar decisiones improvisadas. Coste aceptado: dependencia de tercero, UI a simplificar y adaptador con pruebas. Cambiar proveedor requiere exportación/mapeo, no afecta CPT/taxonomías.

Yoast crea índices derivados propios. Se acepta esta excepción de tercero documentada, no tablas personalizadas de psindustrial-core ni nueva fuente de identidad. No escribir directamente sus tablas ni migrarlas desde local a un sitio con contenido distinto. [Indexables](https://developer.yoast.com/features/indexables/functional-specification/).

## Propiedad de datos y salidas

Valores editoriales activos en almacenamiento nativo del plugin SEO, editados en un único panel. No duplicar campos SEO editables en _psi_*; el manifest guarda snapshot legacy e importado para auditoría. El adaptador de importación convierte esos valores mediante APIs/meta documentada y comprobada para la versión fijada, invalida/reconstruye índices por procedimiento soportado y verifica HTML renderizado. Las APIs de lectura/presentación no se confunden con APIs de escritura.

Core aporta los permalinks primarios, reglas de aliases, breadcrumb lógico, elegibilidad pública, estado review y datos de producto. El tema sólo renderiza H1/breadcrumb visible y usa wp_head; no imprime segunda description/canonical/schema. Adaptador usa filtros públicos cuando la URL no estándar requiera corregir salidas, incluidos canonical y metadatos de archivos; [canonical API](https://developer.yoast.com/features/seo-tags/canonical-urls/api).

Si Yoast se desactiva accidentalmente, catálogo sigue accesible, pero aparece alerta administrativa crítica y se bloquea nueva publicación SEO-sensitive/importación. No se activa un motor SEO paralelo improvisado. Restaurar plugin/configuración o desplegar rollback; la exportación del manifest permite sustitución planificada.

## Matriz de indexación y metadata

| Contenido | Política de diseño |
|---|---|
| Producto publicado revisado | Title/description específicos, canonical primario, H1 server-side; indexable salvo decisión registrada |
| Categoría/marca public | Metadata propia y archivo con contenido; no sitemap de review/vacíos sin valor aprobado |
| Page institucional/landing | Preservar intención/title/description/H1/enlaces; independencia aunque comparta productos |
| Home | Una primaria; variantes sólo se consolidan tras equivalencia aprobada |
| Búsquedas internas/filtros no aprobados | noindex, fuera de sitemap; canonical que no falsee contenido filtrado |
| Paginación | URL autocanónica, enlaces rastreables; no canonical global a página1 |
| Attachment HTML | Sin landing indexable adicional; redirect al archivo si ruta existe y es seguro; comportamiento debe verificarse |
| PDF | Política de06; noindex masivo prohibido; revisar X-Robots-Tag en hosting |
| Borrador/private/review | No público/no sitemap; autenticación para preview |
| Staging/local | Autenticación + noindex; no sólo robots.txt |

UNKNOWN en inventario no prueba indexación ni noindex. Para una página pública aprobada sin directiva demostrada se adopta index/follow como **decisión nueva registrada**, pendiente de contraste con HTTP; no se afirma que era su estado previo. Cambios respecto de baseline requieren razón. No imponer noindex a todo contenido conflictivo para esquivar revisión.

Sitemap: sólo URLs primarias públicas200, publicadas y permitidas, generadas por un único proveedor. Excluir aliases, filtros, attachments HTML, páginas review. Probar que filtros de permalink afectan también sitemap. Guardar mapa antiguo antes del corte; no copiar sus207 entradas ciegamente. No duplicar el sitemap nativo y el del plugin como dos fuentes competidoras.

## Breadcrumbs, schema y medios

Breadcrumb lógico: Inicio → categoría principal y ancestros públicos → producto; si no hay categoría principal, Inicio → producto. Marca conserva su propio archivo. No elegir ruta por referer ni primera categoría. Core suministra recorrido; adaptador y tema consumen el mismo para HTML/JSON-LD. Es mejora nueva, no componente universal demostrado en legacy.

Organization sólo con datos comerciales verificados; Product como extensión de grafo sólo para productos realmente identificados, con nombre/descripción/imagen/brand confirmada. Sin precio, offers, disponibilidad, SKU, GTIN, ratings ni certificaciones inventadas. No se promete resultado enriquecido Google por añadir Product. Pages/landings usan WebPage; ItemList opcional coherente con cards. No schema Product en cualquier PHP antiguo por su familia visual.

Alt descriptivo por contexto; decoraciones alt vacío. H1/H2 visibles sin JavaScript; corregir lang a español. Videos pueden aparecer sin VideoObject si faltan fecha/thumbnail/datos necesarios, sin inventarlos. PDF no recibe schema comercial de otro modelo.

Pruebas: exactamente un title/canonical/description cuando corresponda, URLs absolutas de producción, ausencia de staging en sitemap/OG, titles/H1 comparados por URL, robots HTTP/HTML coherentes, graph IDs únicos, redirects un salto. Revalidar configuración en cada actualización.
