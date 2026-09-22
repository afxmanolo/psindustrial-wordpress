# Verificación — Q03

## Resultado global (las 18 suites)

```
smoke.php                        120 checks   PASS
importer.php                      67 checks   PASS
policy.php                     6.040 checks   PASS
pdf-approvals.php                310 checks   PASS
runner-media-validation.php       82 checks   PASS
editorial-decisions.php          374 checks   PASS   (Q02+Q06, sin regresion)
q05-pdf-resolution.php            66 checks   PASS
q01-content-ownership.php        329 checks   PASS   (actualizado -- ver abajo)
q04-static-products.php          219 checks   PASS
run-snapshot-retention.php        30 checks   PASS
q07-landings.php                 113 checks   PASS
q10-category-cleanup.php          23 checks   PASS
q11-missing-media.php             31 checks   PASS
q13-media-alias.php               14 checks   PASS
q03-multi-category.php           275 checks   PASS   (nuevo)
q03-name-consolidation.php       225 checks   PASS   (nuevo)
q03-brand-unresolved.php          55 checks   PASS   (nuevo)
q03-kelley.php                    27 checks   PASS   (nuevo)
──────────────────────────────────────────────────
TOTAL                          8.400 checks   0 fallos
```

## `tests/q03-multi-category.php` (nuevo, 275 checks)

Cubre Q03-GLOBAL y la política genérica de selección de campo (secciones 17/18 de la tarea).

| Caso pedido | Verificación |
|---|---|
| 16 grupos exactos → consolidación | Los 16 grupos de `editorial-decisions.json` se recorren uno a uno; 13 consolidan (ganador único + todos los demás `SKIP`), 3 rechazan el grupo completo (ningún miembro huérfano) |
| unión de categorías correcta | `puertas-blindadas.php` → exactamente `19+32`; `icaro-smart.php` → exactamente `9+11+18`; verificado contra el propio `category_id` fuente, nunca inventado |
| una única entidad destino | 4 filas SQL de `accesspro-fs1000speed.php` → exactamente 1 entidad `MERGE` |
| ninguna categoría principal inventada | ninguna decisión de los 13 ganadores lleva `primary_category`/`breadcrumb_category`/meta SEO |
| URLs múltiples → una entidad | verificado que los 3 miembros de `puertas-blindadas.php` ya comparten una única `legacy_url` en origen |
| ejecución repetida → mismo resultado | ≥60 filas `Q03-GLOBAL` comparadas entre dos reconstrucciones; 0 diferencias |
| grupo fuera de lista → no afectado | Kelley, Modern Steel y un producto de control (id 150, territorio Q09) nunca reciben `decision_sub_id=Q03-GLOBAL` |
| MOOVI: marca no asignada pese al acuerdo | regresión explícita del hallazgo documentado en [24-q03-brand-unresolved.md](24-q03-brand-unresolved.md) |
| descripción vacía + hermana con contenido → conserva la poblada | verificado para los 13 grupos resueltos: ningún ganador tiene descripción vacía cuando existe un hermano con contenido |
| valores idénticos → selección estable | `puerta-holandesa.php`: `AGREED` con `source_id` definido |
| valores contradictorios → no se sintetiza | `puertas-blindadas.php`: `CONFLICT`, `source_id=null`, identidad consolida igualmente |

## `tests/q03-name-consolidation.php` (nuevo, 225 checks)

Cubre exactamente los 7 grupos Q03-A (sección 19). Casos explícitos: espacios, mayúsculas,
errata, prefijo descriptivo, marca registrada (®), texto de modelo adicional, sufijo de marca
no confirmada en el título (Q03-A7). Verificado explícitamente que la normalización **no** se
extiende a ningún producto fuera de los 7 (>100 productos de control comprobados, ninguno
recibe `decision_sub_id=Q03-A`).

## `tests/q03-brand-unresolved.php` (nuevo, 55 checks)

| Caso pedido | Verificación |
|---|---|
| Q03-B → un producto, sin marca | 4 filas → 1 `MERGE` + 3 `SKIP`, `brand=''`, ninguna fila queda en `REVIEW` sólo por la marca desconocida |
| Q03-C → un producto, sin marca | igual, con evidencia de ambas atribuciones (LiftMaster/Blue Giant) preservada y legible desde `content-master.csv` |
| Q03-D → una identidad, sin marca, conflicto de contenido preservado | `description.status=CONFLICT`, `source_id=null`, identidad consolida igualmente |

## `tests/q03-kelley.php` (nuevo, 27 checks)

| Caso pedido | Verificación |
|---|---|
| Q03-E 24+139 → una identidad | título exactamente el de id 24, byte a byte; id 139 nunca aporta su texto "Blue Giant" |
| Q03-E id3 → sigue REVIEW | sin `decision_id` alguno; `Policy::CATEGORY_CONFLICT_PRODUCTS` verificado intacto (mismo único valor que antes) |
| marca sin asignar pese a la señal individual de id 24 | `Policy::BRAND_CONFLICT_PRODUCTS` contiene id 139, verificado explícitamente |
| página dependiente se desbloquea sin lógica nueva | retira vía `decision_id=Q01`, citando `sql:productos:24` |

## Actualización necesaria en una suite existente

`tests/q01-content-ownership.php` afirmaba que `accesspro-fs1000speed.php` (una página con 4
`related_product_ids`) quedaba sin decisión por ser "territorio de fusión editorial Q03,
fuera de esta fase" — cierto cuando se escribió, antes de que Q03 se implementara. Ahora
Q03-GLOBAL aprueba exactamente ese grupo, así que Q01 (sin ningún cambio propio más allá de
la generalización ya descrita) la retira correctamente. Se actualizó la aserción para reflejar
este comportamiento **mejorado**, y se añadió `puertas-contra-incendio.php` como el nuevo
ejemplo de página que sigue sin decisión — no ya por ambigüedad de propietario, sino porque su
grupo Q03-GLOBAL fue aprobado pero no pudo materializarse (ver
[22-q03-multi-category.md](22-q03-multi-category.md)).

## Comandos ejecutados

```bash
php wordpress/wp-content/plugins/psindustrial-core/tests/smoke.php
php wordpress/wp-content/plugins/psindustrial-core/tests/importer.php
php wordpress/wp-content/plugins/psindustrial-core/tests/policy.php
php wordpress/wp-content/plugins/psindustrial-core/tests/pdf-approvals.php
php wordpress/wp-content/plugins/psindustrial-core/tests/runner-media-validation.php
php wordpress/wp-content/plugins/psindustrial-core/tests/editorial-decisions.php
php wordpress/wp-content/plugins/psindustrial-core/tests/q05-pdf-resolution.php
php wordpress/wp-content/plugins/psindustrial-core/tests/q01-content-ownership.php
php wordpress/wp-content/plugins/psindustrial-core/tests/q04-static-products.php
php wordpress/wp-content/plugins/psindustrial-core/tests/run-snapshot-retention.php
php wordpress/wp-content/plugins/psindustrial-core/tests/q07-landings.php
php wordpress/wp-content/plugins/psindustrial-core/tests/q10-category-cleanup.php
php wordpress/wp-content/plugins/psindustrial-core/tests/q11-missing-media.php
php wordpress/wp-content/plugins/psindustrial-core/tests/q13-media-alias.php
php wordpress/wp-content/plugins/psindustrial-core/tests/q03-multi-category.php
php wordpress/wp-content/plugins/psindustrial-core/tests/q03-name-consolidation.php
php wordpress/wp-content/plugins/psindustrial-core/tests/q03-brand-unresolved.php
php wordpress/wp-content/plugins/psindustrial-core/tests/q03-kelley.php
php -l <cada archivo PHP tocado o nuevo>
```

Ninguna ejecutó `Runner::batch()` sobre un plan `full` con éxito — todas verifican que sigue
rechazado (`VALID_LOCAL_SUBSET_PLAN_REQUIRED`).
