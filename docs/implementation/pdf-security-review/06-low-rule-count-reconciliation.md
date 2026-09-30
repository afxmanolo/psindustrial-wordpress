# Reconciliación 999 vs 1.025

Verificado directamente contra
[`low-rule-decisions.csv`](../review-rules/low-rule-decisions.csv) (1.025 filas). No se
modificó ninguna regla para hacer coincidir las cifras; el desglose es exactamente lo que el
archivo ya contenía.

## La aritmética exacta

```
1.025  filas totales en low-rule-decisions.csv
─  999  APLICADAS   (846 SKIP + 153 CREATE)  ← lo que 00-low-rules-summary.md reporta como "resueltas"
─   18  REVERTIDAS  (11 R-P01 producto + 7 R-M01 medio)
─    8  YA-MANUAL   (la propuesta de política coincide con una decisión manual/subset preexistente)
────────────────────────────────────────────
= 1.025
```

`low-rule-decisions.csv` registra **cada fila para la que `Policy` propuso una decisión**,
incluidas las que no llegaron a "resolver" nada nuevo. `999` es el número de filas que
**dejaron de estar en REVIEW** por causa de esta fase — la cifra correcta para comparar
contra el `REVIEW` antes/después del FULL DRY RUN. Las otras 26 filas (18+8) están en el CSV
por completitud/auditabilidad, no porque el conteo de 999 esté mal.

## Las 26 filas restantes, desglosadas

### 18 revertidas — exactamente los 7 PDF y los 11 productos de esta auditoría

| rule_id | filas | causa |
|---|---:|---|
| `R-M01` | 7 | Los 7 PDF de este informe: `Policy` propuso MIGRATE por relación SQL confirmada con un producto aprobado, pero la validación existente de bytes/seguridad de `Planner` (`Media::file_valid()`) los rechazó antes de crear el attachment |
| `R-P01` | 11 | Los 11 productos que dependen de esos 7 PDF: `Policy` propuso MIGRATE, pero el mecanismo de dependencias existente de `Planner` revirtió la decisión porque uno de sus medios (la ficha técnica) no quedó aprobado |

Esta auditoría (documentos 01–05) es exactamente la explicación detallada de estas 18 filas.
No son "reglas evaluadas pero bloqueadas" en el sentido de que la regla fallara — la
condición de `R-M01`/`R-P01` se cumplía perfectamente (evidencia de propietario/identidad
correcta); lo que bloqueó fue una capa de validación **distinta y posterior**, ya existente
en el importador, funcionando exactamente como debía.

### 8 "ya manuales" — la propuesta de política coincide con una decisión humana preexistente

| source_key | rule_id | por qué |
|---|---|---|
| `category:1` | R-T01 | Ya decidido manualmente en el ensayo subset (`subset-decisions.json`) |
| `category:8` | R-T01 | ídem |
| `brand:1` | R-T03 | ídem |
| `sql:productos:63` | R-P01 | ídem |
| `sql:productos:64` | R-P01 | ídem |
| `php:nosotros.php` | R-G02-LOW | ídem |
| `asset:images/tg.png` | R-M05 | ídem |
| `asset:system/files/images/categorias/59ca6b73...` | R-M05 | ídem |

Estas 8 claves ya tenían una decisión humana explícita en `subset-decisions.json` **antes**
de que existiera la capa `Policy`. Cuando `Policy::decisions()` evalúa sus propias
condiciones sobre los mismos datos, llega — por coincidencia de criterio, no por diseño — a
la misma acción (`MIGRATE`) que la decisión manual ya había tomado. `Planner` usa siempre la
decisión manual (`$d['entities'][$key] ?? $policy[$key] ?? null` — la manual gana), así que
la propuesta de política para estas 8 claves **nunca decidió nada**; sólo coincide. Por eso
`low-rule-decisions.csv` las marca `old_action = UNCHANGED (pre-existing manual/subset
decision)`, no `REVIEW`, y no se cuentan en el "antes" de 1.949 REVIEW que la fase LOW redujo.

## No representan ni duplicados ni un error de conteo

- No son duplicados: cada una de las 1.025 filas corresponde a una `source_key` distinta;
  `low-rule-decisions.csv` no tiene ninguna clave repetida (verificado:
  `len(rows) == len(set(source_key))`).
- No son dependencias en el sentido de "esperando a otra regla LOW": las 18 revertidas
  dependen de una validación de **seguridad/integridad**, no de otra decisión de política
  pendiente. No se resuelven implementando más reglas LOW — se resuelven con la decisión
  editorial descrita en [05-recommendations.md](05-recommendations.md).
- No es un error de las cifras publicadas en la fase anterior: `00-low-rules-summary.md`
  ([../review-rules/00-low-rules-summary.md](../review-rules/00-low-rules-summary.md)) ya
  documentaba explícitamente las 18 revertidas y las 7 rutas exactas antes de esta auditoría;
  esta fase amplía esa explicación con la causa raíz de cada una, no la corrige.

## Verificación

```
999 (APLICADAS) + 18 (REVERTIDAS) + 8 (YA-MANUAL) = 1.025
846 (SKIP) + 153 (CREATE) = 999
11 (R-P01) + 7 (R-M01) = 18
```

Todas las sumas se verificaron directamente contra el contenido real de
`low-rule-decisions.csv`, no recalculadas de memoria.
