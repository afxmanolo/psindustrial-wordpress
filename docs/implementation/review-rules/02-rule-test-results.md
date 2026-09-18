# Resultados de pruebas

Suite nueva: [`tests/policy.php`](../../../wordpress/wp-content/plugins/psindustrial-core/tests/policy.php),
mismo patrón que `tests/importer.php` (CLI, `wp-load.php`, `$assert`/`$throws`, exporta
`docs/implementation/importer-reports/policy-tests.json`). Ejecutada contra
`psindustrial_wp_dev` local, PHP 8.4.24.

## Resultado

```
smoke.php:    todas las comprobaciones OK (suite preexistente, sin cambios)
importer.php: {"passed":true,"checks":67,...}   ← idéntico al baseline previo a esta fase
policy.php:   {"passed":true,"checks":6314,"review_before":1949,"review_after":950}
```

Las tres suites se ejecutaron varias veces de forma consecutiva con resultado idéntico
(determinismo confirmado). `smoke.php` e `importer.php` no se modificaron; se incluyen aquí
como confirmación de que **ninguna prueba existente se rompió**.

## Cobertura por regla (caso positivo / negativo / límite / debe-permanecer-REVIEW)

| Regla | Positivo | Negativo | Límite | Debe permanecer REVIEW |
|---|---|---|---|---|
| R-T01 | `category:14` → MIGRATE | `category:25/26/33` → sin decisión | `category:4` (raíz, padre vacío) → sin dependencia de padre | — |
| R-T03 | `brand:7` → MIGRATE | — (las 12 marcas satisfacen la condición; no existe caso real negativo) | invariante: ninguna decisión R-T03 incluye `categories` | — |
| R-P01 | `sql:productos:34` → MIGRATE con marca (CONFIRMED) | `sql:productos:150,6,16,20,107,14,57,43,81,44,123,125,135,139,3` → sin decisión (D03/D06) | `sql:productos:74` → MIGRATE sin marca (STRONG_INFERENCE) | `sql:productos:151..165` → sin decisión (vacíos/prueba) |
| R-G02-LOW | `php:contacto.php` → MIGRATE | `php:1500-revolving-door.php` (PRODUCT_PAGE) → sin decisión | — | `php:cortinas-enrollables-de-aluminio.php` (SEO_LANDING, MEDIUM) → sin decisión |
| R-M01 | imagen de producto 34 → MIGRATE | — | — | — |
| R-M05 | `asset:images/rytec.png` (logo marca 7) → MIGRATE | — | — | — |
| R-M04 | `asset:images/contact.jpg` (sólo contacto.php) → MIGRATE | — | — | — |
| R-M02 | derivado con original registrado → SKIP | invariante: ningún SKIP R-M02 tiene `original_ids` vacío | — | — |
| R-M03 | `system/backoffice/.../active-bg.gif`, `fonts/bootstrap-icons.woff` → SKIP | invariante: todo SKIP R-M03 coincide con un prefijo exacto, nunca parcial | — | `asset:system/files/images/categorias/0730f484...` (sin dueño, no derivado, bajo `system/files/`) → sin decisión |

## Guardas explícitas pedidas en el encargo

| Guarda | Cómo se verifica | Resultado |
|---|---|---|
| No eliminar originales | para cada SKIP con `rule_id=R-M02`, `original_ids` no vacío | 561/561 cumplen |
| No clasificar asset público como backoffice | invariante de prefijo exacto sobre las 285 filas R-M03 | 285/285 cumplen; ningún path bajo `images/`, `fichas/`, `multimedia/`, `system/files/` fue capturado |
| No asignar fabricante incierto | para cada `brand != ''` en decisiones R-P01, `product-master.brand_confidence === 'CONFIRMED'` | cumple en la única fila con marca asignada |
| No asignar categoría incierta | para cada decisión R-P01, la categoría asignada es ella misma una decisión R-T01 con `action=MIGRATE` | cumple en las 23 filas producidas |
| No duplicar attachments | unicidad de `entity_key`; unicidad de `file_id` entre todas las entidades media aprobadas | sin colisiones (0 file_ids compartidos) |
| No importar un `static_product` duplicado | ninguna clave `static:` recibe decisión | 0 claves `static:` en la salida de `Policy` |
| Regresión: alias binario | `asset:system/files/images/productos/9856...` (alias declarado del PDF manual) nunca recibe decisión propia | confirmado ausente |
| Reproducibilidad | `Policy::decisions()` llamada dos veces produce el mismo hash | idéntico |

## Verificación de las salvaguardas que no debían cambiar

| Salvaguarda | Prueba | Resultado |
|---|---|---|
| `subset` scope inalterado | `Planner::build('subset')` → 17 entradas, 2 REVIEW, sin `policy_rule_id` en ninguna | idéntico al baseline pre-política |
| Plan `full` sigue sin poder ejecutarse | `Runner::batch($full['run_id'], 'IMPORTAR SUBSET EN BORRADOR')` sobre un plan full con política activa | lanza `VALID_LOCAL_SUBSET_PLAN_REQUIRED`, capturado por `$throws` |
| Ningún MERGE | `summary.actions.MERGE === 0` en el plan full | confirmado |
| Decisión manual nunca confundida con política | `sql:productos:63` (manual) → sin `policy_rule_id`, `approval_ref` empieza por "Prompt 6" | confirmado |
| DRY RUN no muta contenido | recuento de posts/terms antes/después de `build('subset')` y de `build('full')` | idéntico en ambos casos |
| `Storage::guard()`, lotes, frase de confirmación, hashes, detección de edición humana, MERGE de ganador mixto | ningún archivo de `Runner.php`/`Storage.php`/`Identity.php` fue modificado | verificado por `git diff` — ver [verification en el resumen](00-low-rules-summary.md) |

## Explicabilidad

Para cada entrada del plan full con `policy_rule_id` establecido, se verificó que
`policy_reason_code`, `policy_evidence` y `policy_risk === 'LOW'` están también presentes
(contrato completo, nunca parcial), y que `policy_rule_id` corresponde siempre a una regla
registrada en `Policy::RULES`.
