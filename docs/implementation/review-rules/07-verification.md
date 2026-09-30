# Verificación final

Fecha: 2026-09-18. Todos los comandos siguientes son de lectura o de dry-run (ninguno
modifica WordPress, la base de datos ni `/legacy`).

## 1. `/legacy` intacto

```
$ git status --porcelain -uall -- legacy
(sin salida)
```

## 2. Ninguna modificación de la base de datos legacy

`Policy.php` no abre ninguna conexión a base de datos legacy ni la referencia; sólo lee CSV
locales vía `Sources`. `Planner.php` no gana ninguna nueva vía de escritura: sigue limitado a
`Storage::write()` (JSON privado fuera del webroot). Verificado además indirectamente:
`Sources::__construct()` valida `count($this->catalog['productos']) === count(product-master
rows)` en cada build (comprobación preexistente) y no se disparó ninguna excepción en
ninguna de las ejecuciones de esta fase.

## 3. Sólo `psindustrial_wp_dev` para pruebas

`Storage::guard()` (sin modificar) exige `wp_get_environment_type() === 'local'` y
`DB_NAME === 'psindustrial_wp_dev'`; se disparó antes de cada `Planner::build()`. Todas las
ejecuciones de esta fase corrieron contra esa base local.

## 4. Ninguna importación completa real

`Runner::batch()` no se modificó. Se verificó explícitamente en `tests/policy.php`:

```php
$throws( static fn() => Runner::batch( $full['run_id'], 'IMPORTAR SUBSET EN BORRADOR' ),
 'A full plan (policy-enriched or not) remains impossible to execute' );
```

Resultado: lanza `VALID_LOCAL_SUBSET_PLAN_REQUIRED`. Confirmado con verificación de
prueba pasada (ver [02-rule-test-results.md](02-rule-test-results.md)).

## 5. Ningún contenido publicado automáticamente

Todas las ejecuciones de `Planner::build()` de esta fase produjeron planes con
`mode: "DRY_RUN"` y `status: "VALIDATED"`. Ningún `Runner::batch()` se ejecutó con la
intención de crear objetos reales (la única invocación, arriba, es para confirmar que se
rechaza). Recuento de `posts`/`terms` idéntico antes/después de cada `build()`, verificado en
`tests/policy.php`.

## 6. Runner safety intacta

`Runner.php` no se modificó — `git status` no lo lista. Confirmado: límite de 25 objetos
mutables, frase de confirmación exacta, control de hashes del plan, re-validación de fuentes,
detección de conflicto/edición humana y rollback limitado siguen siendo el mismo código que
antes de esta fase.

## 7. Todas las pruebas anteriores siguen pasando

```
$ php wordpress/wp-content/plugins/psindustrial-core/tests/smoke.php
... (todas las comprobaciones "passed": true)

$ php wordpress/wp-content/plugins/psindustrial-core/tests/importer.php
{"passed":true,"checks":67,"subset_runs":[...]}
```

`67` es el mismo número de comprobaciones que antes de tocar ningún archivo de esta fase
(verificado ejecutando la suite antes y después del cambio).

## 8. Nuevas pruebas LOW pasan

```
$ php wordpress/wp-content/plugins/psindustrial-core/tests/policy.php
{"passed":true,"checks":6314,"full_run_id":"...","review_before":1949,"review_after":950}
```

## 9. Conteo REVIEW antes/después

```
ANTES:    1.949
DESPUÉS:    950
Resueltas:  999  (846 SKIP + 153 CREATE)
```

Ver desglose completo en [04-full-dry-run-after-low-rules.md](04-full-dry-run-after-low-rules.md).

## 10. Conteo de decisiones por `rule_id`

| rule_id | resueltas | revertidas |
|---|---:|---:|
| R-M02 | 561 | 0 |
| R-M03 | 285 | 0 |
| R-T01 | 33 | 0 |
| R-M05 | 38 | 0 |
| R-M04 | 26 | 0 |
| R-T03 | 11 | 0 |
| R-M01 | 30 | 7 |
| R-P01 | 10 | 11 |
| R-G02-LOW | 5 | 0 |

Fuente completa fila por fila: [low-rule-decisions.csv](low-rule-decisions.csv).

## 11. MEDIUM/HIGH siguen sin implementar

`Policy::RULES` contiene exactamente 9 entradas (R-T01, R-T03, R-P01, R-G02-LOW, R-M01,
R-M05, R-M04, R-M02, R-M03), todas LOW. Ninguna otra regla de
[09-proposed-rules.md](../review-analysis/09-proposed-rules.md) tiene código. Verificado
también por los 950 REVIEW restantes desglosados en
[05-remaining-review.md](05-remaining-review.md): ninguna causa MEDIUM/HIGH bajó a cero.

## 12/13. Sin commit, sin push

```
$ git log --oneline -1
b838134 merge: add safe legacy importer and review analysis
```

Sin commits nuevos. No se ejecutó `git push` en ningún momento de esta sesión.

## Estado de git al terminar

```
$ git status --short -uall
 M wordpress/wp-content/plugins/psindustrial-core/migration/Planner.php
 M wordpress/wp-content/plugins/psindustrial-core/psindustrial-core.php
?? docs/implementation/importer-reports/full-dry-run-after-low-rules.json
?? docs/implementation/importer-reports/policy-tests.json
?? docs/implementation/review-rules/00-low-rules-summary.md
?? docs/implementation/review-rules/01-implemented-rules.md
?? docs/implementation/review-rules/02-rule-test-results.md
?? docs/implementation/review-rules/03-low-rule-simulation.md
?? docs/implementation/review-rules/04-full-dry-run-after-low-rules.md
?? docs/implementation/review-rules/05-remaining-review.md
?? docs/implementation/review-rules/06-known-issues.md
?? docs/implementation/review-rules/07-verification.md
?? docs/implementation/review-rules/low-rule-decisions.csv
?? wordpress/wp-content/plugins/psindustrial-core/migration/Policy.php
?? wordpress/wp-content/plugins/psindustrial-core/tests/policy.php

$ git diff --check
(sin salida — sin problemas de espacio en blanco)

$ git diff --stat -- wordpress/wp-content/plugins/psindustrial-core/migration/Planner.php wordpress/wp-content/plugins/psindustrial-core/psindustrial-core.php
 .../psindustrial-core/migration/Planner.php  | 24 +++++++++++++++++++---
 .../psindustrial-core/psindustrial-core.php  |  2 +-
 2 files changed, 22 insertions(+), 4 deletions(-)
```

`/legacy`: sin cambios. `/wordpress`: sólo `psindustrial-core` (tema `psindustrial` sin
tocar); dentro del plugin, 2 archivos modificados (cambio mínimo y comentado) + 2 archivos
nuevos (`migration/Policy.php`, `tests/policy.php`).

Los dos ficheros `.json` bajo `importer-reports/` son artefactos de ejecución de las nuevas
pruebas (mismo patrón que `subset-plan.json`/`subset-execution-*.json` de la fase anterior),
no cambios de configuración ni de código.
