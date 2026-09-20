# Implementación Q02 + Q05 + Q06 — resumen ejecutivo

Fecha: 2026-09-18. Rama `feature/review-resolution`. Implementa **únicamente** las tres
decisiones humanas explícitas aprobadas por el usuario sobre `decision-questionnaire.md`:

- **Q02 = Opción B** — consolidar los 26 grupos de identidad equivalente en un único
  producto WordPress cada uno.
- **Q05 = Opción A** — extender la política PDF ya aprobada (hash/auditoría/saneado) a los
  32 archivos restantes bloqueados por tipo.
- **Q06 = Opción A** — excluir los 15 registros legacy vacíos/prueba (151-164, 165),
  conservando cualquier medio que también pertenezca a otra entidad válida.

No se implementó Q01, Q03, Q04, Q07-Q13 ni ninguna otra regla MEDIUM/HIGH. No se publicó
contenido. No se ejecutó una importación real completa. `/legacy` permanece intacto. La
versión de `psindustrial-core` sigue en 0.4.0 (no se incrementa hasta cerrar toda la fase).

## Resultado numérico

```
                    ANTES (932 REVIEW)      DESPUÉS
total               2.399                   2.399
UNCHANGED              15                      15
SKIP                1.281                   1.337   (+56)
CREATE                171                     276   (+105, incluye 25 MERGE)
REVIEW                932                     771   (−161)
ERROR                   0                       0
```

Como el usuario advirtió explícitamente, **no se esperaba ni se forzó una caída de
exactamente 195** (124+41+30): varias filas de Q05 tienen un segundo bloqueo de propiedad
independiente, y una fila de Q02 tiene un bloqueo de calidad de datos ajeno a esta fase.
La caída real (161) es menor que la suma nominal de filas "cubiertas" por las tres
preguntas (195) precisamente por esas dependencias — ver el desglose por decisión abajo y
[06-remaining-review.md](06-remaining-review.md).

## Componentes creados/modificados y por qué

| Componente | Tipo | Qué hace |
|---|---|---|
| [`migration/EditorialDecisions.php`](../../../../wordpress/wp-content/plugins/psindustrial-core/migration/EditorialDecisions.php) | Nuevo | Capa de decisión editorial explícita para Q02 (MERGE) y Q06 (SKIP). Separada de `Policy.php` (heurísticas LOW) y de `PdfApprovals.php` (PDF). Nunca infiere: sólo aplica una lista humana cerrada (`editorial-decisions.json`) y recalcula determinísticamente ganador/unión desde campos ya existentes. |
| [`editorial-decisions.json`](editorial-decisions.json) | Nuevo | Registro humano auditable: los 26 `group_key`+IDs de Q02 y los 15 IDs de Q06, con `decision_id`, `editorial_approval` y `reason` explícitos. Mismo patrón que `pdf-approvals.json`. |
| [`migration/Planner.php`](../../../../wordpress/wp-content/plugins/psindustrial-core/migration/Planner.php) | Modificado | Una línea añade `$editorial` a la cadena de precedencia (`manual ?? editorial ?? policy ?? null`), acotada a `scope==='full'` igual que `Policy`. Un segundo cambio (no funcional) corrige `approval_ref` para citar la fuente real de una decisión editorial en vez de etiquetarla por error como "ensayo privado". |
| [`pdf-security-review/pdf-approvals.json`](../../../pdf-security-review/pdf-approvals.json) | Extendido | +32 entradas (10 `group_a_sanitization`, 22 `group_b_exception`). Cero cambios de lógica: `PdfApprovals::resolve()` ya iteraba genéricamente estos arreglos. |
| [`tools/pdf-sanitizer/sanitize.py`](../../../../tools/pdf-sanitizer/sanitize.py) | Corregido (bug real) | Ver más abajo — sin este arreglo, los gemelos de Grupo A rompían la auditoría de saneado. |
| `psindustrial-core.php` | Modificado | Registra `EditorialDecisions` en la lista de clases del plugin. |
| `tests/editorial-decisions.php`, `tests/q05-pdf-resolution.php` | Nuevos | Ver [04-tests.md](04-tests.md). |
| `tests/pdf-approvals.php`, `tests/runner-media-validation.php` | Corregidos | Aserciones que dependían de conteos ahora obsoletos por diseño (ver abajo). |

`Runner.php`, `Identity.php`, `Media.php`, `Sources.php` — **sin cambios**. Q05 reutiliza
exactamente el mecanismo que `Runner::media_is_valid()` ya consulta; Q02/Q06 reutilizan
exactamente el mecanismo de precedencia que ya existía para `Policy`.

## Un bug real encontrado y corregido durante la implementación

`tools/pdf-sanitizer/sanitize.py` guarda el resultado saneado en un archivo nombrado por
el hash de origen (`<source_sha256>.pdf`), para que los gemelos binarios reutilicen el
mismo archivo. Pero **cada entrada volvía a sanear y sobrescribir ese archivo
independientemente**, y `pikepdf.save()` no es determinista byte a byte entre ejecuciones
separadas (el orden interno de objetos puede variar). Resultado: en cuanto una segunda
entrada con el mismo hash se procesaba, el registro de auditoría de la primera quedaba con
un `sanitized_sha256` que ya no coincidía con el archivo real — `PdfApprovals::resolve()`
lo detectaba correctamente y lo rechazaba (fail-closed), pero silenciosamente. Se corrigió
saneando **una sola vez por hash único** y reutilizando ese resultado para todos los
gemelos, cada uno con su propia verificación de hash de origen. Ver
[02-q05-pdf-resolution.md](02-q05-pdf-resolution.md#bug-de-saneado-encontrado-y-corregido).

Este bug sólo podía manifestarse porque Q05 introduce, por primera vez, múltiples entradas
de `pdf-approvals.json` que comparten `source_sha256`. La fase anterior (5+2 entradas,
todas con hash único) nunca lo habría revelado.

## Verificación

Todas las suites (`smoke`, `importer`, `policy`, `pdf-approvals`, `runner-media-validation`,
más las dos nuevas `editorial-decisions`, `q05-pdf-resolution`) pasan: **7.368 checks, 0
fallos**. Detalle en [04-tests.md](04-tests.md). FULL DRY RUN final en
[05-dry-run-after-decisions.md](05-dry-run-after-decisions.md). REVIEW restante analizado
en [06-remaining-review.md](06-remaining-review.md).

## Qué NO hizo esta fase

No se fusionó ningún producto fuera de los 26 grupos exactos de Q02. No se tocó ninguno de
los 27 grupos editoriales de Q03. No se relajó `Media::file_valid()` globalmente — sigue
bloqueando cualquier PDF no aprobado explícitamente por path+hash. No se borró ninguna
imagen compartida con una entidad válida. No se creó ningún producto vacío. No se ejecutó
`Runner::batch()` sobre el plan completo (sigue rechazado por diseño). No se publicó
contenido. No se incrementó la versión del plugin. No se hizo commit ni push.
