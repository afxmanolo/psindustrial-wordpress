# Marcas

## Decisión: taxonomía, no CPT

`psi_marca`, no jerárquica, asociada a psi_producto; etiqueta Marcas; base nueva `/marca/{slug}/`. Las12 marcas del maestro se preparan con identidad estable. Una marca nueva requiere nombre y revisión; no crear Solmmer sólo porque aparece en un PDF sin decisión editorial.

| Aspecto | Taxonomía elegida | CPT alternativo |
|---|---|---|
| Agrupación de productos | Relación/consulta nativa | Requiere post meta u otra relación |
| Nombre/logo/descripción | Término + meta + Media | Título/cuerpo/meta |
| Página pública | Archivo enriquecido de término | Single propio más consulta de productos |
| Administración | Un formulario sencillo | Editor más complejo y sincronización adicional |
| SEO | Metadatos y canonical del término | SEO de post, pero riesgo de archivo duplicado |
| Contenido muy complejo futuro | Limitación de editor de término | Más flexible; no demostrado ahora |
| Migración |12 términos con IDs/logo explícitos |12 posts y relación artificial sin necesidad |

Se elige la taxonomía porque la función demostrada es agrupar productos y mostrar nombre/logo/archivo. No habrá Page adicional de marca que duplique automáticamente el archivo. Si una variante tiene contenido editorial distinto, puede mantenerse como Page con colección hasta aprobar consolidación.

## Datos y experiencia pública

Campos nativos name/slug/description. Meta: _psi_logo_id (attachment), _psi_order, _psi_h1, _psi_public_state, _psi_source_keys/hash. Description es contenido SEO HTML limitado. Imagen alternativa de hero se usa sólo si existe necesidad editorial aprobada; no asumir que logo puede recortarse como banner.

Template `taxonomy-psi_marca.php`: hero/H1, logo con proporción natural, descripción, grid de productos confirmados, enlaces de contacto. Ruta legacy principal aprobada (por ejemplo bft.php) puede ser el permalink primario del término; no se crea un archivo PHP físico. Logo en el directorio siempre enlaza mediante el resolvedor de permalink al término publicado, nunca por posición de array. Los12 logos se obtienen de brand-master, no del campo SQL imagen vacío.

Marca de producto: cero o una. No resolver Modern Steel con tres marcas ni LiftMaster/BlueGiant o MOOVI/BlueGiant por mayoría. Los28 registros sin marca fiable se mantienen sin asignación mientras se revisan. El producto no desaparece del catálogo general por ello, sólo no entra en un archivo de marca no demostrado. No se infiere fabricante a partir de una colección comercial.

SEO: nombre/title/description específicos, canonical primario del término, sitemap si público y revisado. Schema brand sólo con asociación aprobada. No crear Organization con direcciones/certificaciones inventadas de cada fabricante.

Impacto: menos pantallas y ninguna relación personalizada; importación debe separar identidad de marca de sus páginas variantes. La limitación aceptada es no disponer de un editor completo de bloques por término; necesidades editoriales extraordinarias se cubren con una Page, sin migrar toda la taxonomía a CPT.
