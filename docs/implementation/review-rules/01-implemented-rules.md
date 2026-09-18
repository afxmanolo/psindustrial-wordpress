# Reglas LOW implementadas

Fecha: 2026-09-18. Rama `feature/review-rules`. Implementación en
[`migration/Policy.php`](../../../wordpress/wp-content/plugins/psindustrial-core/migration/Policy.php),
nueva clase, separada del motor del importador (`Planner`/`Runner`/`Storage`/`Identity` no
cambian de comportamiento). Referencia de origen:
[09-proposed-rules.md](../review-analysis/09-proposed-rules.md) y
[12-risk-analysis.md](../review-analysis/12-risk-analysis.md).

## Alcance exacto

Implementadas — **únicamente** las marcadas LOW:

| Regla | Tipo | Condición (idéntica a la documentada en la fase de análisis) |
|---|---|---|
| R-T01 | category | `legacy_id` fuera de {25,26,33} y `sql_parent_confidence = CONFIRMED` |
| R-T03 | brand | `legacy_page` y `logo_evidence` no vacíos |
| R-P01 | product | grupo canónico unitario, `unambiguous_php = YES`, sin conflicto D03/D06, categoría propia aprobada por R-T01, marca sólo si `brand_confidence = CONFIRMED` |
| R-G02-LOW | page | `classification` ∈ {INSTITUTIONAL_PAGE, CONTACT_PAGE} y `preserve_url = YES` |
| R-M01 | media | ruta en `product-media-relations.csv` cuyo producto propietario recibió MIGRATE por R-P01 |
| R-M05 | media | ruta con `DATABASE_CATEGORY`/`BRAND_LOGO` en `media-usage-evidence.csv` cuyo término propietario recibió MIGRATE por R-T01/R-T03 |
| R-M04 | media | ruta con `PUBLIC_SOURCE` desde una página aprobada por R-G02-LOW |
| R-M02 | media | `DERIVED` + `original_ids` no vacío + sin flag `*_USED` |
| R-M03 | media | prefijo exacto `system/backoffice/`, `fonts/`, o `system/` (excluyendo `system/files/`) |

**NO implementadas** (permanecen exactamente como en la fase de análisis, sin código nuevo
que las toque): R-P02, R-P03, R-P04, R-P05, R-T02, R-S01, R-G01, R-G03, R-M01b/04b/05b,
R-M06, R-M07, R-X01. Ninguna decisión de
[10-human-decisions.md](../review-analysis/10-human-decisions.md) fue respondida.
`MERGE` sigue en 0: no se implementó ningún ganador determinista para los 53 grupos
canónicos múltiples.

## Principio arquitectónico: capa separada

`Policy::decisions( Sources $s, array $manual = [] ): array` es una función pura: recibe las
mismas fuentes versionadas que `Planner` ya carga (`Sources::FILES`, sin cambios) y el
contenido del fichero de decisiones manuales, y devuelve un mapa `entity_key => decisión`
con exactamente el mismo contrato que `subset-decisions.json` espera, más cuatro campos de
procedencia: `origin`, `rule_id`, `reason_code`, `evidence`.

**No escribe nada.** No llama a `Runner`. No toca WordPress ni la base de datos. No conoce
`Storage::guard()`, lotes, confirmaciones o el bloqueo de importación completa.

### El único punto de integración con `Planner`

`Planner::build()` cambia en tres lugares, todos en
[`migration/Planner.php`](../../../wordpress/wp-content/plugins/psindustrial-core/migration/Planner.php):

1. Se calcula `$policy = 'full' === $scope ? Policy::decisions( $s, $d ) : array();`
   — **sólo se calcula para `scope=full`**. Para `scope=subset` es siempre un array vacío.
2. La búsqueda de decisión pasa de `$d['entities'][$key] ?? null` a
   `$d['entities'][$key] ?? $policy[$key] ?? null`. La decisión manual **siempre gana**;
   política sólo rellena huecos.
3. Un bloque nuevo, después de la extracción/validación existente, copia
   `rule_id`/`reason_code`/`evidence`/`risk=LOW` al `entry` **sólo si la acción propuesta
   sobrevivió** la validación existente (`$e['action'] === $decision['action']`) — si
   `Planner` revirtió la decisión a REVIEW (hash inválido, dependencia no aprobada, etc.),
   la entrada no se atribuye a la política.

**Por qué el scope-gate es la salvaguarda central.** El filtro de subset
(`if ('subset' === $scope && ! $decision && ...) { return; }`) determina qué filas entran
siquiera en un plan de ensayo. Si la política participara ahí, el ensayo subset dejaría de
tener 17 entradas y el gate de ≤25 objetos mutables de `Runner::batch()` podría alcanzarse
con filas nunca revisadas por un humano. Al calcular `$policy` como `[]` para `subset`, el
filtro se comporta exactamente igual que antes de esta fase — verificado en
[02-rule-test-results.md](02-rule-test-results.md).

**Por qué un plan `full` con política activa sigue sin poder ejecutarse.** `Runner::batch()`
no se tocó: sigue exigiendo `'subset' === $plan['scope']` o lanza
`VALID_LOCAL_SUBSET_PLAN_REQUIRED`. Verificado explícitamente en
[02-rule-test-results.md](02-rule-test-results.md).

## Salvaguardas de `Policy` (no del motor)

- **Existencia de término ≠ atribución de producto.** R-T01/R-T03 nunca incluyen
  `categories`/`brand` en su propia decisión; sólo crean el término.
- **Marca nunca por inferencia.** R-P01 sólo asigna `brand` cuando
  `brand_confidence === 'CONFIRMED'` (1 de las filas elegibles). El resto migra sin marca,
  con la razón explícita en `notes`.
- **Categoría nunca inventada.** R-P01 exige que la categoría del producto sea, ella misma,
  una de las aprobadas por R-T01 (no basta con que exista en `category-master.csv`).
- **Registros vacíos/prueba nunca migran.** El filtro `'' === trim($r['name'])` excluye los
  IDs 151–165 antes de cualquier otra condición.
- **`R-M02` nunca descarta un original.** La condición exige `original_ids` no vacío — una
  fila sin ese campo (es decir, un original) nunca puede recibir SKIP por esta regla.
- **`R-M03` es un prefijo de directorio exacto**, no una coincidencia parcial. Verificado con
  guardas de falso positivo en los tests.
- **static_product nunca migra automáticamente.** Ninguna regla produce una decisión para
  una clave `static:`.

## Hallazgo y corrección durante la implementación: colisión de alias binarios

Al ejecutar el primer FULL DRY RUN con la política activa, la propia salvaguarda existente
de `Planner` (`SOURCE_KEY_HAS_TWO_OWNERS`, sin modificar) detuvo la construcción del plan.

**Causa:** la decisión manual de `asset:fichas/puerta-420.pdf` declara
`binary_aliases: ['system/files/images/productos/9856...']` (el mismo PDF, dos rutas de
origen, un único attachment — el caso documentado en
[20-importer-summary.md](../20-importer-summary.md)). `Policy` no conocía esa relación y, de
forma independiente, propuso R-M01 para esa misma ruta alias (pertenece también al producto
64, que satisface R-P01) — dos entidades reclamando la misma identidad subyacente.

**Corrección:** `Policy::decisions()` recibe ahora el fichero de decisiones manuales
(`$manual`) y calcula el conjunto de rutas ya declaradas como `binary_aliases` de **cualquier**
decisión manual; `media()` nunca produce una decisión propia para una ruta en ese conjunto.
No se tocó `Planner`'s `SOURCE_KEY_HAS_TWO_OWNERS`: la protección hizo exactamente lo que
debía. Test de regresión específico en
[`tests/policy.php`](../../../wordpress/wp-content/plugins/psindustrial-core/tests/policy.php).

## Hallazgo real revelado por las reglas LOW (no un defecto de esta fase)

Al validar los medios propuestos contra los bytes reales del archivo (código existente de
`Planner`, sin cambios), **7 PDFs de ficha técnica contienen una referencia `/EmbeddedFile`**
y son rechazados por la comprobación de seguridad existente de `Media::file_valid()`
(`preg_match('#/(?:JavaScript|JS|OpenAction|Launch|EmbeddedFile)\b#i', $data)`), pese a que
su SHA-256 coincide exactamente con el registrado en `media-master.csv`. Esto revierte,
correctamente y en cascada, **11 productos** que dependían de esas fichas técnicas
(65, 66, 67, 68, 69, 70, 71, 72, 73, 77, 95) de vuelta a REVIEW. Detalle completo en
[06-known-issues.md](06-known-issues.md). No se relajó la comprobación de seguridad.
