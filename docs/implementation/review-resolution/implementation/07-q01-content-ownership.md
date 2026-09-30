# Q01 — Propiedad del contenido de páginas

Implementa Q01 = Opción A: el contenido de una página legacy que reexpresa directamente un
producto, una categoría o una marca pertenece a esa entidad canónica; no se crea una Page
independiente. Esta es una decisión de **propiedad de contenido**, no de URL: no se decide
ningún permalink ni se crea ningún redirect aquí (ver
[11-url-mapping-audit.md](11-url-mapping-audit.md) para la trazabilidad que la futura fase
de direcciones web necesitará).

## Resultado

| Sub-tipo | Filas `content-master.csv` | Resuelto (SKIP + propietario) | KEEP_REVIEW |
|---|---:|---:|---:|
| `PRODUCT_PAGE` con `related_product_ids` | 82 | 51 (28 vía Q02 + 23 enriquecidos R-P01) | 31 |
| `CATEGORY_PAGE` | 37 | 33 | 4 |
| `BRAND_PAGE` | 13 | 12 | 1 |
| **total páginas Q01** | **132** | **96** | **36** |

Los otros 32 `PRODUCT_PAGE` (sin `related_product_ids`) son el dominio de Q04, no de Q01
— ver [08-q04-static-products.md](08-q04-static-products.md).

## Algoritmo, con precisión

Implementado en `EditorialDecisions::q01()`. Reutiliza la salida de `Policy::decisions()`
(pasada como parámetro desde `Planner.php`) para reconocer un propietario ya aprobado —
**nunca reimplementa las condiciones de elegibilidad de R-P01/R-T01/R-T03**, que seguirían
siendo responsabilidad exclusiva de `Policy.php`; Q01 sólo *enriquece* la decisión ya
calculada, copiándola y añadiendo los medios/imagen que la política, por sí sola, no
alcanza a incluir.

### (a) Páginas de producto

Para cada fila `PRODUCT_PAGE` con `related_product_ids`:

1. Se resuelven los IDs a sus filas `product-master.csv`. Si falta alguna fila, o si los
   IDs pertenecen a **más de un** `canonical_candidate_group`, la página queda
   `KEEP_REVIEW` (dato inconsistente, no se improvisa).
2. Si el grupo es uno de los 26 grupos aprobados por Q02 **y Q02 lo resolvió realmente en
   esta ejecución**: la página se retira (`SKIP`) citando al ganador de Q02. Q02 ya incluye
   los medios de esta página (usa la misma columna `images`/`technical_pdf` de
   `product-master.csv`), así que no hay nada más que enriquecer.
3. Si hay **exactamente un** ID relacionado y ese producto **ya está aprobado por R-P01**
   (`Policy::decisions()` le asignó `MIGRATE`): se copia esa decisión completa y se
   **reemplazan** sus `images`/`pdfs` (que R-P01 sólo pobló desde
   `product-media-relations.csv`, exclusivamente SQL) por la unión completa página+SQL de
   `product-master.csv` — la misma técnica exacta que usa Q02, deduplicada por SHA-256, no
   por nombre de archivo. Categoría y marca se conservan **sin cambios** respecto a la
   política. La página se retira (`SKIP`).
4. Cualquier otro caso (múltiples IDs que no son un grupo Q02 — territorio de Q03 — o un
   producto que R-P01 no aprobó) queda `KEEP_REVIEW`. No se elige un "ganador" entre varios
   productos: esa es la decisión de Q03, explícitamente fuera de esta fase.

**Hallazgo durante la implementación**: la primera versión de este enriquecimiento olvidó
dar a cada imagen/PDF de la unión su propia decisión `MIGRATE` — exactamente el mismo error
que se encontró y corrigió en Q02 durante la fase anterior. Sin esa decisión individual, la
verificación de dependencias de `Planner::build()` devolvía el producto entero a REVIEW
silenciosamente (20 productos, previamente CREATE bajo R-P01, retrocedieron a REVIEW en la
primera ejecución de prueba). Corregido replicando el mismo `foreach` de Q02.

### (b) Páginas de categoría y marca

Para cada fila `CATEGORY_PAGE`/`BRAND_PAGE`:

1. `legacy_file` debe coincidir con el `legacy_page` de **exactamente un** término en
   `category-master.csv`/`brand-master.csv`. Cero o dos-o-más coincidencias →
   `KEEP_REVIEW` (propietario ambiguo o inexistente).
2. Ese término debe estar ya aprobado (`Policy::decisions()` con R-T01/R-T03). Si no lo
   está (p. ej. categoría 33, registro de conflicto D06), la página queda `KEEP_REVIEW`.
3. Se copia la decisión de Policy y se intenta añadir **el único campo de imagen que el
   modelo actual admite**: `category-master.csv:image_file_id` (resuelto a una ruta vía
   `media-master.csv:file_ids`) para categorías, `brand-master.csv:logo` para marcas. Si la
   imagen no resuelve a una fila válida o su hash no coincide, simplemente no se añade —
   **la página se retira igual**, porque la propiedad del contenido no depende de que
   exista una imagen. Nunca se inventa una galería: `Fields.php`/`TermEditor.php` sólo
   definen un slot de imagen por término (`_psi_image_id`/`_psi_logo_id`), nunca una
   galería, así que cualquier OTRA imagen que la página referencie más allá de esa única
   queda sin decisión — no se le asigna un destino que el modelo no tiene.

## Los 250 medios

Los 250 medios documentados en [09-page-decisions.md](../09-page-decisions.md) se dividen,
según el destino que el modelo actual admite:

- **Medios de producto** (mezcla `PRODUCT_PAGE` sola o combinada, ~178 según esa
  estimación): ya cubiertos por el mecanismo (a) — la unión página+SQL de
  `product-master.csv` es precisamente la fuente que ese conteo original documentó.
- **Imagen de categoría / logo de marca** (un slot cada uno, 45 términos con página+campo
  resolubles): cubiertos por el mecanismo (b), 41 de los cuales no están además
  sombreados por una decisión manual del ensayo previo (categoría 8 y marca 1 sí lo están,
  y conservan su propia imagen del ensayo sin cambios — la precedencia manual > editorial
  se respeta, no se sobreescribe).
- **Cualquier medio adicional** referenciado por una página de categoría/marca más allá de
  su único slot: **no tiene destino legítimo en el modelo actual** y permanece sin decisión
  — ni se descarta ni se inventa un campo nuevo. Documentado aquí como límite consciente,
  no como omisión.

## Precedencia respetada

Ninguna decisión manual existente, ninguna aprobación Q02/Q05/Q06 y ningún conflicto humano
ya documentado (D03 marca, D06 categoría) fue sobrescrito. Verificado explícitamente:
`category:8` y `brand:1` ya llevaban una decisión manual del ensayo (Prompt 6) con su propia
imagen; Q01 calcula una decisión para ellos igualmente, pero la cadena de precedencia de
`Planner.php` (`manual ?? editorial ?? policy`) la descarta a favor de la manual — exactamente
el comportamiento esperado, verificado por test.

## Q03 intacto

Ninguno de los 27 grupos editoriales de Q03 recibe una decisión Q01 o Q02: una página
`PRODUCT_PAGE` cuyos `related_product_ids` pertenecen a un grupo multi-producto que **no**
es uno de los 26 de Q02 queda `KEEP_REVIEW` sin excepción — verificado con el grupo
`accesspro-fs1000speed.php` (IDs 1/109/110/114).

## Trazabilidad

[q01-q04-url-mapping.csv](q01-q04-url-mapping.csv) — una fila por página, con su entidad
destino. [q01-media-ownership.csv](q01-media-ownership.csv) — una fila por medio
reasignado, con su rol (`featured_image`, `gallery`, `datasheet`, `category_image`,
`logo`, `standalone_attachment`).
