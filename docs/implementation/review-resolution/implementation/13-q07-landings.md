# Q07 — Las 9 páginas de gama / landing

Implementa Q07 = Opción A: las 9 páginas `SEO_LANDING` se conservan como WordPress Page en
borrador, nunca publicadas automáticamente, con su propio contenido y sólo los medios cuya
relación es demostrable. Detalle fila por fila en
[q07-landings-audit.csv](q07-landings-audit.csv).

## Resultado

**9/9 páginas → `MIGRATE`** (`target_type=page`, `planned_result=CREATE`, borrador —
`Runner::apply()` siempre escribe `post_status=draft` para un post nuevo y
`_psi_review_state=pending` inmediatamente después; nunca publica). **8 medios propios**
aprobados (7 imágenes exclusivas/compartidas sólo entre landings hermanas, ninguna PDF).
2 de las 9 landings (`puertas-residenciales.php`, `tiras-plasticas-hawaianas.php`) no
tienen ningún medio que pase el filtro de propiedad — migran igualmente, sólo con su texto.

## Algoritmo

Implementado en `EditorialDecisions::q07()`. La lista de las 9 páginas es la única parte
curada en `editorial-decisions.json` (una decisión de política, "Opción A se aplica a
estas 9"); el contenido y la unión de medios se recalculan en cada ejecución desde
`content-master.csv` y `media-usage-evidence.csv`.

### Contenido

Idéntico mecanismo que cualquier producto/página/ficha estática: `Sources::content()`
extrae el cuerpo editorial desde el propio `legacy_php` de la landing. **Nunca se copia
descripción de un producto**: no hay ninguna ruta de código que lea el contenido de otra
entidad para rellenar una landing.

### Medios: sólo relación demostrable, nunca a costa de un propietario más fuerte

Para cada landing se recorren sus referencias en `media-usage-evidence.csv`
(`usage_type=PUBLIC_SOURCE`, `owner=page:<landing>`) y se aprueba una ruta sólo si:

1. **No** tiene relación SQL (`product-media-relations.csv`) — esa relación gana siempre.
2. **Ningún otro propietario** de la misma ruta es una página de clase distinta a
   `SEO_LANDING` (`PRODUCT_PAGE`, `CATEGORY_PAGE`, `BRAND_PAGE`, `INSTITUTIONAL_PAGE`,
   `CONTACT_PAGE`, `UTILITY`) — esas clases representan un propietario más específico.
3. Compartir la ruta **sólo con otras landings** sí está permitido: tres de las nueve
   (`operadores-puerta-abatible.php`, `operadores-puerta-corrediza-residencial.php`,
   `operadores-puertas-ascendentes.php`) son una familia visual que reutiliza literalmente
   las mismas dos fotos (`images/access.jpg`, `images/silent.jpg`) sin que ninguna otra
   entidad las reclame — se aprueban para las tres.

El hallazgo más frecuente al aplicar este filtro: `images/banner1.jpg` aparece en la
*mayoría* de las 617 páginas del sitio (un fondo/ícono genérico de plantilla compartida) —
exactamente el caso "ownership contradictorio" que el punto 3 de la tarea pedía tratar con
cautela. Ninguna landing lo reclama. Lo mismo ocurre con
`fichas/13.0.Folleto-Operador-AccessPRO-FS1000-SPEED-hoja-tecnica.pdf`, la ficha técnica
real del producto AccessPRO, enlazada también desde varias landings de "operadores": gana
la página de producto, ninguna landing se queda con ella.

## Mapeo de URL

Cada landing conserva su `legacy_url` en su propia entrada del plan (retenida, no
descartada), lista para el CSV de mapeo — ver
[11-url-mapping-audit.md](11-url-mapping-audit.md) de la fase anterior; este documento no
añade una fila nueva a `q01-q04-url-mapping.csv` porque esa auditoría es específica de
páginas que **dejan de crearse** (Q01/Q04); las landings sí se crean, así que su mapeo es
simplemente "la Page que ya migra con esta URL", visible directamente en el propio plan.

## Qué no se implementó

Ningún redirect, canonical, regla de reescritura ni `.htaccess`. Ninguna landing se marcó
publicada. No se reescribió ningún texto para eliminar duplicación con la descripción de
un producto — 09-page-decisions.md ya documentó que "algunas landings repiten descripciones
de productos"; esa revisión editorial de contenido queda pendiente de la fase de SEO, no de
esta.
