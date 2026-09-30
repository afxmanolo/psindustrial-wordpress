# Auditoría post-recovery

Continúa de [21-recovery-execution-result.md](21-recovery-execution-result.md). Todas las
comprobaciones son de sólo lectura. Ningún problema editorial nuevo se corrigió en esta
fase — sólo diagnóstico y verificación.

## Idempotencia — DRY RUN únicamente, sin segundo reintento

`Planner::build('full')` reconstruido una vez, sin mutación (confirmado antes/después).
**Nunca se llamó a `batch_recovery()` una segunda vez.**

| Grupo | `planned_result` del DRY RUN | Interpretación |
|---|---|---|
| Los 37 recuperados con éxito | **100 % `UNCHANGED`** | Nunca se re-proponen para creación — idempotencia confirmada. |
| Los 11 en `CONFLICT` | **100 % `CONFLICT`** (nunca `CREATE`) | El objeto parcial existente se reconoce como conflicto a resolver, nunca se re-ofrece como creación limpia (evita duplicados si un futuro proceso repitiera la creación sin cuidado). |

Totales globales del DRY RUN: `UNCHANGED=501` (464 previos + 37 nuevos), `SKIP=1.534`,
`REVIEW=339`, `CONFLICT=21` (10 colisión de slug + 11 nuevos de esta ejecución),
`CREATE=4` (cascada de categoría, sin cambios). Suma = 2.399, exacta.

## Auditoría WordPress

| Comprobación | Resultado |
|---|---|
| Duplicados nuevos por identidad (`_psi_import_identity.entity_key`) | **0** |
| Duplicados nuevos por slug (`psi_producto`) | **0** |
| Contenido publicado (`psi_producto` en `publish`) | **0** |
| PDFs Grupo A — productos dependientes desbloqueados | **10/10 `APPLIED`**, cada uno con `_psi_datasheets` apuntando al adjunto saneado; `Media::valid(attachment_id,'pdf')=true` verificado en los 10 |
| PDFs Grupo B — asociación a producto | **0/11 completada** — ver causa raíz exacta en [21](21-recovery-execution-result.md); los propios PDFs (attachments) sí se crearon correctamente, sólo la asociación `_psi_datasheets` del producto fue rechazada |
| Galerías (`_psi_gallery_ids`) de las 37 aplicadas | **0 problemas** — sin IDs repetidos, sin miniatura duplicada dentro de la galería, todos los IDs son adjuntos reales |
| Attachments válidos | **0 problemas** encontrados |
| 449 objetos previos | **0** de sus `entity_key` aparecen entre los tocados por este recovery — intersección vacía, confirmada |
| 339 REVIEW | Sin cambio (confirmado en el DRY RUN posterior) |
| 1.534 SKIP | Sin cambio (confirmado en el DRY RUN posterior) |
| 14 conflictos legítimos previos (10 slug + 4 categoría) | Sin cambio — siguen fuera, sin tocar |

## Resumen de motivos — los 11 no aplicados

Los 11 comparten **una única causa raíz técnica** (no 11 causas distintas): la brecha de
`Fields::guard()`/`Media::valid()` sin conocimiento de `PdfApprovals` para PDFs Grupo B,
documentada en detalle en [21-recovery-execution-result.md](21-recovery-execution-result.md).
Ninguno es un conflicto editorial, un dato corrupto o una pérdida de información — los
adjuntos PDF Grupo B existen correctamente en la biblioteca de medios; sólo su vínculo con
el producto quedó pendiente.

## Qué NO se hizo en esta fase

No se corrigió `Fields::guard()`/`Media::valid()`. No se reintentaron los 11. No se tocó
ningún objeto fuera del conjunto de 48 sellado. No se publicó nada. No se desplegó a
staging ni producción.
