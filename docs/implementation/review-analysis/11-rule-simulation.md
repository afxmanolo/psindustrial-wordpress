# Simulación

Simulación analítica. **No se ejecutó el importador, no se modificó el manifest, no se
escribió en la base de datos.** Los resultados se obtienen aplicando las reglas de
[09](09-proposed-rules.md) sobre los CSV maestros.

## Reproducibilidad

La clasificación es una función pura de los maestros: sin aleatoriedad, sin red, sin base de
datos, sin estado. Ejecutada dos veces sobre los mismos ficheros produce el mismo
`review-classification.csv` byte a byte.

Entradas exactas: `all-review.csv`, `product-master.csv`, `category-master.csv`,
`brand-master.csv`, `content-master.csv`, `media-master.csv`, `product-media-relations.csv`,
`media-usage-evidence.csv`, `canonical-candidate-groups.csv`, `static-product-supplement.csv`,
`missing-media-references.csv`.

Los registros de conflicto (D03/D06/D07) se toman **literalmente** de la tabla de
`manual-decisions-required.md`; no se derivan por heurística.

## Escenario A — sólo reglas LOW

Automatizables sin intervención humana previa.

| source_type | MIGRATE | MERGE | SKIP | KEEP_REVIEW | total |
|---|---:|---:|---:|---:|---:|
| media | 115 | 0 | 846 | 562 | 1.523 |
| page | 5 | 0 | 0 | 176 | 181 |
| product | 21 | 0 | 0 | 142 | 163 |
| category | 33 | 0 | 0 | 3 | 36 |
| static_product | 0 | 0 | 0 | 31 | 31 |
| brand | 11 | 0 | 0 | 0 | 11 |
| missing_media | 0 | 0 | 0 | 4 | 4 |
| **TOTAL** | **185** | **0** | **846** | **918** | **1.949** |

```
REVIEW iniciales        1.949
potencial MIGRATE         185
potencial SKIP            846
potencial MERGE             0
seguirían REVIEW          918
```

**Reducción: 1.031 filas (52,9 %) sin ninguna decisión humana.**

Aportación por regla:

| Regla | Acción | Filas |
|---|---|---:|
| R-M02 derivados | SKIP | 561 |
| R-M03 infraestructura | SKIP | 285 |
| R-M04 medios de página aprobada | MIGRATE | 42 |
| R-M05 medios de término aprobado | MIGRATE | 38 |
| R-M01 medios de producto aprobado | MIGRATE | 35 |
| R-T01 categorías | MIGRATE | 33 |
| R-P01 productos limpios | MIGRATE | 21 |
| R-T03 marcas | MIGRATE | 11 |
| R-G02 institucionales y contacto | MIGRATE | 5 |

## Escenario B — LOW + MEDIUM (propuesta base)

Añade las reglas que requieren revisión previa pero no evidencia nueva.

| source_type | MIGRATE | MERGE | SKIP | KEEP_REVIEW | total |
|---|---:|---:|---:|---:|---:|
| media | 115 | 0 | 846 | 562 | 1.523 |
| page | 14 | 0 | 164 | 3 | 181 |
| product | 21 | 0 | 15 | 127 | 163 |
| category | 33 | 0 | 0 | 3 | 36 |
| static_product | 0 | 0 | 0 | 31 | 31 |
| brand | 11 | 0 | 0 | 0 | 11 |
| missing_media | 0 | 0 | 0 | 4 | 4 |
| **TOTAL** | **194** | **0** | **1.025** | **730** | **1.949** |

```
REVIEW iniciales        1.949
potencial MIGRATE         194
potencial SKIP          1.025
potencial MERGE             0
seguirían REVIEW          730
```

**Reducción: 1.219 filas (62,5 %).**

La diferencia frente al escenario A son 188 filas: 164 páginas que reexpresan una entidad ya
enumerada (R-G01), 15 registros vacíos/prueba (R-P03) y 9 landings SEO (R-G02).

## Por qué MERGE es 0

No es un fallo de la simulación. `db_image_ids` diverge en los 53 grupos canónicos múltiples,
y el importador exige un ganador único para todos los campos copiados. Ningún grupo lo tiene.
Detalle en [08](08-duplicate-merge-analysis.md).

## Efecto en cascada de las decisiones humanas

Si se respondieran las decisiones de política, sin trabajo editorial caso por caso:

| Decisión | Producto | Medios | Total adicional |
|---|---:|---:|---:|
| H1 · D02-a (política de unión de medios) | 52 | 76 | 128 |
| H3 · D02-c (categoría de fichas estáticas) | 31 | — | 31 |
| H4 · D05 (vacíos y prueba) | 15 | 16 | 31 |

Con las tres respondidas, los 730 REVIEW del escenario B bajarían aproximadamente a **540**.

El resto se concentra en H2 (162 filas de fusión editorial real) y H8 (89 medios que
necesitan evidencia de tráfico externa al repositorio).

## Lo que la simulación NO afirma

- No afirma que 194 objetos deban crearse: afirma que su evidencia lo permitiría.
- No afirma que 1.025 fuentes sean prescindibles: `SKIP` es «no crear objeto de contenido»,
  no «borrar archivo» ni «liberar URL».
- No sustituye al DRY RUN del importador. Un DRY RUN real recalcularía hashes, validaría
  bytes y podría rechazar filas que aquí aparecen como viables.
- No autoriza ninguna ejecución.

## Verificación aritmética

Todas las tablas suman 1.949. Comprobaciones cruzadas superadas:

- 225 rutas con relación SQL de producto = 35 MIGRATE + 190 KEEP_REVIEW.
- 39 medios de término = 38 MIGRATE + 1 KEEP_REVIEW.
- 561 derivados + 285 infraestructura = 846 SKIP de medios.
- 22 grupos canónicos unitarios = 21 R-P01 + 1 R-P05 (ID 150).
- 163 producto = 21 + 15 + 126 + 1.
- 181 página = 164 + 14 + 3.
