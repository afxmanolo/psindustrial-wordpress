# Arquitectura del producto

## Registro del CPT

Nombre interno `psi_producto`; singular Producto, plural Productos. Público, consultable, UI y REST habilitados para el editor nativo. No jerárquico. Base limpia para contenido nuevo `/producto/{post_name}/`, sin categoría ni marca en la ruta. Archivo general desactivado (`has_archive=false`): Soluciones y páginas de categoría son las entradas demostradas, no se añade un catálogo genérico duplicado. `rewrite.with_front=false`; las fichas legacy con ruta aprobada usan el registro de 07.

Supports: title, editor, excerpt, thumbnail, revisions, custom-fields y page-attributes (sólo menu_order; sin padres de producto). Sin comments, trackbacks ni author visible; la autoría técnica de WordPress se conserva. `map_meta_cap=true`, capacidades propias psi_productos. `delete_with_user=false`. Revisión del contenido y meta editorial registrada, con prueba de restauración. [Registro CPT](https://developer.wordpress.org/reference/functions/register_post_type/), [registro de meta](https://developer.wordpress.org/reference/functions/register_meta/).

## Contrato de edición

| Dato | Fuente WordPress | Regla |
|---|---|---|
| Nombre | post_title | Obligatorio al publicar; sin HTML ni nombre derivado automáticamente de URL conflictiva |
| Descripción | post_content | Bloques nativos permitidos: párrafos, encabezados H2/H3, listas, tablas, imágenes y grupos |
| Resumen | post_excerpt | Opcional; texto breve para cards; no copiar descripción entera |
| Slug | post_name | Técnico limpio y único; no decide URL legacy ni cambia por editar nombre |
| Imagen principal | _thumbnail_id | Attachment imagen válido; no tomar banner/verficha como foto comercial |
| Galería | _psi_gallery_ids: integer[] | Orden explícito, IDs únicos; no incluye principal; sin límite comercial de 5 |
| Fichas PDF | _psi_datasheets: object[] | attachment_id, label, language opcional; orden de lista, MIME PDF comprobado |
| Video | _psi_videos: object[] | provider=youtube, video_id, title; parser desde URL; no HTML iframe arbitrario |
| Información adicional | post_content | Tablas/listas editoriales; no se inventa esquema técnico para todos los modelos |
| Categorías | psi_categoria | Varias; vacío válido en borrador |
| Principal | _psi_primary_category_id | 0 o categoría asignada; no «primera por ID» |
| Marca | psi_marca | Una verificada o ninguna; ausencia no crea marca «0» |
| Relacionados | _psi_related_ids: integer[] | Selección manual, sin autoreferencia, sólo publicados al mostrar |
| Orden | menu_order | Entero ≥0; desempate título e ID; colecciones editoriales pueden definir su propio orden |
| H1/hero | _psi_h1, _psi_hero_id | Opcionales; H1 por defecto nombre; hero no sustituye imagen principal |
| SEO | Adaptador de 08 | Un propietario de title/description/canonical/robots |
| Estado editorial | draft/pending/publish/private/trash | Estados nativos; publicación bloqueada por errores de validación, no por falta de marca solamente |
| Control de calidad | _psi_review_state | pending/approved; no es post_status ni action de migración |
| Procedencia | _psi_source_keys, _psi_source_hash, _psi_legacy_date | Privada, importador/Administrator; nunca visible en REST público |

Requeridos para publicar: nombre, cuerpo significativo o resumen técnico aprobado, revisión approved y ruta sin colisión. Una categoría ausente o marca desconocida produce advertencia, no inventa asociación ni elimina el contenido. Si se publica sin marca, no se emite brand en schema. Contenido en conflicto puede quedar como borrador o Page editorial preservada tras revisión; no desaparecerá una URL sin destino en el corte.

No se generan relacionados automáticos: compartir categoría o PDF no prueba relación comercial. La UI puede sugerir; sólo selección guardada se publica.

## Acciones de migración

| Acción | Significado | Aplicación PS Industrial |
|---|---|---|
| MIGRATE | Crear/actualizar entidad inequívoca mediante clave estable | Filas aprobadas, nunca asumir que los136 matches aprueban identidad comercial |
| MERGE | Varias fuentes hacia una entidad con ganador por campo | Sólo grupos aprobados;77 agrupamientos siguen siendo candidatos |
| CREATE_FROM_STATIC | Crear desde contenido PHP extraído sin ejecutar | 32 fichas/familias: producto si describe entidad técnica; si es gama editorial, Page |
| REVIEW | No aplicar cambios públicos del elemento | IDs151–164,165, conflictos y merges no autorizados; paquete conserva todo |
| SKIP | No importar representación sin valor, con motivo registrado | Implementación CMS; productos/URLs sólo tras aprobación concreta |

En MERGE se conserva el conjunto de IDs, URLs y evidencia; textos no idénticos se comparan por campo. Marca no se elige por mayoría; medios se unen sólo con orden/propósito aprobado. Se puede separar un registro compuesto (139) en varias decisiones con subclave; no asignarlo a dos objetos usando una clave ambigua.

Los14 vacíos y Prueba quedan REVIEW, no borradores públicos sin nombre ni SKIP automático. Producto3 conserva la evidencia de categoría contradictoria; la relación canónica no se publica sin resolver. Productos sin marca fiable pueden publicarse sin marca si su contenido y ruta son válidos. Las rutas de variantes conservan contenido editorial separado hasta aprobar equivalencia; no se duplica automáticamente cada SQL como producto.
