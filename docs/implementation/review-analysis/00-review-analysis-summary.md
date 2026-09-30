# Análisis y clasificación de los 1.949 REVIEW — resumen ejecutivo

Fecha: 2026-09-18. Rama: `feature/legacy-importer`. **Fase exclusivamente analítica.**

No se modificó código, WordPress, la base de datos, el manifest ni `/legacy`. No se ejecutó
el importador. No hubo commit ni push. Todo lo entregado son documentos y CSV bajo
`/docs/implementation/review-analysis/`.

Las cifras de partida se verificaron contra el repositorio: `all-review.csv` contiene
exactamente 1.949 filas y el desglose por `source_type` coincide con
[28-full-dry-run-report.md](../28-full-dry-run-report.md) (media 1.523, product 163, page 181,
category 36, static_product 31, brand 11, missing_media 4).

---

## 1. ¿Por qué existen realmente los 1.949 REVIEW?

**Porque el importador no tiene ningún registro de decisión aprobado para ellos, no porque
haya detectado 1.949 problemas.**

En `Planner::build()` toda entrada nace con `action = 'REVIEW'`. Sólo cambia si existe una
entrada en `subset-decisions.json` (hoy: los 15 objetos del ensayo) o si la fila está
clasificada `LEGACY_INTERNAL` (435 → SKIP). La aritmética es exacta:
`2.399 − 435 − 15 = 1.949`.

Esto reencuadra el problema. No hay que arreglar 1.949 defectos: hay que **producir
decisiones** para 1.949 fuentes, y esas fuentes comparten muy pocas decisiones subyacentes.

Dos observaciones refuerzan el punto:

- El campo `notes` de `all-review.csv` es **texto constante por tipo**: los 1.523 medios
  comparten una única cadena. No discrimina nada. La causa real sólo aparece al cruzar cada
  fila con su maestro.
- El mismo artefacto legacy se enumera bajo varios namespaces. 167 filas son la segunda
  representación de una entidad ya contada — de forma verificable, **los 32 `PRODUCT_PAGE`
  sin `related_product_ids` son exactamente los 32 ficheros de `static-product-supplement.csv`**.

## 2. ¿Cuántas causas fundamentales encontramos?

**14 códigos**, de los cuales 13 tienen filas. Detalle en [01](01-review-reason-taxonomy.md).

Cuatro concentran el 82 %: medios con propietario pendiente (582), derivados del CMS (561),
infraestructura (285) y propiedad de fuente indefinida (167).

## 3. ¿Cuántos podrían resolverse con reglas LOW risk?

**1.031 filas (52,9 %), sin ninguna decisión humana previa.** 846 `SKIP` y 185 `MIGRATE`.

## 4. ¿Cuántos requerirían reglas MEDIUM?

**188 filas adicionales**, que elevan el total resuelto a 1.219 (62,5 %): 164 páginas que
reexpresan una entidad ya enumerada, 15 registros vacíos/prueba y 9 landings SEO.

## 5. ¿Cuántos deben permanecer REVIEW?

**730.** De ellos, 596 son ambigüedad correctamente preservada (riesgo MEDIUM) y 134 son
decisiones editoriales reales (riesgo HIGH).

Con las tres decisiones de política respondidas (H1, H3, H4) bajarían a unas 540.

## 6. ¿Cuántas decisiones humanas distintas hay realmente?

**7 del catálogo existente**, desglosadas en **9 preguntas accionables** porque D02 agrupa
tres problemas que se responden por separado. Detalle en [10](10-human-decisions.md).

Tres son decisiones de política de bajo esfuerzo que desbloquean 190 filas. El trabajo
editorial irreducible es uno solo: la fusión caso por caso de 27 grupos canónicos.

**Ninguna de las nueve bloquea el inicio de la migración.**

## 7. ¿Qué porcentaje del problema corresponde a medios?

**1.523 de 1.949 = 78,1 %.** Y es también donde está la mayor reducción segura: 846 filas de
medios (43,4 % del total) se resuelven con dos reglas LOW.

## 8. ¿Qué regla produciría la mayor reducción segura?

**R-M02 — derivados del CMS legacy: 561 filas, riesgo LOW.**

Son variantes de tamaño que el CMS antiguo generaba desde un original que se conserva como
fila independiente. Los 561 están marcados sin referencia pública directa, y WordPress
genera sus propios tamaños al importar el original.

Junto a R-M03 (285 assets de backoffice y fuentes de iconos) suman **846 filas, el 43,4 % del
problema**, sin depender de ninguna decisión pendiente.

## 9. ¿Qué errores serían más peligrosos si automatizáramos demasiado?

Por orden de daño ([12](12-risk-analysis.md)):

1. **Fusionar los 53 grupos canónicos con un ganador por regla.** `db_image_ids` diverge en
   los 53; elegir automáticamente produciría fichas que ningún registro legacy describe, y el
   resultado *parecería* válido tras publicar. La regla de ganadores mixtos del importador ya
   lo previene: relajarla para bajar el conteo sería el peor cambio posible.
2. **Asignar marca por `STRONG_INFERENCE`** en 20 de las 21 fichas limpias: convertiría las
   contradicciones actuales del sitio en dato estructurado.
3. **Descartar los 89 medios sin referencia.** La ausencia de referencia local no demuestra
   orfandad (D09).
4. **Aplicar el SKIP de 164 páginas sin la fase de URLs**: correcto como decisión de
   contenido, catastrófico como decisión de URL.
5. **Interpretar `SKIP` como permiso de borrado**: destruiría 561 archivos que aún sirven
   URLs históricas.

## 10. ¿Cuál debería ser el próximo paso técnico?

**Someter a revisión humana este análisis y, si se aprueba, implementar únicamente las
reglas LOW como un generador de decisiones que alimente `subset-decisions.json`, ejecutado
siempre en DRY RUN.**

Concretamente, y en este orden:

1. **Aprobar o corregir la taxonomía y las reglas** de [01](01-review-reason-taxonomy.md) y
   [09](09-proposed-rules.md). Es una revisión de criterio, no de código.
2. **Responder H1, H3 y H4** ([10](10-human-decisions.md)): tres decisiones de política que
   desbloquean 190 filas adicionales con esfuerzo bajo.
3. **Implementar un productor de decisiones** para las reglas LOW aprobadas, que escriba un
   fichero de decisiones con el mismo contrato que el actual, sin tocar `Planner`, `Runner`
   ni `Identity`.
4. **Ejecutar FULL DRY RUN** y comparar el resultado real con la simulación de
   [11](11-rule-simulation.md). Las discrepancias indicarían un error de este análisis.
5. Sólo entonces, y con autorización explícita, valorar una ejecución acotada.

Lo que **no** debe ser el próximo paso: reescribir el importador, relajar la regla de
ganadores mixtos, o ejecutar una importación completa. El gate de 25 objetos mutables y el
rechazo de planes `full` en `Runner::batch()` siguen siendo correctos y no deben tocarse.

---

## Resultado de la clasificación

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

`MERGE = 0` no es una carencia del análisis: es el resultado correcto. Ningún grupo canónico
tiene un ganador determinista bajo la regla actual del importador
([08](08-duplicate-merge-analysis.md)).

## Advertencia sobre la columna `proposed_action`

`review-classification.csv` contiene una propuesta **analítica**. No modifica el manifest, no
autoriza ninguna ejecución y no sustituye al DRY RUN del importador, que recalcularía hashes
y validaría bytes.

`SKIP` significa, igual que para los 435 SKIP existentes, *no se migra como contenido;
archivo y evidencias preservados*. **No borra archivos, no libera URLs, no altera `/legacy`.**

## Entregables

| Documento | Contenido |
|---|---|
| [01](01-review-reason-taxonomy.md) | Taxonomía de 14 causas y niveles de evidencia |
| [02](02-review-distribution.csv) | Distribución agregada (26 combinaciones) |
| [03](03-media-review-analysis.md) | Medios — 1.523 filas |
| [04](04-product-review-analysis.md) | Productos — 163 filas |
| [05](05-static-product-analysis.md) | Fichas estáticas — 31 filas |
| [06](06-page-review-analysis.md) | Páginas — 181 filas |
| [07](07-taxonomy-brand-review-analysis.md) | Categorías y marcas — 47 filas |
| [08](08-duplicate-merge-analysis.md) | Duplicados y MERGE |
| [09](09-proposed-rules.md) | 23 reglas candidatas con condiciones exactas |
| [10](10-human-decisions.md) | 9 decisiones humanas accionables |
| [11](11-rule-simulation.md) | Simulación reproducible |
| [12](12-risk-analysis.md) | Criterios y análisis de riesgo |
| [review-classification.csv](review-classification.csv) | Una fila por cada uno de los 1.949 REVIEW |

## Asuntos registrados y no resueltos aquí

- La observación `/productos/` frente al rewrite `producto` queda como pendiente de la fase
  de URLs/SEO, según lo indicado. No se toca.
- Los documentos que afirman que el importador aún no tenía commit quedaron desactualizados
  respecto al commit `2f8e098`, ya autorizado. No se modifican en esta fase.
