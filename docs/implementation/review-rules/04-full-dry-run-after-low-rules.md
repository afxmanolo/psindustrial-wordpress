# FULL DRY RUN — después de las reglas LOW

Ejecutado con `Planner::build('full')` (política activa) contra `psindustrial_wp_dev`
local. **DRY RUN únicamente: sin ejecución real, sin publicación, sin escritura en
WordPress.** Confirmado antes/después: recuento de `posts`/`terms` idéntico
(verificado en [tests/policy.php](../../../wordpress/wp-content/plugins/psindustrial-core/tests/policy.php)
y en la verificación final de este documento). Reporte completo exportado a
`docs/implementation/importer-reports/full-dry-run-after-low-rules.json`.

## ANTES (fase de importador, [28-full-dry-run-report.md](../28-full-dry-run-report.md))

```
2.399 fuentes analizadas
   15 UNCHANGED
  435 SKIP     (código interno)
1.949 REVIEW
    0 CREATE / UPDATE / MERGE / ERROR
```

## DESPUÉS (esta fase, política LOW activa)

```
2.399 fuentes analizadas   (idéntico — ninguna fuente se añadió ni se quitó)
   15 UNCHANGED             (idéntico — los 15 objetos del subset no cambian)
1.281 SKIP                  (435 código interno + 846 nuevos por R-M02/R-M03)
  153 CREATE                (nuevas propuestas LOW, en DRY RUN — nada se creó realmente)
  950 REVIEW                (1.949 − 999 resueltas)
    0 UPDATE / MERGE / ERROR
```

**999 filas dejaron de ser REVIEW: 846 pasan a SKIP y 153 a CREATE.**
`1.949 − 999 = 950` siguen en REVIEW. Verificación aritmética:
`15 + 1.281 + 153 + 950 = 2.399`. ✔

## Desglose por `source_type`

| source_type | UNCHANGED | SKIP | CREATE | REVIEW | total |
|---|---:|---:|---:|---:|---:|
| media | 8 | 846 | 94 | 583 | 1.531 |
| category | 2 | 0 | 33 | 3 | 38 |
| brand | 1 | 0 | 11 | 0 | 12 |
| product | 2 | 0 | 10 | 153 | 165 |
| static_product | 1 | 0 | 0 | 31 | 32 |
| page | 1 | 435 | 5 | 176 | 617 |
| missing_media | 0 | 0 | 0 | 4 | 4 |
| **total** | **15** | **1.281** | **153** | **950** | **2.399** |

## Desglose por `rule_id` (filas que la política resolvió, es decir CREATE o SKIP nuevo)

| rule_id | resueltas | de las cuales revertidas a REVIEW por validación existente |
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
| **total resuelto** | **999** | **18** |

Las 18 revertidas están documentadas fila por fila, con motivo, en
[low-rule-decisions.csv](low-rule-decisions.csv) y explicadas en
[06-known-issues.md](06-known-issues.md). No se cuentan como resueltas: siguen en los 950
REVIEW finales.

Fuente completa, fila por fila, de las 999 filas resueltas y las 18 revertidas:
[low-rule-decisions.csv](low-rule-decisions.csv) (1.025 filas: 999 aplicadas + 18 revertidas
+ 8 que coinciden con una decisión manual preexistente y por tanto no las decidió esta capa).

## brand y category — caso especial

`category`/`brand` muestran 3/0 REVIEW en vez de 36/11 menos las aprobadas, porque dos filas
adicionales (`category:1`, `category:8`, `brand:1`) ya eran UNCHANGED por el subset test
antes de esta fase; no se cuentan aquí como "resueltas por LOW" (ver
[low-rule-decisions.csv](low-rule-decisions.csv), filas `N/A`).

## Verificación de integridad

- `mode = DRY_RUN`, `status = VALIDATED` en el plan resultante.
- `posts`/`terms` antes y después de `Planner::build('full')`: idénticos.
- `Runner::batch()` sobre este mismo `run_id` con la frase de confirmación correcta:
  lanza `VALID_LOCAL_SUBSET_PLAN_REQUIRED` — **el plan sigue sin poder ejecutarse.**
- `summary.actions.MERGE = 0`.
- `Planner::build('subset')` ejecutado en la misma sesión: 17 entradas, 2 REVIEW, idéntico al
  comportamiento previo a esta fase.
