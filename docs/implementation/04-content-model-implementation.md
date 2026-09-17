# Modelo implementado y alcance
## Producto
`psi_producto`, visible Productos; público, REST y editor nativo. Base `/producto/{slug}/`; archivo bootstrap `/productos/`. No jerárquico, map_meta_cap y capacidades propias, delete_with_user=false. Supports: title, editor, excerpt, thumbnail, revisions, custom-fields y page-attributes (orden nativo).

| Campo | Almacenamiento/validación |
|---|---|
| Nombre, descripción, resumen, estado y orden | Campos nativos de post |
| Imagen destacada | Control nativo / _thumbnail_id |
| H1 opcional | _psi_h1: texto, 200 caracteres |
| Categoría principal | _psi_primary_category_id: 0 o término asignado |
| Galería | _psi_gallery_ids: IDs de imágenes únicos, ordenados |
| PDFs | _psi_datasheets: lista attachment_id, label, language opcional es/en |
| Videos | _psi_videos: provider=youtube, video_id de 11 caracteres, title |

Metadatos registrados nativamente, schema REST y revisiones habilitadas. Límites defensivos actuales: 50 entradas en galería/PDF, 10 videos; no son una regla comercial y pueden revisarse. No se limita a las cinco imágenes del CMS anterior.

El metabox usa selector nativo de Medios para galería y PDFs; permite ordenar/quitar y editar etiqueta PDF. YouTube se ingresa una URL por línea y se normaliza; no admite iframe arbitrario. La lengua del PDF y el título personalizado del video están representados en el contrato REST, pero no tienen un control completo dedicado en este metabox inicial. Al guardar URLs el título del video usa el texto predeterminado.

## Taxonomías
- `psi_categoria`: jerárquica; slug categoria, URL plana sin ancestros. Name/slug/description/parent nativos; imagen opcional _psi_image_id.
- `psi_marca`: no jerárquica; slug marca. Name/slug/description nativos; logo opcional _psi_logo_id.
- Medios de términos: 0 o attachment imagen existente; un PDF como logo se rechaza.
- Marca: ninguna o una. REST rechaza múltiples; una escritura nativa múltiple restaura la relación anterior y muestra aviso, nunca elige arbitrariamente la primera.
- Principal: debe estar asignada. Al quitar su asociación de categoría por la API de términos se limpia el valor. Guardar primero las categorías al utilizar metaboxes.

## Delimitación frente a arquitectura completa
No se implementaron todavía _psi_related_ids, hero, procedencia, estados review/public de términos, gates editoriales de aprobación, orden editorial de colecciones, filtros finales de categorías sin descendientes ni adaptador SEO. Los archivos actuales usan la consulta nativa de WordPress, que incluye descendientes en categoría: se debe sustituir en la fase del frontend conforme a 03-taxonomy-architecture.md. No usar esta versión para publicar términos legacy pendientes de revisión.

La arquitectura tenía has_archive=false. La petición actual requiere un archive funcional: /productos/ se habilita como infraestructura de prueba. No existe todavía una resolución SEO definitiva para él ni para rutas legacy.
