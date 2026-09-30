# Q03-B / Q03-C / Q03-D — identidad resuelta, marca deliberadamente sin resolver

Tres grupos, cada uno un conflicto de marca ya documentado **antes** de esta fase en
`docs/migration/manual-decisions-required.md`. En los tres: identidad consolidada
(`reason_code=PRODUCT_IDENTITY_RESOLVED`), marca explícitamente vacía
(`brand_status=BRAND_UNRESOLVED_Q09`). Q09 no se resuelve aquí ni parcialmente.

| | Grupo | IDs | Categoría | Descripción | Evidencia del conflicto (ya documentada) |
|---|---|---|---|---|---|
| Q03-B | coleccion-modern-steel.php | 6,16,20,107 | 36 (sin divergencia) | AGREED (idéntica en los 4) | 3 valores de `brand_id` distintos (Overhead Door/Wayne Dalton/Clopay), los 3 a confianza `WEAK_INFERENCE` |
| Q03-C | lift-master-mod-h.php | 43,81 | 13 (sin divergencia) | AGREED (idéntica) | `brand_id` apunta a LiftMaster (`WEAK_INFERENCE`); el título de la página dependiente trae "Blue Giant" |
| Q03-D | puertas-seccionales-de-acero-aisladas-thermospan-modelo-150.php | 14,57 | 8 (sin divergencia) | **CONFLICT** | texto libre mezcla referencias a Wayne Dalton y Clopay |

## Q03-D: el conflicto de descripción, sin sintetizar

Las descripciones SQL de id 14 e id 57 difieren genuinamente (no es un caso de "una vacía, una
con contenido"): ambas tienen texto, y el texto es distinto. Se buscó, dentro del alcance de
esta tarea, si la evidencia ya documentada establecía una fuente pública canónica inequívoca
para preferir una sobre la otra — no se encontró ninguna que no requiriera investigación nueva
(fuera de alcance). Por tanto:

- **No se concatenaron, mezclaron ni combinaron** las dos descripciones.
- **No se eligió la más larga** ni ningún otro criterio arbitrario.
- El campo queda marcado `description_status=CONFLICT` en el audit, y
  `decision.field_provenance.description.status=CONFLICT` en el propio plan — auditable,
  nunca oculto.
- La entidad **sí se consolida** (identidad y contenido son ejes separados, sección 3 de la
  tarea) — no se mantienen dos productos sólo por este conflicto de campo.

## El hallazgo: MOOVI necesitaba la misma protección, con una lista manual no habría llegado

La primera versión de esta implementación forzaba `brand=''` sólo para estos 3 grupos,
pasados explícitamente por el llamador. Al ejecutar el DRY RUN final, `barreras-
estacionamiento-moovi50rm.php` (uno de los 16 grupos Q03-GLOBAL, no uno de estos 3) recibió
`brand:12` (BFT) automáticamente — sus 4 miembros tienen el mismo `brand_id` cuando no está
vacío, así que la comprobación genérica de acuerdo de valores no encontró ningún problema.

Pero MOOVI **ya estaba** en `Policy::BRAND_CONFLICT_PRODUCTS` (ids 44,123,125,135) — el mismo
registro, transcrito literalmente de `manual-decisions-required.md`, que ya excluye a estos
IDs de la migración automática R-P01 por la razón documentada "MOOVI 44/123/125/135: enlace
BlueGiant frente a BFT". El propio análisis Q03 de esta misma tarea
([02-category-only-groups.md](../q03-analysis/02-category-only-groups.md)) ya lo había
anotado explícitamente: *"Identidad de producto limpia..., pero enlace externo BlueGiant vs
brand_id=BFT ya documentado...; marca NO se resuelve aquí"* — una advertencia que la primera
versión del código, centrada sólo en los 3 grupos nombrados explícitamente por la tarea, pasó
por alto.

**Corrección aplicada, antes de considerar esta fase terminada**: se añadió
`Policy::brand_conflict_products()`, un accesor de sólo lectura nuevo sobre la constante
`BRAND_CONFLICT_PRODUCTS` ya existente (la constante en sí **no se modificó**). `q03_consolidate()`
ahora comprueba automáticamente, para **cualquier** grupo (no sólo B/C/D), si alguno de sus
miembros está en ese registro — si lo está, la marca queda sin resolver
independientemente de si los valores de `brand_id` concuerdan o no. Esto convierte una lista
mantenida a mano (con el riesgo de olvido ya demostrado) en una comprobación automática contra
la única fuente de verdad existente. Verificado por test explícito
(`tests/q03-multi-category.php`), incluyendo una comprobación de que el accesor devuelve
exactamente la misma lista que la constante privada.

## Trazabilidad

[q03-consolidation-audit.csv](q03-consolidation-audit.csv) (filas 24-26, más la fila 9 —
MOOVI — dentro del bloque Q03-GLOBAL) y [q03-field-provenance.csv](q03-field-provenance.csv).
