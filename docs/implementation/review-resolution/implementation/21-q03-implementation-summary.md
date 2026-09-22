# Q03 — implementación

Rama `feature/review-resolution`. Implementa exactamente las decisiones aprobadas por el
usuario sobre [q03-decision-questionnaire.md](../q03-decision-questionnaire.md):
`Q03-GLOBAL=A`, `Q03-A=A`, `Q03-B=A`, `Q03-C=A`, `Q03-D=A (conflicto de descripción
preservado)`, `Q03-E=A sólo para ids 24+139 (id=3 KEEP_REVIEW)`. Q08, Q09, Q12 no se tocaron.
Ningún fabricante dudoso se asignó. No se publicó contenido. No se ejecutó una importación
real.

## Qué se construyó

- `EditorialDecisions::q03()` + `q03_consolidate()` (núcleo compartido) + `q03_field_select()`
  (política genérica de 3 vías), en
  [EditorialDecisions.php](../../../../wordpress/wp-content/plugins/psindustrial-core/migration/EditorialDecisions.php).
- `Policy::brand_conflict_products()`: accesor de sólo lectura nuevo, expone el registro
  `BRAND_CONFLICT_PRODUCTS` ya existente (nunca modificado) para que `q03_consolidate()` lo
  consulte automáticamente — ver [24-q03-brand-unresolved.md](24-q03-brand-unresolved.md)
  para por qué esto resultó imprescindible, no cosmético.
- `EditorialDecisions::q01()`: una única generalización de 6 líneas (el mapa de "grupos con
  ganador ya aprobado" que ya consultaba, ampliado de sólo Q02 a Q02+Q03) — reutiliza
  exactamente la lógica de propiedad existente, sin una segunda implementación.
- `editorial-decisions.json`: nueva clave `q03_group_consolidation` con 4 sub-bloques
  (`global_multi_category`, `name_consolidation`, `brand_unresolved_groups`,
  `kelley_partial`) — declara qué grupos están aprobados y las escasas decisiones
  genuinamente editoriales (preferencia de título documentado, marca forzada sin resolver,
  el reparto exacto de Kelley); el ganador, la unión de categorías/medios y el estado de
  conflicto de la descripción se re-derivan en cada build desde
  `product-master.csv`/catálogo SQL, exactamente como ya hacía Q02.
- 4 suites de test nuevas (1.575 verificaciones) + 1 corrección a una aserción obsoleta en
  `tests/q01-content-ownership.php` (mejora esperada, no regresión — ver
  [26-q03-tests.md](26-q03-tests.md)).
- `q03-consolidation-audit.csv` y `q03-field-provenance.csv`, generados directamente desde el
  plan vivo, nunca escritos a mano.

## Resultado numérico

```
                 ANTES (Q07+Q10+Q11+Q13)      DESPUES (+ Q03)
total            2.399                        2.399
UNCHANGED           15                           15
SKIP             1.467                        1.534
CREATE             407                          511
REVIEW             510                          339
ERROR                 0                            0
```

**171 filas** salieron de REVIEW (510→339). Detalle completo, con la aritmética exacta por
sub-decisión, en [27-dry-run-after-q03.md](27-dry-run-after-q03.md).

## El hallazgo no anticipado: 3 de los 16 grupos Q03-GLOBAL no pueden materializarse

`puertas-contra-incendio.php`, `puerta-estandar.php` y `puerta-estandar-reforzada.php` —
tres de los grupos que el análisis previo (y la aprobación del usuario) clasificó
correctamente como category-only, con identidad sólida — **no producen contenido extraíble**:
la página legacy física que comparten sus filas SQL no tiene cuerpo HTML estático fuera de
`<nav>/<header>/<footer>/<form>` que `Sources::content()` pueda extraer, sea cual sea el ID
elegido como ganador (los tres comparten literalmente el mismo archivo `.php`, así que esto
es independiente de cualquier decisión de esta fase). `Planner`'s propio guardia
`NO_STATIC_EDITORIAL_BODY` ya existía antes de esta tarea y correctamente rechaza crear un
producto sin contenido — nunca se relajó ni se rodeó.

Se descubrió una segunda consecuencia, más seria, de este mismo problema, y se corrigió
**antes** de dejar cualquier decisión escrita: sin esa corrección, los miembros perdedores de
estos 3 grupos quedaban `SKIP` ("consolidado en el ganador") mientras el propio ganador caía
a `REVIEW` — perdiendo 9 filas de producto sin dejar rastro. `q03_consolidate()` ahora
verifica la extracción de contenido **antes** de escribir cualquier decisión y rechaza el
grupo completo si falla, exactamente el mismo principio "fail closed" que ya usa para medios
inválidos. Detalle completo en [22-q03-multi-category.md](22-q03-multi-category.md).

## El segundo hallazgo: MOOVI necesitaba el mismo tratamiento que B/C/D, y casi no lo recibió

Al construir el chequeo de marca sólo para los 3 grupos que la tarea nombró explícitamente
(Modern Steel/LiftMaster/Thermospan), `barreras-estacionamiento-moovi50rm.php` (uno de los 16
GLOBAL) estuvo a punto de recibir una marca auto-asignada (BFT) — sus valores de `brand_id`
concuerdan entre sí, así que la comprobación genérica de acuerdo no detecta ningún problema.
Pero MOOVI **ya estaba** en el registro `Policy::BRAND_CONFLICT_PRODUCTS` (evidencia de enlace
"BlueGiant frente a BFT", documentada antes de esta fase) — y el propio análisis previo de
esta tarea ([02-category-only-groups.md](../q03-analysis/02-category-only-groups.md)) ya
había anotado explícitamente "marca NO se resuelve aquí" para este grupo. Se corrigió
sustituyendo la lista manual por una comprobación automática contra el registro existente de
Policy — MOOVI, y cualquier caso futuro parecido, queda cubierto sin depender de que alguien
lo recuerde. Detalle en [24-q03-brand-unresolved.md](24-q03-brand-unresolved.md).

## Documentos de esta entrega

- [22-q03-multi-category.md](22-q03-multi-category.md) — Q03-GLOBAL (16 grupos, 13 resueltos).
- [23-q03-name-consolidation.md](23-q03-name-consolidation.md) — Q03-A (7 grupos).
- [24-q03-brand-unresolved.md](24-q03-brand-unresolved.md) — Q03-B/C/D + el hallazgo MOOVI.
- [25-q03-kelley.md](25-q03-kelley.md) — Q03-E (consolidación parcial).
- [26-q03-tests.md](26-q03-tests.md) — las 18 suites, 8.400 verificaciones.
- [27-dry-run-after-q03.md](27-dry-run-after-q03.md) — FULL DRY RUN final.
- [28-remaining-review-after-q03.md](28-remaining-review-after-q03.md) — las 339 REVIEW
  restantes.

## Fuera de alcance, verificado intacto

Q08 (89 medios sin referencia), Q09 (todos los conflictos de marca — Modern Steel,
LiftMaster/Blue Giant, Thermospan, MOOVI, Sellos Nacionales — siguen sin fabricante asignado),
Q12 (3 páginas utilitarias), redirects SEO, categoría principal/breadcrumb, despliegue a
producción. `Storage::guard()`, el guardia de `Runner::batch()` para planes `full`,
`Media::file_valid()`, `PdfApprovals`, los hashes de origen, la idempotencia y la detección de
edición humana no se tocaron. `/legacy` permanece intacto. Ninguna base de datos legacy fue
consultada ni modificada (no existe conexión a ninguna — `Sources.php` sólo lee el dump SQL
estático como archivo). No se ejecutó `Runner::batch()`. No se publicó contenido. La versión
del plugin permanece en `0.4.0`.
