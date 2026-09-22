# Verificación — Q07, Q10, Q11, Q13 y retención de snapshots

## Resultado global (las 14 suites)

```
smoke.php                        120 checks   PASS
importer.php                      67 checks   PASS
policy.php                     6.040 checks   PASS
pdf-approvals.php                310 checks   PASS
runner-media-validation.php       82 checks   PASS
editorial-decisions.php          374 checks   PASS   (Q02+Q06, sin regresion)
q05-pdf-resolution.php            68 checks   PASS
q01-content-ownership.php        299 checks   PASS   (Q01, sin regresion)
q04-static-products.php          219 checks   PASS   (actualizado -- ver abajo)
run-snapshot-retention.php        30 checks   PASS   (nuevo)
q07-landings.php                 113 checks   PASS   (nuevo)
q10-category-cleanup.php          23 checks   PASS   (nuevo)
q11-missing-media.php             31 checks   PASS   (nuevo)
q13-media-alias.php               14 checks   PASS   (nuevo)
──────────────────────────────────────────────────
TOTAL                          7.790 checks   0 fallos
```

## `tests/run-snapshot-retention.php` (nuevo)

Usa exclusivamente un directorio temporal aislado (`sys_get_temp_dir()`), nunca el
directorio privado real — fixture destruido al final de la ejecución, pase o falle.

| Caso pedido | Verificación |
|---|---|
| 0 runs → no-op | `deleted=[]`, `kept=0` |
| 10 runs → no elimina nada | `deleted=[]`, `kept=10` |
| 30 runs → no elimina nada | `deleted=[]`, `kept=30` |
| 31 runs → elimina sólo el más antiguo | `deleted` tiene exactamente 1 elemento, es el de menor mtime |
| 100 runs → conserva exactamente 30 | `deleted` tiene 70; los 30 supervivientes son exactamente los 30 de mtime más alto |
| identity/backup/decisiones preservados | 5 archivos de otros patrones (`identity-*.json`, `backup-*.json`, `log-*.jsonl`, `asset-*.bin`, `writer.lock`) sobreviven junto a 35 `run-*.json` (de los que se eliminan sólo 5) |
| filename parecido pero no run válido | `run-.json`, `runXYZ.json`, `run-abc123.txt`, `notrun-abc123.json` — ninguno se toca |
| orden estable con timestamps iguales | Dos fixtures idénticas (mismos nombres, mismo mtime exacto) producen la misma elección de eliminados ambas veces |
| fallo de delete → continúa y reporta warning | `unlink` inyectado que siempre devuelve `false`: 0 archivos perdidos realmente, 2 reportados en `failed`, la llamada retorna normalmente (nunca lanza) |

## `tests/q07-landings.php` (nuevo)

| Caso | Fixture real |
|---|---|
| 9 landings → CREATE draft | Las 9 de `editorial-decisions.json`, `target_type=page`, `planned_result=CREATE` |
| no publish | `post_status=draft` es la única ruta de escritura de `Runner::apply()` para un post nuevo |
| contenido preservado | Cuerpo no vacío, extraído de la propia landing, nunca de un producto |
| legacy URL mapping presente | `legacy_url` no vacío en las 9 entradas |
| media propia preservada | `images/cortinas.jpg` (exclusiva); `images/access.jpg`/`silent.jpg` (compartida sólo entre 3 landings hermanas) |
| media conflictiva → REVIEW | `images/banner1.jpg` (sitewide) y la ficha técnica de AccessPRO (PRODUCT_PAGE) — ninguna decidida por Q07 |
| ejecución repetida → estable | ≥17 filas `decision_id=Q07` comparadas entre dos reconstrucciones; 0 diferencias |

## `tests/q10-category-cleanup.php` (nuevo)

| Caso | Verificación |
|---|---|
| category 26 → SKIP | `decision_id=Q10`, `action=SKIP` |
| category 33 preservada | `action=MIGRATE`, `parent=category:5`, imagen propia asignada |
| mismo nombre no provoca MERGE | 26 y 33 comparten `name` en los datos fuente; acciones opuestas, ningún texto de "merge" |
| category 25 sin otro uso → SKIP | Verificado directamente: sin `legacy_page`, 0 enlaces frontend, sus 14 referencias son exactamente los IDs 151–164 de Q06 |
| category con uso público no incluida → intacta | `category:1` ("Industrial", página y productos reales) no lleva `decision_id=Q10`: la lista aprobada nunca se expande por parecido |

## `tests/q11-missing-media.php` (nuevo)

| Caso | Verificación |
|---|---|
| INFRACA Unicode equivalente → resuelto | `resolved_path` real, válido, hash coincide con el registrado; referencia y resolución son bytes distintos (la codificación es literalmente el problema) |
| archivo físicamente diferente → no resolver | Un nombre inventado similar no tiene caso Q11 alguno (control negativo) |
| dura.jpg / magic.jpg ausentes → `missing_media` registrado | `action=REVIEW`, `reason_code=MISSING_SOURCE_FILE`, nunca `MIGRATE` |
| sus productos continúan sin el blocker | `static:dura-glide...`/`static:magic-...` → `CREATE_FROM_STATIC`, galería vacía, nunca una imagen inventada |
| SELLOSSOLMMER parecido de nombre → no aprobado así | `resolved_path` explícitamente distinto de `SELLOSSOLMMER.pdf`; hashes distintos verificados; la resolución real depende de un cruce SQL/PMR ya existente en el repositorio, citado y verificado |

## `tests/q13-media-alias.php` (nuevo)

| Caso | Verificación |
|---|---|
| mismo SHA-256 → un attachment lógico | Ganador `MIGRATE`, alias `SKIP`, hashes verificados iguales de forma independiente |
| segunda legacy path preservada como alias | `binary_aliases` del ganador la incluye; el motivo del alias cita al ganador |
| mismo filename con bytes diferentes → NO dedupe | Una ruta sin relación `binary_alias` declarada nunca recibe decisión Q13, sea cual sea su nombre |
| ejecución repetida → estable | La única fila Q13 comparada entre dos reconstrucciones; 0 diferencias |

## Actualización necesaria en una suite existente

`tests/q04-static-products.php` afirmaba, de la fase anterior, que Dura-Glide y Magic
debían quedarse en `REVIEW` por su imagen ausente. Con la corrección de `media_union()`
(ver [15-q11-missing-media.md](15-q11-missing-media.md)) ambas ahora resuelven
correctamente a `CREATE_FROM_STATIC` con galería vacía — un comportamiento **mejor**, no
una regresión. Se actualizó la aserción para reflejarlo, añadiendo además la verificación
de que la referencia ausente sigue registrada, por separado, como su propia entidad
`REVIEW` — la separación explícita que pidió la tarea.

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
php -l <cada archivo PHP tocado o nuevo>
```

Ninguna ejecutó `Runner::batch()` sobre un plan `full` con éxito — todas verifican que
sigue rechazado.
